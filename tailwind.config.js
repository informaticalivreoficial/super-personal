/** @type {import('tailwindcss').Config} */
export default {
  content: [
      './resources/**/*.blade.php',
      './resources/**/*.js',
      './resources/**/*.vue',
  ],
  theme: {
    extend: {
      colors: {
        // Paleta da marca SportPlan (laranja → marrom)
        // brand-400/500/600/700/800 são as cores exatas da identidade;
        // 50–300 e 900–950 derivadas para fundos, sidebar e estados.
        brand: {
          50: '#FFFAF3',
          100: '#FFF4E1',
          200: '#FFE8C1',
          300: '#FFD68F',
          400: '#FFA506',
          500: '#D58204',
          600: '#AC5F03',
          700: '#823C01',
          800: '#591900',
          900: '#431300',
          950: '#2C0C00',
        },
      },
    },
  },
  plugins: [],
}
