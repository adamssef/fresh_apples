module.exports = {
  mode: 'jit',
  content: [
    './templates/layout/page.html.twig',
  ],
  theme: {
    dropShadow: {
      'custom': '0 0 1px black', // Add your custom drop-shadow here
    },
    extend: {
    },
  },
  plugins: [],
};
