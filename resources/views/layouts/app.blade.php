<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heróis do Tatame</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-black text-white antialiased font-sans">

    <header class="container mx-auto px-6 py-4 flex justify-between items-center border-b border-gray-800">
        <div class="flex items-center gap-2">
            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            <span class="text-xl font-bold tracking-widest">HERÓIS DO TATAME</span>
        </div>
        <nav class="hidden md:flex gap-6 text-sm font-medium text-gray-400 items-center">
            <a href="#" class="text-white border-b-2 border-white pb-1">Início</a>
            <a href="#" class="hover:text-white transition">Cadastro de Alunos</a>
            <a href="#" class="hover:text-white transition">Área do Instrutor</a>
            <a href="#" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-md transition">Matricule-se</a>
        </nav>
    </header>

    <main>
        {{ $slot }}
    </main>

</body>
</html>
