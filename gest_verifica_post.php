 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "186";
require_once("includes/rsusuario.php");


//Solo de uso ventas caja registradora

if (isset($_POST['fnteclasesp'])) {
    //update en preferencias
    $valor = intval($_POST['fnteclasesp']);
    if ($valor == 0) {
        $valor = 1;
    } else {
        $valor = 0;
    }
    $update = "Update preferencias set activa_reg_teclaesp=$valor where idempresa=$idempresa ";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
}


?>
