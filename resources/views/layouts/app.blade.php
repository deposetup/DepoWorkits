<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <style>
        body { font-family: -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 0; color: #1f2933; background: #f7f8fa; }
        main { min-height: calc(100vh - 108px); padding: 24px; box-sizing: border-box; max-width: 960px; margin: 0 auto; }
        nav {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 0 24px;
            height: 48px;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
        }
        nav a { color: #1f2933; text-decoration: none; font-size: 14px; }
        nav a:hover { text-decoration: underline; }
        nav form { margin-left: auto; }
        nav form button { padding: 4px 10px; font-size: 13px; }
        footer {
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 13px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            background: #ffffff;
        }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        th { color: #6b7280; font-weight: 600; }
        form label { display: block; margin: 12px 0 4px; font-size: 14px; color: #374151; }
        form input, form select { padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 14px; }
        button, .button {
            padding: 8px 14px; border: none; border-radius: 4px; background: #2563eb; color: #fff;
            font-size: 14px; cursor: pointer;
        }
        button.secondary { background: #6b7280; }
        .status { padding: 10px 14px; border-radius: 4px; background: #ecfdf5; color: #065f46; margin: 12px 0; }
        .error, .errors { padding: 10px 14px; border-radius: 4px; background: #fef2f2; color: #991b1b; margin: 12px 0; }
    </style>
</head>
<body>
    @auth
    <nav>
        <a href="{{ route('home') }}">{{ config('app.name') }}</a>
        <a href="{{ route('depots.index') }}">Depolar</a>
        <a href="{{ route('its-notifications.index') }}">İTS Bildirimleri</a>
        <a href="{{ route('pts.index') }}">PTS Sorgulama</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="secondary">Çıkış ({{ auth()->user()->name }})</button>
        </form>
    </nav>
    @endauth

    <main>
        @if (session('status'))
            <p class="status">{{ session('status') }}</p>
        @endif

        @yield('content')
    </main>

    <footer>
        <span>&copy; {{ date('Y') }} {{ config('app.company') }}</span>
        <span>&middot;</span>
        <span>v{{ config('app.version') }}</span>
    </footer>
</body>
</html>
