<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | NISA 成長枠タイミング</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper text-ink min-h-screen antialiased">
    <header class="border-b border-line bg-card">
        <div class="max-w-6xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('dashboard') }}" class="font-semibold tracking-tight">NISA 成長枠タイミング</a>
            <nav class="flex gap-1 text-sm">
                @foreach ([
                    'dashboard' => 'ダッシュボード',
                    'purchases.index' => '枠管理',
                    'backtest.index' => 'バックテスト',
                ] as $name => $label)
                    <a href="{{ route($name) }}"
                       @class([
                           'px-3 py-1.5 rounded-md',
                           'bg-ink text-white' => request()->routeIs($name),
                           'text-muted hover:text-ink hover:bg-paper' => ! request()->routeIs($name),
                       ])>{{ $label }}</a>
                @endforeach
            </nav>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-6 flex flex-col gap-6">
        @if (session('status'))
            <p class="rounded-md border border-accent/30 bg-accent/10 px-4 py-2 text-sm text-accent">{{ session('status') }}</p>
        @endif

        @yield('content')
    </main>

    <footer class="max-w-6xl mx-auto px-4 pb-8 text-xs text-muted">
        判断の目安を出すツールです。過去の傾向は将来の結果を保証しません。
    </footer>
</body>
</html>
