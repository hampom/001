import m from "mithril";
import Stream from "mithril/stream";
import moment from "moment";

import Task from "../models/Task";

export default class NewTask {
  constructor(vnode) {
    this.title = Stream("");
    this.date = Stream("");
  }

  oninit(vnode) {
    this.date(vnode.attrs.date);
  }

  add() {
    Task
      .add(this.title, this.date)
      .then(() => {
        if (!Task.error.title()) {
          this.title("");
        }
      })
  }

  view(vnode) {
    return m("form",
      {
        onsubmit: (e) => {
          e.preventDefault();
          vnode.state.add();
        }
      },
      m(".input-field.w-75.mar-sm",
        {
          class: Task.error.title() ? "input-invalid" : ""
        },
        [
          Task.error.title()
            ? m('label', Task.error.title())
            : "",
          m(".input-group.w-75.mar-sm",
            [
              m("input[type=text]", { oninput: m.withAttr("value", vnode.state.title), value: vnode.state.title }),
              m("button", "登録")
            ]),
        ]
      )
    )
  }
}