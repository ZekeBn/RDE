<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "55";
$dirsup = 'S';

require_once("../includes/rsusuario.php");

$idpo = intval($_GET['idpo']);

if ($idpo == 0) {
    header("Location:gest_adm_depositos.php");
    exit;
}
$iddeposito = $idpo;

set_time_limit(0);
$buscar = "Select * from gest_depositos where iddeposito=$idpo and idempresa = $idempresa";
$rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$sucursal_deposito = intval($rsf->fields['idsucursal']);
$tiposala = intval($rsf->fields['tiposala']);

if ($idgrupoinsu > 0) {
    $whereadd .= "
		and insumos_lista.idgrupoinsu = $idgrupoinsu
		";
}

if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-d");
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}


if ($_GET['viendo'] == 's') {
    $whereadd .= "
	and (select cantidad from stock_movimientos 
	where idinsumo = insumos_lista.idinsumo 
	and iddeposito=$iddeposito 
	and fecha_comprobante='$desde' and tipomov = 5 
	order by fechahora asc limit 1 ) > 0
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



$consulta = "select case when insumos_lista.idproducto>0 then insumos_lista.idproducto 
else insumos_lista.idinsumo end as idinsumo,insumos_lista.descripcion as producto,
(select descripcion from gest_depositos where iddeposito=$iddeposito) as nombredeposito,
 medidas.nombre as unidadmedida,(select barcode from productos where idprod = insumos_lista.idproducto) as codigo_barras, 
 (select cantidad_contada from inventario_detalles inner join inventario on inventario.idinventario = inventario_detalles.idinventario where idinsumo = insumos_lista.idinsumo and inventario.iddeposito=$iddeposito and fecha_final='$desde' ) as stock,insumos_lista.idgrupoinsu,grupo_insumos.nombre as grupo,CASE WHEN 
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
END as costo_receta,insumos_lista.idempresa,
(SELECT cn_plancuentas_detalles.descripcion from cn_plancuentas_detalles where idserieun = insumos_lista.idplancuentadet) as nombre_cuenta,
 (SELECT cn_plancuentas_detalles.cuenta from cn_plancuentas_detalles where idserieun = insumos_lista.idplancuentadet) as cuenta
from insumos_lista inner join medidas on medidas.id_medida = insumos_lista.idmedida
left join grupo_insumos on grupo_insumos.idgrupoinsu = insumos_lista.idgrupoinsu
where insumos_lista.mueve_stock = 'S'
and insumos_lista.idempresa = $idempresa 
and insumos_lista.estado = 'A' 
and insumos_lista.idgrupoinsu = 1
and insumos_lista.hab_invent =1
and (select cantidad_contada from inventario_detalles inner join inventario on inventario.idinventario = inventario_detalles.idinventario where idinsumo = insumos_lista.idinsumo and inventario.iddeposito=$iddeposito and fecha_final='$desde') >0
$whereadd
order by 	
descripcion asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




require_once '../../clases/PHPExcel.php';

// Crea un nuevo objeto PHPExcel

$objPHPExcel = new PHPExcel();
// Establecer propiedades
$objPHPExcel->getProperties()
->setCreator("E-Karu")
->setLastModifiedBy("WEB")
->setTitle("Planilla de Stock")
->setSubject("Planilla de Stock")
->setDescription("Planilla de Stock")
->setKeywords("Excel Office 2007 openxml php")
->setCategory("Reportes");

$objPHPExcel->setActiveSheetIndex(0)


//Cabeceras
->setCellValue('A1', 'DEPOSITO :')
->setCellValue('B1', antixss($rsf->fields['descripcion']))
->setCellValue('C1', 'F.DESDE : '.$desde.'  F.HASTA : '.$hasta)
->setCellValue('A2', 'Codigo Articulo')
->setCellValue('B2', 'Codigo Barras')
->setCellValue('C2', 'Articulo')
->setCellValue('D2', 'U. Medida')
->setCellValue('E2', 'Cantidad')
->setCellValue('F2', 'Ult Costo Compra')
->setCellValue('G2', 'Total')
->setCellValue('H2', 'Nombre Cuenta')
->setCellValue('I2', 'Cuenta Contable')
;


$objPHPExcel->getActiveSheet()->getStyle("A1:C1")->getFont()->setSize(12)
->setBold(true);

$objPHPExcel->getActiveSheet()->getStyle("A2:I2")->getFont()->setSize(12)
->setBold(true);


//Auto size de columnas
$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);


/**************   INICIA EL WHILE ***********************/


$pcount = 0;
$i = 3;
while (!$rs->EOF) {

    // grupo de insumos
    $grupo = $rs->fields['nombre'];

    // buscar datos en sus respectivas tablas y completar las variables para ese insumo

    $idinsumo = $rs->fields['idinsumo'];
    $utilidadbruta = $rs->fields['precio_venta'] - $rs->fields['costo_receta'];
    if ($rs->fields['costo_receta'] > 0) {
        $recargo = ((floatval($rs->fields['precio_venta']) - floatval($rs->fields['costo_receta'])) / floatval($rs->fields['costo_receta'])) * 100;
        $margen = (1 - (floatval($rs->fields['costo_receta']) / floatval($rs->fields['precio_venta']))) * 100;
    } else {
        $recargo = 0;
        $margen = 100;
    }
    $recargo = round($recargo, 2);
    $margen = round($margen, 2);
    /// CREA TABLA
    $objPHPExcel->setActiveSheetIndex(0)


    //idcategoria categoria  idsubcategoria subcategoria proveedor idproveedor
    ->setCellValue('A'.$i, $rs->fields['idinsumo'])
    ->setCellValue('B'.$i, $rs->fields['codbar'])
    ->setCellValue('C'.$i, $rs->fields['producto'])
    ->setCellValue('D'.$i, capitalizar(antixss($rs->fields['unidadmedida'])))
    ->setCellValue('E'.$i, $rs->fields['stock'])
    ->setCellValue('F'.$i, $rs->fields['costo_receta'])
    ->setCellValue('G'.$i, $rs->fields['stock'] * $rs->fields['costo_receta'])
    ->setCellValue('H'.$i, $rs->fields['nombre_cuenta'])
    ->setCellValue('I'.$i, $rs->fields['cuenta'])
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
$ile = 'pla_stock_'.$current.'.xlsx';

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
