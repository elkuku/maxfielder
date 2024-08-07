import {Controller} from '@hotwired/stimulus'

import 'leaflet'
import 'leaflet/dist/leaflet.css'

import 'leaflet-fullscreen'
import 'leaflet-fullscreen/dist/leaflet.fullscreen.css'

import 'leaflet.locatecontrol'
import 'leaflet.locatecontrol/dist/L.Control.Locate.css'

import 'leaflet-routing-machine'
import 'leaflet-routing-machine/dist/leaflet-routing-machine.css'

import {Modal} from "bootstrap";

import Swal from 'sweetalert2'

import '../styles/map/play.css'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        path: String,
        jsonData: String,
        waypointIdMap: String
    }

    static targets = ['keys', 'errorMessage']

    maxfieldData = null
    waypointIdMap = null

    farmLayer = L.featureGroup()
    farmLayer2 = L.featureGroup()
    linkLayer = L.layerGroup()
    links = []
    soundNotifier = null
    distance = 0

    map = null

    modal = null

    connect() {
        this.maxfieldData = JSON.parse(this.jsonDataValue)
        this.waypointIdMap = JSON.parse(this.waypointIdMapValue)
        this.modal = new Modal('#exampleModal')
        this.links = this.maxfieldData.links
        this.setupMap()
        this.displayMaxFieldData()
    }

    displayMaxFieldData() {
        this.loadFarmLayer()
        this.loadFarmLayer2()
        this.loadLinkLayer()

        this.addLinkSelector()
    }

    setupMap() {
        const osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
        const osmAttrib = 'Map data (C) <a href="https://openstreetmap.org">OpenStreetMap</a> contributors'

        const
            streets2 = L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Tiles style by <a href="https://www.hotosm.org/" target="_blank">Humanitarian OpenStreetMap Team</a> hosted by <a href="https://openstreetmap.fr/" target="_blank">OpenStreetMap France</a>'
            }),

            streets3 = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="https://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)'
            }),

            streets = L.tileLayer('https://tiles.stadiamaps.com/tiles/outdoors/{z}/{x}/{y}{r}.png', {
                maxZoom: 20,
                attribution: '&copy; <a href="https://stadiamaps.com/">Stadia Maps</a>, &copy; <a href="https://openmaptiles.org/">OpenMapTiles</a> &copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors'
            }),

            Esri_WorldImagery = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
            }),

            CartoDB_Positron = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                subdomains: 'abcd',
                maxZoom: 20
            }),

            CartoDB_PositronNoLabels = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                subdomains: 'abcd',
                maxZoom: 20
            }),

            OSM = L.tileLayer(osmUrl, {attribution: osmAttrib}),

            Stadia_AlidadeSmoothDark = L.tileLayer('https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}{r}.{ext}', {
                minZoom: 0,
                maxZoom: 20,
                attribution: '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                ext: 'png'
            }),

            CartoDB_DarkMatterNoLabels = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                subdomains: 'abcd',
                maxZoom: 20
            });

        this.map = L.map('map', {
            center: [0, 0],
            zoom: 3,
            layers: [CartoDB_PositronNoLabels, this.farmLayer],
            fullscreenControl: true,
            zoomControl: false
        })

        const baseLayers = {
            'Streets': streets,
            'Streets2': streets2,
            'Streets3': streets3,
            'CartoDB': CartoDB_Positron,
            'CartoDB NoLabels': CartoDB_PositronNoLabels,
            'CartoDB DarkMatter NoLabels': CartoDB_DarkMatterNoLabels,
            'Stadia Dark': Stadia_AlidadeSmoothDark,
            'Sattelite': Esri_WorldImagery,
            'OSM': OSM
        }

        const overlays = {
            'Farm': this.farmLayer,
          //  'Farm2': this.farmLayer2,
            'Links': this.linkLayer,
        }

        L.control.layers(baseLayers, overlays).addTo(this.map)

        const zoomControl = L.control({position: 'bottomright'})

        zoomControl.onAdd = function () {
            let div = L.DomUtil.create('div')
            div.innerHTML =
                '<div class="circleBig zoomButton" href="#" data-action="click->maxfield-play#zoomIn">+</div>' +
                '<div class="circleBig zoomButton" href="#" data-action="click->maxfield-play#zoomOut">-</div>' +
                '<div class="circleBig zoomButton" href="#" data-action="click->maxfield-play#zoomAll">A</div>'

            return div
        }

        zoomControl.addTo(this.map)

        // Locate control
        L.control.locate({
            keepCurrentZoomLevel: true,
            position: 'bottomright',
            locateOptions: {
                enableHighAccuracy: true
            }
        }).addTo(this.map)

        this.map.on('locationfound', this.onLocationFound.bind(this))

        this.linkSelector = L.control({position: 'bottomleft'})
        this.distanceBar = L.control({position: 'bottomleft'})

        this.distanceBar.onAdd = function () {
            let div = L.DomUtil.create('div')
            div.innerHTML = '<div id="distanceBar" class="vw-100"></div>'

            return div
        }

        this.distanceBar.addTo(this.map)

        this.destinationMarker = L.marker([0, 0])
            .bindPopup('Please load a GPX file...')
            .setIcon(
                L.divIcon({
                    html: '<b>Please load a GPX file...</b>'
                })
            )
            .addTo(this.map)

        this.userDestinationLine = L.polyline([], {
            color: 'blue',
            dashArray: '5, 15',
        }).addTo(this.map)

        this.userDistanceMarker = L.marker([0, 0],
            {
                icon: L.divIcon({
                    className: 'user-distance',
                    html: '<b class="circle">123m</b>'
                })
            })
            .addTo(this.map)

        this.originDestinationLine = L.polyline([], {
            color: 'red',
            dashArray: '5, 15',
        }).addTo(this.map)

        // Routing control
        this.routingControl = L.Routing.control({
            fitSelectedRoutes: false,
            createMarker: function () {
                return false
            }
        })

        this.routingEnabled = false
        this.soundEnabled = true

        this.addButtons()
    }

    addButtons() {
        const legend = L.control({position: 'topleft'})
        legend.onAdd = function () {
            let div = L.DomUtil.create('div', 'leaflet-bar')
            div.innerHTML =
                '<a id="btnRoute" title="Routing">R</a>'
                + '<a id="btnSoundEnabled" class="routing-enabled" title="Sound">S</a>'
                + '<a id="btnUploadKeys" class="routing-enabled" title="Keys">K</a>'
            div.firstChild.onmousedown = div.firstChild.ondblclick = L.DomEvent.stopPropagation
            L.DomEvent.disableClickPropagation(div)
            return div
        }

        legend.addTo(this.map)
        document.getElementById('btnRoute')
            .addEventListener('click', this.enableRouting.bind(this), false)
        document.getElementById('btnSoundEnabled')
            .addEventListener('click', this.enableSound.bind(this), false)
        document.getElementById('btnUploadKeys')
            .addEventListener('click', this.showModal.bind(this), false)
    }

    loadFarmLayer() {
        this.farmLayer.clearLayers()

        this.maxfieldData.waypoints.forEach(function (o) {
            const num = o.description.replace('Farm keys: ', '')
            let css = num > 3 ? 'circle farmalot' : 'circle'
            let marker =
                L.marker(
                    L.latLng(o.lat, o.lon),
                    {
                        icon: L.divIcon({
                            className: 'farm-layer',
                            html: '<b class="' + css + '">' + num + '</b>'
                        })
                    }
                ).bindPopup('<b>' + o.name + '</b><br>' + o.description)

            this.farmLayer.addLayer(marker)
        }.bind(this))

        this.map.fitBounds(this.farmLayer.getBounds())
    }

    async loadFarmLayer2() {
        return
        this.farmLayer2.clearLayers()

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
                        capsules = '<br><br>'+userKeys[i].capsules
                    }
                    if (hasKeys >= numKeys) {
                        css += ' farm-done';
                    }
                }
            }
            let marker =
                L.marker(
                    L.latLng(o.lat, o.lon),
                    {
                        icon: L.divIcon({
                            className: 'farm-layer',
                            html: `<b class="${css}">${numKeys}<span class="hasKeys">&nbsp;${hasKeys}</span></b>`
                        })
                    }
                ).bindPopup(`<b>${o.name}</b><br>${o.description} (${hasKeys})${capsules}`);
            this.farmLayer2.addLayer(marker)
            cnt++
        }.bind(this))
    }

    loadLinkLayer() {
        let pointList = []
        let num = 1
        let description = ''
        this.linkLayer.clearLayers()
        this.links.forEach(function (link) {
            pointList.push(L.latLng(link.lat, link.lon))
            if (link.links) {
                description = ''

                description += '<ol>'
                link.links.forEach(link => {
                    description += '<li>' + link + '</li>'
                })
                description += '</ol>'

            } else {
                description = link.description.replace(/\*BR\*/g, '<br/>')
            }
            L.marker([link.lat, link.lon], {
                icon: L.divIcon({
                    className: 'link-layer',
                    html: `<b class="circle">${num}</b>`
                })
            })
                .bindPopup(`<b>${link.name}</b><br/>${description}`)
                .addTo(this.linkLayer)
            num++
        }.bind(this))

        L.polyline(pointList, {color: 'blue'}).addTo(this.linkLayer)
    }

    addLinkSelector() {
        if (this.map.hasLayer(this.linkSelector)) {
            this.map.removeLayer(this.linkSelector)
        }

        let linkList = '<option value="-1">Start...</option>'
        let num = 1
        this.links.forEach(function (link, i) {
            linkList += '<option value="' + i + '">' + num + ' - ' + link.name + '</option>'
            num++
        })

        this.linkSelector.onAdd = function () {
            let div = L.DomUtil.create('div', 'info legend')
            div.innerHTML = ''
                + '<button id="btnNext">Start...</button><br />'
                + '<select id="groupSelect">'
                + linkList
                + '</select>'
            div.firstChild.onmousedown = div.firstChild.ondblclick = L.DomEvent.stopPropagation
            L.DomEvent.disableClickPropagation(div)
            return div
        }

        this.linkSelector.addTo(this.map)

        document.getElementById('groupSelect').addEventListener('change', (event) => {
            this.showDestination(event.target.value)
        })

        document.getElementById('btnNext').addEventListener('click', (e) => {
            const select = document.getElementById('groupSelect')
            const length = select.length
            if (select.value < length - 2) {
                e.target.innerText = 'Next'
                const newVal = parseInt(select.value) + 1
                this.showDestination(newVal)
                select.value = newVal
            } else {
                e.target.innerText = 'Finished!'

                Swal.fire('Finished :)');
            }
        })
    }

    zoomIn() {
        this.map.setZoom(this.map.getZoom() + 1);
    }

    zoomOut() {
        this.map.setZoom(this.map.getZoom() - 1);
    }

    zoomAll() {
        this.map.fitBounds(this.farmLayer.getBounds())
    }

    showDestination(id) {
        if (id < 0) {
            this.destinationMarker.setLatLng([0, 0])
                .bindPopup('')
            this.destination = null
            clearInterval(this.soundNotifier)
            this.soundNotifier = null

            document.getElementById('btnNext').innerText = 'Start...'

            return
        }

        document.getElementById('btnNext').innerText = 'Next'

        const destination = this.links[id]
        this.destination = L.latLng(destination.lat, destination.lon)

        this.map.panTo(this.destination)

        let description = ''

        description += '<ol>'
        destination.links.forEach(link => {
            description +=
                '<li>'
                + `<img src="/waypoint_thumb/${this.waypointIdMap[link.num].guid}"`
                + 'width="60px" height="60px" alt="thumbnail image">'
                + '&nbsp;' + link.name
                + '</li>'
        })
        description += '</ol>'

        this.destinationMarker.setLatLng(this.destination)
            .bindPopup('<b>' + destination.name + '</b><hr>' + description)
            .setIcon(
                L.divIcon({
                    html: '<b class="circle circle-dest">' + (parseInt(id) + 1) + '</b>'
                })
            )

        // Routing
        if (id > 0) {
            const previous = this.links[id - 1]
            const points = [
                L.latLng(previous.lat, previous.lon),
                L.latLng(destination.lat, destination.lon)
            ]
            this.originDestinationLine.setLatLngs(points)
            if (this.routingEnabled) {
                this.routingControl.setWaypoints(points)
            }
        }

        // Sound
        if (!this.soundNotifier) {
            this.soundNotifier = setInterval(this.soundNotify.bind(this), 15000)
        }
    }

    onLocationFound(e) {
        if (this.destination) {
            this.distance = e.latlng.distanceTo(this.destination).toFixed(0)
            this.userDestinationLine.setLatLngs([e.latlng, this.destination])
            this.userDistanceMarker
                .setLatLng(e.latlng)
                .setIcon(L.divIcon({
                    className: 'user-distance',
                    html: '<b class="circle">' + this.distance + 'm</b>'
                }))

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

            document.getElementById('distanceBar').innerHTML = '<div class="progress" role="progressbar" style="height: 20px">'
                + '<div class="progress-bar progress-bar-striped progress-bar-animated' + style + '" style="width: ' + dist + '%"></div>'
                + '&nbsp;' + this.distance + ' m</div>'
        }
    }

    enableRouting(e) {
        if (this.routingEnabled) {
            this.routingEnabled = false
            e.target.classList.remove('routing-enabled')
            this.routingControl.remove()
        } else {
            this.routingEnabled = true
            e.target.classList.add('routing-enabled')
            this.routingControl.addTo(this.map)
        }
    }

    enableSound(e) {
        if (this.soundEnabled) {
            this.soundEnabled = false
            e.target.classList.remove('routing-enabled')
        } else {
            this.soundEnabled = true
            e.target.classList.add('routing-enabled')
        }
    }

    showModal() {
        this.modal.show()
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
            await this.loadFarmLayer2();

            this.modal.hide()

            this.errorMessageTarget.className = ''
            this.errorMessageTarget.innerText = ''
            Swal.fire(data['result']);
        }
    }

    soundNotify() {
        if (0 === this.distance) {
            return
        }

        if (!this.soundEnabled) {
            return
        }

        if (this.distance >= 200) {
            this.playSound('/sounds/echo_1.mp3')
        } else if (this.distance >= 100) {
            this.playSound('/sounds/echo_2.mp3')
        } else if (this.distance >= 40) {
            this.playSound('/sounds/echo_3.mp3')
        } else {
            this.playSound('/sounds/portal_in_range.mp3')
        }
    }

    playSound(url) {
        const audio = new Audio(url)
        audio.play()
    }
}
