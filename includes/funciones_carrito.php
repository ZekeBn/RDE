<?php



function carrito_muestra($parametros_array)
{

    global $conexion;

    $estado_pedido = $parametros_array['estado_pedido']; // R: registrado C: carrito
    $idpedido = $parametros_array['idpedido']; // obligatorio cuando estado_pedido = 'R'
    $muestra_extras = $parametros_array['muestra_extras']; // si muestra en carrito, pero igual muestra debajo del producto
    if ($muestra_extras != 'S') {
        $muestra_extras = "N"; // por defecto no
    }
    $idusu = $parametros_array['idusu'];
    $idsucursal = $parametros_array['idsucursal'];
    $idpantallacocina = intval($parametros_array['idpantallacocina']);
    //$idatc=intval($parametros_array['idatc']);


    if ($estado_pedido == 'C') {
        if (intval($idusu) == 0) {
            echo "No indico el usuario";
            exit;
        }
        if (intval($idsucursal) == 0) {
            echo "No indico la sucursal";
            exit;
        }

        // carrito
        $whereadd = "
		and registrado = 'N'
		and tmp_ventares.usuario = $idusu
		and tmp_ventares.finalizado = 'N'
		and tmp_ventares.idsucursal = $idsucursal
		";
    } else { // if($estado_pedido == 'C'){
        if (intval($idpedido) == 0) {
            echo "No indico el pedido";
            exit;
        }

        // pedido
        $whereadd = " 
		and idtmpventares_cab=$idpedido
		";

        if ($idpantallacocina > 0) {
            $whereadd .= "
				and productos.idprod_serial in  (
				select idproducto 
				from producto_pantalla 
				where 
				idpantalla = $idpantallacocina 
			)
			";
        } // if($idpantallacocina > 0){

        // si esta en la tabla bak
        $consulta = "
		select  idventatmp 
		from tmp_ventares_bak 
		where 
		idtmpventares_cab = $idpedido 
		limit 1
		";
        $rspbak = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idventatmpbak = intval($rspbak->fields['idventatmp']);

    } // if($estado_pedido == 'C'){



    // si esta en tabla normal o en tabla bak
    if (intval($idventatmpbak) == 0) {

        $consulta = "
		select 
		0 as idventatmp,
		productos.idprod_serial, productos.descripcion, sum(cantidad) as total, 
		sum(subtotal)/sum(cantidad) as totalprecio, sum(subtotal) as subtotal,
		tmp_ventares.idtipoproducto, productos.idmedida,
		0 as idprod_mitad1,
		0 as idprod_mitad2,
		tipo_plato, cocinado, retirado,
		

		
		CASE WHEN
			(
				select tmp_ventares_agregado.idventatmp 
				from tmp_ventares_agregado 
				WHERE 
				tmp_ventares_agregado.idventatmp = tmp_ventares.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_agregado,
		CASE WHEN
			(
				select tmp_ventares_sacado.idventatmp 
				from tmp_ventares_sacado 
				WHERE 
				tmp_ventares_sacado.idventatmp = tmp_ventares.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_sacado,
		(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto and recetas_detalles.sacar = 'S' limit 1) as permite_sacado, 
		(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as permite_agregado,
		tmp_ventares.observacion as observacion_producto,
		(select muestra_grupo_combo from productos WHERE productos.idprod_serial = tmp_ventares.idproducto limit 1) as muestra_grupo_combo
		

		
		 from 
		tmp_ventares
		inner join productos on productos.idprod=tmp_ventares.idproducto
		where 
		tmp_ventares.borrado = 'N'
		$whereadd
		
		and tmp_ventares.borrado_mozo = 'N'
		
		and tmp_ventares.idtipoproducto not in (2,3,4,9,10,11)
		and (
			select tmp_ventares_agregado.idventatmp 
			from tmp_ventares_agregado 
			WHERE 
			tmp_ventares_agregado.idventatmp = tmp_ventares.idventatmp
			limit 1
		) is null
		and (
			select tmp_ventares_sacado.idventatmp 
			from tmp_ventares_sacado 
			WHERE 
			tmp_ventares_sacado.idventatmp = tmp_ventares.idventatmp
			limit 1
		) is null
		and tmp_ventares.observacion is null
		and desconsolida_forzar is null
		
		
		group by idprod_serial

		
		UNION ALL
		
		select 
		tmp_ventares.idventatmp as idventatmp,
		productos.idprod_serial, productos.descripcion, cantidad as total,
		precio as totalprecio, 
		subtotal as subtotal,
		tmp_ventares.idtipoproducto, productos.idmedida,
		0 as idprod_mitad1,
		0 as idprod_mitad2,
		tipo_plato, cocinado, retirado,
		
		CASE WHEN
			(
				select tmp_ventares_agregado.idventatmp 
				from tmp_ventares_agregado 
				WHERE 
				tmp_ventares_agregado.idventatmp = tmp_ventares.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_agregado,
		CASE WHEN
			(
				select tmp_ventares_sacado.idventatmp 
				from tmp_ventares_sacado 
				WHERE 
				tmp_ventares_sacado.idventatmp = tmp_ventares.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_sacado,
		(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as permite_agregado,
		(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto and recetas_detalles.sacar = 'S' limit 1) as permite_sacado, 

		tmp_ventares.observacion as observacion_producto,
		(select muestra_grupo_combo from productos WHERE productos.idprod_serial = tmp_ventares.idproducto limit 1) as muestra_grupo_combo
		
		 from 
		tmp_ventares
		inner join productos on productos.idprod=tmp_ventares.idproducto
		where 
		
		tmp_ventares.borrado = 'N'
		$whereadd
		
		and tmp_ventares.borrado_mozo = 'N'
		
		and tmp_ventares.idtipoproducto not in (2,3,4,9,10,11)
		and 
		(
			(
				select tmp_ventares_agregado.idventatmp 
				from tmp_ventares_agregado 
				WHERE 
				tmp_ventares_agregado.idventatmp = tmp_ventares.idventatmp
				limit 1
			) is not null
			or
			(
				select tmp_ventares_sacado.idventatmp 
				from tmp_ventares_sacado 
				WHERE 
				tmp_ventares_sacado.idventatmp = tmp_ventares.idventatmp
				limit 1
			) is not null
			or 
			(
				tmp_ventares.observacion is not null
			)
			or 
			(
				desconsolida_forzar = 'S'
			)
		)

		
		UNION ALL
		
		select 
		tmp_ventares.idventatmp as idventatmp,
		productos.idprod_serial, productos.descripcion, cantidad as total, precio as totalprecio, subtotal as subtotal, tmp_ventares.idtipoproducto, productos.idmedida,
		idprod_mitad1,
		idprod_mitad2,
		tipo_plato, cocinado, retirado,
		
		CASE WHEN
			(
				select tmp_ventares_agregado.idventatmp 
				from tmp_ventares_agregado 
				WHERE 
				tmp_ventares_agregado.idventatmp = tmp_ventares.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_agregado,
		CASE WHEN
			(
				select tmp_ventares_sacado.idventatmp 
				from tmp_ventares_sacado 
				WHERE 
				tmp_ventares_sacado.idventatmp = tmp_ventares.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_sacado,
		'N' as permite_agregado,
		'N' as permite_sacado,
		tmp_ventares.observacion as observacion_producto,
		(select muestra_grupo_combo from productos WHERE productos.idprod_serial = tmp_ventares.idproducto limit 1) as muestra_grupo_combo

		
		 from 
		tmp_ventares
		inner join productos on productos.idprod=tmp_ventares.idproducto
		where 
		
		tmp_ventares.borrado = 'N'
		$whereadd
		
		and tmp_ventares.borrado_mozo = 'N'
		and tmp_ventares.idtipoproducto  in (2,3,4,11)
		

		
		order by descripcion asc, total desc
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    } else { //if(intval($idventatmpbak) == 0){

        $consulta = "
		select 
		0 as idventatmp,
		productos.idprod_serial, productos.descripcion, sum(cantidad) as total, 
		sum(subtotal)/sum(cantidad) as totalprecio, sum(subtotal) as subtotal,
		tmp_ventares_bak.idtipoproducto, productos.idmedida,
		0 as idprod_mitad1,
		0 as idprod_mitad2,
		tipo_plato, cocinado, retirado,

		
		CASE WHEN
			(
				select tmp_ventares_agregado.idventatmp 
				from tmp_ventares_agregado 
				WHERE 
				tmp_ventares_agregado.idventatmp = tmp_ventares_bak.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_agregado,
		CASE WHEN
			(
				select tmp_ventares_sacado.idventatmp 
				from tmp_ventares_sacado 
				WHERE 
				tmp_ventares_sacado.idventatmp = tmp_ventares_bak.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_sacado,
		(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares_bak.idproducto and recetas_detalles.sacar = 'S' limit 1) as permite_sacado, 
		(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares_bak.idproducto limit 1) as permite_agregado,
		tmp_ventares_bak.observacion as observacion_producto,
		(select muestra_grupo_combo from productos WHERE productos.idprod_serial = tmp_ventares_bak.idproducto limit 1) as muestra_grupo_combo
		

		
		 from 
		tmp_ventares_bak
		inner join productos on productos.idprod=tmp_ventares_bak.idproducto
		where 
		tmp_ventares_bak.borrado = 'N'
		$whereadd
		
		and tmp_ventares_bak.borrado_mozo = 'N'
		
		and tmp_ventares_bak.idtipoproducto not in (2,3,4,9,10,11)
		and (
			select tmp_ventares_agregado.idventatmp 
			from tmp_ventares_agregado 
			WHERE 
			tmp_ventares_agregado.idventatmp = tmp_ventares_bak.idventatmp
			limit 1
		) is null
		and (
			select tmp_ventares_sacado.idventatmp 
			from tmp_ventares_sacado 
			WHERE 
			tmp_ventares_sacado.idventatmp = tmp_ventares_bak.idventatmp
			limit 1
		) is null
		and tmp_ventares_bak.observacion is null
		and tmp_ventares_bak.desconsolida_forzar is null
		
		
		group by idprod_serial

		
		UNION ALL
		
		select 
		tmp_ventares_bak.idventatmp as idventatmp,
		productos.idprod_serial, productos.descripcion, cantidad as total,
		precio as totalprecio, 
		subtotal as subtotal,
		tmp_ventares_bak.idtipoproducto, productos.idmedida,
		0 as idprod_mitad1,
		0 as idprod_mitad2,
		tipo_plato, cocinado, retirado,
		
		CASE WHEN
			(
				select tmp_ventares_agregado.idventatmp 
				from tmp_ventares_agregado 
				WHERE 
				tmp_ventares_agregado.idventatmp = tmp_ventares_bak.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_agregado,
		CASE WHEN
			(
				select tmp_ventares_sacado.idventatmp 
				from tmp_ventares_sacado 
				WHERE 
				tmp_ventares_sacado.idventatmp = tmp_ventares_bak.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_sacado,
		(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares_bak.idproducto limit 1) as permite_agregado,
		(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares_bak.idproducto and recetas_detalles.sacar = 'S' limit 1) as permite_sacado, 

		tmp_ventares_bak.observacion as observacion_producto,
		(select muestra_grupo_combo from productos WHERE productos.idprod_serial = tmp_ventares_bak.idproducto limit 1) as muestra_grupo_combo
		
		 from 
		tmp_ventares_bak
		inner join productos on productos.idprod=tmp_ventares_bak.idproducto
		where 
		
		tmp_ventares_bak.borrado = 'N'
		$whereadd
		
		and tmp_ventares_bak.borrado_mozo = 'N'
		
		and tmp_ventares_bak.idtipoproducto not in (2,3,4,9,10,11)
		and 
		(
			(
				select tmp_ventares_agregado.idventatmp 
				from tmp_ventares_agregado 
				WHERE 
				tmp_ventares_agregado.idventatmp = tmp_ventares_bak.idventatmp
				limit 1
			) is not null
			or
			(
				select tmp_ventares_sacado.idventatmp 
				from tmp_ventares_sacado 
				WHERE 
				tmp_ventares_sacado.idventatmp = tmp_ventares_bak.idventatmp
				limit 1
			) is not null
			or 
			(
				tmp_ventares_bak.observacion is not null
			)
			or
			(
				tmp_ventares_bak.desconsolida_forzar = 'S'
			)
		)

		
		UNION ALL
		
		select 
		tmp_ventares_bak.idventatmp as idventatmp,
		productos.idprod_serial, productos.descripcion, cantidad as total, precio as totalprecio, subtotal as subtotal, tmp_ventares_bak.idtipoproducto, productos.idmedida,
		idprod_mitad1,
		idprod_mitad2,
		tipo_plato, cocinado, retirado,
		
		CASE WHEN
			(
				select tmp_ventares_agregado.idventatmp 
				from tmp_ventares_agregado 
				WHERE 
				tmp_ventares_agregado.idventatmp = tmp_ventares_bak.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_agregado,
		CASE WHEN
			(
				select tmp_ventares_sacado.idventatmp 
				from tmp_ventares_sacado 
				WHERE 
				tmp_ventares_sacado.idventatmp = tmp_ventares_bak.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_sacado,
		'N' as permite_agregado,
		'N' as permite_sacado,
		tmp_ventares_bak.observacion as observacion_producto,
		(select muestra_grupo_combo from productos WHERE productos.idprod_serial = tmp_ventares_bak.idproducto limit 1) as muestra_grupo_combo

		
		 from 
		tmp_ventares_bak
		inner join productos on productos.idprod=tmp_ventares_bak.idproducto
		where 
		
		tmp_ventares_bak.borrado = 'N'
		$whereadd
		
		and tmp_ventares_bak.borrado_mozo = 'N'
		and tmp_ventares_bak.idtipoproducto  in (2,3,4,11)
		

		
		order by descripcion asc, total desc
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }


    //echo $consulta;exit;
    /*"agregado": {
                "1": {
    "idproducto": 330,
    "cantidad": 1
                },
                "2": {
    "idproducto": 329,
    "cantidad": 2
                }
            },
            "sacado": {
                "1": {
    "idinsumo": "313,314"
                }
            }*/
    $carrito_detalles = [];
    $i = 1;
    while (!$rs->EOF) {
        $muestra_grupo_combo = $rs->fields['muestra_grupo_combo'];

        $mostrar = "S";
        if ($muestra_extras == 'N') {
            if ($rs->fields['idtipoproducto'] == 5) {
                $mostrar = "N";
            }
        }
        if ($mostrar == "S") {

            $carrito_detalles[$i]['idventatmp'] = $rs->fields['idventatmp'];
            $carrito_detalles[$i]['idproducto'] = $rs->fields['idprod_serial'];
            $carrito_detalles[$i]['idtipoproducto'] = $rs->fields['idtipoproducto'];
            $carrito_detalles[$i]['idmedida'] = $rs->fields['idmedida'];
            $carrito_detalles[$i]['descripcion'] = trim($rs->fields['descripcion']);
            $carrito_detalles[$i]['cantidad'] = floatval($rs->fields['total']);
            $carrito_detalles[$i]['precio_unitario'] = floatval($rs->fields['totalprecio']);
            $carrito_detalles[$i]['subtotal'] = floatval($rs->fields['subtotal']);
            $carrito_detalles[$i]['tiene_agregado'] = trim($rs->fields['tiene_agregado']);
            $carrito_detalles[$i]['tiene_sacado'] = trim($rs->fields['tiene_sacado']);
            $carrito_detalles[$i]['tipo_plato'] = trim($rs->fields['tipo_plato']);
            $carrito_detalles[$i]['cocinado'] = trim($rs->fields['cocinado']);
            $carrito_detalles[$i]['retirado'] = trim($rs->fields['retirado']);

            if ($rs->fields['permite_agregado'] > 0) {
                $carrito_detalles[$i]['permite_agregado'] = 'S';
            } else {
                $carrito_detalles[$i]['permite_agregado'] = 'N';
            }
            if ($rs->fields['permite_sacado'] > 0) {
                $carrito_detalles[$i]['permite_sacado'] = 'S';
            } else {
                $carrito_detalles[$i]['permite_sacado'] = 'N';
            }

            //exepciones a agregados y sacados
            if ($rs->fields['idtipoproducto'] == 2 or $rs->fields['idtipoproducto'] == 3 or $rs->fields['idtipoproducto'] == 4) {
                $carrito_detalles[$i]['permite_agregado'] = 'S';
                $carrito_detalles[$i]['permite_sacado'] = 'S';
            }
            if ($rs->fields['idtipoproducto'] == 5 or $rs->fields['idtipoproducto'] == 6) {
                $carrito_detalles[$i]['permite_agregado'] = 'N';
                $carrito_detalles[$i]['permite_sacado'] = 'N';
            }


            $carrito_detalles[$i]['observacion'] = trim($rs->fields['observacion_producto']);


            $idventatmp = $rs->fields['idventatmp'];
            $idtipoproducto = $rs->fields['idtipoproducto'];
            $idprod_mitad1 = $rs->fields['idprod_mitad1'];
            $idprod_mitad2 = $rs->fields['idprod_mitad2'];
            $precio_adicional_acum = 0;
            // combinado viejo
            if ($idtipoproducto == 3) {
                $consulta = "
			select descripcion 
			from productos 
			where 
			(idprod_serial = $idprod_mitad1 or idprod_serial = $idprod_mitad2)
			limit 2
			";
                $rsmit = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                $ipart = 1;
                while (!$rsmit->EOF) {
                    $carrito_detalles[$i]['combinado_v'][$ipart]['descripcion'] = $rsmit->fields['descripcion'];
                    $ipart++;
                    $rsmit->MoveNext();
                }

            }
            // combinado extendido
            if ($idtipoproducto == 4) {
                $consulta = "
			select descripcion 
			from productos 
			inner join tmp_combinado_listas on tmp_combinado_listas.idproducto_partes = productos.idprod_serial
			where 
			tmp_combinado_listas.idventatmp = $idventatmp
			limit 100
			";
                $rsmit = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $ipart = 1;
                while (!$rsmit->EOF) {
                    $carrito_detalles[$i]['combinado'][$ipart]['descripcion'] = $rsmit->fields['descripcion'];
                    $ipart++;
                    $rsmit->MoveNext();
                }
            }
            // combo
            if ($idtipoproducto == 2) {
                if ($muestra_grupo_combo == 'S') {
                    $consulta = "
				select combos_listas.nombre, productos.descripcion, count(*) as total,
				max(tmp_combos_listas.idventatmp_partes) as idventatmp_partes
				from productos 
				inner join tmp_combos_listas on tmp_combos_listas.idproducto = productos.idprod_serial
				inner join combos_listas on combos_listas.idlistacombo = tmp_combos_listas.idlistacombo
				where 
				tmp_combos_listas.idventatmp = $idventatmp
				group by combos_listas.nombre, productos.descripcion
				order by combos_listas.idlistacombo asc
				limit 100
				";
                    $rsmit = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                } else {
                    $consulta = "
				select productos.descripcion, productos.idprod_serial, 
				max(tmp_combos_listas.idventatmp_partes) as idventatmp_partes, 
				count(*) as total
				from productos 
				inner join tmp_combos_listas on tmp_combos_listas.idproducto = productos.idprod_serial
				where 
				tmp_combos_listas.idventatmp = $idventatmp
				group by productos.descripcion, productos.idprod_serial
				limit 100
				";
                    $rsmit = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                }
                $ipart = 1;
                while (!$rsmit->EOF) {
                    if ($muestra_grupo_combo == 'S') {
                        $nombre_grupo = trim($rsmit->fields['nombre']).": ";
                    }
                    $carrito_detalles[$i]['combo'][$ipart]['idproducto'] = $rsmit->fields['idprod_serial'];
                    $carrito_detalles[$i]['combo'][$ipart]['descripcion'] = $nombre_grupo.$rsmit->fields['descripcion'];
                    $carrito_detalles[$i]['combo'][$ipart]['cantidad'] = $rsmit->fields['total'];
                    if ($rsmit->fields['total'] > 0) {
                        $carrito_detalles[$i]['combo'][$ipart]['idventatmp_partes'] = 0;
                    } else {
                        $carrito_detalles[$i]['combo'][$ipart]['idventatmp_partes'] = $rsmit->fields['idventatmp_partes'];
                    }
                    $ipart++;
                    $rsmit->MoveNext();
                }
            }

            // agregados
            if ($rs->fields['tiene_agregado'] == 'S') {
                $consulta = "
			select tmp_ventares_agregado.alias, sum(tmp_ventares_agregado.cantidad) as cantidad,
			tmp_ventares_agregado.precio_adicional*tmp_ventares_agregado.cantidad as precio_adicional
			from tmp_ventares_agregado
			where 
			idventatmp = $idventatmp
			group by alias
			order by alias desc
			";
                $rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $iag = 1;
                while (!$rsag->EOF) {

                    $carrito_detalles[$i]['agregados'][$iag]['alias'] = $rsag->fields['alias'];
                    $carrito_detalles[$i]['agregados'][$iag]['cantidad'] = floatval($rsag->fields['cantidad']);
                    $subtotal = floatval($rsag->fields['cantidad']) * floatval($rsag->fields['precio_adicional']);
                    $carrito_detalles[$i]['agregados'][$iag]['precio_adicional'] = $subtotal;
                    $precio_adicional_acum += floatval($subtotal);
                    $iag++;
                    $rsag->MoveNext();
                }
            }
            // sacados
            if ($rs->fields['tiene_sacado'] == 'S') {
                $consulta = "
			select tmp_ventares_sacado.alias
			from tmp_ventares_sacado
			where 
			idventatmp = $idventatmp
			order by alias desc
			";
                $rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $iag = 1;
                while (!$rsag->EOF) {
                    $carrito_detalles[$i]['sacados'][$iag]['alias'] = $rsag->fields['alias'];
                    $iag++;
                    $rsag->MoveNext();
                }
            }


            $carrito_detalles[$i]['subtotal_con_extras'] = floatval($rs->fields['subtotal']) + $precio_adicional_acum;
            $carrito_detalles[$i]['precio_unitario_con_extras'] = $carrito_detalles[$i]['subtotal_con_extras'] / $carrito_detalles[$i]['cantidad'];

            $i++;

        } // if($mostrar == "S"){

        $rs->MoveNext();
    }



    return $carrito_detalles;
}

function carrito_muestra_mesa($parametros_array)
{

    global $conexion;

    $estado_pedido = $parametros_array['estado_pedido']; // R: registrado C: carrito
    $idpedido = $parametros_array['idpedido']; // obligatorio cuando estado_pedido = 'R'
    $muestra_extras = $parametros_array['muestra_extras']; // si muestra en carrito, pero igual muestra debajo del producto
    if ($muestra_extras != 'S') {
        $muestra_extras = "N"; // por defecto no
    }
    $idusu = $parametros_array['idusu'];
    $idsucursal = $parametros_array['idsucursal'];
    $idpantallacocina = intval($parametros_array['idpantallacocina']);
    $idatc = intval($parametros_array['idatc']);


    if ($estado_pedido == 'C') {
        if (intval($idusu) == 0) {
            echo "No indico el usuario";
            exit;
        }
        if (intval($idsucursal) == 0) {
            echo "No indico la sucursal";
            exit;
        }

        // carrito
        $whereadd = "
		and registrado = 'N'
		and tmp_ventares.usuario = $idusu
		and tmp_ventares.finalizado = 'N'
		and tmp_ventares.idsucursal = $idsucursal
		";
    } else { // if($estado_pedido == 'C'){
        if (intval($idpedido) == 0 && intval($idatc) == 0) {
            echo "No indico el pedido ni el atc";
            exit;
        }

        // pedido
        /*$whereadd="
        and idtmpventares_cab=$idpedido
        ";*/

        if ($idpantallacocina > 0) {
            $whereadd .= "
				and productos.idprod_serial in  (
				select idproducto 
				from producto_pantalla 
				where 
				idpantalla = $idpantallacocina 
			)
			";
        } // if($idpantallacocina > 0){



    } // if($estado_pedido == 'C'){

    if ($idatc > 0) {
        $whereadd .= " and tmp_ventares.idtmpventares_cab in (select idtmpventares_cab from tmp_ventares_cab where idatc = $idatc and estado <> 6) ";
        $whereadd_bak .= " and tmp_ventares_bak.idtmpventares_cab in (select idtmpventares_cab from tmp_ventares_cab where idatc = $idatc and estado <> 6) ";
    }



    $consulta = "
		select 
		0 as idventatmp,
		productos.idprod_serial, productos.descripcion, sum(cantidad) as total, 
		sum(subtotal)/sum(cantidad) as totalprecio, sum(subtotal) as subtotal,
		tmp_ventares.idtipoproducto, productos.idmedida,
		0 as idprod_mitad1,
		0 as idprod_mitad2,
		tipo_plato, cocinado, retirado,
		

		
		CASE WHEN
			(
				select tmp_ventares_agregado.idventatmp 
				from tmp_ventares_agregado 
				WHERE 
				tmp_ventares_agregado.idventatmp = tmp_ventares.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_agregado,
		CASE WHEN
			(
				select tmp_ventares_sacado.idventatmp 
				from tmp_ventares_sacado 
				WHERE 
				tmp_ventares_sacado.idventatmp = tmp_ventares.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_sacado,
		(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto and recetas_detalles.sacar = 'S' limit 1) as permite_sacado, 
		(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as permite_agregado,
		tmp_ventares.observacion as observacion_producto,
		(select muestra_grupo_combo from productos WHERE productos.idprod_serial = tmp_ventares.idproducto limit 1) as muestra_grupo_combo
		

		
		 from 
		tmp_ventares
		inner join productos on productos.idprod=tmp_ventares.idproducto
		where 
		tmp_ventares.borrado = 'N'
		$whereadd
		
		and tmp_ventares.borrado_mozo = 'N'
		
		and tmp_ventares.idtipoproducto not in (2,3,4,9,10,11)
		and (
			select tmp_ventares_agregado.idventatmp 
			from tmp_ventares_agregado 
			WHERE 
			tmp_ventares_agregado.idventatmp = tmp_ventares.idventatmp
			limit 1
		) is null
		and (
			select tmp_ventares_sacado.idventatmp 
			from tmp_ventares_sacado 
			WHERE 
			tmp_ventares_sacado.idventatmp = tmp_ventares.idventatmp
			limit 1
		) is null
		and tmp_ventares.observacion is null
		and desconsolida_forzar is null
		
		
		group by idprod_serial

		
		UNION ALL
		
		select 
		tmp_ventares.idventatmp as idventatmp,
		productos.idprod_serial, productos.descripcion, cantidad as total,
		precio as totalprecio, 
		subtotal as subtotal,
		tmp_ventares.idtipoproducto, productos.idmedida,
		0 as idprod_mitad1,
		0 as idprod_mitad2,
		tipo_plato, cocinado, retirado,
		
		CASE WHEN
			(
				select tmp_ventares_agregado.idventatmp 
				from tmp_ventares_agregado 
				WHERE 
				tmp_ventares_agregado.idventatmp = tmp_ventares.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_agregado,
		CASE WHEN
			(
				select tmp_ventares_sacado.idventatmp 
				from tmp_ventares_sacado 
				WHERE 
				tmp_ventares_sacado.idventatmp = tmp_ventares.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_sacado,
		(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as permite_agregado,
		(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto and recetas_detalles.sacar = 'S' limit 1) as permite_sacado, 

		tmp_ventares.observacion as observacion_producto,
		(select muestra_grupo_combo from productos WHERE productos.idprod_serial = tmp_ventares.idproducto limit 1) as muestra_grupo_combo
		
		 from 
		tmp_ventares
		inner join productos on productos.idprod=tmp_ventares.idproducto
		where 
		
		tmp_ventares.borrado = 'N'
		$whereadd
		
		and tmp_ventares.borrado_mozo = 'N'
		
		and tmp_ventares.idtipoproducto not in (2,3,4,9,10,11)
		and 
		(
			(
				select tmp_ventares_agregado.idventatmp 
				from tmp_ventares_agregado 
				WHERE 
				tmp_ventares_agregado.idventatmp = tmp_ventares.idventatmp
				limit 1
			) is not null
			or
			(
				select tmp_ventares_sacado.idventatmp 
				from tmp_ventares_sacado 
				WHERE 
				tmp_ventares_sacado.idventatmp = tmp_ventares.idventatmp
				limit 1
			) is not null
			or 
			(
				tmp_ventares.observacion is not null
			)
			or 
			(
				desconsolida_forzar = 'S'
			)
		)

		
		UNION ALL
		
		select 
		tmp_ventares.idventatmp as idventatmp,
		productos.idprod_serial, productos.descripcion, cantidad as total, precio as totalprecio, subtotal as subtotal, tmp_ventares.idtipoproducto, productos.idmedida,
		idprod_mitad1,
		idprod_mitad2,
		tipo_plato, cocinado, retirado,
		
		CASE WHEN
			(
				select tmp_ventares_agregado.idventatmp 
				from tmp_ventares_agregado 
				WHERE 
				tmp_ventares_agregado.idventatmp = tmp_ventares.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_agregado,
		CASE WHEN
			(
				select tmp_ventares_sacado.idventatmp 
				from tmp_ventares_sacado 
				WHERE 
				tmp_ventares_sacado.idventatmp = tmp_ventares.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_sacado,
		'N' as permite_agregado,
		'N' as permite_sacado,
		tmp_ventares.observacion as observacion_producto,
		(select muestra_grupo_combo from productos WHERE productos.idprod_serial = tmp_ventares.idproducto limit 1) as muestra_grupo_combo

		
		 from 
		tmp_ventares
		inner join productos on productos.idprod=tmp_ventares.idproducto
		where 
		
		tmp_ventares.borrado = 'N'
		$whereadd
		
		and tmp_ventares.borrado_mozo = 'N'
		and tmp_ventares.idtipoproducto  in (2,3,4,11)
		






		UNION ALL










		select 
		0 as idventatmp,
		productos.idprod_serial, productos.descripcion, sum(cantidad) as total, 
		sum(subtotal)/sum(cantidad) as totalprecio, sum(subtotal) as subtotal,
		tmp_ventares_bak.idtipoproducto, productos.idmedida,
		0 as idprod_mitad1,
		0 as idprod_mitad2,
		tipo_plato, cocinado, retirado,

		
		CASE WHEN
			(
				select tmp_ventares_agregado.idventatmp 
				from tmp_ventares_agregado 
				WHERE 
				tmp_ventares_agregado.idventatmp = tmp_ventares_bak.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_agregado,
		CASE WHEN
			(
				select tmp_ventares_sacado.idventatmp 
				from tmp_ventares_sacado 
				WHERE 
				tmp_ventares_sacado.idventatmp = tmp_ventares_bak.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_sacado,
		(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares_bak.idproducto and recetas_detalles.sacar = 'S' limit 1) as permite_sacado, 
		(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares_bak.idproducto limit 1) as permite_agregado,
		tmp_ventares_bak.observacion as observacion_producto,
		(select muestra_grupo_combo from productos WHERE productos.idprod_serial = tmp_ventares_bak.idproducto limit 1) as muestra_grupo_combo
		

		
		 from 
		tmp_ventares_bak
		inner join productos on productos.idprod=tmp_ventares_bak.idproducto
		where 
		tmp_ventares_bak.borrado = 'N'
		$whereadd_bak
		
		and tmp_ventares_bak.borrado_mozo = 'N'
		
		and tmp_ventares_bak.idtipoproducto not in (2,3,4,9,10,11)
		and (
			select tmp_ventares_agregado.idventatmp 
			from tmp_ventares_agregado 
			WHERE 
			tmp_ventares_agregado.idventatmp = tmp_ventares_bak.idventatmp
			limit 1
		) is null
		and (
			select tmp_ventares_sacado.idventatmp 
			from tmp_ventares_sacado 
			WHERE 
			tmp_ventares_sacado.idventatmp = tmp_ventares_bak.idventatmp
			limit 1
		) is null
		and tmp_ventares_bak.observacion is null
		and tmp_ventares_bak.desconsolida_forzar is null
		
		
		group by idprod_serial

		
		UNION ALL
		
		select 
		tmp_ventares_bak.idventatmp as idventatmp,
		productos.idprod_serial, productos.descripcion, cantidad as total,
		precio as totalprecio, 
		subtotal as subtotal,
		tmp_ventares_bak.idtipoproducto, productos.idmedida,
		0 as idprod_mitad1,
		0 as idprod_mitad2,
		tipo_plato, cocinado, retirado,
		
		CASE WHEN
			(
				select tmp_ventares_agregado.idventatmp 
				from tmp_ventares_agregado 
				WHERE 
				tmp_ventares_agregado.idventatmp = tmp_ventares_bak.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_agregado,
		CASE WHEN
			(
				select tmp_ventares_sacado.idventatmp 
				from tmp_ventares_sacado 
				WHERE 
				tmp_ventares_sacado.idventatmp = tmp_ventares_bak.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_sacado,
		(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares_bak.idproducto limit 1) as permite_agregado,
		(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares_bak.idproducto and recetas_detalles.sacar = 'S' limit 1) as permite_sacado, 

		tmp_ventares_bak.observacion as observacion_producto,
		(select muestra_grupo_combo from productos WHERE productos.idprod_serial = tmp_ventares_bak.idproducto limit 1) as muestra_grupo_combo
		
		 from 
		tmp_ventares_bak
		inner join productos on productos.idprod=tmp_ventares_bak.idproducto
		where 
		
		tmp_ventares_bak.borrado = 'N'
		$whereadd_bak
		
		and tmp_ventares_bak.borrado_mozo = 'N'
		
		and tmp_ventares_bak.idtipoproducto not in (2,3,4,9,10,11)
		and 
		(
			(
				select tmp_ventares_agregado.idventatmp 
				from tmp_ventares_agregado 
				WHERE 
				tmp_ventares_agregado.idventatmp = tmp_ventares_bak.idventatmp
				limit 1
			) is not null
			or
			(
				select tmp_ventares_sacado.idventatmp 
				from tmp_ventares_sacado 
				WHERE 
				tmp_ventares_sacado.idventatmp = tmp_ventares_bak.idventatmp
				limit 1
			) is not null
			or 
			(
				tmp_ventares_bak.observacion is not null
			)
			or
			(
				tmp_ventares_bak.desconsolida_forzar = 'S'
			)
		)

		
		UNION ALL
		
		select 
		tmp_ventares_bak.idventatmp as idventatmp,
		productos.idprod_serial, productos.descripcion, cantidad as total, precio as totalprecio, subtotal as subtotal, tmp_ventares_bak.idtipoproducto, productos.idmedida,
		idprod_mitad1,
		idprod_mitad2,
		tipo_plato, cocinado, retirado,
		
		CASE WHEN
			(
				select tmp_ventares_agregado.idventatmp 
				from tmp_ventares_agregado 
				WHERE 
				tmp_ventares_agregado.idventatmp = tmp_ventares_bak.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_agregado,
		CASE WHEN
			(
				select tmp_ventares_sacado.idventatmp 
				from tmp_ventares_sacado 
				WHERE 
				tmp_ventares_sacado.idventatmp = tmp_ventares_bak.idventatmp
				limit 1
			) is not null
		THEN
			'S'
		ELSE
			'N'
		END as tiene_sacado,
		'N' as permite_agregado,
		'N' as permite_sacado,
		tmp_ventares_bak.observacion as observacion_producto,
		(select muestra_grupo_combo from productos WHERE productos.idprod_serial = tmp_ventares_bak.idproducto limit 1) as muestra_grupo_combo

		
		 from 
		tmp_ventares_bak
		inner join productos on productos.idprod=tmp_ventares_bak.idproducto
		where 
		
		tmp_ventares_bak.borrado = 'N'
		$whereadd_bak
		
		and tmp_ventares_bak.borrado_mozo = 'N'
		and tmp_ventares_bak.idtipoproducto  in (2,3,4,11)
		

		

		
		order by descripcion asc, total desc
		";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    $carrito_detalles = [];
    $i = 1;
    while (!$rs->EOF) {
        $muestra_grupo_combo = $rs->fields['muestra_grupo_combo'];

        $mostrar = "S";
        if ($muestra_extras == 'N') {
            if ($rs->fields['idtipoproducto'] == 5) {
                $mostrar = "N";
            }
        }
        if ($mostrar == "S") {

            $carrito_detalles[$i]['idventatmp'] = $rs->fields['idventatmp'];
            $carrito_detalles[$i]['idproducto'] = $rs->fields['idprod_serial'];
            $carrito_detalles[$i]['idtipoproducto'] = $rs->fields['idtipoproducto'];
            $carrito_detalles[$i]['idmedida'] = $rs->fields['idmedida'];
            $carrito_detalles[$i]['descripcion'] = trim($rs->fields['descripcion']);
            $carrito_detalles[$i]['cantidad'] = floatval($rs->fields['total']);
            $carrito_detalles[$i]['precio_unitario'] = floatval($rs->fields['totalprecio']);
            $carrito_detalles[$i]['subtotal'] = floatval($rs->fields['subtotal']);
            $carrito_detalles[$i]['tiene_agregado'] = trim($rs->fields['tiene_agregado']);
            $carrito_detalles[$i]['tiene_sacado'] = trim($rs->fields['tiene_sacado']);
            $carrito_detalles[$i]['tipo_plato'] = trim($rs->fields['tipo_plato']);
            $carrito_detalles[$i]['cocinado'] = trim($rs->fields['cocinado']);
            $carrito_detalles[$i]['retirado'] = trim($rs->fields['retirado']);

            if ($rs->fields['permite_agregado'] > 0) {
                $carrito_detalles[$i]['permite_agregado'] = 'S';
            } else {
                $carrito_detalles[$i]['permite_agregado'] = 'N';
            }
            if ($rs->fields['permite_sacado'] > 0) {
                $carrito_detalles[$i]['permite_sacado'] = 'S';
            } else {
                $carrito_detalles[$i]['permite_sacado'] = 'N';
            }

            //exepciones a agregados y sacados
            if ($rs->fields['idtipoproducto'] == 2 or $rs->fields['idtipoproducto'] == 3 or $rs->fields['idtipoproducto'] == 4) {
                $carrito_detalles[$i]['permite_agregado'] = 'S';
                $carrito_detalles[$i]['permite_sacado'] = 'S';
            }
            if ($rs->fields['idtipoproducto'] == 5 or $rs->fields['idtipoproducto'] == 6) {
                $carrito_detalles[$i]['permite_agregado'] = 'N';
                $carrito_detalles[$i]['permite_sacado'] = 'N';
            }


            $carrito_detalles[$i]['observacion'] = trim($rs->fields['observacion_producto']);


            $idventatmp = $rs->fields['idventatmp'];
            $idtipoproducto = $rs->fields['idtipoproducto'];
            $idprod_mitad1 = $rs->fields['idprod_mitad1'];
            $idprod_mitad2 = $rs->fields['idprod_mitad2'];
            $precio_adicional_acum = 0;
            // combinado viejo
            if ($idtipoproducto == 3) {
                $consulta = "
			select descripcion 
			from productos 
			where 
			(idprod_serial = $idprod_mitad1 or idprod_serial = $idprod_mitad2)
			limit 2
			";
                $rsmit = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                $ipart = 1;
                while (!$rsmit->EOF) {
                    $carrito_detalles[$i]['combinado_v'][$ipart]['descripcion'] = $rsmit->fields['descripcion'];
                    $ipart++;
                    $rsmit->MoveNext();
                }

            }
            // combinado extendido
            if ($idtipoproducto == 4) {
                $consulta = "
			select descripcion 
			from productos 
			inner join tmp_combinado_listas on tmp_combinado_listas.idproducto_partes = productos.idprod_serial
			where 
			tmp_combinado_listas.idventatmp = $idventatmp
			limit 100
			";
                $rsmit = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $ipart = 1;
                while (!$rsmit->EOF) {
                    $carrito_detalles[$i]['combinado'][$ipart]['descripcion'] = $rsmit->fields['descripcion'];
                    $ipart++;
                    $rsmit->MoveNext();
                }
            }
            // combo
            if ($idtipoproducto == 2) {
                if ($muestra_grupo_combo == 'S') {
                    $consulta = "
				select combos_listas.nombre, productos.descripcion, count(*) as total,
				max(tmp_combos_listas.idventatmp_partes) as idventatmp_partes
				from productos 
				inner join tmp_combos_listas on tmp_combos_listas.idproducto = productos.idprod_serial
				inner join combos_listas on combos_listas.idlistacombo = tmp_combos_listas.idlistacombo
				where 
				tmp_combos_listas.idventatmp = $idventatmp
				group by combos_listas.nombre, productos.descripcion
				order by combos_listas.idlistacombo asc
				limit 100
				";
                    $rsmit = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                } else {
                    $consulta = "
				select productos.descripcion, productos.idprod_serial, 
				max(tmp_combos_listas.idventatmp_partes) as idventatmp_partes, 
				count(*) as total
				from productos 
				inner join tmp_combos_listas on tmp_combos_listas.idproducto = productos.idprod_serial
				where 
				tmp_combos_listas.idventatmp = $idventatmp
				group by productos.descripcion, productos.idprod_serial
				limit 100
				";
                    $rsmit = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                }
                $ipart = 1;
                while (!$rsmit->EOF) {
                    if ($muestra_grupo_combo == 'S') {
                        $nombre_grupo = trim($rsmit->fields['nombre']).": ";
                    }
                    $carrito_detalles[$i]['combo'][$ipart]['idproducto'] = $rsmit->fields['idprod_serial'];
                    $carrito_detalles[$i]['combo'][$ipart]['descripcion'] = $nombre_grupo.$rsmit->fields['descripcion'];
                    $carrito_detalles[$i]['combo'][$ipart]['cantidad'] = $rsmit->fields['total'];
                    if ($rsmit->fields['total'] > 0) {
                        $carrito_detalles[$i]['combo'][$ipart]['idventatmp_partes'] = 0;
                    } else {
                        $carrito_detalles[$i]['combo'][$ipart]['idventatmp_partes'] = $rsmit->fields['idventatmp_partes'];
                    }
                    $ipart++;
                    $rsmit->MoveNext();
                }
            }

            // agregados
            if ($rs->fields['tiene_agregado'] == 'S') {
                $consulta = "
			select tmp_ventares_agregado.alias, sum(tmp_ventares_agregado.cantidad) as cantidad,
			tmp_ventares_agregado.precio_adicional*tmp_ventares_agregado.cantidad as precio_adicional
			from tmp_ventares_agregado
			where 
			idventatmp = $idventatmp
			group by alias
			order by alias desc
			";
                $rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $iag = 1;
                while (!$rsag->EOF) {

                    $carrito_detalles[$i]['agregados'][$iag]['alias'] = $rsag->fields['alias'];
                    $carrito_detalles[$i]['agregados'][$iag]['cantidad'] = floatval($rsag->fields['cantidad']);
                    $subtotal = floatval($rsag->fields['cantidad']) * floatval($rsag->fields['precio_adicional']);
                    $carrito_detalles[$i]['agregados'][$iag]['precio_adicional'] = $subtotal;
                    $precio_adicional_acum += floatval($subtotal);
                    $iag++;
                    $rsag->MoveNext();
                }
            }
            // sacados
            if ($rs->fields['tiene_sacado'] == 'S') {
                $consulta = "
			select tmp_ventares_sacado.alias
			from tmp_ventares_sacado
			where 
			idventatmp = $idventatmp
			order by alias desc
			";
                $rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $iag = 1;
                while (!$rsag->EOF) {
                    $carrito_detalles[$i]['sacados'][$iag]['alias'] = $rsag->fields['alias'];
                    $iag++;
                    $rsag->MoveNext();
                }
            }


            $carrito_detalles[$i]['subtotal_con_extras'] = floatval($rs->fields['subtotal']) + $precio_adicional_acum;
            $carrito_detalles[$i]['precio_unitario_con_extras'] = $carrito_detalles[$i]['subtotal_con_extras'] / $carrito_detalles[$i]['cantidad'];

            $i++;

        } // if($mostrar == "S"){

        $rs->MoveNext();
    }



    return $carrito_detalles;
}
