<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FriendMessage extends Model
{
    protected $fillable = ['sender_id', 'receiver_id', 'body'];
}
