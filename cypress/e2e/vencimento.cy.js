// Depende do banco de dev ter matrículas vencidas ou a vencer (StudentSeeder
// e DemoSeeder garantem isso).
const entrar = () => {
  cy.visit('/login');
  cy.get('[data-cy="input-username"]').type('professor');
  cy.get('[data-cy="input-password"]').type('heroisdotatame');
  cy.get('[data-cy="login-btn"]').click();
  cy.url().should('include', '/admin/dashboard');
};

describe('Vencimento da matrícula', () => {
  beforeEach(entrar);

  it('avisa uma vez por sessão e leva ao aluno para renovar', () => {
    cy.get('[data-cy="aviso-vencimentos"]').should('be.visible');
    cy.get('[data-cy="aviso-aluno"]').should('have.length.greaterThan', 0);

    cy.get('[data-cy="aviso-aluno"]').first().invoke('text').then((texto) => {
      const nome = texto.split('—')[0].trim();

      cy.get('[data-cy="aviso-aluno"]').first().click();
      cy.get('[data-cy="aviso-vencimentos"]').should('not.exist');
      cy.get('[data-cy="modal-vencimento"]').should('be.visible');

      cy.on('window:confirm', () => true);
      cy.get('[data-cy="renew-enrollment"]').click();
      cy.get('[data-cy="modal-vencimento"]').should('not.be.visible');

      cy.contains('[data-cy="student-row"]', nome)
        .find('[data-cy="badge-vencimento"]')
        .should('have.attr', 'data-situacao', 'ok')
        .and('contain', 'faltam 12 meses');
    });

    cy.visit('/admin/dashboard');
    cy.get('[data-cy="student-row"]').should('exist');
    cy.get('[data-cy="aviso-vencimentos"]').should('not.exist');
  });

  it('filtra só as matrículas vencidas ou a vencer', () => {
    cy.get('[data-cy="aviso-fechar"]').click();

    cy.get('[data-cy="toggle-attention"]').check();
    cy.get('[data-cy="badge-vencimento"]').should('have.length.greaterThan', 0);
    cy.get('[data-cy="badge-vencimento"][data-situacao="ok"]').should('not.exist');

    cy.get('[data-cy="toggle-attention"]').uncheck();
    cy.get('[data-cy="badge-vencimento"][data-situacao="ok"]').should('exist');
  });
});
