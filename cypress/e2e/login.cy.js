describe('Login do professor', () => {
  beforeEach(() => {
    cy.visit('/login');
  });

  it('exibe o formulário de login', () => {
    cy.get('[data-cy="login-form"]').should('be.visible');
    cy.get('[data-cy="input-cpf"]').should('be.visible');
    cy.get('[data-cy="input-password"]').should('be.visible');
  });

  it('formata o CPF enquanto é digitado', () => {
    cy.get('[data-cy="input-cpf"]').type('12345678909');
    cy.get('[data-cy="input-cpf"]').should('have.value', '123.456.789-09');
  });

  it('recusa credenciais inválidas', () => {
    cy.get('[data-cy="input-cpf"]').type('12345678909');
    cy.get('[data-cy="input-password"]').type('senha-errada');
    cy.get('[data-cy="login-btn"]').click();
    cy.get('[data-cy="error-cpf"]').should('contain', 'CPF ou senha inválidos');
  });

  it('bloqueia visitante no dashboard', () => {
    cy.visit('/admin/dashboard');
    cy.url().should('include', '/login');
  });
});
