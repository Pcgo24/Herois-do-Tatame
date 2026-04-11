describe("Landing Page - Heróis do Tatame", () => {
    // Antes de cada teste, visita a página inicial
    beforeEach(() => {
        // Substitua pela URL local do seu projeto Laravel (ex: http://localhost:8000)
        cy.visit("http://localhost:8000");
    });

    context("Visualização Desktop", () => {
        beforeEach(() => {
            cy.viewport(1280, 720); // Resolução de PC
        });

        it("Deve carregar o Header e verificar se está fixo (sticky)", () => {
            // Verifica se o logo/título existe
            cy.contains("Heróis do Tatame").should("be.visible");

            // Verifica se o menu desktop está visível
            cy.get("nav").contains("Início").should("be.visible");

            // Verifica se o botão de inscrição existe
            cy.contains("Inscrever-se").should("be.visible");

            // Rola a página para baixo e verifica se o header continua visível (sticky)
            cy.scrollTo(0, 500);
            cy.get("header").should("be.visible");
        });

        it("Deve testar a navegação suave para a seção Sobre", () => {
            // Clica no link "Sobre o Projeto"
            cy.get("nav").contains("Sobre o Projeto").click();

            // Verifica se a URL mudou para a âncora #sobre
            cy.url().should("include", "#sobre");

            // Verifica se o título da seção ficou visível na tela
            cy.get("#sobre").contains("Sobre o Projeto").should("be.visible");
        });

        it("Deve exibir o Quadro de Horários corretamente", () => {
            // Busca o título da seção
            cy.contains("Quadro de Horários")
                .scrollIntoView()
                .should("be.visible");

            // Verifica se a tabela existe
            cy.get("table").should("exist");

            // Verifica dados específicos da tabela
            cy.contains("td", "Segunda-feira").should("be.visible");
            cy.contains("td", "18:00 - 19:30").should("be.visible");
            cy.contains("span", "Jiu Jitsu / Boxe").should("be.visible");
        });
    });

    context("Visualização Mobile (Alpine.js)", () => {
        beforeEach(() => {
            cy.viewport("iphone-xr"); // Simula a tela de um iPhone
        });

        it("Deve testar a abertura e fechamento do Menu Mobile", () => {
            // No mobile, o menu desktop deve estar escondido
            cy.get("nav").contains("Início").should("not.be.visible");

            // Clica no botão Hamburguer (buscando pelo elemento <button> no header)
            cy.get("header button").click();

            // Agora o link "Sobre o Projeto" dentro do menu mobile deve estar visível
            cy.get('header div[x-show="menuAberto"]')
                .contains("Sobre o Projeto")
                .should("be.visible");

            // Testa clicar em um link para ver se o Alpine.js fecha o menu automaticamente (@click="menuAberto = false")
            cy.get('header div[x-show="menuAberto"]')
                .contains("Modalidades")
                .click();

            // O menu deve ficar invisível novamente
            cy.get('header div[x-show="menuAberto"]').should("not.be.visible");
        });

        it("A tabela de horários deve permitir scroll horizontal no mobile", () => {
            cy.contains("Quadro de Horários").scrollIntoView();

            // Pega a div que colocamos com 'overflow-x-auto'
            cy.get("table")
                .parent()
                .then(($div) => {
                    // Verifica se a largura do conteúdo (scrollWidth) é maior que a largura da tela (clientWidth)
                    expect($div[0].scrollWidth).to.be.greaterThan(
                        $div[0].clientWidth,
                    );
                });
        });
    });
});
