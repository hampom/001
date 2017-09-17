var m = require("mithril")
var Stream = require("mithril/stream")

const TOKEN_API_URI = "http://localhost:8080/api/token"

var userModel = function(data) {
  this.expires = Stream(data.expires)
  this.token = Stream(data.token)
}

var User = {
  login: function(user_name, password) {
    return m.request({
      method: "POST",
      url: TOKEN_API_URI,
      user: user_name,
      password: password,
      type: userModel
    })
    .then(function(result) {
      localStorage.setItem('token', result.token())
    })
    .catch(function(e) {
        throw new Error(e)
    })
  },
  refreshToken: function() {
    return m.request({
      method: "POST",
      url: TOKEN_API_URI + "_refresh",
      type: userModel,
      headers: {
        "Authorization": "Bearer " + User.getToken()
      }
    })
    .then(function(result) {
      localStorage.setItem('token', result.token())
    })
    .catch(function(e) {
        throw new Error(e)
    })
  },
  getToken: function() {
    return localStorage.getItem('token')
  }
}

module.exports = User