<div class="w-full max-w-md">

    <x-marca class="justify-center mb-8" logo="h-14" texto="text-xl" />

    <div class="cartao p-8">

        <h1 class="font-display font-extrabold text-2xl mb-1">Área do Professor</h1>
        <p class="text-tatame-muted dark:text-noite-muted text-sm mb-8">Entre com seu CPF e senha para acessar os cadastros.</p>

        <form wire:submit="login" data-cy="login-form" novalidate>

            <div class="mb-5" x-data="{
                fmt(v) {
                    v = String(v||'').replace(/\D/g,'').substring(0,11);
                    return v.length>9 ? v.slice(0,3)+'.'+v.slice(3,6)+'.'+v.slice(6,9)+'-'+v.slice(9)
                         : v.length>6 ? v.slice(0,3)+'.'+v.slice(3,6)+'.'+v.slice(6)
                         : v.length>3 ? v.slice(0,3)+'.'+v.slice(3) : v;
                }
            }">
                <label class="rotulo" for="cpf">CPF</label>
                <input
                    id="cpf"
                    type="text"
                    data-cy="input-cpf"
                    maxlength="14"
                    inputmode="numeric"
                    autocomplete="username"
                    placeholder="123.456.789-01"
                    x-effect="if (document.activeElement !== $el) $el.value = fmt($wire.cpf)"
                    x-on:input="let r=$el.value.replace(/\D/g,'').substring(0,11); $el.value=fmt(r); $wire.set('cpf',r,false);"
                    class="campo @error('cpf') campo--erro @enderror"
                >
                @error('cpf')
                    <p class="erro-campo" data-cy="error-cpf">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label class="rotulo" for="senha">Senha</label>
                <input
                    id="senha"
                    type="password"
                    wire:model="password"
                    data-cy="input-password"
                    autocomplete="current-password"
                    class="campo @error('password') campo--erro @enderror"
                >
                @error('password')
                    <p class="erro-campo" data-cy="error-password">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 mb-8 cursor-pointer">
                <input type="checkbox" wire:model="remember" data-cy="input-remember" class="w-4 h-4 accent-faixa-azul cursor-pointer">
                <span class="text-sm text-tatame-muted dark:text-noite-muted">Manter conectado neste dispositivo</span>
            </label>

            <button
                type="submit"
                data-cy="login-btn"
                wire:loading.attr="disabled" wire:target="login"
                class="botao-primario w-full px-10 py-3"
            >
                <span wire:loading.remove wire:target="login">Entrar</span>
                <span wire:loading wire:target="login" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Entrando...
                </span>
            </button>

        </form>
    </div>

    <p class="text-center mt-6">
        <a href="{{ route('home') }}" class="text-tatame-muted dark:text-noite-muted hover:text-tatame-ink dark:hover:text-noite-ink text-sm transition">← Voltar ao início</a>
    </p>
</div>
