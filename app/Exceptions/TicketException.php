<?php

namespace App\Exceptions;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Exception;

class TicketException extends Exception
{
    public static function notFound(): self
    {
        return new self('Ticket not found.', 404);
    }

    public static function cannotModifyClosed(): self
    {
        return new self('Cannot modify a closed ticket.', 422);
    }

    public static function sameStatus(TicketStatus $status): self
    {
        return new self("Ticket is already {$status->value}.", 422);
    }

    public static function samePriority(TicketPriority $priority): self
    {
        return new self("Ticket already has {$priority->value} priority.", 422);
    }

    public static function invalidTransition(TicketStatus $from, TicketStatus $to): self
    {
        return new self(
            "Cannot transition ticket from '{$from->value}' to '{$to->value}'.",
            422
        );
    }

    public static function unauthorized(): self
    {
        return new self('You are not authorized to perform this action.', 403);
    }
}
