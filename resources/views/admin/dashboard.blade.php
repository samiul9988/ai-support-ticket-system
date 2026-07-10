@extends('layouts.app')
@section('title', 'Admin Panel')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">Ticket Management</h1>

@if($stats->isNotEmpty())
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label' => 'Total Tickets', 'key' => 'total', 'color' => 'text-gray-900'],
        ['label' => 'Open', 'key' => 'open', 'color' => 'text-blue-600'],
        ['label' => 'In Progress', 'key' => 'in_progress', 'color' => 'text-amber-600'],
        ['label' => 'Resolved', 'key' => 'resolved', 'color' => 'text-green-600'],
        ['label' => 'Closed', 'key' => 'closed', 'color' => 'text-gray-600'],
        ['label' => 'Unassigned', 'key' => 'unassigned', 'color' => 'text-orange-600'],
        ['label' => 'Urgent', 'key' => 'urgent', 'color' => 'text-red-600'],
        ['label' => 'Created Today', 'key' => 'created_today', 'color' => 'text-indigo-600'],
    ] as $item)
    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-4">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $item['label'] }}</p>
        <p class="mt-1 text-2xl font-bold {{ $item['color'] }}">{{ $stats->get($item['key'], 0) }}</p>
    </div>
    @endforeach
</div>
@endif

@if($tickets->isEmpty())
<div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-12 text-center">
    <h3 class="text-sm font-semibold text-gray-900">No tickets found</h3>
    <p class="mt-1 text-sm text-gray-500">All tickets will appear here.</p>
</div>
@else
<div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($tickets as $ticket)
            <tr class="hover:bg-gray-50 transition cursor-pointer" onclick="window.location='{{ route('admin.tickets.show', $ticket->id) }}'">
                <td class="px-6 py-4 text-sm font-medium text-gray-900">#{{ $ticket->id }}</td>
                <td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate">{{ $ticket->title }}</td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ $ticket->user->name }}</td>
                <td class="px-6 py-4">
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
                </td>
                <td class="px-6 py-4">
                    <span class="@php
                        match($ticket->priority->value) {
                            'low' => 'text-gray-500',
                            'medium' => 'text-blue-600',
                            'high' => 'text-amber-600',
                            'urgent' => 'text-red-600 font-semibold',
                            default => 'text-gray-500',
                        }
                    @endphp text-xs font-medium">
                        {{ ucfirst($ticket->priority->value) }}
                    </span>
                </td>
                <td class="px-6 py-4 text-xs text-gray-400 whitespace-nowrap">{{ $ticket->created_at->format('M d, Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-6">
    {{ $tickets->links() }}
</div>
@endif
@endsection
