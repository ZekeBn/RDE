 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "12";
$submodulo = "86";
require_once("includes/rsusuario.php");

header("location: gest_administrar_caja.php");
exit;

?>
