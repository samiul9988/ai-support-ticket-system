@extends('layouts.app')
@section('title', 'My Tickets')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">My Support Tickets</h1>
    <a href="{{ route('tickets.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 transition">
        + New Ticket
    </a>
</div>

@if($tickets->isEmpty())
<div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-12 text-center">
    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" /></svg>
    <h3 class="mt-4 text-sm font-semibold text-gray-900">No tickets yet</h3>
    <p class="mt-1 text-sm text-gray-500">Create your first support ticket to get started.</p>
    <a href="{{ route('tickets.create') }}" class="mt-4 inline-block rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 transition">Create Ticket</a>
</div>
@else
<div class="space-y-4">
    @foreach($tickets as $ticket)
    <a href="{{ route('tickets.show', $ticket->id) }}" class="block bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-5 hover:shadow-md transition">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="@php
                        match($ticket->status->value) {
                            'open' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                            'in_progress' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                            'resolved' => 'bg-green-50 text-green-700 ring-green-600/20',
                            'closed' => 'bg-gray-100 text-gray-600 ring-gray-500/20',
                            default => 'bg-gray-50 text-gray-600 ring-gray-500/20',
                        }
                    @endphp inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset">
                        {{ ucfirst(str_replace('_', ' ', $ticket->status->value)) }}
                    </span>
                    <span class="@php
                        match($ticket->priority->value) {
                            'low' => 'text-gray-500',
                            'medium' => 'text-blue-600',
                            'high' => 'text-amber-600',
                            'urgent' => 'text-red-600',
                            default => 'text-gray-500',
                        }
                    @endphp text-xs font-medium">
                        {{ ucfirst($ticket->priority->value) }} priority
                    </span>
                </div>
                <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $ticket->title }}</h3>
                <p class="mt-1 text-sm text-gray-500 line-clamp-2">{{ Str::limit($ticket->description, 120) }}</p>
                @if($ticket->assignedAgent)
                <p class="mt-2 text-xs text-gray-400">Assigned to: {{ $ticket->assignedAgent->name }}</p>
                @endif
            </div>
            <span class="text-xs text-gray-400 whitespace-nowrap">{{ $ticket->created_at->diffForHumans() }}</span>
        </div>
    </a>
    @endforeach
</div>
<div class="mt-6">
    {{ $tickets->links() }}
</div>
@endif
@endsection
