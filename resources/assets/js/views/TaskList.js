import m from "mithril";
import Stream from "mithril/stream";
import moment from "moment";

import NewTask from "./NewTask";

import Task from "../models/Task";

var setHeight = (domNode) => {
  let height = 300;
  domNode.style.height = '';
  if (domNode.scrollHeight > height) {
    height = domNode.scrollHeight;
  }

  domNode.style.height = height + 10 + 'px';
}

export default class TaskList {
  constructor(vnode) {
    this.date = Stream("");
  }

  oninit(vnode) {
    if (moment([vnode.attrs.year, vnode.attrs.month, vnode.attrs.day]).isValid() === false) {
      m.route.set("/today");
    }
    vnode.state.date(vnode.attrs.year + "-" + vnode.attrs.month + "-" + vnode.attrs.day);
    Task.loadList(vnode.state.date);
  }

  edit(task) {
    Task.update(task, this.date);
  }

  delete(task) {
    if (!confirm("本当に削除しますか？")) return;
    Task.delete(task, this.date);
  }

  done(task) {
    task.done(!task.done());
    Task.update(task, this.date);
  }

  view(vnode) {
    return [
      m("h1.date.mar-sm", moment(vnode.state.date, "YYYY-MM-DD").format('YYYY-MM-DD (ddd)')),
      m(NewTask, { date: vnode.state.date }),
      m(".task-list.mar-sm",
        Task.list.map((task) => {
          return [
            m("h4",
              {
                style: { "font-family": "serif" },
                ondblclick: (v) => { task.edit(!task.edit()) }
              },
              m("i.fa.mar-r-sm",
                {
                  class: task.done() ? "fa-calendar-check-o" : "fa-calendar-o",
                  onclick: (v) => { vnode.state.done(task) }
                }
              ),
              task.title
            ),
            m(".row", [
              m(".col-2",
                moment().format('YYYY-MM-DD') != moment(task.date()).format('YYYY-MM-DD')
                  ? m("small.text-warning", [m("i.fa.fa-fw.fa-plus-circle"), moment(task.date()).format('YYYY-MM-DD')])
                  : ""
              ),
              m(".col-3.text-primary",
                task.schedule()
                  ? [m("i.fa.fa-fw.fa-calendar"), task.startAt() ? task.startAt() + " ～ " + task.endAt() : "終日"]
                  : ""
              )
            ]),
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
              Task.error.startAt() || Task.error.endAt()
                ? m(".input-field.input-invalid", [
                  Task.error.startAt()
                    ? m('label', Task.error.startAt())
                    : "",
                  Task.error.endAt()
                    ? m('label', Task.error.endAt())
                    : "",
                ])
                : "",
              m(".input-group.w-50",
                {
                  class: (Task.error.startAt() || Task.error.endAt()) ? "input-invalid" : ""
                },
                [
                  m("span.input-addon", [
                    m("label", [
                      m("i.fa.fa-fw", {
                        class: task.schedule() ? 'fa-toggle-on' : 'fa-toggle-off',
                      }),
                      m("span", { onclick: (v) => { task.schedule(!task.schedule()) } }, "スケジュール"),
                    ])
                  ]),
                  m("input[type=text]", { 'placeholder': '開始時間 xx:xx', oninput: m.withAttr("value", task.startAt), value: task.startAt() }),
                  m("span.input-addon", " ～ "),
                  m("input[type=text]", { 'placeholder': '終了時間 xx:xx', oninput: m.withAttr("value", task.endAt), value: task.endAt() })
                ]),
              m(".input-field", [
                m("textarea", {
                  style: {
                    "white-space": "pre-wrap"
                  },
                  oncreate: (v) => {
                    task.desc.map(() => {
                      setHeight(v.dom)
                    })
                  },
                  onupdate: (v) => {
                    v.dom.focus()
                  },
                  oninput: m.withAttr("value", task.desc),
                  value: task.desc()
                })
              ]),
              m(".input-field", [
                m(".row.row-between", [
                  m(".col-1", [
                    m("button.mar-r-ms", { onclick: (v) => { vnode.state.edit(task) } }, "登録")
                  ]),
                  m(".col-1", [
                    m("button", { onclick: (v) => { vnode.state.delete(task) } }, "削除")
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
