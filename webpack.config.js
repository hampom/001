const path = require('path');

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
  }
}
