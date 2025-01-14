var gralZone = new Array();
var marker;
var latLng;
var checkMark = true;					 


var longitud = document.getElementById('longitud');
var latitud = document.getElementById('latitud');

// Cargar el mapa
/*const map = new google.maps.Map(document.getElementById("map"), {
    zoom: 12,
    center: { lat: -25.283246, lng: -57.567574 },
    mapTypeId: "terrain",
});*/
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


latLng = new google.maps.LatLng(latCliente, lngCliente);
//latLng = new google.maps.LatLng(-25.282197, -57.635099999999966);
// funcion para verificar un punto dado si cae dentro de un poligono
function initMap(polygons, coord_data = '') {
    // CREACION DEL MARCADOR  
    marker = new google.maps.Marker({
        position: latLng,
        title: 'Arrastra el marcador si quieres moverlo',
        map: map,
        draggable: true,
		animation: google.maps.Animation.DROP
    });

    if (polygons) {

        //var a_polygons = ob_polygons = [];
        // var ob_polygons;

        var customCoords = coord_data.full;
        customCoords.forEach((element) => {
            var coordinates = element.coordinates;
            coordinates = JSON.parse(coordinates);
            //a_polygons.push(coordinates);

            var bermudaTriangle = new google.maps.Polygon({
                paths: coordinates,
                strokeColor: element.color,
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: element.color,
                fillOpacity: 0.35,
                content: element.name,
                id_map: element.id,
            });

           // ob_polygons.push(bermudaTriangle);
            /**
             * muestra en el mapa los poligonos, pero no se puede "clickar" sobre ellos para verificar que un punto este dentro del poligono
             * Esta funcion puede estar fuera de initMap() y aun asi va funcionar (va pintar los poligonos en el mapa)
             */
            // (a falta de mejor palabra) "Poligonizar" los puntos.
            bermudaTriangle.setMap(map);
            bermudaTriangle.addListener('click', function(event) {
                if (google.maps.geometry.poly.containsLocation(event.latLng, bermudaTriangle)) {
                    //alert(`dentro del poligono: ${this.content} con ID ${this.id_map}`);
					//alert(this.id_map);
					marcar(`${this.id_map}`);
                }else{
					//marcar('');
				}
                updateMarker(event.latLng);
            });

            // obtener la posicion del puntero al finalizar
            google.maps.event.addListener(marker, 'dragend', function(event) {
                console.log(bermudaTriangle);
                if (google.maps.geometry.poly.containsLocation(event.latLng, bermudaTriangle)) {
					checkMark = false;				  
                    //alert(`dentro del poligono: ${bermudaTriangle.content} con ID ${bermudaTriangle.id_map}`);
					//alert(`${bermudaTriangle.id_map}`);
					marcar(`${bermudaTriangle.id_map}`);
                }else{
					//marcar('');
				}
                updateMarkerPosition(marker.getPosition());
            });
        });

        google.maps.event.addListener(map, 'click', function(event) {
            //alert('No esta dentro de un area');
			marcar('');
            updateMarker(event.latLng);
        });
        // obtener la posicion del puntero al finalizar y verificar si esta fuera de algún polígono
        google.maps.event.addListener(marker, 'dragend', function(event) {
            if (checkMark) {
                alert('Fuera del area de cobertura.');
				marcar('');
            }
            checkMark = true;
            updateMarkerPosition(marker.getPosition());
        });
    } else {

        google.maps.event.addListener(map, 'click', function(event) {
			 //alert('No esta dentro de un area');
			 //marcar('');
            updateMarker(event.latLng);
        });

        // obtener la posicion del puntero al finalizar 
        google.maps.event.addListener(marker, 'dragend', function(event) {
			//alert('No esta dentro de un area');
			//marcar('');
            updateMarkerPosition(marker.getPosition());
        });
    }


    initAutocomplete();

}

// inicializar el mapa
// google.maps.event.addDomListener(window, 'load', initMap);

window.addEventListener('load', (event) => {
    coordenadas();
});


function initAutocomplete() {
    // asignar la barra de busqueda
    const input = document.getElementById("pac-input");
    const searchBox = new google.maps.places.SearchBox(input);
    map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
    
	// "meter" dentro del mapa la barra de busqueda
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

            //updateMarker(place.geometry.location);

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