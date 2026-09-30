<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

/**
 * A customer may only ever see and touch their OWN tickets.
 * Admin access is granted by the /reddy admin middleware, not by this
 * policy — this policy guards the customer-facing routes.
 */
class TicketPolicy
{
    /**
     * View the ticket thread.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        return $ticket->user_id === $user->id;
    }

    /**
     * Reply inside the ticket thread.
     */
    public function reply(User $user, Ticket $ticket): bool
    {
        return $ticket->user_id === $user->id;
    }
}
