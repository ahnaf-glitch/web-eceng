<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RawaRukun') · RawaRukun</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
    @stack('styles')
    <link href="{{ asset('css/site.css') }}" rel="stylesheet">
</head>
<body class="app-shell">
    <header class="topbar">
        <a class="brand" href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('portal.dashboard') }}" aria-label="RawaRukun, beranda">
            <span class="brand-mark" aria-hidden="true"></span>
            <span>rawa<span class="brand-light">rukun</span><small>RAWAT SUNGAI BERSAMA</small></span>
        </a>
        <nav class="main-nav" aria-label="Navigasi utama">
            @if(auth()->user()->isAdmin())
                <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Ringkasan</a>
                <a class="{{ request()->routeIs('portal.reports.index') ? 'active' : '' }}" href="{{ route('portal.reports.index') }}">Papan laporan</a>
                <a class="{{ request()->routeIs('portal.workdays') ? 'active' : '' }}" href="{{ route('portal.workdays') }}">Kerja bakti</a>
                <a class="{{ request()->routeIs('admin.redemptions.*') ? 'active' : '' }}" href="{{ route('admin.redemptions.index') }}">Warga peduli</a>
                <a class="{{ request()->routeIs('admin.activity-registrations.*') ? 'active' : '' }}" href="{{ route('admin.activity-registrations.index') }}">Pendaftaran kegiatan</a>
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
            <span class="user-avatar" aria-hidden="true"></span>
            <span class="user-name">{{ auth()->user()->name }}<small>{{ auth()->user()->isAdmin() ? 'ADMINISTRATOR' : preg_replace('/^\s*(?:RT\s*)?(\d+)\s*\/\s*(?:RW\s*)?(\d+)\s*$/i', 'RT$1/RW$2', (string) auth()->user()->rt_rw) }}</small></span>
            <form action="{{ route('logout') }}" method="POST">@csrf<button class="logout-button" type="submit">Keluar</button></form>
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