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
    <div class="flex h-dvh overflow-hidden">
        <aside id="sidebar" class="group/sidebar hidden h-dvh w-64 shrink-0 overflow-x-hidden border-r border-gray-200 bg-white md:block transition-[width] duration-200">
            <div class="flex h-14 items-center justify-between px-4">
                <a href="{{ route('home') }}" class="text-base font-semibold">GL Uploader</a>
                <button id="collapseBtn" class="hidden rounded-md p-2 text-gray-500 hover:bg-gray-100 md:block" aria-label="Collapse sidebar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5"><path fill-rule="evenodd" d="M3.75 5.25a.75.75 0 01.75-.75h15a.75.75 0 010 1.5h-15a.75.75 0 01-.75-.75zm0 7.5a.75.75 0 01.75-.75h15a.75.75 0 010 1.5h-15a.75.75 0 01-.75-.75zm0 6a.75.75 0 01.75-.75h15a.75.75 0 010 1.5h-15a.75.75 0 01-.75-.75z" clip-rule="evenodd"/></svg>
                </button>
            </div>
            <nav class="flex h-[calc(100dvh-3.5rem)] flex-col px-2">
                <ul class="flex flex-1 flex-col gap-1">
                    <li>
                        <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 transition-all group-[.collapsed]/sidebar:justify-center group-[.collapsed]/sidebar:px-0">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5 text-gray-600">
                                <path d="M11.47 3.84a.75.75 0 0 1 1.06 0l7.5 7.5a.75.75 0 1 1-1.06 1.06l-.72-.72v6.57c0 .62-.5 1.12-1.12 1.12h-3.25a1.12 1.12 0 0 1-1.13-1.12v-3.25a1.13 1.13 0 0 0-1.12-1.13h-1.5a1.12 1.12 0 0 0-1.12 1.13v3.25c0 .62-.5 1.12-1.13 1.12H6.63c-.62 0-1.13-.5-1.13-1.12v-6.57l-.72.72a.75.75 0 1 1-1.06-1.06l7.75-7.5Z"/>
                            </svg>
                            <span class="truncate transition-all group-[.collapsed]/sidebar:opacity-0 group-[.collapsed]/sidebar:w-0 group-[.collapsed]/sidebar:ml-0 group-[.collapsed]/sidebar:invisible">Home</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('uploader') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 transition-all group-[.collapsed]/sidebar:justify-center group-[.collapsed]/sidebar:px-0">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5 text-gray-600">
                                <path d="M12 2.25a.75.75 0 0 1 .75.75v9.19l2.47-2.47a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0L7.72 11.78a.75.75 0 1 1 1.06-1.06l2.47 2.47V3a.75.75 0 0 1 .75-.75Z"/>
                                <path d="M4.5 14.25a.75.75 0 0 1 .75-.75H9a.75.75 0 0 1 0 1.5H5.25a.75.75 0 0 1-.75-.75Zm0 3.5c0-.41.34-.75.75-.75H15a.75.75 0 0 1 0 1.5H5.25a.75.75 0 0 1-.75-.75Zm0 3.5c0-.41.34-.75.75-.75h12.5a.75.75 0 0 1 0 1.5H5.25a.75.75 0 0 1-.75-.75Z"/>
                            </svg>
                            <span class="truncate transition-all group-[.collapsed]/sidebar:opacity-0 group-[.collapsed]/sidebar:w-0 group-[.collapsed]/sidebar:ml-0 group-[.collapsed]/sidebar:invisible">GL Entry uploader</span>
                        </a>
                    </li>
                </ul>
                <form method="POST" action="{{ route('logout') }}" class="mb-3 px-2">
                    @csrf
                    <button class="flex w-full items-center justify-center rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Logout</button>
                </form>
            </nav>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col h-dvh overflow-y-auto">
            <header class="flex h-14 items-center gap-2 border-b border-gray-200 bg-white px-4">
                <button id="mobileMenuBtn" class="rounded-md p-2 text-gray-600 hover:bg-gray-100 md:hidden" aria-label="Open menu">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6"><path fill-rule="evenodd" d="M3.75 5.25a.75.75 0 01.75-.75h15a.75.75 0 010 1.5h-15a.75.75 0 01-.75-.75zm0 7.5a.75.75 0 01.75-.75h15a.75.75 0 010 1.5h-15a.75.75 0 01-.75-.75zm0 6a.75.75 0 01.75-.75h15a.75.75 0 010 1.5h-15a.75.75 0 01-.75-.75z" clip-rule="evenodd"/></svg>
                </button>
                <div class="flex-1 truncate text-base font-semibold">{{ $title ?? '' }}</div>
            </header>

            <main class="min-w-0 flex-1 p-4">@yield('content')</main>
        </div>
    </div>

    <div id="fullscreenLoader" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black/50">
        <div class="rounded-lg bg-white p-6 shadow-lg flex flex-col items-center">
            <div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-300 border-t-gray-900"></div>
            <p class="mt-3 text-sm text-gray-700">Processing...</p>
        </div>
    </div>

    @vite(['resources/js/app.js'])
</body>
</html>


