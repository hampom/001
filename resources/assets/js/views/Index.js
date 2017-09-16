var m = require("mithril")
var Stream = require("mithril/stream")
var User = require("../models/User")

module.exports = {
  user_name: Stream(""),
  password: Stream(""),
  view: function(vnode) {
    return m("form",
      {
        onsubmit: function (e) {
          e.preventDefault()
          User.login(vnode.state.user_name(), vnode.state.password())
          .then(function(e) {
            m.route.set("/today")
          })
        }
      },
      [
        m(".input-field", [
          m("label", "ユーザー名"),
          m("input[type=text]", { oninput: m.withAttr("value", vnode.state.user_name), value: vnode.state.user_name})
        ]),
        m(".input-field", [
          m("label", "パスワード"),
          m("input[type=password]", { oninput: m.withAttr("value", vnode.state.password), value: vnode.state.password})
        ]),
        m("button", "ログイン")
      ]
    )
  }
}
