<div
    x-data="{
        open: false,
        s: { resp: {} },
        select(student) { this.s = student; this.open = true; },
        close() { this.open = false; },
        statusLabel(v) { return v ? v.charAt(0).toUpperCase() + v.slice(1) : ''; },
        badgeClass(v) {
            return ({
                entregue: 'bg-yellow-50 text-yellow-800 border-yellow-300 dark:bg-yellow-950 dark:text-yellow-400 dark:border-yellow-800',
                assinado: 'bg-green-50 text-green-800 border-green-300 dark:bg-green-950 dark:text-green-400 dark:border-green-800',
            })[v] || 'bg-red-50 text-red-700 border-red-300 dark:bg-red-950 dark:text-red-400 dark:border-red-800';
        },
    }"
>
    @php
        use App\Support\Formatters;

        $statusBadge = fn (string $status) => match ($status) {
            'entregue' => 'bg-yellow-50 text-yellow-800 border-yellow-300 dark:bg-yellow-950 dark:text-yellow-400 dark:border-yellow-800',
            'assinado' => 'bg-green-50 text-green-800 border-green-300 dark:bg-green-950 dark:text-green-400 dark:border-green-800',
            default    => 'bg-red-50 text-red-700 border-red-300 dark:bg-red-950 dark:text-red-400 dark:border-red-800',
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

    <div class="mb-8">
        <h1 class="font-display font-extrabold text-3xl tracking-tight">Alunos Cadastrados</h1>
        <p class="text-tatame-muted dark:text-noite-muted mt-1 text-sm">
            {{ $students->count() }} {{ $students->count() === 1 ? 'aluno cadastrado' : 'alunos cadastrados' }}
        </p>
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
                        <th class="px-6 py-4 text-left">Status do Termo</th>
                        <th class="px-6 py-4 text-left">Ficha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-tatame-line dark:divide-noite-line">
                    @foreach($students as $student)
                        <tr
                            wire:key="{{ $student->id }}"
                            @click="select(@js($modalData($student)))"
                            class="bg-tatame-surface dark:bg-noite-surface hover:bg-tatame-raised dark:hover:bg-noite-raised transition-colors duration-150 cursor-pointer"
                            title="Ver detalhes do aluno"
                        >
                            <td class="px-6 py-4 text-tatame-ink dark:text-noite-ink">{{ $student->responsible->name }}</td>
                            <td class="px-6 py-4 text-tatame-muted dark:text-noite-muted font-mono">{{ Formatters::phone($student->responsible->phone_number) }}</td>
                            <td class="px-6 py-4 text-tatame-ink dark:text-noite-ink">{{ $student->name }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusBadge($student->termo_status) }}">
                                    {{ ucfirst($student->termo_status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <a
                                    href="{{ route('admin.students.ficha', $student) }}"
                                    target="_blank"
                                    @click.stop
                                    data-cy="ficha-link"
                                    class="inline-block text-xs font-semibold border border-tatame-line dark:border-noite-line hover:bg-tatame-raised dark:hover:bg-noite-raised px-3 py-1.5 rounded-lg transition"
                                >
                                    Gerar ficha
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
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
                {{-- Status do Termo --}}
                <div class="rounded-xl border border-tatame-line dark:border-noite-line bg-tatame-raised/60 dark:bg-noite-raised/50 p-4">
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

                <div>
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
                <section>
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

            </div>
        </div>
    </div>
</div>
