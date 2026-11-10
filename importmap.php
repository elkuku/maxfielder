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
 *
 * @return array<string, array{    // Import name as key, description of the imported file as value
 *     path: string,               // Logical, relative or absolute path to the file
 *     type?: 'js'|'css'|'json',   // Type of the file, defaults to 'js'
 *     entrypoint?: bool,          // Whether the file is an entrypoint, for 'js' only
 * }|array{
 *     version: string,            // Version of the remote package
 *     package_specifier?: string, // Remote "package-name/path" specifier, defaults to the import name
 *     type?: 'js'|'css'|'json',
 *     entrypoint?: bool,
 * }>
 */
return [
    'app' => ['path' => './assets/app.js', 'entrypoint' => true],
    '@symfony/stimulus-bundle' => ['path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js'],
    '@hotwired/stimulus' => ['version' => '3.2.2'],
    'bootstrap' => ['version' => '5.3.8'],
    '@popperjs/core' => ['version' => '2.11.8'],
    'bootstrap/dist/css/bootstrap.min.css' => ['version' => '5.3.8', 'type' => 'css'],
    'stimulus-use' => ['version' => '0.53.1'],
    'sweetalert2' => ['version' => '11.26.25'],
    'leaflet' => ['version' => '1.9.4'],
    'leaflet/dist/leaflet.min.css' => ['version' => '1.9.4', 'type' => 'css'],
    'leaflet/dist/leaflet.css' => ['version' => '1.9.4', 'type' => 'css'],
    'leaflet-fullscreen' => ['version' => '1.0.2'],
    'leaflet-fullscreen/dist/leaflet.fullscreen.css' => ['version' => '1.0.2', 'type' => 'css'],
    'leaflet.markercluster' => ['version' => '1.5.3'],
    'leaflet.markercluster/dist/MarkerCluster.min.css' => ['version' => '1.5.3', 'type' => 'css'],
    'leaflet.markercluster/dist/MarkerCluster.css' => ['version' => '1.5.3', 'type' => 'css'],
    'leaflet.markercluster/dist/MarkerCluster.Default.css' => ['version' => '1.5.3', 'type' => 'css'],
    'leaflet-draw' => ['version' => '1.0.4'],
    'leaflet-draw/dist/leaflet.draw.min.css' => ['version' => '1.0.4', 'type' => 'css'],
    'leaflet-draw/dist/leaflet.draw.css' => ['version' => '1.0.4', 'type' => 'css'],
    'mapbox-gl' => ['version' => '3.34.2'],
    'mapbox-gl/dist/mapbox-gl.min.css' => ['version' => '3.34.2', 'type' => 'css'],
    'turf-linestring' => ['version' => '1.0.2'],
    'turf-circle' => ['version' => '3.0.12'],
    'turf-destination' => ['version' => '3.0.12'],
    'turf-helpers' => ['version' => '3.0.12'],
    'turf-invariant' => ['version' => '3.0.12'],
    'turf-featurecollection' => ['version' => '1.0.1'],
    'turf-distance' => ['version' => '3.0.12'],
    'turf-point' => ['version' => '2.0.1'],
    'turf-feature' => ['version' => '1.0.0'],
    'leaflet.locatecontrol' => ['version' => '0.90.1'],
    'leaflet.locatecontrol/dist/L.Control.Locate.css' => ['version' => '0.90.1', 'type' => 'css'],
    'leaflet-routing-machine' => ['version' => '3.2.12'],
    'leaflet-routing-machine/dist/leaflet-routing-machine.css' => ['version' => '3.2.12', 'type' => 'css'],
    'bootstrap-icons/font/bootstrap-icons.min.css' => ['version' => '1.13.2', 'type' => 'css'],
    '@mapbox/mapbox-gl-draw' => ['version' => '1.5.2'],
    '@mapbox/geojson-area' => ['version' => '0.3.0'],
    'nanoid/non-secure' => ['version' => '6.0.2'],
    '@mapbox/point-geometry' => ['version' => '1.1.0'],
    '@turf/projection' => ['version' => '7.4.0'],
    'fast-deep-equal' => ['version' => '3.1.3'],
    '@mapbox/geojson-normalize' => ['version' => '0.1.0'],
    '@mapbox/mapbox-gl-draw/dist/mapbox-gl-draw.min.css' => ['version' => '1.5.2', 'type' => 'css'],
    'wgs84' => ['version' => '0.0.0'],
    '@turf/meta' => ['version' => '7.4.0'],
    '@turf/helpers' => ['version' => '7.4.0'],
    '@turf/clone' => ['version' => '7.4.0'],
    '@turf/boolean-point-in-polygon' => ['version' => '7.4.0'],
    'point-in-polygon-hao' => ['version' => '1.2.4'],
    '@turf/invariant' => ['version' => '7.4.0'],
    'robust-predicates' => ['version' => '3.0.3'],
    'mapbox-gl/dist/mapbox-gl.css' => ['version' => '3.34.2', 'type' => 'css'],
    '@mapbox/mapbox-gl-draw/dist/mapbox-gl-draw.css' => ['version' => '1.5.2', 'type' => 'css'],
];
