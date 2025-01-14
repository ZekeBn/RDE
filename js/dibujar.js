var selectedShape;
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


var polyOptions = {
    strokeWeight: 0,
    fillOpacity: 0.45,
    editable: true,
    draggable: true,
    fillColor: '#0AA840'
};

const drawingManager = new google.maps.drawing.DrawingManager({
    drawingControl: true,
    drawingControlOptions: {
        position: google.maps.ControlPosition.TOP_CENTER,
        drawingModes: [
            google.maps.drawing.OverlayType.POLYGON,
        ],
    },
    polygonOptions: polyOptions
});

function setSelection(shape) {
    selectedShape = shape;
    shape.setEditable(true);
}

function deleteSelectedShape() {
    if (selectedShape) {
        coordinates_area.value = '';
        selectedShape.setMap(null);
    }
}
latLng = new google.maps.LatLng(-25.282197, -57.635099999999966);
// funcion para verificar un punto dado si cae dentro de un poligono
function initMap(polygons, coord_data = '') {
    drawingManager.setMap(map);

    if (polygons) {

        var a_polygons = ob_polygons = [];
        // var ob_polygons;

        var customCoords = coord_data.full;
        customCoords.forEach((element) => {
            var coordinates = element.coordinates;
            coordinates = JSON.parse(coordinates);
            a_polygons.push(coordinates);

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

            ob_polygons.push(bermudaTriangle);
            bermudaTriangle.setMap(map);
        });
    }

    drawingManager.addListener('polygoncomplete', function(polygon) {
        // assuming you want the points in a div with id="info"
        deleteSelectedShape();
        polygon.addListener('mouseup', function() {
            polygon_area(polygon)
        });

        setSelection(polygon);

        polygon_area(polygon);

        drawingManager.setDrawingMode(null);
    });

    google.maps.event.addDomListener(document.getElementById('delete-poly'), 'click', deleteSelectedShape);

    initAutocomplete();

}


function polygon_area(polygon) {
    var newZone = new Array();
    for (var i = 0; i < polygon.getPath().getLength(); i++) {
        var latLng = polygon.getPath().getAt(i).toUrlValue(6);
        // assuming you want the points in a div with id="info"
        latLng = latLng.split(',');
        latLng = `{"lat": ${latLng[0]}, "lng": ${latLng[1]}}`
        newZone.push(latLng);
    }
    coordinates_area.value = newZone;
}


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

    searchBox.addListener("places_changed", () => {
        const places = searchBox.getPlaces();

        if (places.length == 0) {
            return;
        }
        // For each place, get the icon, name and location.
        const bounds = new google.maps.LatLngBounds();
        places.forEach((place) => {
            if (!place.geometry || !place.geometry.location) {
                return;
            }

            if (place.geometry.viewport) {
                // Only geocodes have viewport.
                bounds.union(place.geometry.viewport);
            } else {
                bounds.extend(place.geometry.location);
            }
        });
        map.fitBounds(bounds);
    });
}