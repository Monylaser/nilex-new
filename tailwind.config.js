import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans:  ['Cairo', ...defaultTheme.fontFamily.sans],
                cairo: ['Cairo', 'sans-serif'],
            },

            colors: {
                nilex: {
                    DEFAULT: '#1D9E75',
                    dark:    '#085041',
                    light:   '#9FE1CB',
                    50:  '#f0faf5',
                    100: '#d6f3e7',
                    200: '#9FE1CB',
                    300: '#6ecfb0',
                    400: '#3eba95',
                    500: '#1D9E75',
                    600: '#178060',
                    700: '#085041',
                    800: '#063d30',
                    900: '#042b22',
                },
            },

            boxShadow: {
                'nilex':      '0 4px 14px rgba(29, 158, 117, 0.22)',
                'nilex-lg':   '0 8px 28px rgba(29, 158, 117, 0.28)',
                'card':       '0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03)',
                'card-hover': '0 8px 24px rgba(0,0,0,0.08)',
                'dropdown':   '0 8px 24px rgba(0,0,0,0.10), 0 1px 4px rgba(0,0,0,0.05)',
            },

            keyframes: {
                'fade-up': {
                    from: { opacity: '0', transform: 'translateY(24px)' },
                    to:   { opacity: '1', transform: 'translateY(0)' },
                },
                'fade-in': {
                    from: { opacity: '0' },
                    to:   { opacity: '1' },
                },
                'slide-down': {
                    from: { opacity: '0', transform: 'translateY(-8px)' },
                    to:   { opacity: '1', transform: 'translateY(0)' },
                },
                'badge-pulse': {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.6' },
                },
                'float-gentle': {
                    '0%, 100%': { transform: 'translateY(0px)' },
                    '50%':      { transform: 'translateY(-8px)' },
                },
            },

            animation: {
                'fade-up':      'fade-up 0.5s ease both',
                'fade-in':      'fade-in 0.3s ease both',
                'slide-down':   'slide-down 0.2s ease both',
                'badge-pulse':  'badge-pulse 2.5s ease-in-out infinite',
                'float-gentle': 'float-gentle 6s ease-in-out infinite',
            },

            borderRadius: {
                xl:  '0.75rem',
                '2xl': '1rem',
                '3xl': '1.25rem',
            },
        },
    },

    plugins: [forms],
};
