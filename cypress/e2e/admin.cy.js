// Contas do .env de desenvolvimento: admin/heroisdotatame e professor/heroisdotatame.
const entrarComo = (usuario) => {
  cy.visit('/login');
  cy.get('[data-cy="input-username"]').type(usuario);
  cy.get('[data-cy="input-password"]').type('heroisdotatame');
  cy.get('[data-cy="login-btn"]').click();
};

describe('Administrador', () => {
  it('entra direto em Usuários, sem link para Alunos', () => {
    entrarComo('admin');
    cy.url().should('include', '/admin/usuarios');
    cy.get('[data-cy="link-dashboard"]').should('not.exist');
    cy.get('[data-cy="link-password"]').should('be.visible');
  });

  it('não abre o dashboard nem aparece na lista de usuários', () => {
    entrarComo('admin');
    cy.url().should('include', '/admin/usuarios');
    cy.contains('[data-cy="user-row"]', 'admin').should('not.exist');
    cy.contains('[data-cy="user-row"]', 'professor').should('be.visible');

    cy.request({ url: '/admin/dashboard', failOnStatusCode: false }).its('status').should('eq', 403);
  });

  it('o professor não vê o admin', () => {
    entrarComo('professor');
    cy.url().should('include', '/admin/dashboard');
    cy.fecharAviso();
    cy.get('[data-cy="link-users"]').click();
    cy.contains('[data-cy="user-row"]', 'admin').should('not.exist');
    cy.get('[data-cy="toggle-removed"]').check();
    cy.contains('[data-cy="user-row"]', 'admin').should('not.exist');
  });
});
