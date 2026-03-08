<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Teaching Assistance') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%);
            min-height: 100vh;
        }

        .theme-dark body {
            background: #0f172a;
        }

        .theme-dark .bg-white {
            background-color: #0b1220 !important;
        }

        .theme-dark .bg-gray-50 {
            background-color: #0f172a !important;
        }

        .theme-dark .bg-gray-100 {
            background-color: #111827 !important;
        }

        .theme-dark .bg-gray-200 {
            background-color: #1f2937 !important;
        }

        .theme-dark .text-gray-900 {
            color: #f9fafb !important;
        }

        .theme-dark .text-gray-700 {
            color: #e5e7eb !important;
        }

        .theme-dark .text-gray-600,
        .theme-dark .text-gray-500 {
            color: #cbd5e1 !important;
        }

        .theme-dark .border-gray-200,
        .theme-dark .border-gray-300 {
            border-color: #334155 !important;
        }

        .theme-dark .shadow-sm {
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.35) !important;
        }

        .app-container {
            min-height: 100vh;
        }

        /* Navigation */
        .app-nav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }

        .nav-brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-brand-icon svg {
            width: 24px;
            height: 24px;
            color: white;
        }

        .nav-brand-text {
            font-weight: 700;
            font-size: 1.25rem;
            color: #1e293b;
        }

        .nav-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-user-info {
            text-align: right;
        }

        .nav-user-name {
            font-weight: 600;
            color: #1e293b;
        }

        .nav-user-role {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: capitalize;
        }

        .nav-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .nav-logout {
            padding: 0.5rem 1rem;
            background: #f1f5f9;
            border: none;
            border-radius: 8px;
            color: #475569;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-logout:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        /* Main Content */
        .app-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        /* Utility Classes */
        .bg-white {
            background: white;
        }

        .rounded-xl {
            border-radius: 1rem;
        }

        .shadow-sm {
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .border {
            border: 1px solid #e2e8f0;
        }

        .p-6 {
            padding: 1.5rem;
        }

        .space-y-6>*+* {
            margin-top: 1.5rem;
        }

        .grid {
            display: grid;
        }

        .grid-cols-2 {
            grid-template-columns: repeat(2, 1fr);
        }

        .grid-cols-4 {
            grid-template-columns: repeat(4, 1fr);
        }

        .gap-4 {
            gap: 1rem;
        }

        .gap-3 {
            gap: 0.75rem;
        }

        .flex {
            display: flex;
        }

        .items-center {
            align-items: center;
        }

        .justify-between {
            justify-content: space-between;
        }

        .text-sm {
            font-size: 0.875rem;
        }

        .text-xs {
            font-size: 0.75rem;
        }

        .text-gray-500 {
            color: #64748b;
        }

        .text-gray-600 {
            color: #475569;
        }

        .text-gray-900 {
            color: #0f172a;
        }

        .font-medium {
            font-weight: 500;
        }

        .font-semibold {
            font-weight: 600;
        }

        .font-bold {
            font-weight: 700;
        }

        .text-2xl {
            font-size: 1.5rem;
        }

        .text-lg {
            font-size: 1.125rem;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .mb-4 {
            margin-bottom: 1rem;
        }

        .mb-3 {
            margin-bottom: 0.75rem;
        }

        .mt-4 {
            margin-top: 1rem;
        }

        .w-10 {
            width: 2.5rem;
        }

        .h-10 {
            height: 2.5rem;
        }

        .w-12 {
            width: 3rem;
        }

        .h-12 {
            height: 3rem;
        }

        .w-6 {
            width: 1.5rem;
        }

        .h-6 {
            height: 1.5rem;
        }

        .w-5 {
            width: 1.25rem;
        }

        .h-5 {
            height: 1.25rem;
        }

        .rounded-lg {
            border-radius: 0.5rem;
        }

        .divide-y>*+* {
            border-top: 1px solid #e2e8f0;
        }

        .px-6 {
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        .py-4 {
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .py-8 {
            padding-top: 2rem;
            padding-bottom: 2rem;
        }

        .border-b {
            border-bottom: 1px solid #e2e8f0;
        }

        .bg-gray-50 {
            background: #f8fafc;
        }

        .bg-blue-100 {
            background: #dbeafe;
        }

        .bg-green-100 {
            background: #dcfce7;
        }

        .bg-green-50 {
            background: #f0fdf4;
        }

        .border-green-200 {
            border-color: #bbf7d0;
        }

        .text-blue-600 {
            color: #2563eb;
        }

        .text-green-600 {
            color: #16a34a;
        }

        .text-green-700 {
            color: #15803d;
        }

        .mx-auto {
            margin-left: auto;
            margin-right: auto;
        }

        .rounded-full {
            border-radius: 9999px;
        }

        .px-3 {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        .py-1 {
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
        }

        .py-2 {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        @media (min-width: 768px) {
            .md\:grid-cols-2 {
                grid-template-columns: repeat(2, 1fr);
            }

            .md\:grid-cols-4 {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Sidebar Transitions */
        .sidebar-transition {
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Sidebar Links */
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #64748b;
            border-radius: 0.75rem;
            transition: all 0.2s;
            text-decoration: none;
            font-weight: 500;
        }

        .sidebar-link:hover {
            background-color: #f1f5f9;
            color: #0f172a;
        }

        .sidebar-link.active {
            background-color: #0f172a;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .sidebar-link.active svg {
            color: white;
        }

        /* Main Content Area */
        .main-content {
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Search Input */
        .search-input {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.625rem 1rem 0.625rem 2.5rem;
            width: 100%;
            max-width: 320px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .search-input:focus {
            outline: none;
            border-color: #cbd5e1;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(226, 232, 240, 0.5);
        }
    </style>

    @livewireStyles
</head>

<body>
    <div x-data="{ sidebarOpen: false, darkTheme: false }"
        x-init="darkTheme = localStorage.getItem('theme') === 'dark'; document.documentElement.classList.toggle('theme-dark', darkTheme); $watch('darkTheme', value => { document.documentElement.classList.toggle('theme-dark', value); localStorage.setItem('theme', value ? 'dark' : 'light'); })"
        class="min-h-screen bg-gray-50">

        <!-- Top Navigation Bar -->
        <nav class="fixed top-0 z-50 w-full bg-white border-b border-gray-200 h-16">
            <div class="px-3 py-3 lg:px-5 lg:pl-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center justify-start rtl:justify-end">
                        <button @click="sidebarOpen = !sidebarOpen" type="button"
                            class="inline-flex items-center p-2 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200">
                            <span class="sr-only">Open sidebar</span>
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg">
                                <path clip-rule="evenodd" fill-rule="evenodd"
                                    d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z">
                                </path>
                            </svg>
                        </button>
                        <a href="{{ route('dashboard') }}" class="flex ms-2 md:ms-4 items-center gap-2">
                            <div class="w-8 h-8 bg-black rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <span class="self-center text-xl font-bold sm:text-2xl whitespace-nowrap text-gray-900">
                                Teaching Assistance
                            </span>
                        </a>
                    </div>
                    <div class="flex items-center">
                        <div class="flex items-center ms-3">
                            <div class="flex items-center gap-4">
                                <button class="text-gray-500 hover:text-gray-700 relative hidden sm:block">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    <span
                                        class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                                </button>

                                @auth
                                    <div class="relative pl-4 border-l border-gray-200" x-data="{ userMenuOpen: false }">
                                        <button type="button" @click="userMenuOpen = !userMenuOpen" @click.outside="userMenuOpen = false"
                                            class="flex items-center gap-3 text-left">
                                            <div class="text-right hidden md:block">
                                                <p class="text-sm font-semibold text-gray-900 leading-none">
                                                    {{ auth()->user()->name }}
                                                </p>
                                                <p class="text-xs text-gray-500 mt-1 capitalize">
                                                    {{ auth()->user()->role->label() }}
                                                </p>
                                            </div>
                                            <div class="w-8 h-8 rounded-full bg-gray-200 overflow-hidden border border-gray-300">
                                                <img src="{{ auth()->user()->avatar_path ? Storage::url(auth()->user()->avatar_path) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=0D8ABC&color=fff' }}"
                                                    alt="Avatar" class="w-full h-full object-cover">
                                            </div>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <div x-show="userMenuOpen" x-transition
                                            class="absolute right-0 mt-3 w-48 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden z-50">
                                            <a href="{{ route('profile.edit') }}"
                                                class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <span>Perfil</span>
                                            </a>
                                            <button type="button" @click="darkTheme = !darkTheme"
                                                class="flex items-center justify-between w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <span>Tema</span>
                                                <span class="text-xs text-gray-500" x-text="darkTheme ? 'Oscuro' : 'Claro'"></span>
                                            </button>
                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <button type="submit"
                                                    class="flex items-center justify-between w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                    <span>Salir</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Sidebar -->
        <aside id="logo-sidebar"
            class="fixed top-0 left-0 z-40 w-64 h-screen pt-16 transition-transform -translate-x-full bg-white border-r border-gray-200 sm:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full sm:translate-x-0'" aria-label="Sidebar">
            <div class="h-full px-3 pb-4 overflow-y-auto bg-white">
                <ul class="space-y-2 font-medium">
                    @php
                        $navItems = [
                            ['label' => 'Dashboard', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z', 'route' => 'dashboard', 'active' => true],
                            ['label' => 'Reports', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'route' => '#', 'active' => false],
                            ['label' => 'Attendance', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'route' => '#', 'active' => false],
                            ['label' => 'Leaves', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'route' => '#', 'active' => false],
                            ['label' => 'Master Data', 'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4', 'route' => '#', 'active' => false],
                            ['label' => 'Settings', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', 'route' => '#', 'active' => false],
                        ];
                    @endphp

                    @foreach($navItems as $item)
                        <li>
                            <a href="{{ $item['route'] == '#' ? '#' : ($item['route'] == 'dashboard' && auth()->check() ? route(auth()->user()->role->value . '.dashboard') : '#') }}"
                                class="sidebar-link group {{ $item['active'] ? 'active' : '' }}">
                                <div class="flex items-center justify-center w-6 h-6 flex-shrink-0">
                                    <svg class="w-5 h-5 transition duration-75" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="{{ $item['icon'] }}" />
                                    </svg>
                                </div>
                                <span class="ms-3 whitespace-nowrap">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach

                    <li>
                        <a href="#" class="sidebar-link group mt-4">
                            <div class="flex items-center justify-center w-6 h-6 flex-shrink-0">
                                <svg class="w-5 h-5 transition duration-75" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="ms-3 whitespace-nowrap">Help</span>
                        </a>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Page Content -->
        <div class="p-4 sm:ml-64 pt-16">
            {{ $slot }}
        </div>
    </div>

    @livewireScripts
</body>

</html>
