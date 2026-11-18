export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        'neon-cyan': '#d6ad4f',
        'neon-purple': '#8d672a',
        'neon-pink': '#c8973a',
        'neon-gold': '#f6e5ad',
        'dark-bg': '#100e0a',
        'dark-card': '#211b10',
        'dark-border': '#302412',
      },
      fontFamily: {
        'display': ['Space Grotesk', 'sans-serif'],
        'body': ['Inter', 'sans-serif'],
      },
    },
  },
  plugins: [],
}