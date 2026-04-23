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

    <section id="features" class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mx-auto max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-[0.32em] text-[#1861cf]">Core capabilities</p>
                <h2 class="mt-4 text-4xl font-extrabold tracking-tight text-[#022a65] sm:text-5xl">Designed to simplify every part of your workflow.</h2>
                <p class="mt-4 text-lg leading-8 text-slate-600">From attendance and tasks to reports and chat, Staffee gives your team the tools needed to stay aligned and productive.</p>
            </div>

            <div class="mt-16 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm hover:shadow-lg transition">
                    <div class="inline-flex h-14 w-14 items-center justify-center rounded-3xl bg-[#022a65] text-white shadow-md">
                        <i class="fa-solid fa-clock text-lg"></i>
                    </div>
                    <h3 class="mt-6 text-xl font-semibold text-[#022a65]">Attendance tracking</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Fast clock-ins, attendance history, and compliance-ready reporting.</p>
                </article>
                <article class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm hover:shadow-lg transition">
                    <div class="inline-flex h-14 w-14 items-center justify-center rounded-3xl bg-[#1861cf] text-white shadow-md">
                        <i class="fa-solid fa-list-check text-lg"></i>
                    </div>
                    <h3 class="mt-6 text-xl font-semibold text-[#022a65]">Task management</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Create assignments, track progress, and keep every deadline in view.</p>
                </article>
                <article class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm hover:shadow-lg transition">
                    <div class="inline-flex h-14 w-14 items-center justify-center rounded-3xl bg-[#17ac8e] text-white shadow-md">
                        <i class="fa-solid fa-comments text-lg"></i>
                    </div>
                    <h3 class="mt-6 text-xl font-semibold text-[#022a65]">Team communication</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Keep conversations, files, and feedback in one secure channel.</p>
                </article>
                <article class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm hover:shadow-lg transition">
                    <div class="inline-flex h-14 w-14 items-center justify-center rounded-3xl bg-[#f89f10] text-white shadow-md">
                        <i class="fa-solid fa-chart-line text-lg"></i>
                    </div>
                    <h3 class="mt-6 text-xl font-semibold text-[#022a65]">Reports & analytics</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Instant insights into performance, workload, and team health.</p>
                </article>
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
