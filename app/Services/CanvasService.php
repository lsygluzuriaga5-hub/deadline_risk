<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CanvasService
{
    private string $baseUrl;

    private string $clientId;

    private string $clientSecret;

    private string $localApiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.canvas.url'), '/');
        $this->clientId = (string) config('services.canvas.client_id');
        $this->clientSecret = (string) config('services.canvas.client_secret');
        $this->localApiKey = (string) config('services.canvas.api_key');
    }

    public function authorizationUrl(string $redirectUri, string $state): string
    {
        $this->ensureOAuthConfigured();

        return $this->baseUrl.'/login/oauth2/auth?'.http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);
    }

    public function exchangeAuthorizationCode(User $user, string $code, string $redirectUri): void
    {
        $response = $this->tokenRequest([
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);

        $this->storeTokenResponse($user, $response, false);
    }

    public function getCurrentUser(User $user): array
    {
        return $this->request($user, '/api/v1/users/self');
    }

    public function getCourses(User $user): array
    {
        return $this->getPaginated($user, '/api/v1/users/self/courses', [
            'per_page' => 100,
            'enrollment_state' => 'active',
        ]);
    }

    public function getAssignments(User $user, int|string $courseId): array
    {
        return $this->getPaginated(
            $user,
            "/api/v1/courses/{$courseId}/assignments",
            [
                'per_page' => 100,
                'order_by' => 'due_at',
            ]
        );
    }

    public function submitTextAnswer(User $user, string $courseId, string $assignmentId, string $body): void
    {
        if (! ctype_digit($courseId) || ! ctype_digit($assignmentId)) {
            throw new RuntimeException('This task is missing a valid Canvas course or assignment ID. Sync Canvas and try again.');
        }

        $assignmentEndpoint = "/api/v1/courses/{$courseId}/assignments/{$assignmentId}";
        $assignment = $this->request($user, $assignmentEndpoint);
        $submissionTypes = $assignment['submission_types'] ?? [];

        if (! is_array($submissionTypes) || ! in_array('online_text_entry', $submissionTypes, true)) {
            throw new RuntimeException('This Canvas assignment does not accept online text-entry submissions.');
        }

        $response = $this->postResponse(
            $this->client($user),
            $this->baseUrl.$assignmentEndpoint.'/submissions',
            [
                'submission' => [
                    'submission_type' => 'online_text_entry',
                    'body' => $body,
                ],
            ]
        );

        $this->checkApiResponse($response);
    }

    public function disconnect(User $user): void
    {
        $accessToken = $user->canvas_access_token;

        if (filled($accessToken) && $this->baseUrl !== '') {
            try {
                Http::withToken($accessToken)
                    ->acceptJson()
                    ->timeout(15)
                    ->delete($this->baseUrl.'/login/oauth2/token');
            } catch (ConnectionException) {
                // Local credentials are still cleared if Canvas is unreachable.
            }
        }

        $user->forceFill([
            'canvas_user_id' => null,
            'canvas_user_name' => null,
            'canvas_access_token' => null,
            'canvas_refresh_token' => null,
            'canvas_token_expires_at' => null,
        ])->save();
    }

    private function request(User $user, string $endpoint, array $query = []): array
    {
        $response = $this->getResponse($this->client($user), $this->baseUrl.$endpoint, $query);

        $this->checkApiResponse($response);

        return $response->json();
    }

    private function getPaginated(User $user, string $endpoint, array $query = []): array
    {
        $results = [];
        $url = $this->baseUrl.$endpoint;
        $firstRequest = true;

        while ($url !== null) {
            $response = $firstRequest
                ? $this->getResponse($this->client($user), $url, $query)
                : $this->getResponse($this->client($user), $url);

            $firstRequest = false;
            $this->checkApiResponse($response);

            $data = $response->json();

            if (is_array($data)) {
                $results = array_merge($results, $data);
            }

            $url = $this->nextPageUrl($response->header('Link'));
        }

        return $results;
    }

    private function client(User $user): PendingRequest
    {
        $this->ensureCanvasUrlConfigured();

        $accessToken = $user->canvas_access_token;

        if (! filled($accessToken)) {
            if (app()->environment('local') && filled($this->localApiKey)) {
                return $this->apiClient($this->localApiKey);
            }

            throw new RuntimeException('Connect your Canvas account before syncing.');
        }

        if ($user->canvas_token_expires_at?->lte(now()->addMinute())) {
            $accessToken = $this->refreshAccessToken($user);
        }

        return $this->apiClient($accessToken);
    }

    private function apiClient(string $accessToken): PendingRequest
    {
        return Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(30);
    }

    private function refreshAccessToken(User $user): string
    {
        if (! filled($user->canvas_refresh_token)) {
            throw new RuntimeException('Your Canvas authorization has expired. Connect Canvas again.');
        }

        $response = $this->tokenRequest([
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => (string) config('services.canvas.redirect_uri') ?: route('canvas.callback'),
            'refresh_token' => $user->canvas_refresh_token,
        ]);

        $this->storeTokenResponse($user, $response, true);

        return $user->canvas_access_token;
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array<string, mixed>
     */
    private function tokenRequest(array $parameters): array
    {
        $this->ensureOAuthConfigured();

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(30)
                ->post($this->baseUrl.'/login/oauth2/token', $parameters);
        } catch (ConnectionException) {
            throw new RuntimeException('Could not connect to Canvas. Check the Canvas URL and your network connection.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('Canvas authorization failed (HTTP '.$response->status().').');
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Canvas returned an invalid authorization response.');
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function storeTokenResponse(User $user, array $response, bool $preserveRefreshToken): void
    {
        if (! filled($response['access_token'] ?? null)) {
            throw new RuntimeException('Canvas did not return an access token.');
        }

        $userData = is_array($response['user'] ?? null) ? $response['user'] : [];
        $refreshToken = $response['refresh_token'] ?? ($preserveRefreshToken ? $user->canvas_refresh_token : null);

        $user->forceFill([
            'canvas_user_id' => isset($userData['id']) ? (string) $userData['id'] : $user->canvas_user_id,
            'canvas_user_name' => $userData['name'] ?? $user->canvas_user_name,
            'canvas_access_token' => $response['access_token'],
            'canvas_refresh_token' => $refreshToken,
            'canvas_token_expires_at' => isset($response['expires_in'])
                ? now()->addSeconds((int) $response['expires_in'])
                : null,
        ])->save();
    }

    private function nextPageUrl(?string $linkHeader): ?string
    {
        if (! $linkHeader) {
            return null;
        }

        foreach (explode(',', $linkHeader) as $link) {
            if (preg_match('/<([^>]+)>;\s*rel="next"/', $link, $matches)) {
                $nextUrl = $matches[1];
                $baseOrigin = parse_url($this->baseUrl, PHP_URL_SCHEME).'://'.parse_url($this->baseUrl, PHP_URL_HOST);
                $nextOrigin = parse_url($nextUrl, PHP_URL_SCHEME).'://'.parse_url($nextUrl, PHP_URL_HOST);

                if ($baseOrigin !== $nextOrigin) {
                    throw new RuntimeException('Canvas returned an invalid pagination URL.');
                }

                return $nextUrl;
            }
        }

        return null;
    }

    private function checkApiResponse(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        if ($response->status() === 401) {
            throw new RuntimeException('Canvas rejected the access token. Check the token or reconnect your account.');
        }

        if ($response->status() === 403) {
            throw new RuntimeException('Canvas denied access to your courses or assignments.');
        }

        throw new RuntimeException('Canvas API error: HTTP '.$response->status().'.');
    }

    private function ensureCanvasUrlConfigured(): void
    {
        if ($this->baseUrl === '') {
            throw new RuntimeException('Canvas URL is not configured. Set CANVAS_URL.');
        }
    }

    private function ensureOAuthConfigured(): void
    {
        $this->ensureCanvasUrlConfigured();

        if ($this->clientId === '' || $this->clientSecret === '') {
            throw new RuntimeException('Canvas OAuth is not configured. Set CANVAS_URL, CANVAS_CLIENT_ID, and CANVAS_CLIENT_SECRET.');
        }
    }

    private function getResponse(PendingRequest $client, string $url, array $query = []): Response
    {
        try {
            return $client->get($url, $query);
        } catch (ConnectionException) {
            throw new RuntimeException('Could not connect to Canvas. Check the Canvas URL and your network connection.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function postResponse(PendingRequest $client, string $url, array $data): Response
    {
        try {
            return $client->asForm()->post($url, $data);
        } catch (ConnectionException) {
            throw new RuntimeException('Could not connect to Canvas. Check the Canvas URL and your network connection.');
        }
    }
}
