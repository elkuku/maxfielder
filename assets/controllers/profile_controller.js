import {Controller} from '@hotwired/stimulus'

import 'leaflet'
import 'leaflet/dist/leaflet.css'

import 'leaflet-fullscreen'
import 'leaflet-fullscreen/dist/leaflet.fullscreen.css'

import 'styles/profile.css'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        lat: Number,
        lon: Number,
        zoom: Number,
        mapProvider: String,
    }

    static targets = ['lat', 'lon', 'zoom', 'mapOptions']

    map = null

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

        this.map = new L.Map('map', {fullscreenControl: true})
        this.map.setView(new L.LatLng(lat, lon), zoom)

        const osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
        const osmAttrib = 'Map data (C) <a href="https://openstreetmap.org">OpenStreetMap</a> contributors'
        const osm = new L.TileLayer(osmUrl, {attribution: osmAttrib})

        this.map.addLayer(osm)

        this.map.on('dragend', () => this.updateFields())
        this.map.on('zoomend', () => this.updateFields())

        this._updateMapOptions(this.mapProviderValue)
    }

    updateFields() {
        const center = this.map.getCenter()

        this.latTarget.value = center.lat.toFixed(7)
        this.lonTarget.value = center.lng.toFixed(7)
        this.zoomTarget.value = this.map.getZoom()
    }

    checkMapOptions(event) {
        this._updateMapOptions(event.target.value)
    }

    _updateMapOptions(provider) {
        if ('mapbox' === provider) {
            this.mapOptionsTarget.style.display = 'block'
        }else {
            this.mapOptionsTarget.style.display = 'none'
        }
    }
}
