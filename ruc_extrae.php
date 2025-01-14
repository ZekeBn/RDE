 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

// tiempo de espera
set_time_limit(120);

// recibe parametro
/*$vruc=intval($_REQUEST['ruc']);
$ruc_comp=$vruc.'-'.calcular_ruc($vruc);
*/
$ruc_comp = trim($_REQUEST['ruc']);

// consulta en cliente y si no existe en hacienda
$parametros_array = [
    'ruc' => $ruc_comp
];
// respuesta
$res = ruc_hacienda($parametros_array);

// convierte a formato json
$respuesta = json_encode($res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// devuelve la respuesta formateada
echo $respuesta;
exit;
?>
