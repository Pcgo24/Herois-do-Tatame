@props(['comBorda' => true, 'icone' => 'w-5 h-5'])

{{-- Os dois botões (desktop e mobile) leem o mesmo store do Alpine, então
     nunca saem de sincronia com a classe do <html>. --}}
{{-- x-data próprio: fora da landing page não existe escopo Alpine
     ancestral, e sem ele o Alpine nem processa o botão. --}}
<button
    x-data
    type="button"
    @click="$store.tema.alternar()"
    :aria-pressed="$store.tema.escuro"
    {{ $attributes->class([
        'p-2 text-tatame-muted dark:text-noite-muted hover:text-tatame-ink dark:hover:text-noite-ink transition',
        'rounded-md border border-tatame-line dark:border-noite-line hover:border-tatame-muted dark:hover:border-noite-muted' => $comBorda,
    ]) }}
>
    <span class="sr-only" x-text="$store.tema.escuro ? 'Usar tema claro' : 'Usar tema escuro'">Alternar tema</span>
    <svg x-show="!$store.tema.escuro" class="{{ $icone }}" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
    <svg x-show="$store.tema.escuro" x-cloak class="{{ $icone }}" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
</button>
