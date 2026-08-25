<div class="w-full max-w-md">

    <div class="flex items-center justify-center gap-2 mb-8">
        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
        <a href="{{ route('home') }}" class="text-xl font-bold tracking-widest uppercase">Heróis do Tatame</a>
    </div>

    <div class="bg-neutral-950 border border-neutral-800 rounded-2xl p-8">

        <h1 class="text-2xl font-bold text-white mb-1">Área do Professor</h1>
        <p class="text-neutral-500 text-sm mb-8">Entre com seu CPF e senha para acessar os cadastros.</p>

        <form wire:submit="login" data-cy="login-form" novalidate>

            <div class="mb-5" x-data="{
                fmt(v) {
                    v = String(v||'').replace(/\D/g,'').substring(0,11);
                    return v.length>9 ? v.slice(0,3)+'.'+v.slice(3,6)+'.'+v.slice(6,9)+'-'+v.slice(9)
                         : v.length>6 ? v.slice(0,3)+'.'+v.slice(3,6)+'.'+v.slice(6)
                         : v.length>3 ? v.slice(0,3)+'.'+v.slice(3) : v;
                }
            }">
                <label class="block text-sm font-medium text-neutral-400 mb-1.5">CPF</label>
                <input
                    type="text"
                    data-cy="input-cpf"
                    maxlength="14"
                    inputmode="numeric"
                    autocomplete="username"
                    placeholder="123.456.789-01"
                    x-effect="if (document.activeElement !== $el) $el.value = fmt($wire.cpf)"
                    x-on:input="let r=$el.value.replace(/\D/g,'').substring(0,11); $el.value=fmt(r); $wire.set('cpf',r);"
                    class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                           {{ $errors->has('cpf') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                >
                @error('cpf')
                    <p class="text-red-400 text-sm mt-1.5" data-cy="error-cpf">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label class="block text-sm font-medium text-neutral-400 mb-1.5">Senha</label>
                <input
                    type="password"
                    wire:model="password"
                    data-cy="input-password"
                    autocomplete="current-password"
                    class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                           {{ $errors->has('password') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                >
                @error('password')
                    <p class="text-red-400 text-sm mt-1.5" data-cy="error-password">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 mb-8 cursor-pointer">
                <input type="checkbox" wire:model="remember" data-cy="input-remember" class="w-4 h-4 accent-white cursor-pointer">
                <span class="text-sm text-neutral-400">Manter conectado neste dispositivo</span>
            </label>

            <button
                type="submit"
                data-cy="login-btn"
                wire:loading.attr="disabled"
                class="bg-white text-black hover:bg-neutral-200 font-bold px-10 py-3 rounded-lg transition w-full disabled:opacity-60 disabled:cursor-not-allowed"
            >
                <span wire:loading.remove>Entrar</span>
                <span wire:loading class="flex items-center justify-center gap-2">
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
        <a href="{{ route('home') }}" class="text-neutral-500 hover:text-neutral-300 text-sm transition">← Voltar ao início</a>
    </p>
</div>
