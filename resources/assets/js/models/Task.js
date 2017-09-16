var m = require("mithril")
var Stream = require("mithril/stream")
var User = require("./User")

const API_URI = "http://localhost:8080/api/items"

var taskModel = function(data) {
  this.id = Stream(data.id)
  this.title = Stream(data.title)
  this.desc = Stream(data.desc)
  this.format_desc = Stream.combine(function(desc) {
    return desc().replace(/[\n\r]/g, "<br />");
  }, [this.desc])
  this.date = Stream(data.date)
  this.done = Stream(data.done)
  this.schedule = Stream(data.schedule)
  this.startAt = Stream(data.startAt)
  this.endAt = Stream(data.endAt)
  this.edit = Stream(false)
}

var Task = {
  list: [],
  error: {
    title: Stream(""),
    startAt: Stream(""),
    endAt: Stream("")
  },
  add: function(title, date) {
    Task.error.title("")
    console.log("aaaa")
    return m.request({
      method: "POST",
      url: API_URI,
      data: {
        title: title,
        date: date
      },
      headers: {
        "Authorization": "Bearer " + User.getToken()
      },
      type: taskModel
    })
    .then(function(result) {
      Task.loadList(date)
    })
    .catch(function(e) {
      if (e.hasOwnProperty('title')) {
        Task.error.title(e.title[0])
      }
      console.log(e)
    })
  },
  update: function(task, date) {
    Task.error.startAt("")
    Task.error.endAt("")
    return m.request({
      method: "PUT",
      url: API_URI + "/" + task.id(),
      headers: {
        "Authorization": "Bearer " + User.getToken()
      },
      data: {
        title: task.title(),
        desc: task.desc(),
        done: task.done(),
        date: task.date(),
        schedule: task.schedule(),
        startAt: task.startAt(),
        endAt: task.endAt()
      },
      type: taskModel
    })
    .then(function(result) {
      Task.loadList(date)
    })
    .catch(function(e) {
      if (e.hasOwnProperty('startAt')) {
        Task.error.startAt(e.startAt[0])
      }
      if (e.hasOwnProperty('endAt')) {
        Task.error.endAt(e.endAt[0])
      }
      console.log(e)
    })
  },
  delete: function(task, date) {
    return m.request({
      method: "DELETE",
      url: API_URI + "/" + task.id(),
      headers: {
        "Authorization": "Bearer " + User.getToken()
      },
    })
    .then(function(result) {
      Task.loadList(date)
    })
  },
  loadList: function(date) {
    return m.request({
      method: "GET",
      url: API_URI + "/" + date,
      headers: {
        "Authorization": "Bearer " + User.getToken()
      },
      extract: function (xhr) { return { status: xhr.status, body: xhr.responseText } },
      type: function (res) {
        try {
          data = JSON.parse(res.body)
          if (Array.isArray(data)) {
            for (var i = 0; i < data.length; i++) {
              data[i] = new taskModel(data[i])
            }
          } else {
            data = new taskModel(data)
          }
          return data
        } catch (e) {
          throw new Error(res)
        }
      }
    })
    .then(function (tasks) {
      Task.list = tasks
    })
    .catch(function (e) {
      if (e.status == 401) {
        m.route.set("/")
      }
    })
  }
}

module.exports = Task
