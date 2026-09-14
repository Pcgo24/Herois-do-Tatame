<div>

    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display font-extrabold text-3xl tracking-tight">Usuários</h1>
            <p class="text-tatame-muted dark:text-noite-muted mt-1 text-sm">Quem pode entrar na área do professor.</p>
        </div>
        <div class="flex items-center gap-4">
            <label class="flex items-center gap-2 cursor-pointer text-sm text-tatame-muted dark:text-noite-muted">
                <input type="checkbox" wire:model.live="showRemoved" data-cy="toggle-removed" class="w-4 h-4 accent-faixa-azul cursor-pointer">
                Mostrar removidos
            </label>
            <button type="button" wire:click="create" data-cy="user-create-btn" class="botao-primario px-5 py-2.5 text-sm">Novo usuário</button>
        </div>
    </div>

    @error('remove')
        <div class="mb-6 rounded-lg border border-red-300 bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-400 dark:border-red-800 px-4 py-3 text-sm" data-cy="error-remove">
            {{ $message }}
        </div>
    @enderror

    <div class="overflow-x-auto rounded-xl border border-tatame-line dark:border-noite-line">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-tatame-raised dark:bg-noite-raised text-tatame-muted dark:text-noite-muted text-xs font-semibold">
                    <th class="px-6 py-4 text-left">Nome</th>
                    <th class="px-6 py-4 text-left">Usuário</th>
                    <th class="px-6 py-4 text-left">Criado em</th>
                    <th class="px-6 py-4 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-tatame-line dark:divide-noite-line">
                @foreach ($users as $user)
                    <tr wire:key="user-{{ $user->id }}" data-cy="user-row" @class(['opacity-60' => $user->trashed()])>
                        <td class="px-6 py-4 font-semibold">
                            {{ $user->name }}
                            @if ($user->id === auth()->id())
                                <span class="ml-2 text-xs font-normal text-tatame-muted dark:text-noite-muted">(você)</span>
                            @endif
                            @if ($user->trashed())
                                <span class="ml-2 text-xs font-semibold rounded-full border border-red-300 bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-400 dark:border-red-800 px-2 py-0.5">Removido</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-mono text-tatame-muted dark:text-noite-muted">{{ $user->username }}</td>
                        <td class="px-6 py-4 text-tatame-muted dark:text-noite-muted">{{ $user->created_at->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            @if ($user->trashed())
                                <button type="button" wire:click="restore({{ $user->id }})" data-cy="user-restore-btn" class="botao-secundario px-3 py-1.5 text-xs">Restaurar</button>
                            @else
                                <button type="button" wire:click="edit({{ $user->id }})" data-cy="user-edit-btn" class="botao-secundario px-3 py-1.5 text-xs">Editar</button>
                                <button
                                    type="button"
                                    wire:click="remove({{ $user->id }})"
                                    wire:confirm="Remover {{ $user->name }}? Ele deixa de conseguir entrar, mas pode ser restaurado depois."
                                    data-cy="user-remove-btn"
                                    class="ml-2 text-xs font-semibold text-red-700 dark:text-red-400 hover:underline"
                                >Remover</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Modal de criação/edição --}}
    <div
        x-data
        x-show="$wire.modalOpen"
        x-cloak
        @keydown.escape.window="$wire.closeModal()"
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6"
    >
        <div x-show="$wire.modalOpen" x-transition.opacity.duration.200ms @click="$wire.closeModal()" class="absolute inset-0 bg-black/85"></div>

        <div
            x-show="$wire.modalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="relative w-full max-w-md rounded-2xl border border-tatame-line dark:border-noite-line bg-tatame-surface dark:bg-noite-surface shadow-2xl shadow-black/25 dark:shadow-black/60 p-8"
        >
            <h2 class="font-display font-extrabold text-xl mb-6">{{ $editingId ? 'Editar usuário' : 'Novo usuário' }}</h2>

            <form wire:submit="save" data-cy="user-form" novalidate>

                <div class="mb-5">
                    <label class="rotulo" for="user-name">Nome</label>
                    <input id="user-name" type="text" wire:model="name" data-cy="input-user-name" maxlength="100" class="campo @error('name') campo--erro @enderror">
                    @error('name') <p class="erro-campo" data-cy="error-user-name">{{ $message }}</p> @enderror
                </div>

                <div class="mb-5">
                    <label class="rotulo" for="user-username">Usuário</label>
                    <input id="user-username" type="text" wire:model="username" data-cy="input-user-username" maxlength="30" autocapitalize="none" spellcheck="false" placeholder="nome_sobrenome" class="campo @error('username') campo--erro @enderror">
                    @error('username') <p class="erro-campo" data-cy="error-user-username">{{ $message }}</p> @enderror
                </div>

                <div class="mb-5">
                    <label class="rotulo" for="user-password">{{ $editingId ? 'Nova senha (deixe em branco para manter)' : 'Senha' }}</label>
                    <input id="user-password" type="password" wire:model="password" data-cy="input-user-password" autocomplete="new-password" class="campo @error('password') campo--erro @enderror">
                    @error('password') <p class="erro-campo" data-cy="error-user-password">{{ $message }}</p> @enderror
                </div>

                <div class="mb-8">
                    <label class="rotulo" for="user-password-confirmation">Confirmar senha</label>
                    <input id="user-password-confirmation" type="password" wire:model="password_confirmation" data-cy="input-user-password-confirmation" autocomplete="new-password" class="campo">
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="button" wire:click="closeModal" class="botao-secundario px-5 py-2.5 text-sm">Cancelar</button>
                    <button type="submit" data-cy="user-save-btn" wire:loading.attr="disabled" wire:target="save" class="botao-primario px-6 py-2.5 text-sm">
                        <span wire:loading.remove wire:target="save">Salvar</span>
                        <span wire:loading wire:target="save">Salvando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
