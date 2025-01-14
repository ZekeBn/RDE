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

////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////
////////////////////////////////////////////////////////////////


// // limpiar la db
// ///////////////
// $consulta = " $consulta ="TRUNCATE tmpcompras";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE tmpcompradeta ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE tmpcompradetaimp";
//  $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE tmpcompravenc ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE compras ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE compras_detalles ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE cuentas_empresa	";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE transacciones_compras ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE gest_depositos_compras ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE gest_depositos_stock_gral ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE gest_depositos_stock ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE stock_movimientos";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE productos_stock_global ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE costo_productos ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE facturas_proveedores ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE operaciones_proveedores ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE operaciones_proveedores_detalle ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE cuentas_empresa";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE facturas_proveedores_compras";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE facturas_proveedores_det_impuesto";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE cn_conceptos_mov";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE productos_listaprecios";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE compras_ordenes";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE compras_ordenes_detalles";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE ingredientes";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE embarque";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE cn_plancuentas_detalles";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE cn_articulos_vinculados";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate despacho";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE recetas ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE recetas_detalles";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE tmp_ventares";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE tmp_ventares_cab";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE ventas_detalles";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE ventas";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE gest_depositos_ajustes_stock";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE gest_depositos_ajustes_stock_det";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE gest_depositos_stock_almacto";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE conteo";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE conteo_grupos";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " TRUNCATE conteo_detalles";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate ventas_detalles_impuesto";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate ventas_detalles";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate venta_receta";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate gest_pagos_det_datos";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate gest_pagos_det";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate gest_pagos";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate adherente_estadocuenta";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate cuentas_clientes_det";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate cuentas_clientes";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate ventas";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate tmp_ventares_cab";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate carrito_cobros_ventas";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate tmp_ventares";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate cortesia_diaria";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate ventas_datosextra";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate ventas_reg_adicionales";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate lastcomprobantes";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate devolucion";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate devolucion_det";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate retiros_ordenes";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate nota_credito_cabeza";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate nota_credito_cuerpo";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = " truncate nota_credito_cuerpo_impuesto";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



//////////
// $consulta=" DELETE FROM `compras` WHERE `compras`.`idcompra` = 17  ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta=" DELETE FROM `compras` WHERE `compras`.`idcompra` = 16  ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta=" DELETE FROM `compras` WHERE `compras`.`idcompra` = 15  ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta=" DELETE FROM `compras` WHERE `compras`.`idcompra` = 14  ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta=" DELETE FROM `compras` WHERE `compras`.`idcompra` = 5  ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta="DELETE FROM `compras_detalles` WHERE `compras_detalles`.`idregs` = 23";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta="DELETE FROM `compras_detalles` WHERE `compras_detalles`.`idregs` = 22";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta="DELETE FROM `compras_detalles` WHERE `compras_detalles`.`idregs` = 21";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta="DELETE FROM `compras_detalles` WHERE `compras_detalles`.`idregs` = 20";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta="DELETE FROM `compras_detalles` WHERE `compras_detalles`.`idregs` = 7";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = "SELECT * FROM usuarios WHERE usuarios.usuario = \"RAMON\" ";
// $rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
// echo json_encode($rs->fields);
// $consulta = "SELECT * FROM usuarios_mozos WHERE usuarios_mozos.idusu = 23 ";
// $rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
// echo json_encode($rs->fields);exit;

// $consulta = "TRUNCATE sub_categorias_secundaria";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = "TRUNCATE sub_categorias";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = "TRUNCATE categorias";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = "TRUNCATE categorias_tmp";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// exit;
// $consulta = "update  preferencias_proveedores
// set
// ruc_duplicado='S',
// razon_social_duplicado='S'
// where
// idpreferencia=1
// ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = "
// DELETE FROM `insumos_lista` WHERE idinsumo not in (1,2,3,4,5,6,7,8,9);
// ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = "
// DELETE FROM `ingredientes` WHERE 1;
// ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = "
// DELETE FROM `productos` WHERE 1;
// ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = "
// DELETE FROM `recetas_detalles` WHERE 1;
// ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = "
// DELETE FROM `productos_vencimiento` WHERE 1;
// ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = "
// DELETE FROM `costo_productos` WHERE 1;
// ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = "
// DELETE FROM `producto_impresora` WHERE 1;
// ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = "
// DELETE FROM `recetas` WHERE 1;
// ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// $consulta = "
// DELETE FROM productos_sucursales WHERE 1;
// ";
// $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// exit;

// $a=array("1"=>array("nombre"=>"bebida","margen"=>1));
// $a["2"]=array("nombre"=>"ron","margen"=>1);
// echo $a[2]["nombre"];exit;

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

$conulta = "truncate ventas_detalles_impuesto";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate ventas_detalles";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate venta_receta";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate gest_pagos_det_datos";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate gest_pagos_det";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate gest_pagos";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate adherente_estadocuenta";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate cuentas_clientes_det";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate cuentas_clientes";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate ventas";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate tmp_ventares_cab";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate carrito_cobros_ventas";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate tmp_ventares";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate cortesia_diaria";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate ventas_datosextra";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate ventas_reg_adicionales";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate lastcomprobantes";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate devolucion";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate devolucion_det";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate retiros_ordenes";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate nota_credito_cabeza";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate nota_credito_cuerpo";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$conulta = "truncate nota_credito_cuerpo_impuesto";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




exit;
$consulta = "select * from compras where idcompra=2";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
echo json_encode($rs->fields);
exit;
// $ssuni = select_max_id_suma_uno("usuarios_mozos","ssuni")["ssuni"];
// $consulta="
// 		insert into usuarios_mozos
// 		(ssuni,idusu, codigo_acciones,estado_mozo, registrado_el)
// 		values
// 		($ssuni, 23, \"ramon\", 1, '$ahora')
// 		";
// 		$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
// echo $ssuni;exit;
$idcompra_ref = 1;

$consulta = "SELECT despacho.cotizacion from despacho WHERE despacho.idcompra=$idcompra_ref and estado=1 ";
$rs_detalles_despacho = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$cotizacion_despacho = floatval($rs_detalles_despacho->fields['cotizacion']);

$consulta = "SELECT idcompra 
from compras 
where idcompra_ref=$idcompra_ref";


$rs_id_array_gastos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$rs_id_array_gastos = $rs_id_array_gastos->GetArray();
$gastos_totales = 0;
foreach ($rs_id_array_gastos as $key => $value) {
    $idcompra_gasto = $value['idcompra'];
    $consulta = "SELECT usa_cot_despacho from compras where idcompra= $idcompra_gasto ";
    $rs_usa_cot_despacho = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $usa_cot_despacho = ($rs_usa_cot_despacho->fields['usa_cot_despacho']);


    $consulta = "SELECT cotizaciones.cotizacion from cotizaciones
  WHERE cotizaciones.idcot = ( SELECT compras.idcot from compras where idcompra = $idcompra_gasto ) 
  ";
    $rs_detalles_compra = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cotizacion_gasto = floatval($rs_detalles_compra->fields['cotizacion']);



    $consulta = "SELECT SUM(subtotal) as gastos 
  from compras_detalles 
  where idcompra = $idcompra_gasto
  and compras_detalles.codprod not in (
  SELECT idinsumo FROM insumos_lista 
  WHERE UPPER(insumos_lista.descripcion) like \"%DESCUENTO%'\" 
  or  UPPER(insumos_lista.descripcion) like \"%AJUSTE%\" )";
    $rs_gastos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    if ($usa_cot_despacho == "S") {
        $gastos_totales = $gastos_totales + (floatval($rs_gastos->fields['gastos']) / $cotizacion_gasto) * $cotizacion_despacho;
    } else {
        $gastos_totales = $gastos_totales + $rs_gastos->fields['gastos'];
    }



}
echo $gastos_totales;





//Compra obteniendo totalidad



$consulta = "SELECT usa_cot_despacho from compras where idcompra= $idcompra_ref ";
$rs_usa_cot_despacho = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$usa_cot_despacho = ($rs_usa_cot_despacho->fields['usa_cot_despacho']);


$consulta = "SELECT cotizaciones.cotizacion from cotizaciones
WHERE cotizaciones.idcot = ( SELECT compras.idcot from compras where idcompra = $idcompra_ref ) 
";
$rs_detalles_compra = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$cotizacion_compra = floatval($rs_detalles_compra->fields['cotizacion']);



$consulta = "SELECT SUM(subtotal) as total 
from compras_detalles 
where idcompra=$idcompra_ref
and compras_detalles.codprod not in (
  SELECT idinsumo FROM insumos_lista 
  WHERE UPPER(insumos_lista.descripcion) like \"%DESCUENTO%'\" 
  or  UPPER(insumos_lista.descripcion) like \"%AJUSTE%\" )";
$rs_compra = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if ($usa_cot_despacho == "S") {
    $total_compra = ($rs_compra->fields['total'] / $cotizacion_compra) * $cotizacion_despacho;
} else {
    $total_compra = $rs_compra->fields['total'];
}
//Obteniendo productos
$consulta = "SELECT compras_detalles.* from compras_detalles
INNER JOIN compras on compras.idcompra = compras_detalles.idcompra
where compras.idcompra = $idcompra_ref 
and compras_detalles.codprod not in (
  SELECT idinsumo FROM insumos_lista 
  WHERE UPPER(insumos_lista.descripcion) like \"%DESCUENTO%\" 
  or  UPPER(insumos_lista.descripcion) like \"%AJUSTE%\" 
)
";
$rs_detalles_compras = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
while (!$rs_detalles_compras->EOF) {

    if ($usa_cot_despacho == "S") {
        $total_producto = ($rs_detalles_compras->fields['subtotal'] / $cotizacion_compra) * $cotizacion_despacho;
        $costo = ($rs_detalles_compras->fields['costo'] / $cotizacion_compra) * $cotizacion_despacho;
    } else {
        $total_producto = $rs_detalles_compras->fields['subtotal'];
        $costo = $rs_detalles_compras->fields['costo'];
    }
    $idregs = $rs_detalles_compras->fields['idregs'];
    $lote = antisqlinyeccion($rs_detalles_compras->fields['lote'], "text");
    $vencimiento = antisqlinyeccion($rs_detalles_compras->fields['vencimiento'], "date");
    $cantidad = $rs_detalles_compras->fields['cantidad'];
    $codprod = $rs_detalles_compras->fields['codprod'];
    $gastos = (($total_producto) / ($total_compra)) * $gastos_totales;
    $precio_costo = ($gastos + $total_producto) / $cantidad;
    $whereadd = "";
    if ($lote != "NULL" && $vencimiento != "NULL") {
        $whereadd = " and vencimiento=$vencimiento and lote=$lote ";
    } else {
        $whereadd = "and vencimiento is NULL and lote is NULL ";
    }
    $consulta = "
  update
    compras_detalles
  set
    gastos=$gastos
  where
    idregs = $idregs
  ";




    ////////////////////anterior
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $update = "Update costo_productos set costo_cif=$precio_costo, modificado_el='$ahora', precio_costo=$costo where id_producto=$codprod and idcompra=$idcompra_ref $whereadd  ";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
    // echo $update;exit;
    $rs_detalles_compras->MoveNext();
}



?>
<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// echo "hola";exit;
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";

$dirsup = "S";
require_once("../includes/rsusuario.php");
// $diccionario=array();
// $diccionario["hola"]=array('valor'=>'1','texto'=>'Hola');
// function guardar_diccionario($clave,&$diccionario,$valor){
// 	if (array_key_exists($clave, $diccionario)) {
// 		$diccionario[$clave][]=$valor;
// 	  }else{
// 		$diccionario[$clave] = array($valor);
// 	  }
// }
// guardar_diccionario("arroz",$diccionario,array('valor'=>'2','texto'=>'adios'));
// echo var_dump($diccionario);

?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Datos Aduana</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	
<div class="col-12">
<?php
function verificarFormato($cadena)
{
    // Verificar si la cadena tiene el formato "###-###-#######"
    if (preg_match('/^\d{3}-\d{3}-\d{7}$/', $cadena)) {
        return "El string tiene el formato '###-###-#######'.";
    } elseif (preg_match('/^\d+$/', $cadena)) {
        return "El string está compuesto únicamente por números sin guiones.";
    } else {
        return "El string no cumple con ninguno de los formatos especificados.";
    }
}

// Ejemplo de uso:
$cadena1 = "001-002-0000123";
$cadena2 = "123456789";

echo verificarFormato($cadena1) . "\n";
echo verificarFormato($cadena2) . "\n";
?>
</div>
   



                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            

            
            
            
          </div>
        </div>
        <!-- /page content -->
		  
        <!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        
            <div class="modal-header">
            	<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
           		<h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
            	Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
            	<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        
        </div>
    </div>
</div>
        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
