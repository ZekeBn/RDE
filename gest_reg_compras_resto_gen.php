<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "31";
require_once("includes/rsusuario.php");

$idtransaccion = intval($_GET['idt']);
$idordencompra = intval($_GET['ocnum']);
if ($idtransaccion == 0) {
    echo "No indico la transaccion de compra.";
    exit;
}
if ($idordencompra == 0) {
    echo "No indico la orden de compra.";
    exit;
}


// busca el proveedor de la compra temporal

// busca el proveedor de la orden de compra

// valida que coincian ambos proveedores

if ($idtransaccion > 0 && $idordencompra > 0) {

    // carga la cabecera

    // carga el detalle
    $consulta = "
	INSERT INTO tmpcompradeta
	(idt, idprod, idemp, cantidad, costo, idtfk, categoria, subcate, tracking,  precioventa,
	 subtotal, pchar, contenido, sucursal, hoja,  existe, 
	 iva, 
	 idtipoiva,
	 preciomin, preciomax, 
	 listaprecios, medida, vto, gar, comentario, p1, p2, p3, costo2, costohacienda, costoproveedor, 
	 costootramoneda, lote, vencimiento, ocseria) 
	 
	 select 
	 $idtransaccion, idprod, 1, cantidad, precio_compra, NULL, 0, 0, NULL, 0, 
	 cantidad*precio_compra as subtotal, descripcion, NULL, 1, NULL, 1, 
	 (select tipoiva from insumos_lista where idinsumo = compras_ordenes_detalles.idprod ) as iva,
	 (select idtipoiva from insumos_lista where idinsumo = compras_ordenes_detalles.idprod ) as idtipoiva,
	 0,0,
	 NULL, precio_compra, 0, 0, NULL, 0, 0, 0, 0, 0, 0, 
	 0, NULL, NULL, ocseria
	 from compras_ordenes_detalles
	 inner join compras_ordenes on compras_ordenes.ocnum = compras_ordenes_detalles.ocnum
	 where 
	 compras_ordenes.ocnum = $idordencompra
	 and compras_ordenes.estado = 2
	 and compras_ordenes_detalles.ocseria not in 
	 	(
		 select ocseria from tmpcompradeta 
		 where 
		 tmpcompradeta.ocseria is not null
		 and tmpcompradeta.idt = $idtransaccion
		 )
	
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // borra la tabla de impuestos para esta transaccion
    $consulta = "
	 delete from tmpcompradetaimp 
	 where 
	 idtrandet in (
		 select tmpcompradeta.idregcc
		 from tmpcompradeta
		 where
		 tmpcompradeta.ocseria is not null
		 and tmpcompradeta.idt = $idtransaccion
	 )
	 and idtran = $idtransaccion
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
	 select *
	 from tmpcompradeta
	 where
	 tmpcompradeta.ocseria is not null
	 and tmpcompradeta.idt = $idtransaccion
	 order by idregcc asc
	";
    $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    while (!$rsdet->EOF) {
        $idtipoiva = $rsdet->fields['idtipoiva'];
        $subtotal = $rsdet->fields['subtotal'];
        $idregcc = $rsdet->fields['idregcc'];
        //$subtotal=$subt;
        if (intval($idtipoiva) == 0) {
            $idtipoiva = 1;
        }
        $consulta = "
		select idtipoiva, iva_porc, iva_describe, iguala_compra_venta, 
		estado, hab_compra, hab_venta
		from tipo_iva 
		where
		idtipoiva = $idtipoiva
		";
        $rsbimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $iva_porc = floatval($rsbimp->fields['iva_porc']); // alicuota
        $subtotal_monto_iva = calcular_iva($iva_porc, $subtotal);
        $base_imponible = $subtotal - $subtotal_monto_iva;


        $consulta = "
		SELECT 
		idtipoiva, iva_porc, monto_porc, exento 
		FROM tipo_iva_detalle 
		WHERE 
		idtipoiva = $idtipoiva
		";
        $rsivadet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        while (!$rsivadet->EOF) {

            $gravadoml = $base_imponible * ($rsivadet->fields['monto_porc'] / 100);
            $ivaml = $gravadoml * ($rsivadet->fields['iva_porc'] / 100);
            $exento = $rsivadet->fields['exento'];
            $iva_porc_col = $rsivadet->fields['iva_porc'];
            $monto_col = $gravadoml + $ivaml;

            //ventas_detalles_impuesto
            $consulta = "
			INSERT INTO tmpcompradetaimp
			(idtran, idtrandet, iva_porc_col, monto_col, 
			gravadoml, ivaml, exento) 
			VALUES 
			($idtransaccion, $idregcc, $iva_porc_col, $monto_col,
			$gravadoml,  $ivaml,  '$exento'
			)
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $rsivadet->MoveNext();
        }

        $rsdet->MoveNext();
    }

    // temporal, arreglar para que calcule bien el iva
    /*$consulta="
    INSERT INTO tmpcompradetaimp
    ( idtran, idtrandet, iva_porc_col, monto_col, gravadoml, ivaml, exento, idcompradet)
    select
    tmpcompradeta.idt, tmpcompradeta.idregcc, tmpcompradeta.iva, tmpcompradeta.subtotal, 0, 0, 'S', NULL
    from tmpcompradeta
    WHERE
    idt = $idtransaccion
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));*/

    $consulta = "
	update tmpcompras set ocnum = $idordencompra where idtran = $idtransaccion
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    header("location: gest_reg_compras_resto_det.php?id=".$idtransaccion);
    exit;


}
