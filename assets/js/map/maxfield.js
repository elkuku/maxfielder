const $ = require('jquery'); // @todo remove jquery :(

require('leaflet')
require('leaflet/dist/leaflet.css')
require('../../styles/map/maxfield.css')

require('leaflet.markercluster')
require('leaflet.markercluster/dist/MarkerCluster.css')
require('leaflet.markercluster/dist/MarkerCluster.Default.css')

require('leaflet-draw')
require('leaflet-draw/dist/leaflet.draw.css')
require('leaflet-fullscreen')
require('leaflet-fullscreen/dist/leaflet.fullscreen.css')

import 'bootstrap/js/dist/modal'

let map, selectionMode

const LeafIcon = L.Icon.extend({
    options: {
        shadowUrl: '/build/images/map-marker/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    }
})

const redIcon = new LeafIcon({iconUrl: '/build/images/map-marker/marker-icon-red.png'}),
    orangeIcon = new LeafIcon({iconUrl: '/build/images/map-marker/marker-icon-orange.png'})

const selectedMarkers = []
const markers = L.markerClusterGroup({disableClusteringAtZoom: 16})

function initMap() {
    const osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
    const osmAttrib = 'Map data (C) <a href="https://openstreetmap.org">OpenStreetMap</a> contributors'
    const osm = new L.TileLayer(osmUrl, {attribution: osmAttrib})

    map = new L.Map('map', {
        fullscreenControl: true,
        editable: true,
        editOptions: {}
    })

    // World
    map.setView(new L.LatLng(0, 0), 3)

    map.addLayer(osm)

    map.on('draw:created', (e) => {
        let bounds = e.layer.getBounds()
        markers.eachLayer(function (layer) {
            if (bounds.contains(layer.getLatLng())) {
                if ('remove' === selectionMode) {
                    removeMarker(layer)
                } else {
                    addMarker(layer)
                }
            }
        })
    })
}

function loadMarkers() {
    markers.clearLayers()
    let bounds = map.getBounds()
    bounds = bounds._northEast.lat + ',' + bounds._northEast.lng + ',' + bounds._southWest.lat + ',' + bounds._southWest.lng

    $.get('/waypoints_map?bounds=' + bounds, {some_var: ''}, function (data) {

        $(data).each(function () {
            const marker =
                new L.Marker(
                    new L.LatLng(this.lat, this.lng),
                    {icon: orangeIcon, wp_id: this.id, wp_selected: false, title: this.name}
                )

            marker.on('click', function (e) {
                toggleMarker(e.target)
            })

            markers.addLayer(marker)
            map.addLayer(markers)
        })
    }, 'json')
}

function initControls() {
    const mapControlsContainer = document.getElementsByClassName('leaflet-control')[0]
    // mapControlsContainer.appendChild(document.getElementById("logoContainer"));
    mapControlsContainer.appendChild(document.getElementById('selection-count'))
    mapControlsContainer.appendChild(document.getElementById('controls-container'))
}

function toggleMarker(marker) {
    if (marker.options.wp_selected) {
        removeMarker(marker)
    } else {
        addMarker(marker)
    }
}

function addMarker(marker) {
    let index = selectedMarkers.indexOf(marker.options.wp_id)
    if (index === -1) {
        marker.setIcon(redIcon)
        marker.options.wp_selected = true
        selectedMarkers.push(marker.options.wp_id)
        $('#selection-count').html(selectedMarkers.length)
    }
}

function removeMarker(marker) {
    let index = selectedMarkers.indexOf(marker.options.wp_id)
    if (index > -1) {
        marker.setIcon(orangeIcon)
        marker.options.wp_selected = false
        selectedMarkers.splice(index, 1)
        $('#selection-count').html(selectedMarkers.length)
    }
}

function doPostRequest(path, parameters) {
    const form = $('<form></form>')

    form.attr('method', 'post')
    form.attr('action', path)

    $.each(parameters, function (key, value) {
        if (typeof value == 'object' || typeof value == 'array') {
            $.each(value, function (subkey, subvalue) {
                const field = $('<input />')
                field.attr('type', 'hidden')
                field.attr('name', key + '[]')
                field.attr('value', subvalue)
                form.append(field)
            })
        } else {
            const field = $('<input />')
            field.attr('type', 'hidden')
            field.attr('name', key)
            field.attr('value', value)
            form.append(field)
        }
    })
    $(document.body).append(form)
    form.submit()
}

function copyToClipboard(elementId) {
    if (!navigator.clipboard) {
        alert('Copy not possible - change browser :P')
        return
    }
    const text = $('#' + elementId).val()
    navigator.clipboard.writeText(text).then(function () {
        alert('Content has been copied to your clipboard.')
        // console.log('Async: Copying to clipboard was successful!')
    }, function (err) {
        alert('Could not copy text: ' + err)
        // console.error('Async: Could not copy text: ', err)
    })
}

initMap()
loadMarkers()
initControls()

$('#result_maxFields').on('click', function () {
    $.post('/export2', {points: selectedMarkers}, function (data) {
        const options = {}
        const modal = $('#resultModal')
        modal.find('.modal-title').text('MaxFields')
        modal.find('.modal-body').text(data.maxfield)
        modal.modal(options)
    })
})

$('#result_Gpx').on('click', function () {
    $.post('/export2', {points: selectedMarkers}, function (data) {
        const options = {}
        const modal = $('#resultModal')
        modal.find('.modal-title').text('GPX')
        modal.find('.modal-body').text(data.gpx)
        modal.modal(options)
    })
})

$('#createGallery').on('click', function () {
    const options = {}
    const modal = $('#galleryModal')
    modal.modal(options)
    modal.find('.status').text('Waypoints selected: ' + selectedMarkers.length)
})

const galleryModal = $('#galleryModal')

galleryModal.find('button').on('click', function () {
    const input = galleryModal.find('input')

    $.post('/collection/create', {points: selectedMarkers, name: input.val()}, function (data) {
        console.log(data)

        // const options = {}
        // const modal = $('#resultModal')
        // modal.find('.modal-title').text('GPX')
        // modal.find('.modal-body').text(data.gpx)
        // modal.modal(options)
    })

})

const maxfieldModal = document.getElementById('maxfieldModal')

maxfieldModal.addEventListener('shown.bs.modal', () => {
    const modal = $('#maxfieldModal')
    console.log(modal)
        if (!selectedMarkers.length) {
        modal.find('.status').text('No Waypoints selected!')
        modal.find('.controls').hide()
        $('#build').hide()
    } else {
        modal.find('.status').text('Waypoints selected: ' + selectedMarkers.length)
        modal.find('.controls').show()
        $('#build').show()
    }

})

$('#build').on('click', function () {
    let buildName = $('#build_name')
    if (!buildName.val()) {
        alert('Please provide a name')
        buildName.focus()
        return
    }

    $(this).html(
        '<span class="spinner-border spinner-border-sm" role="status"></span>'
        + '  Working...'
    )

    doPostRequest('/maxfield/export', {
        points: selectedMarkers,
        buildName: buildName.val(),
        players_num: $('#players_num').val(),
        skip_plots: $('#skip_plots').is(':checked'),
        skip_step_plots: $('#skip_step_plots').is(':checked')
    })
})

$('#selectRect').on('click', function () {
    let rect = new L.Draw.Rectangle(map)
    rect.enable()
})

$('#selectPoly').on('click', function () {
    let poly = new L.Draw.Polygon(map)
    poly.enable()
})

$('#selectToggle').on('click', function () {
    if ('remove' === selectionMode) {
        selectionMode = 'add'
        $(this).addClass('btn-outline-success')
        $(this).removeClass('btn-outline-danger')
        $(this).find('span').addClass('oi-plus')
        $(this).find('span').removeClass('oi-minus')
    } else {
        selectionMode = 'remove'
        $(this).addClass('btn-outline-danger')
        $(this).removeClass('btn-outline-success')
        $(this).find('span').addClass('oi-minus')
        $(this).find('span').removeClass('oi-plus')
    }
})

$('#copyToClipboard').on('click', function () {
    copyToClipboard('resultText')
})
