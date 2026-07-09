<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin() || $user->isAgent()) {
            return true;
        }

        return $user->hasPermission('ticket.view-own');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->hasPermission('ticket.view-all')) {
            return true;
        }

        if ($user->hasPermission('ticket.view-assigned') && $ticket->assigned_to === $user->id) {
            return true;
        }

        if ($user->hasPermission('ticket.view-own') && $ticket->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('ticket.create');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($ticket->isClosed()) {
            return false;
        }

        if ($user->hasPermission('ticket.update') && $ticket->assigned_to === $user->id) {
            return true;
        }

        if ($user->hasPermission('ticket.update')
            && $ticket->user_id === $user->id
            && $ticket->isOpen()) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission('ticket.delete');
    }

    public function assignAgent(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission('ticket.assign');
    }

    public function changeStatus(User $user, Ticket $ticket): bool
    {
        if (! $user->hasPermission('ticket.change-status')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgent() && $ticket->assigned_to === $user->id) {
            return true;
        }

        return false;
    }

    public function changePriority(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission('ticket.change-status')
            && ($user->isAdmin() || ($user->isAgent() && $ticket->assigned_to === $user->id));
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        if (! $user->hasPermission('ticket.reply')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgent() && $ticket->assigned_to === $user->id) {
            return true;
        }

        if ($user->isCustomer() && $ticket->user_id === $user->id) {
            return true;
        }

        return false;
    }
}
