<div>
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
                        <tr wire:key="{{ $student->id }}" class="bg-neutral-950 hover:bg-neutral-900 transition-colors duration-150">
                            <td class="px-6 py-4 text-neutral-200">{{ $student->responsible->name }}</td>
                            <td class="px-6 py-4 text-neutral-400 font-mono">{{ $student->responsible->phone_number }}</td>
                            <td class="px-6 py-4 text-neutral-200">{{ $student->name }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @php
                                        $badgeClass = match($student->termo_status) {
                                            'entregue' => 'bg-yellow-950 text-yellow-400 border-yellow-800',
                                            'assinado' => 'bg-green-950 text-green-400 border-green-800',
                                            default    => 'bg-red-950 text-red-400 border-red-800',
                                        };
                                    @endphp
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold border {{ $badgeClass }}">
                                        {{ ucfirst($student->termo_status) }}
                                    </span>
                                    <select
                                        wire:change="updateTermoStatus('{{ $student->id }}', $event.target.value)"
                                        class="bg-neutral-800 border border-neutral-700 text-neutral-300 text-xs rounded-md px-2 py-1 focus:outline-none focus:ring-1 focus:ring-neutral-600"
                                    >
                                        <option value="pendente" @selected($student->termo_status === 'pendente')>Pendente</option>
                                        <option value="entregue" @selected($student->termo_status === 'entregue')>Entregue</option>
                                        <option value="assinado" @selected($student->termo_status === 'assinado')>Assinado</option>
                                    </select>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
