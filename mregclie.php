 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "259";
require_once("includes/rsusuario.php");

$valorbuscar = trim($_POST['buscar']);

if ($valorbuscar != '') {


    $buscar = "select * from cliente where ruc='$valorbuscar'";
    $rscca = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idcliente = ($rscca->fields['idcliente']);
    $razon_social = trim($rscca->fields['razon_social']);
    $idcli = intval($idcliente);

} else {

    $buscar = "select idcliente from cliente where ruc='$ruc_pred' ";
    $rscca = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idclipred = $rscca->fields['idcliente'];

    if (intval($idcliente == 0)) {
        $razon_social = $razon_social_pred;
        $idcli = intval($idclipred);
    }


}


?>
<td>Razon Social</td>
<td ><input type="text" style="height:40px;width:90%;"  name='rz' id='rz' value="<?php echo $razon_social ?>"  /></td>
<td><input type="hidden" name="ocidcliente" id="ocidcliente" value="<?php echo $idcli?>" /></td>
