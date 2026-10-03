<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $mode === 'register' ? 'Daftar warga' : 'Masuk' }} · RawaRukun</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
    <link href="{{ asset('css/site.css') }}" rel="stylesheet">
</head>
<body class="auth-page">
    <main class="auth-frame">
        <section class="auth-story">
            <a class="brand brand-on-dark" href="{{ route('login') }}">
                <span class="brand-mark" aria-hidden="true"></span><span>rawa<span class="brand-light">rukun</span><small>RAWAT SUNGAI BERSAMA</small></span>
            </a>
            <div class="story-copy">
                <span class="eyebrow eyebrow-light"><span class="live-dot"></span> GERAKAN WARGA, DAMPAK NYATA</span>
                <h1>Sungai bersih<br>dimulai dari <em>kita.</em></h1>
                <p>Laporkan eceng gondok, ikut kerja bakti, dan jaga aliran sungai tetap hidup bersama tetangga.</p>
            </div>
            <div class="story-caption"><span>01 / PEDULI LINGKUNGAN</span><span>JAGA ALIRAN, JAGA KEHIDUPAN</span></div>
        </section>
        <section class="auth-panel">
            <div class="auth-panel-inner">
                <span class="eyebrow">RUANG WARGA DIGITAL</span>
                <h2>{{ $mode === 'register' ? 'Bergabung dengan warga' : 'Selamat datang kembali' }}</h2>
                <p class="auth-intro">{{ $mode === 'register' ? 'Buat akun untuk mulai merawat sungai di lingkunganmu.' : 'Masuk untuk melihat kabar sungai dan gerakan di lingkunganmu.' }}</p>
                @if(session('success'))<div class="flash flash-success">{{ session('success') }}</div>@endif
                @if($errors->any())<div class="flash flash-error">{{ $errors->first() }}</div>@endif

                <form method="POST" action="{{ $mode === 'register' ? route('register.store') : route('login.store') }}" class="auth-form">
                    @csrf
                    @if($mode === 'register')
                        <label>Nama lengkap<input name="name" value="{{ old('name') }}" autocomplete="name" placeholder="Nama sesuai identitas" required></label>
                        <label>RT / RW<input name="rt_rw" value="{{ old('rt_rw') }}" placeholder="Contoh: RT 1 / RW 1" required></label>
                    @endif
                    <label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="nama@email.com" required></label>
                    <label>Kata sandi<input type="password" name="password" autocomplete="{{ $mode === 'register' ? 'new-password' : 'current-password' }}" placeholder="{{ $mode === 'register' ? 'Minimal 8 karakter' : 'Masukkan kata sandi' }}" required></label>
                    @if($mode === 'register')
                        <label>Ulangi kata sandi<input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Ketik ulang kata sandi" required></label>
                    @else
                        <label class="remember-row"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
                    @endif
                    <button class="button button-primary button-wide" type="submit">{{ $mode === 'register' ? 'Buat akun warga' : 'Masuk ke RawaRukun' }}</button>
                </form>
                <p class="auth-switch">{{ $mode === 'register' ? 'Sudah punya akun?' : 'Belum terdaftar?' }} <a href="{{ route($mode === 'register' ? 'login' : 'register') }}">{{ $mode === 'register' ? 'Masuk di sini' : 'Daftar sebagai warga' }}</a></p>
                <p class="auth-note">{{ $mode === 'register' ? 'Pendaftaran warga perlu dikonfirmasi admin sebelum akun dapat digunakan.' : 'Akun pengelola lingkungan dibuat oleh administrator sistem.' }}</p>
            </div>
        </section>
    </main>
</body>
</html>