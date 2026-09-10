const randomCpf = () =>
  Math.floor(10000000000 + Math.random() * 89999999999).toString();

const responsible = () => ({
  name: 'Maria da Silva',
  phone: '11999999999',
  homePhone: '4232241234',
  cpf: randomCpf(),
  rg: '111111111',
  email: `maria${Date.now()}@email.com`,
  birthDate: '1990-01-15',
  address: 'Rua das Flores, 123',
  neighborhood: 'Centro',
});

// toISOString() converte para UTC e, no fuso do Brasil, adianta um dia depois
// das 21h — o que tornava os testes de faixa etária dependentes da hora do dia.
const isoLocal = (d) =>
  `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

const anosAtras = (anos) => {
  const d = new Date();
  d.setFullYear(d.getFullYear() - anos);
  return isoLocal(d);
};

const student = () => ({
  name: 'João da Silva',
  cpf: randomCpf(),
  rg: '222222222',
  birthDate: '2015-06-10',
  school: 'Colégio Estadual de Prudentópolis',
  grade: '5º ano',
  fatherName: 'José da Silva',
  motherName: 'Maria da Silva',
  phone: '42999998888',
  email: 'joao@email.com',
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
    cy.url().should('include', '/enrollment');
  });

  it('navega para o formulário pelo botão da hero section', () => {
    cy.viewport(1280, 720);
    cy.visit('/');
    cy.get('[data-cy="hero-enrollment-btn"]').click();
    cy.url().should('include', '/enrollment');
  });

  it('exibe o botão Matricule-se no menu mobile', () => {
    cy.viewport('iphone-xr');
    cy.visit('/');
    cy.get('[data-cy="menu-toggle"]').click({ force: true });
    cy.get('[data-cy="enrollment-btn-mobile"]').should('be.visible');
  });
});

describe('Formulário de matrícula', () => {
  beforeEach(() => {
    cy.visit('/enrollment');
  });

  it('exibe as duas seções do formulário', () => {
    cy.get('[data-cy="enrollment-form"]').should('be.visible');
    cy.contains('Dados do Responsável').should('be.visible');
    cy.contains('Dados do Aluno').should('be.visible');
  });

  it('exibe erros ao submeter formulário vazio', () => {
    cy.get('[data-cy="lgpd-consent-checkbox"]').check();
    cy.get('[data-cy="submit-btn"]').click();
    cy.get('[data-cy="error-responsible_name"]').should('be.visible');
    cy.get('[data-cy="error-responsible_cpf"]').should('be.visible');
    cy.get('[data-cy="error-student_name"]').should('be.visible');
  });

  it('exibe erro para CPF com formatação (pontos e traço)', () => {
    cy.get('[data-cy="input-responsible_cpf"]').type('123456789');
    cy.get('[data-cy="lgpd-consent-checkbox"]').check();
    cy.get('[data-cy="submit-btn"]').click();
    cy.get('[data-cy="error-responsible_cpf"]').should('be.visible');
  });

  it('exibe erro para telefone com menos de 11 dígitos', () => {
    cy.get('[data-cy="input-responsible_phone_number"]').type('1199999');
    cy.get('[data-cy="lgpd-consent-checkbox"]').check();
    cy.get('[data-cy="submit-btn"]').click();
    cy.get('[data-cy="error-responsible_phone_number"]').should('be.visible');
  });

  it('exibe erro para aluno com menos de 8 anos', () => {
    cy.get('[data-cy="input-student_birth_date"]').type(anosAtras(7));
    cy.get('[data-cy="lgpd-consent-checkbox"]').check();
    cy.get('[data-cy="submit-btn"]').click();
    cy.get('[data-cy="error-student_birth_date"]').should('be.visible');
  });

  it('exibe erro para aluno com mais de 17 anos', () => {
    cy.get('[data-cy="input-student_birth_date"]').type(anosAtras(19));
    cy.get('[data-cy="lgpd-consent-checkbox"]').check();
    cy.get('[data-cy="submit-btn"]').click();
    cy.get('[data-cy="error-student_birth_date"]').should('be.visible');
  });

  const preencheFormulario = () => {
    const r = responsible();
    const s = student();

    cy.get('[data-cy="input-responsible_name"]').type(r.name);
    cy.get('[data-cy="input-responsible_phone_number"]').type(r.phone);
    cy.get('[data-cy="input-responsible_cpf"]').type(r.cpf);
    cy.get('[data-cy="input-responsible_email"]').type(r.email);
    cy.get('[data-cy="input-responsible_birth_date"]').type(r.birthDate);
    cy.get('[data-cy="input-responsible_address"]').type(r.address);
    cy.get('[data-cy="input-responsible_rg"]').type(r.rg);
    cy.get('[data-cy="input-responsible_home_phone"]').type(r.homePhone);
    cy.get('[data-cy="input-responsible_neighborhood"]').type(r.neighborhood);

    cy.get('[data-cy="input-student_name"]').type(s.name);
    cy.get('[data-cy="input-student_cpf"]').type(s.cpf);
    cy.get('[data-cy="input-student_rg"]').type(s.rg);
    cy.get('[data-cy="input-student_birth_date"]').type(s.birthDate);
    cy.get('[data-cy="input-student_school"]').type(s.school);
    cy.get('[data-cy="input-student_grade"]').type(s.grade);
    cy.get('[data-cy="input-student_father_name"]').type(s.fatherName);
    cy.get('[data-cy="input-student_mother_name"]').type(s.motherName);
    cy.get('[data-cy="input-student_phone"]').type(s.phone);
    cy.get('[data-cy="input-student_email"]').type(s.email);
    cy.get('[data-cy="select-student_modalidade"]').select(s.modalidade);
    cy.get('[data-cy="lgpd-consent-checkbox"]').check();
  };

  it('submete com dados válidos e exibe confirmação', () => {
    preencheFormulario();
    cy.get('[data-cy="submit-btn"]').click();
    cy.get('[data-cy="success-message"]', { timeout: 10000 }).should('be.visible');
  });

  it('o botão de envio fica desabilitado sem aceite LGPD', () => {
    cy.get('[data-cy="submit-btn"]').should('be.disabled');
    cy.get('[data-cy="lgpd-consent-checkbox"]').check();
    cy.get('[data-cy="submit-btn"]').should('not.be.disabled');
  });

  it('mantém a data de nascimento digitada dígito a dígito', () => {
    cy.get('[data-cy="input-student_birth_date"]').type('2015-06-10');
    cy.get('[data-cy="input-student_birth_date"]').should('have.value', '2015-06-10');
  });

  it('mantém a data de nascimento do responsável digitada dígito a dígito', () => {
    cy.get('[data-cy="input-responsible_birth_date"]').type('1990-01-15');
    cy.get('[data-cy="input-responsible_birth_date"]').should('have.value', '1990-01-15');
  });

  it('desabilita o nome do pai ao marcar que não possui', () => {
    cy.get('[data-cy="checkbox-student_no_father"]').check();
    cy.get('[data-cy="input-student_father_name"]').should('be.disabled');
    cy.get('[data-cy="checkbox-student_no_father"]').uncheck();
    cy.get('[data-cy="input-student_father_name"]').should('not.be.disabled');
  });

  // O formulário precisa estar válido no resto: o validate() lança na primeira
  // falha, e a checagem das duas filiações só roda depois dele.
  it('recusa o cadastro quando as duas filiações são marcadas como inexistentes', () => {
    preencheFormulario();
    cy.get('[data-cy="checkbox-student_no_father"]').check();
    cy.get('[data-cy="checkbox-student_no_mother"]').check();
    cy.get('[data-cy="submit-btn"]').click();
    cy.get('[data-cy="error-student_no_father"]', { timeout: 10000 })
      .should('contain', 'pelo menos uma filiação');
  });
});
