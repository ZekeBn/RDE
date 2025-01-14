<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");

$idtransaccion = intval($_GET['idt']);
$idordencompra = intval($_GET['ocnum']);

require_once("../compras_ordenes/preferencias_compras_ordenes.php");
require_once("../insumos/preferencias_insumos_listas.php");
require_once("./preferencias_compras.php");
require_once("../deposito/preferencias_deposito.php");



$deposito_por_defecto = 0;
if ($preferencia_autosel_compras == "S") {
    $select = "select iddeposito from gest_depositos where autosel_compras = 'S'";
    $rs_activo = $conexion->Execute($select) or die(errorpg($conexion, $select));
    $deposito_por_defecto = intval($rs_activo->fields['iddeposito']);
}

$consulta = "SELECT id_medida FROM medidas where  medidas.nombre like \"%UNIDADES%\" ";
$rs_medida = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idmedida_unidad = $rs_medida->fields['id_medida'];
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
    //limpiando carrito al generar auto
    $delete = "delete from tmpcompradeta where idt=$idtransaccion";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));
    $delete = "delete from tmpcompradetaimp where idtran = $idtransaccion";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));


    $preferencia_add = null;
    $preferencia_add2 = null;
    $preferencia_add3 = null;
    $medidas_valor = 0;
    // if($preferencias_medidas_referenciales == "S" || $preferencias_medidas_edi == "S"){
    // 	$medidas_valor=;
    // }
    if ($preferencias_facturas_multiples == "S") {
        $preferencia_add = " (compras_ordenes_detalles.cantidad - COALESCE(cmp_comprado.total_comprado, 0)) AS  cantidad, compras_ordenes_detalles.idmedida ";
        $preferencia_add3 = " (compras_ordenes_detalles.cantidad - COALESCE(cmp_comprado.total_comprado, 0))";
        $preferencia_add2 = " LEFT JOIN (
			SELECT compras_detalles.codprod, SUM(compras_detalles.cantidad) AS total_comprado
			FROM compras_detalles
			INNER JOIN compras ON compras.idcompra = compras_detalles.idcompra AND compras.ocnum = $idordencompra and compras.estado != 6
			GROUP BY compras_detalles.codprod
			) AS cmp_comprado ON cmp_comprado.codprod = compras_ordenes_detalles.idprod ";
        $preferencias_add4 = "AND (compras_ordenes_detalles.cantidad - COALESCE(cmp_comprado.total_comprado, 0)) > 0";
    } else {
        $preferencia_add = " cantidad ";
        $preferencia_add3 = " cantidad";
        $preferencia_add2 = " ";
        $preferencias_add4 = "";
    }



    // carga la cabecera

    // carga el detalle
    $consulta = "
	 select 
	 $idtransaccion, idprod, 1, $preferencia_add, precio_compra, NULL, 0, 0, NULL, 0, 
	 $preferencia_add3*precio_compra as subtotal, compras_ordenes_detalles.precio_compra_total as subtotal1, descripcion, NULL, 1, NULL, 1, 
	 (select tipoiva from insumos_lista where idinsumo = compras_ordenes_detalles.idprod ) as iva,
	 (select idtipoiva from insumos_lista where idinsumo = compras_ordenes_detalles.idprod ) as idtipoiva,
	 0,0,
	 NULL, precio_compra, 0, 0, NULL, 0, 0, 0, 0, 0, 0, 
	 0, NULL, NULL, ocseria, COALESCE(idmedida, 0), compras_ordenes.idtipo_moneda as moneda_id, (SELECT  idcot from tmpcompras where idtran =  $idtransaccion) as idcot_tmp
	 from compras_ordenes_detalles
	 INNER JOIN compras_ordenes ON compras_ordenes.ocnum = compras_ordenes_detalles.ocnum
	 $preferencia_add2
	 where 
	 compras_ordenes.ocnum = $idordencompra
	 and compras_ordenes.estado = 2
	 and compras_ordenes.carga_completa = 'N'
	 and compras_ordenes_detalles.ocseria not in 
	 	(
		 select ocseria from tmpcompradeta 
		 where 
		 tmpcompradeta.ocseria is not null
		 and tmpcompradeta.idt = $idtransaccion
		 )
		 $preferencias_add4
	
	";

    $rs_detalle = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $moneda_id = $rs_detalle->fields['moneda_id'];
    $idmoneda_orden = $moneda_id;
    $no_mostrar_json = 1;
    $idcot = $rs_detalle->fields['idcot_tmp'];
    require_once("../cotizaciones/cotizaciones_hoy.php");
    $cotizacion = 1;
    if ($preferencias_importacion == "S") {
        if ($res['success'] == true && $res['cotiza'] == true) {
            $cotizacion = $res['cotizacion'];
        }
    }
    while (!$rs_detalle->EOF) {

        $idprod = $rs_detalle->fields['idprod'];
        $cantidad = $rs_detalle->fields['cantidad'];
        $precio_compra = $rs_detalle->fields['precio_compra'] * floatval($cotizacion);
        $descripcion = $rs_detalle->fields['descripcion'];
        $iva = $rs_detalle->fields['iva'];
        $idtipoiva = $rs_detalle->fields['idtipoiva'];
        $ocseria = $rs_detalle->fields['ocseria'];
        $idmedida = $rs_detalle->fields['idmedida'];
        // $subtotal = $cantidad*$precio_compra;
        $subtotal = $rs_detalle->fields['subtotal1'] * floatval($cotizacion);
        // $subtotal = $rs_detalle->fields['idcot_tmp'];


        if ($preferencias_importacion == "N") {
            $idmedida = $idmedida_unidad;
        }


        $consulta = "
	INSERT INTO tmpcompradeta
	(idt, idprod, idemp, cantidad, costo, idtfk, categoria, subcate, tracking,  precioventa,
	 subtotal, pchar, contenido, sucursal, hoja,  existe, 
	 iva, 
	 idtipoiva,
	 preciomin, preciomax, 
	 listaprecios, medida, vto, gar, comentario, p1, p2, p3, costo2, costohacienda, costoproveedor, 
	 costootramoneda, lote, vencimiento, ocseria,idmedida,iddeposito_tmp) 
	 VALUES 
			($idtransaccion, $idprod, 1, $cantidad, $precio_compra, NULL, 0, 0, NULL, 0, 
			$subtotal, '$descripcion', NULL, 1, NULL, 1, 
			 $iva,
			 $idtipoiva,
			0,0,
			NULL, $precio_compra, 0, 0, NULL, 0, 0, 0, 0, 0, 0, 
			0, NULL, NULL, $ocseria, $idmedida, $deposito_por_defecto
			)
	";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $rs_detalle->MoveNext();
    }
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
    ///////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////
    //////////////generar iva
    $consulta = "SELECT idtipo_origen FROM tipo_origen WHERE  UPPER(tipo)='IMPORTACION'";
    $rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id_tipo_origen_importacion = intval($rs_guarani->fields["idtipo_origen"]);
    $consulta = "
		select idtipo_origen 
		from tmpcompras
		where 
		idtran = $idtransaccion  
		limit 1
		";
    $rs_tipo_origen = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idtipo_origen = $rs_tipo_origen->fields['idtipo_origen'];
    if ($idtipo_origen != $id_tipo_origen_importacion) {
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


    header("location: compras_detalles.php?id=".$idtransaccion);
    exit;


}
