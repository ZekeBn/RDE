<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script>
	function myFunction2(event) {
		event.preventDefault();
		var idtipo_servicio = $("#idtipo_servicio").val();
		if(idtipo_servicio) {
			var div,ul, li, a, i;
			div = document.getElementById("myDropdown2");
			a = div.getElementsByTagName("a");
			for (i = 0; i < a.length; i++) {
				txtValue = a[i].textContent || a[i].innerText;
				idtipo_servicio_hidden = a[i].getAttribute('data-hidden-servicio');
				if ( idtipo_servicio_hidden==idtipo_servicio ) {
					a[i].style.display = "block";
				} else {
					a[i].style.display = "none";
				}
			}
		}else{
			var div,ul, li, a, i;
                    div = document.getElementById("myDropdown2");
                    a = div.getElementsByTagName("a");
                    for (i = 0; i < a.length; i++) {
						a[i].style.display = "block";
                    }
		}
		document.getElementById("myInput2").classList.toggle("show");
		document.getElementById("myDropdown2").classList.toggle("show");
		div = document.getElementById("myDropdown2");
		$("#myInput2").focus();
		

			
		$(document).mousedown(function(event) {
			var target = $(event.target);
			var myInput = $('#myInput2');
			var myDropdown = $('#myDropdown2');
			var div = $("#lista_proveedores");
			var button = $("#iddepartameto");
			// Verificar si el clic ocurrió fuera del elemento #my_input
			if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown2").length && myInput.hasClass('show')) {
			// Remover la clase "show" del elemento #my_input
			myInput.removeClass('show');
			myDropdown.removeClass('show');
			}
			
		});
	}

	function filterFunction2(event) {
		event.preventDefault();
		var idtipo_servicio = $("#idtipo_servicio").val();
		var input, filter, ul, li, a, i;
		input = document.getElementById("myInput2");
		filter = input.value.toUpperCase();
		div = document.getElementById("myDropdown2");
		a = div.getElementsByTagName("a");
		for (i = 0; i < a.length; i++) {
			txtValue = a[i].textContent || a[i].innerText;
			rucValue = a[i].getAttribute('data-hidden-value');
			idtipo_servicio_hidden = a[i].getAttribute('data-hidden-servicio');
			if(parseInt(idtipo_servicio) > 0){
				if( idtipo_servicio_hidden  == idtipo_servicio && (txtValue.toUpperCase().indexOf(filter) > -1 || rucValue.indexOf(filter) > -1  || filter =="")) {
					a[i].style.display = "block";
				} else {
					a[i].style.display = "none";
				}
			}else{
				if(txtValue.toUpperCase().indexOf(filter) > -1 || rucValue.indexOf(filter) > -1 ) {
					a[i].style.display = "block";
				} else {
					a[i].style.display = "none";
				}
			}

            
            
		}
	}
    </script>
</head>
<body>
    
</body>
</html>