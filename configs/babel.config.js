export default {
  presets: [
    [
      '@babel/preset-env',
      {
        bugfixes: true
      }
    ],
    [
      '@babel/preset-react',
      {
        runtime: 'automatic'
      }
    ]
  ]
};
