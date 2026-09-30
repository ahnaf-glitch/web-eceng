<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RawaRukun') · RawaRukun</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..800&display=swap" rel="stylesheet">
    @stack('styles')
    <link href="{{ asset('css/site.css') }}" rel="stylesheet">
</head>
<body class="app-shell">
    <header class="topbar">
        <a class="brand" href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('portal.dashboard') }}" aria-label="RawaRukun, beranda">
            <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 36 36" focusable="false"><path d="M18 20v-8"/><path d="M18 16c-5 0-8-2.5-8-7 5 0 8 2.5 8 7Z"/><path d="M18 13c0-4.5 3-7 8-7 0 4.5-3 7-8 7Z"/><path d="M7 24c3-1.8 6-1.8 9 0s6 1.8 9 0 4-1.8 6-1"/><path d="M7 29c3-1.8 6-1.8 9 0s6 1.8 9 0 4-1.8 6-1"/></svg></span>
            <span>rawa<span class="brand-light">rukun</span><small>RAWAT SUNGAI BERSAMA</small></span>
        </a>
        <nav class="main-nav" aria-label="Navigasi utama">
            @if(auth()->user()->isAdmin())
                <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Ringkasan</a>
                <a class="{{ request()->routeIs('portal.reports.index') ? 'active' : '' }}" href="{{ route('portal.reports.index') }}">Papan laporan</a>
                <a class="{{ request()->routeIs('portal.workdays') ? 'active' : '' }}" href="{{ route('portal.workdays') }}">Kerja bakti</a>
                <a class="{{ request()->routeIs('admin.redemptions.*') ? 'active' : '' }}" href="{{ route('admin.redemptions.index') }}">Warga peduli</a>
            @else
                <a class="{{ request()->routeIs('portal.dashboard') ? 'active' : '' }}" href="{{ route('portal.dashboard') }}">Beranda</a>
                <a class="{{ request()->routeIs('portal.reports.create') ? 'active' : '' }}" href="{{ route('portal.reports.create') }}">Laporan</a>
                <a class="{{ request()->routeIs('portal.reports.index') ? 'active' : '' }}" href="{{ route('portal.reports.index') }}">Papan laporan</a>
                <a class="{{ request()->routeIs('portal.workdays') ? 'active' : '' }}" href="{{ route('portal.workdays') }}">Kerja bakti</a>
                <a class="{{ request()->routeIs('portal.rewards') ? 'active' : '' }}" href="{{ route('portal.rewards') }}">Warga peduli</a>
                <a class="{{ request()->routeIs('portal.education') ? 'active' : '' }}" href="{{ route('portal.education') }}">Edukasi</a>
            @endif
        </nav>
        <div class="user-menu">
            <img class="user-avatar" src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=96&h=96&q=80" alt="Foto profil warga">
            <span class="user-name">{{ auth()->user()->name }}<small>{{ auth()->user()->isAdmin() ? 'ADMINISTRATOR' : auth()->user()->formatted_rt_rw }}</small></span>
            <form action="{{ route('logout') }}" method="POST">@csrf<button class="logout-button" type="submit" aria-label="Keluar" title="Keluar">Keluar</button></form>
        </div>
    </header>

    <main class="page-wrap">
        @if(session('success'))
            <div class="flash flash-success" role="status">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="flash flash-error" role="alert">{{ $errors->first() }}</div>
        @endif
        @yield('content')
    </main>
    <footer class="site-footer"><span>RawaRukun · Gerakan warga jaga sungai</span><span>Setiap laporan berarti.</span></footer>
    @stack('scripts')
</body>
</html>