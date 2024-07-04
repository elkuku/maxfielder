import {Controller} from '@hotwired/stimulus'
import {Modal} from "bootstrap"

import '../styles/map/play2.css'

import mapboxgl from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'

import * as turf from '@turf/turf'
import Swal from "sweetalert2"

import MapboxAPI from '../lib/MapboxAPI.js'
import MapDataLoader from '../lib/MapDataLoader.js'
import MapObjects from '../lib/MapObjects.js'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        path: String,
        mapboxGlToken: String,
        defaultStyle: String,
        userId: Number,
        urls: Object,
    }

    static targets = [
        'keys', 'errorMessage', 'linkselect',
        'optionsBox', 'optionsBoxTrigger',
        'farmOptions', 'distanceBar',
        'btnModeFarm', 'btnModeLink',
        'selProfile',
        'messageBox', 'farmCounter'
    ]

    maxfieldData = null
    waypointIdMap = null

    links = []
    distance = 0
    location = null
    destination = null

    map = null
    modal = null

    bounds = null
    markers = {}

    trackHeading = false
    showDone = true
    centerLocation = false
    isFullscreen = false
    isBusy = false
    dragHandle = null
    /* 'farm' or 'link' */
    mode = ''

    optimizedRoutePoints = null
    proximityPoints = []

    userData = {}

    MapboxAPI = null
    MapDataLoader = null
    MapObjects = null

    async connect() {
        this.modal = new Modal('#exampleModal')
        this.optimizedRoutePoints = new Map()
        this.MapboxAPI = new MapboxAPI(this.mapboxGlTokenValue)
        this.MapDataLoader = new MapDataLoader(this.urlsValue, this.userIdValue);
        this.MapObjects = new MapObjects;

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
            this.map.addControl(this.getFarmCounter())
            this.map.addControl(this.getMessageBox())
            this.map.addControl(new mapboxgl.FullscreenControl(), 'top-left')
            this.map.addControl(new mapboxgl.NavigationControl({
                visualizePitch: true
            }), 'top-left')
            this.map.addControl(this.getZoomControl())
            this.map.addControl(this.getGeolocateControl(), 'bottom-right')
            this.map.addControl(this.getPlayControl(), 'bottom-left')
            this.map.addControl(this.getOptionsBox())

            this.loadFarmLayer()
            this.zoomAll()

            if (this.userData.current_point >= 0 && this.userData.current_point !== null) {
                // Link mode
                this.showDestination(this.userData.current_point)
                this.linkselectTarget.value = this.userData.current_point
                this._setMode('link')
            } else {
                this._setMode('farm')
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
        this.map.addLayer(this.MapObjects.getTrace())

        const circle = this.MapObjects.getCircle()
        circle.source.data = turf.circle([0, 0], .04)
        this.map.addLayer(circle)

        this.map.addSource('route', {
            type: 'geojson',
            data: turf.featureCollection([])
        })
        this.map.addLayer(this.MapObjects.getRoutlineActive())
        this.map.addLayer(this.MapObjects.getRouteArrows())
    }

    _clearProxiMarkers() {
        if (this.proximityPoints.length) {
            this.proximityPoints.forEach((point, index) => {
                this.map.removeLayer("proxipoint" + index)
                this.map.removeSource("proxipoint" + index)
            })
            this.proximityPoints = []
        }
    }

    updateObjects() {
        if (!this.location) {
            return
        }

        if ('farm' === this.mode) {
            this._clearProxiMarkers()
            this.maxfieldData.waypoints.forEach((point, index) => {
                if (false === this.userData.farm_done.includes(index)) {
                    const distance = turf.distance(this.location, [point.lon, point.lat]) * 1000
                    if (distance <= 40) {
                        this.proximityPoints.push(index)
                    }
                }
            })

            this.proximityPoints.forEach((point, index) => {
                const proxi = this.MapObjects.getCircle()
                proxi.id = 'proxipoint' + index
                proxi.source.data = turf.circle([this.maxfieldData.waypoints[point].lon, this.maxfieldData.waypoints[point].lat], .04)
                this.map.addLayer(proxi)
            })
        } else {
            this._clearProxiMarkers()
        }

        if (this.destination) {

            this.distance = turf.distance(this.location, this.destination).toFixed(3) * 1000;

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
                `<div class="progress progress-big" role="progressbar">
                   <div 
                   class="progress-bar progress-bar-striped progress-bar-animated${style}" 
                   style="width: ${dist}%"
                   >
                   </div>
                   &nbsp;${this.distance} m
                </div>`;
        } else {
            this.map.getSource('circle').setData(turf.circle([0, 0], .04));
            this.map.getSource('trace').setData(turf.lineString([[0, 0], [0, 0]]));
            this.distanceBarTarget.innerHTML = '';
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
            onAdd: () => {
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
            onAdd: () => {
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
            onAdd: () => {
                return document.getElementById('optionsBox')
            }, getDefaultPosition: () => {
                return 'top-right'
            }, onRemove: () => {
            }
        }
    }

    getZoomControl() {
        return {
            onAdd: () => {
                return document.getElementById('zoomBox')
            }, getDefaultPosition: () => {
                return 'bottom-right'
            }, onRemove: () => {
            }
        }
    }

    getMessageBox() {
        return {
            onAdd: () => {
                return this.messageBoxTarget
            }, getDefaultPosition: () => {
                return 'top-left'
            }, onRemove: () => {
            }
        }
    }

    getFarmCounter() {
        return {
            onAdd: () => {
                return this.farmCounterTarget
            }, getDefaultPosition: () => {
                return 'top-left'
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
        if ('link' === this.mode) {
            if (this.location) {
                const bounds = new mapboxgl.LngLatBounds()
                bounds.extend(this.location.geometry.coordinates)
                bounds.extend(this.destination.geometry.coordinates)
                this.map.fitBounds(bounds, {bearing: this.map.getBearing()});
                this.startDrag()
            } else {
                alert('Get location first')
            }
        } else {
            this.map.fitBounds(this.getBounds(), {padding: 100});
        }
    }

    async loadFarmLayer() {
        if (this.markers.farm && this.markers.farm.length) {
            this._removeLayer('farm')
        }
        this.markers.farm = [];
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
            const isDone = this.userData.farm_done.includes(cnt)
            el.className = 'farm-layer'
            el.className += isDone ? ' done' : ''
            el.innerHTML = `<b class="${css}">${numKeys ? numKeys : '-'}<span class="hasKeys">${hasKeys ? '&nbsp;' + hasKeys : ''}</span></b>`
            const popup = `
                <div class="float-end" style="border: 1px solid gray;padding: 5px;">
                <input type="checkbox" id="${id}chkDone"
                ${isDone ? 'checked' : ''}
                data-action="maxfield-play2#toggleDone"
                data-maxfield-play2-marker-param="${cnt}"
                > <label for="${id}chkDone">Done</label>
                </div>
                <img src="/waypoint_thumb/${this.waypointIdMap[cnt].guid}" width="60px" height="60px" alt="thumbnail image">
                <br>
                <b>${o.name}</b>
                <hr>
                ${o.description} ${hasKeys ? '(' + hasKeys + ')' : ''}${capsules}
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
            this.markers.farm.push(marker)
            cnt++
        }.bind(this))
    }

    toggleShowDone(event) {
        this.showDone = event.target.checked
        this._checkShowDone()
    }

    _checkShowDone() {
        this.markers.farm.forEach((e) => {
            if (e._element.classList.contains('done')) {
                e._element.style.display = this.showDone ? 'block' : 'none'
            }
        })
    }

    toggleDone(event) {
        const element = this.markers.farm[event.params.marker]
        if (event.target.checked) {
            element.addClassName('done')
            this.userData.farm_done.push(event.params.marker)
            const popup = document.getElementsByClassName('mapboxgl-popup');
            if (popup.length) {
                popup[0].remove();
            }
            if (false === this.showDone) {
                element._element.style.display = 'none'
            }
        } else {
            element.removeClassName('done')
            this.userData.farm_done = this.userData.farm_done.filter(item => item !== event.params.marker)
        }
        this.updateFarmCounter()
        this._uploadDone()
    }

    updateFarmCounter() {
        this.farmCounterTarget.innerText = this.markers.farm.length - this.userData.farm_done.length
    }

    async _uploadDone() {
        const response = await this.MapDataLoader.uploadUserData({
            farm_done: this.userData.farm_done
        })
        this._showResponse(response)
    }

    async _showResponse(response) {
        if (response.ok) {
            this._clearMessageBox()
        } else {
            try {
                const data = await response.json()
                if (data['error']) {
                    this.displayMessage(data['error'], 'danger')
                } else {
                    this.displayMessage('Error uploading user data', 'danger')

                }
            } catch (err) {
                this.displayMessage('Error parsing user data', 'danger')
                console.log('error', err)
            }
        }
    }

    _clearMessageBox() {
        this.messageBoxTarget.innerHTML = ''
    }

    displayMessage(message, type) {
        this.messageBoxTarget.innerHTML = message
        this.messageBoxTarget.removeAttribute('class')
        this.messageBoxTarget.classList.add('alert', 'alert-' + type)
    }

    async getRoute(event) {
        if (null === this.location) {
            this.displayMessage('Get location first...', 'warning')

            return
        }
        this._clearMessageBox()
        try {
            const route = await this.MapboxAPI.getRoute(
                this.location.geometry.coordinates,
                [event.params.lon, event.params.lat],
                this.selProfileTarget.value
            )
            console.log(route)
            const coordinates = route.geometry.coordinates

            this.map.getSource('route').setData(turf.lineString(coordinates))
        } catch (error) {
            this.displayMessage(error, 'danger')
        }


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

        const response = await this.MapDataLoader.uploadUserData({
            current_point: number
        })
        this._showResponse(response)
    }

    showDestination(id) {
        if (parseInt(id) === this.maxfieldData.links.length) {
            id = -1
        }
        if (id < 0) {
            this.destinationMarker.setLngLat([0, 0])
                .setPopup('')
            this.destination = null
            this._updateNextButtonText(id)

            return
        }

        const destination = this.maxfieldData.links[id]
        this.destination = turf.point([destination.lon, destination.lat])

        this.map.panTo(this.destination.geometry.coordinates)

        let description = ''

        description += '<ol>'
        destination.links.forEach(link => {
            description += '<li>'
                + `<img src="/waypoint_thumb/${this.waypointIdMap[link.num].guid}"`
                + 'width="60px" height="60px" alt="thumbnail image">' + '&nbsp;'
                + link.name + '</li>'
        })
        description += '</ol>'

        const popupContent =
            `<b>${destination.name}</b>
             <button 
                class="btn btn-sm btn-outline-info"
                data-action="maxfield-play2#getRoute"
                data-maxfield-play2-lat-param="${destination.lat}"
                data-maxfield-play2-lon-param="${destination.lon}"
             >Nav</button>
            <hr>${description}`

        const popup = new mapboxgl.Popup({offset: 25, maxWidth: '400px', markerId: id})
            .setHTML(popupContent)

        popup.on('open', (e) => {
            this._showLinkStar(e.target.options.markerId)
        })
        popup.on('close', (e) => {
            this._hideLinkStar()
        })
        const el = document.createElement('div')
        el.className = 'circle destinationMarker'
        el.innerHTML = (parseInt(id) + 1).toString()

        this.destinationMarker.remove()
        this.destinationMarker = new mapboxgl.Marker(el)
            .setLngLat(this.destination.geometry.coordinates)
            .setPopup(popup)
            .addTo(this.map)

        this._updateNextButtonText(id)
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
        const response = await this.MapDataLoader.uploadUserData({keys: keys})
        const data = await response.json()

        this.keysTarget.value = ''

        if (data['error']) {
            this.errorMessageTarget.className = 'alert alert-danger'
            this.errorMessageTarget.innerText = data['error']
        } else {
            await this._loadUserData()
            await this.loadFarmLayer()

            this.modal.hide()

            this.errorMessageTarget.className = ''
            this.errorMessageTarget.innerText = ''
            this.swal(data['result'])
        }
    }

    setMode({params: {mode}}) {
        this._setMode(mode)
    }

    _setMode(mode) {
        if ('farm' === mode) {
            this._toggleLayer('farm', 'block')
            this._checkShowDone()
            this.btnModeFarmTarget.checked = true
            this.farmOptionsTarget.style.display = ''
            this.farmCounterTarget.style.display = ''
        } else if ('link' === mode) {
            this._toggleLayer('farm', 'none')
            this.btnModeLinkTarget.checked = true
            this.farmOptionsTarget.style.display = 'none'
            this.farmCounterTarget.style.display = 'none'
        } else {
            alert('invalid mode');

            return
        }

        this.mode = mode
    }

    toggleCenter(event) {
        if (!this.location) {
            alert('Get location first')
            return
        }
        this.centerLocation = !this.centerLocation;

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
        this.map.easeTo({
            bearing: this.map.getBearing() - 30,
            duration: 300,
            easing: x => x
        });
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

    _toggleButtonClass(button, state, css = 'info') {
        if (true === state) {
            button.classList.add('btn-' + css)
            button.classList.remove('btn-outline-' + css)
        } else {
            button.classList.remove('btn-' + css)
            button.classList.add('btn-outline-' + css)
        }
    }

    startDrag() {
        this.isBusy = true
    }

    endDrag() {
        if (null !== this.dragHandle) {
            clearTimeout(this.dragHandle)
        }

        this.dragHandle = setTimeout(
            function () {
                this.isBusy = false
            }.bind(this),
            3000
        )
    }

    async _loadUserData() {
        const response = await this.MapDataLoader.getUserData()
        const data = await response.json();

        if (200 === response.status) {
            this.userData.keys = 'keys' in data ? data['keys'] : []
            this.userData.current_point = 'current_point' in data ? data['current_point'] : null
            this.userData.farm_done = 'farm_done' in data ? data['farm_done'] : []
        } else {
            alert(data)
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
        const response = await this.MapDataLoader.clearUserData()
        if (response.ok) {
            await this._loadUserData()
            await this.loadFarmLayer()
            setTimeout(function () {
                Swal.fire({
                    title: 'User data have been cleared!',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                })
            }, 500)
        } else {
            let error = 'An error occurred!'
            try {
                const data = await response.json();
                if (data['error']) {
                    error = data['error']
                }
            } catch (e) {
                error = 'Invalid JSON string received'
            }
            setTimeout(function () {
                Swal.fire({
                    title: error,
                    icon: 'error',
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

    _hideLinkStar() {
        if (this.map.getLayer('link-star')) {
            this.map.removeLayer('link-star');
            this.map.removeSource('link-star');
            this.markers.linkstar.forEach(marker => marker.remove())
        }
    }

    _showLinkStar(markerId) {
        const marker = this.maxfieldData.links[markerId]
        const center = [marker.lon, marker.lat]
        const lines = []
        this.markers.linkstar = []
        this.maxfieldData.links[markerId].links.forEach(link => {
            const point = this.maxfieldData.waypoints[link.num]
            const to = [point.lon, point.lat]
            lines.push(turf.lineString([center, to]))

            const el = document.createElement('div')
            el.style.backgroundImage = 'url(/waypoint_thumb/' + this.waypointIdMap[link.num].guid + ')'
            el.style.width = '50px'
            el.style.height = '50px'

            const marker = new mapboxgl.Marker(el)
                .setLngLat(to)
                .setPopup(new mapboxgl.Popup().setHTML('<b>' + point.name + '</b><br>' + point.description))
                .addTo(this.map)
            this.markers.linkstar.push(marker)
        })
        this.map.addSource('link-star', {
            'type': 'geojson',
            'data': turf.featureCollection(lines)
        });

        this.map.addLayer(this.MapObjects.linkStar());
    }
}
