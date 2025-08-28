/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
    './vendor/filament/**/*.blade.php',
    './app/Filament/**/*.php',
  ],
  theme: {
    extend: {
      colors: {
        brand: '#0137A1',
        ink:   '#0f172a',
        muted: '#64748b',
        line:  '#e5e7eb',
        bg:    '#f7f8fa',
      },
      borderRadius: { '2xl': '1rem' },
      boxShadow: { soft: '0 6px 20px rgba(15,23,42,.06)' },
    },
  },
  plugins: [],
}
