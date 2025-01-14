function alertar(titulo,error,tipo,boton){
	swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
}
function alertar_redir(titulo,error,tipo,boton,redir){
	swal({
	  title: titulo,
	  text: error,
	  type: tipo,
	  /*showCancelButton: true,*/
	  confirmButtonClass: "btn-danger",
	  confirmButtonText: boton,
	 /* cancelButtonText: "No, cancel plx!",*/
	  closeOnConfirm: false,
	 /* closeOnCancel: false*/
	},
	function(isConfirm) {
	  if (isConfirm) {
		//swal("Deleted!", "Your imaginary file has been deleted.", "success");
		  document.location.href=redir;
	  } else {
		//swal("Cancelled", "Your imaginary file is safe :)", "error");
		  document.location.href=redir;
	  }
	});

}
function getQueryVariable(variable) {
   var query = window.location.search.substring(1);
   var vars = query.split("&");
   for (var i=0; i < vars.length; i++) {
       var pair = vars[i].split("=");
       if(pair[0] == variable) {
           return pair[1];
       }
   }
   return false;
}
function nl2br (str, is_xhtml) {
  var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>';
  return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}
function agregon(posicion,producto,precio,usar_lote,idregseriedptostk){
	if (posicion!=''){

		//document.getElementById('cv_'+posicion).hidden='hidden';
		//var cantidad=document.getElementById('cvender_'+posicion).value;
		var cantidad=$("#cvender_"+posicion).val();

		var precio='';
		var prod1='';
		var prod2='';
		var lote='';
		var vencimiento='';
		if(usar_lote=="S"){
			lote = $('#lote_'+idregseriedptostk).html();
			vencimiento = $('#vencimiento_'+idregseriedptostk).html();
		}

		if (cantidad==''){
			cantidad=1;

		}
        var parametros = {
                "prod" : producto,
				"cant" : cantidad,
                "precio" : precio,
				"prod_1" : prod1,
				"prod_2" : prod2,
				"lote"	: lote,
				"vencimiento" : vencimiento
        };
		console.log((parametros));
       $.ajax({
                data:  parametros,
                url:   'carrito.php',
                type:  'post',
                beforeSend: function () {
						if(prod1 > 0){

						}else{

							$("#carrito").html("Actualizando Carrito...");
						}
						$("#cvender_"+posicion).val("");
						$("#cv_"+posicion).hide();
                },
                success:  function (response) {
					$("#cv_"+posicion).show();
					$("#carrito").html(response);
					console.log(response);

					var stock = parseInt($("#hidden_stock_cantidad_"+producto).html());
					var vende_sin_stock = $("#hidden_stock_cantidad_"+producto).attr('data-hidden-value');
					var valor = stock - response;
					if (valor <= 0){
						if(vende_sin_stock =="false"){

							$("#cv_" + posicion).addClass("hide");
						}
						// $("#stock_cantidad_"+id).css("display", "none");
						$("#stock_cantidad_"+producto).html(stock - response);
					}else{

						$("#stock_cantidad_"+producto).html(stock - response);
					}
					actualiza_carrito();
                }
        });


	}


}

function busca_cliente(tipopago,idpedido){
		//shortcut.remove('space');
		var direccionurl='clientesexistentes2.php';
		var parametros = {
              "id" : 0,
			  "tipopago" : tipopago,
			  "idpedido" : idpedido
	   };
       $.ajax({
                data:  parametros,
                url:   direccionurl,
                type:  'post',
                beforeSend: function () {
                        //$("#pop1").html("Cargando...");
                },
                success:  function (response) {
						/*popupasigna();
						$("#pop1").html(response);*/
						$("#modal_titulo").html('Busqueda de Clientes');
						$("#modal_cuerpo").html(response);
						$("#blci").focus();
						/*if (document.getElementById('blci')){
							document.getElementById('blci').focus();
						}*/
                }
        });
}
function borra_carrito(){
	setCookie('chapa_cookie',"",-1);
	setCookie('ruc_cookie',"",-1);
	setCookie('razon_social_cookie',"",-1);
	setCookie('delivery_cookie',"",-1);
	setCookie('mesa_cookie',"",-1);
	setCookie('observacion_cookie',"",-1);
}
function Moneda(valor){
	valor = valor+'';
var num = valor.replace(/\./g,"");
	if(!isNaN(num)){
		num = num.toString().split("").reverse().join("").replace(/(?=\d*\.?)(\d{3})/g,"$1.");
		num = num.split("").reverse().join("").replace(/^[\.]/,"");
		res = num;
	}else{
		res = valor.replace(/[^\d\.]*/g,"");
	}
	return res;
}
function esplitear(valor){
	var txto=valor;
	if(typeof(txto) != "undefined"){
		var res = txto.split("-");
		var costo=parseInt(res[1]);
	}else{
		var costo=0;
	}
	var totalventa_real = $("#totalventa_real").val();
	var totalventa_condelivery = parseInt(totalventa_real)+parseInt(costo);
	$("#totalventa").val(totalventa_condelivery);
	$("#totalventa_box").html(Moneda(totalventa_condelivery));
	$("#montorecibido").val(totalventa_condelivery);
	$("#delioc").val(costo);
	if (costo> 0){
		//es delivery
		//$("#llevapos").show();
		//$("#cambiopara").show();
		$("#obstr").show();
		$("#vueltotr").hide();
		$("#recibidotr").hide();

	} else {
		//$("#llevapos").hide();
		//$("#cambiopara").hide();
		$("#obstr").hide();
		$("#vueltotr").show();
		$("#recibidotr").show();
	}
	actualiza_saldos();
}

function apretar(id,prod1,prod2){
		// alert(id+'-'+prod1+'-'+prod2);
		if(prod1 > 0){
			var precio = 0;
		}else{
			var html = document.getElementById("prod_"+id).innerHTML;
			var precio = document.getElementById("precio_"+id).value;
		}
        var parametros = {
                "prod" : id,
				"cant" : 1,
                "precio" : precio,
				"prod_1" : prod1,
				"prod_2" : prod2
        };
       $.ajax({
                data:  parametros,
                url:   'carrito.php',
                type:  'post',
                beforeSend: function () {
						if(prod1 > 0){
							//$("#lista_prod").html("Registrando...");
						}else{
                        	$("#prod_"+id).html("Registrando...");
							$("#carrito").html("Actualizando Carrito...");
						}
                },
                success:  function (response) {
					//alert(response);
						if(prod1 > 0 && parseInt(response) > 0){
							$("#lista_prod").html("Registrando...");
							$("#carrito").html("Actualizando Carrito...");
							document.location.href='gest_ventas_resto_caja.php?cat='+getQueryVariable('cat');
						}else{
							$("#prod_"+id).html(html);
							if(IsJsonString(response)){
								var obj = jQuery.parseJSON(response);
								if(obj.valido == 'N'){
									$('#modal_ventana').modal('show');
									$("#modal_titulo").html('No Permitido');
									$("#modal_cuerpo").html(obj.errores);
								}else{
									$("#contador_"+id).html(response);
									var stock = parseInt($("#hidden_stock_cantidad_"+id).html());
									var vende_sin_stock = $("#hidden_stock_cantidad_"+id).attr('data-hidden-value');
									var valor = stock - response;
									if (valor <= 0){
										if(vende_sin_stock == "false"){

											$("#prod_" + id).removeAttr("onClick");
										}
										// $("#stock_cantidad_"+id).css("display", "none");
										$("#stock_cantidad_"+id).html(stock - response);
									}else{

										$("#stock_cantidad_"+id).html(stock - response);
									}
								}
							}else{
								$("#contador_"+id).html(response);
							}

							actualiza_carrito();
						}
                }
        });

}
function carritocodigo(){
	var cual=document.getElementById('buscador').value;
	var cantidad=document.getElementById('cantibuscador').value;
	var precio='';
	var prod1='';
	var prod2='';

	if (cantidad==''){
			cantidad=1;

	}
	var parametros = {
                "prod" : cual,
				"cant" : cantidad,
                "precio" : precio,
				"prod_1" : prod1,
				"prod_2" : prod2
        };
	  $.ajax({
                data:  parametros,
                url:   'carrito.php',
                type:  'post',
                beforeSend: function () {
						if(prod1 > 0){

						}else{
                        	//$("#prod_"+id).html("Registrando...");
							$("#carrito").html("Actualizando Carrito...");
						}
                },
                success:  function (response) {
						if (document.getElementById('cantibuscador')){
							document.getElementById('cantibuscador').value='';
							document.getElementById('buscador').value='';
							document.getElementById('buscador').focus();
							document.getElementById('recarga').innerHTML='';
						}
						actualiza_carrito();

                }
        });

}
	//Enviamos como parametro partir 2 para indicar que puede ser un producto pesable
function carritocodigonew(){

	var cual=document.getElementById('bccode').value;
	var cantidad=document.getElementById('cantcode').value;
	var precio='';
	var prod1='';
	var prod2='';

	if (cantidad==''){
			cantidad=1;

	}
	var parametros = {
                "prod" : cual,
				"cant" : cantidad,
                "precio" : precio,
				"prod_1" : prod1,
				"prod_2" : prod2,
		        "partir"  :2
        };
	  $.ajax({
                data:  parametros,
                url:   'carrito.php',
                type:  'post',
                beforeSend: function () {
						if(prod1 > 0){

						}else{
                        	//$("#prod_"+id).html("Registrando...");
							$("#carrito").html("Actualizando Carrito...");
						}
                },
                success:  function (response) {
					//alert(response);
						 if (document.getElementById('cantcode')){
							document.getElementById('cantcode').value='';
							document.getElementById('bccode').value='';
							document.getElementById('bccode').focus();
							//document.getElementById('recarga').innerHTML='';
							//alert(response);
						}
						actualiza_carrito();

                }
        });

}
function seleccionar(producto){
		var cantidad=document.getElementById('cantidad').value;
		var precio='';
		var prod1='';
		var prod2='';

		if (cantidad==''){
			cantidad=1;

		}
        var parametros = {
                "prod" : producto,
				"cant" : cantidad,
                "precio" : precio,
				"prod_1" : prod1,
				"prod_2" : prod2
        };
       $.ajax({
                data:  parametros,
                url:   'carrito.php',
                type:  'post',
                beforeSend: function () {
						if(prod1 > 0){
							//$("#lista_prod").html("Registrando...");
						}else{
                        	//$("#prod_"+id).html("Registrando...");
							$("#carrito").html("Actualizando Carrito..");
						}
                },
                success:  function (response) {

					//alert(response);
						/*if(prod1 > 0 && parseInt(response) > 0){
						//	$("#lista_prod").html("Registrando...");
							$("#carrito").html("Actualizando Carrito...");
							//document.location.href='gest_ventas_resto_caja.php?cat=<?php echo $cat; ?>';
						}else{
							//$("#prod_"+id).html(html);
							//$("#contador_"+id).html(response);

						}*/
						if (document.getElementById('cantidad')){
							document.getElementById('cantidad').value='';
							document.getElementById('busqueda').value='';
							document.getElementById('recarga').innerHTML='';
						}
						actualiza_carrito();
                }
        });

}
function apretar_pizza(id){
		var html = document.getElementById("prod_"+id).innerHTML;
        var parametros = {
                "id" : id
        };
       $.ajax({
                data:  parametros,
                url:   'pizza.php',
                type:  'post',
                beforeSend: function () {
                      //  $("#prod_"+id).html("Cargando Opciones...");
						//$("#lista_prod").html("Cargando Opciones...");
						//$("#carrito").html("Actualizando Carrito...");
                },
                success:  function (response) {
						$("#prod_"+id).html(html);
						$("#lista_prod").html(response);
						//$("#contador_"+id).html(response);
						//actualiza_carrito();
                }
        });

}
function marcar_pizza(id,idcomb){
	prodmitad = document.getElementById('mitad_'+id);
	//alert(id);
	//alert(prodmitad.checked);
	if(prodmitad.checked){
		//prodmitad.checked='';
		$('#mitad_'+id)[0].checked = false;
		// si no queda ninguno sin marcar
		if($('input:checkbox:checked').size() == 0){
			document.getElementById('prod_1').value=0;
			document.getElementById('prod_2').value=0;
		}
		// si queda 1 sin marcar
		if($('input:checkbox:checked').size() == 1){
			// busca cual es el que desmarco
			if(document.getElementById('prod_1').value == id){
				document.getElementById('prod_1').value=document.getElementById('prod_2').value;
				document.getElementById('prod_2').value=0;
			}
			if(document.getElementById('prod_2').value == id){
				document.getElementById('prod_2').value=0;
			}
		}
	}else{
		//prodmitad.checked='checked';
		$('#mitad_'+id)[0].checked = true;
		if($('input:checkbox:checked').size() == 1){
			document.getElementById('prod_1').value=id;
			//alert(producto1);
			//apretar(id,prod1=0,prod2=0);
		}
		if($('input:checkbox:checked').size() == 2){
			document.getElementById('prod_2').value=id;
			apretar(idcomb,document.getElementById('prod_1').value,document.getElementById('prod_2').value);
		}
		if($('input:checkbox:checked').size() > 2){
			alert("Error! Solo puedes marcar 2 mitades.");
			$(".cajitasbox").each(function(){
                $(this).prop('checked',false);
				document.getElementById('prod_1').value=0;
				document.getElementById('prod_2').value=0;
            });
		}
	}

}
function actualiza_carrito(){
        var parametros = {
                "act" : 'S'
        };
		$.ajax({
                data:  parametros,
                url:   'gest_ventas_resto_carrito.php',
                type:  'post',
                beforeSend: function () {
                        $("#carrito").html("Actualizando Carrito...");
                },
                success:  function (response) {
						$("#carrito").html(response);
                }
        });
}
function borrar(idprod,txt){
			var parametros = {
                "prod" : idprod
			};
	if(window.confirm("Esta seguro que desea borrar '"+txt+"'?")){
			$.ajax({
					data:  parametros,
					url:   'carrito_borra.php',
					type:  'post',
					beforeSend: function () {
							$("#carrito").html("Actualizando Carrito...");
					},
					success:  function (response) {
							$("#carrito").html(response);
							// si existe el div de ese producto
							if ($("#contador_"+idprod).length > 0) {
								$("#contador_"+idprod).html(0);
							}
							if (document.getElementById('filtrar')){
								var al=document.getElementById('filtrar').value;
								filtra(al);

							}
							document.location.reload();
					}
			});
	}
}
function borrar_item(idventatmp,idprod,txt){
			var parametros = {
                "idventatmp" : idventatmp
			};
	if(window.confirm("Esta seguro que desea borrar '"+txt+"'?")){
			$.ajax({
					data:  parametros,
					url:   'carrito_borra.php',
					type:  'post',
					beforeSend: function () {
							$("#carrito").html("Actualizando Carrito...");
					},
					success:  function (response) {
							$("#carrito").html(response);
							// si existe el div de ese producto
							if ($("#contador_"+idprod).length > 0) {
								$("#contador_"+idprod).html(0);
							}
							if (document.getElementById('filtrar')){
								var al=document.getElementById('filtrar').value;
								filtra(al);

							}
					}
			});
	}
}

function borrar_todo(){
			var parametros = {
                "todo" : 'S'
			};
	if(window.confirm("Esta seguro que desea borrar TODO?")){
			$.ajax({
					data:  parametros,
					url:   'carrito_borra.php',
					type:  'post',
					beforeSend: function () {
							$("#carrito").html("Borrando...");
					},
					success:  function (response) {
							document.location.href='gest_ventas_resto_caja.php';
					}
			});
	}
}
function valida_ruc(){
	var ruc = document.getElementById('ruc').value;
	if(ruc == ''){
		document.getElementById('ruc').value = '44444401-7';
	}
}
function valida_rz(){
	var raz = document.getElementById('razon_social').value;
	if(raz == ''){
		document.getElementById('razon_social').value = 'Consumidor Final';
	}
}
/*
$(window).scroll(function() {
   if($(window).scrollTop() + $(window).height() == $(document).height()) {
       enfocar('chapa');
	   //setTimeout(function(){document.getElementById('chapa').click()},50);
   }
});*/
function cambiar_canal(canal){
	document.body.innerHTML='Cambiando de Canal...';
	document.location.href='gest_ventas_resto_caja.php?canal='+canal;
}
function filtrar_pizza(subcat){
	document.location.href='gest_ventas_resto_caja.php?cat=2&t='+subcat;
}
function filtrar_lomito(subcat){
	document.location.href='gest_ventas_resto_caja.php?cat=1&t='+subcat;
}
function filtrar_subcat(cat,subcat){
	document.location.href='gest_ventas_resto_caja.php?cat='+cat+'&t='+subcat;
}
// manejar cookie
function setCookie(cname,cvalue,exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires=" + d.toGMTString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for(var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function checkCookie() {
    var user=getCookie("username");
    if (user != "") {
        alert("Welcome again " + user);
    } else {
       user = prompt("Please enter your name:","");
       if (user != "" && user != null) {
           setCookie("username", user, 30);
       }
    }
}
function cambia(valor){
	if (valor==1){
		$("#nombreclie_box").show();
		$("#apellidos_box").show();
		$("#rz1").val("");
		$("#rz1_box").hide();
		$("#cedula_box").show();
	}
	if (valor==2){
		$("#nombreclie").val("");
		$("#apellidos").val("");
		$("#nombreclie_box").hide();
		$("#apellidos_box").hide();
		$("#rz1_box").show();
		$("#cedula_box").hide();
	}

}
function actualiza_saldos(){

	// recibe parametros
	var totalventa = parseInt($("#totalventa").val());
	var descuento = parseInt($("#descuento").val());
	var montorecibido = parseInt($("#montorecibido").val());
	var tarjeta = parseInt($("#tarjeta").val());
	var mediopago = $("#mediopagooc").val();
	var vueltotxt = '';
	// convierte nan
	if (isNaN(totalventa)){
		totalventa=0;
	}
	if (isNaN(descuento)){
		descuento=0;
	}
	if (isNaN(montorecibido)){
		montorecibido=0;
	}
	if (isNaN(tarjeta)){
		tarjeta=0;
	}


	// neto a cobrar
	//alert(descuento);
	//alert(totalventa);
	if(descuento <= totalventa){
		var netocobrar = totalventa-descuento;
		//alert(netocobrar);
	}else{
		descuento = 0;
		$("#descuento").val(0);
		var netocobrar = totalventa;
	}
	$("#netocobrar").html(netocobrar);


	// validaciones y conversiones segun medio de pago
	// efectivo
	if(mediopago == 1){
		$("#vueltotd").show();
		var vuelto = montorecibido-netocobrar;
		if (vuelto > 0){
			vueltotxt = Moneda(vuelto);
			$("#vueltocnt").html(vueltotxt);

		} else {
			vueltotxt = 0;
			vuelto = 0;
			$("#vueltocnt").html(vueltotxt);

		}
		$("#vuelto").val(vuelto);
		if(montorecibido < 0){
			$("#montorecibido").val(netocobrar);
		}
	}
	// tarjeta
	if(mediopago == 2){
		if(montorecibido >= netocobrar){
			$("#montorecibido").val(netocobrar);
			$("#vueltocnt").html(0);
		}
	}
	// mixto
	if(mediopago == 3){
		if(montorecibido > netocobrar){
		   $("#montorecibido").val(netocobrar);
			montorecibido = netocobrar;
		}
		tarjeta = netocobrar-montorecibido;
		$("#tarjeta").val(tarjeta);
	}
	// motivo descuento
	if($("#descuento").val() > 0){
		$("#motivodesc_box").show();
	}else{
		$("#motivodesc_box").hide();
		$("#motivo_descuento").val('');
	}


}
//ADHERENTES
function carga_adherentes(texto){
	if(texto != ''){
		shortcut.remove('space');
	}
	var direccionurl='mini_buscar_adherente.php';
	var parametros = {

			  "palabra" : texto
	   };
		$.ajax({
                data:  parametros,
                url:  direccionurl,
                type:  'post',
                beforeSend: function () {
					if($("#cargaad").html() != 'Cargando...'){
                      $("#cargaad").html('Cargando...');
					  $("#adherentebus").focus();
					}
                },
                success:  function (response) {
					  $("#adherentebus").focus();
					  $("#cargaad").html(response);

                }
        });



}
function carga_adherentes2(texto){
	if(texto.length > 3){
		carga_adherentes(texto);
		$("#adherentebus").focus();
	}
}
function este(valor,servcomb){
	var direccionurl='mini_buscar_adherente.php';
	var parametros = {

			  "idadhiere"    : valor,
			  "idservcombox" : servcomb
	   };
		$.ajax({
                data:  parametros,
                url:  direccionurl,
                type:  'post',
                beforeSend: function () {

                },
                success:  function (response) {

						$("#cargaad").html(response);

                }
        });



}
function agrega_cliente(tipopago,idpedido){
		var direccionurl='cliente_agrega.php';
		var parametros = {
              "idpedido" : idpedido,
			  "mediopago" : tipopago
	   };
       $.ajax({
                data:  parametros,
                url:   direccionurl,
                type:  'post',
                beforeSend: function () {
                    $('#modal_ventana').modal('show');
					$("#modal_titulo").html('Alta de Cliente');
					$("#modal_cuerpo").html('Cargando...');
                },
                success:  function (response) {
                    $('#modal_ventana').modal('show');
					$("#modal_titulo").html('Alta de Cliente');
					$("#modal_cuerpo").html(response);
					if (document.getElementById('ruccliente')){
						document.getElementById('ruccliente').focus();
					}
					$("#idpedido").html(idpedido);
                }
        });
}
function carga_ruc_h(idpedido){
	var vruc = $("#ruccliente").val();
	var txtbusca="Buscando...";
	var tipocobro=$("#mediopagooc").val();
	if(txtbusca != vruc){
	var parametros = {
			"ruc" : vruc
	};
	$.ajax({
			data:  parametros,
			url:   'ruc_extrae.php',
			type:  'post',
			beforeSend: function () {
				$("#ruccliente").val('Buscando...');
			},
			success:  function (response) {
				if(IsJsonString(response)){
					var obj = jQuery.parseJSON(response);
					//alert(obj.error);
					if(obj.error == ''){
						var new_ruc = obj.ruc;
						var new_rz = obj.razon_social;
						var new_nom = obj.nombre_ruc;
						var new_ape = obj.apellido_ruc;
						var idcli = obj.idcliente;
						$("#ruccliente").val(new_ruc);
						$("#nombreclie").val(new_nom);
						$("#apellidos").val(new_ape);
						$("#rz1").val(new_rz);
						var obj_json = '{"idcliente":"'+obj.idcliente+'","idsucursal_clie":"'+obj.idsucursal_clie+'"}';
						if(parseInt(idcli)>0){
							//nclie(tipocobro,idpedido);
							selecciona_cliente(obj_json,tipocobro,idpedido);
						}
					}else{
						$("#ruccliente").val(vruc);
						$("#nombreclie").val('');
						$("#apellidos").val('');
					}
				}else{

					alert(response);

				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			if (jqXHR.status === 0) {

				alert('No conectado: verifique la red.');

			} else if (jqXHR.status == 404) {

				alert('Pagina no encontrada [404]');

			} else if (jqXHR.status == 500) {

				alert('Internal Server Error [500].');

			} else if (textStatus === 'parsererror') {

				alert('Requested JSON parse failed.');

			} else if (textStatus === 'timeout') {

				alert('Tiempo de espera agotado, time out error.');

			} else if (textStatus === 'abort') {

				alert('Solicitud ajax abortada.'); // Ajax request aborted.

			} else {

				alert('Uncaught Error: ' + jqXHR.responseText);

			}
		});
	}
}
function carga_ruc_carry(){
	var vruc = $("#ruc_carry").val();
	var vrz = $("#razon_social_carry").val();
	var txtbusca="Buscando...";
	if(txtbusca != vruc){
		var parametros = {
				"ruc" : vruc
		};
		$.ajax({
				data:  parametros,
				url:   'ruc_extrae.php',
				type:  'post',
				beforeSend: function () {
					$("#ruc").val(txtbusca);
					$("#razon_social_carry").val(txtbusca);
				},
				success:  function (response) {
					if(IsJsonString(response)){
						var obj = jQuery.parseJSON(response);
						//alert(obj.error);
						if(obj.error == ''){
							var new_ruc = obj.ruc;
							var new_rz = obj.razon_social;
							var new_nom = obj.nombre_ruc;
							var new_ape = obj.apellido_ruc;
							var idcli = obj.idcliente;
							$("#ruc_carry").val(new_ruc);
							$("#razon_social_carry").val(new_rz);
							//$("#apellidos").val(new_ape);
							//if(parseInt(idcli)>0){
								//nclie(tipocobro,idpedido);
								//selecciona_cliente(idcli,tipocobro,idpedido);
							//}
						}else{
							$("#ruc_carry").val(vruc);
							$("#razon_social_carry").val(vrz);

						}
					}else{

						alert(response);

					}

				},
				error: function(jqXHR, textStatus, errorThrown) {
					if(jqXHR.status == 404){
						alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
					}else if(jqXHR.status == 0){
						alert('Se ha rechazado la conexión.');
					}else{
						alert(jqXHR.status+' '+errorThrown);
					}
				}
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			if (jqXHR.status === 0) {

				alert('No conectado: verifique la red.');

			} else if (jqXHR.status == 404) {

				alert('Pagina no encontrada [404]');

			} else if (jqXHR.status == 500) {

				alert('Internal Server Error [500].');

			} else if (textStatus === 'parsererror') {

				alert('Requested JSON parse failed.');

			} else if (textStatus === 'timeout') {

				alert('Tiempo de espera agotado, time out error.');

			} else if (textStatus === 'abort') {

				alert('Solicitud ajax abortada.'); // Ajax request aborted.

			} else {

				alert('Uncaught Error: ' + jqXHR.responseText);

			}
		});
	}
}
function filtrar_rz(tpago,idpedido){
		var buscar=$("#blci").val();
		var parametros = {
                "bus_rz" : buscar,
				"tpago" : tpago,
				"idpedido" : idpedido
        };
		$.ajax({
                data:  parametros,
                url:   'cliente_filtrado.php',
                type:  'post',
                beforeSend: function () {
                      $("#clientereca").html('Filtrando...');
					  $("#blci2").val('');
					  $("#blci3").val('');
                },
                success:  function (response) {
						$("#clientereca").html(response);
                }
        });


}
function filtrar_ruc(tpago,idpedido){
		var buscar=$("#blci2").val();
		var parametros = {
                "bus_ruc" : buscar,
				"tpago" : tpago,
				"idpedido" : idpedido
        };
		$.ajax({
                data:  parametros,
                url:   'cliente_filtrado.php',
                type:  'post',
                beforeSend: function () {
                      $("#clientereca").html('Filtrando...');
					  $("#blci").val('');
					  $("#blci3").val('');
                },
                success:  function (response) {
						$("#clientereca").html(response);
                }
        });


}
function filtrar_doc(tpago,idpedido){
		var buscar=$("#blci3").val();
		var parametros = {
                "bus_doc" : buscar,
				"tpago" : tpago,
				"idpedido" : idpedido
        };
		$.ajax({
                data:  parametros,
                url:   'cliente_filtrado.php',
                type:  'post',
                beforeSend: function () {
                      $("#clientereca").html('Filtrando...');
					  $("#blci").val('');
					  $("#blci2").val('');
                },
                success:  function (response) {
						$("#clientereca").html(response);
                }
        });


}
function filtrar_rz_carry(){
		var buscar=$("#blci").val();
		var parametros = {
                "bus_rz" : buscar
        };
		$.ajax({
                data:  parametros,
                url:   'cliente_filtrado2.php',
                type:  'post',
                beforeSend: function () {
                      $("#clientereca").html('Filtrando...');
					  $("#blci2").val('');
					  $("#blci3").val('');
                },
                success:  function (response) {
						$("#clientereca").html(response);
                }
        });


}
function filtrar_ruc_carry(){
		var buscar=$("#blci2").val();
		var parametros = {
                "bus_ruc" : buscar
        };
		$.ajax({
                data:  parametros,
                url:   'cliente_filtrado2.php',
                type:  'post',
                beforeSend: function () {
                      $("#clientereca").html('Filtrando...');
					  $("#blci").val('');
					  $("#blci3").val('');
                },
                success:  function (response) {
						$("#clientereca").html(response);
                }
        });


}
function filtrar_doc_carry(){
		var buscar=$("#blci3").val();
		var parametros = {
                "bus_doc" : buscar
        };
		$.ajax({
                data:  parametros,
                url:   'cliente_filtrado2.php',
                type:  'post',
                beforeSend: function () {
                      $("#clientereca").html('Filtrando...');
					  $("#blci").val('');
					  $("#blci2").val('');
                },
                success:  function (response) {
						$("#clientereca").html(response);
                }
        });


}
function retornar(mediopago,idpedido){
		var cual=mediopago;

		//retorno sin cambios la menu de pago
		var direccionurl='cobramini.php';
		var parametros = {
              "idpedido" : idpedido,
			  "tipocobro" : mediopago
	   };
       $.ajax({
                data:  parametros,
                url:   direccionurl,
                type:  'post',
                beforeSend: function () {
                  	$("#modal_titulo").html('Cobrar');
					//$("#modal_cuerpo").html('Cargando...');
                },
                success:  function (response) {
					/*if (document.getElementById('pop1')){
						$("#pop1").html(response);


					}
					if (document.getElementById('pop2')){
						popupasigna2();
						$("#pop2").html(response);
					} */
					$("#modal_titulo").html('Cobrar');
					$("#modal_cuerpo").html(response);
					//si hay medio pago, mostramos
					if (cual !=''){
						$("#oculto1").show();
						$("#oculto2").show();
						$("#warpago").hide();
						$("#cuerpo3").show();
						//$("#mediopagooc").val(cual);
						//var totalventa = parseInt($("#totalventa").val());
						//$("#montorecibido").val(totalventa);
						if (cual==1){
							//EF
							$("#adicional1").hide();
							$("#adicional2").hide();
						} else {
								//$("#montorecibido").val(totalventa);
								$("#adicional1").show();
								$("#adicional2").show();
								if (cual==8){
								//CHEQUE
									$("#warpago").show();
								}
						}
                	}

                }
        });



}
function selecciona_cliente(valor,tipocobro,idpedido){
	//tmp del medio pago
	var tmptp='';
	var tmptp=$("#octpp").val();
	if (isNaN(tmptp)){
		//vemos si hay
		//alert('nan1');
		var tmptp=$("#tipopagoselec").val();
	}
	if (isNaN(tmptp)){
		tmptp=tipocobro;
	}
	//alert('seleclie'+tmptp);
	if(IsJsonString(valor)){
		var obj = jQuery.parseJSON(valor);
		var idcliente = obj.idcliente;
		var idsucursal_clie = obj.idsucursal_clie;
		mostrar_cliente(valor,tmptp,idpedido);

		$("#idcliente").val(idcliente);
		$("#idsucursal_clie").val(idsucursal_clie);
	}else{
		alert(valor);
	}



	//setTimeout(function(){ cerrar(1); }, 100);
}
function selecciona_cliente_carry(datos_json){
	if(IsJsonString(datos_json)){
		var obj = jQuery.parseJSON(datos_json);
		carry_out(obj);
	}else{
		alert(datos_json);
	}

}
function mostrar_cliente(valor,med,idpedido){

	if(IsJsonString(valor)){
		var obj = jQuery.parseJSON(valor);
		var idclie = obj.idcliente;
		var idsucursal_clie = obj.idsucursal_clie;
	}else{
		alert(valor);
	}
		var parametros = {
				"id"              : idclie,
				'idsucursal_clie' : idsucursal_clie,

        };
		$.ajax({
                data:  parametros,
                url:   'cliente_datos.php',
                type:  'post',
                beforeSend: function () {
                     //$("#adicio").html('Cargando datos del cliente...');
                },
                success:  function (response) {
					var datos = response;
					var dato = datos.split("-/-");
					var ruc_completo = dato[0];
					//var ruc_array = ruc_completo.split("-");
					//var ruc = ruc_array[0];
					//var ruc_dv = ruc_array[1];
					var razon_social = dato[1];
					//cargar de nuevo el pop4
					//alert('ok');

					recargacliente(valor,ruc_completo,razon_social,med,idpedido);


                }
        });

}
function recargacliente(valor,ruc,rz,medio,idpedido){
	if(IsJsonString(valor)){
		var obj = jQuery.parseJSON(valor);
		var idclie = obj.idcliente;
		var idsucursal_clie = obj.idsucursal_clie;
	}else{
		alert(valor);
	}
	var cual=medio;
	var parametros = {
				"idcliente"   : idclie,
				"idsucursal_clie"   : idsucursal_clie,
				"razon" : rz,
				"ruc" :ruc,
		        "tipocobro"  :medio,
				"idpedido"  :idpedido
        };
		$.ajax({
                data:  parametros,
                url:   'cobramini.php',
                type:  'post',
                beforeSend: function () {
                     //$("#adicio").html('Cargando datos del cliente...');
                },
                success:  function (response) {
					//alert(response);
					/*if (document.getElementById('agrega_clie')){
						$("#agrega_clie").html(response);
						$("#pop1").html(response);
					} else {
						if (document.getElementById('pop1')){
							$("#pop1").html(response);
						}
					}*/
					$("#agrega_clie").html(response);
					$("#modal_titulo").html('Cobrar');
					$("#modal_cuerpo").html(response);
					//si hay medio pago, mostramos
					if (cual !=''){
						$("#oculto1").show();
						$("#oculto2").show();
						$("#warpago").hide();
						$("#cuerpo3").show();

						$("#mediopagooc").val(medio);
						var totalventa = parseInt($("#totalventa").val());
						$("#montorecibido").val(totalventa);
						if (cual==1){
							//EF
							$("#adicional1").hide();
							$("#adicional2").hide();
						} else {
								//$("#montorecibido").val(totalventa);
								$("#adicional1").show();
								$("#adicional2").show();
								if (cual==8){
								//CHEQUE
									$("#warpago").show();
								}
						}
                	}
					cerrar(0);
				}
        });

}
function cerrar(n){
	if (n==1){
		 $.magnificPopup.close();

	}
}
function nclie(tipocobro,idpedido){
	var p=0;

	if($('#r1').is(':checked')) { p=1; }
	if($('#r2').is(':checked')) { p=2; }

	//alert(tipocobro+'-'+idpedido);
	var errores='';
	var nombres=document.getElementById('nombreclie').value;
	var razg="";
	razg=$("#rz1").val();
	var apellidos=document.getElementById('apellidos').value;
	var docu=$("#cedula").val();
	var ruc=document.getElementById('ruccliente').value;
	var direclie=document.getElementById('direccioncliente').value;
	var telfo=document.getElementById('telefonoclie').value;
	var ruc_especial = $("#ruc_especial").val();
	if (p==1){
		if (nombres==''){
			errores=errores+'Debe indicar nombres del cliente. \n';
		}
		if (apellidos==''){
			errores=errores+'Debe indicar apellidos del cliente. \n';
		}
	}
	if (p==2){
		if (razg==''){
			errores=errores+'Debe indicar razon social del cliente juridico. \n';
		}

	}
	if (docu==''){
		//errores=errores+'Debe indicar documento del cliente. \n';
	}
	if (ruc==''){
		errores=errores+'Debe indicar documento del cliente o ruc generico. \n';
	}
	if (errores==''){
		 var html_old = $("#agrega_clie").html();
		//alert(html_old);
		 var parametros = {
					"n"     : 1,
					"nom"   : nombres,
					"ape"   : apellidos,
					"rz1"	:  razg,
					"dc"    : docu,
					"ruc"   : ruc,
					"dire"  : direclie,
					"telfo" : telfo,
			 		"tipocobro" : tipocobro,
					"idpedido" : idpedido,
					"tc"	: p,
					"ruc_especial" : ruc_especial
			};
		   $.ajax({
					data:  parametros,
					url:   'cliente_registra.php',
					type:  'post',
					beforeSend: function () {
							$("#agrega_clie").html("<br /><br />Registrando, favor espere...<br /><br />");
					},
					success:  function (response) {

						if(IsJsonString(response)){
							var obj = jQuery.parseJSON(response);
							if(obj.valido == 'S'){
								var obj_json = '{"idcliente":"'+obj.idcliente+'","idsucursal_clie":"'+obj.idsucursal_clie+'"}';
								selecciona_cliente(obj_json,tipocobro,idpedido);
							}else{
								alertar('ATENCION:',obj.errores,'error','Lo entiendo!');
								$("#agrega_clie").html(html_old);
								$("#nombreclie").val(nombres);
								$("#apellidos").val(apellidos);
								$("#ruccliente").val(ruc);
								$("#direccioncliente").val(direclie);
								$("#telefonoclie").val(telfo);
								$("#cedula").val(docu);
								$("#rz1").val(razg);
								if(p == 1){
									$("#r1").prop("checked", true);
									$("#r2").prop("checked", false);
								}else{
									$("#r1").prop("checked", false);
									$("#r2").prop("checked", true);
								}
							}
						}else{
							alert(response);
							$("#agrega_clie").html(html_old);
							$("#nombreclie").val(nombres);
							$("#apellidos").val(apellidos);
							$("#ruccliente").val(ruc);
							$("#direccioncliente").val(direclie);
							$("#telefonoclie").val(telfo);
							$("#cedula").val(docu);
							$("#rz1").val(razg);
							if(p == 1){
								$("#r1").prop("checked", true);
								$("#r2").prop("checked", false);
							}else{
								$("#r1").prop("checked", false);
								$("#r2").prop("checked", true);
							}
						}


						//$("#agrega_clie").html(response);

					}
			});
	} else {
		alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');

	}

}
function abreadherente(){
	var valor='';
	var parametros = {
                "id" : valor
        };
       $.ajax({
                data:  parametros,
                url:   'busca_adherente_cod.php',
                type:  'post',
                beforeSend: function () {
                     //$("#pop3").html("Cargando Opciones...");

                },
                success:  function (response) {
					$("#pop7").html(response);
					popupasigna7();

                }
        });




}
function apretar_combo(id){
		//var html = document.getElementById("prod_"+id).innerHTML;
        var parametros = {
                "id" : id
        };
       $.ajax({
                data:  parametros,
                url:   'combo_ventas.php',
                type:  'post',
                beforeSend: function () {
                        //$("#prod_"+id).html("Cargando Opciones...");
						$("#lista_prod").html("Cargando Opciones...");
						//$("#carrito").html("Actualizando Carrito...");
                },
                success:  function (response) {
						//$("#prod_"+id).html(html);
						$("#lista_prod").html(response);
                }
        });

}
function agrega_prod_grupo(idprod,idlista){
	var html = $("#prod_"+idprod+'_'+idlista).html();
	var cant = $('cant_'+idprod+'_'+idlista).val();
	var parametros = {
		"idlista" : idlista,
		"idprod" : idprod
	};
	$.ajax({
		data:  parametros,
		url:   'combo_ventas_add.php',
		type:  'post',
		beforeSend: function () {
			//$("#prod_"+idprod+'_'+idlista).html("Cargando Opciones...");
		},
        success:  function (response) {
			if(response == 'MAX'){
				$("#grupo_"+idlista).html('Cantidad Maxima Alcanzada');
			}else if(response == 'LISTO'){
				$("#grupo_"+idlista).html('Listo!');
			}else{
				$("#prod_"+idprod+'_'+idlista).html(html);
				$("#contador_"+idprod+'_'+idlista).html(response);
			}
		}
	});
}
function reinicia_grupo(id,prod_princ){
        var parametros = {
                "idlista" : id
        };
       $.ajax({
                data:  parametros,
                url:   'combo_ventas_del.php',
                type:  'post',
                beforeSend: function () {
					//$("#lista_prod").html("Cargando Opciones...");
                },
                success:  function (response) {
					if(response == 'OK'){
						apretar_combo(prod_princ);
					}else{
						$("#lista_prod").html(response);
					}
                }
        });
}
function terminar_combo(idprod_princ,cat){
		var html = $("#lista_prod").html();
        var parametros = {
                "idprod_princ" : idprod_princ
        };
       $.ajax({
                data:  parametros,
                url:   'combo_ventas_termina.php',
                type:  'post',
                beforeSend: function () {
					$("#lista_prod").html("Registrando...");
                },
                success:  function (response) {
					if(response == 'OK'){
						document.location.href='?cat='+cat;
					}else if(response == 'NOVALIDO'){
						$("#lista_prod").html(html);
						alert("Favor seleccione todos los productos antes de terminar.");
					}else{
						$("#lista_prod").html(response);
					}
                }
        });
}
function apretar_combinado(prodprinc){
		//var html = document.getElementById("prod_"+id).innerHTML;
        var parametros = {
                "prodprinc" : prodprinc
        };
       $.ajax({
                data:  parametros,
                url:   'combinado_ventas.php',
                type:  'post',
                beforeSend: function () {
                        //$("#prod_"+id).html("Cargando Opciones...");
						$("#lista_prod").html("Cargando Opciones...");
						//$("#carrito").html("Actualizando Carrito...");
                },
                success:  function (response) {
						//$("#prod_"+id).html(html);
						$("#lista_prod").html(response);
                }
        });

}
function agrega_prod_combinado(idproducto_principal,idproducto_partes){
	var html = $("#prod_"+idproducto_principal+'_'+idproducto_partes).html();
	var cant = $('cant_'+idproducto_principal+'_'+idproducto_partes).val();
	var parametros = {
		"prodprinc" : idproducto_principal,
		"prodpart" : idproducto_partes
	};
	$.ajax({
		data:  parametros,
		url:   'combinado_ventas_add.php',
		type:  'post',
		beforeSend: function () {
			//$("#prod_"+idprod+'_'+idlista).html("Cargando Opciones...");
		},
        success:  function (response) {
			//alert(response);
			if(response == 'MAX'){
				$("#grupo_"+idproducto_principal).html('Cantidad Maxima Alcanzada');
			}else if(response == 'LISTO'){
				$("#grupo_"+idproducto_principal).html('Listo!');
			}else{
				$("#prod_"+idproducto_principal+'_'+idproducto_partes).html(html);
				$("#contador_"+idproducto_principal+'_'+idproducto_partes).html(response);
			}
		}
	});
}
function reinicia_combinado(idproducto_principal){
        var parametros = {
                "prodprinc" : idproducto_principal
        };
       $.ajax({
                data:  parametros,
                url:   'combinado_ventas_del.php',
                type:  'post',
                beforeSend: function () {
					//$("#lista_prod").html("Cargando Opciones...");
                },
                success:  function (response) {
					if(response == 'OK'){
						apretar_combinado(idproducto_principal);
					}else{
						$("#lista_prod").html(response);
					}
                }
        });
}
function terminar_combinado(prodprinc,cat){
		var html = $("#lista_prod").html();
        var parametros = {
                "prodprinc" : prodprinc
        };
       $.ajax({
                data:  parametros,
                url:   'combinado_ventas_termina.php',
                type:  'post',
                beforeSend: function () {
					$("#lista_prod").html("Registrando...");
                },
                success:  function (response) {
					if(response == 'OK'){
						document.location.href='?cat='+cat;
					}else if(response == 'NOVALIDO'){
						$("#lista_prod").html(html);
						alert("Favor seleccione todos los productos antes de terminar.");
					}else{
						$("#lista_prod").html(response);
					}
                }
        });
}
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
function borra_delivery(){
	setCookie('dom_deliv',null,-1);
	document.location.href='gest_ventas_resto_caja.php';
}
function borra_pedidoweb(){
	document.location.href='gest_ventas_resto_caja_borrapedweb.php';
}
function cobrar_pedido(id,monto){
		var parametros = {
                "idpedido" : id
        };
       $.ajax({
                data:  parametros,
                url:   'cobramini.php',
                type:  'post',
                beforeSend: function () {
					//$("#pop6").html("Cargando...");
					$("#modal_titulo").html('Cobrar');
					$("#modal_cuerpo").html("Cargando...");
                },
                success:  function (response) {
					//$("#pop6").html(response);
					$("#modal_cuerpo").html(response);
					$("#totalventa_real").val(monto);
                }
        });
}
function cobrar_pedido_del(id,monto,iddomicilio){
		setCookie("dom_deliv", iddomicilio,1);
		actualiza_carrito();
		//alert(iddomicilio);
		var parametros = {
                "idpedido" : id
        };
       $.ajax({
                data:  parametros,
                url:   'cobramini.php',
                type:  'post',
                beforeSend: function () {
					//$("#pop6").html("Cargando...");
					$("#modal_titulo").html('Cobrar');
					$("#modal_cuerpo").html("Cargando...");
                },
                success:  function (response) {
					//$("#pop6").html(response);
					$("#modal_cuerpo").html(response);
					$("#totalventa_real").val(monto);
                }
        });
}
function chau(valor){
	if(window.confirm('Esta seguro que desea borrar el pedido '+valor+'?')){
		if (valor!=''){
			//var parametros='chau='+valor;
			var parametros = {
					"chau" : valor
			};
		   $.ajax({
					data:  parametros,
					url:   'carry_out.php',
					type:  'post',
					beforeSend: function () {
						$("#modal_titulo").html('Carry Out');
						$("#modal_cuerpo").html('Borrando...');
					},
					success:  function (response) {
						$("#modal_titulo").html('Carry Out');
						$("#modal_cuerpo").html(response);
					}
			});
			//OpenPage('carry_out.php',parametros,'POST','pop6','pred');

		}
	}

}
function busque_adh(codigoadh){
	var parametros = {
		"cod" : codigoadh
	};
	$.ajax({
		data:  parametros,
		url:   'busca_adherente_cod_res.php',
		type:  'post',
		beforeSend: function () {
			$("#recarga_adh").html("Cargando...");
		},
		success:  function (response) {
			$("#recarga_adh").html(response);
		}
	});
}
function buscar_rz_carry(){
		//shortcut.remove('space');
		var direccionurl='clientesexistentes3.php';
		var parametros = {
              "id" : 0
	   };
       $.ajax({
                data:  parametros,
                url:   direccionurl,
                type:  'post',
                beforeSend: function () {
                        //$("#pop1").html("Cargando...");
                },
                success:  function (response) {
						/*popupasigna();
						$("#pop1").html(response);*/
						$("#modal_titulo").html('Busqueda de Clientes');
						$("#modal_cuerpo").html(response);
						$("#blci").focus();
						/*if (document.getElementById('blci')){
							document.getElementById('blci').focus();
						}*/
                }
        });
}
function cambiacheckserv(servicio){
	$("#idservcombox").val(servicio);
}
function reimpimir_comp(id){
    $("#reimprimebox").html('<iframe src="impresor_ticket_reimp.php?idped='+id+'" style="width:310px; height:500px;"></iframe>');
}
function descuento_asigna(){
	$("#descuento_box").show();
	$("#descuento").focus();
	var vdescuento = $("#descuento").val();
	var vdescuento_motiv = $("#motivodesc").val();
}
function moneda_extrangera(idmoneda){
	var v_totalventa = $("#totalventa").val();
	var parametros = {
		"idmoneda" : idmoneda
	};
	$.ajax({
		data:  parametros,
		url:   'cotizacion_mini.php',
		type:  'post',
		beforeSend: function () {
			$("#monto_extrangero").hide();
			$("#monto_extrangero").val(0);
		},
		success:  function (response) {
			if(response == 'N'){
				$("#monto_extrangero").val(0);
				$("#monto_extrangero").hide();
			}else{
				var vextrangero=parseInt(v_totalventa)/parseInt(response);
				//vextrangero = parseFloat(vextrangero).toFixed(2);
				vextrangero = vextrangero.toFixed(2);
				$("#monto_extrangero").val(vextrangero);
				$("#monto_extrangero").show();
			}

		}
	});
}
function transfer_mesa(idpedido){
	 var parametros = {
                "idpedido" : idpedido
        };
       $.ajax({
                data:  parametros,
                url:   'transfer_mesa.php',
                type:  'post',
                beforeSend: function () {
					$("#modal_cuerpo").html('Cargando...');
                },
                success:  function (response) {
                    $("#modal_titulo").html('Transferir a Mesa');
					$("#modal_cuerpo").html(response);
                }
        });
}
function transferir_mesa(idpedido){
	var idmesa_destino = $("#idmesa_destino").val();
	 var parametros = {
			"idpedido"       : idpedido,
			"idmesa_destino" : idmesa_destino
        };
       $.ajax({
                data:  parametros,
                url:   'transferir_mesa.php',
                type:  'post',
                beforeSend: function () {
					$("#modal_cuerpo").html('Cargando...');
                },
                success:  function (response) {
					if(IsJsonString(response)){
						var obj = jQuery.parseJSON(response);
						if(obj.valido == 'S'){
							$("#modal_cuerpo").html('Transferencia Exitosa!');
						}else{
							alert(obj.errores);
							$("#modal_cuerpo").html(obj.errores);
						}
					}else{
						alert(response);
						$("#modal_cuerpo").html(response);
					}
                }
        });
}
function agrega_carrito_pag(idpedido){
	var idforma_mixto_monto = $("#idforma_mixto_monto").val();
	var idforma_mixto = $("#idforma_mixto").val();
	var parametros = {
			"idformapago" : idforma_mixto,
			"monto_forma" : idforma_mixto_monto,
			"idpedido"    : idpedido,
			"accion"      : 'add',
	};
	$.ajax({
			data:  parametros,
			url:   'carrito_cobros_venta.php',
			type:  'post',
			beforeSend: function () {
				$("#carrito_pagos_box").html("Cargando...");
			},
			success:  function (response) {
				$("#carrito_pagos_box").html(response);
				// ocultar el boton ticket segun corresponda
				if($("#obliga_facturar").val() == 'S'){
					$("#ticket_btn").hide();
				}else{
					$("#ticket_btn").show();
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			if (jqXHR.status === 0) {

				alert('No conectado: verifique la red.');

			} else if (jqXHR.status == 404) {

				alert('Pagina no encontrada [404]');

			} else if (jqXHR.status == 500) {

				alert('Internal Server Error [500].');

			} else if (textStatus === 'parsererror') {

				alert('Requested JSON parse failed.');

			} else if (textStatus === 'timeout') {

				alert('Tiempo de espera agotado, time out error.');

			} else if (textStatus === 'abort') {

				alert('Solicitud ajax abortada.'); // Ajax request aborted.

			} else {

				alert('Uncaught Error: ' + jqXHR.responseText);

			}
		});
}
function borra_carrito_pag(idcarritocobrosventas){
	var parametros = {
			"idcarritocobrosventas" : idcarritocobrosventas,
			"accion"      : 'del',
	};
	$.ajax({
			data:  parametros,
			url:   'carrito_cobros_venta.php',
			type:  'post',
			beforeSend: function () {
				$("#carrito_pagos_box").html("Cargando...");
			},
			success:  function (response) {
				$("#carrito_pagos_box").html(response);
				// ocultar el boton ticket segun corresponda
				if($("#obliga_facturar").val() == 'S'){
					$("#ticket_btn").hide();
				}else{
					$("#ticket_btn").show();
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if(jqXHR.status == 404){
					alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
				}else if(jqXHR.status == 0){
					alert('Se ha rechazado la conexión.');
				}else{
					alert(jqXHR.status+' '+errorThrown);
				}
			}
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			if (jqXHR.status === 0) {

				alert('No conectado: verifique la red.');

			} else if (jqXHR.status == 404) {

				alert('Pagina no encontrada [404]');

			} else if (jqXHR.status == 500) {

				alert('Internal Server Error [500].');

			} else if (textStatus === 'parsererror') {

				alert('Requested JSON parse failed.');

			} else if (textStatus === 'timeout') {

				alert('Tiempo de espera agotado, time out error.');

			} else if (textStatus === 'abort') {

				alert('Solicitud ajax abortada.'); // Ajax request aborted.

			} else {

				alert('Uncaught Error: ' + jqXHR.responseText);

			}
		});
}
function credito_rapido_enter(e){
	// si apreto enter
	if(e.keyCode == 13){
		//alert('enter');
		credito_rapido();
	}

	/*alert("onkeypress handler: \n"
      + "keyCode property: " + e.keyCode + "\n"
      + "which property: " + e.which + "\n"
      + "charCode property: " + e.charCode + "\n"
      + "Character Key Pressed: "
      + String.fromCharCode(e.charCode) + "\n"
     );*/
}
function inicio(){
	/*
// 60000 = 1 minuto
// 600000 = 10 minutos
// 1200000 = 20 minutos
// 3600000 = 1 hora
	*/
	setInterval('mantiene_session()',600000); // actualizar
}
function mantiene_session(){
	var f=new Date();
	cad=f.getHours()+":"+f.getMinutes()+":"+f.getSeconds();
	var parametros = {
                "ses" : cad,
       };
	  $.ajax({
                data:  parametros,
                url:   'mantiene_session.php',
                type:  'post',
                beforeSend: function () {
                },
                success:  function (response) {
					//alert(response);
                }
        });
}
function busqueda_lupa(){

	var codigo_vrapida =  $("#codigo_vrapida").val();
	document.location.href='?bus=1&vrc='+codigo_vrapida;

}
function categoria_sel(idcat){
	var codigo_vrapida =  $("#codigo_vrapida").val();
	document.location.href='?cat='+idcat+'&vrc='+codigo_vrapida;
}
function alerta_modal(titulo,mensaje){
	$('#modal_ventana').modal('show');
	$("#modal_titulo").html(titulo);
	$("#modal_cuerpo").html(mensaje);
	//$("#modal_pie").html(html_botones);
}
function ventana(){
	var titulo = $("#titulo").val();
	var mensaje = $("#mensaje").val();
	alerta_modal(titulo,mensaje);
}
function busque(valor){

		var cantidad=document.getElementById('cantidad').value;


		var parametros = {
                "bb" 		: valor,
				"cantidad"	: cantidad
        };
       $.ajax({
                data:  parametros,
                url:   'lgbp.php',
                type:  'post',
                beforeSend: function () {
                    // $("#recarga").html("Cargando Opciones...");

                },
                success:  function (response) {
					var res=response.substr(0,2);

					if (res=='cp'){
						var idprod=response.split('=');
						seleccionar(idprod[1]);
						//alert(idprod[1]);
						//$("#recarga").html(response);
					} else {
						$("#recarga").html(response);

					}

                }
        });




}
// INICIALIZA LOS ATAJOS DE TECLAS
window.onload=init;
//abrecodbarra();
function init() {
	shortcut.add("F4", function() {
		tecla_acciones('F4');
	});
	//Enfoque a codigo barras en ventana ppal
	shortcut.add("F9", function() {
		tecla_acciones('F9');
	});
	//Enfoque a codigo barras en ventana ppal
	shortcut.add("F10", function() {
		tecla_acciones('F10');
	});
	//abre buscador con barcode x defecto (metodo no-tradicional viejo)
	/*shortcut.add("F8", function() {
		tecla_acciones('F8');
	});*/
	//Agregar cantidad
	shortcut.add("plus", function() {
		tecla_acciones('plus');

	});
	//Enter
	shortcut.add("enter", function() {
		tecla_acciones('enter');
	});
	//Espacio
	/*shortcut.add("space", function() {
		tecla_acciones('space');
	});*/


}
// INICIALIZA LOS ATAJOS DE TECLAS
// DEFINE LAS ACCIONES DE CADA TECLA
function tecla_acciones(tecla){
	if(tecla == 'F4'){
		popupasigna7();
		if($("#totalventa_real").val() > 0){
			document.getElementById('adhbtn').click();
			setTimeout(function(){ enfoque(7); }, 200);
			setTimeout(function(){ enfoque(7); }, 400);
		}
	}
	if(tecla == 'F8'){
		document.getElementById('ocb').click();
		setTimeout(function(){ enfoque(1); }, 200);
	}
	if(tecla == 'F9'){
		setTimeout(function(){ enfoque(4); }, 200);
	}
	if(tecla == 'F10'){
		//setTimeout(function(){ enfoque(4); }, 200); // aqui la funcion para venta rapida efectivo
	}
	if(tecla == 'plus'){
		setTimeout(function(){ enfoque(2); }, 200);
	}
	if(tecla == 'space'){
		var modal_ventana_box = $("#modal_ventana").is(":visible");
		var primero = $("#pop1").is(":visible");
		var segundo = $("#pop2").is(":visible");
		var tercero = $("#pop3").is(":visible");
		var cuarto = $("#pop4").is(":visible");
		var cinco=$("#filtrar").is(":visible");
		var seis=$("#pop6").is(":visible");
		var siete=$("#pop7").is(":visible");
		var ocho=$("#pop8").is(":visible");
		if(primero==false && segundo==false && tercero==false && cuarto==false && cinco==false  && seis==false && siete==false && ocho==false && modal_ventana_box == false){
			popupasigna5();
			document.getElementById('occ').click();
			setTimeout(function(){ enfoque(3); }, 200);
		} else {
			shortcut.remove("space");
		}
	}
	if(tecla == 'enter'){
		var uno=$("#busqueda").is(":visible");
		var dos=$("#buscador").is(":visible");
		var tres=$("#bccode").is(":visible");
		//para la cantidad en ventana ppal
		var cuatro=$("#cantcode").is(":visible");
		var cinco='';
		var siete = $("#terminaad").is(":visible");
		//Tradicional x F8
		if (uno==true){
			 document.getElementById('busqueda').focus();
		}
		//Fin tradicional x f8
		//Inserta en carrito x codigo directo x espaciadora
		if (dos==true){
			 var valor=$("#buscador").val();
			 if ((valor!='')){
				 carritocodigo();
			 }

		}
		//Inserta en carrito x codigo de barras nuevo en ventana ppal
		if (tres==true){
				var valor=$("#bccode").val();
				if ((valor!='')){
					carritocodigonew();
			 	}

		}
		//ahora la cantidad para enfoqu solamente
		if (tres==true && cuatro==true && dos==false && uno==false && siete == false  && ocho == false){
			$("#bccode").focus();
		}
		if(siete == true){
			$("#rghbtn").click();
		}
	}

}

//Enfoques
function enfoque(cual){
	var uno='';
	var dos='';
	var tres='';
	var cuatro='';
	var cinco='';
	var siete='';
	if (cual==1){
		uno = $("#busqueda").is(":visible");
		if (uno==true){
			 document.getElementById('busqueda').focus();
		}
	}
	if (cual==2){
		dos = $("#cantcode").is(":visible");
		tres=$("#cantidad").is(":visible");
		//Evaluamos primero por el que siempre va estar visible
		if ((dos==true) && (tres==false)){

			document.getElementById('cantcode').focus();
		}
		//si ambos estan visibles, se abro por f8
		if ((dos==true) && (tres==true)) {
			document.getElementById('cantcode').value='';
			document.getElementById('cantidad').focus();
		}


	}
	if (cual==3){
		document.getElementById('buscador').focus();
	}
	if (cual==4){
		document.getElementById('bccode').focus();
	}
	if(cual==7){
		document.getElementById('busqueda_adhb').focus();
	}
}
//Buscador x F8
function abrebusca(){
	popupasigna5();
}
function filtra(valor){
	if (valor!=''){
		shortcut.remove('space');
		if (document.getElementById('minicentro')){

			var parametros = {
					"bb" 		: valor
			};
		   $.ajax({
					data:  parametros,
					url:   'filtromini.php',
					type:  'post',
					beforeSend: function () {
						 $("#minicentro").html("....");

					},
					success:  function (response) {

							$("#minicentro").html(response);



					}
			});


		}
	} else {
		$("#minicentro").html('');


	}
}
function edita_cant(idproducto){
	var parametros = {
		"idproducto" : idproducto
	};
	$.ajax({
		data:  parametros,
		url:   'carrito_edit.php',
		type:  'post',
		beforeSend: function () {
			$("#modal_titulo").html('Editar Cantidad');
			$("#modal_cuerpo").html('Cargando...');
		},
		success:  function (response) {
			$('#modal_ventana').modal('show');
			$("#modal_titulo").html('Editar Cantidad');
			$("#modal_cuerpo").html(response);
			setTimeout(function(){$("#cantidad_edit").focus().select();},1000);
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}


	}).fail( function( jqXHR, textStatus, errorThrown ) {

		if (jqXHR.status === 0) {

			alert('No conectado: verifique la red.');

		} else if (jqXHR.status == 404) {

			alert('Pagina no encontrada [404]');

		} else if (jqXHR.status == 500) {

			alert('Internal Server Error [500].');

		} else if (textStatus === 'parsererror') {

			alert('Requested JSON parse failed.');

		} else if (textStatus === 'timeout') {

			alert('Tiempo de espera agotado, time out error.');

		} else if (textStatus === 'abort') {

			alert('Solicitud ajax abortada.'); // Ajax request aborted.

		} else {

			alert('Uncaught Error: ' + jqXHR.responseText);

		}
	});
}
function edita_cant_reg(idproducto){
	var cantidad = $("#cantidad_edit").val();
	var cuerpo_html = $("#modal_cuerpo").html();
	var parametros = {
		"idproducto" : idproducto,
		"cantidad"   : cantidad,
		"reg"        : 'S'
	};
	$.ajax({
		data:  parametros,
		url:   'carrito_edit.php',
		type:  'post',
		beforeSend: function () {
			$("#modal_titulo").html('Editar Cantidad');
			$("#modal_cuerpo").html('Registrando...');
			$("#error_box").hide();
			$("#error_box_msg").html('');
		},
		success:  function (response) {
			$('#modal_ventana').modal('show');
			$("#modal_titulo").html('Editar Cantidad');

			if(IsJsonString(response)){
				var obj = jQuery.parseJSON(response);
				if(obj.valido == 'S'){
					//$("#modal_cuerpo").html(response);
					$('#modal_ventana').modal('hide');
					actualiza_carrito();
				}else{
					//alert('Errores: '+obj.errores);
					$("#modal_cuerpo").html(cuerpo_html);
					setTimeout(function(){$("#cantidad_edit").focus().select();},1000);
					$("#error_box_msg").html(nl2br(obj.errores));
					$("#error_box").show();
				}
			}else{
				alert(response);
				$("#modal_cuerpo").html(response);
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}


	}).fail( function( jqXHR, textStatus, errorThrown ) {

		if (jqXHR.status === 0) {

			alert('No conectado: verifique la red.');

		} else if (jqXHR.status == 404) {

			alert('Pagina no encontrada [404]');

		} else if (jqXHR.status == 500) {

			alert('Internal Server Error [500].');

		} else if (textStatus === 'parsererror') {

			alert('Requested JSON parse failed.');

		} else if (textStatus === 'timeout') {

			alert('Tiempo de espera agotado, time out error.');

		} else if (textStatus === 'abort') {

			alert('Solicitud ajax abortada.'); // Ajax request aborted.

		} else {

			alert('Uncaught Error: ' + jqXHR.responseText);

		}
	});
}
function edita_cant_reg_enter(idproducto,e){
	// si apreto enter
	if(e.keyCode == 13){
		edita_cant_reg(idproducto);
	}
}
function agregon_enter(posicion,producto,precio,e,usar_lote,idregseriedptostk){	// si apreto enter
	if(e.keyCode == 13){
		agregon(posicion,producto,precio);
	}
}