import {Controller} from '@hotwired/stimulus';
import {Modal} from "bootstrap";

import '../styles/map/play2.css'

import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';

import Swal from "sweetalert2";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        path: String, jsonData: String, waypointIdMap: String, mapboxGlToken: String
    }

    static targets = ['keys', 'errorMessage', 'linkselect', 'mapDebug', 'optionsBox', 'optionsBoxTrigger']

    className = 'maxfield-play2'

    maxfieldData = null
    waypointIdMap = null

    links = []
    soundNotifier = null
    distance = 0

    map = null
    modal = null

    bounds = null
    markers = []

    connect() {
        this.maxfieldData = JSON.parse(this.jsonDataValue)
        this.waypointIdMap = JSON.parse(this.waypointIdMapValue)
        this.modal = new Modal('#exampleModal')
        this.links = this.maxfieldData.links
        this.setupMap()
        //this.loadFarmLayer()
    }

    setupMap() {
        mapboxgl.accessToken = this.mapboxGlTokenValue;
        this.map = new mapboxgl.Map({
            container: 'map', style: 'mapbox://styles/mapbox/streets-v12',
            //style: 'mapbox://styles/mapbox/dark-v11',
            center: [0, 0], zoom: 2,
        });

        const el = document.createElement('div');
        el.className = 'destinationMarker'
        el.innerHTML = 'O'

        this.destinationMarker = new mapboxgl.Marker(el)
            .setLngLat([0, 0])
            .addTo(this.map)

        this.map.addControl(new mapboxgl.FullscreenControl(), 'top-left');
        this.map.addControl(new mapboxgl.NavigationControl({
            visualizePitch: true
        }), 'top-left');
        this.map.addControl(this.getZoomControl());
        this.map.addControl(this.getGeolocateControl(), 'bottom-right');
        this.map.addControl(this.getOptionsBox())
        this.map.addControl(this.getPlayControl())

        this.loadFarmLayer()
        this.zoomAll()
    }

    getBounds() {
        if (!this.bounds) {
            this.bounds = new mapboxgl.LngLatBounds();

            this.maxfieldData.waypoints.forEach(function (o) {
                this.bounds.extend([o.lon, o.lat]);
            }.bind(this));
        }

        return this.bounds
    }

    getPlayControl() {
        return {
            onAdd: (map) => {
                const container = document.createElement('div');
                container.classList.add('mapboxgl-ctrl');

                let linkList = '<option value="-1">Start...</option>'
                let num = 1
                this.links.forEach(function (link, i) {
                    linkList += '<option value="' + i + '">' + num + ' - ' + link.name + '</option>'
                    num++
                })

                container.innerHTML = '<div class="info legend">' + '<button id="btnNext" data-action="maxfield-play2#nextLink">Start...</button><br />' + '<select id="groupSelect" class="form-control" data-maxfield-play2-target="linkselect" data-action="maxfield-play2#jumpToLink">' + linkList + '</select>'

                return container;
            }, getDefaultPosition: () => {
                return 'bottom-left'
            }, onRemove: () => {
                //this.map.off('moveend', updateLatLon);
            }
        }
    }

    getOptionsBox() {
        return {
            onAdd: (map) => {
                return document.getElementById('optionsBox')
            }, getDefaultPosition: () => {
                return 'top-right'
            }, onRemove: () => {
                //this.map.off('moveend', updateLatLon);
            }
        }
    }

    getZoomControl() {
        return {
            onAdd: (map) => {
                const zoomContainer = document.createElement('div');
                zoomContainer.classList.add('mapboxgl-ctrl');
                zoomContainer.innerHTML = '<button class="circle-button" data-action="maxfield-play2#zoomIn"><i class="bi-plus"></i></button>' + '<br>' + '<button class="circle-button" data-action="maxfield-play2#zoomOut"><i class="bi-dash"></i></button>' + '<br>' + '<button class="circle-button" data-action="maxfield-play2#zoomAll"><i class="bi-dash"></i></button>'

                return zoomContainer;
            }, getDefaultPosition: () => {
                return 'bottom-right'
            }, onRemove: () => {
            }
        }
    }

    getGeolocateControl() {
        const control = new mapboxgl.GeolocateControl({
            positionOptions: {
                enableHighAccuracy: true
            }, trackUserLocation: true, showUserHeading: true
        });

        control.on('geolocate', (event) => {
            console.log(event)
            const latitude = event.coords.latitude;
            const longitude = event.coords.longitude;

            console.log(latitude, longitude);
            this.mapDebugTarget.innerHTML = `Lat: ${latitude}<br>  Bearing: ${event.coords.heading}<br>` + `Map head: ${this.map.getBearing()}`
            if (event.coords.heading) {
                //this.map.setBearing(event.coords.heading);
            }
        });

        return control
    }

    zoomIn() {
        this.map.setZoom(this.map.getZoom() + 1);
    }

    zoomOut() {
        this.map.setZoom(this.map.getZoom() - 1);
    }

    zoomAll() {
        this.map.fitBounds(this.getBounds(), {padding: 100})
    }

    loadFarmLayer() {
        this.markers['farm'] = []
        this.maxfieldData.waypoints.forEach(function (o) {
            const num = o.keys
            let css = num > 3 ? 'circle farmalot' : 'circle'
            const el = document.createElement('div');
            el.className = 'farm-layer'
            el.innerHTML = '<b class="' + css + '">' + num + '</b>'
            const marker = new mapboxgl.Marker(el)
                .setLngLat([o.lon, o.lat])
                .setPopup(new mapboxgl.Popup().setHTML('<b>' + o.name + '</b><br>' + o.description))
                .addTo(this.map)
            this.markers['farm'].push(marker)
        }.bind(this))
        this.zoomAll()
    }

    nextLink(e) {
        const length = this.linkselectTarget.length

        if (this.linkselectTarget.value < length - 2) {
            e.target.innerText = 'Next'
            const newVal = parseInt(this.linkselectTarget.value) + 1
            this.showDestination(newVal)
            this.linkselectTarget.value = newVal
        } else {
            e.target.innerText = 'Finished!'

            Swal.fire('Finished :)');
        }
    }

    jumpToLink(e) {
        this.showDestination(this.linkselectTarget.value)
    }

    showDestination(id) {
        if (id < 0) {
            this.destinationMarker.setLngLat([0, 0])
                .setPopup('')
            this.destination = null
            clearInterval(this.soundNotifier)
            this.soundNotifier = null

            document.getElementById('btnNext').innerText = 'Start...'

            return
        }

        document.getElementById('btnNext').innerText = 'Next'

        const destination = this.links[id]
        this.destination = [destination.lon, destination.lat]

        this.map.panTo(this.destination)

        let description = ''

        description += '<ol>'
        destination.links.forEach(link => {
            description += '<li>' + `<img src="/waypoint_thumb/${this.waypointIdMap[link.num].guid}"` + 'width="60px" height="60px" alt="thumbnail image">' + '&nbsp;' + link.name + '</li>'
        })
        description += '</ol>'

        const el = document.createElement('div');
        el.className = 'circle destinationMarker'
        el.innerHTML = parseInt(id) + 1

        this.destinationMarker.remove()
        this.destinationMarker = new mapboxgl.Marker(el)
            .setLngLat(this.destination)
            .setPopup(new mapboxgl.Popup({offset: 25}) // add popups
                .setHTML(`<b>${destination.name}</b><hr>${description}`))
            .addTo(this.map)
        /*
                this.destinationMarker
                    .setLngLat(this.destination)
                    .setPopup(
                        new mapboxgl.Popup({offset: 25}) // add popups
                            .setHTML(
                                `<b>${destination.name}</b><hr>${description}`
                            )
                    )
        */

        // .setPopup('<b>' + destination.name + '</b><hr>' + description)
        /*
        .setIcon(
            L.divIcon({
                html: '<b class="circle circle-dest">' + (parseInt(id) + 1) + '</b>'
            })
        )
*/
        // Routing
        if (id > 0) {
            /*
            const previous = this.links[id - 1]
            const points = [
                L.latLng(previous.lat, previous.lon),
                L.latLng(destination.lat, destination.lon)
            ]
            this.originDestinationLine.setLatLngs(points)
            if (this.routingEnabled) {
                this.routingControl.setWaypoints(points)
            }
            */
        }

        // Sound
        if (!this.soundNotifier) {
            //this.soundNotifier = setInterval(this.soundNotify.bind(this), 15000)
        }
    }

    optionsBoxShow(e) {
        this.optionsBoxTriggerTarget.style.display = 'none'
        this.optionsBoxTarget.style.display = 'block'
        console.log(e)
    }

    optionsBoxHide(e) {
        this.optionsBoxTriggerTarget.style.display = 'block'
        this.optionsBoxTarget.style.display = 'none'
        console.log(e)
    }

    toggleLayer(e) {
        const checked = e.target.checked

        this.markers[e.params.item].forEach((e) => {
            e._element.style.display = checked ? 'block' : 'none'
        })
    }
}
