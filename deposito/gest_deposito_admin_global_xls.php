<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "55";
$dirsup = 'S';
require_once("../includes/rsusuario.php");






// actualiza categoria y subcategoria en insumos
$consulta = "
update insumos_lista 
set 
insumos_lista.idcategoria = (select productos.idcategoria from productos where idprod_serial = insumos_lista.idproducto) 
where 
idproducto is not null
and COALESCE(insumos_lista.idcategoria,0) <> (select productos.idcategoria from productos where idprod_serial = insumos_lista.idproducto);
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$consulta = "
update insumos_lista 
set 
insumos_lista.idsubcate = (select productos.idsubcate from productos where idprod_serial = insumos_lista.idproducto) 
where 
idproducto is not null
and COALESCE(insumos_lista.idsubcate,0) <> (select productos.idsubcate from productos where idprod_serial = insumos_lista.idproducto);
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$consulta = "
update insumos_lista 
set 
insumos_lista.idmarcaprod = (select productos.idmarca from productos where idprod_serial = insumos_lista.idproducto) 
where 
idproducto is not null
and COALESCE(insumos_lista.idmarcaprod,0) <> (select productos.idmarca from productos where idprod_serial = insumos_lista.idproducto);
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


$hab_invent_endeposito = intval($rsco->fields['hab_invent_endeposito']);
if ($hab_invent_endeposito == 1) {
    $whereadd = "
	and insumos_lista.hab_invent = 1
	";
}


//if($idsucursalinv > 0){
$consulta = "
		select insumos_lista.*, grupo_insumos.nombre as grupo_stock, medidas.nombre as unidadmedida,
		insumos_lista.costo as ultimocosto,
		(
		select sum(disponible) as total
		from gest_depositos_stock_gral
		where
		idproducto = insumos_lista.idinsumo
		) as stock_teorico,
		(
		SELECT precio 
		FROM productos_sucursales 
		inner join gest_depositos on gest_depositos.idsucursal = productos_sucursales.idsucursal
		where  
		productos_sucursales.idproducto = insumos_lista.idproducto
		and productos_sucursales.idempresa = $idempresa
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
		(select nombre from categorias where insumos_lista.idcategoria = categorias.id_categoria) as categoria,
		(select descripcion from sub_categorias where insumos_lista.idsubcate =  sub_categorias.idsubcate) as subcategoria,
		(select nombre from proveedores where insumos_lista.idproveedor = proveedores.idproveedor) as proveedor,
		insumos_lista.idmarcaprod as idmarca,
		(select marca.marca from marca where idmarca = insumos_lista.idmarcaprod) as marca,
		insumos_lista.tipoiva,
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
		 
		 END as acepta_devolucion,
		 idcentroprod as cod_centro_prod,
		 (select descripcion from produccion_centros where idcentroprod = insumos_lista.idcentroprod) as centro_prod,
		 (
		 select cuenta
		  from cn_plancuentas_detalles
		 inner join cn_articulos_vinculados on cn_articulos_vinculados.idsercuenta = cn_plancuentas_detalles.idserieun
		 WHERE
		 cn_articulos_vinculados.idinsumo = insumos_lista.idinsumo
		 limit 1
		 ) as cod_art_cont,
		 (
		 select descripcion
		  from cn_plancuentas_detalles
		 inner join cn_articulos_vinculados on cn_articulos_vinculados.idsercuenta = cn_plancuentas_detalles.idserieun
		 WHERE
		 cn_articulos_vinculados.idinsumo = insumos_lista.idinsumo
		 limit 1
		 ) as art_cont
		
		
		from insumos_lista
		inner join grupo_insumos on grupo_insumos.idgrupoinsu = insumos_lista.idgrupoinsu
		inner join medidas on medidas.id_medida = insumos_lista.idmedida
		where
		mueve_stock = 'S'
		and insumos_lista.estado = 'A'
		$whereadd	
		and insumos_lista.idempresa = $idempresa
		and grupo_insumos.idempresa = $idempresa
		order by grupo_insumos.nombre asc, descripcion asc
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
->setDescription("Planilla de Stock")
->setKeywords("Excel Office 2007 openxml php")
->setCategory("Reportes");

$objPHPExcel->setActiveSheetIndex(0)


//Cabeceras
->setCellValue('A1', 'Codigo Articulo')
->setCellValue('B1', 'Codigo Barras')
->setCellValue('C1', 'Articulo')
->setCellValue('D1', 'U. Medida')


->setCellValue('E1', 'Cod Grupo Stock')
->setCellValue('F1', 'Grupo Stock')
->setCellValue('G1', 'Cod Categoria')
->setCellValue('H1', 'Categoria')
->setCellValue('I1', 'Cod Subcategoria')
->setCellValue('J1', 'Subcategoria')
->setCellValue('K1', 'Cod Marca')
->setCellValue('L1', 'Marca')
->setCellValue('M1', 'Cod Proveedor')
->setCellValue('N1', 'Proveedor')


->setCellValue('O1', 'Stock Teorico')
->setCellValue('P1', 'Ult Costo Compra')
->setCellValue('Q1', 'Ult Costo Receta')
->setCellValue('R1', 'Precio venta')
->setCellValue('S1', 'Utilidad Bruta')
->setCellValue('T1', 'Recargo %')
->setCellValue('U1', 'Margen %')
->setCellValue('V1', 'IVA %')

->setCellValue('W1', 'Habilita Compra')
->setCellValue('X1', 'Habilita Inventario')
->setCellValue('Y1', 'Acepta Devolucion')
        ->setCellValue('Z1', 'Cod Centro Prod')
        ->setCellValue('AA1', 'Centro Prod')
        ->setCellValue('AB1', 'Cod Art Cont')
        ->setCellValue('AC1', 'Art Cont')
;


$objPHPExcel->getActiveSheet()->getStyle("A1:AC1")->getFont()->setSize(12)
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
$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('M')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('N')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('O')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('P')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('R')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('S')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('T')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('U')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('V')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('W')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('X')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('Y')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('Z')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('AA')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('AB')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('AC')->setAutoSize(true);
/**************   INICIA EL WHILE ***********************/


$pcount = 0;
$i = 2;
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
    ->setCellValue('C'.$i, $rs->fields['descripcion'])
    ->setCellValue('D'.$i, capitalizar(antixss($rs->fields['unidadmedida'])))

    ->setCellValue('E'.$i, $rs->fields['idgrupoinsu'])
    ->setCellValue('F'.$i, $rs->fields['grupo_stock'])
    ->setCellValue('G'.$i, $rs->fields['idcategoria'])
    ->setCellValue('H'.$i, $rs->fields['categoria'])
    ->setCellValue('I'.$i, $rs->fields['idsubcate'])
    ->setCellValue('J'.$i, $rs->fields['subcategoria'])
    ->setCellValue('K'.$i, $rs->fields['idmarca'])
    ->setCellValue('L'.$i, $rs->fields['marca'])
    ->setCellValue('M'.$i, $rs->fields['idproveedor'])
    ->setCellValue('N'.$i, $rs->fields['proveedor'])


    ->setCellValue('O'.$i, $rs->fields['stock_teorico'])
    ->setCellValue('P'.$i, $rs->fields['ultimocosto'])
    ->setCellValue('Q'.$i, $rs->fields['costo_receta'])
    ->setCellValue('R'.$i, $rs->fields['precio_venta'])

    ->setCellValue('S'.$i, $utilidadbruta)
    ->setCellValue('T'.$i, $recargo)
    ->setCellValue('U'.$i, $margen)
    ->setCellValue('V'.$i, $rs->fields['tipoiva'])

    ->setCellValue('W'.$i, $rs->fields['habilita_compra'])
    ->setCellValue('X'.$i, $rs->fields['habilita_inventario'])
    ->setCellValue('Y'.$i, $rs->fields['acepta_devolucion'])

    ->setCellValue('Z'.$i, $rs->fields['cod_centro_prod'])
    ->setCellValue('AA'.$i, $rs->fields['centro_prod'])
    ->setCellValue('AB'.$i, $rs->fields['cod_art_cont'])
    ->setCellValue('AC'.$i, $rs->fields['art_cont'])

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
$ile = 'pla_stock_glob_'.$current.'.xlsx';

// Se modifican los encabezados del HTTP para indicar que se envia un archivo de Excel.

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

header('Content-Disposition: attachment;filename='.$ile);

header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

$objWriter->save('php://output');
exit;
