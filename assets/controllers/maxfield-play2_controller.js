import {Controller} from '@hotwired/stimulus';
import {Modal} from "bootstrap";

import '../styles/map/play2.css'

import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';

import * as turf from '@turf/turf'
import Swal from "sweetalert2";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        path: String, jsonData: String, waypointIdMap: String, mapboxGlToken: String
    }

    static targets = [
        'keys', 'errorMessage', 'linkselect', 'mapDebug',
        'optionsBox', 'optionsBoxTrigger',
        'btnUploadKeys', 'distanceBar',
        'selProfile'
    ]

    className = 'maxfield-play2'

    maxfieldData = null
    waypointIdMap = null

    links = []
    soundNotifier = null
    distance = 0
    location = null
    destination = null

    map = null
    modal = null

    bounds = null
    markers = {}

    trackHeading = false
    centerLocation = false;
    isBusy = false

    optimizedRoutePoints = null

    connect() {
        this.maxfieldData = JSON.parse(this.jsonDataValue)
        this.waypointIdMap = JSON.parse(this.waypointIdMapValue)
        this.modal = new Modal('#exampleModal')
        this.links = this.maxfieldData.links
        this.optimizedRoutePoints = new Map()
        this.setupMap()
    }

    setupMap() {
        mapboxgl.accessToken = this.mapboxGlTokenValue;
        this.map = new mapboxgl.Map({
            container: 'map',
            center: [this.maxfieldData.waypoints[0].lon, this.maxfieldData.waypoints[0].lat],
            zoom: 14,
        });

        const el = document.createElement('div');
        el.className = 'destinationMarker'
        el.innerHTML = 'O'

        this.destinationMarker = new mapboxgl.Marker(el)
            .setLngLat([0, 0])
            .addTo(this.map)

        this.map.addControl(new mapboxgl.FullscreenControl(), 'top-left');
        this.map.addControl(new mapboxgl.NavigationControl({
            visualizePitch: true
        }), 'top-left');
        this.map.addControl(this.getZoomControl());
        this.map.addControl(this.getGeolocateControl(), 'bottom-right');
        this.map.addControl(this.getPlayControl())
        this.map.addControl(this.getOptionsBox())

        this.loadFarmLayer()
        this.loadFarmLayer2()
        this._clearLayers()
        this.zoomAll()

        this.map.on('dragstart', this.startDrag.bind(this))
        this.map.on('dragend', this.endDrag.bind(this))

        this.map.on('load', () => {
            this._toggleLayer('farm2', 'block');
        })

        this.map.on('style.load', () => {
            this.addObjects()
        })
    }

    addObjects() {
        this.map.addSource('trace', {type: 'geojson', data: turf.lineString([[0, 0], [0, 0]])});
        this.map.addLayer({
            'id': 'trace',
            'type': 'line',
            'source': 'trace',
            'paint': {
                'line-color': 'yellow',
                'line-opacity': 0.75,
                'line-width': 5
            }
        });

        this.map.addLayer({
            "id": "circle",
            "type": "line",
            "source": {
                "type": "geojson",
                "data": turf.circle([0, 0], .04),
                "lineMetrics": true,
            },
            "paint": {
                "line-color": "red",
                "line-width": 10,
                "line-offset": 5,
                "line-dasharray": [1, 1]
            },
            "layout": {}
        });

        const nothing = turf.featureCollection([]);

        this.map.addSource('route', {
            type: 'geojson',
            data: nothing
        });

        this.map.addLayer(
            {
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
        )

        this.map.addLayer(
            {
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
        )
    }

    updateObjects() {
        if (this.location && this.destination) {

            this.distance = turf.distance(this.location, this.destination).toFixed(3) * 1000;

            this.mapDebugTarget.innerText = this.distance;

            this.map.getSource('trace').setData(turf.lineString([this.location.geometry.coordinates, this.destination.geometry.coordinates]));

            if (this.distance <= 50) {
                this.map.getSource('circle').setData(turf.circle(this.location, .04))
            } else {
                this.map.getSource('circle').setData(turf.circle([0, 0], .04))
            }

            let dist = 0;
            let style = '';
            if (this.distance < 100) {
                dist = 100 - this.distance
                if (dist < 20) {
                    style = ' bg-success'
                } else if (dist < 50) {
                    style = ' bg-warning'
                } else {
                    style = ' bg-danger'
                }
            }

            this.distanceBarTarget.innerHTML =
                `<div class="progress" role="progressbar" style="height: 20px">
                   <div 
                   class="progress-bar progress-bar-striped progress-bar-animated${style}" 
                   style="width: ${dist}%"
                   >
                   </div>
                   &nbsp;${this.distance} m
                </div>`;
        } else {
            this.map.getSource('circle').setData(turf.circle([0, 0], .04))
            this.map.getSource('trace').setData(turf.lineString([[0, 0], [0, 0]]));
            this.distanceBarTarget.innerHTML = ''
        }
    }

    getBounds() {
        if (!this.bounds) {
            this.bounds = new mapboxgl.LngLatBounds();

            this.maxfieldData.waypoints.forEach(function (o) {
                this.bounds.extend([o.lon, o.lat]);
            }.bind(this));
        }

        return this.bounds
    }

    getPlayControl() {
        return {
            onAdd: (map) => {
                const container = document.createElement('div');
                container.classList.add('mapboxgl-ctrl');

                let linkList = '<option value="-1">Start...</option>'
                let num = 1
                this.links.forEach(function (link, i) {
                    linkList += '<option value="' + i + '">' + num + ' - ' + link.name + '</option>'
                    num++
                })

                container.innerHTML = '<div class="info legend">' +
                    '<div id="Xinstructions"></div>'+
                    '<button id="btnNext" data-action="maxfield-play2#nextLink">Start...</button><br />' +
                    '<select id="groupSelect" class="form-control" data-maxfield-play2-target="linkselect" data-action="maxfield-play2#jumpToLink">' +
                    linkList +
                    '</select>' +
                    '<div data-maxfield-play2-target="distanceBar" class="vw-100"></div>'

                return container;
            }, getDefaultPosition: () => {
                return 'bottom-left'
            }, onRemove: () => {
                //this.map.off('moveend', updateLatLon);
            }
        }
    }

    getOptionsBox() {
        return {
            onAdd: (map) => {
                return document.getElementById('optionsBox')
            }, getDefaultPosition: () => {
                return 'top-right'
            }, onRemove: () => {
                //this.map.off('moveend', updateLatLon);
            }
        }
    }

    getZoomControl() {
        return {
            onAdd: (map) => {
                return document.getElementById('zoomBox')
            }, getDefaultPosition: () => {
                return 'bottom-right'
            }, onRemove: () => {
            }
        }
    }

    getGeolocateControl() {
        const control = new mapboxgl.GeolocateControl({
            fitBoundsOptions: {
                linear: true,
                minZoom: 18,
                maxZoom: 18,
            },
            positionOptions: {
                enableHighAccuracy: true
            },
            trackUserLocation: true,
            showUserHeading: true
        });

        control.on('geolocate', this.onLocationFound.bind(this))
        control.on('geolocatex', (event) => {
            const latitude = event.coords.latitude;
            const longitude = event.coords.longitude;

            if (this.centerLocation) {
                this.map.flyTo({
                    center: [longitude, latitude],
                    // zoom:16
                });
            }

            this.mapDebugTarget.innerHTML = `Lat: ${latitude.toFixed(2)}<br>  Bearing: ${event.coords.heading}<br>` + `Map head: ${this.map.getBearing().toFixed(2)}`
            if (event.coords.heading) {
                if (this.trackHeading) {
                    this.map.setBearing(event.coords.heading);
                }
            }
        });

        return control
    }

    onLocationFound(event) {
        this.location = turf.point([event.coords.longitude, event.coords.latitude])

        this.mapDebugTarget.innerText = this.isBusy

        if (this.centerLocation && false === this.isBusy) {
            this.map.flyTo({center: this.location.geometry.coordinates});
        }

        this.updateObjects()
    }

    zoomIn() {
        this.map.zoomIn()
    }

    zoomOut() {
        this.map.zoomOut();
    }

    zoomAll() {
        this.map.fitBounds(this.getBounds(), {padding: 100})
    }

    loadFarmLayer() {
        this.markers.farm = []
        this.maxfieldData.waypoints.forEach(function (o) {
            const num = o.keys
            let css = num > 3 ? 'circle farmalot' : 'circle'
            const el = document.createElement('div');
            el.className = 'farm-layer'
            el.innerHTML = '<b class="' + css + '">' + num + '</b>'
            const marker = new mapboxgl.Marker(el)
                .setLngLat([o.lon, o.lat])
                .setPopup(new mapboxgl.Popup().setHTML('<b>' + o.name + '</b><br>' + o.description))
                .addTo(this.map)
            this.markers.farm.push(marker)
        }.bind(this))
        this.zoomAll()
    }

    async loadFarmLayer2() {
        this.markers.farm2 = [];

        const response = await fetch('/maxfield/get-user-keys/' + this.pathValue)
        const data = await response.json()

        // TODO Select proper user
        const userKeys = (data && 1 in data) ? data[1] : []
        let cnt = 0

        this.maxfieldData.waypoints.forEach(function (o) {
            const numKeys = o.keys
            let css = numKeys > 3 ? 'circle farmalot' : 'circle'

            let hasKeys = 0
            let capsules = ''
            for (let i = 0; i < userKeys.length; i++) {
                if (userKeys[i].guid === this.waypointIdMap[cnt].guid) {
                    hasKeys = userKeys[i].count
                    if (userKeys[i].capsules) {
                        capsules = '<br><br>' + userKeys[i].capsules
                    }
                    if (hasKeys >= numKeys) {
                        css += ' farm-done';
                    }
                }
            }

            const id = this.hashCode(o.lon.toString() + o.lat.toString())
            const el = document.createElement('div');
            el.className = 'farm-layer'
            el.innerHTML = `<b class="${css}">${numKeys ? numKeys : '-'}<span class="hasKeys">${hasKeys ? '&nbsp;' + hasKeys : ''}</span></b>`
            const popup = `
                <input type="checkbox" id="${id}chkDone"
                data-action="maxfield-play2#toggleDone"
                data-maxfield-play2-marker-param="${cnt}"
                > <label for="${id}chkDone">Done</label>
                <br>
                <b>${o.name}</b>
                <br>${o.description} ${hasKeys ? '(' + hasKeys + ')' : ''}${capsules}
                <hr>
                <input type="checkbox" id="${id}"
                data-action="maxfield-play2#toggleRoutePoint"
                data-maxfield-play2-lat-param="${o.lat}"
                data-maxfield-play2-lon-param="${o.lon}"
                data-maxfield-play2-id-param="${cnt}"
                > <label for="${id}">Route</label>
                <button class="btn btn-outline-info btn-sm"
                data-action="maxfield-play2#getRoute"
                data-maxfield-play2-lat-param="${o.lat}"
                data-maxfield-play2-lon-param="${o.lon}"
                >NAV</button>`
            const marker = new mapboxgl.Marker(el)
                .setLngLat([o.lon, o.lat])
                .setPopup(new mapboxgl.Popup().setHTML(popup))
                .addTo(this.map)
            this.markers.farm2.push(marker)
            cnt++
        }.bind(this))
    }

    toggleDone(event) {
        const element = this.markers['farm2'][event.params.marker]
        element.toggleClassName('done')
    }

    async getRoute(event) {
        if (null === this.location) {
            alert('Get location first...')

            return
        }
        const route = await this._getRoute([event.params.lon, event.params.lat])

        const coordinates = route.geometry.coordinates

        console.log(this.map.getSource('route'))

        if (this.map.getSource('route')) {
            this.map.getSource('route').setData(turf.lineString(coordinates));
        } else {
            this.map.addLayer({
                id: 'route',
                type: 'line',
                source: {
                    type: 'geojson',
                    data: turf.lineString(coordinates)
                },
                layout: {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                paint: {
                    'line-color': '#3887be',
                    'line-width': 5,
                    'line-opacity': 0.75
                }
            });
        }
        // add turn instructions here at the end
// get the sidebar and add the instructions
        const instructions = document.getElementById('instructions');
        const steps = route.legs[0].steps;

        console.log(steps)
        let tripInstructions = '';
        for (const step of steps) {
            tripInstructions += `<li>${step.maneuver.instruction}</li>`;
        }
        instructions.innerHTML = `<p><strong>Trip duration: ${Math.floor(
            route.duration / 60
        )} min 🚴 </strong></p><ol>${tripInstructions}</ol>`;


    }

    async toggleRoutePoint(event) {
        const point = [event.params.lon, event.params.lat]
        const id = event.params.id

        if (event.target.checked) {
            this.optimizedRoutePoints.set(id, point)
        } else {
            this.optimizedRoutePoints.delete(id);
        }

        const result = await this._displayOptimizedRoute()

        if ('error' === result) {
            event.target.checked = false;
            this.optimizedRoutePoints.delete(id);
        }
    }

    async _displayOptimizedRoute() {
        if (this.optimizedRoutePoints.size < 2) {
            return
        }

        const query = await fetch(this.assembleQueryURL(), {method: 'GET'});
        const response = await query.json();

        if (response.code !== 'Ok') {
            const handleMessage =
                response.code === 'InvalidInput'
                    ? 'Refresh to start a new route. For more information: https://docs.mapbox.com/api/navigation/optimization/#optimization-api-errors'
                    : 'Try a different point.';
            alert(`${response.code} - ${response.message}\n\n${handleMessage}`);
            return 'error'
        } else {
            const routeGeoJSON = turf.featureCollection([
                turf.feature(response.trips[0].geometry)
            ]);
            this.map.getSource('route').setData(routeGeoJSON);
        }

        return 'ok'
    }

    assembleQueryURL() {
        let coordinates = []
        for (const point of this.optimizedRoutePoints.values()) {
            coordinates.push(point)
        }

        return 'https://api.mapbox.com/optimized-trips/v1/'
            + this.selProfileTarget.value + '/'
            + coordinates.join(';') +
            '?overview=full&steps=true&geometries=geojson' +
            '&roundtrip=false' +
            '&source=first&destination=last' +
            '&access_token=' + this.mapboxGlTokenValue
    }

    async _getRoute(end) {

        const coordinates = [this.location.geometry.coordinates, end]
        const query = await fetch(
            'https://api.mapbox.com/directions/v5/'
            + this.selProfileTarget.value + '/'
            + coordinates.join(';')
            + '?steps=true&geometries=geojson'
            + '&access_token=' + this.mapboxGlTokenValue
            ,
            {method: 'GET'}
        )

        const json = await query.json();
        return json.routes[0]
    }

    hashCode(str) {
        // https://stackoverflow.com/posts/7616484/revisions
        let hash = 0,
            i, chr;
        for (i = 0; i < str.length; i++) {
            chr = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + chr;
            hash |= 0; // Convert to 32bit integer
        }
        return hash;
    }

    nextLink(e) {
        const length = this.linkselectTarget.length

        if (this.linkselectTarget.value < length - 2) {
            e.target.innerText = 'Next'
            const newVal = parseInt(this.linkselectTarget.value) + 1
            this.showDestination(newVal)
            this.linkselectTarget.value = newVal
        } else {
            e.target.innerText = 'Finished!'

            Swal.fire('Finished :)');
        }
    }

    jumpToLink(e) {
        this.showDestination(this.linkselectTarget.value)
    }

    showDestination(id) {
        if (id < 0) {
            this.destinationMarker.setLngLat([0, 0])
                .setPopup('')
            this.destination = null
            clearInterval(this.soundNotifier)
            this.soundNotifier = null

            document.getElementById('btnNext').innerText = 'Start...'

            return
        }

        document.getElementById('btnNext').innerText = 'Next'

        const destination = this.links[id]
        this.destination = turf.point([destination.lon, destination.lat])

        this.map.panTo(this.destination.geometry.coordinates)

        let description = ''

        description += '<ol>'
        destination.links.forEach(link => {
            description += '<li>' + `<img src="/waypoint_thumb/${this.waypointIdMap[link.num].guid}"` + 'width="60px" height="60px" alt="thumbnail image">' + '&nbsp;' + link.name + '</li>'
        })
        description += '</ol>'

        const el = document.createElement('div');
        el.className = 'circle destinationMarker'
        el.innerHTML = parseInt(id) + 1

        this.destinationMarker.remove()
        this.destinationMarker = new mapboxgl.Marker(el)
            .setLngLat(this.destination.geometry.coordinates)
            .setPopup(new mapboxgl.Popup({offset: 25, maxWidth: '400px'}) // add popups
                .setHTML(`<b>${destination.name}</b><hr>${description}`))
            .addTo(this.map)

        // Routing
        if (id > 0) {
            /*
            const previous = this.links[id - 1]
            const points = [
                L.latLng(previous.lat, previous.lon),
                L.latLng(destination.lat, destination.lon)
            ]
            this.originDestinationLine.setLatLngs(points)
            if (this.routingEnabled) {
                this.routingControl.setWaypoints(points)
            }
            */
        }

        // Sound
        if (!this.soundNotifier) {
            //this.soundNotifier = setInterval(this.soundNotify.bind(this), 15000)
        }
    }

    optionsBoxShow(e) {
        this.optionsBoxTriggerTarget.style.display = 'none'
        this.optionsBoxTarget.style.display = 'block'
    }

    optionsBoxHide(e) {
        this.optionsBoxTriggerTarget.style.display = 'block'
        this.optionsBoxTarget.style.display = 'none'
    }

    setStyle(event) {
        this.map.setStyle('mapbox://styles/' + event.target.value);
        this.updateObjects()
    }

    setStyleConfig(event) {
        const x = 'showPointOfInterestLabels'
        this.map.setConfigProperty('basemap', x, false);
    }

    async uploadKeys(e) {
        const keys = this.keysTarget.value
        if (!keys) {
            this.errorMessageTarget.className = 'alert alert-danger'
            this.errorMessageTarget.innerText = 'Where are the keys??'

            return
        }
        const response = await fetch('/maxfield/submit-user-keys/' + this.pathValue, {
            method: 'POST',
            body: JSON.stringify({
                // TODO proper agent number
                agentNum: 1,
                keys: keys,
            }),
            headers: {
                "Content-type": "application/json; charset=UTF-8"
            }
        });

        const data = await response.json()

        this.keysTarget.value = ''

        if (data['error']) {
            this.errorMessageTarget.className = 'alert alert-danger'
            this.errorMessageTarget.innerText = data['error']
        } else {
            this.loadFarmLayer2();

            this.modal.hide()

            this.errorMessageTarget.className = ''
            this.errorMessageTarget.innerText = ''
            Swal.fire(data['result']);
        }
    }

    setLayer(event) {
        this._clearLayers()
        const layer = event.target.value
        if (layer) {
            this._toggleLayer(layer, 'block');
        }

        if ('farm2' === layer) {
            this.btnUploadKeysTarget.style.display = 'block'
        } else {
            this.btnUploadKeysTarget.style.display = 'none'
        }
    }

    toggleCenter(event) {
        this.centerLocation = !this.centerLocation

        if (true === this.centerLocation) {
            event.currentTarget.classList.add('button-toggle-selected')
            event.currentTarget.classList.remove('button-toggle')
        } else {
            event.currentTarget.classList.remove('button-toggle-selected')
            event.currentTarget.classList.add('button-toggle')
        }
    }

    followHeading(event) {
        this.trackHeading = event.target.checked
    }

    toggleHeading(event) {
        this.trackHeading = !this.trackHeading
        this._toggleButtonClass(event.target, this.trackHeading)
    }

    rotate() {
        this.map.setBearing(this.map.getBearing() - 30)
    }

    showModal() {
        this.modal.show()
    }

    _clearLayers() {
        Object.keys(this.markers).forEach(function (key, index) {
            this._toggleLayer(key, 'none')
        }.bind(this))
    }

    _toggleLayer(name, value) {
        this.markers[name].forEach((e) => {
            e._element.style.display = value
        })
    }

    _removeLayer(name) {
        this.markers[name].forEach((e) => {
            e.remove()
            //e._element.style.display = value
        })
    }

    setDebug(event) {
        this.mapDebugTarget.style.display = event.target.checked ? 'block' : 'none'
    }

    toggleDebug(event) {
        let state = ('block' === this.mapDebugTarget.style.display || '' === this.mapDebugTarget.style.display)

        if (state) {
            this.mapDebugTarget.style.display = 'none'
        } else {
            this.mapDebugTarget.style.display = 'block'
        }
        state = !state
        this._toggleButtonClass(event.target, state)
    }

    _toggleButtonClass(button, state) {
        if (true === state) {
            button.classList.add('btn-info')
            button.classList.remove('btn-outline-info')
        } else {
            button.classList.remove('btn-info')
            button.classList.add('btn-outline-info')
        }
    }

    startDrag() {
        this.isBusy = true
        this.mapDebugTarget.innerText = this.isBusy
    }

    endDrag() {
        this.isBusy = false
        this.mapDebugTarget.innerText = this.isBusy
        return
        setTimeout(
            function () {
                this.isBusy = false
                this.mapDebugTarget.innerText = this.isBusy
            }.bind(this), 3000);
    }

    setProfile() {
        console.log(this.selProfileTarget.value)
    }
}