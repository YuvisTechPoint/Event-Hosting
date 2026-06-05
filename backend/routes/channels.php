<?php

use HiEvents\Models\User;
use HiEvents\Repository\Interfaces\EventRepositoryInterface;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Private event channels require an authenticated organizer/staff user whose
| account owns the event. Public capacity channels (event.{id}.capacity) are
| not registered here — they are open for ticket purchase pages.
|
*/

Broadcast::channel('event.{eventId}', function (User $user, int $eventId) {
    try {
        $accountId = auth()->payload()->get('account_id');
    } catch (Throwable) {
        return false;
    }

    if (!$accountId) {
        return false;
    }

    $event = app(EventRepositoryInterface::class)->findById($eventId);

    return $event !== null && $event->getAccountId() === (int) $accountId;
});
