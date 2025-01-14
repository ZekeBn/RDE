// Productos

// Evento para el botón de agregar producto
document.addEventListener("DOMContentLoaded", function () {

  // Captura el input con el barcode para filtrar en opciones del select
  document.getElementById("barcode").addEventListener("keydown", function (event) {

    if (event.key === "Enter" || event.key === "Tab") {

      event.preventDefault();
      var inputValue = this.value.toLowerCase();
      var selectElement = document.getElementById("producto");
      var options = selectElement.options;
      var matchFound = false;

      for (var i = 0; i < options.length; i++) {
        var barcode = options[i].getAttribute("data-barcode");

        if (barcode && barcode.toLowerCase().includes(inputValue)) {
          options[i].selected = true;
          matchFound = true;
          break;
        } else {
          options[i].selected = false;
        }
      }

      if (!matchFound) {
        selectElement.selectedIndex = 0;
      }

      // Limpia el campo de descripción y el campo del producto
      if (matchFound) {
        document.getElementById("producto").value = "";       
      }
      // Limpiar el campo de barcode después de procesar
      this.value = ""; // Limpia el campo de barcode
    }
  });
});
