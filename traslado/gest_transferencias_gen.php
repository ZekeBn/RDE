<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "222";
require_once("includes/rsusuario.php");

// funciones para stock
require_once("includes/funciones_stock.php");


$idtanda = intval($_GET['id']);
if ($idtanda == 0) {
    header("location: gest_transferencias.php");
    exit;
}
/*function fecha_dif_dias($fechainicio,$fechafinal){
    $start_date = new DateTime($fechainicio);
    $end_date = new DateTime($fechafinal);
    $objeto_diferencia = $start_date->diff($end_date); // array de objeto
    //print_r($objeto_diferencia); // para ver estructura del objeto
    $signo=""; // por defecto positivo
    if($objeto_diferencia->invert == 1){
        $signo="-";
    }
    $dias_dif=$objeto_diferencia->days; // ver en estructura del objeto
    $res=floatval($signo.$dias_dif); // https://www.php.net/manual/es/dateinterval.format.php
    return $res;

}
*/

//Buscamos tanda activa
$buscar = "select * from gest_transferencias where estado=1 and idtanda = $idtanda";
$rstanda = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idtanda = intval($rstanda->fields['idtanda']);
$fecha_transferencia = $rstanda->fields['fecha_transferencia'];
if ($idtanda == 0) {
    header("location: gest_transferencias.php");
    exit;
}

// valida deposito de transito
$consulta = "
select * from gest_depositos where tiposala = 3 order by iddeposito asc limit 1
";
$rstran = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito_transito = intval($rstran->fields['iddeposito']);
if ($iddeposito_transito == 0) {
    echo "Deposito de transito inexistente.";
    exit;
}
$consulta = "
select tipo_pantalla, confirma_auto from preferencias_transfer limit 1
";
$rspreftran = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tipo_pantalla = $rspreftran->fields['tipo_pantalla'];
$confirma_auto = $rspreftran->fields['confirma_auto'];
$confirmado = "N";
if ($confirma_auto == 'S') {
    $confirmado = "S";
}

// datos de transferencia
$estado = intval($rstanda->fields['estado']);
$origen = intval($rstanda->fields['origen']);
$destino = intval($rstanda->fields['destino']);
if ($rstanda->fields['fecha_transferencia'] != '') {
    $fechis = date("Y-m-d", strtotime($rstanda->fields['fecha_transferencia']));
}

// update que actualice el stock actual, no se puede restringir por fecha por que hace 5 minutos pudo haber cambiado el stock
$ahora_date = date("Y-m-d");
$consulta = "
update stock_minimo 
set stock_actual = 
	COALESCE((
	SELECT sum(disponible)
	FROM gest_depositos_stock_gral 
	where 
	gest_depositos_stock_gral.iddeposito = stock_minimo.iddeposito 
	and gest_depositos_stock_gral.idempresa = $idempresa 
	and gest_depositos_stock_gral.idproducto = stock_minimo.idinsumo 
	),0),
	ult_actualizacion = '$ahora'
where
stock_minimo.iddeposito = $destino
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



/*
$consulta="
select idstockmin, iddeposito, idubicacion, idinsumo, stock_minimo, stock_actual,
stock_ideal, idsucursal, idempresa, ult_actualizacion, (stock_ideal-stock_actual) as reponer
from stock_minimo
where
iddeposito = $destino
and stock_actual < stock_minimo
";
$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
*/

// inserta en tanda de transferencia
$consulta = "
select 
stock_minimo.idinsumo, insumos_lista.descripcion,
(select idprod_serial from productos where idprod_serial = insumos_lista.idproducto) as idproducto,
stock_minimo.stock_actual as stock_actual_destino,
stock_minimo.stock_ideal as stock_ideal_destino,
insumos_lista.cant_paquete,
	(
	SELECT sum(disponible)
	FROM gest_depositos_stock_gral 
	where 
	gest_depositos_stock_gral.iddeposito = $origen
	and gest_depositos_stock_gral.idproducto = stock_minimo.idinsumo 
	) as stock_actual_origen
from stock_minimo 
inner join insumos_lista on insumos_lista.idinsumo = stock_minimo.idinsumo
where 
stock_minimo.iddeposito = $destino 
and COALESCE(stock_minimo.stock_actual,0) <  COALESCE(stock_minimo.stock_minimo,0)
and stock_minimo.idinsumo not in (select idproducto from tmp_transfer where idtanda = $idtanda)
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// borra los faltantes en esta tanda para volver a generar
$consulta = "
delete FROM tmp_transfer_faltan where idtanda = $idtanda
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

while (!$rs->EOF) {

    $idproducto = $rs->fields['idinsumo'];
    $idproducto_ven = intval($rs->fields['idproducto']);
    $descripcion = antisqlinyeccion($rs->fields['descripcion'], "text");
    $stock_actual_destino = floatval($rs->fields['stock_actual_destino']);
    //echo $stock_actual_destino;echo $idproducto;exit;
    $stock_ideal_destino = floatval($rs->fields['stock_ideal_destino']);
    $stock_actual_origen = floatval($rs->fields['stock_actual_origen']);
    $cant_paquete = floatval($rs->fields['cant_paquete']);
    // asumir valores negativos como cero en origen
    if ($stock_actual_origen < 0) {
        $stock_actual_origen = 0;
    }
    // asumir valores negativos como cero en destino
    if ($stock_actual_destino < 0) {
        $stock_actual_destino = 0;
    }
    // despues de cerar negativos calcular la necesidad
    $necesidad_destino = $stock_ideal_destino - $stock_actual_destino;
    // si la necesidad es mayor al stock del centro de distribucion
    if ($necesidad_destino >= $stock_actual_origen) {
        $cantidad_envio = $stock_actual_origen; // envia todo lo que hay en origen
    } else {
        $cantidad_envio = $necesidad_destino;
    }


    // si hay paguetes
    if ($cant_paquete > 0) {
        $pack_envio = round($cantidad_envio / $cant_paquete, 0); // ceil
        $cantidad_envio = $cantidad_envio * $cant_paquete;
    }


    // INICIO DATOS ADICIONALES
    if ($tipo_pantalla == 'C') {

        // ultima reposicion
        $consulta = "
		select gest_depositos_mov.*, gest_transferencias.fecha_transferencia 
		from gest_depositos_mov 
		inner join gest_transferencias on gest_transferencias.idtanda = gest_depositos_mov.idtanda
		where 
		gest_transferencias.destino = $destino 
		and gest_depositos_mov.idproducto = $idproducto
		and gest_depositos_mov.idtanda <> $idtanda
		order by gest_transferencias.fecha_transferencia desc
		limit 1
		";
        //echo $consulta;exit;
        $rsultrepo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // ultima venta
        if ($idproducto_ven > 0) {
            $consulta = "
			select ventas.idventa, date(fecha) as fecha
			from ventas
			inner join ventas_detalles on ventas_detalles.idventa = ventas.idventa
			where
			ventas_detalles.idprod = $idproducto_ven
			and ventas.estado <> 6
			and ventas.iddeposito = $destino
			order by ventas.idventa desc
			limit 1 
			";
            $rsultven = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idventa_ult = intval($rsultven->fields['idventa']);
            if ($idventa_ult > 0) {
                $consulta = "
				select sum(cantidad) as cantidad  
				from ventas_detalles
				where 
				idventa = $idventa_ult
				and idprod = $idproducto_ven
				";
                $rsultvendet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }
        } //  if($idproducto_ven > 0){


    } // if($tipo_pantalla == 'C'){

    $cant_ultrepo = antisqlinyeccion($rsultrepo->fields['cantidad'], "float");
    $fecha_ultrepo = antisqlinyeccion($rsultrepo->fields['fecha_transferencia'], "text");
    $fecha_ultvta = antisqlinyeccion(trim($rsultven->fields['fecha']), "text");
    $cant_ultvta = antisqlinyeccion($rsultvendet->fields['cantidad'], "float");
    $reposicion_sugerida = floatval($cantidad_envio);
    if (floatval($stock_actual_destino) <= 0) {
        if (trim($rsultven->fields['fecha']) != '') {
            $dias_quiebre = fecha_dif_dias(date("Y-m-d", strtotime($rsultven->fields['fecha'])), date("Y-m-d"));
        } else {
            $dias_quiebre = 0;
        }
    } else {
        $dias_quiebre = 0;
    }


    // FIN DATOS ADICIONALES



    if ($cantidad_envio > 0) {
        $consulta = "
		INSERT INTO tmp_transfer
		(
		idtanda, idproducto, descripcion, cantidad, necesidad, horareg, 
		cant_ultrepo, fecha_ultrepo, cant_ultvta, fecha_ultvta,
		stock_origen, stock_destino, stock_ideal_destino,
		reposicion_sugerida, dias_quiebre, confirmado
		)
		values
		(
		$idtanda, $idproducto, $descripcion, $cantidad_envio, $necesidad_destino, '$ahora',
		$cant_ultrepo, $fecha_ultrepo, $cant_ultvta, $fecha_ultvta,
		$stock_actual_origen, $stock_actual_destino, $stock_ideal_destino,
		$reposicion_sugerida, $dias_quiebre, '$confirmado'
		)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        /*
        $consulta="
        INSERT INTO tmp_transfer
        (idtanda, idproducto, descripcion, cantidad, necesidad, horareg)
        values
        ($idtanda, $idproducto, $descripcion, $cantidad_envio, $necesidad_destino, '$ahora')
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    */
    } else {
        $consulta = "
		INSERT INTO tmp_transfer_faltan
		(
		idtanda, idproducto, descripcion, cantidad, necesidad, horareg,
		cant_ultrepo, fecha_ultrepo, cant_ultvta, fecha_ultvta,
		stock_origen, stock_destino, stock_ideal_destino,
		reposicion_sugerida, dias_quiebre
		)
		values
		(
		$idtanda, $idproducto, $descripcion, $cantidad_envio, $necesidad_destino, '$ahora',
		$cant_ultrepo, $fecha_ultrepo, $cant_ultvta, $fecha_ultvta,
		$stock_actual_origen, $stock_actual_destino, $stock_ideal_destino,
		$reposicion_sugerida, $dias_quiebre
		)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        /*
        $consulta="
        INSERT INTO tmp_transfer_faltan
        (idtanda, idproducto, descripcion, cantidad, necesidad, horareg)
        values
        ($idtanda, $idproducto, $descripcion, $cantidad_envio, $necesidad_destino, '$ahora')
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
        */
    }

    $rs->MoveNext();
}




header("location: gest_transferencias_det.php?id=$idtanda");
exit;
