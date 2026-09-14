<div class="max-w-md mx-auto">

    <h1 class="font-display font-extrabold text-2xl mb-1">Alterar senha</h1>
    <p class="text-tatame-muted dark:text-noite-muted text-sm mb-8">A nova senha vale a partir do próximo login.</p>

    <div class="cartao p-8">

        @if ($saved)
            <div class="mb-6 rounded-lg border border-green-300 bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-400 dark:border-green-800 px-4 py-3 text-sm" data-cy="password-saved">
                Senha alterada com sucesso.
            </div>
        @endif

        <form wire:submit="save" data-cy="password-form" novalidate>

            <div class="mb-5">
                <label class="rotulo" for="senha-atual">Senha atual</label>
                <input
                    id="senha-atual"
                    type="password"
                    wire:model="current_password"
                    data-cy="input-current-password"
                    autocomplete="current-password"
                    class="campo @error('current_password') campo--erro @enderror"
                >
                @error('current_password')
                    <p class="erro-campo" data-cy="error-current-password">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label class="rotulo" for="senha-nova">Nova senha</label>
                <input
                    id="senha-nova"
                    type="password"
                    wire:model="password"
                    data-cy="input-new-password"
                    autocomplete="new-password"
                    class="campo @error('password') campo--erro @enderror"
                >
                @error('password')
                    <p class="erro-campo" data-cy="error-new-password">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-8">
                <label class="rotulo" for="senha-confirmacao">Confirmar nova senha</label>
                <input
                    id="senha-confirmacao"
                    type="password"
                    wire:model="password_confirmation"
                    data-cy="input-password-confirmation"
                    autocomplete="new-password"
                    class="campo"
                >
            </div>

            <div class="flex items-center justify-between gap-4">
                <a href="{{ route(auth()->user()->homeRoute()) }}" class="text-sm text-tatame-muted dark:text-noite-muted hover:text-tatame-ink dark:hover:text-noite-ink transition">← Voltar</a>
                <button
                    type="submit"
                    data-cy="password-save-btn"
                    wire:loading.attr="disabled" wire:target="save"
                    class="botao-primario px-8 py-3"
                >
                    <span wire:loading.remove wire:target="save">Salvar</span>
                    <span wire:loading wire:target="save">Salvando...</span>
                </button>
            </div>

        </form>
    </div>
</div>
