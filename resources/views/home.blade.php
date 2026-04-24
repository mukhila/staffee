<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staffee</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --brand-dark: #022a65;
            --brand-gold: #f89f10;
            --brand-blue: #1861cf;
            --brand-green: #17ac8e;
        }

        body {
            font-family: 'Instrument Sans', sans-serif;
            background: #f8fafc;
        }

        .btn-gold {
            background-color: var(--brand-gold);
            color: var(--brand-dark);
        }

        .btn-gold:hover {
            background-color: #f7a330;
        }

        .btn-blue {
            background-color: var(--brand-blue);
            color: #ffffff;
        }

        .decor-glow {
            position: absolute;
            border-radius: 999px;
            filter: blur(40px);
            opacity: 0.55;
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                scroll-behavior: auto !important;
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
</head>
<body class="antialiased text-slate-900">
    <nav class="sticky top-0 z-50 bg-white/95 border-b border-slate-200 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <a href="{{ url('/') }}" class="flex items-center gap-3">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="Staffee logo" class="h-11 w-11 rounded-2xl border border-slate-200 object-cover shadow-sm">
                    <div>
                        <p class="text-base font-semibold text-[#022a65]">Staffee</p>
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Workforce platform</p>
                    </div>
                </a>

                <div class="flex items-center gap-3">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-sm font-medium text-slate-700 hover:text-[#1861cf]">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-medium text-slate-700 hover:text-[#1861cf]">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center rounded-full bg-[#f89f10] px-4 py-2 text-sm font-semibold text-[#022a65] shadow-sm hover:bg-[#f7a330] transition">Get Started</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <main class="relative overflow-hidden">
        <div class="absolute left-0 top-12 h-72 w-72 bg-[#1861cf]/20 decor-glow"></div>
        <div class="absolute right-0 top-28 h-64 w-64 bg-[#17ac8e]/20 decor-glow"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-24">
            <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-[#1861cf]/20 bg-[#1861cf]/10 px-4 py-2 text-sm font-semibold text-[#1861cf]">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-[#17ac8e]"></span>
                        Built for modern teams
                    </div>

                    <h1 class="mt-8 text-5xl font-extrabold tracking-tight text-[#022a65] sm:text-6xl">Staffee brings attendance, tasks, projects, and communication together.</h1>
                    <p class="mt-6 text-lg leading-8 text-slate-600">Streamline every part of your workforce workflow with a modern platform built for fast-growing teams. Get clear visibility, faster decisions, and stronger collaboration.</p>

                    <div class="mt-10 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-full px-8 py-3 text-base font-semibold btn-gold shadow-lg shadow-[#022a65]/10 hover:shadow-[#022a65]/20 transition">Start Free</a>
                        <a href="#features" class="inline-flex items-center justify-center rounded-full border border-[#1861cf] bg-white px-8 py-3 text-base font-semibold text-[#1861cf] hover:bg-[#eff6ff] transition">Explore features</a>
                    </div>

                    <div class="mt-12 grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div class="rounded-3xl border border-slate-200 bg-white/90 px-4 py-5 text-center shadow-sm">
                            <p class="text-2xl font-bold text-[#022a65]">99.9%</p>
                            <p class="mt-1 text-sm text-slate-500">Uptime</p>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-white/90 px-4 py-5 text-center shadow-sm">
                            <p class="text-2xl font-bold text-[#022a65]">45%</p>
                            <p class="mt-1 text-sm text-slate-500">Time saved</p>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-white/90 px-4 py-5 text-center shadow-sm">
                            <p class="text-2xl font-bold text-[#022a65]">24/7</p>
                            <p class="mt-1 text-sm text-slate-500">Support</p>
                        </div>
                        <div class="rounded-3xl border border-slate-200 bg-white/90 px-4 py-5 text-center shadow-sm">
                            <p class="text-2xl font-bold text-[#022a65]">4.8/5</p>
                            <p class="mt-1 text-sm text-slate-500">Rating</p>
                        </div>
                    </div>
                </div>

                <div class="relative flex items-center justify-center">
                    <div class="relative w-full max-w-xl overflow-hidden rounded-[2rem] border border-white/60 bg-white/95 p-8 shadow-[0_40px_100px_rgba(2,42,101,0.12)] backdrop-blur-xl">
                        <div class="absolute -left-10 -top-10 h-28 w-28 rounded-full bg-[#1861cf]/15 blur-3xl"></div>
                        <div class="absolute -right-10 bottom-10 h-28 w-28 rounded-full bg-[#17ac8e]/15 blur-3xl"></div>

                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-[#1861cf]">Staffee dashboard</p>
                                <h2 class="mt-4 text-3xl font-semibold text-slate-900">Team activity at a glance</h2>
                            </div>
                            <div class="inline-flex h-12 w-12 items-center justify-center rounded-3xl bg-[#f89f10]/15 text-[#f89f10]">
                                <i class="fa-solid fa-chart-simple text-lg"></i>
                            </div>
                        </div>

                        <div class="mt-8 grid gap-4">
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                                <div class="flex items-center justify-between text-sm text-slate-500">
                                    <span>Attendance</span>
                                    <span class="font-semibold text-[#022a65]">98%</span>
                                </div>
                                <div class="mt-4 h-2 rounded-full bg-[#e2e8f0]">
                                    <div class="h-2 w-[98%] rounded-full bg-[#1861cf]"></div>
                                </div>
                            </div>
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                                <div class="flex items-center justify-between text-sm text-slate-500">
                                    <span>Tasks completed</span>
                                    <span class="font-semibold text-[#17ac8e]">72%</span>
                                </div>
                                <div class="mt-4 h-2 rounded-full bg-[#e2e8f0]">
                                    <div class="h-2 w-[72%] rounded-full bg-[#17ac8e]"></div>
                                </div>
                            </div>
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                                <div class="flex items-center justify-between text-sm text-slate-500">
                                    <span>Projects on track</span>
                                    <span class="font-semibold text-[#f89f10]">84%</span>
                                </div>
                                <div class="mt-4 h-2 rounded-full bg-[#e2e8f0]">
                                    <div class="h-2 w-[84%] rounded-full bg-[#f89f10]"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex items-center justify-between rounded-3xl bg-[#022a65] px-5 py-4 text-white">
                            <div>
                                <p class="text-sm text-[#c7d7ff]/90">Active teams</p>
                                <p class="mt-1 text-2xl font-bold">1,250+</p>
                            </div>
                            <i class="fa-solid fa-users-line text-3xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @php
        $applicationModules = [
            [
                'title' => 'Dashboard',
                'icon' => 'fa-chart-pie',
                'accent' => 'bg-[#022a65]',
                'iconWrap' => 'bg-[#022a65]',
                'description' => 'Live visibility into daily operations, team movement, and what needs attention now.',
                'items' => ['Live status', 'Team activity', 'Notifications'],
            ],
            [
                'title' => 'Attendance',
                'icon' => 'fa-clock',
                'accent' => 'bg-[#1861cf]',
                'iconWrap' => 'bg-[#1861cf]',
                'description' => 'Keep check-ins reliable, resolve exceptions quickly, and maintain accurate attendance records.',
                'items' => ['Check-in/out', 'Records', 'Admin override'],
            ],
            [
                'title' => 'Projects & Tasks',
                'icon' => 'fa-diagram-project',
                'accent' => 'bg-[#17ac8e]',
                'iconWrap' => 'bg-[#17ac8e]',
                'description' => 'Coordinate project delivery with clear ownership, workflows, and shared project assets.',
                'items' => ['Project management', 'Task assignment', 'Kanban', 'Documents'],
            ],
            [
                'title' => 'Time Tracking',
                'icon' => 'fa-stopwatch',
                'accent' => 'bg-[#f89f10]',
                'iconWrap' => 'bg-[#f89f10]',
                'description' => 'Track effort with flexible timers, categories, and billable reporting across teams.',
                'items' => ['Timer', 'Categories', 'Billable rates', 'Reports'],
            ],
            [
                'title' => 'Leave Management',
                'icon' => 'fa-calendar-check',
                'accent' => 'bg-[#0f766e]',
                'iconWrap' => 'bg-[#0f766e]',
                'description' => 'Manage requests, balances, and policy-driven approvals without leaving the platform.',
                'items' => ['Requests', 'Types', 'Policies', 'Balances', 'Calendar'],
            ],
            [
                'title' => 'Shift Management',
                'icon' => 'fa-business-time',
                'accent' => 'bg-[#2563eb]',
                'iconWrap' => 'bg-[#2563eb]',
                'description' => 'Define shifts, plan assignments, and handle edge cases like holidays and exceptions.',
                'items' => ['Definitions', 'Assignments', 'Exceptions', 'Holidays'],
            ],
            [
                'title' => 'HR Management',
                'icon' => 'fa-user-tie',
                'accent' => 'bg-[#022a65]',
                'iconWrap' => 'bg-white/15',
                'description' => 'A high-control layer for employee lifecycle operations and sensitive people workflows.',
                'items' => ['Profiles', 'Promotions', 'Resignations', 'Terminations'],
                'featured' => true,
                'surface' => 'bg-[#022a65] text-white border-[#022a65] shadow-[0_28px_80px_rgba(2,42,101,0.18)]',
                'muted' => 'text-[#d7e9ff]',
                'chip' => 'border-white/15 bg-white/10 text-white',
            ],
            [
                'title' => 'Communication',
                'icon' => 'fa-comments',
                'accent' => 'bg-[#7c3aed]',
                'iconWrap' => 'bg-[#7c3aed]',
                'description' => 'Keep people aligned through direct messages, announcements, and built-in notifications.',
                'items' => ['Chat', 'Mail', 'Notifications', 'Announcements'],
            ],
            [
                'title' => 'QA Tools',
                'icon' => 'fa-bug',
                'accent' => 'bg-[#dc2626]',
                'iconWrap' => 'bg-[#dc2626]',
                'description' => 'Support delivery quality with bug tracking, test coverage, and daily status visibility.',
                'items' => ['Bug tracking', 'Test cases', 'DSR'],
            ],
            [
                'title' => 'Reports',
                'icon' => 'fa-chart-line',
                'accent' => 'bg-[#1d4ed8]',
                'iconWrap' => 'bg-[#1d4ed8]',
                'description' => 'Turn operational activity into usable insights across workforce and delivery metrics.',
                'items' => ['Attendance', 'Projects', 'Bugs'],
            ],
            [
                'title' => 'Admin & Settings',
                'icon' => 'fa-shield-halved',
                'accent' => 'bg-[#1861cf]',
                'iconWrap' => 'bg-white/15',
                'description' => 'Centralize administration, access control, and the configuration that governs the platform.',
                'items' => ['Staff', 'Departments', 'Roles matrix', 'Permissions'],
                'featured' => true,
                'surface' => 'bg-[#1861cf] text-white border-[#1861cf] shadow-[0_28px_80px_rgba(24,97,207,0.18)]',
                'muted' => 'text-[#dbeafe]',
                'chip' => 'border-white/15 bg-white/10 text-white',
            ],
        ];
    @endphp

    <section id="features" class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.32em] text-[#1861cf]">Application overview</p>
                <h2 class="mt-4 text-4xl font-extrabold tracking-tight text-[#022a65] sm:text-5xl">All 11 Staffee modules in one connected workspace.</h2>
                <p class="mt-4 text-lg leading-8 text-slate-600">The platform spans day-to-day execution, team coordination, HR operations, and admin control. HR Management and Admin & Settings are intentionally highlighted to reflect their broader organizational scope.</p>
            </div>

            <div class="mt-10 rounded-[2rem] border border-slate-200 bg-white/80 p-6 shadow-sm backdrop-blur-sm">
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-[1.5rem] bg-slate-50 px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Operational flow</p>
                        <p class="mt-3 text-2xl font-bold text-[#022a65]">6 modules</p>
                        <p class="mt-2 text-sm text-slate-600">Dashboard through Shift Management cover everyday workforce execution.</p>
                    </div>
                    <div class="rounded-[1.5rem] bg-slate-50 px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Collaboration layer</p>
                        <p class="mt-3 text-2xl font-bold text-[#022a65]">3 modules</p>
                        <p class="mt-2 text-sm text-slate-600">Projects, communication, and QA tools keep delivery and feedback moving.</p>
                    </div>
                    <div class="rounded-[1.5rem] bg-slate-50 px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Control center</p>
                        <p class="mt-3 text-2xl font-bold text-[#022a65]">2 featured</p>
                        <p class="mt-2 text-sm text-slate-600">HR Management and Admin & Settings stand out as platform-level oversight modules.</p>
                    </div>
                </div>
            </div>

            <div class="mt-16 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($applicationModules as $module)
                    <article class="rounded-[2rem] border p-8 transition hover:-translate-y-1 hover:shadow-xl {{ $module['surface'] ?? 'border-slate-200 bg-white shadow-sm' }}">
                        <div class="flex items-start justify-between gap-4">
                            <div class="inline-flex h-14 w-14 items-center justify-center rounded-3xl text-white shadow-md {{ $module['iconWrap'] }}">
                                <i class="fa-solid {{ $module['icon'] }} text-lg"></i>
                            </div>
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] {{ $module['chip'] ?? 'border border-slate-200 bg-slate-50 text-slate-500' }}">
                                {{ !empty($module['featured']) ? 'Featured module' : 'Core module' }}
                            </span>
                        </div>

                        <div class="mt-6">
                            <div class="h-1.5 w-16 rounded-full {{ $module['accent'] }}"></div>
                            <h3 class="mt-5 text-2xl font-semibold {{ !empty($module['featured']) ? 'text-white' : 'text-[#022a65]' }}">{{ $module['title'] }}</h3>
                            <p class="mt-3 text-sm leading-6 {{ $module['muted'] ?? 'text-slate-600' }}">{{ $module['description'] }}</p>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-2">
                            @foreach ($module['items'] as $item)
                                <span class="inline-flex items-center rounded-full border px-3 py-2 text-xs font-medium {{ $module['chip'] ?? 'border-slate-200 bg-slate-50 text-slate-700' }}">
                                    {{ $item }}
                                </span>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="relative py-20">
        <div class="absolute inset-x-0 top-0 h-48 bg-gradient-to-r from-[#022a65]/20 via-transparent to-[#17ac8e]/20"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="rounded-[2.5rem] bg-[#022a65] px-8 py-16 shadow-[0_40px_120px_rgba(2,42,101,0.18)] text-white">
                <div class="grid gap-10 lg:grid-cols-2 lg:items-center">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.3em] text-[#a9c9ff]">Ready for a stronger team</p>
                        <h2 class="mt-6 text-4xl font-extrabold leading-tight">Start managing your team with Staffee today.</h2>
                        <p class="mt-6 max-w-xl text-base text-[#d7e9ff]">A complete workforce platform for attendance, tasks, projects, reports, and collaboration.</p>
                        <div class="mt-10 flex flex-col gap-4 sm:flex-row">
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-full bg-[#f89f10] px-8 py-4 text-base font-semibold text-[#022a65] shadow-lg shadow-[#f89f10]/30 hover:bg-[#f9a93d] transition">Start Free</a>
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-full border border-white/30 bg-white/10 px-8 py-4 text-base font-semibold text-white hover:bg-white/20 transition">Log in</a>
                        </div>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="rounded-[2rem] bg-white/10 p-6">
                            <p class="text-sm uppercase tracking-[0.25em] text-[#d7e9ff]">Trusted Growth</p>
                            <p class="mt-4 text-3xl font-bold">250+</p>
                            <p class="mt-2 text-sm text-[#cde2ff]">companies onboarded</p>
                        </div>
                        <div class="rounded-[2rem] bg-white/10 p-6">
                            <p class="text-sm uppercase tracking-[0.25em] text-[#d7e9ff]">Efficiency Boost</p>
                            <p class="mt-4 text-3xl font-bold">+40%</p>
                            <p class="mt-2 text-sm text-[#cde2ff]">workflow improvement</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-slate-950 text-slate-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="Staffee logo" class="h-11 w-11 rounded-2xl border border-slate-800 bg-white/10 object-cover">
                    <div>
                        <p class="text-lg font-semibold text-white">Staffee</p>
                        <p class="text-sm text-slate-500">Workforce made simple</p>
                    </div>
                </div>
                <p class="text-sm text-slate-500">&copy; {{ date('Y') }} Staffee. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
