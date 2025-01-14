var gralZone = new Array();
var marker;
var latLng;



var longitud = document.getElementById('longitud');
var latitud = document.getElementById('latitud');

// Cargar el mapa
const map = new google.maps.Map(document.getElementById("map"), {
    zoom: 12,
    center: { lat: -25.283246, lng: -57.567574 },
    mapTypeId: "terrain",
});
const coordinates_area = document.getElementById('coordinates');

function updateMarkerPosition(latLng) {
    longitud.value = latLng.lng();
    latitud.value = latLng.lat();
}

// ACTUALIZO LA POSICION DEL MARCADOR
function updateMarker(location) {
    marker.setPosition(location);
    updateMarkerPosition(location);
}

const drawingManager = new google.maps.drawing.DrawingManager({
    drawingControl: true,
    drawingControlOptions: {
        position: google.maps.ControlPosition.TOP_CENTER,
        drawingModes: [
            google.maps.drawing.OverlayType.POLYGON,
        ],
    }
});

latLng = new google.maps.LatLng(-25.282197, -57.635099999999966);
// funcion para verificar un punto dado si cae dentro de un poligono
function initMap(polygons, coord_data = '') {
    drawingManager.setMap(map);
    // CREACION DEL MARCADOR  
    marker = new google.maps.Marker({
        position: latLng,
        title: 'Arrastra el marcador si quieres moverlo',
        map: map,
        draggable: true
    });

    if (polygons) {

        var a_polygons = ob_polygons = [];
        // var ob_polygons;

        var customCoords = coord_data.coordinates;
        customCoords.forEach((element, index) => {
            var elem = element[0];
            elem = JSON.parse(elem);
            a_polygons.push(elem);

            var bermudaTriangle = new google.maps.Polygon({
                paths: elem,
                strokeColor: coord_data.coord_color[index],
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: coord_data.coord_color[index],
                fillOpacity: 0.35,
                content: coord_data.coord_name[index],
                id_map: coord_data.coord_id[index],
            });

            ob_polygons.push(bermudaTriangle);
            /**
             * muestra en el mapa los poligonos, pero no se puede "clickar" sobre ellos para verificar que un punto este dentro del poligono
             * Esta funcion puede estar fuera de initMap() y aun asi va funcionar (va pintar los poligonos en el mapa)
             */
            // (a falta de mejor palabra) "Poligonizar" los puntos.
            bermudaTriangle.setMap(map);
            bermudaTriangle.addListener('click', function(event) {
                if (google.maps.geometry.poly.containsLocation(event.latLng, bermudaTriangle)) {
                    alert('dentro del poligono: ${this.content} con ID ${this.id_map}');
                }
                updateMarker(event.latLng);
            });

            // obtener la posicion del puntero al finalizar
            google.maps.event.addListener(marker, 'dragend', function(event) {
                console.log(bermudaTriangle);
                if (google.maps.geometry.poly.containsLocation(event.latLng, bermudaTriangle)) {
                    alert('dentro del poligono: ${bermudaTriangle.content} con ID ${bermudaTriangle.id_map}');
                }
                updateMarkerPosition(marker.getPosition());
            });
        });

        google.maps.event.addListener(map, 'click', function(event) {
            alert('No esta dentro de un area');
            updateMarker(event.latLng);
        });
    } else {

        google.maps.event.addListener(map, 'click', function(event) {
            updateMarker(event.latLng);
        });

        // obtener la posicion del puntero al finalizar 
        google.maps.event.addListener(marker, 'dragend', function(event) {
            updateMarkerPosition(marker.getPosition());
        });
    }




    google.maps.event.addListener(drawingManager, 'polygoncomplete', function(polygon) {
        // assuming you want the points in a div with id="info"
        var newZone = new Array();

        for (var i = 0; i < polygon.getPath().getLength(); i++) {
            var latLng = polygon.getPath().getAt(i).toUrlValue(6);
            latLng = latLng.split(',');
            latLng = '{"lat": ${latLng[0]}, "lng": ${latLng[1]}}'
            newZone.push(latLng);
        }
        gralZone.push('${newZone}---');

        coordinates_area.value = gralZone;
    });

    initAutocomplete();

}

// inicializar el mapa
// google.maps.event.addDomListener(window, 'load', initMap);

window.addEventListener('load', (event) => {
    coordenadas();
});


function initAutocomplete() {
    // Create the search box and link it to the UI element.
    const input = document.getElementById("pac-input");
    const searchBox = new google.maps.places.SearchBox(input);
    map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
    // Bias the SearchBox results towards current map's viewport.
    map.addListener("bounds_changed", () => {
        searchBox.setBounds(map.getBounds());
    });
    // let markers = [];
    // Listen for the event fired when the user selects a prediction and retrieve
    // more details for that place.
    searchBox.addListener("places_changed", () => {
        const places = searchBox.getPlaces();


        if (places.length == 0) {
            return;
        }
        // Clear out the old markers.
        // markers.forEach((marker) => {
        //     marker.setMap(null);
        // });
        // markers = [];
        // For each place, get the icon, name and location.
        const bounds = new google.maps.LatLngBounds();
        places.forEach((place) => {
            if (!place.geometry || !place.geometry.location) {
                return;
            }

            updateMarker(place.geometry.location);

            if (place.geometry.viewport) {
                // Only geocodes have viewport.
                bounds.union(place.geometry.viewport);
            } else {
                bounds.extend(place.geometry.location);
            }
        });
        map.fitBounds(bounds);
        // marker = new google.maps.Marker({
        //     position: bounds,
        //     title: 'Arrastra el marcador si quieres moverlo',
        //     map: map,
        //     draggable: true
        // });
    });
}