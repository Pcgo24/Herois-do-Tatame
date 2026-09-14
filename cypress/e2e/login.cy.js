describe('Login do professor', () => {
  beforeEach(() => {
    cy.visit('/login');
  });

  it('exibe o formulário de login', () => {
    cy.get('[data-cy="login-form"]').should('be.visible');
    cy.get('[data-cy="input-username"]').should('be.visible');
    cy.get('[data-cy="input-password"]').should('be.visible');
  });

  it('recusa credenciais inválidas', () => {
    cy.get('[data-cy="input-username"]').type('professor');
    cy.get('[data-cy="input-password"]').type('senha-errada');
    cy.get('[data-cy="login-btn"]').click();
    cy.get('[data-cy="error-username"]').should('contain', 'Usuário ou senha inválidos');
  });

  it('aceita o usuário com maiúsculas e espaços ao redor', () => {
    cy.get('[data-cy="input-username"]').type('  Professor ');
    cy.get('[data-cy="input-password"]').type('heroisdotatame');
    cy.get('[data-cy="login-btn"]').click();
    cy.url().should('include', '/admin/dashboard');
  });

  // O botão de tema depende de um escopo Alpine próprio: fora da landing
  // page não existe x-data ancestral que o processe.
  it('alterna o tema na tela de login', () => {
    cy.get('html').should('not.have.class', 'dark');
    cy.get('[data-cy="theme-toggle"]').click();
    cy.get('html').should('have.class', 'dark');
  });

  it('alterna o tema no dashboard', () => {
    cy.get('[data-cy="input-username"]').type('professor');
    cy.get('[data-cy="input-password"]').type('heroisdotatame');
    cy.get('[data-cy="login-btn"]').click();
    cy.url().should('include', '/admin/dashboard');
    cy.get('html').should('not.have.class', 'dark');
    cy.get('[data-cy="theme-toggle"]').click();
    cy.get('html').should('have.class', 'dark');
  });

  // wire:model sem .live só sincroniza no submit. Intercepta qualquer POST
  // porque o endpoint do Livewire 4 tem hash no caminho.
  it('não chama o servidor a cada tecla do usuário', () => {
    const chamadas = [];
    cy.intercept({ method: 'POST', url: '**' }, (req) => { chamadas.push(req.url); });
    cy.get('[data-cy="input-username"]').type('professor', { delay: 60 });
    cy.wait(1500);
    cy.then(() => expect(chamadas, 'requisições durante a digitação').to.be.empty);
  });

  it('bloqueia visitante no dashboard', () => {
    cy.visit('/admin/dashboard');
    cy.url().should('include', '/login');
  });
});
