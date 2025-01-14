<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "22";
require_once("includes/rsusuario.php");


// busca si hay una caja abierta por este usuario
$buscar = "
Select *, 
(select usuario from usuarios where idusu = caja_super.cajero) as cajero
from caja_super 
where 
estado_caja=1 
and cajero=$idusu
order by fecha_apertura desc 
limit 1
";
$rscaj = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaj->fields['idcaja']);
// si no existe caja abierta redirecciona
if ($idcaja == 0) {
    header("location: gest_administrar_caja.php");
    exit;
}

// centrar nombre de empresa
$nombreempresa_centrado = corta_nombreempresa($nombreempresa);
$fecha_apertura = date("d/m/Y H:i:s", strtotime($rscaj->fields['fecha_apertura']));
$fecha_cierre = date("d/m/Y H:i:s", strtotime($rscaj->fields['fecha_cierre']));
$cajero = $rscaj->fields['cajero'];
$montoaper = formatomoneda($rscaj->fields['monto_apertura']);
$cajachica = formatomoneda($rscaj->fields['caja_chica']);
$tefec = formatomoneda($rscaj->fields['total_efectivo']);

// preferencias
$consulta = "SELECT * FROM preferencias WHERE  idempresa = $idempresa ";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$impresor = trim($rspref->fields['script_ticket']);
$impresor = strtolower($impresor);
if ($impresor == '') {
    $impresor = 'http://localhost/impresorweb/ladocliente.php';
}
$script_impresora = $impresor;

$tickete = "
----------------------------------------
$nombreempresa_centrado
            APERTURA DE CAJA            
----------------------------------------
FECHA APERTURA : $fecha_apertura
NRO CAJA       : $idcaja
SUCURSAL       : $nombresucursal
CAJERO         : $cajero 
----------------------------------------
           MONTOS DE INICIO
MONTO APERTURA : $montoaper 
----------------------------------------
";

$texto = $tickete;
/*
CAJA RECAUDACION : $montoaper
CAJA CHICA       : $cajachica
*/


$url1 = 'gest_administrar_caja.php';
/*ApiChannel.postMessage('<?php

$parametros_array_tk=array(
    'texto_imprime' => $texto,
    'url_redir' => $url1
);
echo texto_para_app($parametros_array_tk);

?>');*/
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

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Apertura de Caja</title>
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript">

function imprime_cliente(){
	if(!(typeof ApiChannel === 'undefined')){

							   
		ApiChannel.postMessage('<?php
        // lista de post a enviar
        if ($metodo_app == 'POST_URL') {
            $lista_post = [
                'tk' => $texto,
                'tk_json' => '' // en ticket usar $ticket_json  // no aplica en factura
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
							   
							   
	if((typeof ApiChannel === 'undefined')){						   
		var texto = document.getElementById("texto").value;
        var parametros = {
                "tk"            : texto,
				"duplic_control" : "N"
        };
       $.ajax({
                data:  parametros,
                url:   '<?php echo $script_impresora; ?>',
                type:  'post',
				dataType: 'html',
                beforeSend: function () {
                        $("#impresion_box").html("Enviando Impresion...");
                },
				crossDomain: true,
                success:  function (response) {
						//$("#impresion_box").html(response);	
						//si impresion es correcta marcar
						var str = response;
						var res = str.substr(0, 18);
						//alert(res);
						if(res == 'Impresion Correcta'){
							document.location.href='<?php echo $url1; ?>';
						}else{
							$("#impresion_box").html(response);	
						}
						
                }
        });
			
	}
	
}
function volver_caja(){
	document.location.href='gest_administrar_caja.php';
}	
</script>
</head>
<body bgcolor="#CCCCCC" onLoad="imprime_cliente()">
<div style="width:320px; border:1px solid; margin:0px auto; background-color:#FFFFFF; padding:5px; text-align:center; min-height:50px;" id="impresion_box">
<p align="center"><input type="button" value="imprimir" style="padding:10px;" onmouseup="imprime_cliente();"></p>
</div><br />
<div style="width:320px; border:1px solid; margin:0px auto; background-color:#FFFFFF; padding:5px; text-align:center; min-height:50px;" >
<p align="center"><input type="button" value="Volver a la Caja" style="padding:10px;" onmouseup="volver_caja();"></p>
</div><br />
<div style="width:320px; border:1px solid; margin:0px auto; background-color:#FFFFFF; padding:5px;">
<textarea readonly id="texto" style="display:; width:315px; height:300px;"><?php echo $texto; ?></textarea>
<pre>
<?php //echo $texto;?>
</pre>
</div>

</body>
</html>