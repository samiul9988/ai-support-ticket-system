@extends('layouts.app')
@section('title', 'Login - SupportDesk')
@section('auth-title', 'Sign in to your account')

@section('content')
<form method="POST" action="{{ route('login') }}" class="space-y-5">
    @csrf
    <div>
        <label for="email" class="block text-sm font-medium text-gray-900">Email address</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
            class="mt-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition">
    </div>
    <div>
        <label for="password" class="block text-sm font-medium text-gray-900">Password</label>
        <input id="password" name="password" type="password" required
            class="mt-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition">
    </div>
    <button type="submit"
        class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-300 transition cursor-pointer">
        Sign in
    </button>
</form>
<p class="mt-6 text-center text-sm text-gray-500">
    Don't have an account?
    <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">Sign up</a>
</p>
@endsection
