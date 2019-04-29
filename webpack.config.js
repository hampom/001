const path = require('path');
const {GenerateSW, InjectManifest} = require('workbox-webpack-plugin');
const dist = __dirname + '/dist';

module.exports = {
  entry: './resources/assets/js/index.js',
  output: {
    path: __dirname + '/public/js',
    publicPath: '/js/',
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
      navigateFallback: '/app-shell',
      navigateFallbackBlacklist: [/^\/api/],
      templatedURLs: {
        '/app-shell': 'src/views/index.tpl',
      },
      swDest: dist + '/sw.js',
      clientsClaim: true,
      skipWaiting: true,
      importScripts: ['sw-cdn.js'],
      runtimeCaching: [
        {
          urlPattern: /api\/items/,
          handler: 'NetworkFirst',
          options: {
            cacheName: 'api-cache',
            expiration: {
              maxEntries: 5,
              maxAgeSeconds: 60,
            }
          }
        }
      ]
    }),
  ]
}
