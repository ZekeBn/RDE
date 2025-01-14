 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "333";
require_once("includes/rsusuario.php");

//print_r($_POST);
// validaciones basicas
$valido = "S";
$errores = "";

//http://localhost/ekaru/central_pedidos_fran_transf.php?idpedido=609&idfranquicia=7

// validaciones que nunca deberia de suceder mensajes solo para ambiente de desarrollo
$idpedido = intval($_REQUEST['idpedido']);
if ($idpedido == 0) {
    echo "Codigo de Pedido no enviado!";
    exit;
}
$idsucursal_new = intval($_REQUEST['idsucursal']);
if ($idsucursal_new == 0) {
    echo "Codigo de sucursal no enviado!";
    exit;
}
$consulta = "
select tmp_ventares_cab.idtmpventares_cab, idsucursal
from tmp_ventares_cab
where 
tmp_ventares_cab.estado <> 6
and tmp_ventares_cab.finalizado = 'S'
and tmp_ventares_cab.registrado = 'N'
and idtmpventares_cab = $idpedido
limit 1
";
//echo $consulta;
$rsped = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idpedido = intval($rsped->fields['idtmpventares_cab']);
$idsucursal_ant = intval($rsped->fields['idsucursal']);
if ($idpedido == 0) {
    $valido = "N";
    $errores = "- El pedido seleccionado no existe, no esta activo o ya fue cobrado.".$saltolinea;
}

// validaciones reales
$consulta = "
SELECT idsucu, nombre
FROM sucursales
where
estado = 1
and idsucu = $idsucursal_new
";
$rssuc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idsucursal_new = intval($rssuc->fields['idsucu']);
if ($idsucursal_new == 0) {
    $valido = "N";
    $errores = "- La sucursal seleccionada no existe o no esta activa.".$saltolinea;
}



// si todo es correcto envia
if ($valido == "S") {
    // cambia la sucursal en nuestro sistema
    $consulta = "
    update tmp_ventares_cab 
    set 
    notificado = 'N',
    idsucursal = $idsucursal_new
    where 
    idtmpventares_cab = $idpedido
    and registrado = 'N'
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
    update tmp_ventares
    set 
    idsucursal = $idsucursal_new
    where 
    idtmpventares_cab = $idpedido
    and registrado = 'N'
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    // log de cambio de sucursal
    $consulta = "
    INSERT INTO cambio_sucursal_log
    (idpedido, idsucursal_ant, idsucursal_new, registrado_por, registrado_el) 
    VALUES 
    ($idpedido, $idsucursal_ant, $idsucursal_new, $idusu, '$ahora')    
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}



// genera array con los datos
$arr = [
    'valido' => $valido,
    'errores' => $errores
];

//print_r($arr);

// convierte a formato json
$respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// devuelve la respuesta formateada
echo $respuesta;
exit;
?>
