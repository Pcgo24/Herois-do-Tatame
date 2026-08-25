# Admin Dashboard — Design Spec
**Date:** 2026-05-21
**Status:** Approved

## Overview

Área restrita em `/admin/dashboard` que lista todos os alunos cadastrados com os dados do responsável e permite alterar o status do termo de cada aluno. Autenticação via sistema padrão do Laravel (tabela `users`), login por e-mail ou CPF + senha.

---

## 1. Banco de Dados

### Migration: alterar tabela `students`

Adicionar duas colunas:

| Coluna | Tipo | Detalhes |
|---|---|---|
| `termo_status` | string | Default `'pendente'`. Valores válidos: `pendente`, `entregue`, `assinado` |
| `termo_arquivo` | string, nullable | Vazia por ora. Preparada para upload de PDF do termo assinado no futuro |

Nenhuma nova tabela necessária nesta entrega.

---

## 2. Rotas

Arquivo: `routes/web.php`

| Método | URI | Componente | Nome |
|---|---|---|---|
| GET | `/` | `Home` | `home` |
| GET | `/enrollment` | `EnrollmentForm` | `enrollment` (renomear de `/matricula`) |
| GET | `/admin/dashboard` | `Admin\Dashboard` | `admin.dashboard` (protegida por middleware `auth`) |
O middleware `auth` no grupo admin redireciona automaticamente para `/login` quando não autenticado (comportamento padrão do Laravel). As rotas `/login` e `/logout` **não são criadas nesta entrega**.

---

## 3. Componente Livewire

**Classe:** `App\Livewire\Admin\Dashboard`
**View:** `resources/views/livewire/admin/dashboard.blade.php`
**Layout:** `layouts.app` (existente)

### Responsabilidades

- Carregar todos os `Student` com eager loading de `responsible` (evita N+1)
- Expor método `updateTermoStatus(string $studentId, string $status)` que valida o valor contra os três estados permitidos e persiste no banco

### Colunas da tabela

| Coluna | Fonte |
|---|---|
| Nome do Responsável | `student->responsible->name` |
| Contato | `student->responsible->phone_number` |
| Nome do Aluno | `student->name` |
| Status do Termo | badge colorido + `<select>` via `wire:change` |

### Estados do badge

| Status | Badge |
|---|---|
| `pendente` | fundo vermelho escuro, texto vermelho claro |
| `entregue` | fundo amarelo escuro, texto amarelo claro |
| `assinado` | fundo verde escuro, texto verde claro |

---

## 4. Fora do Escopo desta Entrega

- Implementação da tela de login (rota preparada, sem componente)
- Upload do PDF do termo (coluna `termo_arquivo` criada, lógica não implementada)
- Busca e filtros na tabela de alunos
