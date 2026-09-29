/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
    ],
    theme: {
        extend: {
            colors: {
                // MyEbookBazaar brand palette.
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
            },
        },
    },
    plugins: [],
};
