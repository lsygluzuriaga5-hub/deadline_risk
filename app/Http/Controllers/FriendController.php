<?php

namespace App\Http\Controllers;

use App\Models\FriendMessage;
use App\Models\FriendRequest as FriendRequestModel;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FriendController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $user->forceFill(['last_seen_at' => now()])->save();
        $requests = FriendRequestModel::query()
            ->with(['sender', 'receiver'])
            ->where(fn ($query) => $query
                ->where('sender_id', $user->id)
                ->orWhere('receiver_id', $user->id))
            ->get();

        $friends = $requests
            ->where('status', 'accepted')
            ->map(fn (FriendRequestModel $friendRequest): User => $friendRequest->sender_id === $user->id
                ? $friendRequest->receiver
                : $friendRequest->sender)
            ->values();

        $friendIds = $friends->pluck('id');
        $messages = FriendMessage::query()
            ->where(fn ($query) => $query->where('sender_id', $user->id)->whereIn('receiver_id', $friendIds))
            ->orWhere(fn ($query) => $query->where('receiver_id', $user->id)->whereIn('sender_id', $friendIds))
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn (FriendMessage $message): int => $message->sender_id === $user->id ? $message->receiver_id : $message->sender_id);

        $friends->each(function (User $friend) use ($messages): void {
            $friend->setAttribute('online', $friend->last_seen_at?->greaterThan(now()->subMinutes(5)) ?? false);
            $friend->setAttribute('chat_messages', $messages->get($friend->id, collect()));
        });

        $friendSearch = trim($request->string('q')->toString());
        $discoverableUsers = collect();

        if (mb_strlen($friendSearch) >= 2) {
            $searchPattern = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $friendSearch).'%';

            $discoverableUsers = User::query()
                ->where('id', '!=', $user->id)
                ->where(function ($query) use ($searchPattern): void {
                    $query->where('name', 'like', $searchPattern)
                        ->orWhere('nickname', 'like', $searchPattern)
                        ->orWhere('email', 'like', $searchPattern);
                })
                ->whereDoesntHave('sentFriendRequests', fn ($query) => $query->whereIn('status', ['accepted', 'pending']))
                ->whereDoesntHave('receivedFriendRequests', fn ($query) => $query->whereIn('status', ['accepted', 'pending']))
                ->orderBy('name')
                ->limit(20)
                ->get();
        }

        return view('friends.index', [
            'friends' => $friends,
            'incomingRequests' => $requests->where('status', 'pending')->where('receiver_id', $user->id)->values(),
            'outgoingRequests' => $requests->where('status', 'pending')->where('sender_id', $user->id)->values(),
            'friendSearch' => $friendSearch,
            'discoverableUsers' => $discoverableUsers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);
        $sender = $request->user();
        $receiver = User::where('email', $validated['email'])->firstOrFail();

        if ($receiver->is($sender)) {
            return back()->withErrors(['email' => 'You cannot send a friend request to yourself.']);
        }

        $existingRequests = FriendRequestModel::query()
            ->where(fn ($query) => $query
                ->where(fn ($query) => $query->where('sender_id', $sender->id)->where('receiver_id', $receiver->id))
                ->orWhere(fn ($query) => $query->where('sender_id', $receiver->id)->where('receiver_id', $sender->id)))
            ->get();

        if ($existingRequests->contains('status', 'accepted')) {
            return back()->withErrors(['email' => 'You are already friends with this student.']);
        }

        if ($existingRequests->contains(fn (FriendRequestModel $friendRequest): bool => $friendRequest->status === 'pending' && $friendRequest->sender_id === $receiver->id)) {
            return back()->withErrors(['email' => 'This student has already sent you a request. Review it below.']);
        }

        $outgoingRequest = $existingRequests->first(fn (FriendRequestModel $friendRequest): bool => $friendRequest->status === 'pending' && $friendRequest->sender_id === $sender->id);

        if ($outgoingRequest) {
            return back()->withErrors(['email' => 'You have already sent this student a request.']);
        }

        DB::transaction(function () use ($existingRequests, $receiver, $sender): void {
            $declinedRequest = $existingRequests->firstWhere('status', 'declined');

            if ($declinedRequest) {
                $declinedRequest->update([
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                    'status' => 'pending',
                ]);

                return;
            }

            $sender->sentFriendRequests()->create([
                'receiver_id' => $receiver->id,
                'status' => 'pending',
            ]);
        });

        return redirect()->route('friends.index')->with('success', 'Friend request sent to '.$receiver->name.'.');
    }

    public function respond(Request $request, FriendRequestModel $friendRequest): RedirectResponse
    {
        abort_unless($friendRequest->receiver_id === $request->user()->id && $friendRequest->status === 'pending', 404);

        $validated = $request->validate([
            'action' => ['required', 'in:accept,decline'],
        ]);

        $friendRequest->update([
            'status' => $validated['action'] === 'accept' ? 'accepted' : 'declined',
        ]);

        return redirect()->route('friends.index')->with(
            'success',
            $validated['action'] === 'accept' ? 'Friend request accepted.' : 'Friend request declined.',
        );
    }
}
