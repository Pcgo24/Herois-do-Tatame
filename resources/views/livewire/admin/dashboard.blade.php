<div
    x-data="{
        open: false,
        s: { resp: {} },
        select(student) { this.s = student; this.open = true; },
        close() { this.open = false; },
        statusLabel(v) { return v ? v.charAt(0).toUpperCase() + v.slice(1) : ''; },
        badgeClass(v) {
            return ({
                entregue: 'bg-yellow-950 text-yellow-400 border-yellow-800',
                assinado: 'bg-green-950 text-green-400 border-green-800',
            })[v] || 'bg-red-950 text-red-400 border-red-800';
        },
    }"
>
    @php
        use App\Support\Formatters;

        $statusBadge = fn (string $status) => match ($status) {
            'entregue' => 'bg-yellow-950 text-yellow-400 border-yellow-800',
            'assinado' => 'bg-green-950 text-green-400 border-green-800',
            default    => 'bg-red-950 text-red-400 border-red-800',
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
        <h1 class="text-3xl font-bold uppercase tracking-wide text-white">Alunos Cadastrados</h1>
        <p class="text-neutral-500 mt-1 text-sm">
            {{ $students->count() }} {{ $students->count() === 1 ? 'aluno cadastrado' : 'alunos cadastrados' }}
        </p>
    </div>

    @if($students->isEmpty())
        <div class="text-center py-20 text-neutral-600">
            <p class="text-lg">Nenhum aluno cadastrado ainda.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-neutral-800">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-neutral-900 text-neutral-400 uppercase text-xs tracking-wider">
                        <th class="px-6 py-4 text-left">Responsável</th>
                        <th class="px-6 py-4 text-left">Contato</th>
                        <th class="px-6 py-4 text-left">Aluno</th>
                        <th class="px-6 py-4 text-left">Status do Termo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-900">
                    @foreach($students as $student)
                        <tr
                            wire:key="{{ $student->id }}"
                            @click="select(@js($modalData($student)))"
                            class="bg-neutral-950 hover:bg-neutral-900 transition-colors duration-150 cursor-pointer"
                            title="Ver detalhes do aluno"
                        >
                            <td class="px-6 py-4 text-neutral-200">{{ $student->responsible->name }}</td>
                            <td class="px-6 py-4 text-neutral-400 font-mono">{{ Formatters::phone($student->responsible->phone_number) }}</td>
                            <td class="px-6 py-4 text-neutral-200">{{ $student->name }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusBadge($student->termo_status) }}">
                                    {{ ucfirst($student->termo_status) }}
                                </span>
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
            class="absolute inset-0 bg-black/80 backdrop-blur-sm"
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
            class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl border border-neutral-800 bg-neutral-950 shadow-2xl shadow-black/60"
        >
            {{-- Cabeçalho --}}
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-neutral-800 bg-neutral-950/95 backdrop-blur px-6 py-5">
                <div>
                    <p class="text-xs uppercase tracking-widest text-neutral-500">Detalhes do Aluno</p>
                    <h2 class="mt-1 text-2xl font-bold text-white" x-text="s.name"></h2>
                </div>
                <button
                    type="button"
                    @click="close()"
                    class="rounded-lg p-1.5 text-neutral-500 transition hover:bg-neutral-800 hover:text-white"
                    aria-label="Fechar"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="space-y-8 px-6 py-6">
                {{-- Status do Termo --}}
                <div class="rounded-xl border border-neutral-800 bg-neutral-900/50 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-neutral-400">Status do Termo</span>
                            <span
                                class="inline-block rounded-full border px-2.5 py-1 text-xs font-semibold"
                                :class="badgeClass(s.termo_status)"
                                x-text="statusLabel(s.termo_status)"
                            ></span>
                        </div>
                        <select
                            x-model="s.termo_status"
                            @change="$wire.updateTermoStatus(s.id, s.termo_status)"
                            class="rounded-md border border-neutral-700 bg-neutral-800 px-2 py-1.5 text-xs text-neutral-300 focus:outline-none focus:ring-1 focus:ring-neutral-600"
                        >
                            <option value="pendente">Pendente</option>
                            <option value="entregue">Entregue</option>
                            <option value="assinado">Assinado</option>
                        </select>
                    </div>
                </div>

                {{-- Dados do Responsável --}}
                <section>
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-neutral-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-neutral-600"></span>
                        Responsável
                    </h3>
                    <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-neutral-500">Nome</dt>
                            <dd class="mt-0.5 text-neutral-200" x-text="s.resp.name"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-neutral-500">Telefone</dt>
                            <dd class="mt-0.5 font-mono text-neutral-200" x-text="s.resp.phone"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-neutral-500">CPF</dt>
                            <dd class="mt-0.5 font-mono text-neutral-200" x-text="s.resp.cpf"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-neutral-500">E-mail</dt>
                            <dd class="mt-0.5 break-all text-neutral-200" x-text="s.resp.email"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-neutral-500">Data de Nascimento</dt>
                            <dd class="mt-0.5 text-neutral-200" x-text="s.resp.birth_date"></dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase tracking-wide text-neutral-500">Endereço</dt>
                            <dd class="mt-0.5 text-neutral-200" x-text="s.resp.address"></dd>
                        </div>
                    </dl>
                </section>

                {{-- Dados do Estudante --}}
                <section>
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-neutral-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-neutral-600"></span>
                        Estudante
                    </h3>
                    <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-neutral-500">Nome</dt>
                            <dd class="mt-0.5 text-neutral-200" x-text="s.name"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-neutral-500">CPF</dt>
                            <dd class="mt-0.5 font-mono text-neutral-200" x-text="s.cpf"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-neutral-500">RG</dt>
                            <dd class="mt-0.5 text-neutral-200" x-text="s.rg"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-neutral-500">Data de Nascimento</dt>
                            <dd class="mt-0.5 text-neutral-200" x-text="s.birth_date"></dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase tracking-wide text-neutral-500">Modalidade</dt>
                            <dd class="mt-0.5 text-neutral-200" x-text="s.modalidade"></dd>
                        </div>
                    </dl>
                </section>
            </div>
        </div>
    </div>
</div>
