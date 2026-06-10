Cypress.Commands.add('loginAsVerifiedUser', (email = 'verified@example.com', password = 'password') => {
    cy.session([email, password], () => {
        cy.request('POST', '/login', { email, password, _token: '' }).then(() => {
            cy.visit('/dashboard');
        });
    });
});
