/** @type {import('tailwindcss').Config} */
export default {
  // Tema claro é o padrão; o escuro entra pela classe .dark no <html>,
  // alternada pelo botão no header.
  darkMode: 'class',
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./app/Livewire/**/*.php",
    "./vendor/livewire/livewire/src/resources/views/**/*.blade.php",
  ],
  theme: {
    extend: {
      colors: {
        // Claro: o branco do kimono, não um creme quente.
        tatame: {
          base: '#F7F7F4',
          surface: '#FFFFFF',
          raised: '#EFEFEA',
          line: '#E1E2DC',
          ink: '#1B2027',
          muted: '#5A6472',
        },
        // Escuro: grafite, não preto absoluto.
        noite: {
          base: '#171A1F',
          surface: '#1F242B',
          raised: '#262C34',
          line: '#2F363F',
          ink: '#F2F3F1',
          muted: '#9BA5B1',
        },
        // As faixas identificam as modalidades no site inteiro.
        faixa: {
          amarela: '#F0B429',
          laranja: '#E07A2F',
          verde: '#2E7D5B',
          azul: '#1D6FB8',
          marrom: '#6B4130',
          vermelha: '#C0392B',
        },
      },
      fontFamily: {
        sans: ['Karla', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        display: ['Archivo', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
