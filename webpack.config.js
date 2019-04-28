const path = require('path');
const {GenerateSW} = require('workbox-webpack-plugin');
const dist = __dirname + '/dist';

module.exports = {
  entry: './resources/assets/js/index.js',
  output: {
    path: __dirname + '/public/js',
    filename: 'app.js',
  },
  resolve: {
    modules: [
      'resources/assets/js',
      path.join(__dirname, 'node_modules'),
    ]
  },
  module: {
    loaders: [{
      test: /\.js$/,
      exclude: /node_modules/,
      loader: 'babel-loader',
    }]
  },
  plugins: [
    new GenerateSW({
      swDest: dist + '/js/sw.js',
      clientsClaim: true,
      skipWaiting: true,
    })
  ]
}
