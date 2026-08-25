<div align="center">

  <h1>Heróis no Tatame</h1>
  <p><strong>Plataforma web para divulgação, triagem e gestão de alunos</strong> do projeto social <em>"Heróis no Tatame"</em>, promovido pelo <strong>Centro de Treinamento Marcial (CTM)</strong>.</p>

  <hr/>

  <p>
    A CTM oferece <strong>aulas gratuitas</strong> para a comunidade carente e para <strong>PCDs</strong> (Pessoas com Deficiência), promovendo disciplina, inclusão e desenvolvimento por meio das artes marciais.
  </p>

</div>

<h2>O que mudou (Sprint 3)</h2>
<p>
  A Sprint 3 (04/05/2026 a 25/05/2026) foi marcada pela criação da <strong>Área Administrativa (Dashboard de Triagem)</strong> para instrutores e gestores, ampliando o sistema para além da triagem, com foco em <strong>gestão centralizada</strong>, visualização dos inscritos e integração com o banco de dados em tempo real.
</p>

<h2>Entregas da Sprint 3</h2>
<ul>
  <li>
    <strong>Dashboard Administrativo</strong>: painel visual para listagem, filtragem e visualização centralizada de todos os alunos e responsáveis cadastrados nas etapas anteriores.
  </li>
  <li>
    <strong>Integração completa com Banco de Dados</strong>: painel vinculado à base <strong>PostgreSQL (Neon)</strong>, permitindo leitura dos registros de triagem em tempo real.
  </li>
  <li>
    <strong>Infraestrutura técnica aprimorada</strong>: backend preparado para robustez, escalabilidade e futuras integrações.
  </li>
  <li>
    <strong>Organização do backlog</strong>: tarefas e débitos técnicos do módulo de autenticação/control de acesso mantidos visíveis no Kanban para futura priorização.
  </li>
</ul>

<h2>Pendências e decisões</h2>
<ul>
  <li><strong>Autenticação e controle de acesso</strong>: entrega adiada, aguardando definição do fluxo ideal junto aos responsáveis do projeto e prefeitura.</li>
  <li><strong>Testes manuais</strong>: realizados para responsividade do painel e confirmação da integração com o banco de dados.</li>
</ul>

<h2>Próximos passos</h2>
<ul>
  <li>🔜 Implementação do sistema de autenticação e controle de acesso.</li>
  <li>🔜 Ajustes visuais/técnicos do Dashboard conforme feedback dos instrutores.</li>
  <li>🔜 Evolução contínua com foco em estabilidade, segurança e preparação para o deploy.</li>
</ul>

<div align="center">
  <p><strong>Repositório:</strong> <a href="https://github.com/Pcgo24/Herois-do-Tatame">github.com/Pcgo24/Herois-do-Tatame</a></p>
  <p><strong>Heróis no Tatame</strong> — tecnologia a favor da inclusão e do impacto social.</p>
</div>

<h2>Time de desenvolvimento</h2>
<ul>
  <li><strong>Product Owner:</strong> Vitor Bobato</li>
  <li><strong>Scrum Master:</strong> Paulo Cesar Cardoso Domingues</li>
  <li><strong>Desenvolvimento:</strong> Vitor Bobato; Paulo Cesar Cardoso Domingues</li>
</ul>

## Área do Professor

O acesso ao dashboard exige login em `/login`, com **CPF e senha**.

Crie o usuário professor com:

    ./vendor/bin/sail artisan db:seed --class=ProfessorSeeder

As credenciais vêm de `PROFESSOR_CPF`, `PROFESSOR_PASSWORD`, `PROFESSOR_NAME` e
`PROFESSOR_EMAIL` no `.env` (veja `.env.example`). Os valores padrão — CPF
`12345678909`, senha `heroisdotatame` — servem **apenas para desenvolvimento**.
Troque a senha no `.env` e rode o seeder de novo antes de qualquer uso real; ele
é idempotente e atualiza o usuário existente.

## Ficha de cadastro de atleta

No dashboard, o botão **Gerar ficha** de cada aluno baixa em PDF a Ficha de
Cadastro de Atleta no formato exigido pela Secretaria Municipal de Esportes e
Recreação de Prudentópolis, já preenchida com os dados do cadastro. Só restam as
duas assinaturas.

O PDF é gerado sob demanda e nunca gravado em disco: corrigir um dado do aluno e
reimprimir sempre produz a versão atual. O título acompanha a modalidade do
aluno, então a mesma ficha serve para Boxe, Jiu Jitsu, Muay Thai e Taekwondo.

Para usar a arte oficial no cabeçalho, salve a logo em
`public/img/prudentopolis-smer.png`. Sem o arquivo, o cabeçalho é montado em HTML.

Depois de assinada, a ficha volta ao sistema pelo modal de detalhes do aluno
(PDF ou foto, até 5 MB). O arquivo fica em disco privado e o status do termo
passa a "assinado" automaticamente.

## Testes

    ./vendor/bin/sail artisan test          # PHPUnit, em SQLite local
    npx cypress run                         # E2E, contra http://localhost
