var path = require("path")

const config = {
  resolve: {
    modules: [
      'resources/assets/js',
      path.join(__dirname, 'node_modules')
    ]
  }
}

module.exports = config
