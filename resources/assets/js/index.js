var m = require("mithril")
var TaskList = require("./views/TaskList")
var Index = require("./views/Index")

m.route(document.body, "/", {
  "/": Index,
  "/:year/:month/:day": TaskList
})