<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("../proveedores/preferencias_proveedores.php");
require_once("./preferencias_compras_ordenes.php");

$el = intval($_POST['el']);
if ($el > 0) {
    $delete = "Delete from compras_ordenes_detalles where ocseria=$el";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));
}

$idprod = antisqlinyeccion(intval($_POST['idp']), 'text');
$tipoUnidad = intval($_POST['tipoUnidad']); //1 unida 2 bulto  3 pallet
$idmedida = intval($_POST['idmedida']); //1 unida 2 bulto  3 pallet
$ocn = intval($_POST['ocn']);
$cantidad = floatval($_POST['cant']);
$cantidad_ref = floatval($_POST['cant_ref']);
$precio = floatval($_POST['precio']);
$descuento_valor = floatval($_POST['descuento']);
$precio_total = null;
$precio_total = $cantidad_ref * $precio;
// Fue aplicado la funcion round con 3 decimales
//$precio = round($precio_total /$cantidad,3);
$precio = $precio_total / $cantidad;
$precio_total = $cantidad * $precio;

if ($descuento == "S") {

    $precio_total = $precio_total - $descuento_valor;
    $precio = ($precio_total) / $cantidad;
}
if ($ocn > 0 && $_POST['idp'] > 0) {

    //Vemos para reservar el numero
    $buscar = "
	Select *, cotizaciones.cotizacion 
	from compras_ordenes 
	left JOIN cotizaciones 
	on compras_ordenes.idcot = cotizaciones.idcot  
	where ocnum=$ocn";
    $rsocnum = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    if (intval($rsocnum->fields['ocnum']) == 0) {
        $insertar = "Insert into compras_ordenes (ocnum,generado_por) values ($ocn,$idusu)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
    }

    //Almacenamos el producto
    $buscar = "Select * from compras_ordenes_detalles where idprod=$idprod and ocnum=$ocn";
    $rstf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    if ($rstf->fields['idprod'] == '') {

        // TODO: VERIFICAR SI YA HAY UNA COMPRA DE ESTA ORDEN  add_prodtmp_new.php
        $buscar = "Select descripcion from  insumos_lista where idinsumo=$idprod";
        $rsdes = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $des = str_replace("'", "", trim($rsdes->fields['descripcion']));

        $insertar = "Insert into compras_ordenes_detalles 
		(ocnum,idprod,cantidad,cant_transito,precio_compra,descripcion,precio_compra_total,idmedida,descuento)
		 values ($ocn,$idprod,$cantidad,$cantidad,$precio,'$des',$precio_total,$idmedida,$descuento_valor)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

        //actualiza la cabecera con el monto
        if ($proveedores_importacion == "S") {

            $cotizacion_referencia = floatval($rsocnum->fields['cotizacion']);
            $costo_referencia = floatval($rsocnum->fields['costo_ref']);

            if ($cotizacion_referencia > 0) {

                $precio_total = ($precio_total * $cotizacion_referencia) + $costo_referencia;
                $buscar = "UPDATE compras_ordenes SET costo_ref = $precio_total WHERE ocnum=$ocn";
                $rsocnum = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            }
        }

        //fin actualizacion
        echo "ok";
        exit;
    } else {

        //Ya existe, reemplazamos solo cantidad
        //$update="Update compras_ordenes_detalles set cantidad=$cantidad,precio_compra=$precio where ocnum=$ocn and idprod=$idprod";
        //$conexion->Execute($update) or die(errorpg($conexion,$update));
        echo "yaexiste";
        exit;
    }
}
