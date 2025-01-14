 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "29";
$submodulo = "354";
$dirsup = "S";
require_once("includes/rsusuario.php");

// validaciones basicas
$valido = "S";
$errores = "";

$idpedidodetdatos = intval($_POST['id']);
if ($idpedidodetdatos == 0) {
    $valido = "N";
    $errores = "- No se envio el id.";
}



// si todo es correcto inserta
if ($valido == "S") {
    // borrar
    $consulta = " 
    delete from tmp_detalles_eventos_factu 
    where 
    iddet=$idpedidodetdatos 
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    //header("location: cat_adm_pedidos_new.php");
    //exit;
}
/*
// genera array con los datos
$arr = array(
'valido' => $valido,
'errores' => $errores
);

//print_r($arr);

// convierte a formato json
$respuesta=json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// devuelve la respuesta formateada
echo $respuesta;
*/

?>
