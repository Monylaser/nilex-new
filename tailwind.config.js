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
                // Nilex brand — navy blue (was green; remapped per visual-identity refresh).
                // `nilex` keeps its name (alias) so existing bg-nilex/text-nilex usages turn blue.
                nilex: {
                    DEFAULT: '#11407A',
                    dark:    '#0B2F5C',
                    light:   '#3D8BD4',
                    50:  '#eef4fb',
                    100: '#d5e3f4',
                    200: '#aec8e8',
                    300: '#7ba7d8',
                    400: '#3D8BD4',
                    500: '#1f63a8',
                    600: '#11407A',
                    700: '#0B2F5C',
                    800: '#0a274b',
                    900: '#081d38',
                },
                // CTA / prices (orange-red). Deliberately distinct from semantic red-*.
                'nilex-orange': {
                    DEFAULT: '#E8431D',
                    light:   '#FF9A4D',
                },
                // Sahel turquoise — primary CTA gradient endpoints
                'nilex-teal': {
                    DEFAULT: '#14A5A8',
                    deep:    '#0D7377',
                    light:   '#14BDBC',
                },
                'nilex-ink':    '#11203D', // secondary headings / governorate
                'nilex-bg':     '#F4F6FB', // light page background
            },

            boxShadow: {
                'nilex':      '0 4px 14px rgba(17, 64, 122, 0.22)',
                'nilex-lg':   '0 8px 28px rgba(17, 64, 122, 0.28)',
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
