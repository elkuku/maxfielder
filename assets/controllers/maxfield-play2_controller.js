import {Controller} from '@hotwired/stimulus'
import {Modal} from "bootstrap"

import '../styles/map/play2.css'

import mapboxgl from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'

import * as turf from '@turf/turf'
import Swal from "sweetalert2"

import MapboxAPI from '../lib/MapboxAPI.js'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        path: String,
        mapboxGlToken: String,
        urls: Object,
        defaultStyle: String,
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
    centerLocation = false
    isFullscreen = false
    isBusy = false
    dragHandle = null

    optimizedRoutePoints = null

    userData = {}

    MapboxApi = null

    connect() {
        this.modal = new Modal('#exampleModal')
        this.optimizedRoutePoints = new Map()
        this.MapboxAPI = new MapboxAPI(this.mapboxGlTokenValue)
        this.setupMap()
    }

    async _loadData() {
        const response = await fetch(this.urlsValue.get_data)
        const data = await response.json()

        this.maxfieldData = data.jsonData
        this.waypointIdMap = data.waypointIdMap
    }

    async setupMap() {
        try {
            await this._loadData()
            await this._loadUserData()
        } catch (error) {
            console.error(error)
            alert(error)
        }

        mapboxgl.accessToken = this.mapboxGlTokenValue
        this.map = new mapboxgl.Map({
            container: 'map',
            center: [this.maxfieldData.waypoints[0].lon, this.maxfieldData.waypoints[0].lat],
            zoom: 14,
        })

        this.map.setStyle('mapbox://styles/' + this.defaultStyleValue)

        this.map.on('dragstart', this.startDrag.bind(this))
        this.map.on('dragend', this.endDrag.bind(this))

        this.map.on('load', () => {
            const el = document.createElement('div')
            el.className = 'destinationMarker'
            el.innerHTML = 'O'

            this.destinationMarker = new mapboxgl.Marker(el)
                .setLngLat([0, 0])
                .addTo(this.map)

            this.map.addControl(this.getDistanceBar())
            this.map.addControl(new mapboxgl.FullscreenControl(), 'top-left')
            this.map.addControl(new mapboxgl.NavigationControl({
                visualizePitch: true
            }), 'top-left')
            this.map.addControl(this.getZoomControl())
            this.map.addControl(this.getGeolocateControl(), 'bottom-right')
            this.map.addControl(this.getPlayControl(), 'bottom-left')
            this.map.addControl(this.getOptionsBox())

            this.loadFarmLayer()
            this.loadFarmLayer2()
            this._clearLayers()
            this.zoomAll()

            if (this.userData.current_point >= 0) {
                this.showDestination(this.userData.current_point)
                this.linkselectTarget.value = this.userData.current_point
            } else {
                this._toggleLayer('farm2', 'block')
            }
        })

        this.map.on('style.load', () => {
            this.addObjects()
        })

        this.map.on('resize', () => {
            this.isFullscreen = !!document.fullscreenElement
        })
    }

    addObjects() {
        this.map.addSource('trace', {type: 'geojson', data: turf.lineString([[0, 0], [0, 0]])})
        this.map.addLayer({
            id: 'trace',
            type: 'line',
            source: 'trace',
            paint: {
                'line-color': 'yellow',
                'line-opacity': 0.75,
                'line-width': 5
            }
        })

        this.map.addLayer({
            id: "circle",
            type: "line",
            source: {
                "type": "geojson",
                "data": turf.circle([0, 0], .04),
                "lineMetrics": true,
            },
            paint: {
                "line-color": "red",
                "line-width": 10,
                "line-offset": 5,
                "line-dasharray": [1, 1]
            },
            layout: {}
        })

        this.map.addSource('route', {
            type: 'geojson',
            data: turf.featureCollection([])
        })

        this.map.addLayer({
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
        })

        this.map.addLayer({
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
        })
    }

    updateObjects() {
        if (this.location && this.destination) {

            this.distance = turf.distance(this.location, this.destination).toFixed(3) * 1000

            this.mapDebugTarget.innerText = this.distance

            this.map.getSource('trace').setData(turf.lineString([this.location.geometry.coordinates, this.destination.geometry.coordinates]))

            if (this.distance <= 50) {
                this.map.getSource('circle').setData(turf.circle(this.location, .04))
            } else {
                this.map.getSource('circle').setData(turf.circle([0, 0], .04))
            }

            let dist = 0
            let style = ''
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
                `<div class="progress progress-big" role="progressbar">
                   <div 
                   class="progress-bar progress-bar-striped progress-bar-animated${style}" 
                   style="width: ${dist}%"
                   >
                   </div>
                   &nbsp;${this.distance} m
                </div>`
        } else {
            this.map.getSource('circle').setData(turf.circle([0, 0], .04))
            this.map.getSource('trace').setData(turf.lineString([[0, 0], [0, 0]]))
            this.distanceBarTarget.innerHTML = ''
        }
    }

    getBounds() {
        if (!this.bounds) {
            this.bounds = new mapboxgl.LngLatBounds()

            this.maxfieldData.waypoints.forEach(function (o) {
                this.bounds.extend([o.lon, o.lat])
            }.bind(this))
        }

        return this.bounds
    }

    getPlayControl() {
        return {
            onAdd: (map) => {
                const container = document.createElement('div')
                container.classList.add('mapboxgl-ctrl')

                let linkList = '<option value="-1">Start...</option>'
                let num = 1
                this.maxfieldData.links.forEach(function (link, i) {
                    linkList += '<option value="' + i + '">' + num + ' - ' + link.name + '</option>'
                    num++
                })

                container.innerHTML = ''
                    //+'<div id="instructions"></div>'
                    + '<select id="groupSelect" class="form-control" data-maxfield-play2-target="linkselect" data-action="maxfield-play2#jumpToLink">'
                    + linkList
                    + '</select>'
                    + '<button class="btn btn-outline-success" id="btnNext" data-action="maxfield-play2#nextLink">Start...(' + this.maxfieldData.links.length + ')</button><br />'

                return container
            }, getDefaultPosition: () => {
                return 'bottom-left'
            }, onRemove: () => {
            }
        }
    }

    getDistanceBar() {
        return {
            onAdd: (map) => {
                const container = document.createElement('div')
                container.classList.add('mapboxgl-ctrl')
                container.innerHTML = '<div data-maxfield-play2-target="distanceBar" class="vw-100" id="distanceBar"></div>'
                return container
            }, getDefaultPosition: () => {
                return 'top-left'
            }, onRemove: () => {
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
        })

        control.on('geolocate', this.onLocationFound.bind(this))

        return control
    }

    onLocationFound(event) {
        this.location = turf.point([event.coords.longitude, event.coords.latitude])

        this.mapDebugTarget.innerText = this.isBusy

        if (this.centerLocation && false === this.isBusy) {
            this.map.flyTo({center: this.location.geometry.coordinates})
        }

        this.updateObjects()
    }

    zoomIn() {
        this.map.zoomIn()
    }

    zoomOut() {
        this.map.zoomOut()
    }

    zoomAll() {
        this.map.fitBounds(this.getBounds(), {padding: 100})
    }

    loadFarmLayer() {
        this.markers.farm = []
        this.maxfieldData.waypoints.forEach(function (o) {
            const num = o.keys
            let css = num > 3 ? 'circle farmalot' : 'circle'
            const el = document.createElement('div')
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
        if (this.markers.farm2 && this.markers.farm2.length) {
            this._removeLayer('farm2')
        }
        this.markers.farm2 = [];
        let cnt = 0
        this.maxfieldData.waypoints.forEach(function (o) {
            const numKeys = o.keys
            let css = numKeys > 3 ? 'circle farmalot' : 'circle'

            let hasKeys = 0
            let capsules = ''
            for (let i = 0; i < this.userData.keys.length; i++) {
                if (this.userData.keys[i].guid === this.waypointIdMap[cnt].guid) {
                    hasKeys = this.userData.keys[i].count
                    if (this.userData.keys[i].capsules) {
                        capsules = '<br><br>' + this.userData.keys[i].capsules
                    }
                    if (hasKeys >= numKeys) {
                        css += ' farm-done'
                    }
                }
            }

            const id = this.hashCode(o.lon.toString() + o.lat.toString())
            const el = document.createElement('div')
            el.className = 'farm-layer'
            el.className += this.userData.farm_done.includes(cnt) ? ' done' : ''
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
        if (event.target.checked) {
            element.addClassName('done')
            this.userData.farm_done.push(event.params.marker)
        } else {
            element.removeClassName('done')
            this.userData.farm_done = this.userData.farm_done.filter(item => item !== event.params.marker)
        }
        this._uploadDone()
    }

    async _uploadDone() {
        const response = await fetch(this.urlsValue.submit_user_data, {
            method: 'POST',
            body: JSON.stringify({
                // TODO proper agent number
                agentNum: 1,
                farm_done: this.userData.farm_done,
            }),
            headers: {
                "Content-type": "application/json; charset=UTF-8"
            }
        })

        const data = await response.json()

        console.log(data)

        if (data['error']) {
            alert(data['error'])
        }
    }

    async getRoute(event) {
        if (null === this.location) {
            alert('Get location first...')

            return
        }
        const route = await this.MapboxAPI.getRoute(
            this.location.geometry.coordinates,
            [event.params.lon, event.params.lat],
            this.selProfileTarget.value
        )

        const coordinates = route.geometry.coordinates

        this.map.getSource('route').setData(turf.lineString(coordinates))

        return
        const instructions = document.getElementById('instructions')
        const steps = route.legs[0].steps

        let tripInstructions = ''
        for (const step of steps) {
            tripInstructions += `<li>${step.maneuver.instruction}</li>`
        }
        instructions.innerHTML = `<p><strong>Trip duration: ${Math.floor(
            route.duration / 60
        )} min 🚴 </strong></p><ol>${tripInstructions}</ol>`
    }

    async toggleRoutePoint(event) {
        const point = [event.params.lon, event.params.lat]
        const id = event.params.id

        if (event.target.checked) {
            this.optimizedRoutePoints.set(id, point)
        } else {
            this.optimizedRoutePoints.delete(id)
        }

        const result = await this._displayOptimizedRoute()

        if ('error' === result) {
            event.target.checked = false
            this.optimizedRoutePoints.delete(id)
        }
    }

    async _displayOptimizedRoute() {
        if (this.optimizedRoutePoints.size < 2) {
            return
        }

        const response = await this.MapboxAPI.getOptimizedRoute(this.optimizedRoutePoints.values(), this.selProfileTarget.value)

        if (response.code !== 'Ok') {
            const handleMessage =
                response.code === 'InvalidInput'
                    ? 'Refresh to start a new route. For more information: https://docs.mapbox.com/api/navigation/optimization/#optimization-api-errors'
                    : 'Try a different point.'
            alert(`${response.code} - ${response.message}\n\n${handleMessage}`)
            return 'error'
        } else {
            const routeGeoJSON = turf.featureCollection([
                turf.feature(response.trips[0].geometry)
            ])
            this.map.getSource('route').setData(routeGeoJSON)
        }

        return 'ok'
    }

    hashCode(str) {
        // https://stackoverflow.com/posts/7616484/revisions
        let hash = 0,
            i, chr
        for (i = 0; i < str.length; i++) {
            chr = str.charCodeAt(i)
            hash = ((hash << 5) - hash) + chr
            hash |= 0 // Convert to 32bit integer
        }
        return hash
    }

    async nextLink(event) {
        const length = this.linkselectTarget.length
        const newVal = parseInt(this.linkselectTarget.value) + 1

        if (newVal < length - 1) {
            this.showDestination(newVal)
            this.map.getSource('route').setData(turf.lineString([[0, 0], [0, 0]]))
            this.linkselectTarget.value = newVal
        } else {
            this.swal('Finished :)')
        }

        this._updateNextButtonText(newVal)
        await this._uploadCurrentPoint(newVal)
    }

    jumpToLink(e) {
        this._uploadCurrentPoint(this.linkselectTarget.value)
        this.showDestination(this.linkselectTarget.value)
    }

    async _uploadCurrentPoint(number) {
        const response = await fetch(this.urlsValue.submit_user_data, {
            method: 'POST',
            body: JSON.stringify({
                // TODO proper agent number
                agentNum: 1,
                current_point: number,
            }),
            headers: {
                "Content-type": "application/json; charset=UTF-8"
            }
        })

        const data = await response.json()

        console.log(data)

        if (data['error']) {
            alert(data['error'])
        }
    }

    showDestination(id) {
        if (parseInt(id) === this.maxfieldData.links.length) {
            id = -1
        }
        if (id < 0) {
            this.destinationMarker.setLngLat([0, 0])
                .setPopup('')
            this.destination = null
            clearInterval(this.soundNotifier)
            this.soundNotifier = null
            this._updateNextButtonText(id)

            return
        }

        const destination = this.maxfieldData.links[id]
        this.destination = turf.point([destination.lon, destination.lat])

        this.map.panTo(this.destination.geometry.coordinates)

        let description = ''

        description += '<ol>'
        destination.links.forEach(link => {
            description += '<li>' + `<img src="/waypoint_thumb/${this.waypointIdMap[link.num].guid}"` + 'width="60px" height="60px" alt="thumbnail image">' + '&nbsp;' + link.name + '</li>'
        })
        description += '</ol>'

        const popup =
            `<b>${destination.name}</b>
             <button 
                class="btn btn-sm btn-outline-info"
                data-action="maxfield-play2#getRoute"
                data-maxfield-play2-lat-param="${destination.lat}"
                data-maxfield-play2-lon-param="${destination.lon}"
             >Nav</button>
            <hr>${description}`

        const el = document.createElement('div')
        el.className = 'circle destinationMarker'
        el.innerHTML = (parseInt(id) + 1).toString()

        this.destinationMarker.remove()
        this.destinationMarker = new mapboxgl.Marker(el)
            .setLngLat(this.destination.geometry.coordinates)
            .setPopup(new mapboxgl.Popup({offset: 25, maxWidth: '400px'}) // add popups
                .setHTML(popup))
            .addTo(this.map)

        this._updateNextButtonText(id)

        // Routing
        if (id > 0) {
            /*
            const previous = this.maxfieldData.links[id - 1]
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

    optionsBoxShow(event) {
        this.optionsBoxTriggerTarget.style.display = 'none'
        this.optionsBoxTarget.style.display = 'block'
    }

    optionsBoxHide(event) {
        this.optionsBoxTriggerTarget.style.display = 'block'
        this.optionsBoxTarget.style.display = 'none'
    }

    setStyle(event) {
        this.map.setStyle('mapbox://styles/' + event.target.value)
        this.updateObjects()
    }

    setStyleConfig(event) {
        const x = 'showPointOfInterestLabels'
        this.map.setConfigProperty('basemap', x, false)
    }

    async uploadKeys(event) {
        const keys = this.keysTarget.value
        if (!keys) {
            this.errorMessageTarget.className = 'alert alert-danger'
            this.errorMessageTarget.innerText = 'Where are the keys??'

            return
        }
        const response = await fetch(this.urlsValue.submit_user_data, {
            method: 'POST',
            body: JSON.stringify({
                // TODO proper agent number
                agentNum: 1,
                keys: keys,
            }),
            headers: {
                "Content-type": "application/json; charset=UTF-8"
            }
        })

        const data = await response.json()

        this.keysTarget.value = ''

        if (data['error']) {
            this.errorMessageTarget.className = 'alert alert-danger'
            this.errorMessageTarget.innerText = data['error']
        } else {
            await this._loadUserData()
            this.loadFarmLayer2()

            this.modal.hide()

            this.errorMessageTarget.className = ''
            this.errorMessageTarget.innerText = ''
            this.swal(data['result'])
        }
    }

    setLayer(event) {
        this._clearLayers()
        const layer = event.target.value
        if (layer) {
            this._toggleLayer(layer, 'block')
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
        if (this.isFullscreen) {
            document.exitFullscreen()
        }
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
        if (null !== this.dragHandle) {
            clearTimeout(this.dragHandle)
        }

        this.dragHandle = setTimeout(
            function () {
                this.isBusy = false
                this.mapDebugTarget.innerText = this.isBusy
            }.bind(this),
            3000
        )
    }

    setProfile() {
        console.log(this.selProfileTarget.value)
    }

    async _loadUserData() {
        const response = await fetch(this.urlsValue.get_user_data)
        const data = await response.json()

        // TODO Select proper user
        if (data && 1 in data) {
            this.userData.keys = 'keys' in data[1] ? data[1]['keys'] : []
            this.userData.current_point = 'current_point' in data[1] ? data[1]['current_point'] : null
            this.userData.farm_done = 'farm_done' in data[1] ? data[1]['farm_done'] : []
        } else {
            this.userData.keys = []
            this.userData.current_point = null
            this.userData.farm_done = []
        }
    }

    async clearUserData() {
        Swal.fire({
            title: "Clearing...",
            text: "Please wait",
            imageUrl: "/build/images/loading.gif",
            showConfirmButton: false,
            allowOutsideClick: false
        })
        const response = await fetch(this.urlsValue.clear_user_data, {
            method: 'POST',
            body: JSON.stringify({
                // TODO proper agent number
                agentNum: 1,
            }),
            headers: {
                "Content-type": "application/json; charset=UTF-8"
            }
        })

        const data = await response.json()

        console.log(data)

        if (data['error']) {
            alert(data['error'])
        } else {
            await this._loadUserData()
            await this.loadFarmLayer2()
            setTimeout(function() {
            Swal.fire({
                title: 'User data have been cleared!',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            })
            }, 500)
        }
    }

    swal(message) {
        if (this.isFullscreen) {
            document.exitFullscreen()
        }
        Swal.fire(message)
    }

    _updateNextButtonText(newVal) {
        const element = document.getElementById('btnNext')

        if (newVal < 0) {
            element.innerText = 'Start...';
        } else {
            const length = this.linkselectTarget.length
            const missing = length - newVal - 2
            if (-1 === missing) {
                element.innerText = 'Finished!'
            } else if (0 === missing) {
                element.innerText = "LAST!!!";
            } else {
                element.innerText = `Next (${length - newVal - 2})`
            }
        }
    }
}
