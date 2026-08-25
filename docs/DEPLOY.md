# Deploy no Render

O app roda em um contêiner Docker com nginx + php-fpm. O banco é o Neon
(PostgreSQL) e as fichas assinadas vão para o Cloudflare R2.

Três coisas explicam por que o setup é assim:

- **Sessão, cache e fila moram no banco.** Não há Redis. Cada requisição
  conversa com o Postgres várias vezes, então a distância entre o app e o banco
  importa mais do que o normal — daí o passo 1.
- **O disco do Render é descartado a cada implantação.** As fichas assinadas
  precisam de armazenamento externo, senão somem — daí o passo 2.
- **Não há dados reais para preservar.** O contêiner roda as migrations sozinho
  ao subir, então o banco novo nasce pronto.

---

## 1. Banco: projeto Neon na mesma região do app

O Neon atual está em `sa-east-1` (São Paulo). O Render não tem região na
América do Sul, então o app ficaria nos EUA conversando com um banco no Brasil
— várias viagens de ida e volta por requisição.

1. No painel do Neon, crie um **projeto novo** em **AWS US West 2 (Oregon)**,
   para casar com a região `oregon` do Render.
2. Copie os dados da string de conexão. Ela tem o formato
   `postgresql://USUARIO:SENHA@HOST/BANCO?sslmode=require`.
3. Não migre nada. O contêiner roda `php artisan migrate --force` ao subir e
   cria o schema do zero.

O projeto antigo pode continuar existindo como banco de desenvolvimento.

A string do Neon vem inteira, no formato
`postgresql://USUARIO:SENHA@HOST/BANCO?sslmode=require`, e precisa ser quebrada
nas variáveis do Render — **elas vão no Render, nunca no Cloudflare**:

| Variável | Pedaço da string |
|---|---|
| `DB_HOST` | o `HOST`, algo como `ep-xxxx.us-west-2.aws.neon.tech` |
| `DB_DATABASE` | o `BANCO`, normalmente `neondb` |
| `DB_USERNAME` | o `USUARIO` |
| `DB_PASSWORD` | a `SENHA` |
| `DB_PORT` | `5432`, fixo |

O `sslmode=require` não vira variável: o driver `pgsql` do Laravel já negocia
TLS com o Neon.

## 2. Armazenamento: bucket no Cloudflare R2

O R2 oferece 10 GB gratuitos, mas a Cloudflare exige um **cartão cadastrado**
para liberar o serviço, mesmo sem cobrança. Se isso for um impedimento, o
Supabase Storage também é compatível com S3, tem 1 GB grátis e não pede cartão:
o disco `r2` de `config/filesystems.php` funciona com ele trocando apenas o
`R2_ENDPOINT`.

1. No painel da Cloudflare, barra lateral: **Storage & databases > R2 Object
   Storage > Create bucket**. Nome sugerido: `herois-do-tatame`, location
   **North America (West)** para ficar perto da região `oregon` do Render.
   Deixe o acesso público **desligado** — a ficha assinada tem RG, CPF e
   endereço de menor de idade, e só sai pela rota autenticada.
2. **Dentro do R2**, procure **API > Manage R2 API Tokens > Create API Token**.
   Permissão **Object Read & Write** (não "Admin Read & Write", que permite
   criar e apagar buckets — poder que o app não precisa), restrita ao bucket
   `herois-do-tatame`. Guarde o *Access Key ID* e o *Secret Access Key*: o
   segredo só aparece uma vez.

   > A Cloudflare tem dois sistemas de token com nomes parecidos. Os **API
   > Tokens** do perfil da conta, com templates como "Read and write to
   > Cloudflare Stream and Images", geram um token bearer para a API REST da
   > Cloudflare e **não funcionam aqui** — o Laravel fala o protocolo S3, que
   > exige um par chave/segredo. A tela certa é a de dentro do R2, e ela
   > termina mostrando um campo chamado *Access Key ID*. Se você só vê um token
   > longo e nenhum Access Key ID, está na tela errada.
3. Anote o endpoint da conta:
   `https://SEU_ACCOUNT_ID.r2.cloudflarestorage.com` — **sem** o nome do bucket
   no final. O Account ID aparece na página inicial do R2, à direita, e também
   no meio da URL do painel.

## 3. Chave da aplicação

Gere uma chave só para produção. Nunca reaproveite a do `.env` local:

```bash
./vendor/bin/sail artisan key:generate --show
```

Copie a saída inteira, incluindo o prefixo `base64:`.

## 4. Serviço no Render

No painel do Render: **New > Blueprint**, aponte para este repositório. Ele lê o
[`render.yaml`](../render.yaml) e cria o serviço com o plano gratuito.

Preencha então as variáveis marcadas como `sync: false`:

| Variável | Valor |
|---|---|
| `APP_KEY` | a saída do passo 3, com o `base64:` |
| `APP_URL` | **deixe em branco** — veja a nota abaixo |
| `DB_HOST` | host do Neon novo |
| `DB_DATABASE` | normalmente `neondb` |
| `DB_USERNAME` / `DB_PASSWORD` | do Neon novo |
| `R2_ACCESS_KEY_ID` / `R2_SECRET_ACCESS_KEY` | do passo 2 |
| `R2_BUCKET` | `herois-do-tatame` |
| `R2_ENDPOINT` | `https://SEU_ACCOUNT_ID.r2.cloudflarestorage.com` |
| `PROFESSOR_CPF` | o CPF real do professor |
| `PROFESSOR_PASSWORD` | **uma senha forte, não a de desenvolvimento** |
| `PROFESSOR_EMAIL` | e-mail do professor |

`APP_URL` não precisa ser preenchido. Ele só seria conhecido depois que o
Render cria o serviço, então o `start.sh` o herda de `RENDER_EXTERNAL_URL`, que
o Render injeta automaticamente com a URL final. Só defina a variável à mão se
for usar domínio próprio — nesse caso o valor explícito prevalece.

Ao subir, o contêiner executa nesta ordem: gera a configuração do nginx na porta
que o Render escolheu, roda as migrations, cria ou atualiza o usuário professor,
e cacheia configuração, rotas e views.

> `db:seed --class=ProfessorSeeder` roda a cada implantação. Como ele usa
> `updateOrCreate`, **a senha do professor volta ao valor de
> `PROFESSOR_PASSWORD` toda vez que o app subir.** Para trocar a senha, mude a
> variável no Render, não no banco.
>
> O `StudentSeeder` (os três alunos de demonstração) se recusa a rodar quando
> `APP_ENV=production`, então produção nunca recebe dados fictícios.

## 5. Depois da primeira implantação

1. Acesse `https://SEU-APP.onrender.com/up` — deve responder com o painel de
   saúde do Laravel.
2. Entre em `/login` com o CPF e a senha configurados.
3. Faça uma matrícula de teste pelo formulário público.
4. Gere a ficha em PDF do aluno criado.
5. **Anexe uma ficha assinada e implante de novo.** Se o arquivo continuar lá
   depois da nova implantação, o R2 está funcionando. Se sumir, `FICHAS_DISK`
   não está valendo `r2`.
6. Apague o aluno de teste.

## 6. Hibernação

No plano gratuito o Render derruba o serviço após cerca de 15 minutos sem
acesso, e volta em torno de 50 segundos. O Neon suspende o banco após cerca de
5 minutos ocioso. Somados, o primeiro acesso depois de um período parado pode
levar quase um minuto.

Antes de uma apresentação, configure um ping a cada 10 minutos em
[cron-job.org](https://cron-job.org) (gratuito) apontando para
`https://SEU-APP.onrender.com/up`. Isso mantém os dois lados acordados.

Não é possível desligar a hibernação no plano gratuito — só mantê-la longe com
tráfego.

## Rodar a imagem de produção localmente

Útil para depurar o contêiner sem esperar uma implantação:

```bash
docker build -t herois-do-tatame .
```

```bash
docker run --rm -p 8080:8080 --env-file .env.docker herois-do-tatame
```

Crie o `.env.docker` com as mesmas variáveis da tabela do passo 4 (ele está no
`.gitignore`). O app responde em `http://localhost:8080`.

## Solução de problemas

**Página em branco ou erro 500 sem detalhes.** `APP_DEBUG` é `false` em
produção, de propósito. Veja os logs no painel do Render: eles saem em `stderr`
porque `LOG_CHANNEL=stderr`.

**CSS e JS não carregam.** O `public/build` é gerado durante o build da imagem.
Se estiver faltando, o estágio de assets falhou — procure por `npm` nos logs de
build. Um `package-lock.json` fora de sincronia com o `package.json` faz o
`npm ci` abortar; a correção é rodar `npm install` e commitar o lock.

**Livewire não responde ou o navegador reclama de conteúdo misto.** O Laravel
está gerando URLs `http://`. Confirme que `APP_URL` começa com `https://` e que
o `trustProxies` continua em [`bootstrap/app.php`](../bootstrap/app.php).

**Upload da ficha falha com erro de credencial.** Confira o `R2_ENDPOINT`: ele
é o endereço da conta, sem o nome do bucket. O bucket vai em `R2_BUCKET`.

**A ficha assinada some depois de implantar.** `FICHAS_DISK` não está como
`r2`. Sem isso o arquivo vai para o disco do contêiner, que é descartado.
