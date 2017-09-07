var m = require("mithril")
var Stream = require("mithril/stream")
var moment = require("moment")

var Task = require("../models/Task")
var NewTask = require("./NewTask")

function setHeight(domNode) {
  let height = 300;
  domNode.style.height = '';
  if (domNode.scrollHeight > height) {
    height = domNode.scrollHeight;
  }

  domNode.style.height = height + 10 + 'px';
}

module.exports = {
  date: Stream(""),
  oninit: function(vnode) {
    if (moment([vnode.attrs.year, vnode.attrs.month, vnode.attrs.day]).isValid() === false) {
      m.route.set("/")
    }
    vnode.state.date(vnode.attrs.year + "-" + vnode.attrs.month + "-" + vnode.attrs.day)

    Task.loadList(vnode.state.date)
  },
  edit: function (task) {
    Task.update(task, this.date)
  },
  delete: function(task) {
    if (!confirm("本当に削除しますか？")) return
    Task.delete(task, this.date)
  },
  done: function(task) {
    task.done(!task.done())
    Task.update(task, this.date)
  },
  view: function(vnode) {
    return [
      m("h1.date.mar-sm", vnode.state.date),
      m(NewTask, {date: vnode.state.date}),
      m(".task-list.mar-sm",
        Task.list.map(function(task) {
          return [
            m("h4",
              {
                style: { "font-family": "serif" },
                ondblclick: function(v) { task.edit(!task.edit()) }
              },
              m("i.fa.mar-r-sm",
                {
                  class: task.done()? "fa-calendar-check-o": "fa-calendar-o",
                  onclick: function(v) { vnode.state.done(task) }
                }
              ),
              task.title
            ),
            moment().format('YYYY-MM-DD') != moment(task.date()).format('YYYY-MM-DD')
              ? m("small.text-warning.text-right", moment(task.date()).format('YYYY-MM-DD'))
              : "",
            m(".description.mar-b-sm",
              {
                style: {
                  "font-family": "serif",
                  display: !task.edit() ? "block" : "none"
                }
              },
              m.trust(task.format_desc())
            ),
            m(".edit",
              {
                style: {
                  display: task.edit() ? "block" : "none"
                }
              },
              m(".input-field", [
                m("textarea", {
                  style: {
                    "white-space": "pre-wrap"
                  },
                  oncreate: function (v) {
                    task.desc.map(function () {
                      setHeight(v.dom)
                    })
                  },
                  oninput: m.withAttr("value", task.desc),
                  value: task.desc()
                })
              ]),
              m(".input-field", [
                m(".row.row-between", [
                  m(".col-1", [
                    m("button.mar-r-ms", { onclick: function (v) { vnode.state.edit(task) } }, "登録")
                  ]),
                  m(".col-1", [
                    m("button", { onclick: function (v) { vnode.state.delete(task) } }, "削除")
                  ]),
                ])
              ])
            )
          ]
        })
      )
    ]
  }
}
