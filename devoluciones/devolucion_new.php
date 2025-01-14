<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "613";

$dirsup = "S";
require_once("../includes/rsusuario.php");


$idventa = intval($_GET['id']);

// $urlParts = parse_url($pagina_actual);



//busca si existe una devolucion activa
$consulta = "SELECT devolucion.iddevolucion 
FROM devolucion 
WHERE
	devolucion.idventa = 1
    and devolucion.estado = 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddevolucion = intval($rs->fields['iddevolucion']);
if ($iddevolucion > 0) {
    header("location: devolucion_det.php?id=$iddevolucion");

} else {

    $idempresa = 1;
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $iddevolucion = select_max_id_suma_uno("devolucion", "iddevolucion")["iddevolucion"];
    $consulta = "
    insert into devolucion 
    (iddevolucion, idventa, registrado_por, registrado_el, idempresa, estado)
    values 
    ($iddevolucion, $idventa, $registrado_por, $registrado_el, $idempresa, 1)
    ";

    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    header("location: devolucion_det.php?id=$iddevolucion");
}
