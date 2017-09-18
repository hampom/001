import m from "mithril";
import Stream from "mithril/stream";
import User from "../models/User";

export default class Index {
  constructor(vnode) {
    this.user_name = Stream("");
    this.password = Stream("");
  }
  oninit(vnode) {
    this.error = Stream.combine(
      (user_name, password) => {
        return user_name() && password()
      },
      [
        this.user_name,
        this.password
      ]
    );
  }

  login() {
    if (!this.error()) {
      return;
    }

    User.login(this.user_name(), this.password())
        .then((e) => {
          m.route.set("/today")
        })
        .catch((e) => {
          // TODO: エラーを出力
        });
  }

  view(vnode) {
    return m("form",
      {
        onsubmit: (e) => {
          e.preventDefault();
          vnode.state.login();
        }
      },
      [
        m(".input-field", [
          m("label", "ユーザー名"),
          m("input[type=text]", { oninput: m.withAttr("value", vnode.state.user_name), value: vnode.state.user_name })
        ]),
        m(".input-field", [
          m("label", "パスワード"),
          m("input[type=password]", { oninput: m.withAttr("value", vnode.state.password), value: vnode.state.password })
        ]),
        m("button", "ログイン")
      ]
    )
  }
}
