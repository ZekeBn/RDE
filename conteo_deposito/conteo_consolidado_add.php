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
$conteo_consolidado = antisqlinyeccion(1, "int");//1

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






// si todo es correcto verifica para el update o para insertar
if ($valido == "S") {
    //verificar
    $idconteo = 0;
    $consulta = "SELECT 
						    idconteo 
				       FROM 
                conteo
              where 
                conteo_consolidado = 1 
                and estado = 1
                and iddeposito = $iddeposito
                and idinsumo = $idinsumo
                ";
    $rs_conteo_consolidado = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idconteo = intval($rs_conteo_consolidado->fields['idconteo']);
    ///insertar

    if ($idconteo == 0) {
        $idconteo = select_max_id_suma_uno("conteo", "idconteo")["idconteo"];

        $consulta = "
  		insert into conteo
  		(idconteo, fecha_inicio, iniciado_por, finalizado_por, estado, afecta_stock, fecha_final, observaciones,  idsucursal, idempresa, iddeposito, inicio_registrado_el, final_registrado_el, tipo_conteo, idinsumo, conteo_consolidado)
  		values
  		($idconteo, $fecha_inicio, $iniciado_por, $finalizado_por, $estado, $afecta_stock, $fecha_final, $observaciones,  $idsucu, $idempresa, $iddeposito, $inicio_registrado_el, $final_registrado_el, $tipo_conteo, $idinsumo, $conteo_consolidado)
  		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }

    //agregando el id de referencia a los conteos

    $consulta = "UPDATE conteo
		SET idconteo_ref = $idconteo
		WHERE idconteo IN (
			SELECT idconteo
			FROM conteo
			WHERE estado <> 6
			  AND conteo.iddeposito = $iddeposito
			  AND idinsumo = $idinsumo
			  AND tipo_conteo = 2
			  AND estado = 2
        AND idconteo_ref = 0
		)";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // while (!$rs_conteos_consolidar->EOF){
    // 	$sub_idconteo = intval($rs_conteos_consolidar->fields['idconteo']);
    // 	$consulta="UPDATE conteo
    // 			set
    // 				idconteo_ref=$idconteo
    // 			where
    // 				idconteo=$sub_idconteo
    // 	";
    // 	$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    // 	$rs_conteos_consolidar->MoveNext();
    // }
    //


    /////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////AGREGAR CONTEOS AL CONTEO CONSOLIDADO////////////
    /////////////////////////////////////////////////////////////////////////////////
    // no sera necesario ya que sera una referencia para mayor rastreo pero usara la misma
    // tabla
    // $consulta="INSERT INTO conteo_detalles
    // (idconteo, idinsumo, descripcion, cantidad_contada, cantidad_sistema, cantidad_venta, diferencia, diferencia_pv, diferencia_pc, precio_venta, precio_costo, idusu, ubicacion, lote, vencimiento, fechahora, idalm, fila, columna, idmedida_ref, idpasillo)
    // SELECT
    // $idconteo, conteo_detalles.idinsumo, conteo_detalles.descripcion, conteo_detalles.cantidad_contada, conteo_detalles.cantidad_sistema, conteo_detalles.cantidad_venta, conteo_detalles.diferencia, conteo_detalles.diferencia_pv, conteo_detalles.diferencia_pc, conteo_detalles.precio_venta, conteo_detalles.precio_costo, conteo_detalles.idusu, conteo_detalles.ubicacion, conteo_detalles.lote, conteo_detalles.vencimiento, conteo_detalles.fechahora, conteo_detalles.idalm, conteo_detalles.fila, conteo_detalles.columna, conteo_detalles.idmedida_ref, conteo_detalles.idpasillo
    // from conteo_detalles
    // inner join conteo on conteo.idconteo = conteo_detalles.idconteo
    // where  conteo.estado <> 6
    // 	  AND conteo.iddeposito = $iddeposito
    // 	  AND conteo.tipo_conteo = 2
    // 	  AND conteo.idinsumo = $idinsumo
    // 	  AND conteo.estado = 2
    // ";
    // $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));


    if ($tipo_conteo == 2) {
        # conteo por producto que se encuentra en un deposito pero no asi en el mismo
        // alamacenamiento o tipo de almacenamiento

        header("location: conteo_por_producto_detalle.php?id=$iddeposito&idinsumo=$idinsumo");
        // header("location: conteo_stock_contar_producto.php?id=$idconteo&iddeposito=$iddeposito&idinsumo=$idinsumo");
    } else {
        //tipo_conteo == 6 es para conteo normal pero eso es en otro modulo reutiliza la tabla por eso
        // es el if
    }
    exit;

}
