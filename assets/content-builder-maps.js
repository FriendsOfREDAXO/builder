/**
 * OpenLayers Map Integration for YForm Content Builder
 */

(function() {
    'use strict';

    function initMaps() {
        if (typeof ol === 'undefined') {
            // Wait for OpenLayers to be available
            setTimeout(initMaps, 200);
            return;
        }

        var mapElements = document.querySelectorAll('.yform-cb-map');
        
        mapElements.forEach(function(el) {
            if (el.dataset.mapInitialized) return;
            el.dataset.mapInitialized = 'true';

            var options = JSON.parse(el.dataset.mapOptions || '{}');
            var markers = JSON.parse(el.dataset.mapMarkers || '[]');
            
            // Map settings
            var center = [parseFloat(options.center_lng || 10.4515), parseFloat(options.center_lat || 51.1657)];
            var zoom = parseInt(options.zoom || 12);
            var tileUrl = options.tile_url || 'https://{a-c}.tile.openstreetmap.org/{z}/{x}/{y}.png';

            // Create Map
            var map = new ol.Map({
                target: el, // Use element itself
                layers: [
                    new ol.layer.Tile({
                        source: new ol.source.OSM({
                            url: tileUrl
                        })
                    })
                ],
                view: new ol.View({
                    center: ol.proj.fromLonLat(center),
                    zoom: zoom
                })
            });

            // Ensure map size is calculated (important in backend)
            setTimeout(function() { 
                map.updateSize(); 
            }, 300);

            // Add Markers
            if (markers.length > 0) {
                var vectorSource = new ol.source.Vector();
                
                markers.forEach(function(marker) {
                    if (!marker.lat || !marker.lng) return;
                    
                    var feature = new ol.Feature({
                        geometry: new ol.geom.Point(ol.proj.fromLonLat([parseFloat(marker.lng), parseFloat(marker.lat)])),
                        title: marker.title || '',
                        description: marker.description || ''
                    });

                    // Custom Icon if provided
                    if (marker.icon_url) {
                        feature.setStyle(new ol.style.Style({
                            image: new ol.style.Icon({
                                anchor: [0.5, 46],
                                anchorXUnits: 'fraction',
                                anchorYUnits: 'pixels',
                                src: marker.icon_url
                            })
                        }));
                    } else {
                        // Default red marker
                        feature.setStyle(new ol.style.Style({
                            image: new ol.style.Icon({
                                anchor: [0.5, 1],
                                anchorXUnits: 'fraction',
                                anchorYUnits: 'fraction',
                                src: 'https://openlayers.org/en/latest/examples/data/icon.png'
                            })
                        }));
                    }

                    vectorSource.addFeature(feature);
                });

                var vectorLayer = new ol.layer.Vector({
                    source: vectorSource
                });
                map.addLayer(vectorLayer);

                // Auto-fit if multiple markers
                if (markers.length > 1) {
                    var extent = vectorSource.getExtent();
                    map.getView().fit(extent, { padding: [50, 50, 50, 50], maxZoom: 15 });
                }
            }

            // Popup logic
            var container = document.getElementById('popup-' + el.id);
            var content = document.getElementById('popup-content-' + el.id);
            var closer = document.getElementById('popup-closer-' + el.id);

            if (container && content && closer) {
                var overlay = new ol.Overlay({
                    element: container,
                    autoPan: true,
                    autoPanAnimation: { duration: 250 }
                });
                map.addOverlay(overlay);

                closer.onclick = function() {
                    overlay.setPosition(undefined);
                    closer.blur();
                    return false;
                };

                map.on('singleclick', function(evt) {
                    var feature = map.forEachFeatureAtPixel(evt.pixel, function(feature) {
                        return feature;
                    });

                    if (feature) {
                        var coordinates = feature.getGeometry().getCoordinates();
                        content.innerHTML = '<strong>' + (feature.get('title') || '') + '</strong><br>' + (feature.get('description') || '');
                        overlay.setPosition(coordinates);
                    } else {
                        overlay.setPosition(undefined);
                    }
                });

                // Change mouse cursor over markers
                map.on('pointermove', function(e) {
                    var pixel = map.getEventPixel(e.originalEvent);
                    var hit = map.hasFeatureAtPixel(pixel);
                    map.getTargetElement().style.cursor = hit ? 'pointer' : '';
                });
            }
        });
    }

    // Support for REDAXO PJAX / Backend UI updates
    if (typeof jQuery !== 'undefined') {
        $(document).on('rex:ready', function() { initMaps(); });
    }
    
    // Initial load
    if (document.readyState === 'loading') {
        window.addEventListener('load', initMaps);
    } else {
        initMaps();
    }

})();
                var vectorSource = new ol.source.Vector();
                
                markers.forEach(function(marker) {
                    if (!marker.lat || !marker.lng) return;
                    
                    var feature = new ol.Feature({
                        geometry: new ol.geom.Point(ol.proj.fromLonLat([parseFloat(marker.lng), parseFloat(marker.lat)])),
                        title: marker.title || '',
                        description: marker.description || ''
                    });

                    // Custom Icon if provided
                    if (marker.icon_url) {
                        feature.setStyle(new ol.style.Style({
                            image: new ol.style.Icon({
                                anchor: [0.5, 46],
                                anchorXUnits: 'fraction',
                                anchorYUnits: 'pixels',
                                src: marker.icon_url
                            })
                        }));
                    } else {
                        // Default red marker
                        feature.setStyle(new ol.style.Style({
                            image: new ol.style.Icon({
                                anchor: [0.5, 1],
                                anchorXUnits: 'fraction',
                                anchorYUnits: 'fraction',
                                src: 'https://openlayers.org/en/latest/examples/data/icon.png'
                            })
                        }));
                    }

                    vectorSource.addFeature(feature);
                });

                var vectorLayer = new ol.layer.Vector({
                    source: vectorSource
                });
                map.addLayer(vectorLayer);

                // Auto-fit if multiple markers
                if (markers.length > 1) {
                    var extent = vectorSource.getExtent();
                    map.getView().fit(extent, { padding: [50, 50, 50, 50], maxZoom: 15 });
                }
            }

            // Popup logic
            var container = document.getElementById('popup-' + el.id);
            var content = document.getElementById('popup-content-' + el.id);
            var closer = document.getElementById('popup-closer-' + el.id);

            if (container && content && closer) {
                var overlay = new ol.Overlay({
                    element: container,
                    autoPan: true,
                    autoPanAnimation: { duration: 250 }
                });
                map.addOverlay(overlay);

                closer.onclick = function() {
                    overlay.setPosition(undefined);
                    closer.blur();
                    return false;
                };

                map.on('singleclick', function(evt) {
                    var feature = map.forEachFeatureAtPixel(evt.pixel, function(feature) {
                        return feature;
                    });

                    if (feature) {
                        var coordinates = feature.getGeometry().getCoordinates();
                        content.innerHTML = '<strong>' + (feature.get('title') || '') + '</strong><br>' + (feature.get('description') || '');
                        overlay.setPosition(coordinates);
                    } else {
                        overlay.setPosition(undefined);
                    }
                });

                // Change mouse cursor over markers
                map.on('pointermove', function(e) {
                    var pixel = map.getEventPixel(e.originalEvent);
                    var hit = map.hasFeatureAtPixel(pixel);
                    map.getTargetElement().style.cursor = hit ? 'pointer' : '';
                });
            }
        });
    }

    // Load OL if not present and init
    if (typeof ol === 'undefined') {
        var script = document.createElement('script');
        // Versuche lokale Version aus dem Addon-Assets-Pfad zu laden
        // Der Pfad muss relativ zum aktuellen Dokument oder absolut sein.
        // Im REDAXO-Kontext ist /assets/addons/builder/vendor/openlayers/ol.js oft sicher.
        script.src = '/assets/addons/builder/vendor/openlayers/ol.js';
        script.onerror = function() {
            // Fallback auf CDN falls lokal nicht gefunden
            console.warn('Local OpenLayers not found, falling back to CDN');
            var cdnScript = document.createElement('script');
            cdnScript.src = 'https://cdn.jsdelivr.net/npm/ol@v7.1.0/dist/ol.js';
            cdnScript.onload = initMaps;
            document.head.appendChild(cdnScript);
        };
        script.onload = initMaps;
        document.head.appendChild(script);
        
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = '/assets/addons/builder/vendor/openlayers/ol.css';
        document.head.appendChild(link);
    } else {
        initMaps();
    }

    // Support for REDAXO PJAX / Backend UI updates
    $(document).on('rex:ready', function() { initMaps(); });
    // In some cases (frontend builder), we might need this
    window.addEventListener('load', initMaps);

})();
