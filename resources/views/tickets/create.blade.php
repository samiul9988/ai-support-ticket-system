@extends('layouts.app')
@section('title', 'Create Ticket')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Create New Support Ticket</h1>

    <form method="POST" action="{{ route('tickets.store') }}" class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6 space-y-6">
        @csrf
        <div>
            <label for="title" class="block text-sm font-medium text-gray-900">Title</label>
            <input id="title" name="title" type="text" value="{{ old('title') }}" required
                placeholder="Brief summary of your issue"
                class="mt-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition">
        </div>
        <div>
            <label for="description" class="block text-sm font-medium text-gray-900">Description</label>
            <textarea id="description" name="description" rows="5" required
                placeholder="Describe your issue in detail..."
                class="mt-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition resize-y">{{ old('description') }}</textarea>
        </div>
        <div>
            <label for="priority" class="block text-sm font-medium text-gray-900">Priority</label>
            <select id="priority" name="priority" required
                class="mt-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition">
                <option value="low">Low - Minor issue</option>
                <option value="medium" selected>Medium - Needs attention</option>
                <option value="high">High - Significant impact</option>
                <option value="urgent">Urgent - Critical issue</option>
            </select>
        </div>
        <div class="flex justify-end gap-3">
            <a href="{{ route('dashboard') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition cursor-pointer">Cancel</a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 transition cursor-pointer">Submit Ticket</button>
        </div>
    </form>
</div>
@endsection
