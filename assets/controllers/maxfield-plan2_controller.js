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

    rectDrawing = false
    rectStart = null
    rectEl = null

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
            }
        })

        this.map.addControl(this.draw, 'top-left')

        this.map.on('load', () => {
            this.initSource()
            this.initLayers()
            this.loadMarkers()
            this.initControls()
            this.initRectEvents()

            // Fix for map only showing partial content on initial load
            setTimeout(() => this.map.resize(), 100)
        })

        this.map.on('moveend', () => this.loadMarkers())

        this.map.on('click', 'clusters', (e) => {
            const features = this.map.queryRenderedFeatures(e.point, { layers: ['clusters'] })
            const clusterId = features[0].properties.cluster_id
            this.map.getSource('waypoints').getClusterExpansionZoom(clusterId, (err, zoom) => {
                if (err) return
                this.map.easeTo({
                    center: features[0].geometry.coordinates,
                    zoom
                })
            })
        })

        this.map.on('click', 'unclustered-point', (e) => {
            const id = e.features[0].id
            this.toggleMarker(id)
        })

        this.map.on('mouseenter', 'clusters', () => {
            this.map.getCanvas().style.cursor = 'pointer'
        })
        this.map.on('mouseleave', 'clusters', () => {
            this.map.getCanvas().style.cursor = ''
        })

        this.map.on('mouseenter', 'unclustered-point', () => {
            this.map.getCanvas().style.cursor = 'pointer'
        })
        this.map.on('mouseleave', 'unclustered-point', () => {
            this.map.getCanvas().style.cursor = ''
        })

        this.map.on('draw.create', (e) => this.handleDraw(e))

        this.modal = new Modal(this.modalTarget)
    }

    initControls() {
        const container = document.querySelector('.mapboxgl-ctrl-top-left')

        const wrapControl = (el) => {
            const wrapper = document.createElement('div')
            wrapper.className = 'mapboxgl-ctrl'
            wrapper.appendChild(el)
            container.appendChild(wrapper)
        }

        wrapControl(document.getElementById('selection-count'))
        wrapControl(document.getElementById('controls-container'))
    }

    initRectEvents() {
        const canvas = this.map.getCanvas()

        canvas.addEventListener('mousedown', (e) => {
            if (!this.rectDrawing) return
            const rect = canvas.getBoundingClientRect()
            this.rectStart = { x: e.clientX - rect.left, y: e.clientY - rect.top }

            this.rectEl = document.createElement('div')
            this.rectEl.style.cssText = 'position:absolute;border:2px dashed #fff;background:rgba(255,255,255,0.15);pointer-events:none;z-index:1;'
            this.rectEl.style.left = this.rectStart.x + 'px'
            this.rectEl.style.top = this.rectStart.y + 'px'
            this.map.getCanvasContainer().appendChild(this.rectEl)
        })

        document.addEventListener('mousemove', (e) => {
            if (!this.rectDrawing || !this.rectStart || !this.rectEl) return
            const rect = canvas.getBoundingClientRect()
            const x = e.clientX - rect.left
            const y = e.clientY - rect.top
            Object.assign(this.rectEl.style, {
                left: Math.min(x, this.rectStart.x) + 'px',
                top: Math.min(y, this.rectStart.y) + 'px',
                width: Math.abs(x - this.rectStart.x) + 'px',
                height: Math.abs(y - this.rectStart.y) + 'px'
            })
        })

        document.addEventListener('mouseup', (e) => {
            if (!this.rectDrawing || !this.rectStart) return
            this.rectDrawing = false
            this.map.getCanvas().style.cursor = ''
            this.map.dragPan.enable()

            if (this.rectEl) {
                this.rectEl.remove()
                this.rectEl = null
            }

            const rect = canvas.getBoundingClientRect()
            const endX = e.clientX - rect.left
            const endY = e.clientY - rect.top

            const sw = this.map.unproject([Math.min(endX, this.rectStart.x), Math.max(endY, this.rectStart.y)])
            const ne = this.map.unproject([Math.max(endX, this.rectStart.x), Math.min(endY, this.rectStart.y)])

            const polygon = {
                type: 'Polygon',
                coordinates: [[
                    [sw.lng, sw.lat],
                    [ne.lng, sw.lat],
                    [ne.lng, ne.lat],
                    [sw.lng, ne.lat],
                    [sw.lng, sw.lat]
                ]]
            }

            this.selectWithPolygon(polygon)
            this.rectStart = null
        })
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
                'circle-color': [
                    'case',
                    ['==', ['coalesce', ['feature-state', 'selectedCount'], 0], ['get', 'point_count']], '#ff0000',
                    ['>', ['coalesce', ['feature-state', 'selectedCount'], 0], 0], '#ffff00',
                    '#ff9900'
                ]
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

        this.map.once('idle', () => this.updateClusterColors())
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
        this.updateClusterColors()
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
        this.updateClusterColors()
    }

    updateClusterColors() {
        const source = this.map.getSource('waypoints')
        if (!source) return

        const clusters = this.map.queryRenderedFeatures({ layers: ['clusters'] })
        clusters.forEach(cluster => {
            const clusterId = cluster.properties.cluster_id
            const pointCount = cluster.properties.point_count
            source.getClusterLeaves(clusterId, pointCount, 0, (err, leaves) => {
                if (err) return
                const selectedCount = leaves.filter(leaf => this.selectedMarkers.includes(leaf.id)).length
                this.map.setFeatureState(
                    { source: 'waypoints', id: clusterId },
                    { selectedCount }
                )
            })
        })
    }

    /* -----------------------------
     * Draw selection
     * ----------------------------- */

    handleDraw(e) {
        this.selectWithPolygon(e.features[0].geometry)
        this.draw.deleteAll()
    }

    selectWithPolygon(polygon) {
        const source = this.map.getSource('waypoints')

        const points = this.map.queryRenderedFeatures({ layers: ['unclustered-point'] })
        points.forEach(f => {
            if (booleanPointInPolygon(f.geometry.coordinates, polygon)) {
                this.selectionMode === 'remove'
                    ? this.removeMarker(f.id)
                    : this.addMarker(f.id)
            }
        })

        const clusters = this.map.queryRenderedFeatures({ layers: ['clusters'] })
        clusters.forEach(cluster => {
            const clusterId = cluster.properties.cluster_id
            const pointCount = cluster.properties.point_count
            source.getClusterLeaves(clusterId, pointCount, 0, (err, leaves) => {
                if (err) return
                leaves.forEach(leaf => {
                    if (booleanPointInPolygon(leaf.geometry.coordinates, polygon)) {
                        this.selectionMode === 'remove'
                            ? this.removeMarker(leaf.id)
                            : this.addMarker(leaf.id)
                    }
                })
            })
        })
    }

    /* -----------------------------
     * Draw controls
     * ----------------------------- */

    drawRect() {
        this.rectDrawing = true
        this.map.getCanvas().style.cursor = 'crosshair'
        this.map.dragPan.disable()
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
