<?php
$idventa = 4;
$idempresa = 1;
$idusu = 23;
$idsucursal = 1;

//---------------------------------------------NOTAS-------------------------------------------------------------
// Script centralizado para las impresiones de factura y ticketes respectivamente, habilitar desde el 22-02-2019
//---------------------------------------------------------------------------------------------------------------
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivante
$modulo = "1";
$submodulo = "30";
// require_once("includes/rsusuario.php");
//Preferencias

require_once("includes/funciones_cocina.php");


$factura_auto = "";

// La solicitud proviene de una aplicación que usa flutter_inappwebview
$inappwebview = 1;
$factura_auto2 = factura_autoimpresor($idventa, $inappwebview);
// $factura_auto=factura_autoimpresor($idventa);
$factura_auto = factura_autoimpresor($idventa);
$texto = $factura_auto;
$texto_app = $factura_auto2;

// buscar impresora si pertenece
$consulta = "
SELECT * 
FROM 
impresoratk 
where 
idsucursal = $idsucursal 
and borrado = 'N' 
and tipo_impresora='CAJ' 
order by idimpresoratk  asc
limit 1
";
$rsimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$pie_pagina = $rsimp->fields['pie_pagina'];
$metodo_app = $rsimp->fields['metodo_app'];
$defaultprnt = "http://localhost/impresorweb/ladocliente.php";
$script_impresora = trim($rsimp->fields['script']);
if (trim($script_impresora) == '') {
    $script_impresora = $defaultprnt;
}

function javascript_app_webview($parametros_array)
{
    global $saltolinea;
    $codigo_js = "";

    $parametros_app = $parametros_array['parametros_tk'];
    $div_msg = $parametros_array['div_msg'];
    $mensaje_impre = '';
    if (trim($div_msg) != '') {
        $mensaje_impre = '		$("#'.$div_msg.'").html("Enviando Impresion (app)...");'.$saltolinea;
    }
    $texto_para_app = texto_para_app($parametros_app);
    $texto_para_app_mobile = texto_para_app_mobile($parametros_app);

    $version = 2;
    if ($version == 1) {
        $if_es_app_inicio = "	if(!(typeof ApiChannel == 'undefined')){".$saltolinea;
        $if_es_app_fin = "	}".$saltolinea;
        $if_no_app_inicio = "	if((typeof ApiChannel == 'undefined')){".$saltolinea;
        $if_no_app_fin = "	}";
        $cuerpo_js_app = "		ApiChannel.postMessage('$texto_para_app');".$saltolinea;
    }
    if ($version == 2) {
        $if_es_app_inicio = "	if((typeof window.flutter_inappwebview != 'undefined')){".$saltolinea;
        $if_es_app_fin = "	}".$saltolinea;
        $if_no_app_inicio = "	if((typeof window.flutter_inappwebview == 'undefined')){".$saltolinea;
        $if_no_app_fin = "	}";
        $cuerpo_js_app = "
		var result =	'$texto_para_app_mobile';	
		alert(result);
		window.flutter_inappwebview.callHandler('ApiChannel',result);".$saltolinea;
    }

    // $cuerpo2 ='
    // var result = \'{"metodo":"BLUETOOTH", "lat":"-25.28754","long":"1543"}\';
    // alert(\'entre en el if antes de ejecutar flutter  \' + result);
    // window.flutter_inappwebview.callHandler(\'ApiChannel\', result);
    // 	// ';
    //  esta seria la prueba del cambio
    // 	$cuerpo2 ='




    // 		var result = \'{"texto_imprime":["                  RDE \\r      DE RAMIREZ DIAZ DE ESPADA ICSA \\r             RUC: 80027981-6 \\r C MATRIZ:  \\r             TIMBRADO: 123456 \\r    Vigencia: 25\/10\/2023 AL 09\/12\/2024 \\r  FACTURA CREDITO | Nro: 001-001-0000003 \\r     VENCIMIENTO FACTURA: 04\/01\/2024 \\r      Fecha y Hora: 04\/01\/2024 14:56 \\r ---------------------------------------- \\r RUC      : 4655724-5 \\r CI       : 4655724 \\r Cliente  : MOLINA NAVARRO, RAMON ENRIQUE \\r ---------------------------------------- \\r Cant    Descripcion \\r P.U.              P.T.             Tasa% \\r ---------------------------------------- \\r 2        COPA VCP RICH P\/ EVENTO(BEBID \\r 8.000             16.000           10    \\r ---------------------------------------- \\r Total a pagar en GS: 16.000 \\r DIECISEIS MIL \\r ---------------------------------------- \\r Total Grav. 10% : 16.000 \\r Total Grav. 5%  : 0 \\r Total Exenta    : 0 \\r ---------------------------------------- \\r Liquidacion del I.V.A. \\r 10% : 1.455 \\r 5%  : 0 \\r Total I.V.A. : 1.455 \\r ---------------------------------------- \\r Cajero: RAMON  \\r Caja: #1 Vta: #4 Ped: #4 \\r ---------------------------------------- \\r        *** VENTA A CREDITO ***   ","     \\r RECONOZCO Y PAGARE EL MONTO     ","       \\r    DE ESTA OPERACION       \\r ","","","FIRMA:............................ \\r ...... \\r ---------------------------------------- \\r Impreso: 08\/01\/2024 13:48:13 \\r ---------------------------------------- \\r      *** GRACIAS POR SU COMPRA *** \\r LOS DATOS IMPRESOS REQUIEREN DE CUIDADOS \\r ESPECIALES. PARA ELLO DEBE EVITARSE EL \\r CONTACTO DIRECTO CON PLASTICOS, \\r SOLVENTES DE PRODUCTOS QUIMICOS. EVITE \\r TAMBIEN LA EXPOSICION AL CALOR Y HUMEDAD \\r  EN EXCESO, LUZ SOLAR O LAMPARAS \\r FLUORESCENTES. \\r ---------------------------------------- \\r ORIGINAL: CLIENTE","DUPLICADO: ARCHIVO \\r TRIBUTARIO","TRIPLICADO: CONTABILIDAD \\r  \\r "],"url_redir":null,"gps_obtener":null,"lista_post":[null],"imp_url":null,"metodo":"BLUETOOTH"}\';
    // 		alert(result);
    // 		 window.flutter_inappwebview.callHandler(\'ApiChannel\',result);


    // 	';
    // armamos el codigo javascript
    $codigo_js .= $if_es_app_inicio;
    $codigo_js .= $mensaje_impre;
    $codigo_js .= $cuerpo_js_app;
    // $codigo_js.=$cuerpo2;
    $codigo_js .= $if_es_app_fin;
    $codigo_js .= $if_no_app_inicio;


    $res = [
        'inicio' => $codigo_js,
        'final' => $if_no_app_fin,
    ];
    return $res;
} //window.flutter_inappwebview.callHandler('ApiChannel', result);
?><html>
<head>
<script src="js/jquery-1.10.2.min.js"></script>
<meta charset="utf-8">
<title>Impresor de Prueba Factura</title>
<script>
	window.onload = function(){
		
	}
<?php
// lista de post a enviar
if ($metodo_app == 'POST_URL') {
    $lista_post = [
        'tk' => $texto,
        'tk_json' => $ticket_json
    ];
}
//parametros para la funcion
$parametros_array_tk = [
    'texto_imprime' => $texto, // texto a imprimir
    'texto_imprime_app' => $texto_app, // texto a imprimir
    'url_redir' => $url1, // redireccion luego de imprimir
    'lista_post' => $lista_post, // se usa solo con metodo POST_URL
    'imp_url' => $script_impresora_app, // se usa solo con metodo POST_URL
    'metodo' => $metodo_app // POST_URL, SUNMI, ''
];
$parametros_app = [
    'parametros_tk' => $parametros_array_tk,
    'div_msg' => 'impresion_box'
];


$js_app = javascript_app_webview($parametros_app);
?>
function imprimir_factura_auto(){
	
	// impresor app
<?php echo $js_app['inicio']; ?>
		var texto = $("#texto_fac").val();
		var parametros = {
			"tk" : texto,
			'fac': 'S'
		};
		$.ajax({
			data:  parametros,
			url:   'http://localhost/impresorweb/ladoclientefactura_auto.php',
			type:  'post',
			dataType: 'html',
			cache: false,
			timeout: 5000,  // I chose 5 secs for kicks: 5000
			crossDomain: true,
			beforeSend: function () {
				$("#impresion_box").html("Enviando Impresion...");
			},
			success:  function (response) {
				$("#impresion_box").html(response);	
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
	// impresor app final
<?php echo $js_app['final']; ?>
}	
// ejecutar al cargar la pagina
$( document ).ready(function() {
	imprimir_factura_auto();
});
</script>
</head>
<body>
<input type="button" value="imprimi por favor" id="hola" onclick="imprimir_factura_auto()">

<textarea name="texto_fac" id="texto_fac" style="display: none">
               DEMO S.A.
             RUC: 123456-7
C Matriz: 
Sucursal: SAN BERNARDINO
SAN BERNARDINO
              TIMBRADO: 1
      Inicio Vigencia: 01/02/2020
        Fin Vigencia: 01/02/2030
            FACTURA CONTADO
          Nro: 001-001-0000014
     Fecha y Hora: 02/06/2021 10:41
----------------------------------------
RUC      : 44444401-7
CI       : 0
Cliente  : CONSUMIDOR FINAL
----------------------------------------
Cant    Descripcion
P.U.              P.T.                  
Valores discriminados por impuesto      
----------------------------------------
1       SALSA BECHAMEL                  
18.000            18.000           
 -Exenta    : 8.571             
 -Grav. 10% : 9.429             
1       SANDIA X KG                     
12.000            12.000           
 -Grav. 5%  : 12.000            
----------------------------------------
Total a pagar en GS: 30.000
TREINTA MIL
----------------------------------------
Total Grav. 10% : 9.429
Total Grav. 5%  : 12.000
Total Exenta    : 8.571
----------------------------------------
Liquidacion del I.V.A.
10% : 857
5%  : 571
Total I.V.A. : 1.429
----------------------------------------
Pagos: 
EFECTIVO              :           30.000
----------------------------------------
Cajero: OMARALBERT
Caja: #59 Vta: #6146
----------------------------------------
Impreso: 08/06/2021 10:15:16
----------------------------------------
     *** GRACIAS POR SU COMPRA ***
Original: Cliente
Duplicado: Archivo Tributario
Triplicado: Contabilidad

</textarea>
	<div id="impresion_box"></div>
	</body>
</html>