<?php
require_once("../../includes/conexion.php");
require_once("../../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
$dirsup_sec = "S";
require_once("../../includes/rsusuario.php");


if ($_POST['codigo_vrapida'] > 0) {
    $codigo_vrapida = antisqlinyeccion($_POST['codigo_vrapida'], "text");
    $consulta = "
	select idcliente, razon_social from cliente where estado=1 and ruc = $codigo_vrapida limit 1
	";
    $rsvrap = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcliente = intval($rsvrap->fields['idcliente']);
    $razon_social = $rsvrap->fields['razon_social'];
    if ($idcliente == 0) {
        $consulta = "
		select idcliente, razon_social from cliente where estado=1 and documento = $codigo_vrapida limit 1
		";
        $rsvrap = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcliente = intval($rsvrap->fields['idcliente']);
        $razon_social = $rsvrap->fields['razon_social'];
    }


    ?><div style="background-color:#FFC; font-weight:bold;"><br />
<?php
if ($idcliente > 0) {
    echo $razon_social;
} else {
    echo "No encontrado";
}
    ?><br /><br />
</div>
<?php } ?>