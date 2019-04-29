// static
workbox.routing.registerRoute(
	new RegExp('https://cdn'),
	workbox.strategies.networkFirst({
		cacheName: 'static'
	}),
)
