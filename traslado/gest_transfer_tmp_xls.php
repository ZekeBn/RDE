<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "222";
require_once("includes/rsusuario.php");
require_once '../clases/PHPExcel.php';

set_time_limit(0);
ini_set('memory_limit', '512M');


$idtanda = intval($_GET['idtanda']);
if ($idtanda == 0) {
    echo "No se indico la tanda.";
    exit;
}


$consulta = "
	select *
	from (
			
		Select idtanda,  idproducto as 'Cod Articulo',
			(
			select barcode 
			from productos
			 inner join insumos_lista on insumos_lista.idproducto = productos.idprod_serial
			 where insumos_lista.idinsumo = tmp_transfer.idproducto
			 
			 )  as 'Codigo Barras',
		descripcion as 'Articulo', necesidad as 'Necesidad', cantidad as 'Cantidad Transferir', cantidad-necesidad as 'Diferencia'
	
		from tmp_transfer
		where 
		idtanda=$idtanda
		
		UNION ALL
			
		Select idtanda,  idproducto as 'Cod Articulo',
			(
			select barcode 
			from productos
			 inner join insumos_lista on insumos_lista.idproducto = productos.idprod_serial
			 where insumos_lista.idinsumo = tmp_transfer_faltan.idproducto
			 
			 )  as 'Codigo Barras',
		descripcion as 'Articulo', necesidad as 'Necesidad', cantidad as 'Cantidad Transferir', cantidad-necesidad as 'Diferencia'
		
		from tmp_transfer_faltan
		where 
		idtanda=$idtanda
	) tmp_transferencias
	order by Articulo asc
	";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


$impreso = date("d/m/Y H:i:s");




//generando nombres de columna para excel
$Cantidad_de_columnas_a_crear = $rs->_numOfFields;  // obtiene la cantidad de columnas
$Contador = 0;
$Letra = 'A';
$letrasxls = '';
// genera un array con las letras en base a la cantidad de columnas
while ($Contador < $Cantidad_de_columnas_a_crear) {
    $letrasxls .= ",".$Letra;
    $Contador++;
    $Letra++;
}
// convierte el csv en un array
$letras_xls = $letrasxls;
$letras_xls_ar = explode(',', $letras_xls);

// inicializa el archivo Excel
$objPHPExcel = new PHPExcel();
// Establecer propiedades
$objPHPExcel->getProperties()
->setCreator("e-karú")
->setLastModifiedBy("WEB")
->setTitle("Lista temp a transferir")
->setSubject("XLS temp a transferir")
->setDescription("Lista temp a transferir")
->setKeywords("Excel Office 2007 openxml php")
->setCategory("Reportes");

// asigna los datos de la consulta a una variable
$array = $rs->fields;

// establece la hoja activa
$objPHPExcel->setActiveSheetIndex(0);
$objWorksheet = $objPHPExcel->getActiveSheet();


// CONSTRUYE CABECERA
$array = $rs->fields;
$col = 0;
$colspan = 0;
$i = 0;
foreach ($array as $key => $value) {
    $i++;
    $columna = $letras_xls_ar[$i].'1';
    $objWorksheet->setCellValue($columna, $key);
}
reset($array);

// AUTO AJUSTA ANCHO COLUMNAS Y CAMBIA FORMATO DE LA CABECERA
$i = 0;
foreach ($array as $key => $value) {
    $i++;
    $objPHPExcel->getActiveSheet()->getColumnDimension($letras_xls_ar[$i])->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getStyle($letras_xls_ar[$i].'1')->getFont()->setSize(12)->setBold(true);
}
reset($array);


//CONSTRUYE CUERPO
$ante = 0;
$fila = 1;
while (!$rs->EOF) {
    $fila++;
    $array = $rs->fields;
    $i = 0;
    foreach ($array as $key => $value) {
        $i++;
        $columna = $letras_xls_ar[$i].$fila;
        $objWorksheet->setCellValue($columna, $value);
    }
    $rs->MoveNext();
}




/******** MAERA DEL PIE *********************/


$current = date("YmdHis");
$objPHPExcel->getActiveSheet()->setTitle('Lista tmp a transferir');

// Establecer la hoja activa, para que cuando se abra el documento se muestre primero.

$objPHPExcel->setActiveSheetIndex(0);

$ile = 'transfer_tmp_'.$idtanda.'_'.$current.'.xlsx';


// Se modifican los encabezados del HTTP para indicar que se envia un archivo de Excel.

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

header('Content-Disposition: attachment;filename='.$ile);

header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
exit;
