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
$muestra_traslado_pendientesrecep = $rs->fields['muestra_traslado_pendientesrecep'];
if ($muestra_traslado_pendientesrecep == 'S') {
    $muestra_traslado_pendientesrecep = "N";
} else {
    $muestra_traslado_pendientesrecep = "S";
}
$consulta = "
update usuarios
set 
muestra_traslado_pendientesrecep = '$muestra_traslado_pendientesrecep'
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
$muestra_traslado_pendientesrecep = $rs->fields['muestra_traslado_pendientesrecep'];
if ($muestra_traslado_pendientesrecep == 'S') {
    $checked = "checked";
} else {
    $checked = "";
}

$html_checkbox = '<input name="ver_pendrecep" id="ver_pendrecep" type="checkbox" value="S" class="js-switch" onChange="registra_permiso_ver_recep(); " '.$checked.' />';




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
