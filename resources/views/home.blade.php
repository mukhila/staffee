<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Staff Management') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Instrument Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased">

    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ url('/') }}" class="flex items-center gap-2">
                        <div class="bg-blue-600 text-white p-2 rounded-lg">
                            <i class="fa-solid fa-users-gear text-xl"></i>
                        </div>
                        <span class="font-bold text-xl tracking-tight text-gray-900">StaffManager</span>
                    </a>
                </div>
                <div class="flex items-center gap-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="ml-4 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition">Get Started</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <div class="relative z-10 pb-8 bg-white sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
                <svg class="hidden lg:block absolute right-0 inset-y-0 h-full w-48 text-white transform translate-x-1/2" fill="currentColor" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                    <polygon points="50,0 100,0 50,100 0,100" />
                </svg>

                <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
                    <div class="sm:text-center lg:text-left">
                        <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
                            <span class="block xl:inline">Streamline your</span>
                            <span class="block text-blue-600 xl:inline">workforce management</span>
                        </h1>
                        <p class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                            Effortlessly manage attendance, tasks, projects, and communication in one unified platform. Empower your team to achieve more.
                        </p>
                        <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                            <div class="rounded-md shadow">
                                <a href="{{ route('login') }}" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 md:py-4 md:text-lg md:px-10">
                                    Get Started
                                </a>
                            </div>
                            <div class="mt-3 sm:mt-0 sm:ml-3">
                                <a href="#features" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 md:py-4 md:text-lg md:px-10">
                                    Learn More
                                </a>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2 bg-gray-50 flex items-center justify-center">
            <img class="h-56 w-full object-cover sm:h-72 md:h-96 lg:w-full lg:h-full opacity-90" src="https://images.unsplash.com/photo-1551434678-e076c223a692?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=2850&q=80" alt="Team working">
        </div>
    </div>

    <!-- Features Section -->
    <div id="features" class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:text-center">
                <h2 class="text-base text-blue-600 font-semibold tracking-wide uppercase">Features</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Everything you need to manage your team
                </p>
                <p class="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
                    A comprehensive suite of tools designed to boost productivity and streamline operations.
                </p>
            </div>

            <div class="mt-10">
                <dl class="space-y-10 md:space-y-0 md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-10">
                    <!-- Feature 1 -->
                    <div class="relative">
                        <dt>
                            <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Attendance Tracking</p>
                        </dt>
                        <dd class="mt-2 ml-16 text-base text-gray-500">
                            Seamless check-in and check-out functionality. Track work hours and generate reports effortlessly.
                        </dd>
                    </div>

                    <!-- Feature 2 -->
                    <div class="relative">
                        <dt>
                            <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                                <i class="fa-solid fa-list-check"></i>
                            </div>
                            <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Task Management</p>
                        </dt>
                        <dd class="mt-2 ml-16 text-base text-gray-500">
                            Assign, track, and complete tasks. Visualize progress with Kanban boards and stay organized.
                        </dd>
                    </div>

                    <!-- Feature 3 -->
                    <div class="relative">
                        <dt>
                            <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                                <i class="fa-solid fa-comments"></i>
                            </div>
                            <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Real-time Chat</p>
                        </dt>
                        <dd class="mt-2 ml-16 text-base text-gray-500">
                            Instant communication with your team. Share files, emojis, and keep the conversation flowing.
                        </dd>
                    </div>

                    <!-- Feature 4 -->
                    <div class="relative">
                        <dt>
                            <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Daily Reports</p>
                        </dt>
                        <dd class="mt-2 ml-16 text-base text-gray-500">
                            Submit daily status reports (DSR) to keep management informed about progress and blockers.
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-blue-700">
        <div class="max-w-2xl mx-auto text-center py-16 px-4 sm:py-20 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-extrabold text-white sm:text-4xl">
                <span class="block">Ready to boost productivity?</span>
                <span class="block">Start using StaffManager today.</span>
            </h2>
            <p class="mt-4 text-lg leading-6 text-blue-200">
                Join thousands of teams who have transformed their workflow with our platform.
            </p>
            <a href="{{ route('register') }}" class="mt-8 w-full inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50 sm:w-auto">
                Sign up for free
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <div class="text-white text-xl">
                        <i class="fa-solid fa-users-gear"></i>
                    </div>
                    <span class="text-white font-bold text-lg">StaffManager</span>
                </div>
                <p class="text-gray-400 text-sm">
                    &copy; {{ date('Y') }} Staff Management System. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

</body>
</html>
