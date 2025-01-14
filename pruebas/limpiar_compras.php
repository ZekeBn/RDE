<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");

$fecha_str = '2022-05-13';
// $fecha = strtotime($fecha_str);
// $inicio = date('Y-m-d', $fecha); // Salida: 2022-05-13

// $fecha_str = '2024-05-13';
// $fecha = strtotime($fecha_str);
// $fin = date('Y-m-d', $fecha); // Salida: 2022-05-13


// $retorno='';
// 	//retorna la diferencia entre dos fechas, para calcular antiguedad en tiempo

// 	$datetime1=new DateTime($inicio);
// 	$datetime2=new DateTime($fin);
//  $date = $fin;
// 	# obtenemos la diferencia entre las dos fechas
// 	$converted_date = date("Y", strtotime($date)) . ', ' . (date("n", strtotime($date))-1) . ', ' . date("j", strtotime($date));
//     $res=((8000.08/1000));
// 	echo checkdate(51,11,2023);


// $consulta = "SELECT idinsumo FROM insumos_lista
// 	WHERE UPPER(insumos_lista.descripcion) like \"%DESCUENTO%\"
// 	or  UPPER(insumos_lista.descripcion) like \"%AJUSTE%\" ";
// 	$rs2=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
// 	$bandera=false;
// 	$idp=55;
// 	while (!$rs2->EOF){
// 		if( $rs2->fields['idinsumo']==$idp){
// 			$bandera = true;

// 		}
// 	$rs2->MoveNext(); }
// 	echo $bandera ?  "si":"no";


// $consulta = "SELECT idinsumo FROM insumos_lista
// WHERE UPPER(insumos_lista.descripcion) LIKE '%DESCUENTO%'
// OR UPPER(insumos_lista.descripcion) LIKE '%AJUSTE%'";
// $rs2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// $ids = array(); // Array para almacenar los IDs obtenidos

// while (!$rs2->EOF) {
// 	$ids[] = $rs2->fields['idinsumo']; // Agregar el ID al array
// 	$rs2->MoveNext();
// }

// $idp = 55;
// $bandera = in_array($idp, $ids); // Verificar si $idp está en el array de IDs

// echo $bandera ? "si" : "no";

// $idp = 5;
// $bandera = in_array($idp, $ids); // Verificar si $idp está en el array de IDs

// echo $bandera ? "si" : "no";

// function generarCombinatorias($filas, $columnas) {
// 	$combinatorias = [];

// 	for ($i = 1; $i <= $filas; $i++) {
// 		$combinacionesFila = [];
// 		for ($j = 1; $j <= $columnas; $j++) {
// 			$combinacionesFila[] = $j;
// 		}
// 		$combinatorias[$i] = $combinacionesFila;
// 	}

// 	return $combinatorias;
// }

// // Ejemplo de uso
// $filas = 3;
// $columnas = 5;
// $combinatorias = generarCombinatorias($filas, $columnas);
// $a=array_diff($combinatorias[1],[1]);
// $a = array_values($a);
// echo json_encode($combinatorias);
// echo json_encode($a);

// echo "<br>";
// echo floatval("s");
// echo " eso era un floatval";

// $consulta = "SELECT DISTINCT(conteo_detalles.idalm)
// 			FROM conteo_detalles
// 			INNER JOIN conteo ON conteo.idconteo = conteo_detalles.idconteo
// 			where
// 			conteo.idconteo_ref=21
// 			and estado=2
// ";
// $rs_conteo_deposito= $conexion->GetCol($consulta) or die(errorpg($conexion,$consulta));
// 				var_dump($rs_conteo_deposito);

// $array = [];
// for ($i=0; $i < 10 ; $i++) {
// 	$array[] = [$i,$i,0];
// }
// $array[] = [1,2,0];
// $buscar= [2,1,0];
// echo json_encode($array);
// echo json_encode(array_search($buscar, $array));
// echo json_encode(in_array($buscar, $array));






// $consulta = " UPDATE `preferencias_compras` SET `multimoneda_local` = 'S' WHERE `preferencias_compras`.`idprefe` = 1
// ";
// $rs_conteo_deposito= $conexion->GetCol($consulta) or die(errorpg($conexion,$consulta));





// // This will output the barcode as HTML output to display in the browser
// $generator = new Picqer\Barcode\BarcodeGeneratorHTML();
// echo $generator->getBarcode('081231723897', $generator::TYPE_CODE_128);


// $consulta = "
// ALTER TABLE `cotizaciones` ADD `registrado_el` DATETIME NULL";
// $rs_conteo_deposito= $conexion->GetCol($consulta) or die(errorpg($conexion,$consulta));

// $consulta ="ALTER TABLE `cotizaciones` ADD `registrado_por` INT(11) NOT NULL ";
// $rs_conteo_deposito= $conexion->GetCol($consulta) or die(errorpg($conexion,$consulta));

// limpiar la db




$consulta = "TRUNCATE tmpcompras";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE tmpcompradeta ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE tmpcompradetaimp";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE tmpcompravenc ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE compras ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE compras_detalles ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE cuentas_empresa	";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE transacciones_compras ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE gest_depositos_compras ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE gest_depositos_stock_gral ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE gest_depositos_stock ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE stock_movimientos";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE productos_stock_global ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE costo_productos ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE facturas_proveedores ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE operaciones_proveedores ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE operaciones_proveedores_detalle ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE cuentas_empresa";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE facturas_proveedores_compras";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE facturas_proveedores_det_impuesto";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE cn_conceptos_mov";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE productos_listaprecios";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE compras_ordenes";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE compras_ordenes_detalles";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE ingredientes";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE embarque";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE cn_plancuentas_detalles";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE cn_articulos_vinculados";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate despacho";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE recetas ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE recetas_detalles";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE tmp_ventares";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE tmp_ventares_cab";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE ventas_detalles";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE ventas";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE gest_depositos_ajustes_stock";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE gest_depositos_ajustes_stock_det";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE gest_depositos_stock_almacto";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE conteo";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE conteo_grupos";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "TRUNCATE conteo_detalles";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



$consulta = "truncate ventas_detalles_impuesto";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate ventas_detalles";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate venta_receta";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate gest_pagos_det_datos";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate gest_pagos_det";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate gest_pagos";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate adherente_estadocuenta";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate cuentas_clientes_det";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate cuentas_clientes";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate ventas";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate tmp_ventares_cab";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate carrito_cobros_ventas";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate tmp_ventares";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate cortesia_diaria";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate ventas_datosextra";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate ventas_reg_adicionales";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate lastcomprobantes";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate devolucion";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate devolucion_det";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate retiros_ordenes";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate nota_credito_cabeza";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate nota_credito_cuerpo";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "truncate nota_credito_cuerpo_impuesto";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


// $consulta = "TRUNCATE sub_categorias_secundaria";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = "TRUNCATE sub_categorias";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = "TRUNCATE categorias";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = "TRUNCATE categorias_tmp";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
exit;
