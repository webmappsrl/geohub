module.exports = {
  purge: [
    './resources/**/*.blade.php',
  ],
  darkMode: false, // or 'media' or 'class'
  theme: {
    extend: {
      fontFamily: {
        'body': ['"Inter"'],
      },
      colors: {
        'primary': '#508AA8',
        'secondary': '#FF7E6B',
      }
    }
  },
  variants: {
    extend: {},
  },
  plugins: [],
}
