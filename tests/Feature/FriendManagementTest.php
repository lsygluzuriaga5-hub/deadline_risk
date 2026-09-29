<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FriendManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_send_accept_and_view_friend_request(): void
    {
        $student = User::factory()->create();
        $friend = User::factory()->create();

        $this->actingAs($student)
            ->post(route('friends.requests.store'), ['email' => $friend->email])
            ->assertRedirect(route('friends.index'));

        $this->assertDatabaseHas('friend_requests', [
            'sender_id' => $student->id,
            'receiver_id' => $friend->id,
            'status' => 'pending',
        ]);

        $requestId = (int) DB::table('friend_requests')->value('id');

        $this->actingAs($friend)
            ->get(route('friends.index'))
            ->assertSee($student->name)
            ->assertSee('Accept');

        $this->post(route('friends.requests.respond', $requestId), ['action' => 'accept'])
            ->assertRedirect(route('friends.index'));

        $this->assertDatabaseHas('friend_requests', [
            'id' => $requestId,
            'status' => 'accepted',
        ]);

        $this->actingAs($student)
            ->get(route('friends.index'))
            ->assertSee($friend->name)
            ->assertSee('Your friends');
    }

    public function test_student_cannot_respond_to_another_users_friend_request(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($sender)
            ->post(route('friends.requests.store'), ['email' => $receiver->email]);

        $requestId = (int) DB::table('friend_requests')->value('id');

        $this->actingAs($stranger)
            ->post(route('friends.requests.respond', $requestId), ['action' => 'accept'])
            ->assertNotFound();

        $this->assertDatabaseHas('friend_requests', [
            'id' => $requestId,
            'status' => 'pending',
        ]);
    }

    public function test_student_cannot_send_friend_request_to_self_or_duplicate(): void
    {
        $student = User::factory()->create();
        $friend = User::factory()->create();

        $this->actingAs($student)
            ->from(route('friends.index'))
            ->post(route('friends.requests.store'), ['email' => $student->email])
            ->assertRedirect(route('friends.index'))
            ->assertSessionHasErrors('email');

        $this->post(route('friends.requests.store'), ['email' => $friend->email]);

        $this->from(route('friends.index'))
            ->post(route('friends.requests.store'), ['email' => $friend->email])
            ->assertRedirect(route('friends.index'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('friend_requests', 1);
    }

    public function test_student_can_resend_a_declined_friend_request(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $this->actingAs($sender)
            ->post(route('friends.requests.store'), ['email' => $receiver->email]);

        $requestId = (int) DB::table('friend_requests')->value('id');

        $this->actingAs($receiver)
            ->post(route('friends.requests.respond', $requestId), ['action' => 'decline'])
            ->assertRedirect(route('friends.index'));

        $this->actingAs($sender)
            ->post(route('friends.requests.store'), ['email' => $receiver->email])
            ->assertRedirect(route('friends.index'));

        $this->assertDatabaseHas('friend_requests', [
            'id' => $requestId,
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseCount('friend_requests', 1);
    }
}
