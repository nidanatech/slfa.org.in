import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import animate from 'tailwindcss-animate';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],
    theme: {
        container: {
            center: true,
            padding: "2rem",
            screens: {
                "2xl": "1400px",
            },
        },
        extend: {
            fontFamily: {
                sans: ['Lato', ...defaultTheme.fontFamily.sans],
                lato: ['Lato', 'sans-serif'],
                abhaya: ['Abhaya Libre', 'serif'],
                outfit: ['Outfit', 'sans-serif'],
                inter: ['Inter', 'sans-serif'],
                display: ['Playfair Display', 'Georgia', 'serif'],
            },
            colors: {
                border: "hsl(var(--border))",
                input: "hsl(var(--input))",
                ring: "hsl(var(--ring))",
                background: "hsl(var(--background))",
                foreground: "hsl(var(--foreground))",
                primary: {
                    DEFAULT: "hsl(var(--primary))",
                    foreground: "hsl(var(--primary-foreground))",
                },
                secondary: {
                    DEFAULT: "hsl(var(--secondary))",
                    foreground: "hsl(var(--secondary-foreground))",
                },
                destructive: {
                    DEFAULT: "hsl(var(--destructive))",
                    foreground: "hsl(var(--destructive-foreground))",
                },
                muted: {
                    DEFAULT: "hsl(var(--muted))",
                    foreground: "hsl(var(--muted-foreground))",
                },
                accent: {
                    DEFAULT: "hsl(var(--accent))",
                    foreground: "hsl(var(--accent-foreground))",
                },
                popover: {
                    DEFAULT: "hsl(var(--popover))",
                    foreground: "hsl(var(--popover-foreground))",
                },
                card: {
                    DEFAULT: "hsl(var(--card))",
                    foreground: "hsl(var(--card-foreground))",
                },
                // SLFA Brand Colors (Derived from Logo)
                slfa: {
                    primary: '#F58220',      // Logo Saffron/Orange
                    'primary-dark': '#D96A00', // Darker Orange
                    secondary: '#5C9E42',    // Logo Green
                    'secondary-dark': '#4A8234', // Darker Green
                    accent: '#D32F2F',       // Logo Crest Red
                    navy: '#0a1128',         // Deep midnight navy
                    'navy-light': '#1b2a49', // Lighter midnight navy
                    cream: '#FFFAF3',        // Warm handcrafted cream
                    
                    // Central mapping of all previously used classes to the new logo colors
                    maroon: '#D32F2F',       // Mapped to Logo Red (Accent)
                    gold: '#F58220',         // Mapped to Logo Orange (Primary)
                    teal: '#5C9E42',         // Mapped to Logo Green (Secondary)
                    coral: '#F58220',        // Mapped to Logo Orange (Primary)
                    'coral-dark': '#D96A00', // Mapped to Logo Orange Dark
                    'coral-light': '#F8A07A', 
                    saffron: '#F58220',      // Mapped to Logo Orange (Primary)
                    peach: '#FEF3EB',        // Light orange background tint
                    green: '#5C9E42',        // Mapped to Logo Green (Secondary)
                },
            },
            borderRadius: {
                lg: "var(--radius)",
                md: "calc(var(--radius) - 2px)",
                sm: "calc(var(--radius) - 4px)",
            },
            keyframes: {
                "accordion-down": {
                    from: { height: 0 },
                    to: { height: "var(--radix-accordion-content-height)" },
                },
                "accordion-up": {
                    from: { height: "var(--radix-accordion-content-height)" },
                    to: { height: 0 },
                },
                "fade-in": {
                    from: { opacity: 0, transform: "translateY(10px)" },
                    to: { opacity: 1, transform: "translateY(0)" },
                },
                "slide-in-right": {
                    from: { opacity: 0, transform: "translateX(20px)" },
                    to: { opacity: 1, transform: "translateX(0)" },
                },
                "bounce-slow": {
                    "0%, 100%": { transform: "translateY(0)" },
                    "50%": { transform: "translateY(-8px)" },
                },
            },
            animation: {
                "accordion-down": "accordion-down 0.2s ease-out",
                "accordion-up": "accordion-up 0.2s ease-out",
                "fade-in": "fade-in 0.5s ease-out forwards",
                "slide-in-right": "slide-in-right 0.5s ease-out forwards",
                "bounce-slow": "bounce-slow 3s ease-in-out infinite",
            },
        },
    },
    plugins: [forms, animate],
};
