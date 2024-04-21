import {Controller} from '@hotwired/stimulus';
import {Modal} from "bootstrap";

import '../styles/map/play2.css'

import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';

import Swal from "sweetalert2";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        path: String, jsonData: String, waypointIdMap: String, mapboxGlToken: String
    }

    static targets = [
        'keys', 'errorMessage', 'linkselect', 'mapDebug',
        'optionsBox', 'optionsBoxTrigger',
        'btnUploadKeys'
    ]

    className = 'maxfield-play2'

    maxfieldData = null
    waypointIdMap = null

    links = []
    soundNotifier = null
    distance = 0

    map = null
    modal = null

    bounds = null
    markers = {}

    trackHeading = false
    centerLocation = false;

    connect() {
        this.maxfieldData = JSON.parse(this.jsonDataValue)
        this.waypointIdMap = JSON.parse(this.waypointIdMapValue)
        this.modal = new Modal('#exampleModal')
        this.links = this.maxfieldData.links
        this.setupMap()
        //this.loadFarmLayer()
    }

    setupMap() {
        mapboxgl.accessToken = this.mapboxGlTokenValue;
        this.map = new mapboxgl.Map({
            container: 'map',
            center: [0, 0],
            zoom: 2,
        });

        //this.setStyleConfig('x')
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
        this.map.addControl(this.getOptionsBox())
        this.map.addControl(this.getPlayControl())

        this.loadFarmLayer()
        this.loadFarmLayer2()
        //this._clearLayers()
        //this._toggleLayer('farm', 'block')
        this.zoomAll()
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

                container.innerHTML = '<div class="info legend">' + '<button id="btnNext" data-action="maxfield-play2#nextLink">Start...</button><br />' + '<select id="groupSelect" class="form-control" data-maxfield-play2-target="linkselect" data-action="maxfield-play2#jumpToLink">' + linkList + '</select>'

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

        control.on('geolocate', (event) => {
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

    zoomIn() {
        this.map.setZoom(this.map.getZoom() + 1);
    }

    zoomOut() {
        this.map.setZoom(this.map.getZoom() - 1);
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
        let init = true
        if (this.markers.farm2) {
            this._clearLayers('farm2')
            init = false
        }
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

            const el = document.createElement('div');
            el.className = 'farm-layer'
            if (init) {
                el.style = "display:none";
            }
            el.innerHTML = `<b class="${css}">${numKeys}<span class="hasKeys">&nbsp;${hasKeys}</span></b>`
            const marker = new mapboxgl.Marker(el)
                .setLngLat([o.lon, o.lat])
                .setPopup(new mapboxgl.Popup().setHTML(`<b>${o.name}</b><br>${o.description} (${hasKeys})${capsules}`))
                .addTo(this.map)
            this.markers.farm2.push(marker)
            cnt++
        }.bind(this))
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
        this.destination = [destination.lon, destination.lat]

        this.map.panTo(this.destination)

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
            .setLngLat(this.destination)
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
        console.log(event)
        console.log(event.target.value)
        this.map.setStyle('mapbox://styles/mapbox/' + event.target.value);
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


    toggleLayer(event) {
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
        this._toggleButtonClass(event.target, this.centerLocation)
    }

    followHeading(event) {
        console.log(event)
        console.log(event.target.checked)
        this.trackHeading = event.target.checked
    }

    toggleHeading(event) {
        this.trackHeading = !this.trackHeading
        this._toggleButtonClass(event.target, this.trackHeading)
    }

    showModal() {
        this.modal.show()
    }

    _clearLayers() {
        console.log(this.markers)
        Object.keys(this.markers).forEach(function (key, index) {
            console.log(key)
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
        console.log(event)
        console.log(event.target.checked)
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
}
