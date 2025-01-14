<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "232";
$dirsup = 'S';
require_once("../includes/rsusuario.php");


// validaciones basicas
$valido = "S";
$errores = "";

$idventa = intval($_POST['idventa']);
$clase = intval($_POST['clase']); // 1 articulo 2 monto
$idnotacred = intval($_POST['idnotacred']);
$iddeposito = intval($_POST['iddeposito']);

// recibe parametros
$factura = antisqlinyeccion('', "text");
$idproducto = antisqlinyeccion($_POST['idproducto'], "text");
$concepto = antisqlinyeccion($_POST['concepto'], "text");
$tipoitem = antisqlinyeccion(1, "int");
//$descripcion=antisqlinyeccion($_POST['descripcion'],"text");
$cantidad = antisqlinyeccion($_POST['cantidad'], "float");
$monto_articulo = antisqlinyeccion($_POST['monto_articulo'], "float");
$monto = floatval($_POST['monto']);
$precio = antisqlinyeccion($monto, "float");
$subtotal = antisqlinyeccion($monto, "float");
$idcompra = antisqlinyeccion(0, "int");
$idproveedor = antisqlinyeccion(0, "int");
$registrado_por = $idusu;
$registrado_el = antisqlinyeccion($ahora, "text");
$idempresa = antisqlinyeccion(1, "int");
$precio_unitario = floatval($_POST['precio_unitario']);
//$idsucursal=antisqlinyeccion($_POST['idsucursal'],"int");
//$registro=antisqlinyeccion($_POST['registro'],"text");


// busca datos de la nota credito cabecera
$consulta = "
select *
from nota_credito_cabeza
where
idnotacred = $idnotacred
and estado = 1
limit 1
";
$rsnotacab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcliente_nota = $rsnotacab->fields['idcliente'];

if ($clase == 1) { // 1 articulo
    if (floatval($_POST['idproducto']) == 0) {
        $valido = "N";
        $errores .= " - El campo monto no puede ser cero.".$saltolinea;
    }
    if (floatval($_POST['cantidad']) == 0) {
        $valido = "N";
        $errores .= " - Debe indicar la cantidad.".$saltolinea;
    }
    if (floatval($_POST['iddeposito']) == 0) {
        $valido = "N";
        $errores .= " - Debe indicar el deposito de retorno de las mercaderias.".$saltolinea;
    }
    // suma la cantidad a lo que ya hay en el carrito en este momento y ve que tampoco supere
    $consulta = "
	select sum(nota_credito_cuerpo.cantidad) as cantidad 
	from nota_credito_cuerpo 
	inner join nota_credito_cabeza on nota_credito_cabeza.idnotacred = nota_credito_cuerpo.idnotacred
	where 
	nota_credito_cuerpo.idnotacred = $idnotacred 
	and nota_credito_cuerpo.codproducto = $idproducto
	and nota_credito_cabeza.estado <> 6
	";
    //echo $consulta;exit;
    $rsn = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cantidad_nota = floatval($rsn->fields['cantidad']);


    // busca descripcion del producto
    $consulta = "
	select descripcion, tipoiva from productos where idprod_serial = $idproducto limit 1;
	";
    $rsp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $descripcion = antisqlinyeccion($rsp->fields['descripcion'], "text");
    $iva_porc = antisqlinyeccion($rsp->fields['tipoiva'], "text");
    // precio
    $precio = $precio_unitario;
    $subtotal = $precio * $cantidad;
    //echo $precio."<br />"; echo $cantidad."<br />"; echo $subtotal."<br />";
    //exit;
    // suma la cantidad a lo que ya hay en el carrito en este momento y ve que tampoco supere
    $consulta = "
	select *
	from nota_credito_cuerpo 
	where 
	codproducto = $idproducto
	and idnotacred = $idnotacred
	limit 1
	";
    //echo $consulta;exit;
    $rsn = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsn->fields['idnotacred'] > 0) {
        $valido = "N";
        $errores .= " - Ya agregaste este articulo.".$saltolinea;
    }
} elseif ($clase == 2) { // 2 monto

    if (floatval($_POST['monto']) == 0) {
        $valido = "N";
        $errores .= " - El campo monto no puede ser cero.".$saltolinea;
    }
    if (trim($_POST['concepto']) == '') {
        $valido = "N";
        $errores .= " - El campo concepto no puede estar vacio.".$saltolinea;
    }


    // conversiones
    $idproducto = 0;
    $descripcion = $concepto;
    $iva_porc = antisqlinyeccion(10, "int");
    $cantidad = 1;

} elseif ($clase == 3) { // monto articulo

    if (floatval($_POST['idproducto']) == 0) {
        $valido = "N";
        $errores .= " - El campo producto no puede ser cero.".$saltolinea;
    }
    if (floatval($_POST['monto_articulo']) == 0) {
        $valido = "N";
        $errores .= " - Debe indicar el monto a aplicar del articulo.".$saltolinea;
    }
    /*if(floatval($_POST['iddeposito']) == 0){
        $valido="N";
        $errores.=" - Debe indicar el deposito de retorno de las mercaderias.".$saltolinea;
    }*/
    // la cantidad no  puede superar a la cantidad de la factura
    $consulta = "
	select sum(cantidad) as cantidad, sum(subtotal) as subtotal, iva
	from ventas_detalles 
	where 
	idventa = $idventa 
	and idprod = $idproducto
	";
    $rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cantidad_factura = floatval($rsv->fields['cantidad']);
    $subtotal_factura = floatval($rsv->fields['subtotal']);
    $iva_porc = $rsv->fields['iva'];
    /*if($cantidad > $cantidad_factura){
        $valido="N";
        $errores.=" - La cantidad indicada supera la cantidad de la factura para este producto.".$saltolinea;
    }*/
    // suma la cantidad a lo que ya hay en el carrito en este momento y ve que tampoco supere
    /*$consulta="
    select sum(subtotal) as subtotal
    from nota_credito_cuerpo
    where
    idventa = $idventa
    and codproducto = $idproducto
    ";
    //echo $consulta;exit;
    $rsn=$conexion->Execute($consulta) or die (errorpg($conexion,$consulta));
    $subtotal_nota=floatval($rsn->fields['subtotal']);
    $subtotal_producto=$subtotal_factura+$subtotal_nota;
    //if($subtotal_nota > 0){
        if($monto_articulo+$subtotal_nota > $subtotal_factura){
            $valido="N";
            $errores.=" - El monto a aplicar a este producto supera la el monto de la factura tomando en cuenta (el monto ingresado + lo descontado anteriormente) para la misma factura y el mismo articulo.".$saltolinea;
        }*/
    //}
    //echo "b"; echo $cantidad_nota; echo $cantidad_factura; echo $cantidad_producto; exit;
    // busca descripcion del producto
    $consulta = "
	select descripcion,tipoiva from productos where idprod_serial = $idproducto limit 1;
	";
    $rsp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $descripcion = antisqlinyeccion($rsp->fields['descripcion'], "text");
    $iva_porc = antisqlinyeccion($rsp->fields['tipoiva'], "text");
    // precio
    //$precio=$subtotal_factura/$cantidad_factura;
    //$subtotal=$precio*$cantidad_factura;
    // suma la cantidad a lo que ya hay en el carrito en este momento y ve que tampoco supere
    $consulta = "
	select *
	from nota_credito_cuerpo 
	where 
	codproducto = $idproducto
	and idnotacred = $idnotacred
	limit 1
	";
    //echo $consulta;exit;
    $rsn = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsn->fields['idnotacred'] > 0) {
        $valido = "N";
        $errores .= " - Ya agregaste este articulo.".$saltolinea;
    }
    $cantidad = 1;
    $precio = $monto_articulo;
    $subtotal = $monto_articulo;
    $iddeposito = 0;


} else {
    $valido = "N";
    $errores .= " - Forma de aplicar incorrecta.".$saltolinea;
}







// si todo es correcto inserta
if ($valido == "S") {

    $consulta = "
	insert into nota_credito_cuerpo
	(idnotacred, factura, codproducto, tipoitem, descripcion, cantidad, precio, subtotal, idcompra, idventa, idproveedor, registrado_por, registrado_el, idempresa, idsucursal,  clase, iddeposito, iva_porc)
	values
	($idnotacred, '$factura', $idproducto, $tipoitem, $descripcion, $cantidad, $precio, $subtotal, $idcompra, $idventa, $idproveedor, $registrado_por, $registrado_el, $idempresa, $idsucursal, $clase, $iddeposito, $iva_porc)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



}




// genera array con los datos
$arr = [
'valido' => $valido,
'errores' => $errores
];

//print_r($arr);

// convierte a formato json
$respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// devuelve la respuesta formateada
echo $respuesta;
