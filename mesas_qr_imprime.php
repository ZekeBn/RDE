 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");

$idatc = intval($_GET['idatc']);
if (intval($idatc) == 0) {
    echo "No se recibio el idatc.";
    exit;
}


$consulta = "
SELECT idatc, idmesa, pin
FROM mesas_atc 
where 
idatc = $idatc 
and idsucursal = $idsucursal 
and estado = 1 
";
$rsatc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idmesa = intval($rsatc->fields['idmesa']);
$idatc = intval($rsatc->fields['idatc']);
$pin = trim($rsatc->fields['pin']);
if (intval($idatc) == 0) {
    echo "La mesa no tiene ningun atc activo.";
    exit;
}
if (trim($pin) == '') {
    echo "La mesa no tiene pin asignado.";
    exit;
}

$consulta = "
select  mesas.numero_mesa, mesas.idmesa, salon.nombre as salon, mesas_atc.idatc, mesas_atc.pin, sucursales.nombre as sucursal, salon.idsalon, sucursales.idsucu
from mesas 
inner join salon on salon.idsalon = mesas.idsalon
inner join mesas_atc on mesas_atc.idmesa = mesas.idmesa
inner join sucursales on sucursales.idsucu = mesas_atc.idsucursal 
where 
 mesas.estadoex = 1 
and mesas.idmesa= $idmesa
and mesas_atc.estado = 1
order by mesas.numero_mesa asc, salon.nombre asc
";
$rsmesa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$numero_mesa = antixss($rsmesa->fields['numero_mesa']);

// centrar nombre de empresa
$nombreempresa_centrado = corta_nombreempresa($nombreempresa);
$nombre_sys_centrado = corta_nombreempresa($rsco->fields['nombre_sys']);
$nombre_sys = $rsco->fields['nombre_sys'];
$slogan_sys_centrado = corta_nombreempresa($rsco->fields['slogan_sys']);
$web_sys_centrado = corta_nombreempresa($rsco->fields['web_sys']);


// preferencias
$consulta = "SELECT * FROM preferencias WHERE  idempresa = $idempresa ";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$impresor = trim($rspref->fields['script_ticket']);
$impresor = strtolower($impresor);
if ($impresor == '') {
    $impresor = 'http://localhost/impresorweb/ladocliente_qr.php';
}
$script_impresora = $impresor;

$consulta = "
select * from empresas limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$url_sistema = strtolower(trim($_SERVER['SERVER_NAME']));
//echo $url_sistema;exit;
$contenido_qr = 'https://'.$url_sistema.'/mesas_qr/mesas_qr.php?id_mesa='.$idmesa.'&pin='.$pin;
$texto_qr .= '<QR>'.$contenido_qr.'</QR>';




$tickete = "
----------------------------------------
$nombreempresa_centrado
            ADMINISTRAR MESA            
----------------------------------------
$texto_qr
----------------------------------------
PIN : $pin
Mesa: $numero_mesa
----------------------------------------
         Mesa Smart $nombre_sys
$web_sys_centrado
";

$texto = $tickete;
/*
CAJA RECAUDACION : $montoaper
CAJA CHICA       : $cajachica
*/


$url1 = 'mesas/index.php';
$modredir = intval($_GET['mod']);
if ($modredir == 196) {
    $url1 = 'cuenta_mesas.php';
}


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
$defaultprnt = "http://localhost/impresorweb/ladocliente_qr.php";
$script_impresora_app = trim($rsimp->fields['script']);
if (trim($script_impresora_app) == '') {
    $script_impresora_app = $defaultprnt;
}

// forzar impresor
$script_impresora = 'http://localhost/impresorweb/ladocliente_qr.php';





?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Imprimir QR Mesa</title>
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
    document.location.href='mesas/index.php';
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
