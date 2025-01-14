function validar_fecha_orden(fecha) {
			var errores = '';
			var valido = 'S';
			var fe = fecha.split("-");
			var ano = fe[0];
			var mes = fe[1] - 1;
			var meshtml = fe[1];
			var dia = fe[2];
			var f1 = new Date(ano, mes, dia);
			var f2 = new Date(<?php echo date("Y"); ?>, <?php echo date("m") - 1; ?>, <?php echo date("d"); ?>);

			// fecha no puede estar en el futuro
			if (f1 > f2) {
				valido = 'N';
				errores = 'La Fecha de orden (' + dia + '/' + meshtml + '/' + ano + ') no puede estar en el futuro.';
			}
			var fecha_vencimiento = fecha_sumar_dias_js(fecha);
			$("#fecha_entrega").val(fecha_vencimiento);
		}

		function fecha_sumar_dias_js(fechaString) {
			var selectedOption = $("#idproveedor").find('option:selected');
			var dias = selectedOption.data('hidden-diasvence');
			// console.log(dias);
			// Convertir la fecha a un objeto Date con la zona horaria local
			var fecha = new Date(Date.parse(fechaString + 'T00:00:00'));

			// console.log(fecha);
			fecha.setDate(fecha.getDate() + dias); // Suma la cantidad de días a la fecha
			var year = fecha.getFullYear();
			var month = ('0' + (fecha.getMonth() + 1)).slice(-2); // Ajusta el mes para obtener el formato 'MM'
			var day = ('0' + fecha.getDate()).slice(-2); // Ajusta el día para obtener el formato 'DD'

			var fechaFinal = year + '-' + month + '-' + day; // Formato 'Y-m-d'
			// console.log(fechaFinal);
			return fechaFinal; // Muestra la fecha resultante en la consola

			// Si deseas devolver la fecha final en lugar de mostrarla en la consola, puedes usar 'return fechaFinal;'
		}

		function validar_fecha_vencimiento() {

			// var tipo_origen = $("#idtipo_origen").val(); // 1=local 2=importacion

			// if(tipo_origen == 1) {
			fecha = $("#fecha_entrega").val();
			fecha_compracion = $("#fecha").val();
			var errores = '';
			// var fecha = $("#fecha_compra").val();
			// var vencimiento_timbrado = $("#vto_timbrado").val()
			var valido = 'S';
			var fe = fecha.split("-");
			var ano = fe[0];
			var mes = fe[1] - 1;
			var meshtml = fe[1];
			var dia = fe[2];
			var f1 = new Date(ano, mes, dia);
			var fe1 = fecha_compracion.split("-");
			var ano1 = fe1[0];
			var mes1 = fe1[1] - 1;
			var meshtml1 = fe1[1];
			var dia1 = fe1[2];
			var fdesde = new Date(ano1, mes1, dia1);
			// fecha no puede estar en el futuro

			// la fecha no puede ser menor a la fecha desde
			if (f1 < fdesde) {
				valido = 'N';
				errores = 'La Fecha de Entrega estimada (' + dia + '/' + meshtml + '/' + ano + ') no puede ser menor a la fecha de la Orden:' + dia1 + '/' + meshtml1 + '/' + ano1 + '.';
			}

			if (valido == 'N') {
				alerta_modal('Incorrecto', errores);
				$("#fecha_compra").val('');
				fecha = $("#fecha_entrega").val("");
			} else {
				//cargavto();
			}

			// }
		}

		function cerrar_pop() {
			$("#modal_ventana").modal("hide");
		}

		function buscar_cotizacion_moneda() {
			var idmoneda = $("#idtipo_moneda").val();
			var parametros = {
				"idmoneda": idmoneda
			};
			var id_moneda_nacional = <?php echo $id_moneda_nacional; ?>;
			if (id_moneda_nacional != idmoneda) {
				console.log(parametros);
				$.ajax({
					data: parametros,
					url: './buscar_cotizaciones_modal.php',
					type: 'post',
					beforeSend: function() {

					},
					success: function(response) {
						alerta_modal("Cotizaciones disponibles", response);
					}
				});
			}
		}

		function sumarDiasAFecha(fecha, dias) {
			var nuevaFecha = new Date(fecha);
			nuevaFecha.setDate(nuevaFecha.getDate() + dias);
			return nuevaFecha;
		}

		function formatearFechaParaInput(fecha) {
			var year = fecha.getFullYear();
			var month = ('0' + (fecha.getMonth() + 1)).slice(-2);
			var day = ('0' + fecha.getDate()).slice(-2);
			return `${year}-${month}-${day}`;
		}

		function verificar_tipo_servicio(valor) {}

		function cambia_proveedor(idtipo_origen, idmoneda, idproveedor, nombre, idtipo_compra, dias_entrega, diasvence) {
			// alerta_modal("contenido",idtipo_origen+ " "+idmoneda);
			$('#idproveedor').html($('<option>', {
				value: idproveedor,
				text: nombre,
				'data-hidden-diasvence': diasvence
			}));

			var myInput = $('#myInput2');
			var myDropdown = $('#myDropdown2');
			myInput.removeClass('show');
			myDropdown.removeClass('show');
			$("#idtipo_moneda").val(idmoneda);
			$("#idtipo_origen").val(idtipo_origen);
			$("#idtipocompra").val(idtipo_compra);
			if (dias_entrega > 0) {
				var fechaInicial = new Date();
				var fechaFinal = sumarDiasAFecha(fechaInicial, dias_entrega);
				fechaFinal = formatearFechaParaInput(fechaFinal)
				$("#fecha_entrega").val(fechaFinal)
			}
			verificar_tipo(idtipo_origen);
			verificar_cotizacion_moneda();
		}

		function cargar_cotizacion() {
			var parametros = {
				"idmoneda": $("#idtipo_moneda").val()
			};
			$.ajax({
				data: parametros,
				url: './cotizacion_add_modal.php',
				type: 'post',
				beforeSend: function() {

				},
				success: function(response) {
					alerta_modal("Agregar Cotizacion", response);
				}
			});
		}

		function verificar_cotizacion_moneda() {
			var parametros = {
				"idmoneda": $("#idtipo_moneda").val()
			};
			$.ajax({
				data: parametros,
				url: './cotizaciones_hoy_modal.php',
				type: 'post',
				beforeSend: function() {

				},
				success: function(response) {
					console.log(response);
					if (JSON.parse(response)['success'] == false) {
						alerta_modal("Alerta!", JSON.parse(response)['error']);
						$("#idcot").css('border', '1px solid red');
					} else {

						var cotiza = JSON.parse(response)['cotiza'];
						if (cotiza == true) {
							var idcot = JSON.parse(response)['idcot'];
							var cotizacion = JSON.parse(response)['cotizacion'];
							var fecha = JSON.parse(response)['fecha'];
							console.log(fecha);
							$('#idcot').html($('<option>', {
								value: idcot,
								text: parseFloat(cotizacion).toFixed(2)
							}));

							// Seleccionar opción
							$('#idcot').val(idcot);
							$('#idcotizacion').val(idcot);
							$('#idcot').prop('readonly', true);
							$("#idcot").css('border', '1px solid #ccc');
							$("#fecha_cotizacion_text").removeClass("hide");
							$("#fecha_cotizacion").html(fecha);
							$("#fecha_cotizacion").css("display", "inline");
						} else {
							$('#idcot').html("");
							$('#idcot').prop('readonly', true);
							$("#idcot").css('border', '1px solid #ccc');
						}

						$('#idcot').on('mousedown', function(event) {
							// Evitar que el select se abra
							event.preventDefault();
						});
						$("#idcot").css('background', '#EEE');
						$("#idcot").css('cursor', 'pointer');
					}
				}
			});
		}

		function myFunction2(event) {
			event.preventDefault();
			var idtipo_servicio = $("#idtipo_servicio").val();
			if (idtipo_servicio) {
				var div, ul, li, a, i;
				div = document.getElementById("myDropdown2");
				a = div.getElementsByTagName("a");
				for (i = 0; i < a.length; i++) {
					txtValue = a[i].textContent || a[i].innerText;
					idtipo_servicio_hidden = a[i].getAttribute('data-hidden-servicio');
					if (idtipo_servicio_hidden == idtipo_servicio) {
						a[i].style.display = "block";
					} else {
						a[i].style.display = "none";
					}
				}
			} else {
				var div, ul, li, a, i;
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
				
				if (parseInt(idtipo_servicio) > 0) {
					if (idtipo_servicio_hidden == idtipo_servicio && (txtValue.toUpperCase().indexOf(filter) > -1 || rucValue.indexOf(filter) > -1 || filter == "")) {
						a[i].style.display = "block";
					} else {
						a[i].style.display = "none";
					}
				} else {
					if (txtValue.toUpperCase().indexOf(filter) > -1 || rucValue.indexOf(filter) > -1) {
						a[i].style.display = "block";
					} else {
						a[i].style.display = "none";
					}
				}
			}
		}

		function alerta_modal(titulo, mensaje) {
			$('#modal_ventana').modal('show');
			$("#modal_titulo").html(titulo);
			$("#modal_cuerpo").html(mensaje);
		}

		function verificar_tipo(clase) {
			var idimportacion = <?php echo $idtipo_origen_importacion; ?>;
			if (clase == idimportacion) {
				$("#monedas").show();
			} else {
				<?php if ($multimoneda_local == "S") { ?>
					$("#monedas").show();
				<?php } else { ?>
					$("#monedas").hide();
				<?php } ?>
			}
		}

		function recuperar_cambio() {

			var parametros = {
				"idmoneda": $("#idtipo_moneda").val()
			};
			$.ajax({
				data: parametros,
				url: 'cotizaciones_hoy.php',
				type: 'post',
				beforeSend: function() {
					// $("#listaprodudiv").html('Cargando...');  
				},
				success: function(response) {
					// $("#listaprodudiv").html(response);
					if (JSON.parse(response)['success'] == false) {
						alerta_modal("Alerta!", JSON.parse(response)['error']);
					} else {
						$("#idcot").val(JSON.parse(response)['cotizacion']);
						$("#cotRefHelp").html("Fecha: " + JSON.parse(response)['fecha']);
					}
				}
			});
		}

		window.onload = function() {

			<?php if ($_POST) { ?>
				verificar_tipo($("#idtipo_origen").val());
			<?php } ?>
			if ($("#idtipo_moneda").val() != "") {
				verificar_cotizacion_moneda();
			}

			$('#idproveedor').on('mousedown', function(event) {
				// Evitar que el select se abra
				event.preventDefault();
			});
			<?php if ($idtipo_origen_importacion == 0) { ?>
				alerta_modal("Alerta !", "<h2>El Elemento Tipo Origen: Importacion no fue Creado.</h2>");

			<?php } ?>
		};