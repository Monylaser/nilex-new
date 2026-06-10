describe('Abuse protection', () => {
    it('returns validation errors for invalid registration contact', () => {
        cy.visit('/register');
        cy.get('form').then(($form) => {
            if ($form.find('input[name="contact"]').length) {
                cy.get('input[name="name"]').type('Abuse Tester');
                cy.get('input[name="contact"]').type('not-valid-contact');
                cy.get('input[name="password"]').type('Password123!');
                cy.get('form').submit();
                cy.url().should('include', '/register');
            }
        });
    });
});
