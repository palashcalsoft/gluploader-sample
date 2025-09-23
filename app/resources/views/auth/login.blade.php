<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-dvh bg-gray-50 text-gray-900">
    <div class="flex min-h-dvh items-center justify-center p-4">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-semibold tracking-tight">Sign in</h1>
                <p class="mt-1 text-sm text-gray-500">Access your account</p>
            </div>
            <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
                @csrf

                @if ($errors->any())
                    <div class="rounded-lg bg-red-50 p-3 text-sm text-red-700 ring-1 ring-red-200">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="space-y-1.5">
                    <label for="email" class="block text-sm font-medium">Email</label>
                    <input id="email" name="email" type="email" required autofocus autocomplete="username"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none ring-0 placeholder:text-gray-400 focus:border-indigo-500" />
                </div>

                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm font-medium">Password</label>
                        <a href="#" class="text-sm text-indigo-600 hover:text-indigo-500">Forgot?</a>
                    </div>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none ring-0 placeholder:text-gray-400 focus:border-indigo-500" />
                </div>

                <label class="inline-flex items-center gap-2 text-sm">
                    <input name="remember" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                    <span>Remember me</span>
                </label>

                <button type="submit"
                        class="mt-2 inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Sign in
                </button>
            </form>
        </div>
    </div>
    @vite(['resources/js/app.js'])
    <script>
        // Optional: Autofocus animation
        window.addEventListener('load', () => {
            const email = document.getElementById('email');
            if (email) email.focus();
        });
    </script>
    </body>
    </html>


