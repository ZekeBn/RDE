<?php

//********************//////////// ENVIO POR PHP  //********************////////////
// genera array con los datos
$arr = [
'zona' => $zona,
'ruc' => antixss($ruc),
'razon_social' => antixss($rz),
'nombres' => antixss($nombres),
'apellidos' => antixss($apellidos),
'direccion' => antixss($direccion),
'iddomicilio' => antixss($iddomicilio),
'idclientedel' => antixss($idclientedel),
'lugar' => antixss($lugar),
'lugares' => $lugares,
'valido' => $valido,
'errores' => $errores
];

//print_r($arr);

// convierte a formato json
$respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// devuelve la respuesta formateada
echo $respuesta;

//********************//////////// ENVIO POR PHP  //********************////////////


//********************//////////// RECEPCION POR PHP  //********************////////////

// si se recibe por php

$respuesta = json_decode($respuesta, true);


//********************//////////// RECEPCION POR PHP  //********************////////////



//********************//////////// RECEPCION POR JAVASCRIPT  //********************////////////
// si se recibe por javascript
?>
<script>
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
function carga_factura(idfactura){
	var parametros = {
	  "idfactura"   : idfactura 
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 5000,  // I chose 3 secs for kicks: 5000
		crossDomain: true,
		beforeSend: function () {
			//$("#monto_abonar").val('Cargando...');				
		},
		success:  function (response) {
			if(IsJsonString(response)){
				var obj = jQuery.parseJSON(response);
				if(obj.valido == 'S'){
					// hacer algo
					//$("#monto_abonar").val(obj.saldo_factura);
				}else{
					alert('Errores: '+obj.errores);	
					$("#error_box_msg").html(nl2br(obj.errores));
					$("#error_box").show();
				}
			}else{
				alert(response);	
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
		}
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
	});
}
function nl2br (str, is_xhtml) {
  // http://kevin.vanzonneveld.net
  // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   improved by: Philip Peterson
  // +   improved by: Onno Marsman
  // +   improved by: Atli Þór
  // +   bugfixed by: Onno Marsman
  // +      input by: Brett Zamir (http://brett-zamir.me)
  // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   improved by: Brett Zamir (http://brett-zamir.me)
  // +   improved by: Maximusya
  // *     example 1: nl2br('Kevin\nvan\nZonneveld');
  // *     returns 1: 'Kevin<br />\nvan<br />\nZonneveld'
  // *     example 2: nl2br("\nOne\nTwo\n\nThree\n", false);
  // *     returns 2: '<br>\nOne<br>\nTwo<br>\n<br>\nThree<br>\n'
  // *     example 3: nl2br("\nOne\nTwo\n\nThree\n", true);
  // *     returns 3: '<br />\nOne<br />\nTwo<br />\n<br />\nThree<br />\n'
  var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>'; // Adjust comment to avoid issue on phpjs.org display

  return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}
function errores_ajax_manejador(jqXHR, textStatus, errorThrown, tipo){
	// error
	if(tipo == 'error'){
		if(jqXHR.status == 404){
			alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
		}else if(jqXHR.status == 0){
			alert('Se ha rechazado la conexión.');
		}else{
			alert(jqXHR.status+' '+errorThrown);
		}
	// fail
	}else{
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
	}
}
</script>
<?php //********************//////////// RECEPCION POR JAVASCRIPT  //********************////////////?>