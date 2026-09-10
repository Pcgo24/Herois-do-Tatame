<div>

    @if ($submitted)

        <section class="flex flex-col items-center justify-center min-h-[70vh] px-6 py-20 text-center" data-cy="success-message">
            <div class="cartao p-12 max-w-lg w-full">
                <div class="flex justify-center mb-6">
                    <div class="w-16 h-16 bg-green-500/10 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
                <h2 class="font-display font-extrabold text-2xl mb-3">Matrícula Enviada!</h2>
                <p class="text-tatame-muted dark:text-noite-muted mb-8">Em breve nossa equipe entrará em contato para confirmar a inscrição do aluno.</p>
                <a href="{{ route('home') }}" class="botao-primario inline-block px-8 py-3">
                    Voltar ao Início
                </a>
            </div>
        </section>

    @else

        <section class="border-b border-tatame-line dark:border-noite-line px-6 py-12 text-center">
            <p class="text-sm font-semibold text-faixa-azul dark:text-faixa-amarela mb-3">Projeto social do Centro de Treinamento Marcial</p>
            <h1 class="font-display font-extrabold text-3xl md:text-4xl tracking-tight mb-3">Matrícula de aluno</h1>
            <p class="text-tatame-muted dark:text-noite-muted max-w-xl mx-auto">Preencha os dados do responsável e do aluno para concluir a inscrição.</p>
        </section>

        <section class="container mx-auto px-6 py-16 max-w-3xl">

            @if ($errors->has('general'))
                <div class="bg-red-50 dark:bg-red-500/10 border border-red-300 dark:border-red-500/30 text-red-700 dark:text-red-300 rounded-xl px-6 py-4 mb-8">
                    {{ $errors->first('general') }}
                </div>
            @endif

            <form wire:submit="submit" data-cy="enrollment-form" novalidate>

                {{-- Dados do Responsável --}}
                <div class="cartao p-8 mb-6">
                    <h2 class="font-display font-bold text-xl mb-6 pb-3 border-b border-tatame-line dark:border-noite-line">Dados do Responsável</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div class="md:col-span-2">
                            <label class="rotulo">
                                Nome Completo <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="responsible_name"
                                data-cy="input-responsible_name"
                                maxlength="80"
                                autocomplete="name"
                                x-on:input="$el.value = $el.value.replace(/[^a-zA-ZÀ-ÿ0-9 '\-]/g, '')"
                                class="campo @error('responsible_name') campo--erro @enderror"
                            >
                            @error('responsible_name')
                                <p class="erro-campo" data-cy="error-responsible_name">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-data="{
                            fmt(v) {
                                v = String(v||'').replace(/\D/g,'').substring(0,11);
                                if (v.length > 10)
                                    return '('+v.slice(0,2)+') '+v.slice(2,3)+' '+v.slice(3,7)+'-'+v.slice(7);
                                if (v.length > 6)
                                    return '('+v.slice(0,2)+') '+v.slice(2,6)+'-'+v.slice(6);
                                if (v.length > 2)
                                    return '('+v.slice(0,2)+') '+v.slice(2);
                                return v.length ? '('+v : '';
                            }
                        }">
                            <label class="rotulo">
                                Telefone <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="tel"
                                data-cy="input-responsible_phone_number"
                                maxlength="16"
                                inputmode="numeric"
                                placeholder="(42) 9 9999-9999"
                                autocomplete="tel"
                                x-effect="if (document.activeElement !== $el) $el.value = fmt($wire.responsible_phone_number)"
                                x-on:input="let r=$el.value.replace(/\D/g,'').substring(0,11); $el.value=fmt(r); $wire.set('responsible_phone_number',r,false);"
                                class="campo @error('responsible_phone_number') campo--erro @enderror"
                            >
                            @error('responsible_phone_number')
                                <p class="erro-campo" data-cy="error-responsible_phone_number">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-data="{
                            fmt(v) {
                                v = String(v||'').replace(/\D/g,'').substring(0,11);
                                return v.length>9 ? v.slice(0,3)+'.'+v.slice(3,6)+'.'+v.slice(6,9)+'-'+v.slice(9)
                                     : v.length>6 ? v.slice(0,3)+'.'+v.slice(3,6)+'.'+v.slice(6)
                                     : v.length>3 ? v.slice(0,3)+'.'+v.slice(3) : v;
                            }
                        }">
                            <label class="rotulo">
                                CPF <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="text"
                                data-cy="input-responsible_cpf"
                                maxlength="14"
                                inputmode="numeric"
                                placeholder="123.456.789-01"
                                x-effect="if (document.activeElement !== $el) $el.value = fmt($wire.responsible_cpf)"
                                x-on:input="let r=$el.value.replace(/\D/g,'').substring(0,11); $el.value=fmt(r); $wire.set('responsible_cpf',r,false);"
                                class="campo @error('responsible_cpf') campo--erro @enderror"
                            >
                            @error('responsible_cpf')
                                <p class="erro-campo" data-cy="error-responsible_cpf">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-data="{
                            fmt(v) {
                                v = String(v||'').replace(/\D/g,'').substring(0,9);
                                return v.length>8 ? v.slice(0,2)+'.'+v.slice(2,5)+'.'+v.slice(5,8)+'-'+v.slice(8)
                                     : v.length>5 ? v.slice(0,2)+'.'+v.slice(2,5)+'.'+v.slice(5)
                                     : v.length>2 ? v.slice(0,2)+'.'+v.slice(2) : v;
                            }
                        }">
                            <label class="rotulo">
                                RG <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="text"
                                data-cy="input-responsible_rg"
                                maxlength="12"
                                inputmode="numeric"
                                placeholder="12.232.343-4"
                                x-effect="if (document.activeElement !== $el) $el.value = fmt($wire.responsible_rg)"
                                x-on:input="let r=$el.value.replace(/\D/g,'').substring(0,9); $el.value=fmt(r); $wire.set('responsible_rg',r,false);"
                                class="campo @error('responsible_rg') campo--erro @enderror"
                            >
                            @error('responsible_rg')
                                <p class="erro-campo" data-cy="error-responsible_rg">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-data="{
                            fmt(v) {
                                v = String(v||'').replace(/\D/g,'').substring(0,11);
                                if (v.length > 10)
                                    return '('+v.slice(0,2)+') '+v.slice(2,3)+' '+v.slice(3,7)+'-'+v.slice(7);
                                if (v.length > 6)
                                    return '('+v.slice(0,2)+') '+v.slice(2,6)+'-'+v.slice(6);
                                if (v.length > 2)
                                    return '('+v.slice(0,2)+') '+v.slice(2);
                                return v.length ? '('+v : '';
                            }
                        }">
                            <label class="rotulo">
                                Telefone residencial
                                <span class="text-tatame-muted dark:text-noite-muted text-xs">(opcional)</span>
                            </label>
                            <input
                                type="tel"
                                data-cy="input-responsible_home_phone"
                                maxlength="16"
                                inputmode="numeric"
                                placeholder="(42) 3224-1234"
                                x-effect="if (document.activeElement !== $el) $el.value = fmt($wire.responsible_home_phone)"
                                x-on:input="let r=$el.value.replace(/\D/g,'').substring(0,11); $el.value=fmt(r); $wire.set('responsible_home_phone',r,false);"
                                class="campo @error('responsible_home_phone') campo--erro @enderror"
                            >
                            @error('responsible_home_phone')
                                <p class="erro-campo" data-cy="error-responsible_home_phone">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="rotulo">
                                E-mail <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="email"
                                wire:model="responsible_email"
                                data-cy="input-responsible_email"
                                maxlength="255"
                                autocomplete="email"
                                class="campo @error('responsible_email') campo--erro @enderror"
                            >
                            @error('responsible_email')
                                <p class="erro-campo" data-cy="error-responsible_email">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="rotulo">
                                Data de Nascimento <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                                <span class="text-tatame-muted dark:text-noite-muted text-xs">(mín. 18 anos)</span>
                            </label>
                            <input
                                type="date"
                                wire:model="responsible_birth_date"
                                data-cy="input-responsible_birth_date"
                                min="{{ \Carbon\Carbon::now()->subYears(100)->format('Y-m-d') }}"
                                max="{{ \Carbon\Carbon::now()->subYears(18)->subDay()->format('Y-m-d') }}"
                                class="campo @error('responsible_birth_date') campo--erro @enderror"
                            >
                            @error('responsible_birth_date')
                                <p class="erro-campo" data-cy="error-responsible_birth_date">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="rotulo">
                                Endereço <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="responsible_address"
                                data-cy="input-responsible_address"
                                maxlength="150"
                                placeholder="Rua, número, bairro, cidade"
                                autocomplete="street-address"
                                class="campo @error('responsible_address') campo--erro @enderror"
                            >
                            @error('responsible_address')
                                <p class="erro-campo" data-cy="error-responsible_address">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="rotulo">
                                Bairro <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="responsible_neighborhood"
                                data-cy="input-responsible_neighborhood"
                                maxlength="80"
                                class="campo @error('responsible_neighborhood') campo--erro @enderror"
                            >
                            @error('responsible_neighborhood')
                                <p class="erro-campo" data-cy="error-responsible_neighborhood">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- Dados do Aluno --}}
                <div class="cartao p-8 mb-8">
                    <h2 class="font-display font-bold text-xl mb-6 pb-3 border-b border-tatame-line dark:border-noite-line">Dados do Aluno</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div class="md:col-span-2">
                            <label class="rotulo">
                                Nome Completo <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="student_name"
                                data-cy="input-student_name"
                                maxlength="80"
                                autocomplete="off"
                                x-on:input="$el.value = $el.value.replace(/[^a-zA-ZÀ-ÿ0-9 '\-]/g, '')"
                                class="campo @error('student_name') campo--erro @enderror"
                            >
                            @error('student_name')
                                <p class="erro-campo" data-cy="error-student_name">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-data="{
                            fmt(v) {
                                v = String(v||'').replace(/\D/g,'').substring(0,11);
                                return v.length>9 ? v.slice(0,3)+'.'+v.slice(3,6)+'.'+v.slice(6,9)+'-'+v.slice(9)
                                     : v.length>6 ? v.slice(0,3)+'.'+v.slice(3,6)+'.'+v.slice(6)
                                     : v.length>3 ? v.slice(0,3)+'.'+v.slice(3) : v;
                            }
                        }">
                            <label class="rotulo">
                                CPF <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="text"
                                data-cy="input-student_cpf"
                                maxlength="14"
                                inputmode="numeric"
                                placeholder="123.456.789-01"
                                x-effect="if (document.activeElement !== $el) $el.value = fmt($wire.student_cpf)"
                                x-on:input="let r=$el.value.replace(/\D/g,'').substring(0,11); $el.value=fmt(r); $wire.set('student_cpf',r,false);"
                                class="campo @error('student_cpf') campo--erro @enderror"
                            >
                            @error('student_cpf')
                                <p class="erro-campo" data-cy="error-student_cpf">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-data="{
                            fmt(v) {
                                v = String(v||'').replace(/\D/g,'').substring(0,9);
                                return v.length>8 ? v.slice(0,2)+'.'+v.slice(2,5)+'.'+v.slice(5,8)+'-'+v.slice(8)
                                     : v.length>5 ? v.slice(0,2)+'.'+v.slice(2,5)+'.'+v.slice(5)
                                     : v.length>2 ? v.slice(0,2)+'.'+v.slice(2) : v;
                            }
                        }">
                            <label class="rotulo">
                                RG <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="text"
                                data-cy="input-student_rg"
                                maxlength="12"
                                inputmode="numeric"
                                placeholder="12.232.343-4"
                                x-effect="if (document.activeElement !== $el) $el.value = fmt($wire.student_rg)"
                                x-on:input="let r=$el.value.replace(/\D/g,'').substring(0,9); $el.value=fmt(r); $wire.set('student_rg',r,false);"
                                class="campo @error('student_rg') campo--erro @enderror"
                            >
                            @error('student_rg')
                                <p class="erro-campo" data-cy="error-student_rg">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="rotulo">
                                Data de Nascimento <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                                <span class="text-tatame-muted dark:text-noite-muted text-xs">(8 a 17 anos)</span>
                            </label>
                            <input
                                type="date"
                                wire:model="student_birth_date"
                                data-cy="input-student_birth_date"
                                min="{{ \Carbon\Carbon::now()->subYears(18)->addDay()->format('Y-m-d') }}"
                                max="{{ \Carbon\Carbon::now()->subYears(8)->format('Y-m-d') }}"
                                class="campo @error('student_birth_date') campo--erro @enderror"
                            >
                            @error('student_birth_date')
                                <p class="erro-campo" data-cy="error-student_birth_date">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="rotulo">
                                Escola <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="student_school"
                                data-cy="input-student_school"
                                maxlength="120"
                                class="campo @error('student_school') campo--erro @enderror"
                            >
                            @error('student_school')
                                <p class="erro-campo" data-cy="error-student_school">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="rotulo">
                                Série <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="student_grade"
                                data-cy="input-student_grade"
                                maxlength="30"
                                placeholder="5º ano"
                                class="campo @error('student_grade') campo--erro @enderror"
                            >
                            @error('student_grade')
                                <p class="erro-campo" data-cy="error-student_grade">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-data="{
                            fmt(v) {
                                v = String(v||'').replace(/\D/g,'').substring(0,11);
                                if (v.length > 10)
                                    return '('+v.slice(0,2)+') '+v.slice(2,3)+' '+v.slice(3,7)+'-'+v.slice(7);
                                if (v.length > 6)
                                    return '('+v.slice(0,2)+') '+v.slice(2,6)+'-'+v.slice(6);
                                if (v.length > 2)
                                    return '('+v.slice(0,2)+') '+v.slice(2);
                                return v.length ? '('+v : '';
                            }
                        }">
                            <label class="rotulo">
                                Celular do aluno
                                <span class="text-tatame-muted dark:text-noite-muted text-xs">(opcional)</span>
                            </label>
                            <input
                                type="tel"
                                data-cy="input-student_phone"
                                maxlength="16"
                                inputmode="numeric"
                                placeholder="(42) 9 9999-9999"
                                x-effect="if (document.activeElement !== $el) $el.value = fmt($wire.student_phone)"
                                x-on:input="let r=$el.value.replace(/\D/g,'').substring(0,11); $el.value=fmt(r); $wire.set('student_phone',r,false);"
                                class="campo @error('student_phone') campo--erro @enderror"
                            >
                            @error('student_phone')
                                <p class="erro-campo" data-cy="error-student_phone">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="rotulo">
                                E-mail do aluno
                                <span class="text-tatame-muted dark:text-noite-muted text-xs">(opcional)</span>
                            </label>
                            <input
                                type="email"
                                wire:model="student_email"
                                data-cy="input-student_email"
                                maxlength="255"
                                autocomplete="off"
                                class="campo @error('student_email') campo--erro @enderror"
                            >
                            @error('student_email')
                                <p class="erro-campo" data-cy="error-student_email">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2 border-t border-tatame-line dark:border-noite-line pt-6">
                            <p class="text-sm font-semibold mb-4">Filiação</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                <div>
                                    <label class="rotulo">
                                        Nome do pai <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        wire:model="student_father_name"
                                        data-cy="input-student_father_name"
                                        maxlength="80"
                                        x-bind:disabled="$wire.student_no_father"
                                        x-on:input="$el.value = $el.value.replace(/[^a-zA-Z\u00C0-\u00ff '\-]/g, '')"
                                        class="campo @error('student_father_name') campo--erro @enderror"
                                    >
                                    <label class="flex items-center gap-2 mt-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            wire:model.live="student_no_father"
                                            data-cy="checkbox-student_no_father"
                                            class="w-4 h-4 accent-faixa-azul cursor-pointer"
                                        >
                                        <span class="text-sm text-tatame-muted dark:text-noite-muted">Não possui pai registrado</span>
                                    </label>
                                    @error('student_father_name')
                                        <p class="erro-campo" data-cy="error-student_father_name">{{ $message }}</p>
                                    @enderror
                                    @error('student_no_father')
                                        <p class="erro-campo" data-cy="error-student_no_father">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="rotulo">
                                        Nome da mãe <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        wire:model="student_mother_name"
                                        data-cy="input-student_mother_name"
                                        maxlength="80"
                                        x-bind:disabled="$wire.student_no_mother"
                                        x-on:input="$el.value = $el.value.replace(/[^a-zA-Z\u00C0-\u00ff '\-]/g, '')"
                                        class="campo @error('student_mother_name') campo--erro @enderror"
                                    >
                                    <label class="flex items-center gap-2 mt-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            wire:model.live="student_no_mother"
                                            data-cy="checkbox-student_no_mother"
                                            class="w-4 h-4 accent-faixa-azul cursor-pointer"
                                        >
                                        <span class="text-sm text-tatame-muted dark:text-noite-muted">Não possui mãe registrada</span>
                                    </label>
                                    @error('student_mother_name')
                                        <p class="erro-campo" data-cy="error-student_mother_name">{{ $message }}</p>
                                    @enderror
                                </div>

                            </div>
                        </div>

                        <div>
                            <label class="rotulo">
                                Modalidade <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                            </label>
                            <select
                                wire:model="student_modalidade"
                                data-cy="select-student_modalidade"
                                class="campo @error('student_modalidade') campo--erro @enderror"
                            >
                                <option value="">Selecione uma modalidade</option>
                                <option value="Jiu Jitsu">Jiu Jitsu</option>
                                <option value="Muay Thai">Muay Thai</option>
                                <option value="Taekwondo">Taekwondo</option>
                                <option value="Boxe">Boxe</option>
                            </select>
                            @error('student_modalidade')
                                <p class="erro-campo" data-cy="error-student_modalidade">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- Aceite LGPD --}}
                <div class="cartao p-8 mb-6">
                    <label for="lgpd_consent" class="flex items-start gap-3 cursor-pointer">
                        <input
                            id="lgpd_consent"
                            type="checkbox"
                            wire:model="lgpd_consent"
                            data-cy="lgpd-consent-checkbox"
                            class="mt-1 w-4 h-4 accent-faixa-azul cursor-pointer"
                        >
                        <span class="text-sm text-tatame-muted dark:text-noite-muted leading-relaxed">
                            Declaro que li e concordo que os dados informados serão utilizados
                            exclusivamente para a geração da ficha de cadastro de atleta exigida
                            pela Secretaria Municipal de Esportes e Recreação de Prudentópolis e
                            para controle interno do projeto, conforme a
                            <strong class="text-tatame-ink dark:text-noite-ink">Lei Geral de Proteção de Dados (LGPD)</strong>.
                        </span>
                    </label>
                    @error('lgpd_consent')
                        <p class="erro-campo" data-cy="error-lgpd_consent">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Ações --}}
                <div class="flex flex-col items-center gap-4">
                    <button
                        type="submit"
                        data-cy="submit-btn"
                        wire:loading.attr="disabled" wire:target="submit"
                        :disabled="!$wire.lgpd_consent"
                        class="botao-primario w-full md:w-auto px-10 py-3"
                    >
                        <span wire:loading.remove wire:target="submit">Enviar Matrícula</span>
                        <span wire:loading wire:target="submit" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Aguarde...
                        </span>
                    </button>
                    <a href="{{ route('home') }}" class="text-tatame-muted dark:text-noite-muted hover:text-tatame-ink dark:hover:text-noite-ink text-sm transition">
                        ← Voltar ao início
                    </a>
                </div>

            </form>
        </section>

    @endif

</div>
