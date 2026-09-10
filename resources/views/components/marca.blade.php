@props(['logo' => 'h-11', 'texto' => 'text-lg'])

<a href="{{ route('home') }}" {{ $attributes->class('flex items-center gap-3') }}>
    <img src="{{ asset('img/logo-herois-do-tatame.png') }}" alt="" class="{{ $logo }} w-auto">
    <span class="font-display font-extrabold {{ $texto }} tracking-tight leading-none">Heróis do Tatame</span>
</a>
