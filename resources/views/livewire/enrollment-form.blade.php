<div>

    @if ($submitted)

        <section class="flex flex-col items-center justify-center min-h-[70vh] px-6 py-20 text-center" data-cy="success-message">
            <div class="bg-neutral-950 border border-neutral-800 rounded-2xl p-12 max-w-lg w-full">
                <div class="flex justify-center mb-6">
                    <div class="w-16 h-16 bg-green-500/10 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
                <h2 class="text-2xl font-bold text-white mb-3">Matrícula Enviada!</h2>
                <p class="text-neutral-400 mb-8">Em breve nossa equipe entrará em contato para confirmar a inscrição do aluno.</p>
                <a href="{{ route('home') }}" class="inline-block bg-white text-black font-bold px-8 py-3 rounded-lg hover:bg-neutral-200 transition">
                    Voltar ao Início
                </a>
            </div>
        </section>

    @else

        <section class="border-b border-neutral-900 px-6 py-12 text-center">
            <div class="inline-block bg-neutral-900 border border-neutral-700 text-neutral-300 rounded-full px-3 py-1 text-sm uppercase font-semibold tracking-widest mb-4">
                Projeto Social
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold uppercase mb-3">Matrícula de Aluno</h1>
            <p class="text-neutral-400 max-w-xl mx-auto">Preencha os dados do responsável e do aluno para concluir a inscrição.</p>
        </section>

        <section class="container mx-auto px-6 py-16 max-w-3xl">

            @if ($errors->has('general'))
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 rounded-xl px-6 py-4 mb-8">
                    {{ $errors->first('general') }}
                </div>
            @endif

            <form wire:submit="submit" data-cy="enrollment-form" novalidate>

                {{-- Dados do Responsável --}}
                <div class="bg-neutral-950 border border-neutral-800 rounded-2xl p-8 mb-6">
                    <h2 class="text-xl font-bold text-white mb-6 pb-3 border-b border-neutral-800">Dados do Responsável</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-neutral-400 mb-1.5">
                                Nome Completo <span class="text-red-400">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="responsible_name"
                                data-cy="input-responsible_name"
                                maxlength="80"
                                autocomplete="name"
                                class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                                       {{ $errors->has('responsible_name') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                            >
                            @error('responsible_name')
                                <p class="text-red-400 text-sm mt-1.5" data-cy="error-responsible_name">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-400 mb-1.5">
                                Telefone <span class="text-red-400">*</span>
                                <span class="text-neutral-600 text-xs">(11 dígitos)</span>
                            </label>
                            <input
                                type="tel"
                                wire:model="responsible_phone_number"
                                data-cy="input-responsible_phone_number"
                                maxlength="11"
                                inputmode="numeric"
                                placeholder="11999999999"
                                autocomplete="tel"
                                class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                                       {{ $errors->has('responsible_phone_number') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                            >
                            @error('responsible_phone_number')
                                <p class="text-red-400 text-sm mt-1.5" data-cy="error-responsible_phone_number">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-400 mb-1.5">
                                CPF <span class="text-red-400">*</span>
                                <span class="text-neutral-600 text-xs">(somente números)</span>
                            </label>
                            <input
                                type="text"
                                wire:model="responsible_cpf"
                                data-cy="input-responsible_cpf"
                                maxlength="11"
                                inputmode="numeric"
                                placeholder="12345678901"
                                class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                                       {{ $errors->has('responsible_cpf') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                            >
                            @error('responsible_cpf')
                                <p class="text-red-400 text-sm mt-1.5" data-cy="error-responsible_cpf">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-400 mb-1.5">
                                E-mail <span class="text-red-400">*</span>
                            </label>
                            <input
                                type="email"
                                wire:model="responsible_email"
                                data-cy="input-responsible_email"
                                maxlength="255"
                                autocomplete="email"
                                class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                                       {{ $errors->has('responsible_email') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                            >
                            @error('responsible_email')
                                <p class="text-red-400 text-sm mt-1.5" data-cy="error-responsible_email">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-400 mb-1.5">
                                Data de Nascimento <span class="text-red-400">*</span>
                                <span class="text-neutral-600 text-xs">(mín. 18 anos)</span>
                            </label>
                            <input
                                type="date"
                                wire:model="responsible_birth_date"
                                data-cy="input-responsible_birth_date"
                                class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                                       {{ $errors->has('responsible_birth_date') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                            >
                            @error('responsible_birth_date')
                                <p class="text-red-400 text-sm mt-1.5" data-cy="error-responsible_birth_date">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-neutral-400 mb-1.5">
                                Endereço <span class="text-red-400">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="responsible_address"
                                data-cy="input-responsible_address"
                                maxlength="150"
                                placeholder="Rua, número, bairro, cidade"
                                autocomplete="street-address"
                                class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                                       {{ $errors->has('responsible_address') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                            >
                            @error('responsible_address')
                                <p class="text-red-400 text-sm mt-1.5" data-cy="error-responsible_address">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- Dados do Aluno --}}
                <div class="bg-neutral-950 border border-neutral-800 rounded-2xl p-8 mb-8">
                    <h2 class="text-xl font-bold text-white mb-6 pb-3 border-b border-neutral-800">Dados do Aluno</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-neutral-400 mb-1.5">
                                Nome Completo <span class="text-red-400">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="student_name"
                                data-cy="input-student_name"
                                maxlength="80"
                                autocomplete="off"
                                class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                                       {{ $errors->has('student_name') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                            >
                            @error('student_name')
                                <p class="text-red-400 text-sm mt-1.5" data-cy="error-student_name">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-400 mb-1.5">
                                CPF <span class="text-red-400">*</span>
                                <span class="text-neutral-600 text-xs">(somente números)</span>
                            </label>
                            <input
                                type="text"
                                wire:model="student_cpf"
                                data-cy="input-student_cpf"
                                maxlength="11"
                                inputmode="numeric"
                                placeholder="12345678901"
                                class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                                       {{ $errors->has('student_cpf') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                            >
                            @error('student_cpf')
                                <p class="text-red-400 text-sm mt-1.5" data-cy="error-student_cpf">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-400 mb-1.5">
                                RG
                                <span class="text-neutral-600 text-xs">(opcional)</span>
                            </label>
                            <input
                                type="text"
                                wire:model="student_rg"
                                data-cy="input-student_rg"
                                maxlength="20"
                                class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                                       {{ $errors->has('student_rg') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                            >
                            @error('student_rg')
                                <p class="text-red-400 text-sm mt-1.5" data-cy="error-student_rg">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-400 mb-1.5">
                                Data de Nascimento <span class="text-red-400">*</span>
                                <span class="text-neutral-600 text-xs">(8 a 17 anos)</span>
                            </label>
                            <input
                                type="date"
                                wire:model="student_birth_date"
                                data-cy="input-student_birth_date"
                                class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                                       {{ $errors->has('student_birth_date') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                            >
                            @error('student_birth_date')
                                <p class="text-red-400 text-sm mt-1.5" data-cy="error-student_birth_date">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-400 mb-1.5">
                                Modalidade <span class="text-red-400">*</span>
                            </label>
                            <select
                                wire:model="student_modalidade"
                                data-cy="select-student_modalidade"
                                class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                                       {{ $errors->has('student_modalidade') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                            >
                                <option value="">Selecione uma modalidade</option>
                                <option value="Jiu Jitsu">Jiu Jitsu</option>
                                <option value="Muay Thai">Muay Thai</option>
                                <option value="Taekwondo">Taekwondo</option>
                                <option value="Boxe">Boxe</option>
                            </select>
                            @error('student_modalidade')
                                <p class="text-red-400 text-sm mt-1.5" data-cy="error-student_modalidade">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- Ações --}}
                <div class="flex flex-col items-center gap-4">
                    <button
                        type="submit"
                        data-cy="submit-btn"
                        wire:loading.attr="disabled"
                        class="bg-white text-black hover:bg-neutral-200 font-bold px-10 py-3 rounded-lg transition w-full md:w-auto disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                        <span wire:loading.remove>Enviar Matrícula</span>
                        <span wire:loading class="flex items-center justify-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Aguarde...
                        </span>
                    </button>
                    <a href="{{ route('home') }}" class="text-neutral-500 hover:text-neutral-300 text-sm transition">
                        ← Voltar ao início
                    </a>
                </div>

            </form>
        </section>

    @endif

</div>
