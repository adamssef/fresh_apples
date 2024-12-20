module.exports = {
  mode: 'jit',
  content: [
    './templates/node--show.html.twig',
    './templates/region--content--show.html.twig',
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
