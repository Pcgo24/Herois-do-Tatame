// Usa o primeiro aluno da lista, seja ele qual for: o banco de dev tem os
// três do StudentSeeder mais os que a spec de matrícula cria.
const entrar = () => {
  cy.visit('/login');
  cy.get('[data-cy="input-username"]').type('professor');
  cy.get('[data-cy="input-password"]').type('heroisdotatame');
  cy.get('[data-cy="login-btn"]').click();
  cy.url().should('include', '/admin/dashboard');
};

describe('Cancelamento de matrícula', () => {
  beforeEach(entrar);

  it('cancela e reativa uma matrícula', () => {
    cy.get('[data-cy="student-row"]').first().find('td').eq(2).invoke('text').then((texto) => {
      const nome = texto.trim();

      cy.contains('[data-cy="student-row"]', nome).click();
      cy.on('window:confirm', () => true);
      cy.get('[data-cy="cancel-enrollment"]').click();
      cy.contains('[data-cy="student-row"]', nome).should('not.exist');

      cy.get('[data-cy="toggle-cancelled"]').check();
      cy.contains('[data-cy="student-row"]', nome).find('[data-cy="badge-cancelled"]').should('be.visible');

      cy.contains('[data-cy="student-row"]', nome).click();
      cy.get('[data-cy="restore-enrollment"]').click();
      cy.contains('[data-cy="student-row"]', nome).find('[data-cy="badge-cancelled"]').should('not.exist');

      cy.get('[data-cy="toggle-cancelled"]').uncheck();
      cy.contains('[data-cy="student-row"]', nome).should('be.visible');
    });
  });
});
