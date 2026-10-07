<?php

namespace App\Http\Controllers;

use App\Models\FriendMessage;
use App\Models\FriendRequest as FriendRequestModel;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FriendMessageController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['friend_id' => ['required', 'integer', 'exists:users,id'], 'body' => ['required', 'string', 'max:2000']]);
        $friend = User::findOrFail($validated['friend_id']);
        $areFriends = FriendRequestModel::query()->where('status', 'accepted')->where(function ($query) use ($request, $friend) {
            $query->where(fn ($pair) => $pair->where('sender_id', $request->user()->id)->where('receiver_id', $friend->id))
                ->orWhere(fn ($pair) => $pair->where('sender_id', $friend->id)->where('receiver_id', $request->user()->id));
        })->exists();
        abort_unless($areFriends, 403);
        FriendMessage::create(['sender_id' => $request->user()->id, 'receiver_id' => $friend->id, 'body' => $validated['body']]);

        return back()->with('success', 'Message sent.');
    }
}
