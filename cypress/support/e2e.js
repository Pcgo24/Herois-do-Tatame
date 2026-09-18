// O aviso de vencimentos cobre o dashboard na primeira visita da sessão e
// bloqueia qualquer clique atrás dele. Os specs que só precisam do dashboard
// fecham o aviso logo depois do login.
Cypress.Commands.add('fecharAviso', () => {
  cy.get('body').then(($body) => {
    if ($body.find('[data-cy="aviso-fechar"]').length) {
      cy.get('[data-cy="aviso-fechar"]').click();
      cy.get('[data-cy="aviso-vencimentos"]').should('not.exist');
    }
  });
});
