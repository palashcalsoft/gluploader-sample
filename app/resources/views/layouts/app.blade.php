<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'App')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-dvh bg-gray-50 text-gray-900">
    <div class="flex min-h-dvh">
        <aside id="sidebar" class="group/sidebar hidden w-64 shrink-0 border-r border-gray-200 bg-white md:block transition-[width] duration-200">
            <div class="flex h-14 items-center justify-between px-4">
                <a href="{{ route('home') }}" class="text-base font-semibold">GL Uploader</a>
                <button id="collapseBtn" class="hidden rounded-md p-2 text-gray-500 hover:bg-gray-100 md:block" aria-label="Collapse sidebar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5"><path fill-rule="evenodd" d="M3.75 5.25a.75.75 0 01.75-.75h15a.75.75 0 010 1.5h-15a.75.75 0 01-.75-.75zm0 7.5a.75.75 0 01.75-.75h15a.75.75 0 010 1.5h-15a.75.75 0 01-.75-.75zm0 6a.75.75 0 01.75-.75h15a.75.75 0 010 1.5h-15a.75.75 0 01-.75-.75z" clip-rule="evenodd"/></svg>
                </button>
            </div>
            <nav class="flex h-[calc(100dvh-3.5rem)] flex-col px-2">
                <ul class="flex flex-1 flex-col gap-1">
                    <li>
                        <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                            Home
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('uploader') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                            GL Entry uploader
                        </a>
                    </li>
                </ul>
                <form method="POST" action="{{ route('logout') }}" class="mb-3 px-2">
                    @csrf
                    <button class="flex w-full items-center justify-center rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Logout</button>
                </form>
            </nav>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-14 items-center gap-2 border-b border-gray-200 bg-white px-4">
                <button id="mobileMenuBtn" class="rounded-md p-2 text-gray-600 hover:bg-gray-100 md:hidden" aria-label="Open menu">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6"><path fill-rule="evenodd" d="M3.75 5.25a.75.75 0 01.75-.75h15a.75.75 0 010 1.5h-15a.75.75 0 01-.75-.75zm0 7.5a.75.75 0 01.75-.75h15a.75.75 0 010 1.5h-15a.75.75 0 01-.75-.75zm0 6a.75.75 0 01.75-.75h15a.75.75 0 010 1.5h-15a.75.75 0 01-.75-.75z" clip-rule="evenodd"/></svg>
                </button>
                <div class="flex-1 truncate text-base font-semibold">{{ $title ?? '' }}</div>
            </header>

            <main class="min-w-0 flex-1 p-4">@yield('content')</main>
        </div>
    </div>

    @vite(['resources/js/app.js'])
</body>
</html>


