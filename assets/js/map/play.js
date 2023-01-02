import $ from 'jquery'

import 'leaflet'
import 'leaflet/dist/leaflet.css'

import 'leaflet-fullscreen'
import 'leaflet-fullscreen/dist/leaflet.fullscreen.css'

import 'leaflet.locatecontrol'
import 'leaflet.locatecontrol/dist/L.Control.Locate.css'

import 'leaflet-routing-machine'
import 'leaflet-routing-machine/dist/leaflet-routing-machine.css'

import '../../styles/map/play.css'

class Map {
    constructor(mapId, centerLat = 0, centerLon = 0, zoom = 3) {
        const osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
        const osmAttrib = 'Map data (C) <a href="https://openstreetmap.org">OpenStreetMap</a> contributors'

        const mbAttr = 'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, ' +
                'Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
            mbUrl = 'https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token=pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpejY4NXVycTA2emYycXBndHRqcmZ3N3gifQ.rJcFIG214AriISLbB6B5aw';

        const grayscale = L.tileLayer(mbUrl, {
                id: 'mapbox/light-v9',
                tileSize: 512,
                zoomOffset: -1,
                attribution: mbAttr
            }),
            streets = L.tileLayer(mbUrl, {
                id: 'mapbox/streets-v11',
                tileSize: 512,
                zoomOffset: -1,
                attribution: mbAttr
            }),
            OSM = L.tileLayer(osmUrl, {attribution: osmAttrib});

        this.farmLayer = L.featureGroup()
        this.linkLayer = L.layerGroup()
        this.links = []
        this.soundNotifier = null
        this.distance = 0

        this.map = L.map(mapId, {
            center: [centerLat, centerLon],
            zoom: zoom,
            layers: [grayscale, this.farmLayer, this.linkLayer],
            fullscreenControl: true
        });

        const baseLayers = {
            "Grayscale": grayscale,
            "Streets": streets,
            "OSM": OSM
        };

        const overlays = {
            "Farm": this.farmLayer,
            "Links": this.linkLayer,
        };

        L.control.layers(baseLayers, overlays).addTo(this.map);

        this.linkSelector = L.control({position: 'bottomleft'})

        // this.linkSelector.addTo(this.map)

        this.destinationMarker = L.marker([0, 0])
            .bindPopup('Please load a GPX file...')
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
        }).addTo(this.map);

        this.map.on('locationfound', this.onLocationFound.bind(this));

        // Routing control
        this.routingControl = L.Routing.control({
            fitSelectedRoutes: false,
            createMarker: function () {
                return false
            }
        })
            // .addTo(this.map);

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
            .addEventListener('click', this.enableRouting.bind(this), false);
        document.getElementById('btnSoundEnabled')
            .addEventListener('click', this.enableSound.bind(this), false);
    }

    enableRouting(e) {
        if (this.routingEnabled) {
            this.routingEnabled = false
            $(e.target).removeClass('routing-enabled')
            this.routingControl.remove()
        } else {
            this.routingEnabled = true
            $(e.target).addClass('routing-enabled')
            this.routingControl.addTo(this.map)
        }
    }

    enableSound(e) {
        if (this.soundEnabled) {
            this.soundEnabled = false
            $(e.target).removeClass('routing-enabled')
        } else {
            this.soundEnabled = true
            $(e.target).addClass('routing-enabled')
        }
    }

    parseGpx(contents) {
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(contents, "text/xml");

        const wpts = xmlDoc.getElementsByTagName('wpt');
        const trackpoints = xmlDoc.getElementsByTagName('rtept');

        const waypoints = []
        const track = []

        for (let i = 0; i < wpts.length; i++) {
            waypoints.push({
                lat: wpts[i].getAttribute("lat"),
                lon: wpts[i].getAttribute("lon"),
                name: wpts[i].getElementsByTagName('name')[0].innerHTML,
                description: wpts[i].getElementsByTagName('desc')[0].innerHTML,
            })
        }

        for (let i = 0; i < trackpoints.length; i++) {
            track.push({
                lat: trackpoints[i].getAttribute("lat"),
                lon: trackpoints[i].getAttribute("lon"),
                name: trackpoints[i].getElementsByTagName('name')[0].innerHTML,
                description: trackpoints[i].getElementsByTagName('desc')[0].innerHTML,
            })
        }

        const maxfield = {
            waypoints: waypoints,
            links: track,
        }

        this.displayMaxFieldData(maxfield)
    }

    displayMaxFieldData(maxField) {
        this.links = maxField.links

        this.loadFarmLayer(maxField.waypoints)
        this.loadLinkLayer()

        this.addLinkSelector()
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

        this.map.fitBounds(this.farmLayer.getBounds());
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
                link.links.forEach(link => { description += '<li>'+link+'</li>' });
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
                .addTo(this.linkLayer);
            num++
        }.bind(this))

        L.polyline(pointList, {color: 'blue'}).addTo(this.linkLayer);
    }

    addLinkSelector() {
        if (this.map.hasLayer(this.linkSelector)) {
            this.map.removeLayer(this.linkSelector);
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

        $('#groupSelect')
            .on('change', function (e) {
                this.showDestination($(e.target).val())
            }.bind(this))

        $('#btnNext').on('click', function () {
            const select = $('#groupSelect')
            const length = $('#groupSelect option').length
            if (select.val() < length - 2) {
                const newVal = parseInt(select.val()) + 1;
                this.showDestination(newVal);

                select.val(newVal);
            } else {
                alert('Finished :)')
            }
        }.bind(this))
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

        const destination = this.links[id];
        this.destination = L.latLng(destination.lat, destination.lon)

        this.map.panTo(this.destination)

        let description = ''

        description += '<ol>'
        destination.links.forEach(link => { description += '<li>'+link+'</li>' });
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
                this.routingControl.setWaypoints(points);
            }
        }

        // Sound
        // this.soundNotify()
        if (!this.soundNotifier) {
            this.soundNotifier = setInterval(this.soundNotify.bind(this), 15000)
        }
    }

    onLocationFound(e) {
        if (this.destination) {
            this.distance = e.latlng.distanceTo(this.destination).toFixed(1)
            this.userDestinationLine.setLatLngs([e.latlng, this.destination])
            this.userDistanceMarker
                .setLatLng(e.latlng)
                .setIcon(L.divIcon({
                    className: 'user-distance',
                    html: '<b class="circle">' + this.distance + 'm</b>'
                }))
        }
    }

    soundNotify(){
        if (0 === this.distance) {
            return
        }

        if (!this.soundEnabled) {
            return
        }

        if (this.distance >= 200) {
            this.playLong()
        } else if (this.distance >= 100) {
            this.playMid()
        } else if (this.distance >= 20) {
            this.playShort()
        } else {
            this.playPortalInRange()
        }

        console.log(this.distance)
    }

    playShort(){
        this.playSound('/sounds/echo_3.mp3')
    }
    playMid(){
        this.playSound('/sounds/echo_2.mp3')
    }
    playLong(){
        this.playSound('/sounds/echo_1.mp3')
    }
    playPortalInRange(){
        this.playSound('/sounds/portal_in_range.mp3')
    }

    playSound(url) {
        const audio = new Audio(url);
        audio.play();
    }
}

const map = new Map('map')

const jsonData = JSON.parse(document.getElementById("map").dataset.maxfieldData)

if (jsonData) {
    map.displayMaxFieldData(jsonData)

} else {
    map.parseGpx(gpxString)
}

