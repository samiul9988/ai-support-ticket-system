@extends('layouts.app')
@section('title', 'Ticket #' . $ticket->id)

@section('content')
<div class="max-w-3xl mx-auto">
    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
        Back to tickets
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
            </div>
            <span class="text-xs text-gray-400 whitespace-nowrap">{{ $ticket->created_at->format('M d, Y h:i A') }}</span>
        </div>

        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $ticket->description }}</p>

        @if($ticket->assignedAgent)
        <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-400">Assigned to: <span class="font-medium text-gray-600">{{ $ticket->assignedAgent->name }}</span></p>
        </div>
        @endif
    </div>

    @if($replies->isNotEmpty())
    <div class="space-y-4 mb-6">
        <h3 class="text-sm font-semibold text-gray-900">Replies</h3>
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
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Add Reply</h3>
        <form method="POST" action="{{ route('tickets.reply', $ticket->id) }}">
            @csrf
            <textarea name="content" rows="3" required
                placeholder="Type your reply..."
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
