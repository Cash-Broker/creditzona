<?php

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;

class CalendarEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    public function view(User $user, CalendarEvent $calendarEvent): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, CalendarEvent $calendarEvent): bool
    {
        return $user->isAdmin()
            || $calendarEvent->user_id === $user->id
            || $calendarEvent->created_by_user_id === $user->id;
    }

    public function delete(User $user, CalendarEvent $calendarEvent): bool
    {
        return $this->update($user, $calendarEvent);
    }
}
