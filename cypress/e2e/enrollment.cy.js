const randomCpf = () =>
  Math.floor(10000000000 + Math.random() * 89999999999).toString();

const responsible = () => ({
  name: 'Maria da Silva',
  phone: '11999999999',
  cpf: randomCpf(),
  email: `maria${Date.now()}@email.com`,
  birthDate: '1990-01-15',
  address: 'Rua das Flores, 123, São Paulo',
});

const student = () => ({
  name: 'João da Silva',
  cpf: randomCpf(),
  rg: '123456789',
  birthDate: '2015-06-10',
  modalidade: 'Jiu Jitsu',
});

describe('Navegação para matrícula', () => {
  it('exibe o botão Matricule-se no header desktop', () => {
    cy.viewport(1280, 720);
    cy.visit('/');
    cy.get('[data-cy="enrollment-btn"]').should('be.visible');
  });

  it('navega para o formulário pelo botão do header', () => {
    cy.viewport(1280, 720);
    cy.visit('/');
    cy.get('[data-cy="enrollment-btn"]').click();
    cy.url().should('include', '/matricula');
  });

  it('navega para o formulário pelo botão da hero section', () => {
    cy.viewport(1280, 720);
    cy.visit('/');
    cy.get('[data-cy="hero-enrollment-btn"]').click();
    cy.url().should('include', '/matricula');
  });

  it('exibe o botão Matricule-se no menu mobile', () => {
    cy.viewport('iphone-xr');
    cy.visit('/');
    cy.get('button[aria-label]').first().click({ force: true });
    cy.get('[data-cy="enrollment-btn-mobile"]').should('be.visible');
  });
});

describe('Formulário de matrícula', () => {
  beforeEach(() => {
    cy.visit('/matricula');
  });

  it('exibe as duas seções do formulário', () => {
    cy.get('[data-cy="enrollment-form"]').should('be.visible');
    cy.contains('Dados do Responsável').should('be.visible');
    cy.contains('Dados do Aluno').should('be.visible');
  });

  it('exibe erros ao submeter formulário vazio', () => {
    cy.get('[data-cy="submit-btn"]').click();
    cy.get('[data-cy="error-responsible_name"]').should('be.visible');
    cy.get('[data-cy="error-responsible_cpf"]').should('be.visible');
    cy.get('[data-cy="error-student_name"]').should('be.visible');
  });

  it('exibe erro para CPF com formatação (pontos e traço)', () => {
    cy.get('[data-cy="input-responsible_cpf"]').type('123.456.789-01');
    cy.get('[data-cy="submit-btn"]').click();
    cy.get('[data-cy="error-responsible_cpf"]').should('be.visible');
  });

  it('exibe erro para telefone com menos de 11 dígitos', () => {
    cy.get('[data-cy="input-responsible_phone_number"]').type('1199999');
    cy.get('[data-cy="submit-btn"]').click();
    cy.get('[data-cy="error-responsible_phone_number"]').should('be.visible');
  });

  it('exibe erro para aluno com menos de 8 anos', () => {
    const underage = new Date();
    underage.setFullYear(underage.getFullYear() - 7);
    cy.get('[data-cy="input-student_birth_date"]').type(
      underage.toISOString().split('T')[0]
    );
    cy.get('[data-cy="submit-btn"]').click();
    cy.get('[data-cy="error-student_birth_date"]').should('be.visible');
  });

  it('exibe erro para aluno com mais de 17 anos', () => {
    const overage = new Date();
    overage.setFullYear(overage.getFullYear() - 18);
    cy.get('[data-cy="input-student_birth_date"]').type(
      overage.toISOString().split('T')[0]
    );
    cy.get('[data-cy="submit-btn"]').click();
    cy.get('[data-cy="error-student_birth_date"]').should('be.visible');
  });

  it('submete com dados válidos e exibe confirmação', () => {
    const r = responsible();
    const s = student();

    cy.get('[data-cy="input-responsible_name"]').type(r.name);
    cy.get('[data-cy="input-responsible_phone_number"]').type(r.phone);
    cy.get('[data-cy="input-responsible_cpf"]').type(r.cpf);
    cy.get('[data-cy="input-responsible_email"]').type(r.email);
    cy.get('[data-cy="input-responsible_birth_date"]').type(r.birthDate);
    cy.get('[data-cy="input-responsible_address"]').type(r.address);

    cy.get('[data-cy="input-student_name"]').type(s.name);
    cy.get('[data-cy="input-student_cpf"]').type(s.cpf);
    cy.get('[data-cy="input-student_rg"]').type(s.rg);
    cy.get('[data-cy="input-student_birth_date"]').type(s.birthDate);
    cy.get('[data-cy="select-student_modalidade"]').select(s.modalidade);

    cy.get('[data-cy="submit-btn"]').click();
    cy.get('[data-cy="success-message"]', { timeout: 10000 }).should('be.visible');
  });
});
