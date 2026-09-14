// Roda contra o servidor --env=testing (SQLite) com o ProfessorSeeder aplicado:
// usuário `professor`, senha `heroisdotatame`. Cada teste cria e remove os
// próprios usuários, então a suíte pode rodar mais de uma vez no mesmo banco.
const entrar = (usuario = 'professor', senha = 'heroisdotatame') => {
  cy.visit('/login');
  cy.get('[data-cy="input-username"]').type(usuario);
  cy.get('[data-cy="input-password"]').type(senha);
  cy.get('[data-cy="login-btn"]').click();
  cy.url().should('include', '/admin/dashboard');
};

// Depois do logout, um cy.visit com cookies antigos faz o Cypress reaplicar
// um cookie de sessão obsoleto na navegação seguinte ao login, e o login
// "não cola". Limpar o jar reproduz o que um teste novo já faria.
const sair = () => {
  cy.contains('button', 'Sair').click();
  cy.url().should('not.include', '/admin');
  cy.clearCookies();
};

const criaUsuario = (nome, usuario, senha) => {
  cy.get('[data-cy="user-create-btn"]').click();
  cy.get('[data-cy="input-user-name"]').type(nome);
  cy.get('[data-cy="input-user-username"]').type(usuario);
  cy.get('[data-cy="input-user-password"]').type(senha);
  cy.get('[data-cy="input-user-password-confirmation"]').type(senha);
  cy.get('[data-cy="user-save-btn"]').click();
  cy.get('[data-cy="user-form"]').should('not.be.visible');
};

const linhaDe = (usuario) => cy.contains('[data-cy="user-row"]', usuario);

describe('Usuários', () => {
  beforeEach(() => {
    entrar();
    cy.get('[data-cy="link-users"]').click();
    cy.url().should('include', '/admin/usuarios');
  });

  it('lista o usuário logado e bloqueia a própria remoção', () => {
    linhaDe('professor').should('contain', '(você)');
    linhaDe('professor').find('[data-cy="user-remove-btn"]').click();
    cy.get('[data-cy="error-remove"]').should('be.visible');
    linhaDe('professor').should('exist');
  });

  it('cria, edita, remove e restaura um usuário', () => {
    const usuario = `cy_${Date.now().toString(36)}`;

    criaUsuario('Usuário Cypress', usuario, 'senha-cypress');
    linhaDe(usuario).should('contain', 'Usuário Cypress');

    linhaDe(usuario).find('[data-cy="user-edit-btn"]').click();
    cy.get('[data-cy="input-user-name"]').clear().type('Usuário Editado');
    cy.get('[data-cy="user-save-btn"]').click();
    linhaDe(usuario).should('contain', 'Usuário Editado');

    linhaDe(usuario).find('[data-cy="user-remove-btn"]').click();
    cy.contains('[data-cy="user-row"]', usuario).should('not.exist');

    cy.get('[data-cy="toggle-removed"]').check();
    linhaDe(usuario).should('contain', 'Removido');
    linhaDe(usuario).find('[data-cy="user-restore-btn"]').click();
    linhaDe(usuario).should('not.contain', 'Removido');

    linhaDe(usuario).find('[data-cy="user-remove-btn"]').click();
  });

  it('um usuário novo consegue entrar e um removido não', () => {
    const usuario = `cy_${Date.now().toString(36)}`;

    criaUsuario('Usuário Cypress', usuario, 'senha-cypress');
    sair();

    entrar(usuario, 'senha-cypress');
    sair();

    entrar();
    cy.get('[data-cy="link-users"]').click();
    linhaDe(usuario).find('[data-cy="user-remove-btn"]').click();
    sair();

    cy.visit('/login');
    cy.get('[data-cy="input-username"]').type(usuario);
    cy.get('[data-cy="input-password"]').type('senha-cypress');
    cy.get('[data-cy="login-btn"]').click();
    cy.get('[data-cy="error-username"]').should('contain', 'Usuário ou senha inválidos');
  });

  it('rejeita usuário duplicado e fora do formato', () => {
    cy.get('[data-cy="user-create-btn"]').click();
    cy.get('[data-cy="input-user-name"]').type('Duplicado');
    cy.get('[data-cy="input-user-username"]').type('professor');
    cy.get('[data-cy="input-user-password"]').type('senha-qualquer');
    cy.get('[data-cy="input-user-password-confirmation"]').type('senha-qualquer');
    cy.get('[data-cy="user-save-btn"]').click();
    cy.get('[data-cy="error-user-username"]').should('contain', 'já está em uso');

    cy.get('[data-cy="input-user-username"]').clear().type('Nome Com Espaço!');
    cy.get('[data-cy="user-save-btn"]').click();
    cy.get('[data-cy="error-user-username"]').should('contain', 'letras minúsculas');
  });
});

describe('Alterar senha', () => {
  it('troca a senha e volta para a original', () => {
    entrar();
    cy.get('[data-cy="link-password"]').click();
    cy.url().should('include', '/admin/senha');

    cy.get('[data-cy="input-current-password"]').type('errada');
    cy.get('[data-cy="input-new-password"]').type('senha-temporaria');
    cy.get('[data-cy="input-password-confirmation"]').type('senha-temporaria');
    cy.get('[data-cy="password-save-btn"]').click();
    cy.get('[data-cy="error-current-password"]').should('contain', 'não confere');

    cy.get('[data-cy="input-current-password"]').clear().type('heroisdotatame');
    cy.get('[data-cy="input-new-password"]').clear().type('senha-temporaria');
    cy.get('[data-cy="input-password-confirmation"]').clear().type('senha-temporaria');
    cy.get('[data-cy="password-save-btn"]').click();
    cy.get('[data-cy="password-saved"]').should('be.visible');

    sair();
    entrar('professor', 'senha-temporaria');

    // Restaura a senha original para os demais testes.
    cy.get('[data-cy="link-password"]').click();
    cy.get('[data-cy="input-current-password"]').type('senha-temporaria');
    cy.get('[data-cy="input-new-password"]').type('heroisdotatame');
    cy.get('[data-cy="input-password-confirmation"]').type('heroisdotatame');
    cy.get('[data-cy="password-save-btn"]').click();
    cy.get('[data-cy="password-saved"]').should('be.visible');
  });
});
