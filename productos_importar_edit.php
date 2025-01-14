<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";
require_once("includes/rsusuario.php");

require_once("includes/funciones_articulos.php");


// archivos
require_once("includes/upload.php");
require_once("includes/funcion_upload.php");
set_time_limit(120);


function limpia_csv_externo($texto)
{
    $texto = utf8_encode($texto);
    return $texto;
}



//VERIFICAMOS EL % DEL IVA SI ESTAN TODOS CORRECTOS Y SI EXISTE EN LA BASE DE DATOS
$consulta = "
SELECT estado, GROUP_CONCAT(iva_porc) as ivas
FROM tipo_iva
where estado = 1
GROUP BY estado
";
$rsivas = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$ivas_array = explode(",", $rsivas->fields['ivas']);

//VERIFICAMOS LA CATEGORIA SI LOS IDS ESTAN TODOS CORRECTOS Y SI EXISTE EN LA BASE DE DATOS
$consulta = "
SELECT estado, GROUP_CONCAT(id_categoria) as idcategoria
FROM categorias
where estado = 1
GROUP BY estado
";
$rscategoria = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcate_array = explode(",", $rscategoria->fields['idcategoria']);
//verificamos los anulados
$consulta = "
SELECT estado, GROUP_CONCAT(id_categoria) as idcategoria
FROM categorias
where (estado = 6 or estado = 0)
GROUP BY idempresa
";
$rscategoria = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcate_arrayanul = explode(",", $rscategoria->fields['idcategoria']);
///////////////////////////////////////////////////////////////////////////////////////////

//VERIFICAMOS SUBCATEGORIAS SI LOS IDS ESTAN TODOS CORRECTOS Y SI EXISTE EN LA BASE DE DATOS

$consulta = "
SELECT estado, GROUP_CONCAT(idsubcate) as idsubcate
FROM sub_categorias
where estado = 1
GROUP BY estado
";
$rssubcategoria = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idsubcate_array = explode(",", $rssubcategoria->fields['idsubcate']);

$consulta = "
SELECT estado, GROUP_CONCAT(idsubcate) as idsubcate
FROM sub_categorias
where (estado = 6 or estado = 0)
GROUP BY idempresa
";
$rssubcategoria = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idsubcate_arrayanul = explode(",", $rssubcategoria->fields['idsubcate']);
///////////////////////////////////////////////////////////////////////////////////////////
//VERIFICAMOS LOS PROVEEDORES SI LOS IDS ESTAN TODOS CORRECTOS Y SI EXISTE EN LA BASE DE DATOS
$consulta = "
SELECT estado, GROUP_CONCAT(idproveedor) as idproveedor
FROM proveedores
where estado = 1
GROUP BY estado
";
$rsidprov = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idprov_array = explode(",", $rsidprov->fields['idproveedor']);

$consulta = "
SELECT estado, GROUP_CONCAT(idproveedor) as idproveedor
FROM proveedores
where estado = 6
GROUP BY estado
";
$rsidprov = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idprov_arrayanul = explode(",", $rsidprov->fields['idproveedor']);
///////////////////////////////////////////////////////////////////////////////////////////
//VERIFICAMOS EL CENTRO DE PROUCCION SI LOS IDS ESTAN TODOS CORRECTOS Y SI EXISTE EN LA BASE DE DATOS
$consulta = "
SELECT estado, GROUP_CONCAT(idcentroprod) as idcentroprod
FROM produccion_centros
where estado = 1
GROUP BY estado
";
$rscentroprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcentroprod_array = explode(",", $rscentroprod->fields['idcentroprod']);

$consulta = "
SELECT estado, GROUP_CONCAT(idcentroprod) as idcentroprod
FROM produccion_centros
where estado = 6
GROUP BY estado
";
$rscentroprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcentroprod_arrayanul = explode(",", $rscentroprod->fields['idcentroprod']);
///////////////////////////////////////////////////////////////////////////////////////////
//VERIFICAMOS EL GRUPO DE STOCK SI LOS IDS ESTAN TODOS CORRECTOS Y SI EXISTE EN LA BASE DE DATOS
$consulta = "
SELECT estado, GROUP_CONCAT(idgrupoinsu) as idgrupostock
FROM grupo_insumos
where estado = 1
GROUP BY estado
";
$rsidgrupoinsu = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idgrupoinsu_array = explode(",", $rsidgrupoinsu->fields['idgrupostock']);

$consulta = "
SELECT estado, GROUP_CONCAT(idgrupoinsu) as idgrupostock
FROM grupo_insumos
where estado = 6
GROUP BY estado
";
$rsidgrupoinsu = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idgrupoinsu_arrayanul = explode(",", $rsidgrupoinsu->fields['idgrupostock']);
///////////////////////////////////////////////////////////////////////////////////////////
//VERIFICAMOS EL CONCEPTO SI LOS IDS ESTAN TODOS CORRECTOS Y SI EXISTE EN LA BASE DE DATOS
$consulta = "
SELECT estado, GROUP_CONCAT(idconcepto) as idconcepto
FROM cn_conceptos
where estado = 1
GROUP BY estado
";
$rsidconcepto = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto_array = explode(",", $rsidconcepto->fields['idconcepto']);

$consulta = "
SELECT estado, GROUP_CONCAT(idconcepto) as idconcepto
FROM cn_conceptos
where estado = 6
GROUP BY estado
";
$rsidconcepto = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto_arrayanul = explode(",", $rsidconcepto->fields['idconcepto']);
///////////////////////////////////////////////////////////////////////////////////////////

//VERIFICAMOS EL CONCEPTO SI LOS IDS ESTAN TODOS CORRECTOS Y SI EXISTE EN LA BASE DE DATOS
$consulta = "
SELECT estado, GROUP_CONCAT(idsucu) as idsucursal
FROM sucursales
where estado = 1
GROUP BY estado
";
$rsidsucursal = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idsucursal_array = explode(",", $rsidsucursal->fields['idsucursal']);

$consulta = "
SELECT estado, GROUP_CONCAT(idsucu) as idsucursal
FROM sucursales
where estado = 6
GROUP BY estado
";
$rsidsucursal = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idsucursal_arrayanul = explode(",", $rsidsucursal->fields['idsucursal']);
///////////////////////////////////////////////////////////////////////////////////////////

//VERIFICAMOS LA AGRUPACION DE PROD. SI LOS IDS ESTAN TODOS CORRECTOS Y SI EXISTE EN LA BASE DE DATOS
$consulta = "
SELECT estado, GROUP_CONCAT(idagrupacionprod) as idagrupacionprod
FROM produccion_agrupacion
where estado = 1
GROUP BY estado
";
$rsagrupacion = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idagrupacion_array = explode(",", $rsagrupacion->fields['idagrupacionprod']);


$consulta = "
SELECT estado, GROUP_CONCAT(idagrupacionprod) as idagrupacionprod
FROM produccion_agrupacion
where estado = 6
GROUP BY estado
";
$rsagrupacion = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idagrupacion_arrayanul = explode(",", $rsagrupacion->fields['idagrupacionprod']);
///////////////////////////////////////////////////////////////////////////////////////////
//VERIFICAMOS LAS MEDIDAS Y EL ID DE MEDIDAS SI LOS IDS ESTAN TODOS CORRECTOS Y SI EXISTE EN LA BASE DE DATOS
$consulta = "
SELECT medidas.estado, GROUP_CONCAT(medidas.id_medida) as idmedida, GROUP_CONCAT(medidas.nombre) as medidas 
FROM medidas 
where 
estado = 1 
GROUP BY estado
";
$rsmed = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$medidas_array = explode(",", $rsmed->fields['medidas']);
$idmedidas_array = explode(",", $rsmed->fields['idmedida']);

$consulta = "
SELECT medidas.estado, GROUP_CONCAT(medidas.id_medida) as idmedida, GROUP_CONCAT(medidas.nombre) as medidas 
FROM medidas 
where 
estado = 6
GROUP BY estado
";
$rsmed = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$medidas_arrayanul = explode(",", $rsmed->fields['medidas']);
$idmedidas_arrayanul = explode(",", $rsmed->fields['idmedida']);
///////////////////////////////////////////////////////////////////////////////////////////
//VERIFICAMOS LAS IMPRESORAS SI LOS IDS ESTAN CORRECTOS EN LA BASE DE DATOS
$consulta = "
SELECT borrado, GROUP_CONCAT(idimpresoratk) as idimpresoratk,idsucursal
FROM impresoratk
where borrado = 'N' and tipo_impresora ='COC' 
GROUP BY borrado
";
$rsimpresoras = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idimpresora_array = explode(",", $rsimpresoras->fields['idimpresoratk']);


$consulta = "
SELECT borrado, GROUP_CONCAT(idimpresoratk) as idimpresoratk,idsucursal
FROM impresoratk
where borrado = 'S' and tipo_impresora ='COC' 
GROUP BY borrado
";
$rsimpresorasanul = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idimpresoraanul_array = explode(",", $rsimpresorasanul->fields['idimpresoratk']);
///////////////////////////////////////////////////////////////////////////////////////////


$status = "";
$idsucu = "0";
$msg = urlencode("Archivo Cargado Exitosamente!");
if (isset($_POST["MM_upload"]) && ($_POST["MM_upload"] == "form1")) {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots

    $directorio = "gfx";

    $fupload = new Upload();
    $fupload->setPath($directorio);
    $fupload->setMinSize(1);
    $fupload->setMaxSize(10000);
    $exten = ['csv'];
    $extension = strtolower(substr($_FILES['archivo']['name'], strrpos($_FILES['archivo']['name'], '.') + 1));

    $tiempo = date("YmdHis");
    $nombrearchivo = 'prod_'.$tiempo.'.'.$extension;
    $nombrearchivo2 = 'prod_'.$tiempo;

    $ip_real = htmlentities(ip_real());

    if ($extension == 'csv') {
        $fupload->setFile("archivo", $nombrearchivo2, 'S');
        $fupload->isImage(false);
        // IMAGEN
    } else {
        //$fupload->setFile("archivo",$nombrearchivo2,'N',$extension);
        //$fupload->isImage(true);
    }
    //$fupload->isImage(true);
    $fupload->save();

    $cargado = $fupload->isupload;
    $status = $fupload->message;

    // si se cargo
    if ($cargado) {


        $archivo_csv = file_get_contents($directorio.'/'.$nombrearchivo);
        $array_res = csv_to_array($archivo_csv, ";");
        //print_r($array_res);exit;

        // borra el archivo
        if (file_exists($directorio.'/'.$nombrearchivo)) {
            if (trim($nombrearchivo) != '') {
                unlink($directorio.'/'.$nombrearchivo);
            }
        }


        $i = 1;
        //DESCRIPCION;CATEGORIA;SUBCATEGORIA;IDMEDIDA;COSTO UNITARIO;IVA COMPRA;GRUPO STOCK;HABILITA COMPRA;HABILITA INVENTARIO
        // recorre el archio y valida
        foreach ($array_res as $fila) {
            // la cabecera se salta
            if ($i > 1) {
                //codigo_articulo;codigo_producto;codigo_barras;codigo_pesable;
                //codigo_externo;articulo_nombre;descripcion;idmedida;medida;
                //idcategoria;categoria;idsubcate;subcategoria;id_grupo_stock;
                //grupo_stock;idproveedor;proveedores;idconcepto;concepto;
                //habilita_compra;habilita_inventario;acepta_devolucion;
                //solo_conversion;iva;idcentroprod;centro_produccion;
                //idagrupacionprod;agrupacion_prod;cuenta_contable_compra_cod_interno;
                //cuenta_contable_compra_nro;cuenta_contable_compra_descripcion;
                //redimiento;arti_para_venta;precio_venta;precio_min;precio_max;idsucursal;sucursal;precio_sucursal;activo_sucursal;
                $codigo_articulo = trim($fila[1]);
                $codigo_producto = trim($fila[2]);
                $codigo_barras = trim($fila[3]);
                $codigo_barras = ltrim($codigo_barras, "'");
                $codigo_pesable = trim($fila[4]);
                $codigo_externo = trim($fila[5]);
                $articulo_nombre = trim($fila[6]);
                $descripcion = trim($fila[7]);
                $idmedida = trim($fila[8]);
                $medida = trim($fila[9]);
                $idcategoria = trim($fila[10]);
                $categoria = trim($fila[11]);
                $idsubcate = trim($fila[12]);
                $subcategoria = trim($fila[13]);
                $id_grupo_stock = trim($fila[14]);
                $grupo_stock = trim($fila[15]);
                $idproveedor = trim($fila[16]);
                $proveedores = trim($fila[17]);
                $idconcepto = trim($fila[18]);
                $concepto = trim($fila[19]);
                $habilita_compra = trim($fila[20]);
                $habilita_inventario = trim($fila[21]);
                $acepta_devolucion = trim($fila[22]);
                $solo_conversion = trim($fila[23]);
                $iva = trim($fila[24]);
                $idcentroprod = trim($fila[25]);
                $centro_produccion = trim($fila[26]);
                $idagrupacionprod = trim($fila[27]);
                $agrupacion_prod = trim($fila[28]);
                $cuenta_contable_compra_cod_interno = trim($fila[29]);
                $cuenta_contable_compra_nro = trim($fila[30]);
                $cuenta_contable_compra_descripcion = trim($fila[31]);
                $redimiento = trim($fila[32]);
                $arti_para_venta = trim($fila[33]);
                $precio_venta = trim($fila[34]);
                $precio_min = trim($fila[35]);
                $precio_max = trim($fila[36]);
                $idsucursal = trim($fila[37]);
                $sucursal = trim($fila[38]);
                $precio_sucursal = trim($fila[39]);
                $activo_sucursal = trim($fila[40]);
                $codigo_impresora = trim($fila[41]);
                $nombre_impresora = trim($fila[42]);
                $web_muestra = trim($fila[43]);
                $idmarca = trim($fila[44]);
                $marca = trim($fila[45]);
                if ($codigo_articulo == '' || intval($codigo_articulo) <= 0) {
                    $valido = "N";
                    $errores .= "- El campo codigo_articulo no puede ser cero o estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($codigo_producto == '' || intval($codigo_producto) <= 0) {
                    $valido = "N";
                    $errores .= "- El campo codigo_producto no puede ser cero o estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($articulo_nombre == '') {
                    $valido = "N";
                    $errores .= "- El campo articulo_nombre no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($idmedida == '') {
                    $valido = "N";
                    $errores .= "- El campo idmedida no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($medida == '') {
                    $valido = "N";
                    $errores .= "- El campo medida no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($idcategoria == '') {
                    $valido = "N";
                    $errores .= "- El campo idcategoria no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }

                if ($categoria == '') {
                    $valido = "N";
                    $errores .= "- El campo categoria no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($subcategoria == '') {
                    $valido = "N";
                    $errores .= "- El campo categoria no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($redimiento == '') {
                    $valido = "N";
                    $errores .= "- El campo redimiento no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($idsucursal == '') {
                    $valido = "N";
                    $errores .= "- El campo idsucursal no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($habilita_compra == '') {
                    $valido = "N";
                    $errores .= "- El campo habilita_compra no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($habilita_inventario == '') {
                    $valido = "N";
                    $errores .= "- El campo habilita_inventario no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($arti_para_venta == '') {
                    $valido = "N";
                    $errores .= "- El campo arti_para_venta no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($acepta_devolucion == '') {
                    $valido = "N";
                    $errores .= "- El campo acepta_devolucion no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($activo_sucursal == '') {
                    $valido = "N";
                    $errores .= "- El campo activo_sucursal no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if (intval($redimiento) == 0) {
                    $valido = "N";
                    $errores .= "- El campo redimiento no puede ser cero o nulo. en la fila: [".$i.'] '.$saltolinea;
                }

                if ($precio_venta == 'SI' || $precio_venta == 'NO') {

                } else {
                    $valido = "N";
                    $errores .= "- El campo precio abierto no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($web_muestra == 'SI' || $web_muestra == 'NO') {

                } else {
                    $valido = "N";
                    $errores .= "- El campo web_muestra no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }

                // medida si existe en la bd
                if (!in_array($medida, $medidas_array)) {
                    $medida_txt = htmlentities($medida);
                    if (in_array($medida, $medidas_arrayanul)) {
                        $valido = "N";
                        $errores .= "- El campo medida [$medida_txt] se encuentra Anulado! - fila: [".$i.'] '.$saltolinea;
                    } else {
                        $valido = "N";
                        $errores .= "- El campo medida [$medida_txt] no corresponde a ningun valor aceptado en la fila: [".$i.'] '.$saltolinea;
                    }

                }
                // medida si existe en la bd
                if (!in_array($idmedida, $idmedidas_array)) {
                    $idmedida_txt = htmlentities($idmedida);
                    if (in_array($idmedida, $idmedidas_arrayanul)) {
                        $valido = "N";
                        $errores .= "- El campo idmedida [$idmedida_txt] se encuentra Anulado! - fila: [".$i.'] '.$saltolinea;
                    } else {
                        $valido = "N";
                        $errores .= "- El campo idmedida [$idmedida_txt] no corresponde a ningun valor aceptado en la fila: [".$i.'] '.$saltolinea;
                    }

                }

                // iva si existe en la bd
                if (!in_array($iva, $ivas_array)) {
                    $iva_venta_txt = htmlentities($iva);
                    $valido = "N";
                    $errores .= "- El campo valor del campo IVA [$iva_venta_txt] no corresponde a ningun valor aceptado en la fila: [".$i.']'.$saltolinea;
                }

                ///Veriricamos si existe el id de categoria
                if (!in_array($idcategoria, $idcate_array)) {
                    $idcate_txt = htmlentities($idcategoria);
                    if (in_array($idcategoria, $idcate_arrayanul)) {
                        $valido = "N";
                        $errores .= "- El campo valor del campo id_categoria [$idcate_txt] se encuentra Anulado! - fila: [".$i.']'.$saltolinea;
                    } else {
                        $valido = "N";
                        $errores .= "- El campo valor del campo id_categoria [$idcate_txt] no corresponde a ningun valor aceptado en la fila: [".$i.']'.$saltolinea;
                    }

                }

                ///Veriricamos si existe el id de subcategoria
                if (!in_array($idsubcate, $idsubcate_array)) {
                    $idsubcate_txt = htmlentities($idsubcate);
                    if (in_array($idsubcate, $idsubcate_arrayanul)) {
                        $valido = "N";
                        $errores .= "- El campo valor del campo id_subcategoria [$idsubcate_txt] se encuentra Anulado! - fila: [".$i.']'.$saltolinea;
                    } else {
                        $valido = "N";
                        $errores .= "- El campo valor del campo id_subcategoria [$idsubcate_txt] no corresponde a ningun valor aceptado en la fila: [".$i.']'.$saltolinea;
                    }

                }

                ///Veriricamos si existe el id de Proveedores
                if ($idproveedor > 0) {
                    if (!in_array($idproveedor, $idprov_array)) {
                        $idprov_txt = htmlentities($idproveedor);
                        if (in_array($idproveedor, $idprov_arrayanul)) {
                            $valido = "N";
                            $errores .= "- El campo valor del campo idproveedor [$idprov_txt] se encuentra Anulado! - fila: [".$i.']'.$saltolinea;
                        } else {
                            $valido = "N";
                            $errores .= "- El campo valor del campo idproveedor [$idprov_txt] no corresponde a ningun valor aceptado en la fila: [".$i.']'.$saltolinea;
                        }

                    }
                }
                ///Veriricamos si existe el id de grupo insumo
                if ($id_grupo_stock > 0) {
                    if (!in_array($id_grupo_stock, $idgrupoinsu_array)) {
                        $idgrupoinsu_txt = htmlentities($id_grupo_stock);
                        if (in_array($id_grupo_stock, $idgrupoinsu_arrayanul)) {
                            $valido = "N";
                            $errores .= "- El campo valor del campo id_grupo_stock [$idgrupoinsu_txt] se encuentra Anulado! - fila: [".$i.']'.$saltolinea;
                        } else {
                            $valido = "N";
                            $errores .= "- El campo valor del campo id_grupo_stock [$idgrupoinsu_txt] no corresponde a ningun valor aceptado en la fila: [".$i.']'.$saltolinea;
                        }


                    }
                }
                ///Veriricamos si existe el id de centro de Produccion
                if ($idcentroprod > 0) {
                    if (!in_array($idcentroprod, $idcentroprod_array)) {
                        $idcentroprod_txt = htmlentities($idcentroprod);
                        if (in_array($idcentroprod, $idcentroprod_arrayanul)) {
                            $valido = "N";
                            $errores .= "- El campo valor del campo idcentroprod [$idcentroprod_txt] se encuentra Anulado! - fila: [".$i.']'.$saltolinea;
                        } else {
                            $valido = "N";
                            $errores .= "- El campo valor del campo idcentroprod [$idcentroprod_txt] no corresponde a ningun valor aceptado en la fila: [".$i.']'.$saltolinea;
                        }


                    }
                }

                ///Veriricamos si existe el id de Concepto
                if ($idconcepto > 0) {
                    if (!in_array($idconcepto, $idconcepto_array)) {
                        $idconcepto_txt = htmlentities($idconcepto);
                        if (in_array($idconcepto, $idconcepto_arrayanul)) {
                            $valido = "N";
                            $errores .= "- El campo valor del campo idconcepto [$idconcepto_txt] se encuentra Anulado! - fila: [".$i.']'.$saltolinea;
                        } else {
                            $valido = "N";
                            $errores .= "- El campo valor del campo idconcepto [$idconcepto_txt] no corresponde a ningun valor aceptado en la fila: [".$i.']'.$saltolinea;
                        }
                    }
                }


                ///Veriricamos si existe el id de Concepto
                if ($idagrupacionprod > 0) {
                    if (!in_array($idagrupacionprod, $idagrupacion_array)) {
                        $idagrupacion_txt = htmlentities($idagrupacionprod);
                        if (in_array($idagrupacionprod, $idagrupacion_arrayanul)) {
                            $valido = "N";
                            $errores .= "- El campo valor del campo idagrupacion produccion [$idagrupacion_txt] se encuentra Anulado! en la fila: [".$i.']'.$saltolinea;
                        } else {
                            $valido = "N";
                            $errores .= "- El campo valor del campo idagrupacion produccion [$idagrupacion_txt] no corresponde a ningun valor aceptado en la fila: [".$i.']'.$saltolinea;
                        }


                    }
                }
                ///Veriricamos si existe el id de sucursal

                if (!in_array($idsucursal, $idsucursal_array)) {
                    $idsucursal_txt = htmlentities($idsucursal);
                    if (in_array($idsucursal, $idsucursal_arrayanul)) {
                        $valido = "N";
                        $errores .= "- El campo valor del campo idsucursal [$idsucursal_txt] no corresponde a ningun valor aceptado en la fila: [".$i.']'.$saltolinea;
                    } else {
                        $valido = "N";
                        $errores .= "- El campo valor del campo idsucursal [$idsucursal_txt] no corresponde a ningun valor aceptado en la fila: [".$i.']'.$saltolinea;
                    }

                }

                ///Veriricamos si existe el id de cn_plancuentas_detalles
                if ($cuenta_contable_compra_nro > 0) {
                    $consulta = "
                    SELECT estado,idserieun as idplandet
                    FROM cn_plancuentas_detalles
                    where idserieun = $cuenta_contable_compra_nro
                    ";
                    $rsplandet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    $idplandet_array = intval($rsplandet->fields['idplandet']);
                    $idplandet_txt = htmlentities($cuenta_contable_compra_nro);
                    if ($cuenta_contable_compra_nro <> $idplandet_array) {
                        if (intval($rsplandet->fields['idplandet']) == 6) {
                            $valido = "N";
                            $errores .= "- El campo valor del campo cuenta_contable_compra_nro [$idplandet_txt] se encuentra Anulado! - fila: [".$i.']'.$saltolinea;
                        } else {
                            $valido = "N";
                            $errores .= "- El campo valor del campo cuenta_contable_compra_nro [$idplandet_txt] no corresponde a ningun valor aceptado en la fila: [".$i.']'.$saltolinea;
                        }


                    }

                }
                ///Veriricamos si existe el id de la impresora
                if ($codigo_impresora > 0) {
                    if (!in_array($codigo_impresora, $idimpresora_array)) {
                        $idimpresora_txt = htmlentities($codigo_impresora);
                        if (in_array($codigo_impresora, $idimpresora_arrayanul)) {
                            $valido = "N";
                            $errores .= "- El campo valor del campo cod_impresora [$idimpresora_txt] se encuentra Anulado! - fila: [".$i.']'.$saltolinea;
                        } else {
                            $valido = "N";
                            $errores .= "- El campo valor del campo cod_impresora [$idimpresora_txt] no corresponde a ningun valor aceptado en la fila: [".$i.']'.$saltolinea;
                        }
                    } else {
                        //Verificamos si 3l codigo corresponde a la misma sucursal
                        $consulta = "
                SELECT a.idsucursal,b.nombre as sucursal from impresoratk a inner join sucursales b on b.idsucu = a.idsucursal
                where idimpresoratk = $codigo_impresora";
                        $rsimpresoraverisucu = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                        $idimpresorasucu = $rsimpresoraverisucu->fields['idsucursal'];
                        $nombreimpresorasucu = $rsimpresoraverisucu->fields['sucursal'];
                        if ($idsucursal <> $idimpresorasucu) {
                            $idimpresora_txt = htmlentities($codigo_impresora);
                            $valido = "N";
                            $errores .= "- El campo valor del campo cod_impresora [$idimpresora_txt] no pertenece a la misma Sucural del producto[".$sucursal.']'.", impresora[".$nombreimpresorasucu.']'." - fila: [".$i.']'.$saltolinea;
                        }
                    }

                }
                ////verificamos que el codigo del producto corresponda al del Insumo
                $consulta = "
                SELECT b.idproducto from productos a left join insumos_lista b on b.idproducto = a.idprod where b.idproducto  = $codigo_producto";
                $reveriproducto = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idproductoveri = $reveriproducto->fields['idproducto'];
                if ($codigo_producto <> $idproductoveri) {
                    $codigo_producto_txt = htmlentities($codigo_producto);
                    $valido = "N";
                    $errores .= "- El campo valor del campo cod_producto [$codigo_producto_txt] no pertenece al codigo de Insumo[".$codigo_articulo.']'." - fila: [".$i.']'.$saltolinea;
                }
                //verificamos que el codigo del insumo corresponda al del Producto
                if ($codigo_articulo > 0) {
                    $consulta = "
                SELECT b.idinsumo from productos a left join insumos_lista b on b.idproducto = a.idprod where b.idinsumo  = $codigo_articulo";
                    $reveriinsumo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    $idinsumoveri = $reveriinsumo->fields['idinsumo'];
                    if ($codigo_articulo <> $idinsumoveri) {
                        $codigo_insumo_txt = htmlentities($codigo_articulo);
                        $valido = "N";
                        $errores .= "- El campo valor del campo cod_articulo [$codigo_insumo_txt] no pertenece al codigo de Producto[".$codigo_producto.']'." - fila: [".$i.']'.$saltolinea;
                    }


                }

            } // if($i > 1){
            $i++;

        }
        // reset del array
        reset($array_res);

        //echo $errores;
        //exit;
        // si todo es valido inserta
        if ($valido == 'S') {

            // crea cabecera de importacion
            $consulta = "
            INSERT INTO productos_import_cab_edit
            (registrado_por,registrado_el) 
            VALUES 
            ($idusu,'$ahora')
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // busca el id creado
            $consulta = "
            select idproductoimpcab from productos_import_cab_edit where registrado_por = $idusu order by registrado_el desc limit 1 
            ";
            $rsprpodcab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idproductoimpcab = $rsprpodcab->fields['idproductoimpcab'];
            // recorre e inserta los detalles
            $i = 1;
            foreach ($array_res as $fila) {
                // la cabecera se salta
                if ($i > 1) {
                    // reemplazar popr el de arriba
                    $codigo_articulo = antisqlinyeccion(limpia_csv_externo(intval($fila[1])), "text");
                    $codigo_producto = antisqlinyeccion(limpia_csv_externo(intval($fila[2])), "text");
                    $codigo_barras = antisqlinyeccion(ltrim(limpia_csv_externo(trim($fila[3])), "'"), "text");
                    $codigo_pesable = antisqlinyeccion(limpia_csv_externo(trim($intval[4])), "text");
                    $codigo_externo = antisqlinyeccion(limpia_csv_externo(intval($fila[5])), "textbox");
                    $articulo_nombre = antisqlinyeccion(limpia_csv_externo(trim($fila[6])), "text");
                    $descripcion = antisqlinyeccion(limpia_csv_externo(trim($fila[7])), "text");
                    $idmedida = antisqlinyeccion(limpia_csv_externo(intval($fila[8])), "text");
                    $medida = antisqlinyeccion(limpia_csv_externo(trim($fila[9])), "text");
                    $idcategoria = antisqlinyeccion(limpia_csv_externo(intval($fila[10])), "text");
                    $categoria = antisqlinyeccion(limpia_csv_externo(trim($fila[11])), "text");
                    $idsubcate = antisqlinyeccion(limpia_csv_externo(intval($fila[12])), "text");
                    $subcategoria = antisqlinyeccion(limpia_csv_externo(trim($fila[13])), "text");
                    $id_grupo_stock = antisqlinyeccion(limpia_csv_externo(intval($fila[14])), "text");
                    $grupo_stock = antisqlinyeccion(limpia_csv_externo(trim($fila[15])), "text");
                    $idproveedor = antisqlinyeccion(limpia_csv_externo(intval($fila[16])), "text");
                    $proveedores = antisqlinyeccion(limpia_csv_externo(trim($fila[17])), "text");
                    $idconcepto = antisqlinyeccion(limpia_csv_externo(intval($fila[18])), "text");
                    $concepto = antisqlinyeccion(limpia_csv_externo(trim($fila[19])), "text");
                    $habilita_compra = antisqlinyeccion(limpia_csv_externo(trim($fila[20])), "text");
                    $habilita_inventario = antisqlinyeccion(limpia_csv_externo(trim($fila[21])), "text");
                    $acepta_devolucion = antisqlinyeccion(limpia_csv_externo(trim($fila[22])), "text");
                    $solo_conversion = antisqlinyeccion(limpia_csv_externo(trim($fila[23])), "text");
                    $iva = antisqlinyeccion(limpia_csv_externo(intval($fila[24])), "text");
                    $idcentroprod = antisqlinyeccion(limpia_csv_externo(intval($fila[25])), "text");
                    $centro_produccion = antisqlinyeccion(limpia_csv_externo(trim($fila[26])), "text");
                    $idagrupacionprod = antisqlinyeccion(limpia_csv_externo(trim($fila[27])), "text");
                    $agrupacion_prod = antisqlinyeccion(limpia_csv_externo(trim($fila[28])), "text");
                    $cuenta_contable_compra_cod_interno = antisqlinyeccion(limpia_csv_externo(intval($fila[29])), "text");
                    $cuenta_contable_compra_nro = antisqlinyeccion(limpia_csv_externo(intval($fila[30])), "text");
                    $cuenta_contable_compra_descripcion = antisqlinyeccion(limpia_csv_externo(trim($fila[31])), "text");
                    $redimiento = antisqlinyeccion(limpia_csv_externo(intval($fila[32])), "text");
                    $arti_para_venta = antisqlinyeccion(limpia_csv_externo(trim($fila[33])), "text");
                    $precio_venta = antisqlinyeccion(limpia_csv_externo(trim($fila[34])), "text");
                    $precio_min = antisqlinyeccion(limpia_csv_externo(intval($fila[35])), "text");
                    $precio_max = antisqlinyeccion(limpia_csv_externo(intval($fila[36])), "text");
                    $idsucursal = antisqlinyeccion(limpia_csv_externo(intval($fila[37])), "text");
                    $sucursal = antisqlinyeccion(limpia_csv_externo(trim($fila[38])), "text");
                    $precio_sucursal = antisqlinyeccion(limpia_csv_externo(intval($fila[39])), "text");
                    $activo_sucursal = antisqlinyeccion(limpia_csv_externo(trim($fila[40])), "text");
                    $codigo_impresora = antisqlinyeccion(limpia_csv_externo(intval($fila[41])), "text");
                    $nombre_impresora = antisqlinyeccion(limpia_csv_externo(trim($fila[42])), "text");
                    $web_muestra = antisqlinyeccion(limpia_csv_externo(trim($fila[43])), "text");
                    $idmarca = antisqlinyeccion(limpia_csv_externo(trim($fila[44])), "int");
                    $marca = antisqlinyeccion(limpia_csv_externo(trim($fila[45])), "text");
                    // busca el codigo anterior de la impresora para poder comparar
                    //con el del Excel y solo actualizar los datos que se modificaron
                    $consulta = "
                    select a.idimpresora
                    from producto_impresora a
                    inner join impresoratk b on b.idimpresoratk = a.idimpresora
                    where 
                    idproducto = $codigo_producto and idsucursal = $idsucursal
                    limit 1
                    ";
                    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    $cod_impresora_old = intval($rs->fields['idimpresora']);

                    // corregir
                    $consulta = "
                    insert into productos_import_edit
                    (
                    idproductoimpcab,codigo_articulo,codigo_producto,codigo_barras,codigo_pesable,
                    codigo_externo,articulo_nombre,descripcion,idmedida,medida,
                    idcategoria,categoria,idsubcate,subcategoria,id_grupo_stock,
                    grupo_stock,idproveedor,proveedores,idconcepto,concepto,
                    hab_compra,hab_inventario,acepta_devolucion,
                    solo_conversion,iva,idcentroprod,centro_produccion,
                    idagrupacionprod,agrupacion_prod,idplan,idplancuentadet,
                    plancuenta_descrip,redimiento,arti_para_venta,precio_abierto,
                    precio_min,precio_max,idsucursal,sucursal,precio_sucursal,
                    activo_sucursal,cod_impresora,nombre_impresora,cod_impresora_old,web_muestra,
                    idmarca,marca
                    )
                    values
                    (
                        $idproductoimpcab,$codigo_articulo,$codigo_producto,$codigo_barras,$codigo_pesable,
                        $codigo_externo,$articulo_nombre,$descripcion,$idmedida,$medida,
                        $idcategoria,$categoria,$idsubcate,$subcategoria,$id_grupo_stock,
                        $grupo_stock,$idproveedor,$proveedores,$idconcepto,$concepto,
                        $habilita_compra,$habilita_inventario,$acepta_devolucion,
                        $solo_conversion,$iva,$idcentroprod,$centro_produccion,
                        $idagrupacionprod,$agrupacion_prod,$cuenta_contable_compra_cod_interno,
                        $cuenta_contable_compra_nro,$cuenta_contable_compra_descripcion,
                        $redimiento,$arti_para_venta,$precio_venta,$precio_min,$precio_max,
                        $idsucursal,$sucursal,$precio_sucursal,$activo_sucursal,
                        $codigo_impresora,$nombre_impresora,$cod_impresora_old,$web_muestra,
                        $idmarca,$marca

                    )
                    ";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


                } // if($i > 1){
                $i++;
            }


            // redireccionar
            header("location: productos_importar_control_edit.php?id=$idproductoimpcab");
            exit;

        }
    } else {
        header("location: productos_importar_edit.php?cargado=n&status=".$status);
        exit;
    }


}


// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());


if (isset($_GET['status']) && ($_GET['status'] != '')) {
    $status = substr(htmlentities($_GET['status']), 0, 200);
}
if ($_GET['cargado'] == 'n') {
    $errores = htmlentities($_GET['status']);
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once("includes/head_gen.php"); ?>
</head>

<body class="nav-md">
    <div class="container body">
    <div class="main_container">
        <?php require_once("includes/menu_gen.php"); ?>
        <!-- top navigation -->
    <?php require_once("includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->
        <!-- page content -->
        <div class="right_col" role="main">
        <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
            <?php require_once("includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                <div class="x_title">
                    <h2>Importar Productos P/Modificación</h2>
                    <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                    </li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
<p>
<a href="gest_listado_productos.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
</p>
<hr />
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">

<strong>Errores:</strong><br /><?php echo nl2br($errores); ?>
</div>
<?php } ?>



<form action="" method="post" enctype="multipart/form-data" name="form1" id="form1">



<div class="form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Archivo CSV *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="file" name="archivo" id="archivo"  class="form-control" accept=".csv"  />
    </div>
</div>


<div class="clearfix"></div>
<br />





<div class="clearfix"></div>
<br />

<div class="form-group">
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
    <button type="submit" class="btn btn-success" ><span class="fa fa-upload"></span> Cargar Archivo</button>
    
    <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_listado_productos.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>
<input type="hidden" name="MM_update" value="form1" />
<input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">

        <input type="hidden" name="MM_upload" id="MM_upload" value="form1" /></td>
</form>

<p>&nbsp;</p>
<hr />
<h2>Instrucciones:</h2><br />
<br />
<strong>Paso 1:</strong><br />


<div class="col-md-6 col-sm-6 form-group">
<strong>Seleccionar Sucursal *:</strong><br />

    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
$acciones = 'onchange="document.location.href=\'productos_importar_edit.php?idsucu=\'+this.value"';
// consulta
$consulta = "
SELECT idsucu, nombre
FROM sucursales
where
estado = 1
order by nombre asc
";

// valor seleccionado
if (isset($_GET['idsucu'])) {
    $value_selected = htmlentities($_GET['idsucu']);
    $idsucu = $value_selected;
} else {
    $value_selected = 0;
}
// parametros
$parametros_array = [
    'nombre_campo' => 'idsucu',
    'id_campo' => 'idsucu',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idsucu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '0',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" '.$acciones.' ',
    'autosel_1registro' => 'S'

];
// construye campo
echo campo_select($consulta, $parametros_array);

?>    </div>
</div>


<strong>Paso 2:</strong><br />.
<button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='formato_csv_modificar_masiva_cate.php?idsucu=<?php echo intval($_GET['idsucu']) ?>'"><span class="fa fa-download"></span> Descargar Productos con Formato Extendido CSV</button>
<br />
<br />
<strong>Paso 2:</strong><br />
Cargar aqui el archivo excel con los productos p/Actualizar
<br />
    <br />                
    <strong>Observacion:</strong><br />
Este modulo sirve solamente para Actualizar los productos, no para crearlos o borrarlos.<br />
<br />

<strong>Codigos y Descripciones de las Clasificaciones:</strong><br />
<hr />
<button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='formato_csv_modificar_datos.php?id=1'"><span class="fa fa-download"></span> Descargar Sucursales</button>
<button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='formato_csv_modificar_datos.php?id=2'"><span class="fa fa-download"></span> Descargar Categorias</button>
<button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='formato_csv_modificar_datos.php?id=3'"><span class="fa fa-download"></span> Descargar Medidas</button>
<button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='formato_csv_modificar_datos.php?id=4'"><span class="fa fa-download"></span> Descargar Sub Categorias</button>
<button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='formato_csv_modificar_datos.php?id=5'"><span class="fa fa-download"></span> Descargar Grupo Stock</button>
<button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='formato_csv_modificar_datos.php?id=6'"><span class="fa fa-download"></span> Descargar Proveedores</button>
<button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='formato_csv_modificar_datos.php?id=7'"><span class="fa fa-download"></span> Descargar Conceptos</button>
<button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='formato_csv_modificar_datos.php?id=8'"><span class="fa fa-download"></span> Descargar Centros de Produccion</button>
<button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='formato_csv_modificar_datos.php?id=9'"><span class="fa fa-download"></span> Descargar Agrupacion de Producciones</button>
<button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='formato_csv_modificar_datos.php?id=10'"><span class="fa fa-download"></span> Descargar Detalles Plan de Cuentas</button>
<button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='formato_csv_modificar_datos.php?id=11&idsucu=<?php echo intval($_GET['idsucu']) ?>'"><span class="fa fa-download"></span>Descargar Impresoras</button>
<button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='formato_csv_modificar_datos.php?id=12'"><span class="fa fa-download"></span>Descargar Marcas</button>


<hr />

<strong>Campos NO Editables:</strong><br />
- Codigo_articulo    (No modificar/mover/borrar o podria editar/borrar otro producto sin posibilidad de reversion)<br />
- Codigo_producto (No modificar/mover/borrar o podria editar/borrar otro producto sin posibilidad de reversion)<br />
- Medida (Editar idmedida en su reemplazo)<br />
- Categoria (Editar idcategoria en su reemplazo)<br />
- Subcategoria (Editar idsubcate en su reemplazo)<br />
- Grupo_stock (Editar id_grupo_stock en su reemplazo)<br />
- Proveedores (Editar idproveedor en su reemplazo)<br />
- Concepto (Editar idconcepto en su reemplazo)<br />
- Agrupacion_prod    (Editar idagrupacionprod en su reemplazo)<br />
- Centro_produccion (Editar idcentroprod en su reemplazo)    <br />
- Cuenta_contable_compra_nro     (Editar cuenta_contable_compra_cod_interno en su reemplazo)<br />
- Cuenta_contable_compra_descripcion (Editar cuenta_contable_compra_cod_interno en su reemplazo)<br />
- Sucursal (Editar idsucursal en su reemplazo)        <br />
- Nombre_impresora (Editar cod_impresora en su reemplazo)    <br />    

<p>&nbsp;</p>
<hr />
<strong>Ultimos 20 Cargados:</strong>
<?php


$consulta = "
select *,
(select usuario from usuarios where productos_import_cab_edit.registrado_por = usuarios.idusu) as registrado_por_txt,
(select usuario from usuarios where productos_import_cab_edit.finalizado_por = usuarios.idusu) as finalizado_por_txt,
CASE 
        WHEN estado = 1 THEN 'Pendiente proceso'
        WHEN estado = 3 THEN 'Procesado'
        WHEN estado = 6 THEN 'Anulado'
END AS estado_txt
from productos_import_cab_edit 
where 
 estado <> 6 
order by idproductoimpcab desc
limit 20
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>


<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Idproductoimpcab</th>
            <th align="center">Registrado por</th>
            <th align="center">Registrado el</th>
            <th align="center">Estado</th>
            <th align="center">Finalizado por</th>
            <th align="center">Finalizado el</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td>
                <?php if (intval($rs->fields['estado']) == 1) { ?>
                <div class="btn-group">
                    <a href="productos_importar_control_edit.php?id=<?php echo $rs->fields['idproductoimpcab']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
                    
                </div>
                <?php } ?>
            </td>
                <td align="center"><?php echo intval($rs->fields['idproductoimpcab']); ?></td>
                <td align="center"><?php echo antixss($rs->fields['registrado_por_txt']); ?></td>
                <td align="center"><?php if ($rs->fields['registrado_el'] != "") {
                    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
                }  ?></td>
                <td align="center"><?php echo antixss($rs->fields['estado_txt']); ?></td>
                <td align="center"><?php echo antixss($rs->fields['finalizado_por_txt']); ?></td>
                <td align="center"><?php if ($rs->fields['finalizado_el'] != "") {
                    echo date("d/m/Y H:i:s", strtotime($rs->fields['finalizado_el']));
                }  ?></td>
        </tr>
<?php

$rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>

    </table>
</div>
<br />


            


<div class="clearfix"></div>
<br /><br />
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
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
    </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
</body>
</html>
