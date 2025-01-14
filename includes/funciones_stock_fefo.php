<?php

function movimientos_stock($parametros_array)
{
    global $idempresa;
    global $conexion;
    global $ahora;
    global $idusu;

    $diferencia_inicial = $parametros_array['diferencia_inicial'];
    $idconteo = intval($parametros_array['idconteo']);
    $iddeposito = $parametros_array['iddeposito'];
    $idinsumo = $parametros_array['idinsumo'];
    $fecha_inicio = $parametros_array['fecha_inicio'];
    $cantidad = $parametros_array['cantidad'];
    $tipomov = $parametros_array['tipomov'];
    $sumaoresta = $parametros_array['sumaoresta'];

    $codrefer = $idconteo;
    $fecha_comprobante = $fecha_inicio;// ver fecha de conteo
    $buscar = "SELECT disponible
	from gest_depositos_stock_gral
	where 
	idproducto = $idinsumo 
	and idempresa = $idempresa 
	and estado = 1 
	and iddeposito = $iddeposito
	";
    $rsst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $cantidad_sistema = floatval($rsst->fields['disponible']);

    if ($sumaoresta == '-') {
        $cantidad_sistema_ant = floatval($cantidad_sistema + $cantidad);
    } elseif ($sumaoresta == '+') {
        $cantidad_sistema_ant = floatval($cantidad_sistema - $cantidad);
    } else {
        echo "Error! no suma ni resta.";
        exit;
    }

    $fecha_comprobante = antisqlinyeccion($fecha_comprobante, "text");
    $codrefer = antisqlinyeccion($codrefer, "int");

    $consulta = "
	INSERT INTO stock_movimientos 
	(tipomov, idinsumo, cantidad, cantidad_sistema, iddeposito, fechahora, idusu, idempresa, sumaoresta, cantidad_sistema_ant, codrefer, fecha_comprobante) 
	VALUES 
	($tipomov,$idinsumo, $cantidad, $cantidad_sistema, $iddeposito,'$ahora',$idusu, $idempresa, '$sumaoresta', $cantidad_sistema_ant, $codrefer, $fecha_comprobante)
	";
    //echo $consulta."<br />";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}
function descuenta_stock_vent_fefo($idinsumo_receta, $cantidad_receta, $iddeposito, $vencimiento = "")
{
    global $idempresa;
    global $conexion;
    global $ahora;



    $disponible_receta = floatval($cantidad_receta);
    $costo_acumulado = 0;
    // busca el nombre del insumo
    $consulta = "select descripcion, costo  from insumos_lista where idinsumo = $idinsumo_receta";
    $rsnom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $insumo_desc = str_replace("'", "", $rsnom->fields['descripcion']);
    $ult_costo = floatval($rsnom->fields['costo']);

    $consulta = "
	select forzar_ultcosto from preferencias_caja limit 1
	";
    $rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $forzar_ultcosto = $rsprefcaj->fields['forzar_ultcosto'];



    if ($forzar_ultcosto == 'S') {
        $costo_acumulado = $cantidad_receta * $ult_costo;
    } else {

        // recorre mientras haya disponible
        while ($disponible_receta > 0) {
            // busca los disponibles
            $consulta = "
			select disponible, idseriepkcos, precio_costo
			from costo_productos 
			where 
			idempresa = $idempresa 
			and ubicacion = $iddeposito
			and id_producto = $idinsumo_receta
			and disponible > 0
			order by fechacompra asc, idseriepkcos asc
			";
            $rsdisp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $total_tandas = intval($rsdisp->RecordCount());
            // si existen tandas en deposito con cantidad disponible > 0
            if ($total_tandas > 0) {
                // inicio recorre los registros de costos en deposito
                $costo_acumulado = 0;
                while (!$rsdisp->EOF) {
                    $disponible_depo = floatval($rsdisp->fields['disponible']);
                    $idseriepkcos = $rsdisp->fields['idseriepkcos'];
                    $precio_costo = $rsdisp->fields['precio_costo'];
                    $diferencia = 0;

                    // si hay disponible en esta tanda de costos
                    if ($disponible_depo > 0) {


                        // si el disponible de la tanda de costos es mayor al disponible de receta, se pone la diferencia
                        if ($disponible_depo > $disponible_receta) {
                            $diferencia = $disponible_depo - $disponible_receta;
                            $consulta = "
							UPDATE costo_productos
							SET 
							disponible = $diferencia
							WHERE 
							idempresa = $idempresa 
							and ubicacion = $iddeposito
							and id_producto = $idinsumo_receta
							and idseriepkcos = $idseriepkcos
							";
                            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                            $consulta = "
							UPDATE gest_depositos_stock
							SET 
							disponible = $diferencia
							WHERE 
							idempresa = $idempresa 
							and iddeposito = $iddeposito
							and idproducto = $idinsumo_receta
							and idseriecostos = $idseriepkcos
							";
                            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                            // costo es igual al costo de la tanda * la cantidad de receta que queda
                            $costo_acumulado += $precio_costo * $disponible_receta;

                        }
                        // si el disponible de la tanda de costos es menor o igual al disponible de receta, se cera el disponible de esa tanda de costo
                        if ($disponible_depo <= $disponible_receta) {
                            $consulta = "
							UPDATE costo_productos
							SET 
							disponible = 0
							WHERE 
							idempresa = $idempresa 
							and ubicacion = $iddeposito
							and id_producto = $idinsumo_receta
							and idseriepkcos = $idseriepkcos
							";
                            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                            $consulta = "
							UPDATE gest_depositos_stock
							SET 
							disponible = 0
							WHERE 
							idempresa = $idempresa 
							and iddeposito = $iddeposito
							and idproducto = $idinsumo_receta
							and idseriecostos = $idseriepkcos
							";
                            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                            // costo es igual al costo de la tanda * la cantidad de la tanda
                            $costo_acumulado += $precio_costo * $disponible_depo;

                        }

                        // descuenta del disponible de receta
                        $disponible_receta = $disponible_receta - $disponible_depo;


                        // si no hay disponible	en la tanda de costos es por que no hay en ninguna tanda por el filtro disponible > 0
                    } else {
                        // nunca puede entrar aca por el filtro disponible > 0
                        echo "ERROR! es imposible que suceda esto!";
                        exit;
                    }

                    // si no queda disponible en receta termina ambos while
                    if ($disponible_receta <= 0) {
                        break 2;
                    }

                    // fin recorre los registros
                    $rsdisp->MoveNext();
                }




            } //if($total_tandas > 0){
            // si no existen tandas disponibles en deposito
            if ($total_tandas <= 0) {
                if ($disponible_receta > 0) {
                    $disponible_receta_negativo = $disponible_receta * -1;
                } else {
                    $disponible_receta_negativo = $disponible_receta;
                }
                // preferencia costo
                $consulta = "
				select ult_costo_sinstock from preferencias where idempresa = $idempresa
				";
                $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $ult_costo_sinstock = $rspref->fields['ult_costo_sinstock'];
                if ($ult_costo_sinstock == 'S') {
                    $precio_costo = $ult_costo;
                } else {
                    $precio_costo = 0;
                }
                if ($precio_costo < 0) {
                    $precio_costo = $precio_costo * -1;
                }
                $costo_acumulado = $precio_costo * ($disponible_receta_negativo * -1);
                // ya que no hay nada con disponible mayor a 0 busca el descontador ficticio
                $consulta = "
				select idseriepkcos 
				from costo_productos 
				where 
				ficticio = 1 
				and id_producto = $idinsumo_receta 
				and ubicacion = $iddeposito
				and idempresa = $idempresa
				and disponible < 0
				limit 1
				";
                $rsfic = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idseriepkcos = intval($rsfic->fields['idseriepkcos']);
                // si no existe el ficticio
                if ($idseriepkcos == 0) {
                    // se inserta tanda con el disponible que sobro de la receta pero negativo
                    $consulta = "
					insert into costo_productos 
					(cantidad,precio_costo,id_producto,idempresa,registrado_el,ubicacion,disponible,idcompra,idproducido,fechacompra,ficticio)
					values
					($disponible_receta_negativo, $precio_costo, $idinsumo_receta, $idempresa, '$ahora', $iddeposito, $disponible_receta_negativo, 0, NULL, '$ahora',1)

					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    // id insertado
                    $consulta = "select max(idseriepkcos) as ultid from costo_productos where idempresa = $idempresa";
                    $rsulid = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    $ultid = $rsulid->fields['ultid'];

                    $consulta = "
					INSERT INTO gest_depositos_stock
					(idproducto,idseriecostos,fechacompra,disponible,cantidad,iddeposito,recibido_el,verificado_el,descripcion,costogs,idempresa,ficticio)
					values
					($idinsumo_receta, $ultid, '$ahora', $disponible_receta_negativo, $disponible_receta_negativo, $iddeposito,'$ahora', '$ahora', '$insumo_desc', $precio_costo, $idempresa,1)
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                } else { // if($idseriepkcos == 0){

                    // si existe el ficticio actualiza
                    $consulta = "
					update costo_productos
					set
						disponible = disponible+$disponible_receta_negativo
					where
						idseriepkcos = $idseriepkcos
						and ficticio = 1
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                    $consulta = "
					update gest_depositos_stock
					set
						disponible = disponible+$disponible_receta_negativo
					where
						idseriecostos = $idseriepkcos
						and ficticio = 1
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


                } // if($idseriepkcos == 0){
                $disponible_receta = 0;

            }// if($total_tandas <= 0){


        } //while($disponible_receta > 0){

    } // if($forzar_ultcosto == 'S'){

    // costo total de los insumos utilizados, no es el promedio
    return $costo_acumulado;
}
function descuenta_stock_prod($idinsumo_receta, $cantidad_receta, $iddeposito, $idproducido = 0)
{
    global $idempresa;
    global $conexion;
    global $ahora;
    $disponible_receta = floatval($cantidad_receta);
    $costo_acumulado = 0;
    // busca el nombre del insumo
    $consulta = "select * from insumos_lista where idinsumo = $idinsumo_receta and idempresa = $idempresa";
    $rsnom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $insumo_desc = str_replace("'", "", $rsnom->fields['descripcion']);
    $ult_costo = floatval($rsnom->fields['costo']);

    $consulta = "
	select forzar_ultcosto from preferencias_caja limit 1
	";
    $rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $forzar_ultcosto = $rsprefcaj->fields['forzar_ultcosto'];
    if ($forzar_ultcosto == 'S') {
        $costo_acumulado = $cantidad_receta * $ult_costo;
    } else {

        // recorre mientras haya disponible
        while ($disponible_receta > 0) {
            // busca los disponibles
            $consulta = "
			select * 
			from costo_productos 
			where 
			idempresa = $idempresa 
			and ubicacion = $iddeposito
			and id_producto = $idinsumo_receta
			and disponible > 0
			order by fechacompra asc, idseriepkcos asc
			";
            $rsdisp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $total_tandas = intval($rsdisp->RecordCount());

            // si existen tandas en deposito con cantidad disponible > 0
            if ($total_tandas > 0) {
                // inicio recorre los registros de costos en deposito
                $costo_acumulado = 0;
                while (!$rsdisp->EOF) {
                    $disponible_depo = floatval($rsdisp->fields['disponible']);
                    $idseriepkcos = $rsdisp->fields['idseriepkcos'];
                    $precio_costo = $rsdisp->fields['precio_costo'];
                    $diferencia = 0;

                    // si hay disponible en esta tanda de costos
                    if ($disponible_depo > 0) {


                        // si el disponible de la tanda de costos es mayor al disponible de receta, se pone la diferencia
                        if ($disponible_depo > $disponible_receta) {
                            $diferencia = $disponible_depo - $disponible_receta;
                            $consulta = "
							UPDATE costo_productos
							SET 
							disponible = $diferencia
							WHERE 
							idempresa = $idempresa 
							and ubicacion = $iddeposito
							and id_producto = $idinsumo_receta
							and idseriepkcos = $idseriepkcos
							";
                            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                            $consulta = "
							UPDATE gest_depositos_stock
							SET 
							disponible = $diferencia
							WHERE 
							idempresa = $idempresa 
							and iddeposito = $iddeposito
							and idproducto = $idinsumo_receta
							and idseriecostos = $idseriepkcos
							";
                            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                            // costo es igual al costo de la tanda * la cantidad de receta que queda
                            $costo_acumulado += $precio_costo * $disponible_receta;

                        }
                        // si el disponible de la tanda de costos es menor o igual al disponible de receta, se cera el disponible de esa tanda de costo
                        if ($disponible_depo <= $disponible_receta) {
                            $consulta = "
							UPDATE costo_productos
							SET 
							disponible = 0
							WHERE 
							idempresa = $idempresa 
							and ubicacion = $iddeposito
							and id_producto = $idinsumo_receta
							and idseriepkcos = $idseriepkcos
							";
                            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                            $consulta = "
							UPDATE gest_depositos_stock
							SET 
							disponible = 0
							WHERE 
							idempresa = $idempresa 
							and iddeposito = $iddeposito
							and idproducto = $idinsumo_receta
							and idseriecostos = $idseriepkcos
							";
                            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                            // costo es igual al costo de la tanda * la cantidad de la tanda
                            $costo_acumulado += $precio_costo * $disponible_depo;

                        }

                        // descuenta del disponible de receta
                        $disponible_receta = $disponible_receta - $disponible_depo;


                        // si no hay disponible	en la tanda de costos es por que no hay en ninguna tanda por el filtro disponible > 0
                    } else {
                        // nunca puede entrar aca por el filtro disponible > 0
                        echo "ERROR! es imposible que suceda esto!";
                        exit;
                    }

                    // si no queda disponible en receta termina ambos while
                    if ($disponible_receta <= 0) {
                        break 2;
                    }

                    // fin recorre los registros
                    $rsdisp->MoveNext();
                }




            } //if($total_tandas > 0){
            // si no existen tandas disponibles en deposito
            if ($total_tandas <= 0) {
                if ($disponible_receta > 0) {
                    $disponible_receta_negativo = $disponible_receta * -1;
                } else {
                    $disponible_receta_negativo = $disponible_receta;
                }
                // preferencia costo
                $consulta = "
				select ult_costo_sinstock from preferencias where idempresa = $idempresa
				";
                $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $ult_costo_sinstock = $rspref->fields['ult_costo_sinstock'];
                if ($ult_costo_sinstock == 'S') {
                    $precio_costo = $ult_costo;
                } else {
                    $precio_costo = 0;
                }
                if ($precio_costo < 0) {
                    $precio_costo = $precio_costo * -1;
                }
                $costo_acumulado = $precio_costo * ($disponible_receta_negativo * -1);
                // ya que no hay nada con disponible mayor a 0 busca el descontador ficticio
                $consulta = "
				select idseriepkcos 
				from costo_productos 
				where 
				ficticio = 1 
				and id_producto = $idinsumo_receta 
				and ubicacion = $iddeposito
				and idempresa = $idempresa
				and disponible < 0
				limit 1
				";
                $rsfic = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idseriepkcos = intval($rsfic->fields['idseriepkcos']);
                // si no existe el ficticio
                if ($idseriepkcos == 0) {
                    // se inserta tanda con el disponible que sobro de la receta pero negativo
                    $consulta = "
					insert into costo_productos 
					(cantidad,precio_costo,id_producto,idempresa,registrado_el,ubicacion,disponible,idcompra,idproducido,fechacompra,ficticio)
					values
					($disponible_receta_negativo, $precio_costo, $idinsumo_receta, $idempresa, '$ahora', $iddeposito, $disponible_receta_negativo, 0, $idproducido, '$ahora',1)

					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    // id insertado
                    $consulta = "select max(idseriepkcos) as ultid from costo_productos where idempresa = $idempresa";
                    $rsulid = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    $ultid = $rsulid->fields['ultid'];

                    $consulta = "
					INSERT INTO gest_depositos_stock
					(idproducto,idseriecostos,fechacompra,disponible,cantidad,iddeposito,recibido_el,verificado_el,descripcion,costogs,idempresa,ficticio)
					values
					($idinsumo_receta, $ultid, '$ahora', $disponible_receta_negativo, $disponible_receta_negativo, $iddeposito,'$ahora', '$ahora', '$insumo_desc', $precio_costo, $idempresa,1)
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                } else { // if($idseriepkcos == 0){

                    // si existe el ficticio actualiza
                    $consulta = "
					update costo_productos
					set
						disponible = disponible+$disponible_receta_negativo
					where
						idseriepkcos = $idseriepkcos
						and ficticio = 1
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                    $consulta = "
					update gest_depositos_stock
					set
						disponible = disponible+$disponible_receta_negativo
					where
						idseriecostos = $idseriepkcos
						and ficticio = 1
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


                } // if($idseriepkcos == 0){
                $disponible_receta = 0;

            } // if($total_tandas <= 0){


        } //while($disponible_receta > 0){

    } // if($forzar_ultcosto == 'S'){

    // costo total de los insumos utilizados, no es el promedio
    return $costo_acumulado;
}


function traslada_stock($idinsumo_traslado, $cantidad_traslado, $iddeposito_origen, $iddeposito_destino)
{
    global $idempresa;
    global $conexion;
    global $ahora;
    $disponible_traslado = floatval($cantidad_traslado);
    $costo_acumulado = 0;
    // busca el nombre del insumo
    $consulta = "select * from insumos_lista where idinsumo = $idinsumo_traslado and idempresa = $idempresa";
    $rsnom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $insumo_desc = str_replace("'", "", $rsnom->fields['descripcion']);
    $ult_costo = floatval($rsnom->fields['costo']);

    // recorre mientras haya disponible
    while ($disponible_traslado > 0) {
        // busca los disponibles en origen
        $consulta = "
		select * 
		from costo_productos 
		where 
		idempresa = $idempresa 
		and ubicacion = $iddeposito_origen
		and id_producto = $idinsumo_traslado
		and disponible > 0
		order by fechacompra asc, idseriepkcos asc
		";
        $rsdisp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $total_tandas = intval($rsdisp->RecordCount());

        // si existen tandas en deposito con cantidad disponible > 0
        if ($total_tandas > 0) {
            // inicio recorre los registros de costos en deposito
            $costo_acumulado = 0;
            while (!$rsdisp->EOF) {
                $disponible_depo = floatval($rsdisp->fields['disponible']);
                $idseriepkcos = $rsdisp->fields['idseriepkcos'];
                $precio_costo = $rsdisp->fields['precio_costo'];
                $idcompra = $rsdisp->fields['idcompra'];
                $fechacompra = $rsdisp->fields['fechacompra'];
                $idproveedor = intval($rsdisp->fields['idproveedor']);
                $numfactura = $rsdisp->fields['numfactura'];

                $diferencia = 0;

                // si hay disponible en esta tanda de costos
                if ($disponible_depo > 0) {


                    // si el disponible de la tanda de costos es mayor al disponible de receta, se pone la diferencia
                    if ($disponible_depo > $disponible_traslado) {
                        $diferencia = $disponible_depo - $disponible_traslado;
                        $consulta = "
						UPDATE costo_productos
						SET 
						disponible = $diferencia
						WHERE 
						idempresa = $idempresa 
						and ubicacion = $iddeposito_origen
						and id_producto = $idinsumo_traslado
						and idseriepkcos = $idseriepkcos
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                        $consulta = "
						UPDATE gest_depositos_stock
						SET 
						disponible = $diferencia
						WHERE 
						idempresa = $idempresa 
						and iddeposito = $iddeposito_origen
						and idproducto = $idinsumo_traslado
						and idseriecostos = $idseriepkcos
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                        $fechacompra = antisqlinyeccion(str_replace("'", "", $fechacompra), "text");
                        $fechacompra = str_replace("'NULL'", "NULL", $fechacompra);

                        // inserta en el deposito destino
                        $consulta = "
						insert into costo_productos 
						(cantidad,precio_costo,id_producto,idempresa,registrado_el,ubicacion,disponible,idcompra,idproducido,fechacompra,
						idproveedor,numfactura)
						values
						($disponible_traslado, $precio_costo, $idinsumo_traslado, $idempresa, '$ahora', $iddeposito_destino, $disponible_traslado, 
						$idcompra, NULL, $fechacompra,$idproveedor,'$numfactura')
			
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                        // id insertado
                        $consulta = "select max(idseriepkcos) as ultid from costo_productos where idempresa = $idempresa";
                        $rsulid = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                        $ultid = $rsulid->fields['ultid'];

                        $consulta = "
						INSERT INTO gest_depositos_stock
						(idproducto,idseriecostos,fechacompra,disponible,cantidad,iddeposito,recibido_el,verificado_el,descripcion,costogs,idempresa)
						values
						($idinsumo_traslado, $ultid, $fechacompra, $disponible_traslado, $disponible_traslado, $iddeposito_destino,
						'$ahora', '$ahora', '$insumo_desc', $precio_costo, $idempresa)
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                        // costo es igual al costo de la tanda * la cantidad de receta que queda
                        $costo_acumulado += $precio_costo * $disponible_traslado;

                    }
                    // si el disponible de la tanda de costos es menor o igual al disponible de receta, se cera el disponible de esa tanda de costo
                    if ($disponible_depo <= $disponible_traslado) {
                        $consulta = "
						UPDATE costo_productos
						SET 
						disponible = 0
						WHERE 
						idempresa = $idempresa 
						and ubicacion = $iddeposito_origen
						and id_producto = $idinsumo_traslado
						and idseriepkcos = $idseriepkcos
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                        $consulta = "
						UPDATE gest_depositos_stock
						SET 
						disponible = 0
						WHERE 
						idempresa = $idempresa 
						and iddeposito = $iddeposito_origen
						and idproducto = $idinsumo_traslado
						and idseriecostos = $idseriepkcos
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                        $fechacompra = antisqlinyeccion(str_replace("'", "", $fechacompra), "text");
                        $fechacompra = str_replace("'NULL'", "NULL", $fechacompra);
                        // inserta en el deposito destino
                        $consulta = "
						insert into costo_productos 
						(cantidad,precio_costo,id_producto,idempresa,registrado_el,ubicacion,disponible,idcompra,idproducido,fechacompra,
						idproveedor,numfactura)
						values
						($disponible_depo, $precio_costo, $idinsumo_traslado, $idempresa, '$ahora', $iddeposito_destino, $disponible_depo, 
						$idcompra, NULL, $fechacompra,$idproveedor,'$numfactura')
			
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                        // id insertado
                        $consulta = "select max(idseriepkcos) as ultid from costo_productos where idempresa = $idempresa";
                        $rsulid = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                        $ultid = $rsulid->fields['ultid'];
                        //$fechacompra=antisqlinyeccion(str_replace("'","",$fechacompra),"text");
                        $fechacompra = antisqlinyeccion(str_replace("'", "", $fechacompra), "text");
                        $fechacompra = str_replace("'NULL'", "NULL", $fechacompra);
                        $consulta = "
						INSERT INTO gest_depositos_stock
						(idproducto,idseriecostos,fechacompra,disponible,cantidad,iddeposito,recibido_el,verificado_el,descripcion,costogs,idempresa)
						values
						($idinsumo_traslado, $ultid, $fechacompra, $disponible_depo, $disponible_depo, $iddeposito_destino,
						'$ahora', '$ahora', '$insumo_desc', $precio_costo, $idempresa)
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                        // costo es igual al costo de la tanda * la cantidad de la tanda
                        $costo_acumulado += $precio_costo * $disponible_depo;

                    }

                    // descuenta del disponible de receta
                    $disponible_traslado = $disponible_traslado - $disponible_depo;


                    // si no hay disponible	en la tanda de costos es por que no hay en ninguna tanda por el filtro disponible > 0
                } else {
                    // nunca puede entrar aca por el filtro disponible > 0
                    echo "ERROR! es imposible que suceda esto!";
                    exit;
                }

                // si no queda disponible en receta termina ambos while
                if ($disponible_traslado <= 0) {
                    break 2;
                }

                // fin recorre los registros
                $rsdisp->MoveNext();
            }




        } //if($total_tandas > 0){
        // si no existen tandas disponibles en deposito	origen
        if ($total_tandas <= 0) {
            if ($disponible_traslado > 0) {
                $disponible_traslado_negativo = $disponible_traslado * -1;
                $disponible_traslado_positivo = $disponible_traslado;
            } else {
                $disponible_traslado_negativo = $disponible_traslado;
                $disponible_traslado_positivo = $disponible_traslado;
            }
            // preferencia costo
            $consulta = "
			select ult_costo_sinstock from preferencias where idempresa = $idempresa
			";
            $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $ult_costo_sinstock = $rspref->fields['ult_costo_sinstock'];
            if ($ult_costo_sinstock == 'S') {
                $precio_costo = $ult_costo;
            } else {
                $precio_costo = 0;
            }
            if ($precio_costo < 0) {
                $precio_costo = $precio_costo * -1;
            }
            $costo_acumulado = $precio_costo * ($disponible_traslado_negativo * -1);
            // ya que no hay nada con disponible mayor a 0 busca el descontador ficticio
            $consulta = "
			select idseriepkcos 
			from costo_productos 
			where 
			ficticio = 1 
			and id_producto = $idinsumo_traslado 
			and ubicacion = $iddeposito_origen
			and idempresa = $idempresa
			and disponible < 0
			limit 1
			";
            $rsfic = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idseriepkcos = intval($rsfic->fields['idseriepkcos']);
            // si no existe el ficticio
            if ($idseriepkcos == 0) {
                // se inserta tanda en origen con el disponible que sobro del traslado pero negativo
                $consulta = "
				insert into costo_productos 
				(cantidad,precio_costo,id_producto,idempresa,registrado_el,ubicacion,disponible,idcompra,idproducido,fechacompra,ficticio)
				values
				($disponible_traslado_negativo, $precio_costo, $idinsumo_traslado, $idempresa, '$ahora', $iddeposito_origen, $disponible_traslado_negativo, 0, NULL, '$ahora',1)
	
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                // id insertado
                $consulta = "select max(idseriepkcos) as ultid from costo_productos where idempresa = $idempresa";
                $rsulid = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $ultid = $rsulid->fields['ultid'];

                $consulta = "
				INSERT INTO gest_depositos_stock
				(idproducto,idseriecostos,fechacompra,disponible,cantidad,iddeposito,recibido_el,verificado_el,descripcion,costogs,idempresa,ficticio)
				values
				($idinsumo_traslado, $ultid, '$ahora', $disponible_traslado_negativo, $disponible_traslado_negativo, $iddeposito_origen,'$ahora', '$ahora', '$insumo_desc', $precio_costo, $idempresa,1)
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




            } else { // if($idseriepkcos == 0){

                // si existe el ficticio actualiza
                $consulta = "
				update costo_productos
				set
					disponible = disponible+$disponible_traslado_negativo
				where
					idseriepkcos = $idseriepkcos
					and ficticio = 1
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                $consulta = "
				update gest_depositos_stock
				set
					disponible = disponible+$disponible_traslado_negativo
				where
					idseriecostos = $idseriepkcos
					and ficticio = 1
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            } // if($idseriepkcos == 0){

            // se inserta tanda en destino con el disponible que sobro del traslado pero positivo
            $consulta = "
			insert into costo_productos 
			(cantidad,precio_costo,id_producto,idempresa,registrado_el,ubicacion,disponible,idcompra,idproducido,fechacompra)
			values
			($disponible_traslado_positivo, $precio_costo, $idinsumo_traslado, $idempresa, '$ahora', $iddeposito_destino, $disponible_traslado_positivo, 0, NULL, '$ahora')
	
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // id insertado
            $consulta = "select max(idseriepkcos) as ultid from costo_productos where idempresa = $idempresa";
            $rsulid = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $ultid = $rsulid->fields['ultid'];

            $consulta = "
			INSERT INTO gest_depositos_stock
			(idproducto,idseriecostos,fechacompra,disponible,cantidad,iddeposito,recibido_el,verificado_el,descripcion,costogs,idempresa)
			values
			($idinsumo_traslado, $ultid, '$ahora', $disponible_traslado_positivo, $disponible_traslado_positivo,  $iddeposito_destino,'$ahora', '$ahora', '$insumo_desc', $precio_costo, $idempresa)
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // se cera el disponible para traslado
            $disponible_traslado = 0;

        } // if($total_tandas <= 0){


    } //while($disponible_traslado > 0){

    // costo total de los insumos utilizados, no es el promedio
    return $costo_acumulado;
}
function descontar_stock_general($idinsumo_descontar, $cantidad_descontar, $iddeposito, $vencimiento)
{

    // variables globales
    global $idempresa;
    global $conexion;
    global $ahora;
    $cantidad_descontar = floatval($cantidad_descontar);

    // busca el nombre del insumo
    $consulta = "select descripcion from insumos_lista where idinsumo = $idinsumo_descontar and idempresa = $idempresa";
    $rsnom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $insumo_desc = str_replace("'", "", $rsnom->fields['descripcion']);

    // busca si existe en stock general el insumo
    $buscar = "
	Select * 
	from gest_depositos_stock_gral 
	where 
	idproducto=$idinsumo_descontar 
	and idempresa=$idempresa 
	and estado=1 
	and iddeposito = $iddeposito
	";
    $rsst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    // si no existe inserta
    if (intval($rsst->fields['idproducto']) == 0) {
        $insertar = "
		INSERT INTO gest_depositos_stock_gral
		(iddeposito, idproducto, disponible, tipodeposito, last_transfer, estado, descripcion, idempresa) 
		VALUES 
		($iddeposito,$idinsumo_descontar,0,1,'$ahora',1,'$insumo_desc',$idempresa
		)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
    }

    // descontar insumo de stock general
    $consulta = "
	UPDATE gest_depositos_stock_gral 
	SET 
	disponible=(disponible-$cantidad_descontar)
	WHERE 
	idempresa=$idempresa 
	and iddeposito=$iddeposito
	and idproducto=$idinsumo_descontar
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // descontar insumo de stock detalle
    $consulta = "
	UPDATE gest_depositos_stock 
	SET 
	disponible=(disponible-$cantidad_descontar),
	cantidad=(cantidad-$cantidad_descontar)
	WHERE 
	idempresa=$idempresa 
	and iddeposito=$iddeposito
	and idproducto=$idinsumo_descontar
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    return "OK";

}
function aumentar_stock_general($idinsumo_aumentar, $cantidad_aumentar, $iddeposito)
{

    // variables globales
    global $idempresa;
    global $conexion;
    global $ahora;
    $cantidad_aumentar = floatval($cantidad_aumentar);

    // busca el nombre del insumo
    $consulta = "select * from insumos_lista where idinsumo = $idinsumo_aumentar and idempresa = $idempresa";
    $rsnom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $insumo_desc = str_replace("'", "", $rsnom->fields['descripcion']);

    // busca si existe en stock general el insumo
    $buscar = "
	Select * 
	from gest_depositos_stock_gral 
	where 
	idproducto=$idinsumo_aumentar 
	and idempresa=$idempresa 
	and estado=1 
	and iddeposito = $iddeposito
	";
    $rsst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    // si no existe inserta
    if (intval($rsst->fields['idproducto']) == 0) {
        $insertar = "
		INSERT INTO gest_depositos_stock_gral
		(iddeposito, idproducto, disponible, tipodeposito, last_transfer, estado, descripcion, idempresa) 
		VALUES 
		($iddeposito,$idinsumo_aumentar,0,1,'$ahora',1,'$insumo_desc',$idempresa
		)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
    }

    // aumentar insumo de stock
    $consulta = "
	UPDATE gest_depositos_stock_gral 
	SET 
	disponible=(disponible+$cantidad_aumentar)
	WHERE 
	idempresa=$idempresa 
	and iddeposito=$iddeposito
	and idproducto=$idinsumo_aumentar
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    return "OK";

}
function descuenta_stock_inv($parametros_array)
{
    global $idempresa;
    global $conexion;
    global $ahora;
    $costo_acumulado = 0;
    $idinsumo_receta = $parametros_array['idinsumo_receta'];
    $cantidad_receta = $parametros_array['cantidad_receta'];
    $iddeposito = $parametros_array['iddeposito'];
    $vencimiento = $parametros_array['vencimiento'];
    $lote = $parametros_array['lote'];
    $disponible_receta = floatval($cantidad_receta);
    // busca el nombre del insumo
    $consulta = "select * from insumos_lista where idinsumo = $idinsumo_receta and idempresa = $idempresa";
    $rsnom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $insumo_desc = str_replace("'", "", $rsnom->fields['descripcion']);
    $ult_costo = floatval($rsnom->fields['costo']);

    // recorre mientras haya disponible
    while ($disponible_receta > 0) {
        // busca los disponibles

        $whereadd = "";
        if ($vencimiento != "" && $vencimiento != "NULL") {
            $whereadd = " and (DATE_FORMAT(costo_productos.vencimiento, '%Y-%m-%d') > DATE_FORMAT('$vencimiento', '%Y-%m-%d') or (costo_productos.vencimiento is null and costo_productos.lote is null) )
			order by costo_productos.vencimiento asc";
        } else {
            $whereadd = " order by fechacompra asc, idseriepkcos asc";
        }


        $consulta = "
		select * 
		from costo_productos 
		where 
		idempresa = $idempresa 
		and ubicacion = $iddeposito
		and id_producto = $idinsumo_receta
		and disponible > 0
		$whereadd
		";
        $rsdisp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $total_tandas = intval($rsdisp->RecordCount());

        // si existen tandas en deposito con cantidad disponible > 0
        if ($total_tandas > 0) {
            // inicio recorre los registros de costos en deposito
            $costo_acumulado = 0;
            while (!$rsdisp->EOF) {
                $disponible_depo = floatval($rsdisp->fields['disponible']);
                $idseriepkcos = $rsdisp->fields['idseriepkcos'];
                $precio_costo = $rsdisp->fields['precio_costo'];
                $lote = $rsdisp->fields['lote'];
                $vencimiento = $rsdisp->fields['vencimiento'];
                $diferencia = 0;

                // si hay disponible en esta tanda de costos
                if ($disponible_depo > 0) {


                    // si el disponible de la tanda de costos es mayor al disponible de receta, se pone la diferencia
                    if ($disponible_depo > $disponible_receta) {
                        $diferencia = $disponible_depo - $disponible_receta;
                        $consulta = "
						UPDATE costo_productos
						SET 
						disponible = $diferencia
						WHERE 
						idempresa = $idempresa 
						and ubicacion = $iddeposito
						and id_producto = $idinsumo_receta
						and idseriepkcos = $idseriepkcos
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                        $consulta = "
						UPDATE gest_depositos_stock
						SET 
						disponible = $diferencia
						WHERE 
						idempresa = $idempresa 
						and iddeposito = $iddeposito
						and idproducto = $idinsumo_receta
						and idseriecostos = $idseriepkcos
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                        // costo es igual al costo de la tanda * la cantidad de receta que queda
                        $costo_acumulado += $precio_costo * $disponible_receta;

                    }
                    // si el disponible de la tanda de costos es menor o igual al disponible de receta, se cera el disponible de esa tanda de costo
                    if ($disponible_depo <= $disponible_receta) {
                        $consulta = "
						UPDATE costo_productos
						SET 
						disponible = 0
						WHERE 
						idempresa = $idempresa 
						and ubicacion = $iddeposito
						and id_producto = $idinsumo_receta
						and idseriepkcos = $idseriepkcos
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                        $consulta = "
						UPDATE gest_depositos_stock
						SET 
						disponible = 0
						WHERE 
						idempresa = $idempresa 
						and iddeposito = $iddeposito
						and idproducto = $idinsumo_receta
						and idseriecostos = $idseriepkcos
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                        // costo es igual al costo de la tanda * la cantidad de la tanda
                        $costo_acumulado += $precio_costo * $disponible_depo;

                    }

                    // descuenta del disponible de receta
                    $disponible_receta = $disponible_receta - $disponible_depo;


                    // si no hay disponible	en la tanda de costos es por que no hay en ninguna tanda por el filtro disponible > 0
                } else {
                    // nunca puede entrar aca por el filtro disponible > 0
                    echo "ERROR! es imposible que suceda esto!";
                    exit;
                }

                // si no queda disponible en receta termina ambos while
                if ($disponible_receta <= 0) {
                    break 2;
                }

                // fin recorre los registros
                $rsdisp->MoveNext();
            }




        } //if($total_tandas > 0){
        // si no existen tandas disponibles en deposito
        if ($total_tandas <= 0) {
            if ($disponible_receta > 0) {
                $disponible_receta_negativo = $disponible_receta * -1;
            } else {
                $disponible_receta_negativo = $disponible_receta;
            }
            // preferencia costo
            $consulta = "
			select ult_costo_sinstock from preferencias where idempresa = $idempresa
			";
            $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $ult_costo_sinstock = $rspref->fields['ult_costo_sinstock'];
            if ($ult_costo_sinstock == 'S') {
                $precio_costo = $ult_costo;
            } else {
                $precio_costo = 0;
            }
            if ($precio_costo < 0) {
                $precio_costo = $precio_costo * -1;
            }
            $costo_acumulado = $precio_costo * ($disponible_receta_negativo * -1);
            // ya que no hay nada con disponible mayor a 0 busca el descontador ficticio
            $consulta = "
			select idseriepkcos 
			from costo_productos 
			where 
			ficticio = 1 
			and id_producto = $idinsumo_receta 
			and ubicacion = $iddeposito
			and idempresa = $idempresa
			and disponible < 0
			limit 1
			";
            $rsfic = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idseriepkcos = intval($rsfic->fields['idseriepkcos']);
            // si no existe el ficticio
            if ($idseriepkcos == 0) {
                // se inserta tanda con el disponible que sobro de la receta pero negativo
                $consulta = "
				insert into costo_productos 
				(cantidad,precio_costo,id_producto,idempresa,registrado_el,ubicacion,disponible,idcompra,idproducido,fechacompra,ficticio)
				values
				($disponible_receta_negativo, $precio_costo, $idinsumo_receta, $idempresa, '$ahora', $iddeposito, $disponible_receta_negativo, 0, NULL, '$ahora',1)
	
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                // id insertado
                $consulta = "select max(idseriepkcos) as ultid from costo_productos where idempresa = $idempresa";
                $rsulid = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $ultid = $rsulid->fields['ultid'];

                $consulta = "
				INSERT INTO gest_depositos_stock
				(idproducto,idseriecostos,fechacompra,disponible,cantidad,iddeposito,recibido_el,verificado_el,descripcion,costogs,idempresa,ficticio)
				values
				($idinsumo_receta, $ultid, '$ahora', $disponible_receta_negativo, $disponible_receta_negativo, $iddeposito,'$ahora', '$ahora', '$insumo_desc', $precio_costo, $idempresa,1)
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            } else { // if($idseriepkcos == 0){

                // si existe el ficticio actualiza
                $consulta = "
				update costo_productos
				set
					disponible = disponible+$disponible_receta_negativo
				where
					idseriepkcos = $idseriepkcos
					and ficticio = 1
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                $consulta = "
				update gest_depositos_stock
				set
					disponible = disponible+$disponible_receta_negativo
				where
					idseriecostos = $idseriepkcos
					and ficticio = 1
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            } // if($idseriepkcos == 0){
            $disponible_receta = 0;
        } // if($total_tandas <= 0){


    } //while($disponible_receta > 0){

    // costo total de los insumos utilizados, no es el promedio
    return $costo_acumulado;
}
function aumentar_stock($parametros_array)
{

    // variables globales
    global $idempresa;
    global $conexion;
    global $ahora;
    $idinsumo_aumentar = antisqlinyeccion($parametros_array['idinsumo_aumentar'], "int");
    $cantidad_aumentar = antisqlinyeccion($parametros_array['cantidad_aumentar'], "int");
    $costo_unitario = antisqlinyeccion($parametros_array['costo_unitario'], "float");
    $iddeposito = antisqlinyeccion($parametros_array['iddeposito'], "int");
    $vencimiento = antisqlinyeccion($parametros_array['vencimiento'], "text");
    $lote = antisqlinyeccion($parametros_array['lote'], "text");
    $numfactura = antisqlinyeccion($parametros_array['numfactura'], "text");
    $idproveedor = antisqlinyeccion($parametros_array['idproveedor'], "int");
    $cantidad_aumentar = floatval($cantidad_aumentar);

    // busca el nombre del insumo
    $consulta = "select * from insumos_lista where idinsumo = $idinsumo_aumentar and idempresa = $idempresa";
    $rsnom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $insumo_desc = str_replace("'", "", $rsnom->fields['descripcion']);
    $ult_costo = floatval($rsnom->fields['costo']);
    if ($costo_unitario == 0) {
        $costo_unitario = $ult_costo;
    }

    // aumentar stock fisico de costo productos y depositos stock
    //felata
    // mu,factura idproveedor
    // al hacer transferencias se aumenta la gran pregunta es si dividir tambien y rastrear por el id compra
    // es decir si se compro 5 y se transfiere 2 siguen esos dos asociados a la compra pero con otro
    // registro en costo_producto y se disminuye la cantidad en el costo producto original
    $consulta = "
	insert into costo_productos 
	(numfactura,idproveedor,cantidad,precio_costo,id_producto,idempresa,registrado_el,ubicacion,disponible,idcompra,idproducido,fechacompra,vencimiento,lote)
	values
	($numfactura, $idproveedor, $cantidad_aumentar, $costo_unitario, $idinsumo_aumentar, $idempresa, '$ahora', $iddeposito, $cantidad_aumentar, 0, NULL, '$ahora', $vencimiento, $lote)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // id insertado
    $consulta = "select max(idseriepkcos) as ultid from costo_productos where idempresa = $idempresa";
    $rsulid = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $ultid = $rsulid->fields['ultid'];

    $consulta = "
	INSERT INTO gest_depositos_stock
	(idproducto,idseriecostos,fechacompra,disponible,cantidad,iddeposito,recibido_el,verificado_el,descripcion,costogs,idempresa, vencimiento, lote)
	values
	($idinsumo_aumentar, $ultid, '$ahora', $cantidad_aumentar, $cantidad_aumentar, $iddeposito,'$ahora', '$ahora', '$insumo_desc', 
	$costo_unitario, $idempresa, $vencimiento, $lote)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    $parametros_array = [
        "idproducto"
    ];
    return "OK";

}
function disminuir_stockfefo($parametros_array)
{
    // variables globales
    global $idempresa;
    global $conexion;
    global $ahora;

    $idinsumo = $parametros_array['idinsumo'];
    $lote = $parametros_array['lote'];
    $vencimiento = $parametros_array['vencimiento'];
    $fila = $parametros_array['fila'];
    $columna = $parametros_array['columna'];
    $idalm = $parametros_array['idalm'];
    $iddeposito = $parametros_array['iddeposito'];
    $diferencia = $parametros_array['diferencia'];
    // encontrar si maneja o no lote
    $consulta = "SELECT maneja_lote FROM insumos_lista where insumos_lista.idinsumo = $idinsumo";
    $rs_maneja_lote = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $maneja_lote = $rs_maneja_lote ->fields['maneja_lote'];
    $whereadd_lote = "";
    if ($maneja_lote == 1) {
        $whereadd_lote = "and gest_depositos_stock.lote = $lote
		and gest_depositos_stock.vencimiento = $vencimiento";

    } else {
        $whereadd_lote = "and gest_depositos_stock.lote is NULL
		and gest_depositos_stock.vencimiento is NULL";

    }
    $consulta = "SELECT gest_depositos_stock_almacto.*, gest_depositos_stock.idseriecostos, gest_depositos_stock.idregseriedptostk
		FROM gest_depositos_stock_almacto 
		INNER JOIN gest_depositos_stock on gest_depositos_stock.idregseriedptostk = gest_depositos_stock_almacto.idregseriedptostk
		WHERE
		gest_depositos_stock_almacto.fila = $fila
		and gest_depositos_stock_almacto.columna = $columna
		and gest_depositos_stock_almacto.estado = 1
		and gest_depositos_stock_almacto.idalm = $idalm
		and gest_depositos_stock_almacto.disponible > 0
		and gest_depositos_stock.iddeposito = $iddeposito
		and gest_depositos_stock.idproducto = $idinsumo
		$whereadd_lote
		";
    $rs_gest_depisitos_datos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    //buscar costos_productos y la factura de compra asociada
    $tandas_gest_deposito_stock = intval($rs_gest_depisitos_datos->RecordCount());



    if ($tandas_gest_deposito_stock > 0) {
        while (!$rs_gest_depisitos_datos->EOF) {
            $idseriecostos = $rs_gest_depisitos_datos->fields['idseriecostos'];// id para costos_productos
            $idregseriedptostk = $rs_gest_depisitos_datos->fields['idregseriedptostk']; // id del gest_depo_stock
            $disponible_almacenado = $rs_gest_depisitos_datos->fields['disponible'];
            $idregserie_almacto = $rs_gest_depisitos_datos->fields['idregserie_almacto'];

            if (($diferencia * -1) <= $disponible_almacenado) {
                $cantidad_descontar = ($diferencia * -1);
                $set_add = "";
                $set_add = ($diferencia * -1) == $disponible_almacenado ? ", estado = 3" : "";
                $diferencia = $disponible_almacenado + $diferencia;//diferencia ya es negativo

                //descuenta el almacenamiento

                $consulta = "UPDATE gest_depositos_stock_almacto 
								set
								disponible = (disponible - $cantidad_descontar)
								$set_add 
								where
								idregserie_almacto=$idregserie_almacto   
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                // descuenta del detalle de los depositos
                $consulta = "UPDATE gest_depositos_stock 
							   set
								disponible = (disponible - $cantidad_descontar)
							   where
								idregseriedptostk=$idregseriedptostk   
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                //descuenta el deposito general

                $consulta = "UPDATE gest_depositos_stock_gral 
							   SET 
							   disponible=(disponible-$cantidad_descontar)
							   WHERE 
							   idempresa=$idempresa 
							   and iddeposito=$iddeposito
							   and idproducto=$idinsumo
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                //descontando de costos productos
                $consulta = "UPDATE 
					costo_productos
					set
					disponible=(disponible-$cantidad_descontar)
					where 
					idseriepkcos = $idregseriedptostk
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            }
            if (($diferencia * -1) > $disponible_almacenado) {
                $cantidad_descontar = $disponible_almacenado;
                $diferencia = $disponible_almacenado + $diferencia;//diferencia ya es negativo
                $consulta = "UPDATE gest_depositos_stock_almacto 
								set
								disponible = 0
								, estado = 3
								where
								idregserie_almacto=$idregserie_almacto   
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                // descuenta del detalle de los depositos
                $consulta = "UPDATE gest_depositos_stock 
								set
								disponible = (disponible - $cantidad_descontar)
								where
								idregseriedptostk=$idregseriedptostk   
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                //descuenta el deposito general

                $consulta = "UPDATE gest_depositos_stock_gral 
							   SET 
							   disponible=(disponible-$cantidad_descontar)
							   WHERE 
							   idempresa=$idempresa 
							   and iddeposito=$iddeposito
							   and idproducto=$idinsumo
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                //descontando de costos productos
                $consulta = "UPDATE 
					costo_productos
					set
					disponible=(disponible-$cantidad_descontar)
					where 
					idseriepkcos = $idregseriedptostk
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            }

            if ($diferencia == 0) {
                break;
            }

            $rs_gest_depisitos_datos->MoveNext();
        }
        /// verificar si no pudo descontar todo y dar una alerta
    }
}
function aumentar_stockfefo($parametros_array)
{
    // variables globales
    global $idempresa;
    global $conexion;
    global $ahora;

    $idinsumo = $parametros_array['idinsumo'];
    $lote = $parametros_array['lote'];
    $vencimiento = $parametros_array['vencimiento'];
    $fila = $parametros_array['fila'];
    $columna = $parametros_array['columna'];
    $idalm = $parametros_array['idalm'];
    $iddeposito = $parametros_array['iddeposito'];
    $diferencia = $parametros_array['diferencia'];
    $idmedida_ref = $parametros_array['idmedida_ref'];

    $registrado_el = antisqlinyeccion($parametros_array['fecha_inicio'], 'date');
    $registrado_por = intval($parametros_array['iniciado_por']);
    $idpasillo = intval($parametros_array['idpasillo']);

    // encontrar si maneja o no lote
    $consulta = "SELECT maneja_lote, costo, descripcion FROM insumos_lista where insumos_lista.idinsumo = $idinsumo";
    $rs_maneja_lote = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $maneja_lote = $rs_maneja_lote ->fields['maneja_lote'];
    $costos = $rs_maneja_lote ->fields['costo'];
    $insumo_desc = $rs_maneja_lote ->fields['descripcion'];
    // $whereadd_lote="";
    // if($maneja_lote == 1 ){
    // 	$head="and gest_depositos_stock.lote = $lote
    // 	and gest_depositos_stock.vencimiento = $vencimiento";
    // }else{
    // 	$whereadd_lote="and gest_depositos_stock.lote is NULL
    // 	and gest_depositos_stock.vencimiento is NULL";
    // }

    //crea costos productos para el conteo asociado
    $consulta = "
	insert into costo_productos 
	(cantidad,precio_costo,id_producto,idempresa,registrado_el,ubicacion,disponible,idcompra,idproducido,fechacompra,vencimiento,lote)
	values
	($diferencia, $costos, $idinsumo, $idempresa, '$ahora', $iddeposito, $diferencia, 0, NULL, '$ahora',$vencimiento,$lote)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    //deposito general
    $consulta = "UPDATE gest_depositos_stock_gral 
				SET 
				disponible=(disponible + $diferencia)
				WHERE 
				idempresa=$idempresa 
				and iddeposito=$iddeposito
				and idproducto=$idinsumo
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // descuenta del detalle de los depositos
    // id insertado
    $idseriepkcos = select_max_id_suma_uno("costo_productos", "idseriepkcos")["idseriepkcos"];
    $consulta = "
	INSERT INTO gest_depositos_stock
	(idproducto,idseriecostos,fechacompra,disponible,cantidad,iddeposito,recibido_el,verificado_el,descripcion,costogs,idempresa, vencimiento, lote)
	values
	($idinsumo, $idseriepkcos, '$ahora', $diferencia, $diferencia, $iddeposito,'$ahora', '$ahora', '$insumo_desc', 
	$costos, $idempresa, $vencimiento, $lote)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    //descuenta el deposito general

    $idregseriedptostk = select_max_id_suma_uno("gest_depositos_stock", "idregseriedptostk")["idregseriedptostk"];

    //descuenta el almacenamiento
    $idregserie_almacto = select_max_id_suma_uno("gest_depositos_stock_almacto", "idregserie_almacto")["idregserie_almacto"];

    $consulta = "INSERT INTO  gest_depositos_stock_almacto 
    (idregserie_almacto, idalm, idregseriedptostk,fila,columna,
    cantidad,disponible,idmedida,registrado_por,registrado_el,estado,idpasillo)
    values ($idregserie_almacto, $idalm, $idregseriedptostk, $fila, $columna,
    $diferencia, $diferencia, $idmedida_ref, $registrado_por, $registrado_el, '1', $idpasillo)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}
function descontar_stock($parametros_array)
{
    global $idempresa;
    global $conexion;
    global $ahora;
    $idinsumo = $parametros_array['idinsumo'];
    $cantidad = $parametros_array['cantidad'];
    $iddeposito = $parametros_array['iddeposito'];
    $vencimiento = $parametros_array['vencimiento'];
    $disponible_paradesc = floatval($cantidad);
    $costo_acumulado = 0;
    // busca el nombre del insumo
    $consulta = "select * from insumos_lista where idinsumo = $idinsumo and idempresa = $idempresa";
    $rsnom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $insumo_desc = str_replace("'", "", $rsnom->fields['descripcion']);
    $ult_costo = floatval($rsnom->fields['costo']);

    // recorre mientras haya disponible
    while ($disponible_paradesc > 0) {
        // busca los disponibles
        $whereadd = "";
        if ($vencimiento != "" && $vencimiento != "NULL") {
            $whereadd = " and (DATE_FORMAT(costo_productos.vencimiento, '%Y-%m-%d') > DATE_FORMAT('$vencimiento', '%Y-%m-%d') or (costo_productos.vencimiento is null and costo_productos.lote is null) )
			order by costo_productos.vencimiento asc";
        } else {
            $whereadd = " order by fechacompra asc, idseriepkcos asc";
        }
        $consulta = "
			select * 
			from costo_productos 
			where 
			idempresa = $idempresa 
			and ubicacion = $iddeposito
			and id_producto = $idinsumo
			and disponible > 0
			$whereadd
		";


        $rsdisp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $total_tandas = intval($rsdisp->RecordCount());

        // si existen tandas en deposito con cantidad disponible > 0
        if ($total_tandas > 0) {
            // inicio recorre los registros de costos en deposito
            $costo_acumulado = 0;
            while (!$rsdisp->EOF) {
                $disponible_depo = floatval($rsdisp->fields['disponible']);
                $idseriepkcos = $rsdisp->fields['idseriepkcos'];
                $precio_costo = $rsdisp->fields['precio_costo']; //gest costogs
                $lote = $rsdisp->fields['lote'];
                $vencimiento = $rsdisp->fields['vencimiento'];
                $diferencia = 0;

                // si hay disponible en esta tanda de costos
                if ($disponible_depo > 0) {


                    // si el disponible de la tanda de costos es mayor al disponible de receta, se pone la diferencia
                    if ($disponible_depo > $disponible_paradesc) {
                        $diferencia = $disponible_depo - $disponible_paradesc;
                        $consulta = "
						UPDATE costo_productos
						SET 
						disponible = $diferencia
						WHERE 
						idempresa = $idempresa 
						and ubicacion = $iddeposito
						and id_producto = $idinsumo
						and idseriepkcos = $idseriepkcos
						";
                        // $whereadd = "";
                        // if($vencimiento != "" && $vencimiento != "NULL"){

                        // }
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                        $consulta = "
						UPDATE gest_depositos_stock
						SET 
						disponible = $diferencia
						WHERE 
						idempresa = $idempresa 
						and iddeposito = $iddeposito
						and idproducto = $idinsumo
						and idseriecostos = $idseriepkcos
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                        // costo es igual al costo de la tanda * la cantidad de receta que queda
                        $costo_acumulado += $precio_costo * $disponible_paradesc;

                    }
                    // si el disponible de la tanda de costos es menor o igual al disponible de receta, se cera el disponible de esa tanda de costo
                    if ($disponible_depo <= $disponible_paradesc) {
                        $consulta = "
						UPDATE costo_productos
						SET 
						disponible = 0
						WHERE 
						idempresa = $idempresa 
						and ubicacion = $iddeposito
						and id_producto = $idinsumo
						and idseriepkcos = $idseriepkcos
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                        $consulta = "
						UPDATE gest_depositos_stock
						SET 
						disponible = 0
						WHERE 
						idempresa = $idempresa 
						and iddeposito = $iddeposito
						and idproducto = $idinsumo
						and idseriecostos = $idseriepkcos
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                        // costo es igual al costo de la tanda * la cantidad de la tanda
                        $costo_acumulado += $precio_costo * $disponible_depo;

                    }

                    // descuenta del disponible de receta
                    $disponible_paradesc = $disponible_paradesc - $disponible_depo;


                    // si no hay disponible	en la tanda de costos es por que no hay en ninguna tanda por el filtro disponible > 0
                } else {
                    // nunca puede entrar aca por el filtro disponible > 0
                    echo "ERROR! es imposible que suceda esto!";
                    exit;
                }

                // si no queda disponible en receta termina ambos while
                if ($disponible_paradesc <= 0) {
                    break 2;
                }

                // fin recorre los registros
                $rsdisp->MoveNext();
            }




        } //if($total_tandas > 0){
        // si no existen tandas disponibles en deposito
        if ($total_tandas <= 0) {
            if ($disponible_paradesc > 0) {
                $disponible_paradesc_negativo = $disponible_paradesc * -1;
            } else {
                $disponible_paradesc_negativo = $disponible_paradesc;
            }
            // preferencia costo
            $consulta = "
			select ult_costo_sinstock from preferencias where idempresa = $idempresa
			";
            $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $ult_costo_sinstock = $rspref->fields['ult_costo_sinstock'];
            if ($ult_costo_sinstock == 'S') {
                $precio_costo = $ult_costo;
            } else {
                $precio_costo = 0;
            }
            if ($precio_costo < 0) {
                $precio_costo = $precio_costo * -1;
            }
            $costo_acumulado = $precio_costo * ($disponible_paradesc_negativo * -1);
            // ya que no hay nada con disponible mayor a 0 busca el descontador ficticio

            // $whereadd="";
            // if( $vencimiento != "" && $vencimiento != "NULL" ){
            // 	$whereadd=" and (DATE_FORMAT(costo_productos.vencimiento, '%Y-%m-%d') > DATE_FORMAT('$vencimiento', '%Y-%m-%d') or (costo_productos.vencimiento is null and costo_productos.lote is null) )
            // 	order by costo_productos.vencimiento asc";
            // }else{
            // 	$whereadd=" order by fechacompra asc, idseriepkcos asc";
            // }
            $consulta = "
			select idseriepkcos 
			from costo_productos 
			where 
			ficticio = 1 
			and id_producto = $idinsumo 
			and ubicacion = $iddeposito
			and idempresa = $idempresa
			and disponible < 0
			limit 1
			";
            $rsfic = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idseriepkcos = intval($rsfic->fields['idseriepkcos']);
            // si no existe el ficticio
            if ($idseriepkcos == 0) {
                // se inserta tanda con el disponible que sobro de la receta pero negativo
                $consulta = "
				insert into costo_productos 
				(cantidad,precio_costo,id_producto,idempresa,registrado_el,ubicacion,disponible,idcompra,idproducido,fechacompra,ficticio)
				values
				($disponible_paradesc_negativo, $precio_costo, $idinsumo, $idempresa, '$ahora', $iddeposito, $disponible_paradesc_negativo, 0, NULL, '$ahora',1)
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                // id insertado
                $consulta = "select max(idseriepkcos) as ultid from costo_productos where idempresa = $idempresa";
                $rsulid = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $ultid = $rsulid->fields['ultid'];

                $consulta = "
				INSERT INTO gest_depositos_stock
				(idproducto,idseriecostos,fechacompra,disponible,cantidad,iddeposito,recibido_el,verificado_el,descripcion,costogs,idempresa,ficticio)
				values
				($idinsumo, $ultid, '$ahora', $disponible_paradesc_negativo, $disponible_paradesc_negativo, $iddeposito,'$ahora', '$ahora', '$insumo_desc', $precio_costo, $idempresa,1)
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            } else { // if($idseriepkcos == 0){

                // si existe el ficticio actualiza
                $consulta = "
				update costo_productos
				set
					disponible = disponible+$disponible_paradesc_negativo
				where
					idseriepkcos = $idseriepkcos
					and ficticio = 1
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                $consulta = "
				update gest_depositos_stock
				set
					disponible = disponible+$disponible_paradesc_negativo
				where
					idseriecostos = $idseriepkcos
					and ficticio = 1
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            } // if($idseriepkcos == 0){
            $disponible_paradesc = 0;


        } // if($total_tandas <= 0){


    } //while($disponible_paradesc > 0){

    // costo total de los insumos utilizados, no es el promedio
    return $costo_acumulado;
}
function recalcular_costo_promedio($parametros_array)
{
    //no se usara
    require_once('../insumos/preferencias_insumos_listas.php');
    global $preferencias_costo_promedio;


    global $idempresa;
    global $conexion;
    global $ahora;
    $idp = $parametros_array['idproducto'];  //idinsumo
    $idseriepkcos = $parametros_array['idseriepkcos'];  //id de la tabla costo_productos
    $cantidad = $parametros_array['cantidad'];  // cantidad agregada o descontada
    $aumenta = $parametros_array['aumenta'];  // true si aumenta y false si disminuye


    $precio_costo = "";
    $costo_promedio = "";
    $costo_cif = "";

    $consulta = "SELECT 
	gest_depositos_stock_gral.idproducto, 
	gest_depositos_stock_gral.costo_promedio, 
	SUM(
	  gest_depositos_stock_gral.disponible
	) as cantidad 
  from 
	gest_depositos_stock_gral 
  where 
	gest_depositos_stock_gral.estado = 1 
	and gest_depositos_stock_gral.idproducto = $idp 
  GROUP BY 
	idproducto
  ";
    $rs_costo_promedio = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $costo_promedio = floatval($rs_costo_promedio->fields['costo_promedio']);
    $cantidad_deposito = floatval($rs_costo_promedio->fields['cantidad']);


    $consulta = "select precio_costo, costo_cif from costo_productos where idseriepkcos=$idseriepkcos";
    $rs_costo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $precio_costo = floatval($rs_costo->fields['precio_costo']);
    $costo_cif = floatval($rs_costo->fields['costo_cif']);
    $costo = null;
    if ($costo_cif == 0) {
        $costo = $precio_costo;
    } else {
        $costo = $costo_cif;
    }

    if ($cantidad_deposito > 0) {

        // if($cotizacion_compra > 0 && $cotizacion_despacho > 0){
        // 	$costo_promedio = ($costo_promedio/$cotizacion_compra)*$cotizacion_despacho;
        // }

        if ($aumenta == true) {
            $costo_promedio = ($costo_promedio * $cantidad_deposito + $cantidad * $costo) / ($cantidad + $cantidad_deposito);
        } else {
            $costo_promedio = ($costo_promedio * $cantidad_deposito - $cantidad * $costo) / ($cantidad - $cantidad_deposito);
        }

        if ($costo_cif == 0) {
            $consulta = "UPDATE costo_productos set costo_promedio=$costo_promedio, costo_cif=$costo,cantidad_stock=$cantidad_deposito,modificado_el='$ahora' WHERE idseriepkcos = $idseriepkcos ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        } else {
            $consulta = "UPDATE costo_productos set costo_promedio=$costo_promedio,cantidad_stock=$cantidad_deposito,modificado_el='$ahora' WHERE idseriepkcos = $idseriepkcos ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }
    } else {
        $costo_promedio = $costo;
        if ($costo_cif == 0) {
            $consulta = "UPDATE costo_productos set costo_promedio=$costo_promedio,costo_cif=$costo_promedio, cantidad_stock=0,modificado_el='$ahora' WHERE idseriepkcos = $idseriepkcos ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        } else {
            $consulta = "UPDATE costo_productos set costo_promedio=$costo_promedio, cantidad_stock=0,modificado_el='$ahora' WHERE idseriepkcos = $idseriepkcos ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }

    }


    if ($preferencias_costo_promedio == "S") {
        $consulta = "
		update insumos_lista 
		set costo = 
				COALESCE((
					select  costo_promedio from  costo_productos   WHERE idseriepkcos = $idseriepkcos
				),0)
		where
		idinsumo = $idp
		and idempresa = $idempresa
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "
		update productos_sucursales 
		set precio = 
				COALESCE((
					select  costo_promedio from  costo_productos   WHERE idseriepkcos = $idseriepkcos
				),0)
		where
		idproducto = (select idproducto from insumos_lista where idinsumo = $idp)
		and idempresa = $idempresa
		and activo_suc = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }



}
// este se usa en otra funcion stock_costo_aumentar
function stock_costo_aumentar_ppp($parametros_array_sc)
{

    // variables globales
    global $conexion;


    $idproducto = intval($parametros_array_sc['idinsumo']);
    $cantidad_aumentar = floatval($parametros_array_sc['cantidad_aumentar']);
    $costo_unitario = floatval($parametros_array_sc['costo_unitario']);
    $iddeposito = intval($parametros_array_sc['iddeposito']);
    $fecha_tanda_completa = $parametros_array_sc['fecha_tanda_completa'];
    $idcompra = intval($parametros_array_sc['idcompra']);
    $idproducido = intval($parametros_array_sc['idproducido']);
    $idventa = intval($parametros_array_sc['idventa']);




    // se usa solo para devoluciones/anulaciones de venta
    $idventa = intval($idventa);
    if ($idventa > 0) {
        $consulta = "
		select * 
		from ventas_detalles 
		where 
		idventa = $idventa 
		and idproducto = $idproducto 
		limit 1
		";
        $rscosto = $adodb_conn->Execute($consulta) or die(errorpg($adodb_conn, $consulta));
        $costo_unitario = floatval($rscosto->fields['precio_costo']);
    }

    // obtiene el costo total
    $consulta = "
	select  sum(costo) as costo,  sum(total_costo)  as total_costo, sum(disponible) as disponible
	from depositos_stock_costo_ppp
	where
	idproducto = $idproducto
	and estado_disponible = 1
	";
    //echo $consulta;
    $rsant = $adodb_conn->Execute($consulta) or die(errorpg($adodb_conn, $consulta));
    $total_costo_ant = floatval($rsant->fields['total_costo']);
    $costo_ant = floatval($rsant->fields['costo']);
    $total_costo_mov = floatval($cantidad_aumentar * $costo_unitario);
    $disponible_ant = floatval($rsant->fields['disponible']);
    $disponible_new = floatval($disponible_ant + $cantidad_aumentar);
    $costo_new = floatval(($total_costo_ant + $total_costo_mov) / $disponible_new);
    //echo $total_costo_ant;
    $total_costo_new = $costo_new * $disponible_new;
    if ($disponible_new > 0) {
        $estado_disponible = 1;
    } else {
        $estado_disponible = 2;
    }

    // cera todos los registros anteriores
    $consulta = "
	update depositos_stock_costo_ppp 
	set 
	disponible = 0, 
	estado_disponible = 2 
	where 
	idproducto = $idproducto 
	and estado_disponible = 1
	";
    $adodb_conn->Execute($consulta) or die(errorpg($adodb_conn, $consulta));
    // inserta nuevo registro
    $consulta = "
	INSERT INTO depositos_stock_costo_ppp
	(
    iddeposito, idproducto, cantidad, disponible, fecha_tanda, 
    fecha_tanda_completa, costo, total_costo, estado_disponible, ficticio, idcompra, idproducido
	)
    VALUES
	(0, $idproducto, $disponible_new, $disponible_new, '$fecha_tanda_completa', '$fecha_tanda_completa', $costo_new, $total_costo_new, $estado_disponible, 0,$idcompra,$idproducido);
	";
    $adodb_conn->Execute($consulta) or die(errorpg($adodb_conn, $consulta));


}

function stock_costo_descontar_ppp($parametros_array_sc)
{

    // variables globales
    global $conexion;

    // recibe parametros
    $idproducto = intval($parametros_array_sc['idproducto']);
    $cantidad_descontar = floatval($parametros_array_sc['cantidad_descontar']);
    $iddeposito = intval($parametros_array_sc['iddeposito']);
    $fecha_tanda_completa = date("Y-m-d H:i:s", strtotime($parametros_array_sc['fecha_tanda_completa']));
    $idcompra = intval($parametros_array_sc['idcompra']);
    $idventa = intval($parametros_array_sc['idventa']);


    // se usan solo para devoluciones/anulaciones de compra
    $idcompra = intval($idcompra);
    if ($idcompra > 0) {
        $consulta = "
		select * 
		from facturas_proveedores_compras 
		where 
		id_factura = $idcompra 
		and idproducto = $idproducto 
		limit 1
		";
        $rscosto = $adodb_conn->Execute($consulta) or die(errorpg($adodb_conn, $consulta));
        $costo_unitario = floatval($rscosto->fields['precio']);
    }


    // obtiene el costo total
    $consulta = "
	select sum(costo) as costo, sum(total_costo)  as total_costo, sum(disponible) as disponible
	from depositos_stock_costo_ppp
	where
	idproducto = $idproducto
	and estado_disponible = 1
	";
    $rsant = $adodb_conn->Execute($consulta) or die(errorpg($adodb_conn, $consulta));
    $total_costo_ant = floatval($rsant->fields['total_costo']);
    $costo_ant = floatval($rsant->fields['costo']);
    if ($idcompra > 0) {
        $total_costo_mov = floatval($cantidad_descontar * $costo_unitario);
        $costo_mov = $costo_unitario;
    } else {
        $total_costo_mov = floatval($cantidad_descontar * $costo_ant);
        $costo_mov = $costo_ant;
    }
    $disponible_ant = floatval($rsant->fields['disponible']);
    $disponible_new = floatval($disponible_ant - $cantidad_descontar);
    // para evitar error de division by cero y que arroja NaN
    if (($total_costo_ant - $total_costo_mov) <= 0) {
        $costo_new = 0;
    } else {
        $costo_new = floatval(($total_costo_ant - $total_costo_mov) / $disponible_new);
    }
    // evitar error de infinito
    if (is_infinite($costo_new)) {
        $costo_new = 0;
    }
    // para evitar error Nan
    if (is_nan($costo_new)) {
        $costo_new = 0;
    }
    if (floatval($costo_new) <= 0) {
        $costo_new = 0;
    }
    $total_costo_new = floatval($costo_new * $disponible_new);
    if (floatval($total_costo_new) <= 0) {
        $total_costo_new = 0;
    }
    if ($disponible_new > 0) {
        $estado_disponible = 1;
    } else {
        $estado_disponible = 2;
    }


    // cera todos los registros anteriores
    $consulta = "
	update depositos_stock_costo_ppp 
	set 
	disponible = 0, 
	estado_disponible = 2 
	where 
	idproducto = $idproducto 
	and estado_disponible = 1
	";
    $adodb_conn->Execute($consulta) or die(errorpg($adodb_conn, $consulta));
    // inserta nuevo registro
    $consulta = "
	INSERT INTO depositos_stock_costo_ppp
	(
    iddeposito, idproducto, cantidad, disponible, fecha_tanda, 
    fecha_tanda_completa, costo, total_costo, estado_disponible, ficticio, idcompra, idproducido, idventa
	)
    VALUES
	(0, $idproducto, $disponible_new, $disponible_new, '$fecha_tanda_completa', '$fecha_tanda_completa', $costo_new, $total_costo_new, $estado_disponible, 0,0,0, $idventa);
	";
    $adodb_conn->Execute($consulta) or die(errorpg($adodb_conn, $consulta));



    return $costo_mov;

}
function consultar_stock_fefo_fecha($parametros_array)
{
    // variables globales
    // variables globales
    global $idempresa;
    global $conexion;
    global $ahora;
    global $idusu;
    global $idsucursal;

    // recibe parametros
    $vencimiento = ($parametros_array['vencimiento']);
    $usar_lote = ($parametros_array['usar_lote']);
    $idlistaprecio = ($parametros_array['idlistaprecio']);
    $seladd_lp = $parametros_array['seladd_lp'];
    $total_tmp = "";
    $disponible_valor_select = "";
    $from_join_add = "";
    $joinadd_lp = "";
    $mini = $parametros_array['mini'];
    $whereadd_lp = "";
    $order_vencimiento = "";

    $where_vencimiento = null;
    if (isset($vencimiento) && $usar_lote == "S") {
        $where_vencimiento = " and (DATE_FORMAT(gest_depositos_stock.vencimiento, '%Y-%m-%d') > DATE_FORMAT('$vencimiento', '%Y-%m-%d') or (gest_depositos_stock.vencimiento is null and gest_depositos_stock.lote is null) )";
    }



    // $consulta="SELECT * FROM gest_depositos_stock WHERE gest_depositos_stock.idproducto = 2 and DATE_FORMAT(vencimiento, '%Y-%m-%d') > DATE_FORMAT($vencimiento, '%Y-%m-%d')";
    // $rs_producto_lote=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    // $costo_unitario=floatval($rs_producto_lote->fields['precio']);
    // return $rs_producto_lote;

    $seladd_lp = " productos_sucursales.precio as p1 ";

    if ($idlistaprecio > 0) {
        $joinadd_lp = " inner join productos_listaprecios on productos_listaprecios.idproducto = productos.idprod_serial ";
        $whereadd_lp = "
		and productos_listaprecios.idsucursal = $idsucursal 
		and productos_listaprecios.idlistaprecio = $idlistaprecio 
		and productos_listaprecios.estado = 1
		";
        $seladd_lp = " productos_listaprecios.precio as p1";
    }




    if ($usar_lote == "S") {
        $total_tmp = "(
			select 
			sum(cantidad) as total
			from tmp_ventares 
			where 
			tmp_ventares.registrado = 'N'
			and tmp_ventares.usuario = $idusu
			and tmp_ventares.borrado='N'
			and tmp_ventares.finalizado = 'N'
			and tmp_ventares.idproducto = productos.idprod_serial
			and tmp_ventares.lote = gest_depositos_stock.lote
			and DATE_FORMAT(tmp_ventares.vencimiento, '%Y-%m-%d') = gest_depositos_stock.vencimiento
			and tmp_ventares.idempresa = $idempresa
			and tmp_ventares.idsucursal = $idsucursal
			) as total";
        $from_join_add = "gest_depositos_stock
		INNER JOIN insumos_lista on insumos_lista.idinsumo = gest_depositos_stock.idproducto
		INNER JOIN productos on productos.idprod = insumos_lista.idproducto ";
        $disponible_valor_select = " SUM(gest_depositos_stock.disponible) as disponible,  lote, vencimiento, idregseriedptostk, gest_depositos_stock.idproducto as idinsumo ";
        $order_vencimiento = " ,  gest_depositos_stock.vencimiento";
    } else {
        $total_tmp = "(
			select 
			sum(cantidad) as total
			from tmp_ventares 
			where 
			tmp_ventares.registrado = 'N'
			and tmp_ventares.usuario = $idusu
			and tmp_ventares.borrado='N'
			and tmp_ventares.finalizado = 'N'
			and tmp_ventares.idproducto = productos.idprod_serial
			and tmp_ventares.idempresa = $idempresa
			and tmp_ventares.idsucursal = $idsucursal
			) as total";
        $from_join_add = "productos ";
        $disponible_valor_select = " (
			select sum(gest_depositos_stock_gral.disponible)
			from insumos_lista
			inner join gest_depositos_stock_gral on insumos_lista.idinsumo = gest_depositos_stock_gral.idproducto
			where 
			productos.idprod_serial = insumos_lista.idproducto
			and gest_depositos_stock_gral.iddeposito = (
														select gest_depositos.iddeposito 
														from gest_depositos 
														where 
														tiposala = 2 
														and idsucursal = $idsucursal
														)
			) as disponible, (select insumos_lista.idinsumo from  insumos_lista  where 
			productos.idprod_serial = insumos_lista.idproducto) as idinsumo ";
    }



    $buscar = "Select productos.*, $seladd_lp,
				$total_tmp
					,
					(
						SELECT excepciones_producto.venta_sin_stock 
						FROM excepciones_producto 
						WHERE excepciones_producto.idproducto = productos.idprod_serial
					) as venta_sin_stock
					,
					$disponible_valor_select
					
	from  $from_join_add 
	inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
	$joinadd_lp
	where 

	productos.descripcion like '%$mini%'
	and productos.borrado = 'N'
	and productos.idempresa = $idempresa
	and idprod_serial not in (SELECT excepciones_producto.idproducto FROM excepciones_producto WHERE excepciones_producto.venta = 2)

	and productos_sucursales.idsucursal = $idsucursal 
	and productos_sucursales.idempresa = $idempresa
	and productos_sucursales.activo_suc = 1
	$where_vencimiento
	$whereadd_lp
	GROUP BY idprod
	order by productos.descripcion asc  $order_vencimiento
	limit 50";

    $rsbusq = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    return $rsbusq;


}
