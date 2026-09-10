<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    @include('partials.head', ['titulo' => 'Entrar — Heróis do Tatame'])
</head>
<body class="bg-tatame-base text-tatame-ink dark:bg-noite-base dark:text-noite-ink antialiased font-sans min-h-screen flex flex-col">

    <div class="flex justify-end px-6 pt-6">
        <x-botao-tema data-cy="theme-toggle" />
    </div>

    <main class="flex-1 flex items-center justify-center px-6 pb-12">
        {{ $slot }}
    </main>

    <x-faixa-progressao fina />
</body>
</html>
