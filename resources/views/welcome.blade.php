@include('layouts.partials.header')
<body class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 antialiased">

    <!-- Navigation -->
    <nav id="navbar" class="fixed w-full z-50 transition-all duration-300 bg-transparent">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-2">
                    <a href="{{ url('/') }}" class="flex items-center gap-2">
                        <div class="bg-gradient-to-br from-blue-600 to-indigo-600 p-2 rounded-xl shadow-lg">
                            <i class="fa-solid fa-users-gear text-xl text-white"></i>
                        </div>
                        <span class="font-bold text-xl gradient-text">StaffManager</span>
                    </a>
                </div>
                
                <div class="hidden md:flex items-center gap-8">
                    <a href="#features" class="text-gray-700 hover:text-blue-600 transition font-medium">Features</a>
                    <a href="#benefits" class="text-gray-700 hover:text-blue-600 transition font-medium">Benefits</a>
                    <a href="#stats" class="text-gray-700 hover:text-blue-600 transition font-medium">Why Us</a>
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="px-4 py-2 text-gray-700 hover:text-blue-600 transition font-medium">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="px-4 py-2 text-gray-700 hover:text-blue-600 transition font-medium">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-semibold hover:shadow-lg hover:scale-105 transition-all">
                                    Get Started
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>

                <button id="mobile-menu-btn" class="md:hidden text-gray-700">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t shadow-lg">
            <div class="px-4 py-4 space-y-3">
                <a href="#features" class="block text-gray-700 hover:text-blue-600 py-2">Features</a>
                <a href="#benefits" class="block text-gray-700 hover:text-blue-600 py-2">Benefits</a>
                <a href="#stats" class="block text-gray-700 hover:text-blue-600 py-2">Why Us</a>
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="block w-full px-4 py-2 text-center text-gray-700 border border-gray-300 rounded-lg">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="block w-full px-4 py-2 text-center text-gray-700 border border-gray-300 rounded-lg">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="block w-full px-4 py-2 text-center bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg">Get Started</a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 px-4 sm:px-6 lg:px-8 overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="space-y-8">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold">
                        <i class="fa-solid fa-bolt"></i>
                        <span>Trusted by 10,000+ teams worldwide</span>
                    </div>
                    
                    <h1 class="text-5xl md:text-6xl lg:text-7xl font-extrabold leading-tight">
                        <span class="block text-gray-900">Transform Your</span>
                        <span class="block gradient-text">Workforce Management</span>
                    </h1>
                    
                    <p class="text-xl text-gray-600 leading-relaxed">
                        Streamline attendance, tasks, and communication in one intelligent platform. 
                        Empower your team to achieve extraordinary results.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('register') }}" class="group px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-semibold hover:shadow-2xl hover:scale-105 transition-all inline-flex items-center justify-center gap-2">
                            Start Free Trial
                            <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                        </a>
                        <a href="#features" class="px-8 py-4 bg-white text-gray-700 rounded-xl font-semibold hover:shadow-lg transition-all border-2 border-gray-200 inline-flex items-center justify-center">
                            Watch Demo
                        </a>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 pt-8">
                        <div class="text-center">
                            <div class="text-3xl font-bold gradient-text">10K+</div>
                            <div class="text-sm text-gray-600 mt-1">Active Users</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold gradient-text">99.9%</div>
                            <div class="text-sm text-gray-600 mt-1">Uptime</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold gradient-text">50%</div>
                            <div class="text-sm text-gray-600 mt-1">Time Saved</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold gradient-text">24/7</div>
                            <div class="text-sm text-gray-600 mt-1">Support</div>
                        </div>
                    </div>
                </div>

                <div class="relative animate-float">
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-400 to-indigo-400 rounded-3xl blur-3xl opacity-20 animate-pulse-slow"></div>
                    <div class="relative bg-white rounded-3xl shadow-2xl p-8">
                        <div class="space-y-6">
                            <!-- Dashboard Preview -->
                            <div class="flex items-center justify-between pb-4 border-b">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-full"></div>
                                    <div>
                                        <div class="font-semibold">Team Dashboard</div>
                                        <div class="text-sm text-gray-500">Real-time Overview</div>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                </div>
                            </div>

                            <!-- Feature Cards -->
                            <div class="feature-card flex items-center gap-4 p-4 rounded-xl bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-lg">
                                <div class="p-3 bg-white/20 rounded-lg">
                                    <i class="fa-solid fa-clock text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold">Smart Attendance</div>
                                    <div class="text-sm text-white/80">Automated tracking with real-time insights</div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 hover:bg-gray-100 transition-all">
                                <div class="p-3 bg-white rounded-lg">
                                    <i class="fa-solid fa-list-check text-xl text-purple-500"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold">Task Management</div>
                                    <div class="text-sm text-gray-500">Kanban boards and assignments</div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 hover:bg-gray-100 transition-all">
                                <div class="p-3 bg-white rounded-lg">
                                    <i class="fa-solid fa-comments text-xl text-orange-500"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold">Team Communication</div>
                                    <div class="text-sm text-gray-500">Real-time chat and collaboration</div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 hover:bg-gray-100 transition-all">
                                <div class="p-3 bg-white rounded-lg">
                                    <i class="fa-solid fa-chart-line text-xl text-green-500"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold">Analytics & Reports</div>
                                    <div class="text-sm text-gray-500">Daily status and insights</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Grid -->
    <section id="features" class="py-20 px-4 sm:px-6 lg:px-8 bg-white/50">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-4">
                    <span class="text-gray-900">Powerful</span>
                    <span class="gradient-text"> Features</span>
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Everything you need to manage, motivate, and measure your team's success
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Feature 1 -->
                <div class="card-hover group bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:border-transparent hover:shadow-2xl overflow-hidden relative">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-cyan-500 opacity-0 group-hover:opacity-10 transition-opacity"></div>
                    <div class="relative w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center text-white mb-4 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-clock text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Smart Attendance</h3>
                    <p class="text-gray-600">Automated tracking with real-time insights and analytics</p>
                </div>

                <!-- Feature 2 -->
                <div class="card-hover group bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:border-transparent hover:shadow-2xl overflow-hidden relative">
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-pink-500 opacity-0 group-hover:opacity-10 transition-opacity"></div>
                    <div class="relative w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center text-white mb-4 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-list-check text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Task Management</h3>
                    <p class="text-gray-600">Kanban boards, assignments, and progress visualization</p>
                </div>

                <!-- Feature 3 -->
                <div class="card-hover group bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:border-transparent hover:shadow-2xl overflow-hidden relative">
                    <div class="absolute inset-0 bg-gradient-to-br from-orange-500 to-red-500 opacity-0 group-hover:opacity-10 transition-opacity"></div>
                    <div class="relative w-14 h-14 bg-gradient-to-br from-orange-500 to-red-500 rounded-xl flex items-center justify-center text-white mb-4 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-comments text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Team Communication</h3>
                    <p class="text-gray-600">Real-time chat, file sharing, and collaboration tools</p>
                </div>

                <!-- Feature 4 -->
                <div class="card-hover group bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:border-transparent hover:shadow-2xl overflow-hidden relative">
                    <div class="absolute inset-0 bg-gradient-to-br from-green-500 to-emerald-500 opacity-0 group-hover:opacity-10 transition-opacity"></div>
                    <div class="relative w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center text-white mb-4 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-chart-line text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Analytics & Reports</h3>
                    <p class="text-gray-600">Daily status reports and comprehensive insights</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section id="benefits" class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="space-y-8">
                    <h2 class="text-4xl md:text-5xl font-bold">
                        <span class="text-gray-900">Why Teams Choose</span>
                        <span class="gradient-text"> StaffManager</span>
                    </h2>
                    
                    <div class="space-y-6">
                        <div class="flex gap-4 p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow">
                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-lg flex items-center justify-center text-white">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg mb-1">50% Increase in Productivity</h3>
                                <p class="text-gray-600">Automated workflows save hours every week</p>
                            </div>
                        </div>

                        <div class="flex gap-4 p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow">
                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center text-white">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg mb-1">Enterprise-Grade Security</h3>
                                <p class="text-gray-600">Bank-level encryption and compliance</p>
                            </div>
                        </div>

                        <div class="flex gap-4 p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow">
                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-orange-500 to-red-500 rounded-lg flex items-center justify-center text-white">
                                <i class="fa-solid fa-bolt"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg mb-1">Lightning Fast Performance</h3>
                                <p class="text-gray-600">Real-time updates across all devices</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <div class="absolute inset-0 bg-gradient-to-r from-purple-400 to-pink-400 rounded-3xl blur-3xl opacity-20"></div>
                    <div class="relative bg-gradient-to-br from-blue-600 to-indigo-600 rounded-3xl p-8 text-white shadow-2xl">
                        <h3 class="text-2xl font-bold mb-6">Start Your Free Trial</h3>
                        <ul class="space-y-4 mb-8">
                            <li class="flex items-center gap-3">
                                <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-check text-sm"></i>
                                </div>
                                <span>No credit card required</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-check text-sm"></i>
                                </div>
                                <span>14-day free trial</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-check text-sm"></i>
                                </div>
                                <span>Full feature access</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-check text-sm"></i>
                                </div>
                                <span>24/7 customer support</span>
                            </li>
                        </ul>
                        <a href="{{ route('register') }}" class="block w-full py-4 bg-white text-blue-600 rounded-xl font-bold text-center hover:shadow-xl hover:scale-105 transition-all">
                            Get Started Free
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="stats" class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600">
        <div class="max-w-4xl mx-auto text-center text-white">
            <h2 class="text-4xl md:text-5xl font-bold mb-6">
                Ready to Transform Your Team?
            </h2>
            <p class="text-xl mb-8 text-blue-100">
                Join thousands of teams already using StaffManager to boost productivity
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}" class="px-8 py-4 bg-white text-blue-600 rounded-xl font-bold hover:shadow-2xl hover:scale-105 transition-all inline-flex items-center justify-center">
                    Start Free Trial
                </a>
                <a href="#features" class="px-8 py-4 bg-transparent border-2 border-white text-white rounded-xl font-bold hover:bg-white/10 transition-all inline-flex items-center justify-center">
                    Learn More
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex items-center gap-2">
                    <div class="bg-gradient-to-br from-blue-600 to-indigo-600 p-2 rounded-xl">
                        <i class="fa-solid fa-users-gear text-xl text-white"></i>
                    </div>
                    <span class="font-bold text-xl text-white">StaffManager</span>
                </div>
                <p class="text-sm">
                    &copy; {{ date('Y') }} Staff Management System. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    @include('layouts.partials.footer')
    <!-- JavaScript -->
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('bg-white/80', 'backdrop-blur-lg', 'shadow-lg');
                navbar.classList.remove('bg-transparent');
            } else {
                navbar.classList.remove('bg-white/80', 'backdrop-blur-lg', 'shadow-lg');
                navbar.classList.add('bg-transparent');
            }
        });

        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    // Close mobile menu if open
                    mobileMenu.classList.add('hidden');
                }
            });
        });

        // Feature card animation rotation
        const featureCards = document.querySelectorAll('.feature-card');
        let currentCard = 0;
        
        setInterval(() => {
            // This would rotate through cards if you had multiple with the class
            // For demo purposes, keeping the first one highlighted
        }, 3000);
    </script>

</body>
</html>