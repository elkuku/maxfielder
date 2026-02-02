import { Controller } from '@hotwired/stimulus'
import { Modal } from 'bootstrap'
import mapboxgl from 'mapbox-gl'
import MapboxDraw from '@mapbox/mapbox-gl-draw'
import booleanPointInPolygon from '@turf/boolean-point-in-polygon'

import 'mapbox-gl/dist/mapbox-gl.css'
import '@mapbox/mapbox-gl-draw/dist/mapbox-gl-draw.css'

import '../styles/map/plan.css'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['counter', 'modal', 'modalStatus', 'modalControls', 'modalPoints']
    static values = {
        lat: Number,
        lon: Number,
        zoom: Number,
        token: String
    }

    map = null
    draw = null
    modal = null

    selectedMarkers = []
    selectionMode = 'add'

    connect() {
        mapboxgl.accessToken = this.tokenValue

        this.map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v12',
            center: [
                this.lonValue ?? 0,
                this.latValue ?? 0
            ],
            zoom: this.zoomValue ?? 3
        })

        this.map.addControl(new mapboxgl.FullscreenControl(), 'top-left')

        this.draw = new MapboxDraw({
            displayControlsDefault: false,
            controls: {
                polygon: true,
                rectangle: true
            }
        })

        this.map.addControl(this.draw, 'top-left')

        this.map.on('load', () => {
            this.initSource()
            this.initLayers()
            this.loadMarkers()
        })

        this.map.on('moveend', () => this.loadMarkers())

        this.map.on('click', 'unclustered-point', (e) => {
            const id = e.features[0].id
            this.toggleMarker(id)
        })

        this.map.on('draw.create', (e) => this.handleDraw(e))

        this.modal = new Modal(this.modalTarget)
    }

    /* -----------------------------
     * Map source & layers
     * ----------------------------- */

    initSource() {
        this.map.addSource('waypoints', {
            type: 'geojson',
            data: {
                type: 'FeatureCollection',
                features: []
            },
            cluster: true,
            clusterMaxZoom: 16,
            clusterRadius: 50
        })
    }

    initLayers() {
        this.map.addLayer({
            id: 'clusters',
            type: 'circle',
            source: 'waypoints',
            filter: ['has', 'point_count'],
            paint: {
                'circle-radius': 18,
                'circle-color': '#ff9900'
            }
        })

        this.map.addLayer({
            id: 'cluster-count',
            type: 'symbol',
            source: 'waypoints',
            filter: ['has', 'point_count'],
            layout: {
                'text-field': '{point_count_abbreviated}',
                'text-size': 12
            }
        })

        this.map.addLayer({
            id: 'unclustered-point',
            type: 'circle',
            source: 'waypoints',
            filter: ['!', ['has', 'point_count']],
            paint: {
                'circle-radius': 10,
                'circle-color': [
                    'case',
                    ['boolean', ['feature-state', 'selected'], false],
                    '#ff0000',
                    '#ff9900'
                ]
            }
        })
    }

    /* -----------------------------
     * Data loading
     * ----------------------------- */

    async loadMarkers() {
        if (!this.map.getSource('waypoints')) return

        const b = this.map.getBounds()
        const boundsString =
            `${b.getNorth()},${b.getEast()},${b.getSouth()},${b.getWest()}`

        const response = await fetch('/waypoints_map?bounds=' + boundsString)
        const data = await response.json()

        const features = data.map(item => ({
            type: 'Feature',
            id: item.id,
            geometry: {
                type: 'Point',
                coordinates: [item.lng, item.lat]
            },
            properties: {
                title: item.name
            }
        }))

        this.map.getSource('waypoints').setData({
            type: 'FeatureCollection',
            features
        })

        this.selectedMarkers.forEach(id => {
            this.map.setFeatureState(
                { source: 'waypoints', id },
                { selected: true }
            )
        })
    }

    /* -----------------------------
     * Selection logic
     * ----------------------------- */

    toggleMarker(id) {
        this.selectedMarkers.includes(id)
            ? this.removeMarker(id)
            : this.addMarker(id)
    }

    addMarker(id) {
        if (this.selectedMarkers.includes(id)) return

        this.selectedMarkers.push(id)
        this.map.setFeatureState(
            { source: 'waypoints', id },
            { selected: true }
        )
        this.counterTarget.innerText = this.selectedMarkers.length
    }

    removeMarker(id) {
        const index = this.selectedMarkers.indexOf(id)
        if (index === -1) return

        this.selectedMarkers.splice(index, 1)
        this.map.setFeatureState(
            { source: 'waypoints', id },
            { selected: false }
        )
        this.counterTarget.innerText = this.selectedMarkers.length
    }

    /* -----------------------------
     * Draw selection
     * ----------------------------- */

    handleDraw(e) {
        const polygon = e.features[0]

        const points = this.map.queryRenderedFeatures({
            layers: ['unclustered-point']
        })

        points.forEach(f => {
            if (
                booleanPointInPolygon(
                    f.geometry.coordinates,
                    polygon.geometry
                )
            ) {
                this.selectionMode === 'remove'
                    ? this.removeMarker(f.id)
                    : this.addMarker(f.id)
            }
        })

        this.draw.deleteAll()
    }

    /* -----------------------------
     * Draw controls
     * ----------------------------- */

    drawRect() {
        this.draw.changeMode('draw_rectangle')
    }

    drawPoly() {
        this.draw.changeMode('draw_polygon')
    }

    /* -----------------------------
     * UI helpers
     * ----------------------------- */

    toggleSelectMode(e) {
        const button = e.target.closest('button')
        const span = button.querySelector('span')

        if (this.selectionMode === 'remove') {
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

    showModal() {
        this.modal.show()

        if (!this.selectedMarkers.length) {
            this.modalStatusTarget.innerText = 'No Waypoints selected!'
        } else if (this.selectedMarkers.length < 4) {
            this.modalStatusTarget.innerText =
                'Please select at least 4 points.'
        } else if (this.selectedMarkers.length > 100) {
            this.modalStatusTarget.innerText =
                'Maximum of 100 waypoints allowed.'
        } else {
            this.modalStatusTarget.classList.remove('alert-warning')
            this.modalStatusTarget.classList.add('alert-success')
            this.modalStatusTarget.innerText =
                'Waypoints selected: ' + this.selectedMarkers.length

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
}
