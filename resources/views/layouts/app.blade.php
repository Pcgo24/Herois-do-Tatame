<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @include('partials.head')
</head>
<body class="bg-tatame-base text-tatame-ink dark:bg-noite-base dark:text-noite-ink antialiased font-sans transition-colors duration-300">

    <a href="#conteudo" class="sr-only focus:not-sr-only focus:absolute focus:z-[60] focus:m-3 focus:rounded-md focus:bg-faixa-azul focus:px-4 focus:py-2 focus:text-white">
        Pular para o conteúdo
    </a>

    <header x-data="{ menuAberto: false }" class="sticky top-0 z-50 w-full bg-tatame-base dark:bg-noite-base border-b border-tatame-line dark:border-noite-line">

        <div class="container mx-auto px-6 py-3 flex justify-between items-center relative">

            <x-marca />

            <nav class="hidden md:flex gap-7 text-sm font-medium text-tatame-muted dark:text-noite-muted items-center">
                <a href="{{ route('home') }}" class="hover:text-tatame-ink dark:hover:text-noite-ink transition">Início</a>
                <a href="#modalidades" class="hover:text-tatame-ink dark:hover:text-noite-ink transition">Modalidades</a>
                <a href="{{ route('login') }}" class="hover:text-tatame-ink dark:hover:text-noite-ink transition">Área do Professor</a>

                <x-botao-tema data-cy="theme-toggle" />

                <a href="{{ route('enrollment') }}" data-cy="enrollment-btn" class="bg-faixa-azul hover:bg-[#175a96] text-white px-4 py-2 rounded-md font-semibold transition">Matricule-se</a>
            </nav>

            <div class="md:hidden flex items-center gap-1">
                <x-botao-tema data-cy="theme-toggle-mobile" :com-borda="false" icone="w-6 h-6" />

                <button data-cy="menu-toggle" @click="menuAberto = !menuAberto" class="p-2 text-tatame-ink dark:text-noite-ink focus:outline-none transition">
                    <span class="sr-only">Abrir menu</span>
                    <svg x-show="!menuAberto" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    <svg x-show="menuAberto" x-cloak class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

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
            class="md:hidden absolute top-full left-0 w-full bg-tatame-surface dark:bg-noite-surface border-b border-tatame-line dark:border-noite-line shadow-xl"
            x-cloak
        >
            <nav class="flex flex-col px-6 py-6 gap-4">
                <a href="{{ route('home') }}" @click="menuAberto = false" class="text-lg font-semibold border-b border-tatame-line dark:border-noite-line pb-3">Início</a>
                <a href="#sobre" @click="menuAberto = false" class="text-tatame-muted dark:text-noite-muted hover:text-tatame-ink dark:hover:text-noite-ink text-lg font-medium border-b border-tatame-line dark:border-noite-line pb-3 transition">Sobre o Projeto</a>
                <a href="#modalidades" @click="menuAberto = false" class="text-tatame-muted dark:text-noite-muted hover:text-tatame-ink dark:hover:text-noite-ink text-lg font-medium border-b border-tatame-line dark:border-noite-line pb-3 transition">Modalidades</a>
                <a href="{{ route('login') }}" @click="menuAberto = false" class="text-tatame-muted dark:text-noite-muted hover:text-tatame-ink dark:hover:text-noite-ink text-lg font-medium border-b border-tatame-line dark:border-noite-line pb-3 transition">Área do Professor</a>
                <a href="{{ route('enrollment') }}" @click="menuAberto = false" data-cy="enrollment-btn-mobile" class="bg-faixa-azul hover:bg-[#175a96] text-white text-lg font-semibold px-4 py-2 rounded-md transition text-center">Matricule-se</a>
            </nav>
        </div>

    </header>

    <main id="conteudo">
        {{ $slot }}
    </main>

</body>
</html>
