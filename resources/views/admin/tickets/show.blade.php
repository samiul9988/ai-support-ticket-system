@extends('layouts.app')
@section('title', 'Ticket #' . $ticket->id . ' - Admin')

@section('content')
<div class="max-w-3xl mx-auto">
    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
        Back to admin panel
    </a>

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6 mb-6">
        <div class="flex items-start justify-between gap-4 mb-4">
            <div>
                <div class="flex items-center gap-2 mb-2">
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
                <h1 class="text-xl font-bold text-gray-900">{{ $ticket->title }}</h1>
                <div class="mt-1 flex items-center gap-4 text-xs text-gray-400">
                    <span>by {{ $ticket->user->name }} ({{ $ticket->user->email }})</span>
                    <span>{{ $ticket->created_at->format('M d, Y h:i A') }}</span>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.tickets.delete', $ticket->id) }}" onsubmit="return confirm('Delete this ticket?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 transition cursor-pointer">Delete</button>
            </form>
        </div>

        <p class="text-sm text-gray-700 whitespace-pre-wrap bg-gray-50 rounded-lg p-4">{{ $ticket->description }}</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Update Status</h3>
            <form method="POST" action="{{ route('admin.tickets.status', $ticket->id) }}" class="space-y-3">
                @csrf
                @method('PATCH')
                <select name="status" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition">
                    <option value="open" {{ $ticket->status->value === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="in_progress" {{ $ticket->status->value === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="resolved" {{ $ticket->status->value === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ $ticket->status->value === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
                <input name="note" type="text" placeholder="Optional note" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition">
                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 transition cursor-pointer">Update Status</button>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Update Priority</h3>
            <form method="POST" action="{{ route('admin.tickets.priority', $ticket->id) }}" class="space-y-3">
                @csrf
                @method('PATCH')
                <select name="priority" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition">
                    <option value="low" {{ $ticket->priority->value === 'low' ? 'selected' : '' }}>Low</option>
                    <option value="medium" {{ $ticket->priority->value === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="high" {{ $ticket->priority->value === 'high' ? 'selected' : '' }}>High</option>
                    <option value="urgent" {{ $ticket->priority->value === 'urgent' ? 'selected' : '' }}>Urgent</option>
                </select>
                <button type="submit" class="w-full rounded-lg bg-gray-700 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-600 transition cursor-pointer">Update Priority</button>
            </form>
        </div>
    </div>

    @if($replies->isNotEmpty())
    <div class="space-y-4 mb-6">
        <h3 class="text-sm font-semibold text-gray-900">Conversation</h3>
        @foreach($replies as $reply)
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-5 {{ $reply->is_ai_generated ? 'border-l-4 border-l-purple-400' : '' }}">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-900">{{ $reply->user->name }}</span>
                    @if($reply->is_ai_generated)
                    <span class="inline-flex items-center rounded bg-purple-50 px-1.5 py-0.5 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-600/20">AI</span>
                    @endif
                </div>
                <span class="text-xs text-gray-400">{{ $reply->created_at->diffForHumans() }}</span>
            </div>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $reply->content }}</p>
        </div>
        @endforeach
    </div>
    @endif

    @if(!$ticket->isClosed())
    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Send Reply</h3>
        <form method="POST" action="{{ route('admin.tickets.reply', $ticket->id) }}">
            @csrf
            <textarea name="content" rows="3" required
                placeholder="Type your reply to the customer..."
                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition resize-y mb-3"></textarea>
            <button type="submit"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 transition cursor-pointer">
                Send Reply
            </button>
        </form>
    </div>
    @endif
</div>
@endsection
