<div>

@php
    // As faixas dão identidade a cada modalidade e reaparecem no quadro de
    // horários, para dar pra ler a tabela de relance.
    $modalidades = [
        [
            'nome' => 'Jiu Jitsu',
            'cor' => '#1D6FB8',
            'texto' => 'Chão, imobilizações e defesa pessoal. Ensina paciência: o jogo é resolvido com técnica, não com força.',
            'foto' => 'https://images.unsplash.com/photo-1564415315949-7a0c4c73aab4?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080',
        ],
        [
            'nome' => 'Muay Thai',
            'cor' => '#E07A2F',
            'texto' => 'Punhos, joelhos e cotovelos em pé. Constrói condicionamento, coordenação e leitura de distância.',
            'foto' => 'https://images.unsplash.com/photo-1525680996651-0222228be6f0?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080',
        ],
        [
            'nome' => 'Taekwondo',
            'cor' => '#2E7D5B',
            'texto' => 'Chutes altos, velocidade e equilíbrio. A modalidade mais cerimoniosa: começa e termina com uma reverência.',
            'foto' => 'https://images.unsplash.com/photo-1514050566906-8d077bae7046?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080',
        ],
        [
            'nome' => 'Boxe',
            'cor' => '#C0392B',
            'texto' => 'Só as mãos, e tudo depende do ritmo. É a porta de entrada mais fácil para quem nunca treinou nada.',
            'foto' => 'https://images.unsplash.com/photo-1660212074310-6d7ed176c746?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080',
        ],
    ];

    $cores = collect($modalidades)->pluck('cor', 'nome');

    $horarios = [
        ['dia' => 'Segunda-feira', 'hora' => '18:00 - 19:30', 'aulas' => ['Jiu Jitsu', 'Boxe']],
        ['dia' => 'Terça-feira',   'hora' => '18:30 - 20:00', 'aulas' => ['Muay Thai', 'Taekwondo']],
        ['dia' => 'Quarta-feira',  'hora' => '18:00 - 19:30', 'aulas' => ['Jiu Jitsu', 'Boxe']],
        ['dia' => 'Quinta-feira',  'hora' => '18:30 - 20:00', 'aulas' => ['Muay Thai', 'Taekwondo']],
        ['dia' => 'Sábado',        'hora' => '09:00 - 11:00', 'aulas' => ['Treino livre e recreação']],
    ];
@endphp

{{-- Hero --}}
<section class="relative overflow-hidden">
    <div class="container mx-auto px-6 pt-14 pb-16 md:pt-20 md:pb-24">
        <div class="grid md:grid-cols-[minmax(0,17rem)_minmax(0,1fr)] gap-10 md:gap-16 items-center">

            <img
                src="{{ asset('img/logo-herois-do-tatame.png') }}"
                alt="Brasão do projeto Heróis do Tatame"
                class="w-44 md:w-full max-w-xs mx-auto md:mx-0 drop-shadow-[0_18px_35px_rgba(27,32,39,0.18)] dark:drop-shadow-[0_18px_35px_rgba(0,0,0,0.55)]"
                width="507" height="692" fetchpriority="high"
            >

            <div class="text-center md:text-left">
                <p class="text-sm font-semibold text-faixa-azul dark:text-faixa-amarela mb-4">
                    Projeto social do Centro de Treinamento Marcial
                </p>

                <h1 class="font-display font-extrabold text-4xl sm:text-5xl lg:text-[3.4rem] leading-[1.06] tracking-tight text-balance mb-5">
                    Artes marciais para crianças e adolescentes de 8 a 17 anos
                </h1>

                <p class="text-lg text-tatame-muted dark:text-noite-muted max-w-xl mx-auto md:mx-0 mb-8 leading-relaxed">
                    Quatro modalidades, cinco dias de treino por semana, em Prudentópolis.
                    Quem matricula é o responsável, e o cadastro leva poucos minutos.
                </p>

                <div class="flex flex-col sm:flex-row justify-center md:justify-start gap-3">
                    <a href="{{ route('enrollment') }}" data-cy="hero-enrollment-btn"
                       class="bg-faixa-azul hover:bg-[#175a96] text-white px-7 py-3.5 rounded-md font-semibold text-center transition">
                        Inscrever aluno
                    </a>
                    <a href="#sobre"
                       class="border border-tatame-ink/25 dark:border-noite-ink/30 hover:bg-tatame-raised dark:hover:bg-noite-raised px-7 py-3.5 rounded-md font-semibold text-center transition">
                        Conheça o projeto
                    </a>
                </div>
            </div>

        </div>
    </div>

    {{-- O único momento de movimento da página: a faixa se amarra ao carregar. --}}
    <x-faixa-progressao class="faixa-progressao--anima" />
</section>

{{-- Sobre o Projeto --}}
<section id="sobre" class="bg-tatame-surface dark:bg-noite-surface">
    <div class="container mx-auto px-6 py-20">
        <div class="max-w-2xl mb-14">
            <h2 class="font-display font-extrabold text-3xl md:text-4xl tracking-tight mb-4">Sobre o Projeto</h2>
            <p class="text-lg text-tatame-muted dark:text-noite-muted leading-relaxed">
                O Heróis do Tatame é mantido pelo Centro de Treinamento Marcial para abrir o esporte
                a quem não teria como pagar uma academia.
            </p>
        </div>

        <dl class="grid md:grid-cols-3 md:divide-x divide-y md:divide-y-0 divide-tatame-line dark:divide-noite-line border-t border-tatame-line dark:border-noite-line">
            <div class="py-8 md:pr-10">
                <span class="block w-9 h-1.5 rounded-full bg-faixa-amarela mb-5"></span>
                <dt class="font-display font-bold text-xl mb-2">Quem pode entrar</dt>
                <dd class="text-tatame-muted dark:text-noite-muted leading-relaxed">
                    Meninos e meninas de 8 a 17 anos. A matrícula é feita por um responsável,
                    que assina a ficha de cadastro.
                </dd>
            </div>

            <div class="py-8 md:px-10">
                <span class="block w-9 h-1.5 rounded-full bg-faixa-verde mb-5"></span>
                <dt class="font-display font-bold text-xl mb-2">Disciplina e respeito</dt>
                <dd class="text-tatame-muted dark:text-noite-muted leading-relaxed">
                    Antes do golpe vem a saudação, a hora de chegar e o cuidado com o colega.
                    É o que fica quando o treino acaba.
                </dd>
            </div>

            <div class="py-8 md:pl-10">
                <span class="block w-9 h-1.5 rounded-full bg-faixa-azul mb-5"></span>
                <dt class="font-display font-bold text-xl mb-2">Saúde e convívio</dt>
                <dd class="text-tatame-muted dark:text-noite-muted leading-relaxed">
                    Movimento constante melhora coordenação, sono e humor — e o tatame
                    coloca a criança junto de outras todos os dias de treino.
                </dd>
            </div>
        </dl>
    </div>
</section>

{{-- Modalidades --}}
<section id="modalidades" class="container mx-auto px-6 py-20">
    <div class="max-w-2xl mb-12">
        <h2 class="font-display font-extrabold text-3xl md:text-4xl tracking-tight mb-4">Modalidades</h2>
        <p class="text-lg text-tatame-muted dark:text-noite-muted leading-relaxed">
            O aluno escolhe uma arte marcial e pode trocar depois de experimentar.
            Cada uma tem sua cor, que se repete no quadro de horários.
        </p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach ($modalidades as $modalidade)
            <article class="bg-tatame-surface dark:bg-noite-surface border border-tatame-line dark:border-noite-line rounded-xl overflow-hidden flex flex-col">
                <div class="h-1.5 w-full" style="background-color: {{ $modalidade['cor'] }}"></div>

                <img
                    src="{{ $modalidade['foto'] }}"
                    alt="Treino de {{ $modalidade['nome'] }}"
                    loading="lazy"
                    class="h-44 w-full object-cover"
                >

                <div class="p-6 flex-1">
                    <h3 class="font-display font-bold text-xl mb-2">{{ $modalidade['nome'] }}</h3>
                    <p class="text-tatame-muted dark:text-noite-muted text-[0.95rem] leading-relaxed">
                        {{ $modalidade['texto'] }}
                    </p>
                </div>
            </article>
        @endforeach
    </div>
</section>

{{-- Quadro de Horários --}}
<section class="bg-tatame-surface dark:bg-noite-surface border-y border-tatame-line dark:border-noite-line">
    <div class="container mx-auto px-6 py-20">
        <div class="max-w-2xl mb-12">
            <h2 class="font-display font-extrabold text-3xl md:text-4xl tracking-tight mb-4">Quadro de Horários</h2>
            <p class="text-lg text-tatame-muted dark:text-noite-muted leading-relaxed">
                Os treinos acontecem no fim da tarde durante a semana e na manhã de sábado.
            </p>
        </div>

        <div class="overflow-x-auto rounded-xl border border-tatame-line dark:border-noite-line">
            <table class="w-full text-left whitespace-nowrap">
                <thead class="bg-tatame-raised dark:bg-noite-raised text-sm font-semibold">
                    <tr>
                        <th scope="col" class="px-6 py-4">Dia da semana</th>
                        <th scope="col" class="px-6 py-4">Horário</th>
                        <th scope="col" class="px-6 py-4">Modalidades</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-tatame-line dark:divide-noite-line bg-tatame-surface dark:bg-noite-surface">
                    @foreach ($horarios as $linha)
                        <tr>
                            <th scope="row" class="px-6 py-5 font-display font-bold text-left">{{ $linha['dia'] }}</th>
                            <td class="px-6 py-5 text-tatame-muted dark:text-noite-muted tabular-nums">{{ $linha['hora'] }}</td>
                            <td class="px-6 py-5">
                                <span class="flex flex-wrap gap-2">
                                    @foreach ($linha['aulas'] as $aula)
                                        @php $cor = $cores[$aula] ?? null; @endphp
                                        <span
                                            class="inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-semibold {{ $cor ? '' : 'border-tatame-line dark:border-noite-line text-tatame-muted dark:text-noite-muted' }}"
                                            @if ($cor) style="border-color: {{ $cor }}44; color: {{ $cor }}; background-color: {{ $cor }}14" @endif
                                        >
                                            @if ($cor)
                                                <span class="w-2 h-2 rounded-full" style="background-color: {{ $cor }}" aria-hidden="true"></span>
                                            @endif
                                            {{ $aula }}
                                        </span>
                                    @endforeach
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>

{{-- Chamada final --}}
<section class="container mx-auto px-6 py-20">
    <div class="bg-tatame-surface dark:bg-noite-surface border border-tatame-line dark:border-noite-line rounded-2xl px-8 py-12 md:px-14 md:py-14 flex flex-col md:flex-row md:items-center gap-8 justify-between">
        <div class="max-w-xl">
            <h2 class="font-display font-extrabold text-2xl md:text-3xl tracking-tight mb-3">Pronto para o primeiro treino?</h2>
            <p class="text-tatame-muted dark:text-noite-muted text-lg leading-relaxed">
                Preencha a ficha com os dados do responsável e do aluno. Depois é só aparecer
                no dia da modalidade escolhida.
            </p>
        </div>
        <a href="{{ route('enrollment') }}"
           class="bg-faixa-azul hover:bg-[#175a96] text-white px-8 py-4 rounded-md font-semibold text-center shrink-0 transition">
            Inscrever aluno
        </a>
    </div>
</section>

<footer>
    <x-faixa-progressao fina />

    <div class="bg-tatame-surface dark:bg-noite-surface">
        <div class="container mx-auto px-6 pt-14 pb-8">
            <div class="grid md:grid-cols-3 gap-10 mb-12">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <img src="{{ asset('img/logo-herois-do-tatame.png') }}" alt="" class="h-12 w-auto">
                        <span class="font-display font-extrabold text-lg leading-tight">Heróis do Tatame</span>
                    </div>
                    <p class="text-tatame-muted dark:text-noite-muted leading-relaxed">
                        Projeto comunitário do Centro de Treinamento Marcial para crianças e
                        adolescentes de 8 a 17 anos.
                    </p>
                </div>

                <div>
                    <h3 class="font-display font-bold mb-4">Links rápidos</h3>
                    <ul class="text-tatame-muted dark:text-noite-muted space-y-2.5">
                        <li><a href="{{ route('home') }}" class="hover:text-tatame-ink dark:hover:text-noite-ink transition">Início</a></li>
                        <li><a href="{{ route('enrollment') }}" class="hover:text-tatame-ink dark:hover:text-noite-ink transition">Inscrever aluno</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-tatame-ink dark:hover:text-noite-ink transition">Área do Professor</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-display font-bold mb-4">Contato</h3>
                    <p class="text-tatame-muted dark:text-noite-muted mb-1">Mestre Alisson Antunes</p>
                    <a href="tel:+554288615081" class="font-display font-bold text-lg hover:text-faixa-azul dark:hover:text-faixa-amarela transition">
                        (42) 8861-5081
                    </a>
                </div>
            </div>

            <p class="text-tatame-muted dark:text-noite-muted text-sm border-t border-tatame-line dark:border-noite-line pt-8">
                &copy; {{ date('Y') }} Centro de Treinamento Marcial. Todos os direitos reservados.
            </p>
        </div>
    </div>
</footer>

</div>
