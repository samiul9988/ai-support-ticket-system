<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SupportDesk</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="h-screen bg-gray-50 font-sans antialiased">
    <div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <svg class="mx-auto h-16 w-16 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
            </svg>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-gray-900">SupportDesk</h1>
            <p class="mt-2 text-base text-gray-500">AI-powered customer support ticket system</p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white px-6 py-8 shadow-sm ring-1 ring-gray-900/5 rounded-xl sm:px-10 text-center space-y-4">
                @auth
                    @if(auth()->user()->hasRole(['admin', 'agent']))
                        <a href="{{ route('admin.dashboard') }}" class="block w-full rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                            Admin Panel
                        </a>
                    @endif
                    <a href="{{ route('dashboard') }}" class="block w-full rounded-lg bg-gray-800 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-gray-700 transition">
                        My Tickets
                    </a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 transition cursor-pointer">Sign Out</button>
                    </form>
                @else
                    <div class="space-y-3">
                        <a href="{{ route('login') }}" class="block w-full rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                            Sign In
                        </a>
                        <a href="{{ route('register') }}" class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition">
                            Create Account
                        </a>
                    </div>
                @endauth
            </div>

            <div class="mt-8 grid grid-cols-3 gap-4 text-center">
                <div class="rounded-lg bg-white p-3 shadow-sm ring-1 ring-gray-900/5">
                    <p class="text-lg font-bold text-indigo-600">Create</p>
                    <p class="text-xs text-gray-500">Submit tickets</p>
                </div>
                <div class="rounded-lg bg-white p-3 shadow-sm ring-1 ring-gray-900/5">
                    <p class="text-lg font-bold text-indigo-600">Track</p>
                    <p class="text-xs text-gray-500">Monitor status</p>
                </div>
                <div class="rounded-lg bg-white p-3 shadow-sm ring-1 ring-gray-900/5">
                    <p class="text-lg font-bold text-indigo-600">Resolve</p>
                    <p class="text-xs text-gray-500">Get solutions</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
