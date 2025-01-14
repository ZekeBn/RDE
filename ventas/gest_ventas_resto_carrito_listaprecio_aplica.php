<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

$idlistaprecio = intval($_GET['id']);
if ($idlistaprecio > 1) {
    $consulta = "
	select * 
	from lista_precios_venta 
	inner join lista_precios_venta_perm on lista_precios_venta_perm.idlistaprecio = lista_precios_venta.idlistaprecio
	where 
	lista_precios_venta_perm.idusuario = $idusu
	and lista_precios_venta_perm.estado = 1 
	and lista_precios_venta.estado = 1 
	and lista_precios_venta.idlistaprecio > 1
	and lista_precios_venta.idlistaprecio = $idlistaprecio
	order by lista_precios_venta.lista_precio asc
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $lista_precio = $rs->fields['lista_precio'];
    $idlistaprecio = $rs->fields['idlistaprecio'];
    $combinar = $rs->fields['combinar'];

    if ($idlistaprecio > 0) {

        $consulta = "
		update productos_listaprecios
		set
		precio = 
			COALESCE(
				(
				select 
				CASE redondeo_direccion
				WHEN 'A' THEN CEIL(((precio*(lista_precios_venta.recargo_porc/100))+precio)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
				WHEN 'B' THEN FLOOR(((precio*(lista_precios_venta.recargo_porc/100))+precio)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
				ELSE
					ROUND(((precio*(lista_precios_venta.recargo_porc/100))+precio)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
				END as redondeado
				from productos_sucursales, lista_precios_venta
				where
				productos_sucursales.idproducto = productos_listaprecios.idproducto
				and productos_sucursales.idsucursal = productos_listaprecios.idsucursal
				and lista_precios_venta.idlistaprecio = productos_listaprecios.idlistaprecio
				)
			,0),
		reg_por = $idusu,
		reg_el = '$ahora'
		where
		idlistaprecio in (select idlistaprecio from lista_precios_venta where recargo_porc <> 0)
		and idlistaprecio > 1
		and idlistaprecio = $idlistaprecio
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // si la lista no permite combinar borra el carrito
        // INICIO BORRA TODO
        if ($combinar == 'N') {
            $consulta = "
			select * 
			from tmp_ventares 
			where
			usuario = $idusu
			and finalizado = 'N'
			and registrado = 'N'
			and idsucursal = $idsucursal
			and borrado = 'N'
			;
			";
            $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



            // recorre borra
            while (!$rs->EOF) {

                $idventatmp = $rs->fields['idventatmp'];
                $idtmpventaresagregado = intval($rs->fields['idtmpventaresagregado']);

                // borra los detalles que contienen ese producto
                $consulta = "
				update tmp_ventares
				set borrado = 'S'
				where
				idventatmp = $idventatmp
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                // borra los agregados relacionados al idventatmp principal
                $consulta = "
				update tmp_ventares
				set 
				borrado = 'S'
				where
				idventatmp_princ_delagregado = $idventatmp
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                // borra los agregados de la tabla de agregados
                $consulta = "
				delete from tmp_ventares_agregado
				where
				idventatmp = $idventatmp
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                if ($idtmpventaresagregado > 0) {
                    $consulta = "
					delete from tmp_ventares_agregado
					where
					idtmpventaresagregado = $idtmpventaresagregado
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                }


                $rs->MoveNext();
            }
        } // if($combinar == 'N'){
        // FIN BORRA TODO

        // asigna la sesion
        $_SESSION['idlistaprecio'] = $idlistaprecio;

        header("location: gest_ventas_resto_caja.php");
        exit;

    }
} else {

    $idlistaprecio = intval($_SESSION['idlistaprecio']);

    $consulta = "
	select * 
	from lista_precios_venta 
	inner join lista_precios_venta_perm on lista_precios_venta_perm.idlistaprecio = lista_precios_venta.idlistaprecio
	where 
	lista_precios_venta_perm.idusuario = $idusu
	and lista_precios_venta_perm.estado = 1 
	and lista_precios_venta.estado = 1 
	and lista_precios_venta.idlistaprecio > 1
	and lista_precios_venta.idlistaprecio = $idlistaprecio
	order by lista_precios_venta.lista_precio asc
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $lista_precio = $rs->fields['lista_precio'];
    $idlistaprecio = $rs->fields['idlistaprecio'];
    $combinar = $rs->fields['combinar'];

    // INICIO BORRA TODO
    if ($combinar == 'N') {
        $consulta = "
		select * 
		from tmp_ventares 
		where
		usuario = $idusu
		and finalizado = 'N'
		and registrado = 'N'
		and idsucursal = $idsucursal
		and borrado = 'N'
		;
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



        // recorre borra
        while (!$rs->EOF) {

            $idventatmp = $rs->fields['idventatmp'];
            $idtmpventaresagregado = intval($rs->fields['idtmpventaresagregado']);

            // borra los detalles que contienen ese producto
            $consulta = "
			update tmp_ventares
			set borrado = 'S'
			where
			idventatmp = $idventatmp
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // borra los agregados relacionados al idventatmp principal
            $consulta = "
			update tmp_ventares
			set 
			borrado = 'S'
			where
			idventatmp_princ_delagregado = $idventatmp
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // borra los agregados de la tabla de agregados
            $consulta = "
			delete from tmp_ventares_agregado
			where
			idventatmp = $idventatmp
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            if ($idtmpventaresagregado > 0) {
                $consulta = "
				delete from tmp_ventares_agregado
				where
				idtmpventaresagregado = $idtmpventaresagregado
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }


            $rs->MoveNext();
        }
    } // if($combinar == 'N'){

    // destruye la sesion
    $_SESSION['idlistaprecio'] = 0;
    $_SESSION['idlistaprecio'] = null;
    unset($_SESSION['idlistaprecio']);

    header("location: gest_ventas_resto_caja.php");
    exit;
}
