/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./node_modules/flowbite/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
                // El verde oliva/musgo del fondo
                'solare-green': '#898e79', 
                // El color café del botón y el texto activo
                'solare-gold': '#a68b77', 
                // El gris oscuro/topo de los textos de la derecha
                'solare-text': '#5a5a5a',
                // Gris muy claro para los inputs
                'solare-input': '#f3f1ef',
            },
            fontFamily: {
                // Una fuente serif elegante (requiere importarla en el CSS o usar del sistema)
                'serif-solare': ['Playfair Display', 'serif'], 
                'sans': ['Inter', ...defaultTheme.fontFamily.sans],
            },
        },
  },
  plugins: [
    require('flowbite/plugin')
  ],
}