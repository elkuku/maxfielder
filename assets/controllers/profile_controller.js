import {Controller} from '@hotwired/stimulus'

import 'leaflet'
import 'leaflet/dist/leaflet.css'

import 'leaflet-fullscreen'
import 'leaflet-fullscreen/dist/leaflet.fullscreen.css'

import '../styles/profile.css'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        lat: Number,
        lon: Number,
        zoom: Number,
        mapProvider: String,
        maxfieldEngine: String,
    }

    static targets = ['lat', 'lon', 'zoom', 'mapOptions', 'dockerOptions', 'agentName', 'agentNameLabel']

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

        // Initialize map options visibility on page load
        this._updateMapOptions(this.mapProviderValue)
        this._updateDockerOptions(this.maxfieldEngineValue)
        
        // Add form submit handler for validation
        const form = this.agentNameTarget.closest('form')
        if (form) {
            form.addEventListener('submit', (e) => this._handleSubmit(e))
        }
    }

    updateFields() {
        const center = this.map.getCenter()

        this.latTarget.value = center.lat.toFixed(7)
        this.lonTarget.value = center.lng.toFixed(7)
        this.zoomTarget.value = this.map.getZoom()
    }

    validateAgentName() {
        const value = this.agentNameTarget.value.trim()
        const formGroup = this.agentNameTarget.closest('.mb-3') || this.agentNameTarget.parentNode
        
        if (value === '') {
            // Show Bootstrap validation error
            this.agentNameTarget.classList.add('is-invalid')
            this.agentNameTarget.classList.remove('is-valid')
            
            // Create or update invalid feedback message
            let feedback = formGroup.querySelector('.invalid-feedback')
            if (!feedback) {
                feedback = document.createElement('div')
                feedback.className = 'invalid-feedback'
                formGroup.appendChild(feedback)
            }
            feedback.textContent = 'El nombre del agente no puede estar vacío.'
            return false
        } else {
            // Clear validation error
            this.agentNameTarget.classList.remove('is-invalid')
            this.agentNameTarget.classList.add('is-valid')
            
            // Remove invalid feedback if exists
            const feedback = formGroup.querySelector('.invalid-feedback')
            if (feedback) {
                feedback.remove()
            }
            return true
        }
    }

    checkMapOptions(event) {
        this._updateMapOptions(event.target.value)
    }

    checkEngineOptions(event) {
        this._updateDockerOptions(event.target.value)
    }

    _handleSubmit(event) {
        const isValid = this.validateAgentName()
        if (!isValid) {
            event.preventDefault()
            event.stopPropagation()
        }
    }

    _updateDockerOptions(engine) {
        this.dockerOptionsTarget.style.display = engine === 'docker' ? 'block' : 'none'
    }

    _updateMapOptions(provider) {
        if ('mapbox' === provider) {
            this.mapOptionsTarget.style.display = 'block'
        } else {
            this.mapOptionsTarget.style.display = 'none'
        }
    }
}
