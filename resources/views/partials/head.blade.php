{{-- <head> compartilhado pelos três layouts: tema, fontes e assets. --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $titulo ?? 'Heróis do Tatame' }}</title>
<link rel="icon" href="{{ asset('img/logo-herois-do-tatame.png') }}">

{{-- Aplica o tema antes da primeira pintura para a página não piscar.
     O claro é o padrão: só fica escuro quem escolheu o escuro. --}}
<script>
    if (localStorage.getItem('tema') === 'escuro') {
        document.documentElement.classList.add('dark');
    }
</script>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=archivo:600,700,800|karla:400,500,600,700&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])

<style> [x-cloak] { display: none !important; } </style>
