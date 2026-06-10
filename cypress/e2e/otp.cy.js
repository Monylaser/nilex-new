describe('OTP verification flow', () => {
    it('redirects unauthenticated users from verify-otp', () => {
        cy.visit('/verify-otp', { failOnStatusCode: false });
        cy.url().should('include', '/login');
    });
});
