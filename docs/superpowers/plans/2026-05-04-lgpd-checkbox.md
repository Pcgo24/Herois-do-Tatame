# LGPD Consent Checkbox Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Adicionar checkbox de aceite LGPD inline antes do botão de envio do formulário de matrícula, bloqueando o envio server-side (regra `accepted`) e client-side (`:disabled` no botão via Alpine.js).

**Architecture:** A propriedade `lgpd_consent` vive no componente Livewire e é validada pelo `StoreEnrollmentRequest` com a regra `accepted`. O botão de envio fica desabilitado via `:disabled="!$wire.lgpd_consent"` no blade. Os testes PHPUnit são TDD-first; os Cypress são atualizados por último.

**Tech Stack:** Laravel 13, Livewire 4, Alpine.js, Tailwind CSS, PHPUnit, Cypress.

---

## Files

| Ação | Arquivo |
|------|---------|
| Modify | `app/Livewire/EnrollmentForm.php` |
| Modify | `app/Http/Requests/StoreEnrollmentRequest.php` |
| Modify | `resources/views/livewire/enrollment-form.blade.php` |
| Modify | `tests/Feature/EnrollmentTest.php` |
| Modify | `cypress/e2e/enrollment.cy.js` |

---

## Task 1: Backend — propriedade Livewire + regra de validação

**Files:**
- Modify: `app/Livewire/EnrollmentForm.php`
- Modify: `app/Http/Requests/StoreEnrollmentRequest.php`
- Modify: `tests/Feature/EnrollmentTest.php`

- [ ] **Step 1: Escrever testes PHPUnit que ainda vão falhar**

Em `tests/Feature/EnrollmentTest.php`:

1. Adicionar `'lgpd_consent' => true` ao array retornado por `validPayload()`:

```php
private function validPayload(): array
{
    return [
        'responsible_name'         => 'Maria da Silva',
        'responsible_phone_number' => '11999999999',
        'responsible_cpf'          => '12345678901',
        'responsible_email'        => 'maria@email.com',
        'responsible_birth_date'   => '1990-01-15',
        'responsible_address'      => 'Rua das Flores, 123, São Paulo',
        'student_name'             => 'João da Silva',
        'student_cpf'              => '98765432100',
        'student_rg'               => '123456789',
        'student_birth_date'       => '2015-06-10',
        'student_modalidade'       => 'Jiu Jitsu',
        'lgpd_consent'             => true,
    ];
}
```

2. Adicionar `->set('lgpd_consent', $data['lgpd_consent'])` ao final do `fillForm()` (antes do `;`):

```php
private function fillForm(array $overrides = []): mixed
{
    $data = array_merge($this->validPayload(), $overrides);

    return Livewire::test(EnrollmentForm::class)
        ->set('responsible_name', $data['responsible_name'])
        ->set('responsible_phone_number', $data['responsible_phone_number'])
        ->set('responsible_cpf', $data['responsible_cpf'])
        ->set('responsible_email', $data['responsible_email'])
        ->set('responsible_birth_date', $data['responsible_birth_date'])
        ->set('responsible_address', $data['responsible_address'])
        ->set('student_name', $data['student_name'])
        ->set('student_cpf', $data['student_cpf'])
        ->set('student_rg', $data['student_rg'])
        ->set('student_birth_date', $data['student_birth_date'])
        ->set('student_modalidade', $data['student_modalidade'])
        ->set('lgpd_consent', $data['lgpd_consent']);
}
```

3. Adicionar os dois novos testes ao final da classe (antes do `}`):

```php
public function test_lgpd_consent_is_required(): void
{
    $this->fillForm(['lgpd_consent' => false])
        ->call('submit')
        ->assertHasErrors(['lgpd_consent']);
}

public function test_submission_blocked_without_lgpd_consent(): void
{
    $this->fillForm(['lgpd_consent' => false])->call('submit');

    $this->assertDatabaseMissing('responsibles', ['cpf' => '12345678901']);
    $this->assertDatabaseMissing('students', ['cpf' => '98765432100']);
}
```

- [ ] **Step 2: Rodar os novos testes e confirmar que falham**

```bash
./vendor/bin/sail artisan test --filter test_lgpd_consent_is_required
./vendor/bin/sail artisan test --filter test_submission_blocked_without_lgpd_consent
```

Esperado: ambos FAIL — `lgpd_consent` não existe ainda no componente.

- [ ] **Step 3: Adicionar propriedade `lgpd_consent` ao componente Livewire**

Em `app/Livewire/EnrollmentForm.php`, adicionar após `public bool $submitted = false;`:

```php
public bool $lgpd_consent = false;
```

- [ ] **Step 4: Adicionar regra `accepted` ao StoreEnrollmentRequest**

Em `app/Http/Requests/StoreEnrollmentRequest.php`, adicionar ao final do array retornado por `enrollmentRules()`:

```php
'lgpd_consent' => ['accepted'],
```

E ao final do array retornado por `enrollmentMessages()`:

```php
'lgpd_consent.accepted' => 'Você precisa concordar com o uso dos dados para prosseguir.',
```

- [ ] **Step 5: Rodar todos os testes PHPUnit e confirmar que passam**

```bash
./vendor/bin/sail artisan test
```

Esperado: todos os testes passam (incluindo os existentes, que agora setam `lgpd_consent => true` via `fillForm()`).

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/EnrollmentForm.php \
        app/Http/Requests/StoreEnrollmentRequest.php \
        tests/Feature/EnrollmentTest.php
git commit -m "feat: adiciona propriedade e validação LGPD no formulário de matrícula"
```

---

## Task 2: View — checkbox inline + botão desabilitado

**Files:**
- Modify: `resources/views/livewire/enrollment-form.blade.php`

- [ ] **Step 1: Adicionar bloco do checkbox antes da div de ações**

Localizar o comentário `{{-- Ações --}}` (linha 310) e inserir o bloco logo antes dele:

```blade
{{-- Aceite LGPD --}}
<div class="bg-neutral-950 border border-neutral-800 rounded-2xl px-8 py-6 mb-6">
    <label class="flex items-start gap-3 cursor-pointer">
        <input
            type="checkbox"
            wire:model="lgpd_consent"
            data-cy="lgpd-consent-checkbox"
            class="mt-1 w-4 h-4 accent-white cursor-pointer"
        >
        <span class="text-sm text-neutral-400 leading-relaxed">
            Declaro que li e concordo que os dados informados serão utilizados
            exclusivamente para geração do termo de aceite para assinatura e
            para controle interno do CTM, conforme a
            <strong class="text-neutral-300">Lei Geral de Proteção de Dados (LGPD)</strong>.
        </span>
    </label>
    @error('lgpd_consent')
        <p class="text-red-400 text-sm mt-3" data-cy="error-lgpd_consent">{{ $message }}</p>
    @enderror
</div>

```

- [ ] **Step 2: Atualizar o botão de envio com `:disabled`**

Substituir o `<button type="submit" ...>` atual por:

```blade
<button
    type="submit"
    data-cy="submit-btn"
    wire:loading.attr="disabled"
    :disabled="!$wire.lgpd_consent"
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
```

- [ ] **Step 3: Rodar testes PHPUnit para confirmar que não houve regressão**

```bash
./vendor/bin/sail artisan test
```

Esperado: todos passam.

- [ ] **Step 4: Commit**

```bash
git add resources/views/livewire/enrollment-form.blade.php
git commit -m "feat: adiciona checkbox de aceite LGPD e desabilita botão sem aceite"
```

---

## Task 3: Testes Cypress — atualizar submit existente + novo teste

**Files:**
- Modify: `cypress/e2e/enrollment.cy.js`

- [ ] **Step 1: Atualizar o teste de submissão bem-sucedida**

No teste `submete com dados válidos e exibe confirmação`, adicionar o check do checkbox logo antes do clique no botão de envio:

```js
// adicionar logo antes de cy.get('[data-cy="submit-btn"]').click();
cy.get('[data-cy="lgpd-consent-checkbox"]').check();
cy.get('[data-cy="submit-btn"]').click();
```

O bloco completo fica:

```js
it('submete com dados válidos e exibe confirmação', () => {
  const r = responsible();
  const s = student();

  cy.get('[data-cy="input-responsible_name"]').type(r.name);
  cy.get('[data-cy="input-responsible_phone_number"]').type(r.phone);
  cy.get('[data-cy="input-responsible_cpf"]').type(r.cpf);
  cy.get('[data-cy="input-responsible_email"]').type(r.email);
  cy.get('[data-cy="input-responsible_birth_date"]').type(r.birthDate);
  cy.get('[data-cy="input-responsible_address"]').type(r.address);

  cy.get('[data-cy="input-student_name"]').type(s.name);
  cy.get('[data-cy="input-student_cpf"]').type(s.cpf);
  cy.get('[data-cy="input-student_rg"]').type(s.rg);
  cy.get('[data-cy="input-student_birth_date"]').type(s.birthDate);
  cy.get('[data-cy="select-student_modalidade"]').select(s.modalidade);

  cy.get('[data-cy="lgpd-consent-checkbox"]').check();
  cy.get('[data-cy="submit-btn"]').click();
  cy.get('[data-cy="success-message"]', { timeout: 10000 }).should('be.visible');
});
```

- [ ] **Step 2: Adicionar novo teste — botão desabilitado sem aceite**

Dentro do `describe('Formulário de matrícula', ...)`, adicionar após o último `it(...)`:

```js
it('o botão de envio fica desabilitado sem aceite LGPD', () => {
  cy.get('[data-cy="submit-btn"]').should('be.disabled');
  cy.get('[data-cy="lgpd-consent-checkbox"]').check();
  cy.get('[data-cy="submit-btn"]').should('not.be.disabled');
});
```

- [ ] **Step 3: Subir o ambiente e rodar os testes Cypress**

```bash
./vendor/bin/sail up -d
./vendor/bin/sail npm run build
npx cypress run
```

Esperado: todos os testes Cypress passam, incluindo o novo.

- [ ] **Step 4: Commit**

```bash
git add cypress/e2e/enrollment.cy.js
git commit -m "test: atualiza Cypress para aceite LGPD e adiciona teste de botão desabilitado"
```
