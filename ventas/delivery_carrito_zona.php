<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "129";
require_once("includes/rsusuario.php");

require_once("includes/funciones_ventas.php");

$agrega_delivery_auto = $rsco->fields['agrega_delivery_auto'];

$valido = "S";
$errores = "";

if ($agrega_delivery_auto == 'S') {


    $idzonadel = intval($_POST['idzona']);
    $consulta = "
	select * 
	from zonas_delivery 
	inner join productos on productos.idprod_serial = zonas_delivery.idproducto_zona
	inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial 
	where 
	idzonadel = $idzonadel
	and productos_sucursales.idsucursal = $idsucursal
	limit 1;
	";
    //echo $consulta;exit;
    $rsprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idproducto = $rsprod->fields['idproducto_zona'];
    $precio = $rsprod->fields['precio'];
    $tipoiva = $rsprod->fields['tipoiva'];
    if (intval($idproducto) == 0) {
        $valido = "N";
        $errores = "- La zona seleccionada no tiene un producto asignado.".$saltolinea;
    }


    if ($valido == 'S') {
        // si hay articulos delivery en el carrito lo borra
        $consulta = "
		update  tmp_ventares 
		set borrado = 'S'
		where 
		idtipoproducto = 6
		and registrado = 'N'
		and tmp_ventares.usuario = $idusu
		and tmp_ventares.borrado = 'N'
		and tmp_ventares.finalizado = 'N'
		and tmp_ventares.idsucursal = $idsucursal
		and tmp_ventares.idempresa = $idempresa
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // agrega el nuevo articulo delivery al carrito
        $consulta = "
		INSERT INTO tmp_ventares
		(idproducto, idtipoproducto, cantidad, precio, fechahora, usuario, registrado, idsucursal, idempresa, receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2, subtotal, iva) 
		VALUES 
		($idproducto, 6, 1, $precio, '$ahora',$idusu, 'N', $idsucursal, $idempresa, 'N', 'N', 'N', NULL, NULL, $precio, $tipoiva)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    }

}

$arr = [
'valido' => $valido,
'errores' => $errores
];

//print_r($arr);



// convierte a formato json
$respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// devuelve la respuesta formateada
echo $respuesta;
