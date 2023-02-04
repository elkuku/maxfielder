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
    }

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
    }

    updateFields() {
        const center = this.map.getCenter()

        document.getElementById('user_params_lat').value = center.lat.toFixed(7)
        document.getElementById('user_params_lon').value = center.lng.toFixed(7)
        document.getElementById('user_params_zoom').value = this.map.getZoom()
    }
}
