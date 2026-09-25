<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
use App\Models\Room;

Broadcast::channel('sala.{roomId}', function ($user, $roomId) {
    $room = Room::find($roomId);

    return $room ? $room->isParticipante($user->id) : false;
});
