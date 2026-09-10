@props(['fina' => false])

{{-- Divisor de seções: a sequência das faixas, da branca à preta. --}}
<div {{ $attributes->class(['faixa-progressao', 'faixa-progressao--fina' => $fina]) }} aria-hidden="true"></div>
