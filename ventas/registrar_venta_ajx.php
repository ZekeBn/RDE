<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "21";
$submodulo = "412";
require_once("includes/rsusuario.php");

$idtmpventares_cab = intval($_GET['id']);
$idcliente = intval($_GET['idcliente']);
$consulta = "
select tmp_ventares_cab.idtmpventares_cab,
(select idcliente from cliente where ruc = tmp_ventares_cab.ruc and estado = 1 order by idcliente desc limit 1) as idcliente
from tmp_ventares_cab
where 
tmp_ventares_cab.estado <> 6
and tmp_ventares_cab.idcanal = 1
and tmp_ventares_cab.finalizado = 'S'
and tmp_ventares_cab.registrado = 'N'
and tmp_ventares_cab.idtmpventares_cab = $idtmpventares_cab
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

//$idcliente=intval($rs->fields['idcliente']);
if ($idcliente == 0) {
    echo "CLIENTE INEXISTENTE!";
    exit;
}



?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Registrando Venta</title>
<script src="js/jquery-1.10.2.min.js"></script>
<script>
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
function registrar_venta(){
	
	// INICIO REGISTRAR VENTAS //
	//alert(cual);
	var parametros = {
		"pedido"           : <?php echo $idtmpventares_cab ?>,
		"idzona"           : '', // zona costo delivery
		"idadherente"      : '',
		"idservcom"        : '', // servicio comida
		"banco"            : '',
		"adicional"        : '', // numero de cheque, tarjeta, etc
		"condventa"        : 2, // credito o contado
		"mediopago"        : 7, // forma de pago
		"fac_suc"          : '',
		"fac_pexp"         : '',
		"fac_nro"          : '',
		"domicilio"        : '', // codigo domicilio
		"llevapos"         : '',
		"cambiode"         : '',
		"observadelivery"  : '',
		"observacion"      : '',
		"mesa"             : 0,
		"canal"            : 1,
		"fin"              : 3,
		"idcliente"        : <?php echo $idcliente; ?>,
		"monto_recibido"   : '',
		"descuento"        : '',
		"motivo_descuento" : '',
		"chapa"            : '',
		"montocheque"      : '',
		"idvendedor"       : '',
		"iddeposito"       : '',
		"idmotorista"      : '',
		"json"             : 'S'
		
		
	};
	
	$.ajax({
			data:  parametros,
			url:   'registrar_venta.php',
			type:  'post',
			beforeSend: function () {
					$("#carrito").html("<br /><br />Registrando...<br /><br />");
			},
			success:  function (response) {
				if(IsJsonString(response)){
					var obj = jQuery.parseJSON(response);
					 <?php $script = "script_central_impresion.php";?>
					if(obj.error == ''){
							document.body.innerHTML='<meta http-equiv="refresh" content="0; url=script_central_impresion.php?tk=2&clase=1&v='+obj.idventa+'<?php echo $redirbus2; ?>&modventa=8">';
					}else{
						alert('No se registro la venta: '+nl2br(obj.error));
						$("#erroresbox").html(response);
					}
				}else{
					alert(response);
					$("#erroresbox").html(response);
				}
			}
	});
	
	// FIN REGISTRAR VENTAS //


}
function nl2br (str, is_xhtml) {
  var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>';
  return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}
</script>
</head>

<body onload="registrar_venta();">
Registrando venta, favor aguarde..

<div id="erroresbox"></div>
</body>
</html>