<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$dirsup = "S";
$submodulo = "134";
require_once("../includes/rsusuario.php");


//print_r($_POST);
$iddeposito = $_GET['id'];
$idinsumo = intval($_GET['idinsumo']);




// recibe parametros
$fecha_inicio = antisqlinyeccion($ahora, "text");
$iniciado_por = antisqlinyeccion($idusu, "int");
$finalizado_por = antisqlinyeccion('', "int");
$inicio_registrado_el = antisqlinyeccion($ahora, "text");
$final_registrado_el = antisqlinyeccion('', "text");
$estado = antisqlinyeccion(1, "int");
$afecta_stock = antisqlinyeccion('N', "text");
$fecha_final = antisqlinyeccion('', "text");
$observaciones = antisqlinyeccion(' ', "text");
$iddeposito = antisqlinyeccion($iddeposito, "int");
$totinsu = intval($_POST['totinsu']);

$tipo_conteo = antisqlinyeccion(2, "int");

if ($idinsumo > 0) {
    $tipo_contep = 2;
}
// validaciones basicas
$valido = "S";
$errores = "";


if ($iddeposito == 0) {
    $valido = "N";
    $errores .= " - Debes seleccionar el deposito.<br />";
}
// buscamos que exista el deposito y su sucursal
$consulta = "
	select * from gest_depositos
	where 
	idempresa = $idempresa
	and estado = 1
	and iddeposito = $iddeposito
	";
$rsdep = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito = intval($rsdep->fields['iddeposito']);
$idsucu = intval($rsdep->fields['idsucursal']);
if ($iddeposito == 0) {
    $valido = "N";
    $errores .= " - Deposito inexistente.<br />";
}




//$valido="N";
// validaciones especificas

// no se puede iniciar un conteo por que este deposito ya tiene activo otro con el mismo grupo de insumos






// si todo es correcto inserta
if ($valido == "S") {
    $idconteo = select_max_id_suma_uno("conteo", "idconteo")["idconteo"];

    $consulta = "
		insert into conteo
		(idconteo, fecha_inicio, iniciado_por, finalizado_por, estado, afecta_stock, fecha_final, observaciones,  idsucursal, idempresa, iddeposito, inicio_registrado_el, final_registrado_el, tipo_conteo, idinsumo)
		values
		($idconteo, $fecha_inicio, $iniciado_por, $finalizado_por, $estado, $afecta_stock, $fecha_final, $observaciones,  $idsucu, $idempresa, $iddeposito, $inicio_registrado_el, $final_registrado_el, $tipo_conteo, $idinsumo)
		";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    if ($tipo_conteo == 2) {
        # conteo por producto que se encuentra en un deposito pero no asi en el mismo
        // alamacenamiento o tipo de almacenamiento
        header("location: conteo_stock_contar_producto.php?id=$idconteo&iddeposito=$iddeposito&idinsumo=$idinsumo");
    } elseif ($tipo_conteo == 1) {
        //conteo por tipo_almacenamiento que pertenecen a un deposito
        header("location: conteo_stock_contar_deposito.php?id=$idconteo&iddeposito=$iddeposito");

    } else {
        //tipo_conteo == 6 es para conteo normal pero eso es en otro modulo reutiliza la tabla por eso
        // es el if
    }
    exit;

}
