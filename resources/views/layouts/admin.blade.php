<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Heróis do Tatame</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-black text-white antialiased font-sans min-h-screen">

    <header class="sticky top-0 z-50 w-full bg-black/80 backdrop-blur-md border-b border-neutral-900">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                <span class="text-xl font-bold tracking-widest uppercase">Heróis do Tatame</span>
                <span class="text-xs bg-neutral-800 text-neutral-400 border border-neutral-700 px-2 py-0.5 rounded-full uppercase tracking-widest">Admin</span>
            </div>
            @if(Route::has('logout'))
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-neutral-400 hover:text-white transition">Sair</button>
            </form>
            @endif
        </div>
    </header>

    <main class="container mx-auto px-6 py-10">
        {{ $slot }}
    </main>

</body>
</html>
