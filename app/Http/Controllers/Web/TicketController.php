<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\TicketService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(
        protected TicketService $ticketService,
    ) {}

    public function index(Request $request)
    {
        $tickets = $this->ticketService->getCustomerTickets(
            userId: $request->user()->id,
            filters: $request->only(['status', 'priority', 'search']),
        );

        return view('dashboard', compact('tickets'));
    }

    public function create()
    {
        return view('tickets.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
        ]);

        $this->ticketService->create([
            'user_id' => $request->user()->id,
            ...$request->only('title', 'description', 'priority'),
            'status' => 'open',
            'source' => 'web',
        ]);

        return redirect()->route('dashboard')->with('success', 'Ticket created successfully.');
    }

    public function show(Request $request, int $id)
    {
        $ticket = $this->ticketService->findOrFail($id);

        if ($ticket->user_id !== $request->user()->id && ! $request->user()->hasRole('admin') && ! $request->user()->hasRole('agent')) {
            abort(403);
        }

        $replies = $this->ticketService->getReplies($id);

        return view('tickets.show', compact('ticket', 'replies'));
    }

    public function reply(Request $request, int $id)
    {
        $request->validate([
            'content' => ['required', 'string'],
        ]);

        $ticket = $this->ticketService->findOrFail($id);

        if ($ticket->user_id !== $request->user()->id && ! $request->user()->hasRole('admin') && ! $request->user()->hasRole('agent')) {
            abort(403);
        }

        $this->ticketService->addReply($ticket, [
            'user_id' => $request->user()->id,
            'content' => $request->content,
        ]);

        return back()->with('success', 'Reply added successfully.');
    }
}
