import m from "mithril";
import Stream from "mithril/stream";

import User from "./User";

const API_URI = "http://localhost:8080/api/items"

class taskModel {
  constructor(data) {
    this.id = Stream(data.id);
    this.title = Stream(data.title);
    this.desc = Stream(data.desc);
    this.date = Stream(data.date);
    this.done = Stream(data.done);
    this.schedule = Stream(data.schedule);
    this.startAt = Stream(data.startAt);
    this.endAt = Stream(data.endAt);
    this.edit = Stream(false);
    this.tags = Stream(data.tags);
  }
}

class Task {
  constructor() {
    this.list = Stream([]);
    this.error = {
      title: Stream(""),
      startAt: Stream(""),
      endAt: Stream("")
    };
  }

  add(title, date) {
    this.error.title("");
    return m.request({
      method: "POST",
      url: API_URI,
      data: {
        title: title(),
        date: date()
      },
      headers: {
        "Authorization": "Bearer " + User.getToken()
      },
      extract: (xhr) => {
        if (xhr.status == 401) {
          m.route.set('/');
        }

        return xhr.responseText;
      },
      type: taskModel
    })
    .then((result) => this.loadList(date))
    .catch((e) => {
      if (e.hasOwnProperty('title')) {
        this.error.title(e.title[0]);
      }
    });
  }

  update(task, date) {
    this.error.startAt("");
    this.error.endAt("");
    return m.request({
      method: "PUT",
      url: API_URI + "/" + task.id(),
      headers: {
        "Authorization": "Bearer " + User.getToken()
      },
      extract: (xhr) => {
        if (xhr.status == 401) {
          m.route.set('/');
        }

        return xhr.responseText;
      },
      data: {
        title: task.title(),
        desc: task.desc(),
        done: task.done(),
        date: task.date(),
        schedule: task.schedule(),
        startAt: task.startAt(),
        endAt: task.endAt(),
        tags: task.tags()
      },
      type: taskModel
    })
    .then((result) => this.loadList(date))
    .catch((e) => {
      if (e.hasOwnProperty('startAt')) {
        this.error.startAt(e.startAt[0]);
      }
      if (e.hasOwnProperty('endAt')) {
        this.error.endAt(e.endAt[0]);
      }
    });
  }

  delete(task, date) {
    return m.request({
      method: "DELETE",
      url: API_URI + "/" + task.id(),
      headers: {
        "Authorization": "Bearer " + User.getToken()
      },
      extract: (xhr) => {
        if (xhr.status == 401) {
          m.route.set('/');
        }

        return xhr.responseText;
      },
    })
    .then((result) => this.loadList(date));
  }

  loadList(date) {
    return m.request({
      method: "GET",
      url: API_URI + "/" + date(),
      headers: {
        "Authorization": "Bearer " + User.getToken()
      },
      extract: (xhr) => {
        if (xhr.status == 401) {
          m.route.set('/');
        }

        let data = xhr.responseText;
        try {return data !== "" ? JSON.parse(data) : null}
        catch (e) {throw new Error(data)}
      },
      type: taskModel
    })
    .then(this.list)
    .then(User.refreshToken());
  }
}

export default new Task();
