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
    }
  },
  variants: {
    extend: {},
  },
  plugins: [],
}
