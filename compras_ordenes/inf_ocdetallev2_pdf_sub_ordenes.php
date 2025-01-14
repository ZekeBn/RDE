<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "12";
$submodulo = "53";
require_once("../includes/rsusuario.php");

//buscando moneda nacional
$consulta = "SELECT idtipo,descripcion FROM `tipo_moneda` WHERE nacional='S' ";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$id_moneda_nacional = $rs_guarani->fields["idtipo"];
$nombre_moneda_nacional = $rs_guarani->fields["descripcion"];
$idcoc = intval($_GET['idoc']);

if ($idcoc > 0) {

    $buscar = "Select * from empresas";
    $rse = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $empresachar = trim($rse->fields['empresa']);

    $buscar = "Select compras_ordenes.fecha,compras_ordenes.ocnum,usuario, compras_ordenes.idtipo_moneda, compras_ordenes.tipocompra,nombre,fecha_entrega,compras_ordenes.estado,cant_dias,inicia_pago,forma_pago,proveedores.diasvence,
	tipo_moneda.descripcion as moneda_nombre, cotizaciones.cotizacion as cotizacion_venta, cotizaciones.compra as cotizacion_compra
	from compras_ordenes 
	inner join proveedores on proveedores.idproveedor=compras_ordenes.idproveedor 
	inner join usuarios on usuarios.idusu=compras_ordenes.generado_por
	LEFT JOIN tipo_moneda on tipo_moneda.idtipo = compras_ordenes.idtipo_moneda
	LEFT JOIN cotizaciones on cotizaciones.idcot = compras_ordenes.idcot
	where ocnum=$idcoc";
    $rsh = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $idtipo_moneda = $rsh->fields['idtipo_moneda'];
    $moneda_nombre = $rsh->fields['moneda_nombre'];
    $cotizacion_venta = $rsh->fields['cotizacion_venta'];
    $cotizacion_compra = $rsh->fields['cotizacion_compra'];
    $cotizacion_mensaje = " Tipo Cambio: $cotizacion_venta  ";

    if ($id_moneda_nacional != $idtipo_moneda) {
        $cotizacion_mensaje = " Tipo Cambio: $cotizacion_venta ";
    }
    if (!isset($moneda_nombre) || $moneda_nombre == null || $moneda_nombre == "") {
        $moneda_nombre = $nombre_moneda_nacional;
    }

    $buscar = "
	select *, 
	(
	select barcode 
	from productos 
	inner join insumos_lista on insumos_lista.idproducto = productos.idprod_serial 
	where 
	insumos_lista.idinsumo =  compras_ordenes_detalles.idprod
	) as codbar
	from compras_ordenes_detalles 
	where 
	ocnum=$idcoc 
	order by descripcion asc";
    $rshd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
} else {
    $error = 'Debe indicar n&uacute;mero de orden.';
}

$img = "../gfx/empresas/emp_" . $idempresa . ".png";

if (!file_exists($img)) {
    $img = "../gfx/empresas/emp_0.png";
}
if (!isset($moneda_nombre) || $moneda_nombre == null || $moneda_nombre == "") {
    $moneda_nombre = "GS";
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Orden de Compra</title>
</head>

<body>
    <div align="center">
        <?php if ($error != '') { ?>
            <h1><strong><?php echo $error;
                        exit; ?></strong></h1>
        <?php } ?>
        <div style="width:600px; border:1px solid #000000; height:125px;">
            <table width="600">
                <tr>
                    <td> <img src="<?php echo $img; ?>" height="120" alt="" /></td>
                    <td>
                        <h1><?php echo $empresachar ?><br />Orden Compra N&deg; <?php echo $idcoc ?></h1>
                    </td>
                </tr>
            </table>
        </div>

        <table width="700" border="0">
            <tbody>
                <tr>
                    <td height="29" colspan="4" align="center" bgcolor="#F0EBEB">Fecha Orden: <?php echo date("d/m/Y", strtotime($rsh->fields['fecha'])) . '  |  Operador: ' . $rsh->fields['usuario']; ?></td>
                </tr>
                <tr>
                    <td align="right" bgcolor="#F0EBEB"><strong>Proveedor</strong></td>
                    <td colspan="3" align="left"><?php echo  $rsh->fields['nombre'] ?></td>
                </tr>
                <tr>
                    <td width="25%" height="36" align="right" bgcolor="#F0EBEB"><strong>Fecha Entrega Esperada</strong></td>
                    <td width="25%" style="color:#FC0004; font-weight:bold"><?php echo date("d/m/Y", strtotime($rsh->fields['fecha_entrega'])); ?></td>
                    <td width="25%" bgcolor="#F0EBEB" style="font-weight:bold"><strong>Condicion</strong></td>
                    <td width="25%" style="color:#FC0004; font-weight:bold"><?php if (intval($rsh->fields['tipocompra']) == 2) {
                                                                                echo "Credito";
                                                                            } else {
                                                                                echo "Contado";
                                                                            } ?></td>
                </tr>
                <tr>
                    <td align="right" bgcolor="#F0EBEB"><strong>Fecha Pago (estimada)</strong></td>
                    <td align="left"><?php if ($rsh->fields['inicia_pago'] != '') {
                                            echo date("d/m/Y", strtotime($rsh->fields['inicia_pago']));
                                        } ?></td>
                    <td align="right" bgcolor="#F0EBEB"><strong>Cantidad Dias</strong></td>
                    <td align="center"><?php if ($rsh->fields['cant_dias'] > 0) {
                                            echo ($rsh->fields['cant_dias']);
                                        } ?></td>
                </tr>
            </tbody>
        </table>

        <strong>Art&iacute;culos</strong>
        <hr /><br />

        <table width="799" border="1" style="border-collapse:collapse;">
            <tr>
                <td width="100" height="29" align="center" bgcolor="#B4B4B4"><strong><em>C&oacute;digo</em></strong></td>
                <td width="183" align="center" bgcolor="#B4B4B4"><strong>Cod Barra</strong></td>
                <td width="183" align="center" bgcolor="#B4B4B4"><strong><em>Producto</em></strong></td>
                <td width="79" align="center" bgcolor="#B4B4B4"><strong><em>Cantidad</em></strong></td>
                <td width="111" align="center" bgcolor="#B4B4B4"><strong><em>Precio Compra</em></strong></td>

            </tr>

            <?php
            $to = 0;
            while (!$rshd->EOF) {
                $subt = $rshd->fields['cantidad'] * $rshd->fields['precio_compra'];
                $to = $to + $subt;
            ?>
                <tr>
                    <td height="29" align="center"><?php echo $rshd->fields['idprod'] ?></td>
                    <td align="center"><?php echo $rshd->fields['codbar'] ?></td>
                    <td align="left"><?php echo $rshd->fields['descripcion'] ?></td>
                    <td align="right"><?php echo formatomoneda($rshd->fields['cantidad'], 4, 'N') ?></td>
                    <td align="right"><?php echo formatomoneda($rshd->fields['precio_compra'], 4, 'N') ?></td>
                    <td align="right"><?php echo formatomoneda($subt, 4, 'N'); ?></td>
                </tr>

            <?php $rshd->MoveNext();
            }

            $cotizacion_mensaje2 = "";

            if ($id_moneda_nacional != $idtipo_moneda) {
                $cotizacion_mensaje2 = " $nombre_moneda_nacional: " . formatomoneda($cotizacion_compra * $to, 4, 'N');
            }
            ?>
        </table>
        <br />

        <?php

        $ocn = $idcoc;

        //Traemos los productos seleccionados para la compra
        $buscar = "
        select compras_ordenes.ocnum, embarque.estado_embarque, compras_ordenes.fecha, puertos.descripcion as puerto , embarque.idembarque, embarque.idcompra , compras_ordenes.estado_orden
        from compras_ordenes 
        LEFT JOIN embarque on embarque.ocnum = compras_ordenes.ocnum
        LEFT JOIN puertos on puertos.idpuerto = embarque.idpuerto
        where 
        compras_ordenes.ocnum_ref =$ocn
        and compras_ordenes.estado = 2";
        $rscu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        $estado_orden = intval($rs->fields['estado_orden']);
        $subocnum = $rscu->fields['ocnum'];
        $to = 0;
        $contador = 0;

        while (!$rscu->EOF) {

            $subt = $rscu->fields['cantidad'] * $rscu->fields['precio_compra'];
            $to = $to + $subt;
            $ocn = $subocnum;
            $buscar = "
						Select *, cotizaciones.cotizacion,compras_ordenes.finalizado_el as fin,
						( Select nombre from medidas where medidas.id_medida = compras_ordenes_detalles.idmedida ) as medida,
						(select insumos_lista.descripcion from insumos_lista where idinsumo = compras_ordenes_detalles.idprod )  as articulo,
						(
							select productos.barcode
							from insumos_lista
							inner join productos on productos.idprod_serial = insumos_lista.idproducto
							where
							idinsumo = compras_ordenes_detalles.idprod
						)  as barcode
						from  compras_ordenes_detalles
						inner join compras_ordenes on compras_ordenes.ocnum = compras_ordenes_detalles.ocnum
						LEFT JOIN cotizaciones on cotizaciones.idcot = compras_ordenes.idcot
						where
						compras_ordenes_detalles.ocnum=$ocn
						and compras_ordenes.estado = 2
						order by compras_ordenes_detalles.descripcion asc";
            $rscu2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $tprod = $rscu2->RecordCount();

            $cot_ref = floatval($rscu2->fields['cotizacion']);
            $costo_ref = floatval($rscu2->fields['costo_ref']);

            $buscar = "SELECT *,
						( select insumos_lista.descripcion from insumos_lista where idinsumo = compras_ordenes_detalles.idprod )  as articulo,
						(
							select productos.barcode
							from insumos_lista
							inner join productos on productos.idprod_serial = insumos_lista.idproducto
							where
							idinsumo = compras_ordenes_detalles.idprod
						)  as barcode
						from  compras_ordenes_detalles
						inner join compras_ordenes on compras_ordenes.ocnum = compras_ordenes_detalles.ocnum
						where
						compras_ordenes_detalles.ocnum=$ocn
						and compras_ordenes.estado = 1
						order by compras_ordenes_detalles.ocseria desc
						limit 1";
            $rsultag = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

            $cotizacion = "SELECT cotizaciones.cotizacion, costo_ref, tipo_moneda.descripcion as nombre_moneda, tipo_moneda.banderita
						from compras_ordenes
						LEFT JOIN cotizaciones on cotizaciones.idcot = compras_ordenes.idcot
						INNER JOIN tipo_moneda on tipo_moneda.idtipo = cotizaciones.tipo_moneda
						where ocnum = $ocn";
            $rscotizacion = $conexion->Execute($cotizacion) or die(errorpg($conexion, $cotizacion));

            $cot_ref = floatval($rscotizacion->fields['cotizacion']);
            $costo_ref = floatval($rscotizacion->fields['costo_ref']);
            $banderita = antixss($rscotizacion->fields['banderita']);
            $nombre_moneda = antixss($rscotizacion->fields['nombre_moneda']);
            $tot = 0;
            $fin = $rscu2->fields['fin'];

            if ($contador == 0) { ?>

                <strong style="text-align:center;">Proformas</strong>
                <hr /><br />

            <?php $contador++;
            } ?>

            <table width="799" border="1" style="border-collapse:collapse;">
                <tr>
                    <td width="100" height="29" align="center" bgcolor="#B4B4B4"><strong><em>C&oacute;digo</em></strong></td>
                    <td width="183" align="center" bgcolor="#B4B4B4"><strong>Cod Barra</strong></td>
                    <td width="183" align="center" bgcolor="#B4B4B4"><strong><em>Producto</em></strong></td>
                    <td width="79" align="center" bgcolor="#B4B4B4"><strong><em>Cantidad</em></strong></td>
                    <td width="111" align="center" bgcolor="#B4B4B4"><strong><em>Precio Compra</em></strong></td>
                    <td width="103" align="center" bgcolor="#B4B4B4"><strong><em>Sub Total <?php echo $moneda_nombre; ?></em></strong></td>
                </tr>

                <?php

                while (!$rscu2->EOF) {

                    $subt = $rscu2->fields['precio_compra_total'];
                    $tot = $tot + $subt;
                ?>

                    <tr <?php if (intval($rscu2->fields['cant_transito']) != 0) { ?> class="transito" <?php } else { ?> class="completo" <?php } ?>>
                        <td align="center"><?php echo trim($rscu2->fields['idprod']) ?></td>
                        <td align="right"><?php echo ($rscu2->fields['barcode']); ?></td>
                        <!-- <td align="left"><?php //echo intval($rscu2->fields['cant_transito']) == 0 ? "Si":"No"
                                                ?></td> -->
                        <td align="left"><?php echo trim($rscu2->fields['articulo']) ?></td>
                        <td align="right"><?php echo formatomoneda($rscu2->fields['cantidad'], 4, 'N'); ?></td>
                        <td align="right"><?php echo formatomoneda($rscu2->fields['precio_compra'], 4, 'N'); ?></td>
                        <td align="right"><?php echo formatomoneda($rscu2->fields['precio_compra_total'], 4, 'N'); ?></td>
                    </tr>

            <?php
                    $rscu2->MoveNext();
                }

                $rscu->MoveNext();
            }

            $cotizacion_mensaje2 = "";

            if ($id_moneda_nacional != $idtipo_moneda) {
                $cotizacion_mensaje2 = " $nombre_moneda_nacional: " . formatomoneda($cotizacion_compra * $to, 4, 'N');
            }
            ?>
            </table>

            <?php

            $to = 0;
            $ocn = $_GET["idoc"];

            // este select busca en compras
            $consulta = "SELECT cmp_dt.idprod,cmp_dt.idmedida,
                            (cmp_dt.cantidad - COALESCE(cmp_comprado.total_comprado, 0)) AS cantidad_faltante,
                            (cmp_dt.cantidad - COALESCE(cmp_comprado.total_comprado, 0)) AS cantidad_faltante,
                            (
							select productos.barcode
							from insumos_lista
							inner join productos on productos.idprod_serial = insumos_lista.idproducto
							where
							idinsumo = cmp_dt.idprod
						    )  as barcode,
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
                            WHERE cmp_dt.ocnum = $ocn";
            $rs_detalles = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $contador = 0;

            while (!$rs_detalles->EOF) {

                $cantidad_faltante = $rs_detalles->fields['cantidad_faltante'];
                $precio_compra = $rs_detalles->fields['precio_compra'];
                $descripcion = $rs_detalles->fields['descripcion'];
                $idprod = $rs_detalles->fields['idprod'];
                $precio_compra_total = $cantidad_faltante * $precio_compra;
                $idmedida = $rs_detalles->fields['idmedida'];
                $descuento = $rs_detalles->fields['descuento'];
                $codbar = $rs_detalles->fields['barcode'];

                if ($cantidad_faltante > 0) {

                    if ($contador == 0) { ?>

                        <strong>Art&iacute;culos Faltantes</strong>
                        <hr /><br />

                        <table width="799" border="1" style="border-collapse:collapse;">
                            <tr>
                                <td width="100" height="29" align="center" bgcolor="#B4B4B4"><strong><em>C&oacute;digo</em></strong></td>
                                <td width="183" align="center" bgcolor="#B4B4B4"><strong>Cod Barra</strong></td>
                                <td width="183" align="center" bgcolor="#B4B4B4"><strong><em>Producto</em></strong></td>
                                <td width="79" align="center" bgcolor="#B4B4B4"><strong><em>Cantidad</em></strong></td>
                                <td width="111" align="center" bgcolor="#B4B4B4"><strong><em>Precio Compra</em></strong></td>
                                <td width="103" align="center" bgcolor="#B4B4B4"><strong><em>Sub Total <?php echo $moneda_nombre; ?></em></strong></td>
                            </tr>
                        <?php } ?>

                        <tr>
                            <td height="29" align="center"><?php echo $idprod ?></td>
                            <td align="center"><?php echo $codbar ?></td>
                            <td align="left"><?php echo $descripcion ?></td>
                            <td align="right"><?php echo formatomoneda($cantidad_faltante, 4, 'N') ?></td>
                            <td align="right"><?php echo formatomoneda($precio_compra, 4, 'N') ?></td>
                            <td align="right"><?php echo formatomoneda($precio_compra_total, 4, 'N'); ?></td>
                        </tr>

                <?php }

                $contador++;
                $rs_detalles->MoveNext();
            } ?>

                        </table>
                        <br />
    </div>
    <br />

    <div align="center">
        <div style="width:600px; height:260px;border:1px solid #000000;">
            <table width="600px;" height="240">
                <tr>
                    <td height="32" colspan="4" align="center"><strong>Total Compra <?php echo $moneda_nombre; ?>: <?php echo formatomoneda($to) . " " . $cotizacion_mensaje; ?></strong></td>
                </tr>
                <tr>
                    <td height="32" colspan="4" align="center"><strong><?php echo $cotizacion_mensaje2; ?></strong></td>
                </tr>
                <tr>
                    <td width="84" height="79"><strong>Encargado Compras</strong></td>
                    <td width="216">
                        <p>..................................................</p>
                    </td>
                    <td width="41"><strong>Firma </strong></td>
                    <td width="239">
                        <p>......................................................</p>
                    </td>
                </tr>
                <tr>
                    <td width="84" height="55"><strong>Administraci&oacute;n</strong></td>
                    <td width="216">..................................................</td>
                    <td width="41"><strong>Firma</strong></td>
                    <td width="239">........................................................</td>
                </tr>
                <tr>
                    <td height="61"><strong>Observaciones</strong></td>
                    <td colspan="3"><?php echo 'Impreso el ' . date("d/m/Y H:i:s"); ?></td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>