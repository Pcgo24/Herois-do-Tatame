// Usa o primeiro aluno da lista, seja ele qual for: o banco de dev tem os
// três do StudentSeeder mais os que a spec de matrícula cria.
const entrar = () => {
  cy.visit('/login');
  cy.get('[data-cy="input-username"]').type('professor');
  cy.get('[data-cy="input-password"]').type('heroisdotatame');
  cy.get('[data-cy="login-btn"]').click();
  cy.url().should('include', '/admin/dashboard');
  cy.fecharAviso();
};

describe('Filtro por status do termo', () => {
  beforeEach(entrar);

  it('mostra só os alunos com o status escolhido', () => {
    cy.get('[data-cy="filter-termo"]').select('assinado');
    // should() com callback refaz a checagem até o Livewire terminar de re-renderizar.
    cy.get('[data-cy="badge-termo"]').should(($badges) => {
      expect($badges.length).to.be.greaterThan(0);
      $badges.each((_, badge) => expect(badge.textContent.trim()).to.equal('Assinado'));
    });

    cy.get('[data-cy="filter-termo"]').select('');
    cy.get('[data-cy="badge-termo"]').contains('Pendente').should('exist');
  });
});

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
