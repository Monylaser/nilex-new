describe('Social authentication', () => {
    it('redirects to provider for google oauth', () => {
        cy.visit('/auth/google/redirect', { failOnStatusCode: false });
        cy.url().should('not.include', '/dashboard');
    });
});
