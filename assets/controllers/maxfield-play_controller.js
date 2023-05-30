import {Controller} from '@hotwired/stimulus'

import 'leaflet'
import 'leaflet/dist/leaflet.css'

import 'leaflet-fullscreen'
import 'leaflet-fullscreen/dist/leaflet.fullscreen.css'

import 'leaflet.locatecontrol'
import 'leaflet.locatecontrol/dist/L.Control.Locate.css'

import 'leaflet-routing-machine'
import 'leaflet-routing-machine/dist/leaflet-routing-machine.css'

import '../styles/map/play.css'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        jsonData: String,
    }

    maxfieldData = null

    farmLayer = L.featureGroup()
    linkLayer = L.layerGroup()
    links = []
    soundNotifier = null
    distance = 0

    map = null

    connect() {
        this.maxfieldData = JSON.parse(this.jsonDataValue)
        this.setupMap()
        this.displayMaxFieldData(this.maxfieldData)
    }

    displayMaxFieldData(maxField) {
        this.links = maxField.links

        this.loadFarmLayer(maxField.waypoints)
        this.loadLinkLayer()

        this.addLinkSelector()
    }

    setupMap() {
        const osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
        const osmAttrib = 'Map data (C) <a href="https://openstreetmap.org">OpenStreetMap</a> contributors'

        const mbAttr = 'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, ' +
                'Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
            mbUrl = 'https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token=pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpejY4NXVycTA2emYycXBndHRqcmZ3N3gifQ.rJcFIG214AriISLbB6B5aw'

        const
            streets2 = L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Tiles style by <a href="https://www.hotosm.org/" target="_blank">Humanitarian OpenStreetMap Team</a> hosted by <a href="https://openstreetmap.fr/" target="_blank">OpenStreetMap France</a>'
            }),

            streets3 = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)'
            }),

            streets = L.tileLayer('https://tiles.stadiamaps.com/tiles/outdoors/{z}/{x}/{y}{r}.png', {
                maxZoom: 20,
                attribution: '&copy; <a href="https://stadiamaps.com/">Stadia Maps</a>, &copy; <a href="https://openmaptiles.org/">OpenMapTiles</a> &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors'
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

            OSM = L.tileLayer(osmUrl, {attribution: osmAttrib})

        this.map = L.map('map', {
            center: [0, 0],
            zoom: 3,
            layers: [CartoDB_PositronNoLabels, this.farmLayer, this.linkLayer],
            fullscreenControl: true
        })

        const baseLayers = {
            'Streets': streets,
            'Streets2': streets2,
            'Streets3': streets3,
            'CartoDB': CartoDB_Positron,
            'CartoDB NoLabels': CartoDB_PositronNoLabels,
            'Sattelite': Esri_WorldImagery,
            'OSM': OSM
        }

        const overlays = {
            'Farm': this.farmLayer,
            'Links': this.linkLayer,
        }

        L.control.layers(baseLayers, overlays).addTo(this.map)

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

        // Locate control
        L.control.locate({
            keepCurrentZoomLevel: true,
            locateOptions: {
                enableHighAccuracy: true
            }
        }).addTo(this.map)

        this.map.on('locationfound', this.onLocationFound.bind(this))

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
            div.firstChild.onmousedown = div.firstChild.ondblclick = L.DomEvent.stopPropagation
            L.DomEvent.disableClickPropagation(div)
            return div
        }

        legend.addTo(this.map)
        document.getElementById('btnRoute')
            .addEventListener('click', this.enableRouting.bind(this), false)
        document.getElementById('btnSoundEnabled')
            .addEventListener('click', this.enableSound.bind(this), false)
    }

    loadFarmLayer(markerObjects) {
        this.farmLayer.clearLayers()

        markerObjects.forEach(function (o) {
            const num = o.description.replace('Farm keys:', '')
            const css = num > 3 ? 'circle farmalot' : 'circle'
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
                    html: '<b class="circle">' + num + '</b>'
                })
            })
                .bindPopup('<b>' + link.name + '</b><br/>' + description)
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
                + '<button id="btnNext">Next...</button><br />'
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

        document.getElementById('btnNext').addEventListener('click', () => {
            const select = document.getElementById('groupSelect')
            const length = select.length
            if (select.value < length - 2) {
                const newVal = parseInt(select.value) + 1
                this.showDestination(newVal)
                select.value = newVal
            } else {
                alert('Finished :)')
            }
        })
    }

    showDestination(id) {
        if (id < 0) {
            this.destinationMarker.setLatLng([0, 0])
                .bindPopup('')
            this.destination = null
            clearInterval(this.soundNotifier)
            this.soundNotifier = null

            return
        }

        const destination = this.links[id]
        this.destination = L.latLng(destination.lat, destination.lon)

        this.map.panTo(this.destination)

        let description = ''

        description += '<ol>'
        destination.links.forEach(link => {
            description += '<li>' + link + '</li>'
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
                if(this.distance < 100){
                    dist = 100 - this.distance
                    if(dist<20){
                        style = ' bg-success'
                    }else if(dist<50){
                        style = ' bg-warning'
                    }else{
                    style = ' bg-danger'
                }
                }

                let bar = '<div class="progress" role="progressbar" style="height: 20px">'
                +'<div class="progress-bar progress-bar-striped progress-bar-animated'+style+'" style="width: '+dist+'%"></div>'
              +'&nbsp;'+this.distance+' m</div>'
                document.getElementById('distanceBar').innerHTML=bar
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
