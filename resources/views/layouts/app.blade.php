<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heróis do Tatame</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style> [x-cloak] { display: none !important; } </style>
</head>
<body class="bg-black text-white antialiased font-sans">

    <header x-data="{ menuAberto: false }" class="sticky top-0 z-50 w-full bg-black/80 backdrop-blur-md border-b border-neutral-900">

        <div class="container mx-auto px-6 py-4 flex justify-between items-center relative">

            <div class="flex items-center gap-2">
                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                <a href="#" class="text-xl font-bold tracking-widest uppercase">Heróis do Tatame</a>
            </div>

            <nav class="hidden md:flex gap-6 text-sm font-medium text-gray-400 items-center">
                <a href="{{ route('home') }}" class="hover:text-white transition">Início</a>
                <a href="{{ route('enrollment') }}" data-cy="enrollment-btn" class="bg-neutral-800 hover:bg-neutral-700 text-white px-4 py-2 rounded-md transition">Matricule-se</a>
            </nav>

            <button @click="menuAberto = !menuAberto" class="md:hidden text-neutral-300 hover:text-white focus:outline-none transition">
                <svg x-show="!menuAberto" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                <svg x-show="menuAberto" x-cloak class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>

        </div>

        <div
            x-show="menuAberto"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-4"
            @click.away="menuAberto = false"
            class="md:hidden absolute top-full left-0 w-full bg-[#111111] border-b border-neutral-900 shadow-2xl"
            x-cloak
        >
            <nav class="flex flex-col px-6 py-6 gap-4">
                <a href="{{ route('home') }}" @click="menuAberto = false" class="text-white text-lg font-medium border-b border-neutral-800 pb-3">Início</a>
                <a href="#sobre" @click="menuAberto = false" class="text-gray-400 hover:text-white text-lg font-medium border-b border-neutral-800 pb-3 transition">Sobre o Projeto</a>
                <a href="#modalidades" @click="menuAberto = false" class="text-gray-400 hover:text-white text-lg font-medium border-b border-neutral-800 pb-3 transition">Modalidades</a>
                <a href="{{ route('enrollment') }}" @click="menuAberto = false" data-cy="enrollment-btn-mobile" class="bg-neutral-800 hover:bg-neutral-700 text-white text-lg font-medium px-4 py-2 rounded-md transition text-center">Matricule-se</a>
            </nav>
        </div>

    </header>

    <main>
        {{ $slot }}
    </main>

</body>
</html>


<!-- <a href="#" class="hover:text-white transition">Cadastro de Alunos</a>
                <a href="#" class="hover:text-white transition">Área do Instrutor</a>
                <a href="#" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-md transition">Matricule-se</a> -->
