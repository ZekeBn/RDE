<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "55";
$dirsup = "S";

require_once("../includes/rsusuario.php");

$idpo = intval($_GET['idpo']);


$iddeposito = $idpo;
set_time_limit(0);


//Lista de depositos
$buscar = "Select * from gest_depositos where iddeposito=$idpo and idempresa = $idempresa";
$rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$sucursal_deposito = intval($rsf->fields['idsucursal']);
$tiposala = intval($rsf->fields['tiposala']);


if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-d");
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}

$hab_invent_endeposito = intval($rsco->fields['hab_invent_endeposito']);
if ($hab_invent_endeposito == 1) {
    $whereadd = "
	and insumos_lista.hab_invent = 1
	";
}

if ($_GET['viendo'] == 's') {
    $whereadd .= "
	and (
		select disponible 
		from gest_depositos_stock_gral
		where
		idproducto = insumos_lista.idinsumo
		and iddeposito = $iddeposito
		and idempresa = $idempresa
	) > 0
	";
}
if ($_GET['hab_venta'] == 's') {
    $whereadd .= "
	and 
	(
	select activo_suc 
	from productos_sucursales 
	where 
	idproducto = insumos_lista.idproducto 
	and idsucursal = $sucursal_deposito
	
	) = 1
	";
}

if ($_GET['idproveedor'] > 0) {
    $idproveedor = intval($_GET['idproveedor']);
    $whereadd .= " and insumos_lista.idproveedor = $idproveedor ";
}

$whereaddconmovimiento = " and (select sum(cantidad) from stock_movimientos 
inner join gest_depositos_ajustes_stock 
on gest_depositos_ajustes_stock.idajuste = stock_movimientos.codrefer  
where stock_movimientos.idinsumo = insumos_lista.idinsumo 
and stock_movimientos.iddeposito =$iddeposito 
and stock_movimientos.tipomov=9 
and (gest_depositos_ajustes_stock.idmotivo =4 
or gest_depositos_ajustes_stock.idmotivo =5
or gest_depositos_ajustes_stock.idmotivo =6)
and stock_movimientos.fecha_comprobante >='$desde' 
and stock_movimientos.fecha_comprobante<= '$hasta')<>0";




//if($idsucursalinv > 0){
$consulta = "select insumos_lista.*, 
		(
		select grupo_insumos.nombre from grupo_insumos 
		where 
		grupo_insumos.idgrupoinsu = insumos_lista.idgrupoinsu 
		and grupo_insumos.idempresa = $idempresa
		) as nombre,
		 medidas.nombre as unidadmedida,
		costo as ultimocosto,
		(
		select disponible 
		from gest_depositos_stock_gral
		where
		idproducto = insumos_lista.idinsumo
		and iddeposito =$iddeposito 
		and idempresa = $idempresa 
		) as stock_teorico,
		(
		SELECT precio 
		FROM productos_sucursales 
		inner join gest_depositos on gest_depositos.idsucursal = productos_sucursales.idsucursal
		where  
		productos_sucursales.idproducto = insumos_lista.idproducto
		and productos_sucursales.idempresa =$idempresa 
		and gest_depositos.idempresa = $idempresa 
		limit 1
		) as precio_venta,
		CASE WHEN 
			insumos_lista.idproducto > 0
		THEN
			(
				select sum(subcosto)  
				from 
				( select recetas_detalles.idprod, 
					(
						select isl.costo 
						from insumos_lista isl 
						inner join ingredientes on ingredientes.idinsumo = isl.idinsumo 
						where 
						ingredientes.idingrediente = recetas_detalles.ingrediente
					)*cantidad as subcosto 
					from recetas_detalles 
				) as tt
				where
				tt.idprod=insumos_lista.idproducto
			) 
		ELSE
			insumos_lista.costo
		END as costo_receta,
		(select barcode from productos where idprod_serial = insumos_lista.idproducto) as codbar,
		(select sum(cantidad) from stock_movimientos 
		inner join gest_depositos_ajustes_stock 
		on gest_depositos_ajustes_stock.idajuste = stock_movimientos.codrefer  
		where stock_movimientos.idinsumo = insumos_lista.idinsumo 
		and stock_movimientos.iddeposito =$iddeposito 
		and stock_movimientos.tipomov=9 
		and (gest_depositos_ajustes_stock.idmotivo =4 
		or gest_depositos_ajustes_stock.idmotivo =5
		or gest_depositos_ajustes_stock.idmotivo =6)
		and stock_movimientos.fecha_comprobante >='$desde' 
		and stock_movimientos.fecha_comprobante<= '$hasta') as perdida
		from insumos_lista
		inner join medidas on medidas.id_medida = insumos_lista.idmedida
		inner join gest_depositos_stock_gral on gest_depositos_stock_gral.idproducto = insumos_lista.idinsumo
		where mueve_stock = 'S' 
		and insumos_lista.idempresa = $idempresa 
		and insumos_lista.estado = 'A' 
		and gest_depositos_stock_gral.iddeposito =$iddeposito 
		and gest_depositos_stock_gral.idempresa = $idempresa 
		and gest_depositos_stock_gral.disponible<>0
		$whereadd
		$whereaddconmovimiento
		order by 	
		descripcion asc
		";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



//}

if (intval($rs->fields['idinsumo']) > 0) {
    $invsel = "S";
}



require_once '../../clases/PHPExcel.php';

// Crea un nuevo objeto PHPExcel

$objPHPExcel = new PHPExcel();
// Establecer propiedades
$objPHPExcel->getProperties()
->setCreator("E-Karu")
->setLastModifiedBy("WEB")
->setTitle("Planilla de Stock")
->setSubject("Planilla de Stock")
->setDescription("Planilla de Stock Entradas y Salidas de Articulos")
->setKeywords("Excel Office 2007 openxml php")
->setCategory("Reportes");

$objPHPExcel->setActiveSheetIndex(0)


//Cabeceras
->setCellValue('A1', 'DEPOSITO :')
->setCellValue('B1', antixss($rsf->fields['descripcion']).' - SALIDAS ')
->setCellValue('C1', 'FECHA DESDE :')
->setCellValue('D1', $desde)
->setCellValue('E1', 'FECHA HASTA :')
->setCellValue('F1', $hasta)
->setCellValue('A2', 'Codigo Articulo')
->setCellValue('B2', 'Articulo')
->setCellValue('C2', 'Ult. Costo Compra')
->setCellValue('D2', 'Perdidas')
->setCellValue('E2', 'PC Valorizado')

;


$objPHPExcel->getActiveSheet()->getStyle("A2:E2")->getFont()->setSize(12)
->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle("A1:F1")->getFont()->setSize(12)
->setBold(true);
//Auto size de columnas
$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
/**************   INICIA EL WHILE ***********************/


$pcount = 0;
$i = 3;
$pcvalorizado = 0;
$totsalida = 0;
$totsalidaacum = 0;
while (!$rs->EOF) {

    $transalida = $rs->fields['perdida'];
    $valorizado_pc = $transalida * $ultimo_costo;

    $valorizado_pc_acum += $valorizado_pc;
    // buscar datos en sus respectivas tablas y completar las variables para ese insumo
    $totsalidaacum += $totsalida;

    /// CREA TABLA
    $objPHPExcel->setActiveSheetIndex(0)

    //idcategoria categoria  idsubcategoria subcategoria proveedor idproveedor
    ->setCellValue('A'.$i, $rs->fields['idinsumo'])
    ->setCellValue('B'.$i, $rs->fields['descripcion'])
    ->setCellValue('C'.$i, $rs->fields['costo_receta'])
    ->setCellValue('D'.$i, $transalida)
    ->setCellValue('E'.$i, $pcvalorizado)

    // (Precio de venta - Costo total) / Costo total
    ;


    /**************   FIN DEL WHILE ***********************/


    $grupoant = $grupo;
    $i++;

    $rs->MoveNext();
}



// Renombrar Hoja
$current = date("YmdHis");
$objPHPExcel->getActiveSheet()->setTitle('Planilla de Stock');

// Establecer la hoja activa, para que cuando se abra el documento se muestre primero.

$objPHPExcel->setActiveSheetIndex(0);
$ile = 'pla_perdidas_'.$current.'.xlsx';

// Se modifican los encabezados del HTTP para indicar que se envia un archivo de Excel.

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

header('Content-Disposition: attachment;filename='.$ile);

header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

$objWriter->save('php://output');
exit;







/*

CREATE ALGORITHM=UNDEFINED DEFINER=`innovasysadmin`@`localhost` SQL SECURITY DEFINER
VIEW `vta_articulos2`  AS

select
insumos_lista.idinsumo, insumos_lista.idproducto, insumos_lista.descripcion, insumos_lista.idgrupoinsu,
insumos_lista.idcategoria, insumos_lista.idsubcate, insumos_lista.idmarcaprod, insumos_lista.idproveedor, insumos_lista.hab_compra, insumos_lista.hab_invent, insumos_lista.idtipoiva, insumos_lista.tipoiva, insumos_lista.costo,
insumos_lista.estado,insumos_lista.respeta_precio_sugerido,

grupo_insumos.nombre as grupo_stock, medidas.nombre as unidadmedida,
    insumos_lista.costo as ultimocosto,
    (
    select disponible
    from gest_depositos_stock_gral
    where
    idproducto = insumos_lista.idinsumo
    and iddeposito = 1
    ) as stock_teorico,
    (
    SELECT precio
    FROM productos_sucursales
    inner join gest_depositos on gest_depositos.idsucursal = productos_sucursales.idsucursal
    where
    productos_sucursales.idproducto = insumos_lista.idproducto
    limit 1
    ) as precio_venta,
    CASE WHEN
        insumos_lista.idproducto > 0
    THEN
        (
            select sum(subcosto)
            from
            ( select recetas_detalles.idprod,
                (
                    select isl.costo
                    from insumos_lista isl
                    inner join ingredientes on ingredientes.idinsumo = isl.idinsumo
                    where
                    ingredientes.idingrediente = recetas_detalles.ingrediente
                )*cantidad as subcosto
                from recetas_detalles
            ) as tt
            where
            tt.idprod=insumos_lista.idproducto
        )
    ELSE
        insumos_lista.costo
    END as costo_receta,
    (select barcode from productos where idprod_serial = insumos_lista.idproducto) as codbar,

    (select nombre from categorias where insumos_lista.idcategoria = categorias.id_categoria) as categoria,
    (select descripcion from sub_categorias where insumos_lista.idsubcate =  sub_categorias.idsubcate) as subcategoria,
    (select nombre from proveedores where insumos_lista.idproveedor = proveedores.idproveedor) as proveedor,
    insumos_lista.idmarcaprod as idmarca,
    (select marca.marca from marca where idmarca = insumos_lista.idmarcaprod) as marca,


    (select stock_minimo.stock_minimo from stock_minimo where stock_minimo.idinsumo = insumos_lista.idinsumo and iddeposito = 1) as stock_minimo,
    (select stock_minimo.stock_ideal from stock_minimo where stock_minimo.idinsumo = insumos_lista.idinsumo and iddeposito = 1) as stock_ideal,
    (select ubicaciones.descripcion from stock_minimo inner join ubicaciones on ubicaciones.idubicacion = stock_minimo.idubicacion where stock_minimo.idinsumo = insumos_lista.idinsumo and iddeposito = 1) as ubicacion,
    (select stock_minimo.idbandeja from stock_minimo where stock_minimo.idinsumo = insumos_lista.idinsumo and iddeposito = 1) as idbandeja,
    (select stock_minimo.idorden from stock_minimo where stock_minimo.idinsumo = insumos_lista.idinsumo and iddeposito = 1) as idorden,
    (select stock_minimo.idpasillo from stock_minimo where stock_minimo.idinsumo = insumos_lista.idinsumo and iddeposito = 1) as idpasillo,

    CASE WHEN insumos_lista.hab_compra = 1 THEN 'SI' ELSE 'NO' END as habilita_compra,
    CASE WHEN insumos_lista.hab_invent = 1 THEN 'SI' ELSE 'NO' END as habilita_inventario,

    CASE WHEN
        insumos_lista.acepta_devolucion = 'S'
    THEN
        'SI'
    ELSE
        CASE WHEN
            insumos_lista.acepta_devolucion = 'N'
        THEN
            'NO'
        ELSE
            ''
        END

     END as acepta_devolucion



    from insumos_lista
    inner join grupo_insumos on grupo_insumos.idgrupoinsu = insumos_lista.idgrupoinsu
    inner join medidas on medidas.id_medida = insumos_lista.idmedida
    where
    mueve_stock = 'S'
    and insumos_lista.estado = 'A'

    order by grupo_insumos.nombre asc, descripcion asc




WITH CASCADED CHECK OPTION ;

CREATE ALGORITHM=UNDEFINED DEFINER=`innovasysadmin`@`localhost` SQL SECURITY DEFINER VIEW `vta_articulos`  AS  select `insumos_lista`.`idinsumo` AS `idinsumo`,`insumos_lista`.`idproducto` AS `idproducto`,`insumos_lista`.`descripcion` AS `descripcion`,`insumos_lista`.`idgrupoinsu` AS `idgrupoinsu`,`insumos_lista`.`idcategoria` AS `idcategoria`,`insumos_lista`.`idsubcate` AS `idsubcate`,`insumos_lista`.`idmarcaprod` AS `idmarcaprod`,`insumos_lista`.`idproveedor` AS `idproveedor`,`insumos_lista`.`hab_compra` AS `hab_compra`,`insumos_lista`.`hab_invent` AS `hab_invent`,`insumos_lista`.`idtipoiva` AS `idtipoiva`,`insumos_lista`.`tipoiva` AS `tipoiva`,`insumos_lista`.`costo` AS `costo`,`insumos_lista`.`estado` AS `estado`,`insumos_lista`.`respeta_precio_sugerido` AS `respeta_precio_sugerido`,`grupo_insumos`.`nombre` AS `grupo_stock`,`medidas`.`nombre` AS `unidadmedida`,`insumos_lista`.`costo` AS `ultimocosto`,(select `gest_depositos_stock_gral`.`disponible` from `gest_depositos_stock_gral` where `gest_depositos_stock_gral`.`idproducto` = `insumos_lista`.`idinsumo` and `gest_depositos_stock_gral`.`iddeposito` = 1) AS `stock_teorico`,(select `productos_sucursales`.`precio` from (`productos_sucursales` join `gest_depositos` on(`gest_depositos`.`idsucursal` = `productos_sucursales`.`idsucursal`)) where `productos_sucursales`.`idproducto` = `insumos_lista`.`idproducto` limit 1) AS `precio_venta`,case when `insumos_lista`.`idproducto` > 0 then (select sum(`tt`.`subcosto`) from (select `recetas_detalles`.`idprod` AS `idprod`,(select `isl`.`costo` from (`insumos_lista` `isl` join `ingredientes` on(`ingredientes`.`idinsumo` = `isl`.`idinsumo`)) where `ingredientes`.`idingrediente` = `recetas_detalles`.`ingrediente`) * `recetas_detalles`.`cantidad` AS `subcosto` from `recetas_detalles`) `tt` where `tt`.`idprod` = `insumos_lista`.`idproducto`) else `insumos_lista`.`costo` end AS `costo_receta`,(select `productos`.`barcode` from `productos` where `productos`.`idprod_serial` = `insumos_lista`.`idproducto`) AS `codbar`,(select `categorias`.`nombre` from `categorias` where `insumos_lista`.`idcategoria` = `categorias`.`id_categoria`) AS `categoria`,(select `sub_categorias`.`descripcion` from `sub_categorias` where `insumos_lista`.`idsubcate` = `sub_categorias`.`idsubcate`) AS `subcategoria`,(select `proveedores`.`nombre` from `proveedores` where `insumos_lista`.`idproveedor` = `proveedores`.`idproveedor`) AS `proveedor`,`insumos_lista`.`idmarcaprod` AS `idmarca`,(select `marca`.`marca` from `marca` where `marca`.`idmarca` = `insumos_lista`.`idmarcaprod`) AS `marca`,(select `stock_minimo`.`stock_minimo` from `stock_minimo` where `stock_minimo`.`idinsumo` = `insumos_lista`.`idinsumo` and `stock_minimo`.`iddeposito` = 1) AS `stock_minimo`,(select `stock_minimo`.`stock_ideal` from `stock_minimo` where `stock_minimo`.`idinsumo` = `insumos_lista`.`idinsumo` and `stock_minimo`.`iddeposito` = 1) AS `stock_ideal`,(select `ubicaciones`.`descripcion` from (`stock_minimo` join `ubicaciones` on(`ubicaciones`.`idubicacion` = `stock_minimo`.`idubicacion`)) where `stock_minimo`.`idinsumo` = `insumos_lista`.`idinsumo` and `stock_minimo`.`iddeposito` = 1) AS `ubicacion`,(select `stock_minimo`.`idbandeja` from `stock_minimo` where `stock_minimo`.`idinsumo` = `insumos_lista`.`idinsumo` and `stock_minimo`.`iddeposito` = 1) AS `idbandeja`,(select `stock_minimo`.`idorden` from `stock_minimo` where `stock_minimo`.`idinsumo` = `insumos_lista`.`idinsumo` and `stock_minimo`.`iddeposito` = 1) AS `idorden`,(select `stock_minimo`.`idpasillo` from `stock_minimo` where `stock_minimo`.`idinsumo` = `insumos_lista`.`idinsumo` and `stock_minimo`.`iddeposito` = 1) AS `idpasillo`,case when `insumos_lista`.`hab_compra` = 1 then 'SI' else 'NO' end AS `habilita_compra`,case when `insumos_lista`.`hab_invent` = 1 then 'SI' else 'NO' end AS `habilita_inventario`,case when `insumos_lista`.`acepta_devolucion` = 'S' then 'SI' else case when `insumos_lista`.`acepta_devolucion` = 'N' then 'NO' else '' end end AS `acepta_devolucion` from ((`insumos_lista` join `grupo_insumos` on(`grupo_insumos`.`idgrupoinsu` = `insumos_lista`.`idgrupoinsu`)) join `medidas` on(`medidas`.`id_medida` = `insumos_lista`.`idmedida`)) where `insumos_lista`.`mueve_stock` = 'S' and `insumos_lista`.`estado` = 'A' order by `grupo_insumos`.`nombre`,`insumos_lista`.`descripcion` WITH CASCADED CHECK OPTION ;








*/
