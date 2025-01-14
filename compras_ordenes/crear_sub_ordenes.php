<?php

require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");

require_once("../compras_ordenes/preferencias_compras_ordenes.php");
require_once("../insumos/preferencias_insumos_listas.php");

$ocn = intval($_POST['ocn']);

//verificando cotizacion
$consulta = "select tipo_moneda.idtipo as idmoneda_select
from compras_ordenes
LEFT JOIN cotizaciones on compras_ordenes.idcot = cotizaciones.idcot
LEFT JOIN tipo_moneda ON tipo_moneda.idtipo = cotizaciones.tipo_moneda
where ocnum = $ocn";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idmoneda_orden = $rs->fields['idmoneda_select'];
$no_mostrar_json = 1;
require_once("../cotizaciones/cotizaciones_hoy.php");

$consulta = "
	select ocnum from compras_ordenes
	where ocnum_ref = $ocn and estado = 1
";
$rs_oc_pendiente = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$oc_pendiente = intval($rs_oc_pendiente->fields['ocnum']);


if ($res['success'] == true and $oc_pendiente == 0) {




    $buscar = "SELECT cant_dias,inicia_pago
	FROM compras_ordenes
	where ocnum = $ocn 
	";

    $rs_orden_padre = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $inicia_pago = antisqlinyeccion($rs_orden_padre->fields['inicia_pago'], 'text');
    $cant_dias = antisqlinyeccion($rs_orden_padre->fields['cant_dias'], 'text');

    if ($res['cotiza'] == false) {
        $idcot = 0;
    } else {
        $idcot = $res['idcot'];
    }

    $ocnum_nuevo = select_max_id_suma_uno("compras_ordenes", "ocnum")["ocnum"];
    $consulta = "
	insert into compras_ordenes
	(ocnum, fecha, generado_por, tipocompra, fecha_entrega, idproveedor, estado, forma_pago, cant_dias, inicia_pago, registrado_por, registrado_el,idtipo_moneda,idcot,idtipo_origen,ocnum_ref,carga_completa,estado_orden)
	select
	$ocnum_nuevo, fecha, $idusu, tipocompra, '$ahora', idproveedor, 1, 0, $cant_dias, $inicia_pago, $idusu, '$ahora',idtipo_moneda,$idcot,idtipo_origen,ocnum,'N',1
	from compras_ordenes where ocnum = $ocn 
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    // ///////creando detalle
    // verificar el select frande solo busca en ordenes
    // $insertar="Insert into compras_ordenes_detalles
    // 	(ocnum,idprod,cantidad,cant_transito,precio_compra,descripcion,precio_compra_total,idmedida,descuento)
    // SELECT $ocnum_nuevo,cmp_dt.idprod,
    // (cmp_dt.cantidad - COALESCE(cmp_comprado.total_comprado, 0)) AS cantidad_faltante,
    // (cmp_dt.cantidad - COALESCE(cmp_comprado.total_comprado, 0)) AS cantidad_faltante,
    // cmp_dt.precio_compra,cmp_dt.descripcion,cmp_dt.precio_compra_total,cmp_dt.idmedida,cmp_dt.descuento
    // 		FROM compras_ordenes_detalles AS cmp_dt
    // 		INNER JOIN compras_ordenes AS cmp ON cmp.ocnum = cmp_dt.ocnum
    // 		LEFT JOIN (
    // 			SELECT cmp_det.idprod, SUM(cmp_det.cantidad) AS total_comprado
    // 			FROM compras_ordenes_detalles AS cmp_det
    // 			INNER JOIN compras_ordenes AS cmp ON cmp.ocnum = cmp_det.ocnum and cmp.estado !=6
    // 			AND cmp.ocnum_ref = $ocn
    // 			GROUP BY cmp_det.idprod
    // 			) AS cmp_comprado ON cmp_comprado.idprod = cmp_dt.idprod
    // 		WHERE cmp_dt.ocnum = $ocn ";
    // $conexion->Execute($insertar) or die(errorpg($conexion,$insertar));


    $consulta = "SELECT $ocnum_nuevo,cmp_dt.idprod,cmp_dt.idmedida,
		(cmp_dt.cantidad - COALESCE(cmp_comprado.total_comprado, 0)) AS cantidad_faltante,
		(cmp_dt.cantidad - COALESCE(cmp_comprado.total_comprado, 0)) AS cantidad_faltante,
		cmp_dt.precio_compra,cmp_dt.descripcion,cmp_dt.precio_compra_total,cmp_dt.descuento
				FROM compras_ordenes_detalles AS cmp_dt
				INNER JOIN compras_ordenes AS cmp ON cmp.ocnum = cmp_dt.ocnum
				INNER JOIN insumos_lista ON insumos_lista.idinsumo = cmp_dt.idprod
				LEFT JOIN (
					SELECT cmp_det.idprod, SUM(cmp_det.cantidad) AS total_comprado
					FROM compras_ordenes_detalles AS cmp_det
					INNER JOIN compras_ordenes AS cmp ON cmp.ocnum = cmp_det.ocnum and cmp.estado !=6
					AND cmp.ocnum_ref = $ocn 
					GROUP BY cmp_det.idprod
					) AS cmp_comprado ON cmp_comprado.idprod = cmp_dt.idprod
				WHERE cmp_dt.ocnum = $ocn ";

    // este select busca en compras
    $rs_detalles = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    while (!$rs_detalles->EOF) {
        $cantidad_faltante = $rs_detalles->fields['cantidad_faltante'];
        $precio_compra = $rs_detalles->fields['precio_compra'];
        $descripcion = $rs_detalles->fields['descripcion'];
        $idprod = $rs_detalles->fields['idprod'];
        $precio_compra_total = $rs_detalles->fields['precio_compra_total'];
        $idmedida = $rs_detalles->fields['idmedida'];
        $descuento = $rs_detalles->fields['descuento'];
        if ($cantidad_faltante > 0) {
            $insertar = "Insert into compras_ordenes_detalles 
						(ocnum,idprod,cantidad,cant_transito,precio_compra,descripcion,precio_compra_total,idmedida,descuento)values
						($ocnum_nuevo,$idprod,$cantidad_faltante,$cantidad_faltante,$precio_compra,'$descripcion',$precio_compra_total,$idmedida,$descuento)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        }
        $rs_detalles->MoveNext();
    }

    //////////////////////////////////////////

    $res = [
        "success" => true,
        "ocnum" => $ocnum_nuevo
    ];
} else {
    if ($oc_pendiente > 0) {
        $res = [
            "success" => false,
            "ocnum" => $oc_pendiente,
            "error" => $res['error'],
        ];

    } else {
        $res = [
            "success" => false,
            "error" => $res['error'],
        ];

    }

}
echo json_encode($res);
