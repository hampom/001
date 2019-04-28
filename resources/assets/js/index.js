if ('serviceWorker' in navigator) {
	window.addEventListener('load', () => {
		navigator.serviceWorker.register('/js/sw.js').then(registration => {
			console.log('SW registered: ', registration);
		}).catch(registrationError => {
			console.log('SW registration failed: ', registrationError);
		});
	});
}

import m from "mithril";
import moment from "moment";
import TaskList from "./views/TaskList";
import Index from "./views/Index";

class TodayRoute {
  onmatch(args, requestedPath) {
    m.route.set(moment().format("/YYYY/MM/DD"));
  }
}

m.route.prefix("");
m.route(document.body, "/", {
  "/": Index,
  "/today": new TodayRoute,
  "/:year/:month/:day": TaskList
});
