/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./app/Livewire/**/*.php",
    "./vendor/livewire/livewire/src/resources/views/**/*.blade.php",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
