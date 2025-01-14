<?php
require_once("../../includes/conexion.php");
require_once("../../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
$dirsup_sec = "S";
require_once("../../includes/rsusuario.php");

$redir = intval($_GET['redir']);
$redir_url = "inf_pagosxcaja.php";
if ($redir == 1) {
    $redir_url = "inf_pagosxcaja.php";
}
if ($redir == 2) {
    $redir_url = "gest_administrar_caja.php";
}
if ($redir == 3) {
    $redir_url = "gest_administrar_caja_new.php#ultcajamov";
}
// verifica apertura de caja
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal and tipocaja = 1 order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);
if ($idcaja == 0) {

    if ($redir == 3) {
        echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja_new.php'/>" 	;
    } else {
        echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>" 	;

    }
    exit;
}
if ($estadocaja == 3) {
    if ($redir == 3) {
        echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja_new.php'/>" 	;
    } else {
        echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>" 	;

    }
    exit;
}

// trae la primera impresora
$consulta = "SELECT * FROM impresoratk where idempresa = $idempresa  and idsucursal = $idsucursal and borrado = 'N' and tipo_impresora = 'CAJ' order by idimpresoratk asc limit 1";
$rsimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$pie_pagina = $rsimp->fields['pie_pagina'];
$defaultprnt = "http://localhost/impresorweb/ladocliente.php";
$script_impresora = trim($rsimp->fields['script']);
if (trim($script_impresora) == '') {
    $script_impresora = $defaultprnt;
}

$unis = intval($_GET['id']);

$buscar = "Select 
estado,unis,fecha,concepto,monto_abonado,
(select nombre from proveedores 
where idempresa=$idempresa and idproveedor=pagos_extra.idprov)as provee,
factura,anulado_el
,(select usuario from usuarios where idusu=pagos_extra.anulado_por) as quien
from pagos_extra 
where 
idusu=$idusu 
and idcaja=$idcaja 
and pagos_extra.unis = $unis
order by fecha asc";
//echo $buscar;
$rst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$nombreempresa_centrado = $saltolinea.corta_nombreempresa($nombreempresa);
$fecha = date("d/m/Y H:i:s", strtotime($rst->fields['fecha']));

// buscar usuario
$operador = $idusu;
$consulta = "
select usuario from usuarios where idusu = $operador
";
$rsop = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$operador = $rsop->fields['usuario'];

$fechaimp = date("d/m/Y H:i:s", strtotime($ahora));

$texto = "
****************************************$nombreempresa_centrado
     PAGO A PROVEEDOR POR CAJA
****************************************
CAJA N° $idcaja | Cajero: $operador
----------------------------------------
FECHA REG : $fecha
FECHA IMP : $fechaimp
----------------------------------------
PROVEEDOR : ".$rst->fields['provee']."
FACTURA   : ".$rst->fields['factura']."
MONTO     : ".formatomoneda($rst->fields['monto_abonado'])."
CONCEPTO  : ".$rst->fields['concepto']."
----------------------------------------
	COMPROBANTE DE USO INTERNO
";

// buscar impresora remota
$consulta = "
SELECT * FROM 
impresoratk 
where 
idsucursal = $idsucursal
and borrado = 'N' 
and tipo_impresora='REM' 
order by idimpresoratk  asc
limit 1
";
$rsimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$pie_pagina = $rsimp->fields['pie_pagina'];
$metodo_app = $rsimp->fields['metodo_app'];
$defaultprnt = "http://localhost/impresorweb/ladocliente.php";
$script_impresora_app = trim($rsimp->fields['script']);
if (trim($script_impresora_app) == '') {
    $script_impresora_app = $defaultprnt;
}

$url1 = $redir_url;

?>
<html>
<head>
<script src="js/jquery-1.10.2.min.js"></script>
<meta charset="utf-8">
<title>Imprimir</title>
<script>
function imprime_cliente(){
	
	// impresor app
	if(!(typeof ApiChannel === 'undefined')){
		$("#impresion_box").html("Enviando Impresion (app)...");
		ApiChannel.postMessage('<?php
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
    'url_redir' => $url1, // redireccion luego de imprimir
    'lista_post' => $lista_post, // se usa solo con metodo POST_URL
    'imp_url' => $script_impresora_app, // se usa solo con metodo POST_URL
    'metodo' => $metodo_app // POST_URL, SUNMI, ''
];
echo texto_para_app($parametros_array_tk);

?>');
	}
	// impresor normal
	if((typeof ApiChannel === 'undefined')){

		var texto = document.getElementById("texto").value;
		var parametros = {
			"tk"      : texto,
		};
		$.ajax({
			data:  parametros,
			url:   '<?php echo $script_impresora ?>',
			type:  'post',
			dataType: 'html',
			beforeSend: function () {
				$("#impresion_box").html("Enviando Impresion...");
			},
			crossDomain: true,
			success:  function (response) {
				//si impresion es correcta marcar
				var str = response;
				var res = str.substr(0, 18);
				if(res == 'Impresion Correcta'){
					document.location.href='<?php echo $redir_url ?>';
				}else{
					$("#impresion_box").html(response);	
					document.location.href='<?php echo $redir_url ?>';
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
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
// ejecutar al cargar la pagina
$( document ).ready(function() {
	imprime_cliente();
});
</script>
</head>
<body>
<textarea name="texto" id="texto" style="display: none"><?php echo $texto; ?></textarea>
<div id="impresion_box"></div>
</body>
</html>