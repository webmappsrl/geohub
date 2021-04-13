describe('After migrations', () => {
    describe('it should be able to login', () => {
        beforeEach(() => {
            cy.visit('/');
        });

        afterEach(() => {
            cy.get('.v-popover.dropdown-right button.rounded').click();
            cy.contains('Logout').click();
        });

        it('as Webmapp admin', () => {
            cy.get('input[name=email]').type('team@webmapp.it');
            cy.get('input[name=password]').type('webmapp');
            cy.get('button').contains('Login').click();
            cy.url().should('contain', 'dashboards');
        });

        it('as Alessio Piccioli', () => {
            cy.get('input[name=email]').type('alessiopiccioli@webmapp.it');
            cy.get('input[name=password]').type('webmapp');
            cy.get('button').contains('Login').click();
            cy.url().should('contain', 'dashboards');
        });

        it('as Andrea Del Sarto', () => {
            cy.get('input[name=email]').type('andreadel84@gmail.com');
            cy.get('input[name=password]').type('webmapp');
            cy.get('button').contains('Login').click();
            cy.url().should('contain', 'dashboards');
        });

        it('as Antonella Puglia', () => {
            cy.get('input[name=email]').type('antonellapuglia@webmapp.it');
            cy.get('input[name=password]').type('webmapp');
            cy.get('button').contains('Login').click();
            cy.url().should('contain', 'dashboards');
        });

        it('as Davide Pizzato', () => {
            cy.get('input[name=email]').type('davidepizzato@webmapp.it');
            cy.get('input[name=password]').type('webmapp');
            cy.get('button').contains('Login').click();
            cy.url().should('contain', 'dashboards');
        });

        it('as Marco Barbieri', () => {
            cy.get('input[name=email]').type('marcobarbieri@webmapp.it');
            cy.get('input[name=password]').type('webmapp');
            cy.get('button').contains('Login').click();
            cy.url().should('contain', 'dashboards');
        });

        it('as Pedram Katanchi', () => {
            cy.get('input[name=email]').type('pedramkatanchi@webmapp.it');
            cy.get('input[name=password]').type('webmapp');
            cy.get('button').contains('Login').click();
            cy.url().should('contain', 'dashboards');
        });

        it('as Laura Roth', () => {
            cy.get('input[name=email]').type('lauraroth72@gmail.com');
            cy.get('input[name=password]').type('geohub');
            cy.get('button').contains('Login').click();
            cy.url().should('contain', 'dashboards');
        });
    })
})
