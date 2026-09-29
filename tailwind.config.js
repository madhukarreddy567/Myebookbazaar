/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
    ],
    theme: {
        extend: {
            colors: {
                // D1/D2 design system — mapped to CSS variables in
                // resources/css/tokens.css (the single source of truth),
                // so dark mode swaps them automatically.
                ink: {
                    900: 'var(--color-teal-900)',
                    700: 'var(--color-teal-700)',
                    100: 'var(--color-teal-100)',
                },
                teal: {
                    900: 'var(--color-teal-900)',
                    700: 'var(--color-teal-700)',
                    100: 'var(--color-teal-100)',
                },
                saffron: {
                    700: 'var(--color-saffron-700)',
                    600: 'var(--color-saffron-600)',
                    500: 'var(--color-saffron-500)',
                    100: 'var(--color-saffron-100)',
                },
                surface: 'var(--color-surface)',
                canvas: 'var(--color-bg)',
                line: 'var(--color-border)',
                inktext: 'var(--color-text)',
                mutedtext: 'var(--color-muted)',
                success: 'var(--color-success)',
                warning: 'var(--color-warning)',
                danger: 'var(--color-danger)',
                // Legacy Phase 1 palette — still used by the admin panel
                // until D2 restyles it. Do not remove before then.
                brand: {
                    teal: '#0F4C5C',
                    'teal-dark': '#0C3D4A',
                    'teal-darker': '#082E38',
                    'teal-700': '#146072',
                    'teal-300': '#8FBFC9',
                    'teal-200': '#B9D9E0',
                    'teal-100': '#DCECEF',
                    'teal-50': '#EFF6F8',
                    orange: '#F59E0B',
                    'orange-dark': '#D97706',
                    'orange-light': '#FBBF24',
                },
            },
            fontFamily: {
                heading: ['Poppins', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                body: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                display: ['"Barlow Condensed"', '"Arial Narrow"', 'system-ui', 'sans-serif'],
                sans: ['"DM Sans"', 'system-ui', '-apple-system', '"Segoe UI"', 'sans-serif'],
            },
        },
    },
    plugins: [],
};
