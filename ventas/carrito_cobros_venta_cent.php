<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");



// BORRAR
if ($_POST['accion'] == 'del') {

    // validaciones basicas
    $valido = "S";
    $errores = "";


    // recibe parametros
    $idcarritocobrosventas = antisqlinyeccion($_POST['idcarritocobrosventas'], "text");

    if ($_POST['all'] != 'S') {
        if (intval($_POST['idcarritocobrosventas']) == 0) {
            $valido = "N";
            $errores .= " - No se indico el registro a borrar.<br />";
        }
    }


    // si todo es correcto inserta
    if ($valido == "S") {
        if ($_POST['all'] != 'S') {
            // borrar carrito cobro del usuario si no es mixto
            $consulta = "
			DELETE 
			FROM carrito_cobros_ventas 
			WHERE 
			registrado_por = $idusu
			and idcarritocobrosventas = $idcarritocobrosventas
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        } else {
            // borrar carrito cobro del usuario si no es mixto
            $consulta = "
			DELETE 
			FROM carrito_cobros_ventas 
			WHERE 
			registrado_por = $idusu
			";
            //echo $consulta;
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }
        //exit;
    }

}

// VER PEDIDO
if (intval($_POST['idpedido']) > 0) {
    $idpedido = intval($_POST['idpedido']);
} else {
    echo "No se recibio el idpedido";
    exit;
}

if ($idpedido > 0) {
    // total en carrito
    $consulta = "
	select sum(subtotal) as subtotal
	from tmp_ventares 
	where 
	registrado = 'N'
	and tmp_ventares.borrado = 'N'
	and tmp_ventares.finalizado = 'S'
	and tmp_ventares.idtmpventares_cab = $idpedido
	";
    //echo $consulta;
    $rscarv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $total_carrito_prodventas = $rscarv->fields['subtotal'];

} else {
    // total en carrito
    /*$consulta="
    select sum(subtotal) as subtotal
    from tmp_ventares
    where
    registrado = 'N'
    and tmp_ventares.usuario = $idusu
    and tmp_ventares.borrado = 'N'
    and tmp_ventares.finalizado = 'N'
    and tmp_ventares.idsucursal = $idsucursal
    ";
    $rscarv = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $total_carrito_prodventas=$rscarv->fields['subtotal'];*/

}

/// AGREGAR
if ($_POST['accion'] == 'add') {

    // validaciones basicas
    $valido = "S";
    $errores = "";


    // recibe parametros
    //$idcarritocobrosventas=antisqlinyeccion($_POST['idcarritocobrosventas'],"text");
    $idformapago = antisqlinyeccion($_POST['idformapago'], "int");
    $monto_forma = antisqlinyeccion($_POST['monto_forma'], "float");
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $iddenominaciontarjeta = antisqlinyeccion($_POST['iddenominaciontarjeta'], "int");
    $banco = antisqlinyeccion($_POST['banco'], "int");
    $cheque_numero = antisqlinyeccion($_POST['cheque_numero'], "text");


    if (intval($_POST['idformapago']) == 0) {
        $valido = "N";
        $errores .= " - No completaste la Forma de Pago.<br />";
    }
    if (floatval($_POST['monto_forma']) <= 0) {
        $valido = "N";
        $errores .= " - No completaste el monto.<br />";
    }

    if ($facturador_electronico == 'S') {

        $consulta = "
		select idforma, idformapago_set 
		from formas_pago 
		where 
		estado <> 6
		and idforma = $idformapago
		";
        $rsfpagset = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idformapago_set = intval($rsfpagset->fields['idformapago_set']);
        // tarjeta debito y credito
        if ($idformapago_set == 3 or $idformapago_set == 4) {
            // conversiones
            $banco = antisqlinyeccion('', "int");
            $cheque_numero = antisqlinyeccion('', "text");
            // validaciones
            if (intval($_POST['iddenominaciontarjeta']) == 0) {
                $valido = "N";
                $errores .= " - No completaste el tipo de tarjeta.<br />";
            }
        }
        // cheque
        if ($idformapago_set == 2) {
            // conversiones
            $iddenominaciontarjeta = antisqlinyeccion('', "int");
            // validaciones
            if (intval($_POST['banco']) == 0) {
                $valido = "N";
                $errores .= " - No completaste el banco.<br />";
            }
            if (trim($_POST['cheque_numero']) == '') {
                $valido = "N";
                $errores .= " - No completaste el numero de cheque<br />";
            }
        }

    }


    // total en carrito pagos
    $consulta = "
	select sum(monto_forma) as total_monto_forma
	from carrito_cobros_ventas
	where
	registrado_por = $registrado_por
	";
    $rscarpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $total_carrito_pagventas = $rscarpag->fields['total_monto_forma'];

    if (($total_carrito_pagventas + $monto_forma) > $total_carrito_prodventas) {
        $valido = "N";
        $errores .= " - El monto a registrar supera el monto de la venta.<br />";
    }


    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		insert into carrito_cobros_ventas
		(idformapago, monto_forma, registrado_por, registrado_el,
		iddenominaciontarjeta, idbanco, cheque_numero)
		values
		($idformapago, $monto_forma, $registrado_por, $registrado_el,
		$iddenominaciontarjeta, $banco, $cheque_numero)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // genera array con los datos
        /*$arr = array(
        'valido' => $valido,
        'errores' => $errores
        );

        //print_r($arr);

        // convierte a formato json
        $respuesta=json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

        // devuelve la respuesta formateada
        echo $respuesta;*/

    }

}

$consulta = "
SELECT sum(monto_forma) as monto_total
FROM carrito_cobros_ventas 
inner join formas_pago on formas_pago.idforma = carrito_cobros_ventas.idformapago
WHERE 
registrado_por = $idusu
";
$rscarcobventot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$monto_acum_cobros_ventas = $rscarcobventot->fields['monto_total'];
if ($total_carrito_prodventas < $monto_acum_cobros_ventas) {
    // borrar carrito cobro del usuario si no es mixto
    $consulta = "
		DELETE 
		FROM carrito_cobros_ventas 
		WHERE 
		registrado_por = $idusu
		";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}


$consulta = "
SELECT * 
FROM carrito_cobros_ventas 
inner join formas_pago on formas_pago.idforma = carrito_cobros_ventas.idformapago
WHERE 
registrado_por = $idusu
order by idcarritocobrosventas desc
";
$rscarcobven = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>                    
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert" style="border:1px solid #F00; padding:2px; margin:2px;">
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">

<?php
$monto_acum_cobros_ventas = 0;
$factura_obliga = "N";
while (!$rscarcobven->EOF) {
    $monto_acum_cobros_ventas += $rscarcobven->fields['monto_forma'];
    if ($rscarcobven->fields['obliga_facturar'] == 'S') {
        $factura_obliga = "S";
    }
    ?>
    <tr>
        <td align="left"><?php echo $rscarcobven->fields['descripcion']; ?></td>
        <td align="right"><?php echo formatomoneda($rscarcobven->fields['monto_forma'], 4, 'N'); ?></td>
        <td align="center">
        <a href="javascript:void(0);" class="btn btn-sm btn-danger" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar" onMouseUp="borra_carrito_pag(<?php echo $rscarcobven->fields['idcarritocobrosventas']; ?>,<?php echo $idpedido ?>);"><span class="fa fa-trash-o"></span></a>
        </td>
    </tr>
<?php $rscarcobven->MoveNext();
} ?>
</table>
</div> 
<strong>Total Pedido: <?php echo formatomoneda($total_carrito_prodventas, 4, 'N'); ?></strong>
| <strong>Total Pagos: <?php echo formatomoneda($monto_acum_cobros_ventas, 4, 'N'); ?></strong> 
| <strong>Pendiente: <?php echo formatomoneda($total_carrito_prodventas - $monto_acum_cobros_ventas, 4, 'N'); ?></strong>
<input name="tot_carrito_cobros_venta" type="hidden" value="<?php echo $monto_acum_cobros_ventas; ?>">
<input name="obliga_facturar" id="obliga_facturar" type="hidden" value="<?php echo $factura_obliga; ?>">