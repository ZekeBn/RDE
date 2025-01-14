<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "232";
$dirsup = 'S';
require_once("../includes/rsusuario.php");

require_once("../includes/funciones_iva.php");

//print_r($_POST);exit;

// validaciones basicas
// var monto_articulo = $("#idprod_"+idproducto).val();
// 	var iddeposito = $("#iddeposito_"+idproducto).val();
// 	var direccionurl='nota_credito_cuerpo_fac_add.php';
// 	var parametros = {
// 	  "idventa"           : idventa,
// 	  "idproducto"        : idproducto,
// 	  "iddeposito"        : iddeposito,
// 	  "monto_articulo"    : monto_articulo,
// 	  "clase"             : clase,
// 	  "idnotacred"        : <?php echo intval($idnotacred);
// 	};


$idorden_retiro = intval($_POST['idorden_retiro']);

$consulta = "SELECT ( t1.pventa) as pventa, t1.cantidad, t1.idinsumo, t1.idventa, t1.idproducto as idprod, t1.insumo as producto 
from (SELECT 
	devolucion_det.cantidad, devolucion_det.idproducto, insumos_lista.idinsumo, devolucion.idventa,
	(
		select 
		insumos_lista.descripcion 
		from 
		insumos_lista 
		where 
		insumos_lista.idproducto = devolucion_det.idproducto
	) as insumo,
    (
        SELECT ventas_detalles.pventa 
        from ventas_detalles 
        WHERE ventas_detalles.idprod = devolucion_det.idproducto 
        and ventas_detalles.idventa = devolucion.idventa limit 1
    ) as pventa
	FROM 
		devolucion_det 
		INNER JOIN devolucion on devolucion.iddevolucion = devolucion_det.iddevolucion
		INNER JOIN retiros_ordenes on retiros_ordenes.iddevolucion = devolucion_det.iddevolucion
		INNER JOIN insumos_lista on insumos_lista.idproducto = devolucion_det.idproducto
	WHERE 
		retiros_ordenes.idorden_retiro = $idorden_retiro) as t1";
$rs_productos_devolucion = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito = intval($_POST['iddeposito']);

// recibe parametros
$idnotacred = intval($_POST['idnotacred']);
$clase = intval($_POST['clase']); // 1 articulo 2 monto
$factura = antisqlinyeccion('', "text");
$concepto = antisqlinyeccion($_POST['concepto'], "text");
$tipoitem = antisqlinyeccion(1, "int");
$monto = floatval($_POST['monto']);
$precio = antisqlinyeccion($monto, "float");
$subtotal = antisqlinyeccion($monto, "float");
$idcompra = antisqlinyeccion(0, "int");
$idproveedor = antisqlinyeccion(0, "int");
$registrado_por = $idusu;
$registrado_el = antisqlinyeccion($ahora, "text");
$idempresa = antisqlinyeccion(1, "int");
//$idsucursal=antisqlinyeccion($_POST['idsucursal'],"int");
//$registro=antisqlinyeccion($_POST['registro'],"text");


while (!$rs_productos_devolucion->EOF) {
    $monto_articulo = $rs_productos_devolucion->fields['pventa'];
    $cantidad = $rs_productos_devolucion->fields['cantidad'];
    $idinsumo = $rs_productos_devolucion->fields['idinsumo'];
    $idproducto = $rs_productos_devolucion->fields['idprod'];
    $descripcion = $rs_productos_devolucion->fields['producto'];
    $idventa = $rs_productos_devolucion->fields['idventa'];
    $valido = "S";
    $errores = "";

    echo $descripcion;




    if (intval($idventa) == 0) {
        $valido = "N";
        $errores .= " - El campo idventa no puede estar vacio.".$saltolinea;
    }

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
    // busca datos de la factura
    $consulta = "
	select * 
	from ventas 
	where 
	idventa = $idventa 
	and estado <> 6
	limit 1;
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcliente_venta = $rs->fields['idcliente'];
    if (intval($rs->fields['idventa']) == 0) {
        $valido = "N";
        $errores .= " - La venta indicada no existe o fue anulada.".$saltolinea;
    }
    if ($idcliente_nota != $idcliente_venta) {
        $valido = "N";
        $errores .= " - El cliente de la factura seleccionada no es el mismo que el de la nota.".$saltolinea;
    }
    $factura = trim($rs->fields['factura']);

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
        // la cantidad no  puede superar a la cantidad de la factura
        $consulta = "
		select sum(cantidad) as cantidad, sum(subtotal) as subtotal, iva, idtipoiva
		from ventas_detalles 
		where 
		idventa = $idventa 
		and idprod = $idproducto
		";
        $rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $cantidad_factura = floatval($rsv->fields['cantidad']);
        $subtotal_factura = floatval($rsv->fields['subtotal']);
        $iva_porc = $rsv->fields['iva'];
        $idtipoiva = $rsv->fields['idtipoiva'];
        if ($cantidad > $cantidad_factura) {
            $valido = "N";
            $errores .= " - La cantidad indicada supera la cantidad de la factura para este producto.".$saltolinea;
        }
        // suma la cantidad a lo que ya hay en el carrito en este momento y ve que tampoco supere
        $consulta = "
		select sum(nota_credito_cuerpo.cantidad) as cantidad 
		from nota_credito_cuerpo 
		inner join nota_credito_cabeza on nota_credito_cabeza.idnotacred = nota_credito_cuerpo.idnotacred
		where 
		nota_credito_cuerpo.idventa = $idventa 
		and nota_credito_cuerpo.codproducto = $idproducto
		and nota_credito_cabeza.estado <> 6
		";
        //echo $consulta;exit;
        $rsn = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $cantidad_nota = floatval($rsn->fields['cantidad']);
        $cantidad_producto = $cantidad_factura + $cantidad_nota;
        if ($cantidad_nota > 0) {
            if ($cantidad + $cantidad_nota > $cantidad_factura) {
                $valido = "N";
                $errores .= " - La cantidad a aplicar a este producto supera la cantidad de la factura tomando en cuenta (la cantidad ingresada + lo descontado anteiormente) para la misma factura y el mismo articulo.".$saltolinea;
            }
        }
        //echo "b"; echo $cantidad_nota; echo $cantidad_factura; echo $cantidad_producto; exit;
        // busca descripcion del producto
        $consulta = "
		select descripcion from productos where idprod_serial = $idproducto limit 1;
		";
        $rsp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $descripcion = antisqlinyeccion($rsp->fields['descripcion'], "text");
        // precio
        $precio = $subtotal_factura / $cantidad_factura;
        $subtotal = $precio * $cantidad;
        //echo $precio."<br />"; echo $cantidad."<br />"; echo $subtotal."<br />";
        //exit;
        // suma la cantidad a lo que ya hay en el carrito en este momento y ve que tampoco supere
        $consulta = "
		select *
		from nota_credito_cuerpo 
		where 
		idventa = $idventa 
		and codproducto = $idproducto
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
        if (intval($_POST['idventa']) == 0) {
            $valido = "N";
            $errores .= " - El campo idventa no se envio.".$saltolinea;
        }
        $monto = floatval($_POST['monto']);

        $consulta = "
		select monto_col, idtipoiva, iva_porc_col, gravadoml
		from ventas_detalles_impuesto
		where
		idventa = $idventa
		order by iva_porc_col desc
		limit 1
		";
        $rsvimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // conversiones

        $descripcion = $concepto;
        $iva_porc = antisqlinyeccion($rsvimp->fields['iva_porc_col'], "int");
        $idtipoiva = antisqlinyeccion($rsvimp->fields['idtipoiva'], "int");
        $cantidad = 1;
        $precio = $monto;
        $subtotal = $monto;

    } elseif ($clase == 3) { // monto articulo

        if (floatval($idproducto) == 0) {
            $valido = "N";
            $errores .= " - El campo producto no puede ser cero.".$saltolinea;
        }
        if (floatval($monto_articulo) == 0) {
            $valido = "N";
            $errores .= " - Debe indicar el monto a aplicar del articulo.".$saltolinea;
        }
        /*if(floatval($_POST['iddeposito']) == 0){
            $valido="N";
            $errores.=" - Debe indicar el deposito de retorno de las mercaderias.".$saltolinea;
        }*/
        // la cantidad no  puede superar a la cantidad de la factura
        $consulta = "
		select sum(cantidad) as cantidad, sum(subtotal) as subtotal, iva, idtipoiva
		from ventas_detalles 
		where 
		idventa = $idventa 
		and idprod = $idproducto
		";
        $rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $cantidad_factura = floatval($rsv->fields['cantidad']);
        $subtotal_factura = floatval($rsv->fields['subtotal']);
        $iva_porc = $rsv->fields['iva'];
        $idtipoiva = $rsv->fields['idtipoiva'];
        /*if($cantidad > $cantidad_factura){
            $valido="N";
            $errores.=" - La cantidad indicada supera la cantidad de la factura para este producto.".$saltolinea;
        }*/
        // suma la cantidad a lo que ya hay en el carrito en este momento y ve que tampoco supere
        $consulta = "
		select sum(subtotal) as subtotal 
		from nota_credito_cuerpo 
		where 
		idventa = $idventa 
		and codproducto = $idproducto
		";
        //echo $consulta;exit;
        $rsn = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $subtotal_nota = floatval($rsn->fields['subtotal']);
        $subtotal_producto = $subtotal_factura + $subtotal_nota;
        //if($subtotal_nota > 0){
        if ($monto_articulo + $subtotal_nota > $subtotal_factura) {
            $valido = "N";
            $errores .= " - El monto a aplicar a este producto supera la el monto de la factura tomando en cuenta (el monto ingresado + lo descontado anteriormente) para la misma factura y el mismo articulo.".$saltolinea;
        }
        //}
        //echo "b"; echo $cantidad_nota; echo $cantidad_factura; echo $cantidad_producto; exit;
        // busca descripcion del producto
        $consulta = "
		select descripcion from productos where idprod_serial = $idproducto limit 1;
		";
        $rsp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $descripcion = antisqlinyeccion($rsp->fields['descripcion'], "text");
        // precio
        //$precio=$subtotal_factura/$cantidad_factura;
        //$subtotal=$precio*$cantidad_factura;
        // suma la cantidad a lo que ya hay en el carrito en este momento y ve que tampoco supere
        $consulta = "
		select *
		from nota_credito_cuerpo 
		where 
		idventa = $idventa 
		and codproducto = $idproducto
		and idnotacred = $idnotacred
		limit 1
		";
        //echo $consulta;exit;
        $rsn = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if ($rsn->fields['idnotacred'] > 0) {
            $valido = "N";
            $errores .= " - Ya agregaste este articulo.".$saltolinea;
        }
        // $cantidad=1;
        $precio = $monto_articulo;
        $subtotal = $monto_articulo * $cantidad;
        $iddeposito = 0;


    } else {
        $valido = "N";
        $errores .= " - Forma de aplicar incorrecta.".$saltolinea;
    }







    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		insert into nota_credito_cuerpo
		(idnotacred, factura, codproducto, tipoitem, descripcion, cantidad, precio, subtotal, idcompra, idventa, idproveedor, registrado_por, registrado_el, idempresa, idsucursal,  clase, iddeposito, iva_porc, idtipoiva)
		values
		($idnotacred, '$factura', $idproducto, $tipoitem, $descripcion, $cantidad, $precio, $subtotal, $idcompra, $idventa, $idproveedor, $registrado_por, $registrado_el, $idempresa, $idsucursal, $clase, $iddeposito, $iva_porc, $idtipoiva)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		select registro as idnotacreddet
		from nota_credito_cuerpo
		where
		idnotacred = $idnotacred
		order by  registro desc 
		limit 1
		";
        $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idnotacreddet = $rsmax->fields['idnotacreddet'];

        $parametros_array = [
            'idtipoiva' => $idtipoiva,
            'monto_ivaincluido' => $subtotal
        ];
        $res_iva = calcular_iva_tipos($parametros_array);
        foreach ($res_iva as $iva_linea) {
            //print_r($iva_linea);exit;
            $gravadoml = $iva_linea['gravadoml'];
            $ivaml = $iva_linea['ivaml'];
            $exento = $iva_linea['exento'];
            $iva_porc_col = $iva_linea['iva_porc_col'];
            $monto_col = $iva_linea['monto_col'];

            //ventas_detalles_impuesto
            $consulta = "
			INSERT INTO nota_credito_cuerpo_impuesto
			(idnotacred, idnotacreddet, idproducto, idtipoiva, iva_porc_col, monto_col,
			gravadoml,  ivaml,  exento) 
			VALUES 
			($idnotacred, $idnotacreddet, $idproducto, $idtipoiva, $iva_porc_col, $monto_col,
			$gravadoml,  $ivaml,  '$exento'
			)
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        }



    }

    $rs_productos_devolucion->MoveNext();

}
// genera array con los datos
$arr = [
'valido' => $valido,
'errores' => $errores
];

print_r($arr);

// convierte a formato json
$respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// devuelve la respuesta formateada
echo $respuesta;
