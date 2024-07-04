export class MapObjects {

    getTrace(color = 'yellow') {
        return {
            id: 'trace',
            type: 'line',
            source: 'trace',
            paint: {
                'line-color': color,
                'line-opacity': 0.75,
                'line-width': 5
            }
        }
    };

    getRoutlineActive() {
        return {
            id: 'routeline-active',
            type: 'line',
            source: 'route',
            layout: {
                'line-join': 'round',
                'line-cap': 'round'
            },
            paint: {
                'line-color': '#3887be',
                'line-width': ['interpolate', ['linear'], ['zoom'], 12, 3, 22, 12]
            }
        }
    }

    getRouteArrows() {
        return {
            id: 'routearrows',
            type: 'symbol',
            source: 'route',
            layout: {
                'symbol-placement': 'line',
                'text-field': '▶',
                'text-size': ['interpolate', ['linear'], ['zoom'], 12, 24, 22, 60],
                'symbol-spacing': ['interpolate', ['linear'], ['zoom'], 12, 30, 22, 160],
                'text-keep-upright': false
            },
            paint: {
                'text-color': '#3887be',
                'text-halo-color': 'hsl(55, 11%, 96%)',
                'text-halo-width': 3
            }
        }
    }

    getCircle() {
        return {
            id: "circle",
            type: "line",
            source: {
                "type": "geojson",
                "data": "",
                "lineMetrics": true,
            },
            paint: {
                "line-color": "red",
                "line-width": 10,
                "line-offset": 5,
                "line-dasharray": [1, 1]
            },
            layout: {}
        }
    }

    linkStar() {
        return {
            'id': 'link-star',
            'type': 'line',
            'source': 'link-star',
            'layout': {},
            'paint': {
                'line-color': '#00C000',
                'line-width': 3,
            },
        }
    }
}
