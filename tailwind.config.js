module.exports = {
  purge: [
    './app/**/*.php',
    './resources/**/*.php'
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
      },
      zIndex: {
        '1000': '1000',
      }
    }
  },
  variants: {
    extend: {},
  },
  plugins: [],
}
