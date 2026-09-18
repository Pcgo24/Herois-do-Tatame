<div
    x-data="{
        open: false,
        s: { resp: {} },
        select(student) { this.s = student; this.open = true; },
        close() { this.open = false; },
        statusLabel(v) { return v ? v.charAt(0).toUpperCase() + v.slice(1) : ''; },
        vencimentoClass(v) {
            return ({
                vencida: 'bg-red-50 text-red-700 border-red-300 dark:bg-red-500/10 dark:text-red-300 dark:border-red-500/25',
                vencendo: 'bg-yellow-50 text-yellow-800 border-yellow-300 dark:bg-yellow-500/10 dark:text-yellow-200 dark:border-yellow-500/25',
            })[v] || 'border-tatame-line dark:border-noite-line text-tatame-muted dark:text-noite-muted';
        },
        badgeClass(v) {
            return ({
                entregue: 'bg-yellow-50 text-yellow-800 border-yellow-300 dark:bg-yellow-500/10 dark:text-yellow-200 dark:border-yellow-500/25',
                assinado: 'bg-green-50 text-green-800 border-green-300 dark:bg-green-500/10 dark:text-green-300 dark:border-green-500/25',
            })[v] || 'bg-red-50 text-red-700 border-red-300 dark:bg-red-500/10 dark:text-red-300 dark:border-red-500/25';
        },
    }"
>
    @php
        use App\Support\Formatters;

        $statusBadge = fn (string $status) => match ($status) {
            'entregue' => 'bg-yellow-50 text-yellow-800 border-yellow-300 dark:bg-yellow-500/10 dark:text-yellow-200 dark:border-yellow-500/25',
            'assinado' => 'bg-green-50 text-green-800 border-green-300 dark:bg-green-500/10 dark:text-green-300 dark:border-green-500/25',
            default    => 'bg-red-50 text-red-700 border-red-300 dark:bg-red-500/10 dark:text-red-300 dark:border-red-500/25',
        };
        $vencimentoBadge = fn (string $situacao) => match ($situacao) {
            'vencida'  => 'bg-red-50 text-red-700 border-red-300 dark:bg-red-500/10 dark:text-red-300 dark:border-red-500/25',
            'vencendo' => 'bg-yellow-50 text-yellow-800 border-yellow-300 dark:bg-yellow-500/10 dark:text-yellow-200 dark:border-yellow-500/25',
            default    => 'border-tatame-line dark:border-noite-line text-tatame-muted dark:text-noite-muted',
        };
        $modalData = fn ($s) => [
            'id'           => $s->id,
            'name'         => $s->name,
            'cpf'          => Formatters::cpf($s->cpf),
            'rg'           => Formatters::rg($s->rg) ?: '—',
            'birth_date'   => Formatters::date($s->birth_date),
            'school'       => $s->school ?: '—',
            'grade'        => $s->grade ?: '—',
            'father_name'  => $s->no_father ? 'Não declarado' : ($s->father_name ?: '—'),
            'mother_name'  => $s->no_mother ? 'Não declarado' : ($s->mother_name ?: '—'),
            'phone'        => Formatters::phone($s->phone) ?: '—',
            'email'        => $s->email ?: '—',
            'modalidade'   => $s->modalidade,
            'termo_status' => $s->termo_status,
            'termo_arquivo'      => (bool) $s->termo_arquivo,
            'termo_arquivo_nome' => $s->termo_arquivo_nome,
            'cancelled'          => $s->trashed(),
            'matricula_em'       => Formatters::date($s->matricula_em),
            'vence_em'           => Formatters::date($s->vence_em),
            'situacao'           => $s->situacaoMatricula(),
            'vencimento'         => $s->textoVencimento(),
            'resp'         => [
                'name'         => $s->responsible->name,
                'phone'        => Formatters::phone($s->responsible->phone_number),
                'home_phone'   => Formatters::phone($s->responsible->home_phone) ?: '—',
                'cpf'          => Formatters::cpf($s->responsible->cpf),
                'rg'           => Formatters::rg($s->responsible->rg) ?: '—',
                'email'        => $s->responsible->email,
                'birth_date'   => Formatters::date($s->responsible->birth_date),
                'address'      => $s->responsible->address,
                'neighborhood' => $s->responsible->neighborhood ?: '—',
            ],
        ];
    @endphp

    @php $ativos = $students->whereNull('deleted_at')->count(); @endphp
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display font-extrabold text-3xl tracking-tight">Alunos Cadastrados</h1>
            <p class="text-tatame-muted dark:text-noite-muted mt-1 text-sm">
                {{ $ativos }} {{ $ativos === 1 ? 'aluno matriculado' : 'alunos matriculados' }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
            <label class="flex items-center gap-2 text-sm text-tatame-muted dark:text-noite-muted">
                Status do termo
                <select
                    wire:model.live="termoFilter"
                    data-cy="filter-termo"
                    class="rounded-md border border-tatame-line dark:border-noite-line bg-tatame-surface dark:bg-noite-surface px-2 py-1.5 text-xs text-tatame-ink dark:text-noite-ink focus:outline-none focus:ring-1 focus:ring-neutral-600"
                >
                    <option value="">Todos</option>
                    <option value="pendente">Pendente</option>
                    <option value="entregue">Entregue</option>
                    <option value="assinado">Assinado</option>
                </select>
            </label>
            <label class="flex items-center gap-2 cursor-pointer text-sm text-tatame-muted dark:text-noite-muted">
                <input type="checkbox" wire:model.live="onlyAttention" data-cy="toggle-attention" class="w-4 h-4 accent-faixa-azul cursor-pointer">
                Só vencidas ou a vencer
            </label>
            <label class="flex items-center gap-2 cursor-pointer text-sm text-tatame-muted dark:text-noite-muted">
                <input type="checkbox" wire:model.live="showCancelled" data-cy="toggle-cancelled" class="w-4 h-4 accent-faixa-azul cursor-pointer">
                Mostrar matrículas canceladas
            </label>
        </div>
    </div>

    @if($students->isEmpty())
        <div class="text-center py-20 text-tatame-muted dark:text-noite-muted">
            <p class="text-lg">Nenhum aluno cadastrado ainda.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-tatame-line dark:border-noite-line">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-tatame-raised dark:bg-noite-raised text-tatame-muted dark:text-noite-muted text-xs font-semibold">
                        <th class="px-6 py-4 text-left">Responsável</th>
                        <th class="px-6 py-4 text-left">Contato</th>
                        <th class="px-6 py-4 text-left">Aluno</th>
                        <th class="px-6 py-4 text-left">Matrícula</th>
                        <th class="px-6 py-4 text-left">Status do Termo</th>
                        <th class="px-6 py-4 text-left">Ficha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-tatame-line dark:divide-noite-line">
                    @foreach($students as $student)
                        <tr
                            wire:key="{{ $student->id }}"
                            @click="select(@js($modalData($student)))"
                            @class([
                                'bg-tatame-surface dark:bg-noite-surface hover:bg-tatame-raised dark:hover:bg-noite-raised transition-colors duration-150 cursor-pointer',
                                'opacity-60' => $student->trashed(),
                            ])
                            data-cy="student-row"
                            title="Ver detalhes do aluno"
                        >
                            <td class="px-6 py-4 text-tatame-ink dark:text-noite-ink">{{ $student->responsible->name }}</td>
                            <td class="px-6 py-4 text-tatame-muted dark:text-noite-muted font-mono">{{ Formatters::phone($student->responsible->phone_number) }}</td>
                            <td class="px-6 py-4 text-tatame-ink dark:text-noite-ink">{{ $student->name }}</td>
                            <td class="px-6 py-4">
                                @if ($student->trashed())
                                    <span class="text-xs text-tatame-muted dark:text-noite-muted">—</span>
                                @else
                                    <span class="inline-block whitespace-nowrap px-2.5 py-1 rounded-full text-xs font-semibold border {{ $vencimentoBadge($student->situacaoMatricula()) }}" data-cy="badge-vencimento" data-situacao="{{ $student->situacaoMatricula() }}">
                                        {{ $student->textoVencimento() }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($student->trashed())
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold border border-tatame-line dark:border-noite-line text-tatame-muted dark:text-noite-muted" data-cy="badge-cancelled">
                                        Cancelada
                                    </span>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusBadge($student->termo_status) }}" data-cy="badge-termo">
                                        {{ ucfirst($student->termo_status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($student->trashed())
                                    <span class="text-xs text-tatame-muted dark:text-noite-muted">—</span>
                                @else
                                <a
                                    href="{{ route('admin.students.ficha', $student) }}"
                                    target="_blank"
                                    @click.stop
                                    data-cy="ficha-link"
                                    class="inline-block text-xs font-semibold border border-tatame-line dark:border-noite-line hover:bg-tatame-raised dark:hover:bg-noite-raised px-3 py-1.5 rounded-lg transition"
                                >
                                    Gerar ficha
                                </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Aviso de vencimentos: uma vez por sessão, lista quem precisa renovar --}}
    @if ($avisoAberto)
        <div
            x-data="{ aberto: true }"
            x-show="aberto"
            @keydown.escape.window="aberto = false; $wire.dismissAviso()"
            class="fixed inset-0 z-[70] flex items-center justify-center p-4 sm:p-6"
            data-cy="aviso-vencimentos"
        >
            <div class="absolute inset-0 bg-black/85" @click="aberto = false; $wire.dismissAviso()"></div>

            <div class="relative w-full max-w-lg max-h-[85vh] flex flex-col rounded-2xl border border-tatame-line dark:border-noite-line bg-tatame-surface dark:bg-noite-surface shadow-2xl shadow-black/25 dark:shadow-black/60">
                <div class="px-6 py-5 border-b border-tatame-line dark:border-noite-line">
                    <p class="text-xs uppercase tracking-widest text-tatame-muted dark:text-noite-muted">Matrículas</p>
                    <h2 class="mt-1 text-xl font-bold text-tatame-ink dark:text-noite-ink">Matrículas que precisam de atenção</h2>
                    <p class="mt-1 text-sm text-tatame-muted dark:text-noite-muted">Clique no nome para abrir o aluno e renovar.</p>
                </div>

                <div class="overflow-y-auto px-6 py-4 space-y-5">
                    @foreach ([['Vencidas', $avisoVencidas, 'vencida'], ['Vencem em até 30 dias', $avisoVencendo, 'vencendo']] as [$titulo, $lista, $situacao])
                        @if ($lista->isNotEmpty())
                            <section>
                                <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold">
                                    <span class="inline-block rounded-full border px-2 py-0.5 text-xs {{ $vencimentoBadge($situacao) }}">{{ $lista->count() }}</span>
                                    {{ $titulo }}
                                </h3>
                                <ul class="divide-y divide-tatame-line dark:divide-noite-line">
                                    @foreach ($lista as $s)
                                        <li>
                                            <button
                                                type="button"
                                                @click="aberto = false; $wire.dismissAviso(); select(@js($modalData($s)))"
                                                data-cy="aviso-aluno"
                                                class="w-full flex flex-wrap items-center justify-between gap-2 py-2 text-left text-sm hover:bg-tatame-raised dark:hover:bg-noite-raised rounded-md px-2 -mx-2 transition"
                                            >
                                                <span class="text-tatame-ink dark:text-noite-ink">{{ $s->name }} <span class="text-tatame-muted dark:text-noite-muted">— {{ $s->modalidade }}</span></span>
                                                <span class="text-xs text-tatame-muted dark:text-noite-muted">{{ $s->textoVencimento() }}</span>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </section>
                        @endif
                    @endforeach
                </div>

                <div class="px-6 py-4 border-t border-tatame-line dark:border-noite-line flex justify-end">
                    <button type="button" @click="aberto = false; $wire.dismissAviso()" data-cy="aviso-fechar" class="botao-primario px-6 py-2 text-sm">Entendi</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal de detalhes do aluno (100% client-side) --}}
    <div
        x-show="open"
        x-cloak
        @keydown.escape.window="close()"
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6"
    >
        {{-- Overlay --}}
        <div
            x-show="open"
            x-transition.opacity.duration.200ms
            @click="close()"
            class="absolute inset-0 bg-black/85"
        ></div>

        {{-- Card --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl border border-tatame-line dark:border-noite-line bg-tatame-surface dark:bg-noite-surface shadow-2xl shadow-black/25 dark:shadow-black/60"
        >
            {{-- Cabeçalho --}}
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-tatame-line dark:border-noite-line bg-tatame-surface dark:bg-noite-surface px-6 py-5">
                <div>
                    <p class="text-xs uppercase tracking-widest text-tatame-muted dark:text-noite-muted">Detalhes do Aluno</p>
                    <h2 class="mt-1 text-2xl font-bold text-tatame-ink dark:text-noite-ink" x-text="s.name"></h2>
                    <span x-show="s.cancelled" x-cloak class="mt-2 inline-block rounded-full border border-tatame-line dark:border-noite-line px-2.5 py-1 text-xs font-semibold text-tatame-muted dark:text-noite-muted">Matrícula cancelada</span>
                </div>
                <button
                    type="button"
                    @click="close()"
                    class="rounded-lg p-1.5 text-tatame-muted dark:text-noite-muted transition hover:bg-tatame-raised dark:bg-noite-raised hover:text-tatame-ink dark:text-noite-ink"
                    aria-label="Fechar"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="space-y-8 px-6 py-6">
                {{-- Matrícula cancelada: só resta reativar --}}
                <div x-show="s.cancelled" x-cloak class="rounded-xl border border-tatame-line dark:border-noite-line bg-tatame-raised/60 dark:bg-noite-raised/50 p-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-tatame-muted dark:text-noite-muted">Esta matrícula foi cancelada. Os dados e a ficha assinada continuam guardados.</p>
                    <button
                        type="button"
                        @click="$wire.restoreEnrollment(s.id).then(() => close())"
                        data-cy="restore-enrollment"
                        class="botao-primario px-5 py-2 text-sm"
                    >
                        Reativar matrícula
                    </button>
                </div>

                {{-- Matrícula: validade e renovação --}}
                <div x-show="! s.cancelled" class="rounded-xl border border-tatame-line dark:border-noite-line bg-tatame-raised/60 dark:bg-noite-raised/50 p-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                        <span class="text-tatame-muted dark:text-noite-muted">Matrícula em <span class="font-mono text-tatame-ink dark:text-noite-ink" x-text="s.matricula_em"></span></span>
                        <span class="text-tatame-muted dark:text-noite-muted">Vence em <span class="font-mono text-tatame-ink dark:text-noite-ink" x-text="s.vence_em"></span></span>
                        <span
                            class="inline-block rounded-full border px-2.5 py-1 text-xs font-semibold"
                            :class="vencimentoClass(s.situacao)"
                            x-text="s.vencimento"
                            data-cy="modal-vencimento"
                        ></span>
                    </div>
                    <button
                        type="button"
                        @click="if (confirm('Renovar a matrícula de ' + s.name + ' por mais um ano a partir de hoje?')) $wire.renovarMatricula(s.id).then(() => close())"
                        data-cy="renew-enrollment"
                        class="botao-primario px-5 py-2 text-sm"
                    >
                        Renovar matrícula
                    </button>
                </div>

                {{-- Status do Termo --}}
                <div x-show="! s.cancelled" class="rounded-xl border border-tatame-line dark:border-noite-line bg-tatame-raised/60 dark:bg-noite-raised/50 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-tatame-muted dark:text-noite-muted">Status do Termo</span>
                            <span
                                class="inline-block rounded-full border px-2.5 py-1 text-xs font-semibold"
                                :class="badgeClass(s.termo_status)"
                                x-text="statusLabel(s.termo_status)"
                            ></span>
                        </div>
                        <select
                            x-model="s.termo_status"
                            @change="$wire.updateTermoStatus(s.id, s.termo_status)"
                            class="rounded-md border border-tatame-line dark:border-noite-line bg-tatame-raised dark:bg-noite-raised px-2 py-1.5 text-xs text-tatame-ink dark:text-noite-ink focus:outline-none focus:ring-1 focus:ring-neutral-600"
                        >
                            <option value="pendente">Pendente</option>
                            <option value="entregue">Entregue</option>
                            <option value="assinado">Assinado</option>
                        </select>
                    </div>
                </div>

                <div x-show="! s.cancelled">
                    <a
                        :href="'/admin/alunos/' + s.id + '/ficha'"
                        target="_blank"
                        data-cy="ficha-link-modal"
                        class="botao-primario inline-block px-6 py-2.5"
                    >
                        Gerar ficha
                    </a>
                </div>

                {{-- Dados do Responsável --}}
                <section>
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-tatame-muted dark:text-noite-muted">
                        <span class="h-1.5 w-1.5 rounded-full bg-tatame-muted dark:bg-noite-muted"></span>
                        Responsável
                    </h3>
                    <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-tatame-muted dark:text-noite-muted">Nome</dt>
                            <dd class="mt-0.5 text-tatame-ink dark:text-noite-ink" x-text="s.resp.name"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-tatame-muted dark:text-noite-muted">Telefone</dt>
                            <dd class="mt-0.5 font-mono text-tatame-ink dark:text-noite-ink" x-text="s.resp.phone"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-tatame-muted dark:text-noite-muted">CPF</dt>
                            <dd class="mt-0.5 font-mono text-tatame-ink dark:text-noite-ink" x-text="s.resp.cpf"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-tatame-muted dark:text-noite-muted">RG</dt>
                            <dd class="mt-0.5 font-mono text-tatame-ink dark:text-noite-ink" x-text="s.resp.rg"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-tatame-muted dark:text-noite-muted">Telefone residencial</dt>
                            <dd class="mt-0.5 font-mono text-tatame-ink dark:text-noite-ink" x-text="s.resp.home_phone"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-tatame-muted dark:text-noite-muted">Bairro</dt>
                            <dd class="mt-0.5 text-tatame-ink dark:text-noite-ink" x-text="s.resp.neighborhood"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-tatame-muted dark:text-noite-muted">E-mail</dt>
                            <dd class="mt-0.5 break-all text-tatame-ink dark:text-noite-ink" x-text="s.resp.email"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-tatame-muted dark:text-noite-muted">Data de Nascimento</dt>
                            <dd class="mt-0.5 text-tatame-ink dark:text-noite-ink" x-text="s.resp.birth_date"></dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase tracking-wide text-tatame-muted dark:text-noite-muted">Endereço</dt>
                            <dd class="mt-0.5 text-tatame-ink dark:text-noite-ink" x-text="s.resp.address"></dd>
                        </div>
                    </dl>
                </section>

                {{-- Dados do Estudante --}}
                <section>
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-tatame-muted dark:text-noite-muted">
                        <span class="h-1.5 w-1.5 rounded-full bg-tatame-muted dark:bg-noite-muted"></span>
                        Estudante
                    </h3>
                    <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-tatame-muted dark:text-noite-muted">Nome</dt>
                            <dd class="mt-0.5 text-tatame-ink dark:text-noite-ink" x-text="s.name"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-tatame-muted dark:text-noite-muted">CPF</dt>
                            <dd class="mt-0.5 font-mono text-tatame-ink dark:text-noite-ink" x-text="s.cpf"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-tatame-muted dark:text-noite-muted">RG</dt>
                            <dd class="mt-0.5 text-tatame-ink dark:text-noite-ink" x-text="s.rg"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-tatame-muted dark:text-noite-muted">Data de Nascimento</dt>
                            <dd class="mt-0.5 text-tatame-ink dark:text-noite-ink" x-text="s.birth_date"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-tatame-muted dark:text-noite-muted">Escola</dt>
                            <dd class="mt-0.5 text-tatame-ink dark:text-noite-ink" x-text="s.school"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-tatame-muted dark:text-noite-muted">Série</dt>
                            <dd class="mt-0.5 text-tatame-ink dark:text-noite-ink" x-text="s.grade"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-tatame-muted dark:text-noite-muted">Filiação — Pai</dt>
                            <dd class="mt-0.5 text-tatame-ink dark:text-noite-ink" x-text="s.father_name"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-tatame-muted dark:text-noite-muted">Filiação — Mãe</dt>
                            <dd class="mt-0.5 text-tatame-ink dark:text-noite-ink" x-text="s.mother_name"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-tatame-muted dark:text-noite-muted">Celular do aluno</dt>
                            <dd class="mt-0.5 font-mono text-tatame-ink dark:text-noite-ink" x-text="s.phone"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-tatame-muted dark:text-noite-muted">E-mail do aluno</dt>
                            <dd class="mt-0.5 break-all text-tatame-ink dark:text-noite-ink" x-text="s.email"></dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase tracking-wide text-tatame-muted dark:text-noite-muted">Modalidade</dt>
                            <dd class="mt-0.5 text-tatame-ink dark:text-noite-ink" x-text="s.modalidade"></dd>
                        </div>
                    </dl>
                </section>

                {{-- Ficha assinada --}}
                <section x-show="! s.cancelled">
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-tatame-muted dark:text-noite-muted">
                        <span class="h-1.5 w-1.5 rounded-full bg-tatame-muted dark:bg-noite-muted"></span>
                        Ficha assinada
                    </h3>

                    <template x-if="s.termo_arquivo">
                        <div class="flex flex-wrap items-center gap-3">
                            <a
                                :href="'/admin/alunos/' + s.id + '/ficha-assinada'"
                                target="_blank"
                                data-cy="ficha-assinada-link"
                                class="botao-primario inline-block px-5 py-2"
                            >
                                Baixar ficha assinada
                            </a>
                            <span class="text-sm text-tatame-muted dark:text-noite-muted" x-text="s.termo_arquivo_nome"></span>
                            <button
                                type="button"
                                @click="$wire.removeSignedFicha(s.id).then(() => close())"
                                data-cy="ficha-assinada-remove"
                                class="text-sm text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition"
                            >
                                Remover
                            </button>
                        </div>
                    </template>

                    <template x-if="! s.termo_arquivo">
                        <div>
                            <p class="text-sm text-tatame-muted dark:text-noite-muted mb-3">
                                Anexe o PDF ou a foto da ficha que o responsável assinou.
                                O status do termo passa a &quot;assinado&quot; automaticamente.
                            </p>
                            <input
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                data-cy="ficha-assinada-input"
                                @change="$wire.set('uploadTargetId', s.id)"
                                wire:model="signedFicha"
                                class="block w-full text-sm text-tatame-muted dark:text-noite-muted file:mr-4 file:rounded-lg file:border-0
                                       file:bg-tatame-raised dark:file:bg-noite-raised file:px-4 file:py-2 file:text-sm file:font-semibold
                                       file:text-tatame-ink dark:file:text-noite-ink
                                       hover:file:bg-tatame-line dark:hover:file:bg-noite-line"
                            >
                            <div wire:loading wire:target="signedFicha" class="text-sm text-tatame-muted dark:text-noite-muted mt-2">
                                Enviando arquivo...
                            </div>
                            <button
                                type="button"
                                @click="$wire.uploadSignedFicha().then(() => close())"
                                data-cy="ficha-assinada-submit"
                                class="botao-primario mt-3 px-5 py-2"
                            >
                                Salvar ficha assinada
                            </button>
                            @error('signedFicha')
                                <p class="erro-campo mt-2" data-cy="error-signedFicha">{{ $message }}</p>
                            @enderror
                        </div>
                    </template>
                </section>

                {{-- Cancelar matrícula --}}
                <section x-show="! s.cancelled" class="border-t border-tatame-line dark:border-noite-line pt-6">
                    <p class="text-sm text-tatame-muted dark:text-noite-muted mb-3">
                        Cancelar tira o aluno da lista, mas guarda o cadastro e a ficha assinada. Dá para reativar depois em "Mostrar matrículas canceladas".
                    </p>
                    <button
                        type="button"
                        @click="if (confirm('Cancelar a matrícula de ' + s.name + '?')) $wire.cancelEnrollment(s.id).then(() => close())"
                        data-cy="cancel-enrollment"
                        class="text-sm font-semibold text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition"
                    >
                        Cancelar matrícula
                    </button>
                </section>

            </div>
        </div>
    </div>
</div>
