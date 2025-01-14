<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");


$token = antisqlinyeccion(trim($_REQUEST['token']), "text");

$consulta = "
select * 
from app_usuarios 
where 
token = $token
and bloqueado = 'N' 
and estado = 1
limit 1
";
$rsapius = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idusu = intval($rsapius->fields['idusu']);

if (intval($rsapius->fields['idusu']) == 0) {
    // validar usuario y clave
    $arr = ['valido' => 'N', 'errores' => 'Token Incorrecto'];
    $respuesta = [
        'status' => 0,
        'data' => $arr
    ];
    // convierte a formato json
    $respuesta = json_encode($respuesta, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

    // devuelve la respuesta formateada
    echo $respuesta;
    exit;
}
