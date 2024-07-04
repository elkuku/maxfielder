<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    'bootstrap' => [
        'version' => '5.3.3',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '5.3.3',
        'type' => 'css',
    ],
    'stimulus-use' => [
        'version' => '0.52.2',
    ],
    'sweetalert2' => [
        'version' => '11.12.1',
    ],
    'leaflet' => [
        'version' => '1.9.4',
    ],
    'leaflet/dist/leaflet.min.css' => [
        'version' => '1.9.4',
        'type' => 'css',
    ],
    'leaflet/dist/leaflet.css' => [
        'version' => '1.9.4',
        'type' => 'css',
    ],
    'leaflet-fullscreen' => [
        'version' => '1.0.2',
    ],
    'leaflet-fullscreen/dist/leaflet.fullscreen.css' => [
        'version' => '1.0.2',
        'type' => 'css',
    ],
    'leaflet.markercluster' => [
        'version' => '1.5.3',
    ],
    'leaflet.markercluster/dist/MarkerCluster.min.css' => [
        'version' => '1.5.3',
        'type' => 'css',
    ],
    'leaflet.markercluster/dist/MarkerCluster.css' => [
        'version' => '1.5.3',
        'type' => 'css',
    ],
    'leaflet.markercluster/dist/MarkerCluster.Default.css' => [
        'version' => '1.5.3',
        'type' => 'css',
    ],
    'leaflet-draw' => [
        'version' => '1.0.4',
    ],
    'leaflet-draw/dist/leaflet.draw.min.css' => [
        'version' => '1.0.4',
        'type' => 'css',
    ],
    'leaflet-draw/dist/leaflet.draw.css' => [
        'version' => '1.0.4',
        'type' => 'css',
    ],
    'mapbox-gl' => [
        'version' => '3.4.0',
    ],
    'mapbox-gl/dist/mapbox-gl.min.css' => [
        'version' => '3.4.0',
        'type' => 'css',
    ],
    'turf-linestring' => [
        'version' => '1.0.2',
    ],
    'turf-circle' => [
        'version' => '3.0.12',
    ],
    'turf-destination' => [
        'version' => '3.0.12',
    ],
    'turf-helpers' => [
        'version' => '3.0.12',
    ],
    'turf-invariant' => [
        'version' => '3.0.12',
    ],
    'turf-featurecollection' => [
        'version' => '1.0.1',
    ],
    'turf-distance' => [
        'version' => '3.0.12',
    ],
    'turf-point' => [
        'version' => '2.0.1',
    ],
    'turf-feature' => [
        'version' => '1.0.0',
    ],
    'leaflet.locatecontrol' => [
        'version' => '0.81.1',
    ],
    'leaflet.locatecontrol/dist/L.Control.Locate.css' => [
        'version' => '0.81.1',
        'type' => 'css',
    ],
    'leaflet-routing-machine' => [
        'version' => '3.2.12',
    ],
    'leaflet-routing-machine/dist/leaflet-routing-machine.css' => [
        'version' => '3.2.12',
        'type' => 'css',
    ]
];
