import {Controller} from '@hotwired/stimulus'
import {Modal} from 'bootstrap'

import 'leaflet'
import 'leaflet/dist/leaflet.css'

require('leaflet.markercluster')
require('leaflet.markercluster/dist/MarkerCluster.css')
require('leaflet.markercluster/dist/MarkerCluster.Default.css')

require('leaflet-draw')
require('leaflet-draw/dist/leaflet.draw.css')

import 'leaflet-fullscreen'
import 'leaflet-fullscreen/dist/leaflet.fullscreen.css'

import 'styles/maxfield/plan.css'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['counter', 'modal', 'modalStatus', 'modalControls', 'modalPoints']
    static values = {
        lat: Number,
        lon: Number,
        zoom: Number,
    }

    map = null
    markers = L.markerClusterGroup({disableClusteringAtZoom: 16})
    selectedMarkers = []
    selectionMode = ''
    redIcon = null
    orangeIcon = null
    modal = null

    connect() {
        let lat, lon, zoom

        if (this.latValue) {
            lat = this.latValue
            lon = this.lonValue
            zoom = this.zoomValue
        } else {
            lat = 0
            lon = 0
            zoom = 3
        }

        this.map = new L.Map('map', {
            fullscreenControl: true,
            editable: true,
            editOptions: {}
        })
        this.map.setView(new L.LatLng(lat, lon), zoom)

        const osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
        const osmAttrib = 'Map data (C) <a href="https://openstreetmap.org">OpenStreetMap</a> contributors'
        const osm = new L.TileLayer(osmUrl, {attribution: osmAttrib})

        this.map.addLayer(osm)
        this.map.addLayer(this.markers)

        this.loadMarkers()
        this.initControls()

        this.map.on('dragend', () => this.loadMarkers())

        this.map.on('zoomend', () => this.loadMarkers())

        this.map.on('draw:created', (e) => {
            let bounds = e.layer.getBounds()
            this.markers.eachLayer(function (marker) {
                if (bounds.contains(marker.getLatLng())) {
                    if ('remove' === this.selectionMode) {
                        this.removeMarker(marker)
                    } else {
                        this.addMarker(marker)
                    }
                }
            }.bind(this))
        })

        const LeafIcon = L.Icon.extend({
            options: {
                shadowUrl: '/build/images/map-marker/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            }
        })

        this.redIcon = new LeafIcon({iconUrl: '/build/images/map-marker/marker-icon-red.png'})
        this.orangeIcon = new LeafIcon({iconUrl: '/build/images/map-marker/marker-icon-orange.png'})

        this.modal = new Modal(this.modalTarget)
    }

    initControls() {
        const mapControlsContainer = document.getElementsByClassName('leaflet-control')[0]
        mapControlsContainer.appendChild(document.getElementById('selection-count'))
        mapControlsContainer.appendChild(document.getElementById('controls-container'))
    }

    async loadMarkers() {
        this.markers.clearLayers()

        const bounds = this.map.getBounds()
        const boundsString = bounds._northEast.lat + ',' + bounds._northEast.lng + ',' + bounds._southWest.lat + ',' + bounds._southWest.lng
        const response = await fetch('/waypoints_map?bounds=' + boundsString)
        const data = await response.json()

        data.forEach(function (item) {
            let icon, selected
            if (this.selectedMarkers.includes(item.id)) {
                icon = this.redIcon
                selected = true
            } else {
                icon = this.orangeIcon
                selected = false
            }
            const marker =
                new L.Marker(
                    new L.LatLng(item.lat, item.lng),
                    {icon: icon, id: item.id, selected: selected, title: item.name}
                )
            marker.on('click', function (e) {
                this.toggleMarker(e.target)
            }.bind(this))
            this.markers.addLayer(marker)
        }.bind(this))
    }

    showModal() {
        if (this.map.isFullscreen()) {
            this.map.toggleFullscreen()
        }

        this.modal.show()

        if (!this.selectedMarkers.length) {
            this.modalStatusTarget.innerText = 'No Waypoints selected!'
        } else if (this.selectedMarkers.length < 4) {
            this.modalStatusTarget.innerText = 'You know that a field consists of three points? this is Maxfield! (Please provide at least 4 points)'
        } else if (this.selectedMarkers.length > 100) {
            this.modalStatusTarget.innerText = 'Currently 100 Waypoints is the maximum. Sorry :('
        } else {
            this.modalStatusTarget.classList.remove('alert-warning')
            this.modalStatusTarget.classList.add('alert-success')
            this.modalStatusTarget.innerText = 'Waypoints selected: ' + this.selectedMarkers.length
            this.modalControlsTarget.style.display = ''
            this.modalPointsTarget.value = this.selectedMarkers
            document.getElementById('build').style.display = ''

            return
        }

        this.modalStatusTarget.classList.remove('alert-success')
        this.modalStatusTarget.classList.add('alert-warning')
        this.modalControlsTarget.style.display = 'none'
        document.getElementById('build').style.display = 'none'
    }

    drawRect() {
        new L.Draw.Rectangle(this.map).enable()
    }

    drawPoly() {
        new L.Draw.Polygon(this.map).enable()
    }

    toggleSelectMode(e) {
        let button, span

        if ('BUTTON' === e.target.tagName) {
            button = e.target
            span = button.childNodes[1]
        } else if ('SPAN' === e.target.tagName) {
            span = e.target
            button = span.parentNode
        } else {
            console.error('unsupported element: ' + e.target.tagName)
            return
        }

        if ('remove' === this.selectionMode) {
            this.selectionMode = 'add'
            button.classList.add('btn-outline-success')
            button.classList.remove('btn-outline-danger')
            span.classList.add('bi-plus-lg')
            span.classList.remove('bi-dash-lg')
        } else {
            this.selectionMode = 'remove'
            button.classList.add('btn-outline-danger')
            button.classList.remove('btn-outline-success')
            span.classList.add('bi-dash-lg')
            span.classList.remove('bi-plus-lg')
        }
    }

    toggleMarker(marker) {
        if (marker.options.selected) {
            this.removeMarker(marker)
        } else {
            this.addMarker(marker)
        }
    }

    addMarker(marker) {
        let index = this.selectedMarkers.indexOf(marker.options.id)
        if (index === -1) {
            marker.setIcon(this.redIcon)
            marker.options.selected = true
            this.selectedMarkers.push(marker.options.id)
            this.counterTarget.innerText = this.selectedMarkers.length
        }
    }

    removeMarker(marker) {
        const index = this.selectedMarkers.indexOf(marker.options.id)
        if (index > -1) {
            marker.setIcon(this.orangeIcon)
            marker.options.selected = false
            this.selectedMarkers.splice(index, 1)
            this.counterTarget.innerText = this.selectedMarkers.length
        }
    }
}
