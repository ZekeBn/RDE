 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "313";
require_once("includes/rsusuario.php");

$valido = "S";
$errores = "";

$idusuario = intval($_POST['idusuario']);

$consulta = "
select * 
from usuarios 
where 
idusu = $idusuario 
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$verstock_traslado = $rs->fields['verstock_traslado'];
if ($verstock_traslado == 'S') {
    $verstock_traslado = "N";
} else {
    $verstock_traslado = "S";
}
$consulta = "
update usuarios
set 
verstock_traslado = '$verstock_traslado'
where
idusu = $idusuario
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// busca en la bd el nuevo valor
$consulta = "
select * 
from usuarios 
where 
idusu = $idusuario 
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$verstock_traslado = $rs->fields['verstock_traslado'];
if ($verstock_traslado == 'S') {
    $checked = "checked";
} else {
    $checked = "";
}

$html_checkbox = '<input name="verstock" id="verstock" type="checkbox" value="S" class="js-switch" onChange="registra_permiso_ver(); " '.$checked.' />';




// genera array con los datos
$arr = [
    'html_checkbox' => $html_checkbox,
    'permitido' => $permitido, // permitido si o no
    'valido' => $valido,
    'errores' => $errores
];

//print_r($arr);

// convierte a formato json
$respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// devuelve la respuesta formateada
echo $respuesta;

?>
