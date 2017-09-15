var m = require("mithril")
var moment = require("moment")
var TaskList = require("./views/TaskList")
var Index = require("./views/Index")

m.route.prefix("")
m.route(document.body, "/", {
  "/": Index,
  "/today": {
    onmatch: function(args, requestedPath) {
      var today = moment().format("/YYYY/MM/DD")
      m.route.set(today)
    }
  },
  "/:year/:month/:day": TaskList
})