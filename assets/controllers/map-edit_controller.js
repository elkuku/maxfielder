import {Controller} from '@hotwired/stimulus'

import 'leaflet'
import'leaflet/dist/leaflet.css'

import'leaflet.markercluster'
import'leaflet.markercluster/dist/MarkerCluster.css'
import'leaflet.markercluster/dist/MarkerCluster.Default.css'

import'../styles/map/edit.css'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    map = null
    markers = null

    connect() {
        this.map = new L.Map('map', {fullscreenControl: true})
        this.markers = L.markerClusterGroup({disableClusteringAtZoom: 16})
        this.initMap()
        this.loadMarkers()

        // Fix for map only showing partial content on initial load
        setTimeout(() => this.map.invalidateSize(), 100)
    }

    initMap() {
        const osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
        const osmAttrib = 'Map data (C) <a href="https://openstreetmap.org">OpenStreetMap</a> contributors'
        const osm = new L.TileLayer(osmUrl, {attribution: osmAttrib})

        this.map.setView(new L.LatLng(0, 0), 3)
        this.map.addLayer(osm)
    }

    async loadMarkers() {
        this.markers.clearLayers()

        const myIcon = L.icon({
            iconUrl: '/images/leaflet/marker.png',
            iconSize: [22, 36],
            iconAnchor: [11, 36],
            popupAnchor: [0, -18],
        })

        const response = await fetch('/waypoints_map')
        const data = await response.json()

        data.forEach((e) => {
            let marker =
                new L.Marker(
                    new L.LatLng(e.lat, e.lng),
                    {
                        icon: myIcon,
                        wp_id: e.id, wp_selected: false, title: e.name
                    }
                )

            marker.bindPopup('Loading...', {maxWidth: 'auto'})

            marker.on('click', async function (e) {
                const popup = e.target.getPopup()
                const response = await fetch('/waypoints_info/' + e.target.options.wp_id)
                const data = await response.text()
                popup.setContent(data)
                popup.update()
            })

            this.markers.addLayer(marker)
        })

        this.map.addLayer(this.markers)
    }
}
