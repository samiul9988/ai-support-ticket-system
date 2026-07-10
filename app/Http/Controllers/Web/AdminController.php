<?php

namespace App\Http\Controllers\Web;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Services\TicketService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        protected TicketService $ticketService,
    ) {}

    public function index(Request $request)
    {
        $tickets = $this->ticketService->list(
            filters: $request->only(['status', 'priority', 'search']),
        );

        $stats = $this->ticketService->getStats();

        return view('admin.dashboard', compact('tickets', 'stats'));
    }

    public function showTicket(int $id)
    {
        $ticket = $this->ticketService->findOrFail($id);
        $replies = $this->ticketService->getReplies($id);

        return view('admin.tickets.show', compact('ticket', 'replies'));
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => ['required', 'string', 'in:open,in_progress,resolved,closed'],
        ]);

        $ticket = $this->ticketService->findOrFail($id);
        $this->ticketService->changeStatus(
            $ticket,
            TicketStatus::from($request->status),
            $request->note,
        );

        return back()->with('success', 'Status updated successfully.');
    }

    public function updatePriority(Request $request, int $id)
    {
        $request->validate([
            'priority' => ['required', 'string', 'in:low,medium,high,urgent'],
        ]);

        $ticket = $this->ticketService->findOrFail($id);
        $this->ticketService->changePriority(
            $ticket,
            TicketPriority::from($request->priority),
        );

        return back()->with('success', 'Priority updated successfully.');
    }

    public function reply(Request $request, int $id)
    {
        $request->validate([
            'content' => ['required', 'string'],
        ]);

        $ticket = $this->ticketService->findOrFail($id);
        $this->ticketService->addReply($ticket, [
            'user_id' => $request->user()->id,
            'content' => $request->content,
        ]);

        return back()->with('success', 'Reply sent successfully.');
    }

    public function deleteTicket(int $id)
    {
        $ticket = $this->ticketService->findOrFail($id);
        $this->ticketService->delete($ticket);

        return redirect()->route('admin.dashboard')->with('success', 'Ticket deleted successfully.');
    }
}
