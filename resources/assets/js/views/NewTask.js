var m = require("mithril")
var Stream = require("mithril/stream")
var Task = require("../models/Task")

module.exports = {
  title: Stream(""),
  date: Stream(""),
  oninit: function(vnode) {
      vnode.state.date(vnode.attrs.date)
  },
  view: function(vnode) {
    return m("form",
      {
        onsubmit: function (e) {
          Task
            .add(vnode.state.title(), vnode.attrs.date())
            .then(vnode.state.title(""))
        }
      },
      m(".input-group.w-75.mar-sm", [
        m("input[type=text]", { oninput: m.withAttr("value", vnode.state.title), value: vnode.state.title }),
        m("button", "登録")
      ])
    )
  }
}