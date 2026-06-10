import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.data('passwordToggle', () => ({
        showPassword: false,
        toggle() {
            this.showPassword = !this.showPassword;
        },
    }));

    Alpine.data('authTabs', () => ({
        activeTab: 'login',
        setTab(tab) {
            this.activeTab = tab;
        },
    }));
});

Alpine.start();
