import {Controller} from '@hotwired/stimulus'

import 'leaflet'
import 'leaflet/dist/leaflet.css'

import 'leaflet-fullscreen'
import 'leaflet-fullscreen/dist/leaflet.fullscreen.css'

import 'styles/profile.css'

export default class extends Controller {
    static values = {
        lat: Number,
        lon: Number,
        zoom: Number,
    }

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

        const map = new L.Map('map', { fullscreenControl: true })
        map.setView(new L.LatLng(lat, lon), zoom)

        const osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
        const osmAttrib = 'Map data (C) <a href="https://openstreetmap.org">OpenStreetMap</a> contributors'
        const osm = new L.TileLayer(osmUrl, { attribution: osmAttrib })

        map.addLayer(osm)

        map.on('dragend', function (e) {
            this.updateFields(map)
        }.bind(this))

        map.on('zoomend', function (e) {
            this.updateFields(map)
        }.bind(this))
    }

    updateFields(map) {
        const center = map.getCenter()

        document.getElementById('user_params_lat').value = center.lat.toFixed(7)
        document.getElementById('user_params_lon').value = center.lng.toFixed(7)
        document.getElementById('user_params_zoom').value = map.getZoom()
    }
}
