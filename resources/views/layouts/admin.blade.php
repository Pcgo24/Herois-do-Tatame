<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    @include('partials.head', ['titulo' => 'Admin — Heróis do Tatame'])
</head>
<body class="bg-tatame-base text-tatame-ink dark:bg-noite-base dark:text-noite-ink antialiased font-sans min-h-screen flex flex-col">

    <header class="sticky top-0 z-50 w-full bg-tatame-base dark:bg-noite-base border-b border-tatame-line dark:border-noite-line">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center gap-4">
            <div class="flex items-center gap-3">
                <x-marca />
                <span class="text-xs bg-tatame-raised dark:bg-noite-raised text-tatame-muted dark:text-noite-muted border border-tatame-line dark:border-noite-line px-2 py-0.5 rounded-full font-semibold">Admin</span>
            </div>

            <div class="flex items-center gap-2">
                <x-botao-tema data-cy="theme-toggle" />
                @if (Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-tatame-muted dark:text-noite-muted hover:text-tatame-ink dark:hover:text-noite-ink transition px-3 py-2">Sair</button>
                    </form>
                @endif
            </div>
        </div>
    </header>

    <main class="flex-1 container mx-auto px-6 py-10">
        {{ $slot }}
    </main>

    <x-faixa-progressao fina />
</body>
</html>
