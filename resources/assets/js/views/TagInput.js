import m from "mithril";
import Stream from "mithril/stream";

export default class TagInput {
  add (e, tags) {
    if (e.keyCode === 13 && e.target.value) {
      if (tags().indexOf(e.target.value) === -1) {
        let tmp = tags();
        tmp.push(e.target.value);
        tags(tmp);
      }
      e.target.value = '';
    }
  }

  del (tag, tags) {
    let tmp = tags().filter(function(v) {
        return v != tag;
    });
    tags(tmp);
  }

  view(vnode) {
    return m(".tag-input.mar-t-xs",
      [
      vnode.attrs.tags().map((tag) => {
        return m('span.badge.mar-r-xs.mar-b-xs',
          {
            style: {
              "cursor": "pointer",
            },
            onclick: (e) => vnode.state.del(tag, vnode.attrs.tags)
          },
          tag
        );
      }),
      m("input[type=text].pad-0", {
        onkeypress: (e) => vnode.state.add(e, vnode.attrs.tags)
      })
    ])
  }
}