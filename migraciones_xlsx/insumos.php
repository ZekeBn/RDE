<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require("../../clases/PHPExcel.php");

// archivos 9
require_once("../includes/upload.php");
require_once("../includes/funcion_upload.php");
require_once("../insumos/funciones_insumos.php");
set_time_limit(0);

function limpia_csv_externo($texto)
{
    $texto = utf8_encode($texto);
    return $texto;
}


$consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%DESPACHO\" ";
$rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto_despacho = intval($rs_conceptos->fields['idconcepto']);

$consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%FLETE\" ";
$rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto_flete = intval($rs_conceptos->fields['idconcepto']);





$consulta = "SELECT medidas.id_medida as id_medida FROM medidas WHERE UPPER(medidas.nombre) = UPPER('UNIDADES') ";
$rs_medida = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idunidad = $rs_medida -> fields['id_medida'];

$consulta = "SELECT idconcepto FROM cn_conceptos WHERE UPPER(descripcion) = 'MERCADERIAS' ";
$rs_medida = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto = $rs_medida -> fields['idconcepto'];

$consulta = "SELECT id_medida, nombre FROM medidas where estado = 1 AND 
medidas.nombre LIKE \"%cajas\" order by nombre asc";
$rs_cajas = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcaja = $rs_cajas->fields['id_medida'];

$consulta = "SELECT id_medida, nombre FROM medidas where estado = 1 
AND medidas.nombre LIKE \"%pall%\" order by nombre asc ";
$rs_pallets = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idpallet = $rs_pallets->fields['id_medida'];


$array_fallados = [];
$status = "";
$msg = urlencode("Archivo Cargado Exitosamente!");
if (isset($_POST["MM_upload"]) && ($_POST["MM_upload"] == "form1")) {
    $archivo = $_FILES['archivo'];
    if ($archivo['name'] != "") {
        $archivoExcel = $archivo['tmp_name'];
        $excel = PHPExcel_IOFactory::load($archivoExcel);
        $hoja = $excel->getSheet(0);// categoria
        $numFilas_categorias = $hoja->getHighestRow();
        $numColumnas_categorias = PHPExcel_Cell::columnIndexFromString($hoja->getHighestColumn());
        $valido = "S";
        $errores = "";
        for ($fila = 2; $fila <= $numFilas_categorias; $fila++) {
            $nombre_articulo = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(1, $fila)->getValue()), "text");
            $cantidad_por_caja = intval($hoja->getCellByColumnAndRow(3, $fila)->getValue());
            $cantidad_por_pallet = intval($hoja->getCellByColumnAndRow(4, $fila)->getValue());
            $categoria_nombre = trim($hoja->getCellByColumnAndRow(6, $fila)->getValue());
            $familia_nombre = trim($hoja->getCellByColumnAndRow(8, $fila)->getValue());
            $sub_familia_nombre = trim($hoja->getCellByColumnAndRow(10, $fila)->getValue());
            $pais_nombre = trim($hoja->getCellByColumnAndRow(12, $fila)->getValue());
            $dias_utiles = intval($hoja->getCellByColumnAndRow(13, $fila)->getValue());
            $dias_stock = intval($hoja->getCellByColumnAndRow(14, $fila)->getValue());
            $codbar = trim($hoja->getCellByColumnAndRow(15, $fila)->getValue());
            if ($codbar == "#N/A") {
                $codbar = "";

            }
            $codbar = antisqlinyeccion($codbar, "text");
            $costo = floatval($hoja->getCellByColumnAndRow(16, $fila)->getValue());
            $iva_nombre = trim($hoja->getCellByColumnAndRow(18, $fila)->getValue());
            //aca ver
            $cant_caja_edi = intval($hoja->getCellByColumnAndRow(23, $fila)->getValue());
            $largo = floatval($hoja->getCellByColumnAndRow(24, $fila)->getValue());
            $alto = floatval($hoja->getCellByColumnAndRow(25, $fila)->getValue());
            $ancho = floatval($hoja->getCellByColumnAndRow(26, $fila)->getValue());
            $peso = floatval($hoja->getCellByColumnAndRow(27, $fila)->getValue());
            $articulo_origen = trim($hoja->getCellByColumnAndRow(29, $fila)->getValue());
            $rs = trim($hoja->getCellByColumnAndRow(30, $fila)->getValue());
            $rspa = trim($hoja->getCellByColumnAndRow(31, $fila)->getValue());
            $mod_precio = trim($hoja->getCellByColumnAndRow(32, $fila)->getValue());
            $maneja_lote = trim($hoja->getCellByColumnAndRow(33, $fila)->getValue());
            $reg_turismo = trim($hoja->getCellByColumnAndRow(34, $fila)->getValue());
            $es_alternativo = trim($hoja->getCellByColumnAndRow(35, $fila)->getValue());
            $maneja_stock = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(36, $fila)->getValue()), "text");
            $marca = trim($hoja->getCellByColumnAndRow(38, $fila)->getValue());
            $estado = trim($hoja->getCellByColumnAndRow(39, $fila)->getValue());
            $ruc = trim($hoja->getCellByColumnAndRow(20, $fila)->getValue());
            $ruc = $ruc == 0 ? "" : $ruc;
            $ruc = antisqlinyeccion($ruc, "text");

            $razon = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(21, $fila)->getValue()), "text");
            $fantasia = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(22, $fila)->getValue()), "text");




            // echo "nombre =".$nombre_articulo . "       cant_caja=" .
            // $cantidad_por_caja . "       cant_pallet=" .
            // $cantidad_por_pallet . "       cat_nombre=" .
            // $categoria_nombre . "       subcate=" .
            // $familia_nombre . "       subcate_sec=" .
            // $sub_familia_nombre . "       pais=" .
            // $pais_nombre . "       dias_ut=" .
            // $dias_utiles . "       dias_stock=" .
            // $dias_stock . "       codbar=" .
            // $codbar . "       costo=" .
            // $costo . "       iva=" .
            // $iva_nombre . "       can_edi=" .
            // $cant_caja_edi . "       largo=" .
            // $largo . "       ancho=" .
            // $alto . "       alto=" .
            // $ancho . "       peso=" .
            // $peso . "       art_ori=" .
            // $articulo_origen . "       rs=" .
            // $rs . "       rspa=" .
            // $rspa . "       mod_precio=" .
            // $mod_precio . "       maneja_lote=" .
            // $maneja_lote . "       reg_turismo=" .
            // $reg_turismo . "       es_alt=" .
            // $es_alternativo . "       maneja_stock=" .
            // $maneja_stock . "       marca=" .
            // $marca . "      estado=" .
            // $estado . " ruc=".
            // $ruc . " razon=".
            // $razon . " fantasia=".
            // $fantasia . "<br>";


            ///idcategoria
            $consulta = "SELECT id_categoria FROM categorias WHERE UPPER(nombre) = UPPER('$categoria_nombre')";
            $rs_medida = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $id_categoria = $rs_medida -> fields['id_categoria'];
            // echo $id_categoria."<br>";
            $consulta = "SELECT idsubcate FROM sub_categorias WHERE UPPER(descripcion) = UPPER('$familia_nombre')";
            $rs_medida = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $id_familia_nombre = $rs_medida -> fields['idsubcate'];
            // echo $id_familia_nombre."<br>";

            $consulta = "SELECT idsubcate_sec FROM sub_categorias_secundaria WHERE UPPER(descripcion) = UPPER('$sub_familia_nombre')";
            $rs_medida = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $id_sub_familia_nombre = $rs_medida -> fields['idsubcate_sec'];
            // echo $id_sub_familia_nombre."<br>";


            $consulta = "SELECT idpais FROM paises_propio WHERE UPPER(nombre) = UPPER('$pais_nombre')";
            $rs_medida = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idpais = $rs_medida -> fields['idpais'];
            // echo "el idpais=".$idpais."<br>";

            $marca = antisqlinyeccion($marca, 'text');
            $consulta = "SELECT idmarca FROM marca WHERE UPPER(marca) = UPPER($marca)";
            $rs_medida = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idmarca = $rs_medida -> fields['idmarca'];
            // echo "el idmarca de ".$marca."=".$idmarca."<br>";
            // exit;

            $consulta = "SELECT idgrupoinsu FROM grupo_insumos WHERE UPPER(nombre) = UPPER('GRUPO STOCK')";
            $rs_medida = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idgrupoinsu = $rs_medida -> fields['idgrupoinsu'];
            // echo "grupo insu= ".$idgrupoinsu;exit;
            $consulta = "SELECT idtipoiva, iva_porc
				FROM tipo_iva
				WHERE iva_describe = 
					CASE 
						WHEN '$iva_nombre' = 'GRAVADAS 10' THEN '10%'
						WHEN '$iva_nombre' = 'GRAVADAS 5' THEN '5%'
						ELSE 'Exento'
					END;";
            $rs_medida = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idtipoiva = $rs_medida -> fields['idtipoiva'];
            $iva_porc = $rs_medida -> fields['iva_porc'];



            $consulta = "SELECT idproveedor FROM proveedores WHERE nombre = $razon and fantasia = $fantasia ";

            if ($ruc == "NULL") {
                $consulta .= " and ruc is null";
            } else {
                $consulta .= " and ruc = $ruc";
            }
            $rs_medida = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idproveedor = $rs_medida -> fields['idproveedor'];

            // if($estado !=""){
            // 	if($estado == "A"){
            // 		$estado = A;
            // 	}else{
            // 		$estado = 6;
            // 	};
            // }

            if ($maneja_lote != "") {
                if ($maneja_lote == "S") {
                    $maneja_lote = 1;
                } else {
                    $maneja_lote = 6;
                };
            }






            ///////////////////////validaciones




            // validaciones basicas
            $valido = "S";
            $errores = "<br>";


            // control de formularios, seguridad para evitar doble envio y ataques via bots


            // recibe parametros
            $idproducto = antisqlinyeccion('', "int");
            $descripcion = $nombre_articulo;
            $idconcepto = $idconcepto;
            //$idcategoria=antisqlinyeccion('',"int");
            //$idsubcate=antisqlinyeccion('',"int");
            $idmarcaprod = antisqlinyeccion($idmarca, "int");
            $idmedida = $idunidad;
            $cant_medida2 = $cantidad_por_caja;
            $cant_medida3 = $cantidad_por_pallet;
            $idmedida2 = $idcaja;
            $idmedida3 = $idpallet;
            $idsubcate_sec = $id_sub_familia_nombre;
            $idpais = antisqlinyeccion($idpais, "int");



            $dias_utiles = $dias_utiles;
            $dias_stock = $dias_stock;
            $bar_code = $codbar;



            if (intval($cant_medida2) > 0) {
                $idmedida2 = $idcaja;
            }
            if (intval($cant_medida3) > 0) {
                $idmedida3 = $idpallet;
            }
            $produccion = antisqlinyeccion('1', "int");
            $costo = antisqlinyeccion('0', "float");
            $idtipoiva_compra = $idtipoiva;
            $mueve_stock = $maneja_stock;
            $paquete = antisqlinyeccion('', "text");
            $cant_paquete = antisqlinyeccion('', "float");
            $estado = antisqlinyeccion($estado, "text");
            $idempresa = antisqlinyeccion(1, "int");
            $idgrupoinsu = $idgrupoinsu;
            $ajuste = antisqlinyeccion('N', "text");
            $fechahora = antisqlinyeccion($ahora, "text");
            $registrado_por_usu = antisqlinyeccion($idusu, "int");
            $hab_compra = antisqlinyeccion('1', "int");
            $hab_invent = antisqlinyeccion('1', "int");
            $idproveedor = antisqlinyeccion($idproveedor, "int");
            $aplica_regalia = antisqlinyeccion('S', "text");
            $solo_conversion = antisqlinyeccion('', "int");
            $respeta_precio_sugerido = antisqlinyeccion('N', "text");
            $idprodexterno = antisqlinyeccion('', "int");
            $restaurado_por = antisqlinyeccion('', "int");
            $restaurado_el = antisqlinyeccion('', "text");
            $idcategoria = antisqlinyeccion($id_categoria, "int");
            $idsubcate = antisqlinyeccion($id_familia_nombre, "int");
            $cuentacontable = antisqlinyeccion(0, "int");
            $centroprod = intval(0);
            $idagrupacionprod = antisqlinyeccion(0, "int");
            $rendimiento_porc = antisqlinyeccion(100, "float");

            //opcionales
            // TODO: poner preferencias

            ////////////////////////encontrar cod origen ARTICULO MIGRACION #N/A
            $consulta = "SELECT idinsumo FROM insumos_lista WHERE UPPER(descripcion) = UPPER('$articulo_origen')";
            $rs_medida = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idinsumo_origen = $rs_medida -> fields['idinsumo'];


            $cant_caja_edi = antisqlinyeccion($cant_caja_edi, "float");
            $largo = antisqlinyeccion($largo, "float");
            $ancho = antisqlinyeccion($ancho, "float");
            $alto = antisqlinyeccion($alto, "float");
            $peso = antisqlinyeccion($peso, "float");
            $cod_fob = antisqlinyeccion("", "text");
            $rs = antisqlinyeccion($rs, "text");
            $rspa = antisqlinyeccion($rspa, "text");
            $hab_desc = antisqlinyeccion("S", "text");
            $modifica_precio = antisqlinyeccion($mod_precio, "text");
            $maneja_lote = antisqlinyeccion($maneja_lote, "text");
            $regimen_turismo = antisqlinyeccion($reg_turismo, "text");
            $maneja_cod_alt = antisqlinyeccion($es_alternativo, "text");
            $idcod_alt = antisqlinyeccion($idinsumo_origen, "int");





            if (trim($descripcion) == '' || trim($descripcion) == 'NULL') {
                $valido = "N";
                $errores .= " - El campo descripcion no puede estar vacio.<br />";
            }

            if ($usa_concepto == 'S') {
                if ($idconcepto == 0) {
                    $valido = "N";
                    $errores .= " - El campo concepto no puede ser cero o nulo.<br />";
                }
            }

            if (intval($idmedida) == 0) {
                $valido = "N";
                $errores .= " - El campo medida no puede ser cero o nulo.<br />";
            }
            /*if(floatval($_POST['costo']) <= 0){
                $valido="N";
                $errores.=" - El campo costo no puede ser cero o negativo.<br />";
            }*/

            if ($idconcepto_despacho != $idconcepto && $idconcepto_flete != $idconcepto) {
                if (trim($idtipoiva_compra) == '') {
                    $valido = "N";
                    $errores .= " - El campo iva compra no puede estar vacio.<br />";
                }
            } else {
                $idtipoiva_compra = 0;
                $tipoiva_compra = 0;
            }

            if (intval($idgrupoinsu) == 0) {
                $valido = "N";
                $errores .= " - El campo grupo stock no puede estar vacio.<br />";
            }

            if ($hab_compra == '') {
                $valido = "N";
                $errores .= " - El campo habilita compra debe completarse.<br />";
            }

            if ($hab_invent == '') {
                $valido = "N";
                $errores .= " - El campo habilita inventario debe completarse.<br />";
            }
            if ($hab_compra > 0) {
                if (intval($solo_conversion) == 0) {
                    if ($hab_invent == 0) {
                        $valido = "N";
                        $errores .= " - Cuando se habilita compra tambien debe habilitarse inventario.<br />";
                    }
                }
            }
            // validar que no existe un producto con el mismo nombre
            $consulta = "
				select * from productos where descripcion = $descripcion and borrado = 'N' limit 1
				";
            $rsexpr = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // si existe producto
            $idprod_serial = $rsexpr->fields['idprod_serial'] ? $rsexpr->fields['idprod_serial'] : "";
            if ($idprod_serial > 0) {
                $errores .= "- Ya existe un producto con el mismo nombre.<br />";
                $valido = 'N';
            }
            // validar que no hay insumo con el mismo nombre
            $buscar = "Select * from insumos_lista where UPPER(descripcion)=UPPER($descripcion) and estado = $estado limit 1";
            $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            if ($rsb->fields['idinsumo'] > 0) {
                $errores .= "- Ya existe un articulo con el mismo nombre.<br />";
                $valido = 'N';
            }


            /////////////////

            if ($idconcepto_despacho != $idconcepto && $idconcepto_flete != $idconcepto) {
                // iva compra
                $consulta = "
					select * 
					from tipo_iva
					where 
					idtipoiva = $idtipoiva_compra
					";
                $rsiva = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $tipoiva_compra = $rsiva->fields['iva_porc'];
                $idtipoiva_compra = $rsiva->fields['idtipoiva'];

                $contabilidad = intval($rsco->fields['contabilidad']);
                if ($contabilidad == 1) {
                    if (trim($hab_compra) == '1') {
                        if (intval($cuentacont) == 0) {
                            $valido = "N";
                            $errores .= "- Debe indicar la cuenta contable para compras del producto, cuando el producto esta habilitado para compras.<br />";
                        }
                    }
                }
            }
            /////////////////////
            if (floatval($rendimiento_porc) <= 0) {
                $valido = "N";
                $errores .= " - El campo rendimiento no puede ser cero o negativo.<br />";
            }
            if (floatval($rendimiento_porc) > 100) {
                $valido = "N";
                $errores .= " - El campo rendimiento no puede ser mayor a 100.<br />";
            }


            // si todo es correcto inserta
            if ($valido == "S") {


                $buscar = "select max(idinsumo) as mayor from insumos_lista";
                $rsmayor = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $idinsumo = intval($rsmayor->fields['mayor']) + 1;

                $consulta = "
					insert into insumos_lista
					(idinsumo, idproducto, descripcion, idconcepto, idcategoria, idsubcate, idmarcaprod, idmedida,idmedida2,idmedida3, cant_medida2, cant_medida3, produccion, costo,  idtipoiva, tipoiva, mueve_stock, paquete, cant_paquete, estado, idempresa, idgrupoinsu, ajuste, fechahora, registrado_por_usu, hab_compra, hab_invent, idproveedor, aplica_regalia, solo_conversion, respeta_precio_sugerido, idprodexterno, restaurado_por, restaurado_el,
					idplancuentadet, idcentroprod, idagrupacionprod, rendimiento_porc,cant_caja_edi,largo,ancho,alto,peso,cod_fob,rs,rspa,hab_desc,modifica_precio,maneja_lote,regimen_turismo,maneja_cod_alt,idcod_alt, idpais, dias_utiles, dias_stock,bar_code, idsubcate_sec
					)
					values
					($idinsumo, $idproducto, $descripcion, $idconcepto, $idcategoria, $idsubcate, $idmarcaprod, $idmedida, $idmedida2, $idmedida3, $cant_medida2, $cant_medida3, $produccion, $costo, $idtipoiva_compra, $tipoiva_compra, $mueve_stock, $paquete, $cant_paquete, $estado, $idempresa, $idgrupoinsu, $ajuste, $fechahora, $registrado_por_usu, $hab_compra, $hab_invent, $idproveedor, $aplica_regalia, $solo_conversion, $respeta_precio_sugerido, $idprodexterno, $restaurado_por, $restaurado_el,
					$cuentacontable, $centroprod, $idagrupacionprod, $rendimiento_porc,$cant_caja_edi,$largo,$ancho,$alto,$peso,$cod_fob,$rs,$rspa,$hab_desc,$modifica_precio,$maneja_lote,$regimen_turismo,$maneja_cod_alt,$idcod_alt, $idpais, $dias_utiles, $dias_stock, $bar_code, $idsubcate_sec
					)
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                $insertar = "Insert into ingredientes (idinsumo,estado,idempresa) values ($idinsumo,1,$idempresa)";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

                $codarticulocontable = intval($cuentacont);
                if ($codarticulocontable > 0) {
                    //traemos los datos del plan de cuentas activo
                    $buscar = "Select * from cn_plancuentas_detalles where cuenta=$codarticulocontable and estado <> 6";
                    $rsvv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                    $idplan = intval($rsvv->fields['idplan']);
                    $idsercuenta = intval($rsvv->fields['idserieun']);

                    $insertar = "Insert into cn_articulos_vinculados
						(idinsumo,idplancuenta,idsercuenta,vinculado_el,vinculado_por) 
						values 
						($idinsumo,$idplan,$idsercuenta,current_timestamp,$idusu)";
                    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


                }



                $parametros_array = [
                    "idinsumo" => $idinsumo,
                    "costo" => $costo,
                    "idtipoiva" => $idtipoiva_compra,
                    "bar_code" => $bar_code,
                    "idcategoria" => $idcategoria,
                    "idsubcate" => $idsubcate,
                    "idsubcate_sec" => $idsubcate_sec,
                    "margen_seguridad_categoria" => 0,
                    "margen_seguridad_sub_categorias" => 0,
                    "margen_seguridad_sub_categorias_secundaria" => 0,
                    "idmedida" => $idmedida,
                    "descripcion" => $descripcion,
                    "fcompra" => "NULL",
                    "fecompra" => "NULL",
                    "cantidad" => "",
                    "idmarca" => $idmarca,
                    "p1" => 0,
                    "iva_porc" => $tipoiva_compra
                ];
                // array("valido"=> $valido,"errores"=> $errores);
                $res = insumo_convertir_producto($parametros_array);
                if ($res["valido"] == "N") {
                    $errores2 = $res["errores"];
                    // echo $errores;
                    // $array_fallados[] = array("fila" => $fila, "nombre" => $nombre_articulo, "error" => $errores2);
                }

            } else {
                $array_fallados[] = ["fila" => $fila, "nombre" => $nombre_articulo, "estado" => $estado, "error" => $errores];

            }


        }


        ///////////////////////////////////////////////////////////////////////////////////////////////////
    }

}

if (isset($_GET['status']) && ($_GET['status'] != '')) {
    $status = substr(htmlentities($_GET['status']), 0, 200);
}
if ($_GET['cargado'] == 'n') {
    $errores = htmlentities($_GET['status']);
}



?><!DOCTYPE html>
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
                    <h2>Importar articulos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p>
<a href="insumos_lista.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
</p>
<hr />

<hr />
<?php if (count($array_fallados) > 0) { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
<strong>Errores:</strong><br />
	<div>
		<ul class="list-group">
			<?php if (count($array_fallados) > 0) { ?>
				<li class="list-group-item list-group-item-danger">
					<strong>Total Errores:</strong> <?php  echo count($array_fallados); ?><br>
					<?php foreach ($array_fallados as $key => $value) {?>
						<strong>Fila:</strong> <?php  echo $value["fila"]; ?>&nbsp;&nbsp;&nbsp; <strong>Articulo:</strong> <?php  echo $value["nombre"]; ?> <strong><br>Error:</strong> <?php  echo $value["error"]; ?><br>
					<?php } ?>
				</li>
			<?php } ?>
		</ul>
	</div>
</div>
<?php } ?>


<form action="" method="post" enctype="multipart/form-data" name="form1" id="form1">



<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Archivo xlsx *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
    <input type="file" name="archivo" id="archivo"  class="form-control" accept=".xlsx"  />
	</div>
</div>


<div class="clearfix"></div>
<br />





<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-upload"></span> Cargar Archivo</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='codigo_origen.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

        <input type="hidden" name="MM_upload" id="MM_upload" value="form1" /></td>
 </form>

<p>&nbsp;</p>
<hr />
<h2>Instrucciones:</h2><br />
<br />
<strong>Paso 1:</strong><br />
<a class="btn btn-sm btn-default" type="button" 
href='../gfx/formatos_arch/articulos.xlsx' download><span class="fa fa-download" ></span> Descargar Formato XLSX Ejemplo</a>
<br />
<br />
<strong>Paso 2:</strong><br />
Cargar aqui el archivo excel con las nuevas cantidades.
<br />
 </form>

<p>&nbsp;</p>
<br />




                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
