<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <style>
        body { font-family: -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 0; color: #1f2933; background: #f7f8fa; }
        main { min-height: calc(100vh - 60px); padding: 24px; box-sizing: border-box; }
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
    </style>
</head>
<body>
    <main>
        @yield('content')
    </main>

    <footer>
        <span>&copy; {{ date('Y') }} {{ config('app.company') }}</span>
        <span>&middot;</span>
        <span>v{{ config('app.version') }}</span>
    </footer>
</body>
</html>
