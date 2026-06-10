describe('Authentication', () => {
    it('shows login page', () => {
        cy.visit('/login');
        cy.contains('تسجيل الدخول').should('exist');
    });

    it('shows registration page', () => {
        cy.visit('/register');
        cy.get('input[name="name"]').should('exist');
    });
});
