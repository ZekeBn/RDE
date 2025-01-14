 <?php
/*-----------------------------------
11/07/2024: Se incorpora uso de tarjeta

------------------------------------*/
function comanda_cocina_consolidado($parametros_array)
{
    global $conexion;
    global $saltolinea;
    global $ahora;
    global $idempresa;
    global $nombresucursal;
    global $nombreempresa;



    // inicializar
    $valido = "S";
    $erroes = "";
    $hay_datos = "N";

    // datos entrada
    $idimpresoratk = $parametros_array['idimpresoratk'];
    $idpedido = $parametros_array['idpedido'];
    $idmesa = $parametros_array['idmesa'];
    $impresor_tip = $parametros_array['impresor_tip'];
    $muestra_suc = 'S';
    $idventa = intval($parametros_array['v']);



    // validaciones
    if (intval($idimpresoratk) == 0) {
        $errores .= "-No indico la impresora.".$saltolinea;
        $valido = "N";
    }
    if (intval($idpedido) == 0 && intval($idmesa) == 0) {
        $errores .= "-No indico el pedido.".$saltolinea;
        $valido = "N";
    }

    //Preferencias
    $consulta = "Select * from preferencias where idempresa=$idempresa";
    $rprf = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $datos_fac_preticket = $rprf->fields['datos_fac_preticket'];
    $propina_preticket = $rprf->fields['propina_preticket'];

    //Si usa lista de zonas
    $usalistazonas = trim($rprf->fields['usa_lista_zonas']);

    // preferencias impresora
    if (intval($idimpresoratk) > 0) {
        $consulta = "
        SELECT * 
        FROM impresoratk 
        where 
        idimpresoratk = $idimpresoratk
        limit 1
        ";
        $rsimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $leyenda_credito = $rsimp->fields['leyenda_credito'];
        $datos_fiscal = $rsimp->fields['datos_fiscal'];
        $muestra_nombre = $rsimp->fields['muestra_nombre'];
        $usa_chapa = $rsimp->fields['usa_chapa'];
        $usa_obs = $rsimp->fields['usa_obs'];
        $usa_precio = $rsimp->fields['usa_precio'];
        $usa_total = $rsimp->fields['usa_total'];
        $usa_nombreemp = $rsimp->fields['usa_nombreemp'];
        $usa_totaldiscreto = $rsimp->fields['usa_totaldiscreto'];
        $txt_codvta = $rsimp->fields['txt_codvta'];
        $cabecera_pagina = trim($rsimp->fields['cabecera_pagina']);
        $pie_pagina = $rsimp->fields['pie_pagina'];
        $idsucursal = $rsimp->fields['idsucursal'];

        //Nuevos parametros
        $muestra_idpedido = $rsimp->fields['muestra_idpedido'];
        $muestra_idventa = $rsimp->fields['muestra_idventa'];
        $muestra_fechapedido = $rsimp->fields['muestra_fecha_pedido'];
        $muestra_fechaimpresion = $rsimp->fields['muestra_fecha_impre'];
        $muestra_sucursal_cabeza = $rsimp->fields['muestra_sucursal_cabeza'];
        $muestra_sucursal_pie = $rsimp->fields['muestra_sucursal_pie'];
        $muestra_operador = $rsimp->fields['mostrar_operador'];
        $firma_credito = trim($rsimp->fields['firma_credito']);
        $cabezacuerpo = trim($rsimp->fields['muestra_cabecera_cuerpo']);
        $usa_enfasis = trim($rsimp->fields['usa_enfasis']);




    }


    $enfasis = "";
    if ($usa_enfasis == 'S') {
        $enfasis = "<BIG>";
    }

    // valicaciones condicionadas
    if ($impresor_tip == "") {
        $errores .= "-No indico el tipo de impresion.".$saltolinea;
        $valido = "N";
    } else {
        if ($impresor_tip == "MES") {
            if ($idmesa == 0) {
                $errores .= "-No indico el numero de mesa.".$saltolinea;
                $valido = "N";
            }
        }
        if ($impresor_tip == "REI") {
            if ($idpedido == 0) {
                $errores .= "-No indico el codigo de pedido.".$saltolinea;
                $valido = "N";
            }
        }
    }


    // conversiones
    // forzar impresor a mesa
    if ($idmesa > 0) {
        $impresor_tip = "MES";
    }
    if ($datos_fiscal != 'S') {
        $datos_fiscal = "N";
    }
    if ($consolida == "") {
        $consolida = "N";
    }
    $leyenda = corta_nombreempresa(trim($leyenda), 40);

    if ($usa_nombreemp == "S") {
        $nombreempresa_centrado = $saltolinea.corta_nombreempresa($nombreempresa);
    } else {
        $nombreempresa_centrado = "";
    }
    $nombreempresa_centrado = $nombreempresa_centrado;
    //echo $nombreempresa_centrado;exit;
    if ($muestra_suc == 'S') {
        $sucursaltxt = $saltolinea."SUCURSAL: ".texto_tk(trim($nombresucursal), 30);
    }
    $fechaimp = date("d/m/Y H:i:s", strtotime($ahora));

    // filtros por tipo de impresor
    $filtroadd = "";
    $leyenda = "";
    // cocina
    if ($impresor_tip == 'COC') {
        $leyenda = "$cabecera_pagina";
        $filtroadd = "
        and productos.idprod_serial in (select idproducto from producto_impresora where idimpresora = $idimpresoratk and idempresa = $idempresa)
        ";
        $selectadd = "";
    }
    // caja
    if ($impresor_tip == 'CAJ') {
        $leyenda = "$cabecera_pagina";
        if ($idventa > 0) {
            $filtroaddcab .= " and tmp_ventares_cab.idventa = $idventa ";
        }
        $selectadd = "";
    }
    // mesa
    if ($impresor_tip == 'MES') {
        $leyenda = "RESUMEN DE MESA";
        $filtroadd = "
        
        ";
        $filtroaddcab = "
    
        ";
        $selectadd = " sum(monto) as monto, ";
        if ($idventa > 0) {
            $filtroadd2 = "
            and registrado = 'S'
            and idmesa=$idmesa
            ";
            $filtroaddcab .= " and tmp_ventares_cab.idventa = $idventa ";
        } else {
            $filtroadd2 = "
            and registrado = 'N'
            and idmesa=$idmesa
            ";
        }
    }
    // reimpresion
    if ($impresor_tip == 'REI') {
        $leyenda = "$cabecera_pagina - REIMPRESO";

        $idtmpventares_cab = $idpedido;
        $filtroadd = "
        and tmp_ventares.idtmpventares_cab = $idtmpventares_cab
        ";
        $filtroadd_bak = "
        and tmp_ventares_bak.idtmpventares_cab = $idtmpventares_cab
        ";
        $filtroaddcab = "";
        $selectadd = "";
    }
    // centrar leyenda
    $leyenda = corta_nombreempresa($leyenda, 40);
    if ($idpedido > 0) {
        $whereadd = " and tmp_ventares_cab.idtmpventares_cab = $idpedido ";
    }

    // primer valido
    if ($valido == 'S') {
        // cabecera
        $consulta = "
        select *, $selectadd (select tipo_venta from ventas where ventas.idventa = tmp_ventares_cab.idventa limit 1) as vtacredito,
        (select numero_tarjeta from tarjeta_delivery where idtarjetadelivery=tmp_ventares_cab.idtarjetadelivery) as numero_tar_delivery,
        (select idadherente from ventas where ventas.idventa = tmp_ventares_cab.idventa limit 1) as idadherente,
        (select descneto from ventas where ventas.idventa = tmp_ventares_cab.idventa and tmp_ventares_cab.idventa is not null limit 1) as descuento,
        (select razon_social from ventas where ventas.idventa = tmp_ventares_cab.idventa and tmp_ventares_cab.idventa is not null limit 1) as razon_social_ven,
        (select ruc from ventas where ventas.idventa = tmp_ventares_cab.idventa and tmp_ventares_cab.idventa is not null limit 1) as ruc_ven,
        (select canal_venta from canal_venta where idcanalventa  = tmp_ventares_cab.idcanalventa) as canal_venta
        from tmp_ventares_cab
        where
        idsucursal = $idsucursal
        and finalizado = 'S'
        and estado <> 6
        $whereadd
        $filtroaddcab
        $filtroadd2
        and tmp_ventares_cab.idtmpventares_cab in (
            select tmp_ventares.idtmpventares_cab
            from tmp_ventares 
            inner join productos on tmp_ventares.idproducto = productos.idprod_serial
            where 
            tmp_ventares.borrado = 'N'
            and tmp_ventares.finalizado = 'S'
            and tmp_ventares.idsucursal = $idsucursal
            $filtroadd
            and idtmpventaresagregado is null
            ORDER BY descripcion asc
        )
        order by idtmpventares_cab asc
        limit 1
        ";
        //echo $consulta;
        $rscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idtmpventares_cab = intval($rscab->fields['idtmpventares_cab']);
        if ($idtmpventares_cab > 0) {
            $hay_datos = "S";
            // si no encuentra busca en el bak
        } else {
            $consulta = "
            select *, $selectadd (select tipo_venta from ventas where ventas.idventa = tmp_ventares_cab.idventa limit 1) as vtacredito,
            (select numero_tarjeta from tarjeta_delivery where idtarjetadelivery=tmp_ventares_cab.idtarjetadelivery) as numero_tar_delivery,
            (select idadherente from ventas where ventas.idventa = tmp_ventares_cab.idventa limit 1) as idadherente,
            (select descneto from ventas where ventas.idventa = tmp_ventares_cab.idventa and tmp_ventares_cab.idventa is not null limit 1) as descuento,
            (select razon_social from ventas where ventas.idventa = tmp_ventares_cab.idventa and tmp_ventares_cab.idventa is not null limit 1) as razon_social_ven,
            (select ruc from ventas where ventas.idventa = tmp_ventares_cab.idventa and tmp_ventares_cab.idventa is not null limit 1) as ruc_ven,
            (select canal_venta from canal_venta where idcanalventa  = tmp_ventares_cab.idcanalventa) as canal_venta
            from tmp_ventares_cab
            where
            idsucursal = $idsucursal
            and finalizado = 'S'
            and estado <> 6
            $whereadd
            $filtroaddcab
            $filtroadd2
            and tmp_ventares_cab.idtmpventares_cab in (
                select tmp_ventares_bak.idtmpventares_cab
                from tmp_ventares_bak
                inner join productos on tmp_ventares_bak.idproducto = productos.idprod_serial
                where 
                tmp_ventares_bak.borrado = 'N'
                and tmp_ventares_bak.finalizado = 'S'
                and tmp_ventares_bak.idsucursal = $idsucursal
                $filtroadd_bak
                and idtmpventaresagregado is null
                ORDER BY descripcion asc
            )
            order by idtmpventares_cab asc
            limit 1
            ";
            //echo $consulta;
            $rscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            if ($idtmpventares_cab > 0) {
                $hay_datos = "S";
            }
        }
    }
    // si ademas de ser valido contiene datos
    if ($valido == 'S' && $hay_datos == 'S') {

        $vtaid = intval($rscab->fields['idventa']);
        $id = intval($rscab->fields['idtmpventares_cab']);
        $idadherente = intval($rscab->fields['idadherente']);
        $descuento = intval($rscab->fields['descuento']);
        $canal_venta = $rscab->fields['canal_venta'];


        if ($descuento > 0) {
            $desctxt = $saltolinea."DESCUENTO: ".formatomoneda($descuento);
        }

        // sub filtros
        if ($impresor_tip == 'COC') {
            $subfiltroadd = "and tmp_ventares.idtmpventares_cab = $id";
        }
        if ($impresor_tip == 'CAJ') {
            $subfiltroadd = "and tmp_ventares.idtmpventares_cab = $id";
        }
        if ($impresor_tip == 'MES') {
            if ($idventa > 0) {
                $subfiltroadd = "
                and tmp_ventares.idtmpventares_cab in (
                                                        select idtmpventares_cab
                                                        from tmp_ventares_cab
                                                        where
                                                        idsucursal = $idsucursal
                                                        and idempresa = $idempresa
                                                        and finalizado = 'S'
                                                        and registrado = 'S'
                                                        and estado = 3
                                                        and idmesa=$idmesa
                                                        and idventa = $idventa
                                                       )
                ";
            } else {
                $subfiltroadd = "
                and tmp_ventares.idtmpventares_cab in (
                                                        select idtmpventares_cab
                                                        from tmp_ventares_cab
                                                        where
                                                        idsucursal = $idsucursal
                                                        and idempresa = $idempresa
                                                        and finalizado = 'S'
                                                        and registrado = 'N'
                                                        and estado = 1
                                                        and idmesa=$idmesa
                                                       )
                ";

            }
        }
        if ($impresor_tip == 'REI') {
            $subfiltroadd = "and tmp_ventares.idtmpventares_cab = $id";
        }

        // datos del adherente
        if ($idadherente > 0) {
            $consulta = "SELECT * FROM adherentes where idadherente = $idadherente";
            $rsadh = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $nombre_adherente = $saltolinea."ADH: ".texto_tk(trim($rsadh->fields['nomape']), 35);
            $codadherente = "";
            if ($rprf->fields['imprime_cod_adherente'] == 'S') {

                $consulta = "SELECT * FROM clientes_codigos where idadherente = $idadherente";
                $rsadhcod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $codadherente = $saltolinea."COD ADH: ".trim($rsadhcod->fields['us_cod']).$saltolinea;
                //echo $codadherente;exit;
                //$nombre_adherente=$nombre_adherente.'';
            }
        }




        // datos utiles
        $fechapedido = date("d/m/Y H:i:s", strtotime($rscab->fields['fechahora']));
        $razon_social = trim(texto_tk($rscab->fields['razon_social'], 28));
        $ruc = texto_tk($rscab->fields['ruc'], 28);
        if (intval($vtaid) > 0 or intval($idventa) > 0) {
            $razon_social = trim(texto_tk($rscab->fields['razon_social_ven'], 28));
            $ruc = texto_tk($rscab->fields['ruc_ven'], 28);
        }

        $datos_fiscales = "Razon SOC.: $razon_social".$saltolinea;
        $datos_fiscales .= "RUC NRO.  : $ruc".$saltolinea;
        if ($datos_fiscal == 'N') {
            $datos_fiscales = "";
        }
        if ($muestra_nombre == 'S') {
            if ($codadherente == '') {
                $datos_fiscales .= "CLIENTE: $razon_social".$saltolinea;
            }
        }

        // si es venta a credito
        $vtacredito = intval($rscab->fields['vtacredito']);
        if ($vtacredito == 2) {
            $creditotxt = "
----------------------------------------
        *** VENTA A CREDITO ***   
      RECONOZCO Y PAGARE EL MONTO       
           DE ESTA OPERACION        


FIRMA:..................................
ACLARACION: $razon_social";

        } else {
            $creditotxt = "";
        }
        if ($firma_credito != 'S') {
            $creditotxt = "";
        }
        // si no quiere leyenda para credito
        if ($leyenda_credito == 'N') {
            $creditotxt = "";
        }

        if ($muestra_sucursal_cabeza == 'S') {
            $leyenda .= $sucursaltxt;
            $muestra_sucursal_pie = 'N';
        } else {
            $leyenda .= "";
        }
        $leyenda = trim($leyenda);
        $texto = $nombreempresa_centrado.$saltolinea;
        if (trim($leyenda) != '') {
            $texto .= $leyenda.$saltolinea;
        }
        //print_r($rscab);exit;
        if ($rscab->fields['delivery'] == "S") {
            //echo 'ss';exit;
            $idtarjeta_delivery = intval($rscab->fields['numero_tar_delivery']);
            if ($idtarjeta_delivery > 0) {
                $addcabeza = "-->TARJETA DELIVERY NUMERO: $idtarjeta_delivery<--".$saltolinea;
            } else {
                $addcabeza = "-->PEDIDO P/ DELIVERY<--".$saltolinea;
            }
            $texto .= $enfasis."$addcabeza";
        }
        if ($muestra_idventa == 'S' or $muestra_idpedido == 'S') {
            if ($muestra_idpedido == 'S') {
                $texto .= $enfasis."PEDIDO N° $id";
            }
            if ($muestra_idventa == 'S') {
                if ($muestra_idpedido == 'S') {
                    $texto .= " | ";
                }
                $texto .= "$txt_codvta: $vtaid ";
            }
            //$texto.=$saltolinea;
        }
        if (($muestra_idpedido != 'S') && ($muestra_idventa != 'S')) {
            $lineainf = "";
        } else {
            $lineainf = $saltolinea."----------------------------------------".$saltolinea;
            $texto .= $lineainf;
        }
        if ($muestra_fechapedido == 'S') {
            $texto .= "FECHA PED : $fechapedido".$saltolinea;
        }
        if ($muestra_fechaimpresion == 'S') {
            $texto .= "FECHA IMP : $fechaimp".$saltolinea;
        }
        if ($canal_venta != '') {
            $texto .= $enfasis."CANAL VENTA: ".$canal_venta.$saltolinea;
        }
        if ($datos_fiscales != '') {
            $texto .= $datos_fiscales;
        }
        if ($nombre_adherente != '') {
            $texto .= $nombre_adherente.' ';
        }
        if ($codadherente != '') {
            $texto .= $codadherente.'';
        }

        if ($cabezacuerpo == 'S') {
            $texto .= "----------------------------------------".$saltolinea;
            $texto .= "N  |PRODUCTOS";
        }
        $texto .= "----------------------------------------";

        // detalle sin agregados/sacados
        $consulta = "
        select tmp_ventares.*, productos.descripcion, sum(cantidad) as total, sum(precio) as totalprecio, sum(subtotal) as subtotal,
        (select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
        (select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado
        from tmp_ventares 
        inner join productos on tmp_ventares.idproducto = productos.idprod_serial
        where 
        tmp_ventares.finalizado = 'S'
        and tmp_ventares.borrado = 'N'
        $subfiltroadd
        and tmp_ventares.idsucursal = $idsucursal
        and tmp_ventares.idempresa = $idempresa
        $filtroadd
        and tmp_ventares.idtipoproducto = 1
        and idtmpventaresagregado is null
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
        group by descripcion, receta_cambiada
        ORDER BY tmp_ventares.idventatmp asc
        ";
        //echo $consulta;
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // sin agregados
        while (!$rs->EOF) {
            $idventatmp = $rs->fields['idventatmp'];
            $idproducto = $rs->fields['idproducto'];
            if ($rs->fields['tipo_plato'] != '') {
                $tipoplato = trim($rs->fields['tipo_plato']." | ");
            }
            $texto .= $saltolinea.$enfasis."".agregaespacio(texto_tk(formatomoneda($rs->fields['total'], 4, 'N'), 6), 6).'|'.texto_tk($tipoplato.$rs->fields['descripcion'], 34);
            //$texto.=$saltolinea.$enfasis."".agregaespacio(texto_tk(formatomoneda($rs->fields['total'],3,'N'),3),3).'|'.texto_tk($tipoplato.$rs->fields['descripcion'],37);
            if ($usa_precio == 'S') {
                $texto .= $saltolinea."   Gs.".texto_tk(formatomoneda($rs->fields['subtotal']), 30);
            }
            if (trim($rs->fields['observacion']) != '') {
                //$texto.=$saltolinea.$enfasis."   *OBS: ".texto_tk($rs->fields['observacion'],35).$saltolinea;
                $texto .= $saltolinea.$enfasis."   *OBS: ".$rs->fields['observacion'].$saltolinea;
            }
            //$texto=trim($texto);
            $rs->MoveNext();
        }

        // detalle con agregados/sacados
        $consulta = "
        select tmp_ventares.*, productos.descripcion, cantidad as total, precio as totalprecio, subtotal as subtotal,
        (select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
        (select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado
        from tmp_ventares 
        inner join productos on tmp_ventares.idproducto = productos.idprod_serial
        where 
        tmp_ventares.finalizado = 'S'
        and tmp_ventares.borrado = 'N'
        $subfiltroadd
        and tmp_ventares.idsucursal = $idsucursal
        and tmp_ventares.idempresa = $idempresa
        $filtroadd
        and tmp_ventares.idtipoproducto = 1
        and idtmpventaresagregado is null
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
        ORDER BY tmp_ventares.idventatmp asc
        ";
        //echo $consulta;
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // con agregados
        while (!$rs->EOF) {
            $idventatmp = $rs->fields['idventatmp'];
            $idproducto = $rs->fields['idproducto'];
            if ($rs->fields['tipo_plato'] != '') {
                $tipoplato = trim($rs->fields['tipo_plato']." | ");
            }

            $texto .= $saltolinea.$enfasis."".agregaespacio(texto_tk(formatomoneda($rs->fields['total'], 3, 'N'), 3), 3).'|'.texto_tk($tipoplato.$rs->fields['descripcion'], 37);
            if ($usa_precio == 'S') {
                $texto .= $saltolinea."   Gs.".texto_tk(formatomoneda($rs->fields['subtotal']), 30);
            }
            //$texto=trim($texto);
            // busca si tiene agregado
            $idvt = $rs->fields['idventatmp'];
            $consulta = "
            select tmp_ventares_agregado.*
            from tmp_ventares_agregado
            where 
            idventatmp = $idventatmp
            order by alias desc
            ";
            $rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // genera agregados si tiene
            if (trim($rsag->fields['alias']) != '') {
                $texto .= $saltolinea."   > AGREGADOS:".$saltolinea;
                while (!$rsag->EOF) {
                    $texto .= $enfasis."    + ".texto_tk($rsag->fields['alias'], 36).$saltolinea;
                    $texto .= "      Gs.".texto_tk(formatomoneda($rsag->fields['precio_adicional'] * $rsag->fields['cantidad']), 30).$saltolinea;
                    $rsag->MoveNext();
                }
                $texto = substr($texto, 0, -1);
            }
            //$texto=trim($texto);

            // busca si tiene sacados
            $consulta = "
            select tmp_ventares_sacado.*
            from tmp_ventares_sacado
            where 
            idventatmp = $idventatmp
            order by alias desc
            ";
            $rssac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // genera sacados si tiene
            if (trim($rssac->fields['alias']) != '') {
                $texto .= $saltolinea."   > EXCLUIDOS:".$saltolinea;
                while (!$rssac->EOF) {
                    $texto .= $enfasis."    - ".texto_tk($rssac->fields['alias'], 36).$saltolinea;
                    $rssac->MoveNext();
                }
                $texto = substr($texto, 0, -1);
            }
            if (trim($rs->fields['observacion']) != '') {
                //$texto.=$saltolinea.$enfasis."   *OBS: ".texto_tk($rs->fields['observacion'],35).$saltolinea;
                $texto .= $saltolinea.$enfasis."   *OBS: ".$rs->fields['observacion'].$saltolinea;
            }
            //$texto=trim($texto);
            $rs->MoveNext();
        }


        // combo y combinado
        //and (tmp_ventares.combo = 'S' or tmp_ventares.combinado = 'S' or tmp_ventares.idtipoproducto > 1)
        $consulta = "
        select tmp_ventares.*, tmp_ventares.idtipoproducto, productos.descripcion, tmp_ventares.cantidad as total, tmp_ventares.observacion as observacion,
        (select muestra_grupo_combo from productos WHERE productos.idprod_serial = tmp_ventares.idproducto limit 1) as muestra_grupo_combo
        from tmp_ventares 
        inner join productos on tmp_ventares.idproducto = productos.idprod_serial
        where 
        tmp_ventares.borrado = 'N'
        and tmp_ventares.finalizado = 'S'
        $subfiltroadd
        and tmp_ventares.idempresa = $idempresa
        and tmp_ventares.idsucursal = $idsucursal
        $filtroadd
        and  tmp_ventares.idtipoproducto > 1
        and idtmpventaresagregado is null
        ORDER BY descripcion asc
        ";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        while (!$rs->EOF) {
            $idventatmp = $rs->fields['idventatmp'];
            $idproducto = $rs->fields['idproducto'];
            $muestra_grupo_combo = $rs->fields['muestra_grupo_combo'];
            $texto .= $saltolinea.$enfasis."".agregaespacio(texto_tk(formatomoneda($rs->fields['total'], 3, 'N'), 3), 3).'|'.texto_tk($rs->fields['descripcion'], 37);
            $texto .= $saltolinea."   Gs.".texto_tk(formatomoneda($rs->fields['subtotal']), 30);
            //$texto=trim($texto);
            // busca si es un producto combinado viejo
            if ($rs->fields['idtipoproducto'] == 3) {
                $prod_1 = $rs->fields['idprod_mitad1'];
                $prod_2 = $rs->fields['idprod_mitad2'];
                $consulta = "
                select *
                from productos
                where 
                (idprod_serial = $prod_1 or idprod_serial = $prod_2)
                order by descripcion asc
                ";
                $rspcom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                while (!$rspcom->EOF) {
                    $texto .= $saltolinea.$enfasis."   » MITAD ".texto_tk($rspcom->fields['descripcion'], 31);
                    $rspcom->MoveNext();
                }

            }
            // busca si es un combo
            if ($rs->fields['idtipoproducto'] == 2) {

                if ($muestra_grupo_combo == 'S') {
                    $consulta = "
                    select combos_listas.nombre, productos.descripcion, count(*) as total
                    from productos 
                    inner join tmp_combos_listas on tmp_combos_listas.idproducto = productos.idprod_serial
                    inner join combos_listas on combos_listas.idlistacombo = tmp_combos_listas.idlistacombo
                    where 
                    tmp_combos_listas.idventatmp = $idventatmp
                    group by combos_listas.nombre, productos.descripcion
                    order by combos_listas.idlistacombo asc
                    limit 20
                    ";
                    $rsgrupos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                } else {
                    $consulta = "
                    select count(idprod) as total, descripcion, productos.idprod_serial 
                    from tmp_combos_listas
                    inner join productos on productos.idprod_serial = tmp_combos_listas.idproducto
                    where
                    tmp_combos_listas.idventatmp = $idventatmp
                    group by productos.idprod_serial 
                    order by descripcion asc
                    ";
                    //echo $consulta;
                    $rsgrupos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                }


                while (!$rsgrupos->EOF) {
                    if ($muestra_grupo_combo == 'S') {
                        $nombre_grupo = trim($rsgrupos->fields['nombre']).": ";
                    }
                    $texto .= $saltolinea.$enfasis."   » ".$nombre_grupo.$rsgrupos->fields['total'].' '.texto_tk($rsgrupos->fields['descripcion'], 25);
                    $rsgrupos->MoveNext();
                }

            }
            // busca si es un combinado extendido
            if ($rs->fields['idtipoproducto'] == 4) {
                $consulta = "
                select count(idprod) as total, descripcion, productos.idprod_serial 
                from tmp_combinado_listas
                inner join productos on productos.idprod_serial = tmp_combinado_listas.idproducto_partes
                where
                tmp_combinado_listas.idventatmp = $idventatmp
                group by productos.idprod_serial 
                order by descripcion asc
                ";
                //echo $consulta;
                $rsgrupos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                while (!$rsgrupos->EOF) {
                    $texto .= $saltolinea.$enfasis."   » ".$rsgrupos->fields['total'].' '.texto_tk($rsgrupos->fields['descripcion'], 25);
                    $rsgrupos->MoveNext();
                }

            }

            // busca si tiene agregado
            $idvt = $rs->fields['idventatmp'];
            $consulta = "
            select tmp_ventares_agregado.*
            from tmp_ventares_agregado
            where 
            idventatmp = $idvt
            order by alias desc
            ";
            $rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // genera agregados si tiene
            if (trim($rsag->fields['alias']) != '') {
                $texto .= $saltolinea."   > AGREGADOS:".$saltolinea;
                while (!$rsag->EOF) {
                    $texto .= $enfasis."    + ".texto_tk($rsag->fields['alias'], 36).$saltolinea;
                    $texto .= "      Gs.".texto_tk(formatomoneda($rsag->fields['precio_adicional'] * $rsag->fields['cantidad']), 30).$saltolinea;
                    $rsag->MoveNext();
                }
                $texto = substr($texto, 0, -1);
            }
            //$texto=trim($texto);

            // busca si tiene sacados
            $consulta = "
            select tmp_ventares_sacado.*
            from tmp_ventares_sacado
            where 
            idventatmp = $idvt
            order by alias desc
            ";
            $rssac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // genera sacados si tiene
            if (trim($rssac->fields['alias']) != '') {
                $texto .= $saltolinea."   > EXCLUIDOS:".$saltolinea;
                while (!$rssac->EOF) {
                    $texto .= $enfasis."    - ".texto_tk($rssac->fields['alias'], 36).$saltolinea;
                    $rssac->MoveNext();
                }
                $texto = substr($texto, 0, -1);
            }


            if (trim($rs->fields['observacion']) != '') {
                //$texto.=$saltolinea.$enfasis."   *OBS: ".texto_tk($rs->fields['observacion'],35).$saltolinea;
                $texto .= $saltolinea.$enfasis."   *OBS: ".$rs->fields['observacion'].$saltolinea;
            }
            //$texto=trim($texto);
            $rs->MoveNext();
        }


        // buscar usuario
        $operador = $rscab->fields['idusu'];
        $consulta = "
        select usuario from usuarios where idusu = $operador
        ";
        $rsop = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $operador = $rsop->fields['usuario'];

        // datos de cabecera
        $chapa = texto_tk($rscab->fields['chapa'], 33);
        $telefono_carry = texto_tk($rscab->fields['telefono'], 33);
        $mesa = texto_tk($rscab->fields['idmesa'], 33);
        $mesa = texto_tk($rscab->fields['idmesa'], 33);
        $observacion = $rscab->fields['observacion'];

        // datos cabecera
        $direccion = $rscab->fields['direccion'];
        $telefono = $rscab->fields['telefono'];
        $nombre_deliv = texto_tk($rscab->fields['nombre_deliv'], 26);
        $apellido_deliv = texto_tk($rscab->fields['apellido_deliv'], 26);
        $llevapos = siono($rscab->fields['llevapos']);
        $cambio = formatomoneda($rscab->fields['cambio']);
        $observacion_delivery = $rscab->fields['observacion_delivery'];
        $delivery_costo = formatomoneda($rscab->fields['delivery_costo']);
        $monto = formatomoneda($rscab->fields['monto']);
        $totalpagar = formatomoneda(intval($rscab->fields['monto']) + intval($rscab->fields['delivery_costo']) - intval($descuento));
        $tpagasinformato = intval($rscab->fields['monto']) + intval($rscab->fields['delivery_costo']) - intval($descuento);
        $vuelto = $rscab->fields['cambio'] - ($rscab->fields['monto'] + $rscab->fields['delivery_costo'] - intval($descuento));
        if ($vuelto < 0) {
            $vuelto = 0;
        }
        $vuelto = formatomoneda($vuelto);

        // si no es delivery
        if ($rscab->fields['delivery'] != "S") {
            if ($usa_total == 'S') {
                $texto_total = $saltolinea.$enfasis."TOTAL GLOBAL: $totalpagar";
            }
            if ($usa_totaldiscreto == 'S') {
                $texto_totaldiscreto = $saltolinea."#$totalpagar";
            }

            $totalesg = $desctxt.$texto_total.$texto_totaldiscreto;
            if (trim($totalesg) != '') {
                // totales
                $texto .= "
----------------------------------------$totalesg";
            }
        } // if($rscab->fields['delivery'] != "S"){

        // si es delivery
        if ($rscab->fields['delivery'] == "S") {
            //echo 'ss';exit;
            $idtarjeta_delivery = intval($rscab->fields['numero_tar_delivery']);
            if ($idtarjeta_delivery > 0) {
                $addcabeza = "-->TARJETA DELIVERY NUMERO: $idtarjeta_delivery<--".$saltolinea;
            } else {
                $addcabeza = "-->PEDIDO P/ DELIVERY<--".$saltolinea;
            }
            $iddomicilio = intval($rscab->fields['iddomicilio']);
            //    echo $iddomicilio;exit;
            if (($iddomicilio > 0) && ($usalistazonas == 'S')) {
                $buscar = "Select describezona,obs,iddomicilio,cliente_delivery_dom.referencia
                from 
                zonas_delivery
                inner join cliente_delivery_dom on cliente_delivery_dom.idzonadel=zonas_delivery.idzonadel
                where  cliente_delivery_dom.iddomicilio=$iddomicilio
                limit 1            
                "    ;
                //echo $buscar;exit;
                $rszon = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $addz = trim($rszon->fields['describezona']).' | '.trim($rszon->fields['obs']);
                $addr = '----------------------------------------';
                $referencia = trim($rszon->fields['referencia']);

            }

            $texto .= "
----------------------------------------
$addcabeza
$addz 
$addr
NOMBRE      : $nombre_deliv
APELLIDO    : $apellido_deliv
TELEFONO    : 0$telefono
LLEVAR POS  : $llevapos
TOTAL GLOBAL: $totalpagar
PAGA CON    : $cambio
VUELTO      : $vuelto
DIRECCION   : $direccion
REFERENCIA  : $referencia
OBS. DEL.   : $observacion_delivery";

        } // if($rscab->fields['delivery'] == "S"){

        // si tiene mesa buscar el numero
        if ($mesa > 0) {
            $consulta = " 
            select numero_mesa, nombre
            from mesas
            inner join salon on mesas.idsalon = salon.idsalon
            where 
            idmesa = $mesa
            and salon.idsucursal = $idsucursal
            ";
            $rsmes = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $numeromesa = $rsmes->fields['numero_mesa'];
            $salon = $rsmes->fields['nombre'];
        }


        // si tiene mesa mostrar
        if ($numeromesa != '') {
            $mesa_m = $saltolinea.$enfasis."MESA: ".texto_tk($numeromesa, 34).$saltolinea."SALON: ".texto_tk(strtoupper($salon), 33);
        }

        // si tiene chapa mostrar
        if (trim($chapa) != '') {
            if ($usa_chapa == 'S') {
                if ($chapa != '') {
                    $chapa_m = $saltolinea.$enfasis."NOMBRE: ".$chapa;
                }
                if ($telefono_carry != '') {
                    $telefono_carry_m = $saltolinea.$enfasis."TELEFONO: ".$telefono_carry;
                }
            } else {
                $chapa_m = "";
                $chapa = "";
                $telefono_carry_m = "";
                $telefono_carry = "";
            }
        }
        // si tiene observacion mostrar
        if (trim($observacion) != '') {
            if ($usa_obs == 'S') {
                $observacion_m = $saltolinea.$enfasis."OBSERVACION: ".strtoupper($observacion);
            }
        }

        // si es mesa
        if ($impresor_tip == "MES" && $vtaid == 0) {
            $textomesa = "";
            if ($datos_fac_preticket == 'S') {
                $textomesa .= "RUC:____________________________________".$saltolinea;
                $textomesa .= "RAZON SOCIAL:___________________________".$saltolinea;
            }
            if ($propina_preticket == 'S') {
                $textomesa .= "PROPINA:________________________________".$saltolinea;
            }
            if ($datos_fac_preticket == 'S' or $propina_preticket == 'S') {
                $textomesa .= "----------------------------------------".$saltolinea;
            }
        }

        // si es una venta
        if ($vtaid > 0) {
            $idventa = $vtaid;
            $consulta = "
        select * from preferencias_caja limit 1;
        ";
            $rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $muestra_formapago = trim($rsprefcaj->fields['muestra_formapago']);
            //mostrar cotizacion , monto y vuelto recibidos
            $muestra_cotizacion = trim($rsprefcaj->fields['usa_cotizacion']);
            $muestra_recibevuelto = trim($rsprefcaj->fields['muestrarecibe']);

            $buscar = "Select vuelto,recibido from ventas where idventa=$vtaid ";
            $rsvte = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

            if ($muestra_cotizacion == 'S') {
                //Mostraremos la cotizacion en otras monedas
                // muestra solo las monedas con cotizacion cargada en el dia
                $ahorad = date("Y-m-d");
                $consulta = "
                select *,
                        (
                        select cotizaciones.cotizacion
                        from cotizaciones
                        where 
                        cotizaciones.estado = 1 
                        and date(cotizaciones.fecha) = '$ahorad'
                        and tipo_moneda.idtipo = cotizaciones.tipo_moneda
                        order by cotizaciones.fecha desc
                        limit 1
                        ) as cotizacion
                from tipo_moneda 
                where
                estado = 1
                and borrable = 'S'
                and 
                (
                    (
                        borrable = 'N'
                    ) 
                    or  
                    (
                        tipo_moneda.idtipo in 
                        (
                        select cotizaciones.tipo_moneda 
                        from cotizaciones
                        where 
                        cotizaciones.estado = 1 
                        and date(cotizaciones.fecha) = '$ahorad'
                        )
                    )
                )
                order by borrable ASC, descripcion asc
                ";
                $rsmoneda = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $tmoneda = $rsmoneda->RecordCount();
                if ($tmoneda > 0) {

                    $texto .= $saltolinea.'------------OTRAS MONEDAS---------------'.$saltolinea;
                    while (!$rsmoneda->EOF) {
                        $coti = formatomoneda($rsmoneda->fields['cotizacion'], 2, 'N');
                        $monto = formatomoneda($tpagasinformato / $rsmoneda->fields['cotizacion'], 2, 'N');
                        $palabra1 = trim($rsmoneda->fields['descripcion'])." x ($coti) : ".$monto;


                        $addlinea = "$palabra1";
                        $texto .= $addlinea.$saltolinea;
                        $rsmoneda->MoveNext();
                    }
                }
                $texto .= '----------------------------------------'.$saltolinea;
            }
            if ($muestra_recibevuelto == 'S') {
                //Mostrar monto recibido y vuelto
                $recibeplata = floatval($rsvte->fields['recibido']);
                $vuelto = floatval($rsvte->fields['vuelto']);
                $addlinea = "Recibido Gs: ".formatomoneda($recibeplata, 4, 'N')." -> Vuelto : ".formatomoneda($vuelto, 4, 'N');
                $texto .= $addlinea.$saltolinea;
            }




            // si la preferencia dice que muestre
            if ($muestra_formapago == 'S') {

                $consulta = "
            SELECT formas_pago.descripcion as formapago, gest_pagos_det.monto_pago_det
            FROM gest_pagos_det
            inner join gest_pagos on gest_pagos.idpago = gest_pagos_det.idpago
            inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
            where 
            gest_pagos.idventa = $idventa
            ";
                $rsfpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $tpag = $rsfpag->RecordCount();
                if ($tpag > 0) {
                    $fpagotxt .= $saltolinea.'----------------------------------------'.$saltolinea;
                    $fpagotxt .= 'Pagos:'.$saltolinea;
                    while (!$rsfpag->EOF) {
                        $fpagotxt .= agregaespacio(strtoupper($rsfpag->fields['formapago']), 22).': '.agregaespacio_tk(formatomoneda($rsfpag->fields['monto_pago_det']), 16, 'der', 'S').$saltolinea;
                        $rsfpag->MoveNext();
                    }

                    $texto .= $saltolinea.trim($fpagotxt);
                }
            }
        }
        if ($muestra_operador == 'S') {

            $opertxt = "OPERADOR: $operador";

        }
        if (($opertxt != '') or ($mesaychapa != '')) {
            $texto .= "
----------------------------------------
$opertxt $mesaychapa";

        }

        $texto .= $mesa_m;
        $texto .= $chapa_m;
        $texto .= $telefono_carry_m;
        $texto .= $observacion_m;
        $texto .= $creditotxt;
        if ($muestra_sucursal_pie == 'S') {
            $texto .= $sucursaltxt;
        }
        $texto .= "
----------------------------------------
$textomesa$pie_pagina
";



    } // if($valido == 'S'){
    //echo $texto;exit;

    $texto = wordwrap($texto, 40, $saltolinea, true);

    $res = [
        'valido' => $valido,
        'errores' => $errores,
        'hay_datos' => $hay_datos,
        'ticket' => $texto
    ];


    return $res;
}
function comanda_cocina_consolidado_anul($parametros_array)
{
    global $conexion;
    global $saltolinea;
    global $ahora;
    global $idempresa;
    global $nombresucursal;
    global $nombreempresa;



    // inicializar
    $valido = "S";
    $erroes = "";
    $hay_datos = "N";

    // datos entrada
    $idimpresoratk = $parametros_array['idimpresoratk'];
    $idpedido = $parametros_array['idpedido'];
    $idmesa = $parametros_array['idmesa'];
    $impresor_tip = $parametros_array['impresor_tip'];
    $muestra_suc = 'S';
    $idventa = intval($parametros_array['v']);



    // validaciones
    if (intval($idimpresoratk) == 0) {
        $errores .= "-No indico la impresora.".$saltolinea;
        $valido = "N";
    }
    if (intval($idpedido) == 0 && intval($idmesa) == 0) {
        $errores .= "-No indico el pedido.".$saltolinea;
        $valido = "N";
    }

    //Preferencias
    $consulta = "Select * from preferencias where idempresa=$idempresa";
    $rprf = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $datos_fac_preticket = $rprf->fields['datos_fac_preticket'];
    $propina_preticket = $rprf->fields['propina_preticket'];

    //Si usa lista de zonas
    $usalistazonas = trim($rprf->fields['usa_lista_zonas']);

    // preferencias impresora
    if (intval($idimpresoratk) > 0) {
        $consulta = "
        SELECT * 
        FROM impresoratk 
        where 
        idimpresoratk = $idimpresoratk
        limit 1
        ";
        $rsimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $leyenda_credito = $rsimp->fields['leyenda_credito'];
        $datos_fiscal = $rsimp->fields['datos_fiscal'];
        $muestra_nombre = $rsimp->fields['muestra_nombre'];
        $usa_chapa = $rsimp->fields['usa_chapa'];
        $usa_obs = $rsimp->fields['usa_obs'];
        $usa_precio = $rsimp->fields['usa_precio'];
        $usa_total = $rsimp->fields['usa_total'];
        $usa_nombreemp = $rsimp->fields['usa_nombreemp'];
        $usa_totaldiscreto = $rsimp->fields['usa_totaldiscreto'];
        $txt_codvta = $rsimp->fields['txt_codvta'];
        $cabecera_pagina = trim($rsimp->fields['cabecera_pagina']);
        $pie_pagina = $rsimp->fields['pie_pagina'];
        $idsucursal = $rsimp->fields['idsucursal'];

        //Nuevos parametros
        $muestra_idpedido = $rsimp->fields['muestra_idpedido'];
        $muestra_idventa = $rsimp->fields['muestra_idventa'];
        $muestra_fechapedido = $rsimp->fields['muestra_fecha_pedido'];
        $muestra_fechaimpresion = $rsimp->fields['muestra_fecha_impre'];
        $muestra_sucursal_cabeza = $rsimp->fields['muestra_sucursal_cabeza'];
        $muestra_sucursal_pie = $rsimp->fields['muestra_sucursal_pie'];
        $muestra_operador = $rsimp->fields['mostrar_operador'];
        $firma_credito = trim($rsimp->fields['firma_credito']);
        $cabezacuerpo = trim($rsimp->fields['muestra_cabecera_cuerpo']);
        $usa_enfasis = trim($rsimp->fields['usa_enfasis']);




    }


    $enfasis = "";
    if ($usa_enfasis == 'S') {
        $enfasis = "<BIG>";
    }

    // valicaciones condicionadas
    if ($impresor_tip == "") {
        $errores .= "-No indico el tipo de impresion.".$saltolinea;
        $valido = "N";
    } else {
        if ($impresor_tip == "MES") {
            if ($idmesa == 0) {
                $errores .= "-No indico el numero de mesa.".$saltolinea;
                $valido = "N";
            }
        }
        if ($impresor_tip == "REI") {
            if ($idpedido == 0) {
                $errores .= "-No indico el codigo de pedido.".$saltolinea;
                $valido = "N";
            }
        }
    }


    // conversiones
    // forzar impresor a mesa
    if ($idmesa > 0) {
        $impresor_tip = "MES";
    }
    if ($datos_fiscal != 'S') {
        $datos_fiscal = "N";
    }
    if ($consolida == "") {
        $consolida = "N";
    }
    $leyenda = corta_nombreempresa(trim($leyenda), 40);

    if ($usa_nombreemp == "S") {
        $nombreempresa_centrado = $saltolinea.corta_nombreempresa($nombreempresa);
    } else {
        $nombreempresa_centrado = "";
    }
    $nombreempresa_centrado = $nombreempresa_centrado;
    //echo $nombreempresa_centrado;exit;
    if ($muestra_suc == 'S') {
        $sucursaltxt = $saltolinea."SUCURSAL: ".texto_tk(trim($nombresucursal), 30);
    }
    $fechaimp = date("d/m/Y H:i:s", strtotime($ahora));

    // filtros por tipo de impresor
    $filtroadd = "";
    $leyenda = "";
    // cocina
    if ($impresor_tip == 'COC') {
        $leyenda = "$cabecera_pagina";
        $filtroadd = "
        and productos.idprod_serial in (select idproducto from producto_impresora where idimpresora = $idimpresoratk and idempresa = $idempresa)
        ";
        $selectadd = "";
    }
    // caja
    if ($impresor_tip == 'CAJ') {
        $leyenda = "$cabecera_pagina";
        if ($idventa > 0) {
            $filtroaddcab .= " and tmp_ventares_cab.idventa = $idventa ";
        }
        $selectadd = "";
    }
    // mesa
    if ($impresor_tip == 'MES') {
        $leyenda = "RESUMEN DE MESA";
        $filtroadd = "
        
        ";
        $filtroaddcab = "
    
        ";
        $selectadd = " sum(monto) as monto, ";
        if ($idventa > 0) {
            $filtroadd2 = "
            and registrado = 'S'
            and idmesa=$idmesa
            ";
            $filtroaddcab .= " and tmp_ventares_cab.idventa = $idventa ";
        } else {
            $filtroadd2 = "
            and registrado = 'N'
            and idmesa=$idmesa
            ";
        }
    }
    // reimpresion
    if ($impresor_tip == 'REI') {
        $leyenda = "$cabecera_pagina - REIMPRESO";

        $idtmpventares_cab = $idpedido;
        $filtroadd = "
        and tmp_ventares.idtmpventares_cab = $idtmpventares_cab
        ";
        $filtroaddcab = "";
        $selectadd = "";
    }
    $leyenda = '';
    // centrar leyenda
    $leyenda = corta_nombreempresa($leyenda, 40);
    if ($idpedido > 0) {
        $whereadd = " and tmp_ventares_cab.idtmpventares_cab = $idpedido ";
    }

    // primer valido
    if ($valido == 'S') {
        // cabecera
        $consulta = "
        select *, $selectadd (select tipo_venta from ventas where ventas.idventa = tmp_ventares_cab.idventa limit 1) as vtacredito,
        (select numero_tarjeta from tarjeta_delivery where idtarjetadelivery=tmp_ventares_cab.idtarjetadelivery) as numero_tar_delivery,
        (select idadherente from ventas where ventas.idventa = tmp_ventares_cab.idventa limit 1) as idadherente,
        (select descneto from ventas where ventas.idventa = tmp_ventares_cab.idventa and tmp_ventares_cab.idventa is not null limit 1) as descuento,
        (select razon_social from ventas where ventas.idventa = tmp_ventares_cab.idventa and tmp_ventares_cab.idventa is not null limit 1) as razon_social_ven,
        (select ruc from ventas where ventas.idventa = tmp_ventares_cab.idventa and tmp_ventares_cab.idventa is not null limit 1) as ruc_ven,
        (select canal_venta from canal_venta where idcanalventa  = tmp_ventares_cab.idcanalventa) as canal_venta,
        (select usuario from usuarios where idusu = tmp_ventares_cab.anulado_por) as anulado_por
        from tmp_ventares_cab
        where
        idsucursal = $idsucursal
        and finalizado = 'S'
        and estado = 6
        $whereadd
        $filtroaddcab
        $filtroadd2
        and tmp_ventares_cab.idtmpventares_cab in (
            select tmp_ventares.idtmpventares_cab
            from tmp_ventares 
            inner join productos on tmp_ventares.idproducto = productos.idprod_serial
            where 
            tmp_ventares.borrado = 'S'
            and tmp_ventares.finalizado = 'S'
            and tmp_ventares.idsucursal = $idsucursal
            $filtroadd
            and idtmpventaresagregado is null
            ORDER BY descripcion asc
        )
        order by idtmpventares_cab asc
        limit 1
        ";
        //echo $consulta;
        $rscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idtmpventares_cab = intval($rscab->fields['idtmpventares_cab']);
        if ($idtmpventares_cab > 0) {
            $hay_datos = "S";
        }
    }
    // si ademas de ser valido contiene datos
    if ($valido == 'S' && $hay_datos == 'S') {

        $vtaid = intval($rscab->fields['idventa']);
        $id = intval($rscab->fields['idtmpventares_cab']);
        $idadherente = intval($rscab->fields['idadherente']);
        $descuento = intval($rscab->fields['descuento']);
        $canal_venta = $rscab->fields['canal_venta'];


        if ($descuento > 0) {
            $desctxt = $saltolinea."DESCUENTO: ".formatomoneda($descuento);
        }

        // sub filtros
        if ($impresor_tip == 'COC') {
            $subfiltroadd = "and tmp_ventares.idtmpventares_cab = $id";
        }
        if ($impresor_tip == 'CAJ') {
            $subfiltroadd = "and tmp_ventares.idtmpventares_cab = $id";
        }
        if ($impresor_tip == 'MES') {
            if ($idventa > 0) {
                $subfiltroadd = "
                and tmp_ventares.idtmpventares_cab in (
                                                        select idtmpventares_cab
                                                        from tmp_ventares_cab
                                                        where
                                                        idsucursal = $idsucursal
                                                        and idempresa = $idempresa
                                                        and finalizado = 'S'
                                                        and registrado = 'S'
                                                        and estado = 6
                                                        and idmesa=$idmesa
                                                        and idventa = $idventa
                                                       )
                ";
            } else {
                $subfiltroadd = "
                and tmp_ventares.idtmpventares_cab in (
                                                        select idtmpventares_cab
                                                        from tmp_ventares_cab
                                                        where
                                                        idsucursal = $idsucursal
                                                        and idempresa = $idempresa
                                                        and finalizado = 'S'
                                                        and registrado = 'N'
                                                        and estado = 6
                                                        and idmesa=$idmesa
                                                       )
                ";

            }
        }
        if ($impresor_tip == 'REI') {
            $subfiltroadd = "and tmp_ventares.idtmpventares_cab = $id";
        }

        // datos del adherente
        if ($idadherente > 0) {
            $consulta = "SELECT * FROM adherentes where idadherente = $idadherente";
            $rsadh = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $nombre_adherente = $saltolinea."ADH: ".texto_tk(trim($rsadh->fields['nomape']), 35);
            $codadherente = "";
            if ($rprf->fields['imprime_cod_adherente'] == 'S') {
                $consulta = "SELECT * FROM clientes_codigos where idadherente = $idadherente";
                $rsadhcod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $codadherente = $saltolinea."COD ADH: ".trim($rsadhcod->fields['us_cod']).$saltolinea;


                //$nombre_adherente=$nombre_adherente.'';
            }
        }

        //echo "s";exit;


        // datos utiles
        $fechapedido = date("d/m/Y H:i:s", strtotime($rscab->fields['fechahora']));
        $anulado_el = date("d/m/Y H:i:s", strtotime($rscab->fields['anulado_el']));
        $anulado_por = trim(texto_tk($rscab->fields['anulado_por'], 28));
        $razon_social = trim(texto_tk($rscab->fields['razon_social'], 28));
        $ruc = texto_tk($rscab->fields['ruc'], 28);
        if (intval($vtaid) > 0 or intval($idventa) > 0) {
            $razon_social = trim(texto_tk($rscab->fields['razon_social_ven'], 28));
            $ruc = texto_tk($rscab->fields['ruc_ven'], 28);
        }

        $datos_fiscales = "Razon SOC.: $razon_social".$saltolinea;
        $datos_fiscales .= "RUC NRO.  : $ruc".$saltolinea;
        if ($datos_fiscal == 'N') {
            $datos_fiscales = "";
        }
        if ($muestra_nombre == 'S') {
            if ($codadherente == '') {
                $datos_fiscales .= "CLIENTE: $razon_social".$saltolinea;
            }
        }

        // si es venta a credito
        $vtacredito = intval($rscab->fields['vtacredito']);
        if ($vtacredito == 2) {
            $creditotxt = "
----------------------------------------
        *** VENTA A CREDITO ***   
      RECONOZCO Y PAGARE EL MONTO       
           DE ESTA OPERACION        


FIRMA:..................................
ACLARACION: $razon_social";

        } else {
            $creditotxt = "";
        }
        if ($firma_credito != 'S') {
            $creditotxt = "";
        }
        // si no quiere leyenda para credito
        if ($leyenda_credito == 'N') {
            $creditotxt = "";
        }

        if ($muestra_sucursal_cabeza == 'S') {
            $leyenda .= $sucursaltxt;
            $muestra_sucursal_pie = 'N';
        } else {
            $leyenda .= "";
        }
        $leyenda = trim($leyenda);
        //$texto=$nombreempresa_centrado.$saltolinea;
        $texto = "****************************************".$saltolinea;
        $texto .= $enfasis.'              PEDIDO BORRADO            '.$saltolinea;
        $texto .= "****************************************".$saltolinea;
        if (trim($leyenda) != '') {
            $texto .= $leyenda.$saltolinea;
        }
        if ($muestra_idventa == 'S' or $muestra_idpedido == 'S') {
            if ($muestra_idpedido == 'S') {
                $texto .= $enfasis."PEDIDO N° $id";
            }
            if ($muestra_idventa == 'S') {
                if ($muestra_idpedido == 'S') {
                    $texto .= " | ";
                }
                $texto .= "$txt_codvta: $vtaid ";
            }
            //$texto.=$saltolinea;
        }
        if (($muestra_idpedido != 'S') && ($muestra_idventa != 'S')) {
            $lineainf = "";
        } else {
            $lineainf = $saltolinea."----------------------------------------".$saltolinea;
            $texto .= $lineainf;
        }
        if ($muestra_fechapedido == 'S') {
            $texto .= "FECHA PED : $fechapedido".$saltolinea;
        }
        if ($muestra_fechaimpresion == 'S') {
            //$texto.="FECHA IMP : $fechaimp".$saltolinea;
        }
        $texto .= "FEC BORRA : $anulado_el".$saltolinea;
        $texto .= "USU BORRO : $anulado_por".$saltolinea;
        if ($canal_venta != '') {
            $texto .= $enfasis."CANAL VENTA: ".$canal_venta.$saltolinea;
        }
        if ($datos_fiscales != '') {
            $texto .= $datos_fiscales;
        }
        if ($nombre_adherente != '') {
            $texto .= $nombre_adherente.' ';
        }
        if ($codadherente != '') {
            $texto .= $codadherente.'';
        }

        if ($cabezacuerpo == 'S') {
            $texto .= "----------------------------------------".$saltolinea;
            $texto .= "N  |PRODUCTOS";
        }
        $texto .= "----------------------------------------";

        // detalle sin agregados/sacados
        $consulta = "
        select tmp_ventares.*, productos.descripcion, sum(cantidad) as total, sum(precio) as totalprecio, sum(subtotal) as subtotal,
        (select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
        (select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado
        from tmp_ventares 
        inner join productos on tmp_ventares.idproducto = productos.idprod_serial
        where 
        tmp_ventares.finalizado = 'S'
        and tmp_ventares.borrado = 'S'
        $subfiltroadd
        and tmp_ventares.idsucursal = $idsucursal
        and tmp_ventares.idempresa = $idempresa
        $filtroadd
        and tmp_ventares.idtipoproducto = 1
        and idtmpventaresagregado is null
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
        group by descripcion, receta_cambiada
        ORDER BY tmp_ventares.idventatmp asc
        ";
        //echo $consulta;
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // sin agregados
        while (!$rs->EOF) {
            $idventatmp = $rs->fields['idventatmp'];
            $idproducto = $rs->fields['idproducto'];
            if ($rs->fields['tipo_plato'] != '') {
                $tipoplato = trim($rs->fields['tipo_plato']." | ");
            }
            $texto .= $saltolinea.$enfasis."".agregaespacio(texto_tk(formatomoneda($rs->fields['total'], 4, 'N'), 6), 6).'|'.texto_tk($tipoplato.$rs->fields['descripcion'], 34);
            //$texto.=$saltolinea.$enfasis."".agregaespacio(texto_tk(formatomoneda($rs->fields['total'],3,'N'),3),3).'|'.texto_tk($tipoplato.$rs->fields['descripcion'],37);
            if ($usa_precio == 'S') {
                $texto .= $saltolinea."   Gs.".texto_tk(formatomoneda($rs->fields['subtotal']), 30);
            }
            if (trim($rs->fields['observacion']) != '') {
                //$texto.=$saltolinea.$enfasis."   *OBS: ".texto_tk($rs->fields['observacion'],35).$saltolinea;
                $texto .= $saltolinea.$enfasis."   *OBS: ".$rs->fields['observacion'].$saltolinea;
            }
            //$texto=trim($texto);
            $rs->MoveNext();
        }

        // detalle con agregados/sacados
        $consulta = "
        select tmp_ventares.*, productos.descripcion, cantidad as total, precio as totalprecio, subtotal as subtotal,
        (select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
        (select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado
        from tmp_ventares 
        inner join productos on tmp_ventares.idproducto = productos.idprod_serial
        where 
        tmp_ventares.finalizado = 'S'
        and tmp_ventares.borrado = 'N'
        $subfiltroadd
        and tmp_ventares.idsucursal = $idsucursal
        and tmp_ventares.idempresa = $idempresa
        $filtroadd
        and tmp_ventares.idtipoproducto = 1
        and idtmpventaresagregado is null
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
        )
        ORDER BY tmp_ventares.idventatmp asc
        ";
        //echo $consulta;
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // con agregados
        while (!$rs->EOF) {
            $idventatmp = $rs->fields['idventatmp'];
            $idproducto = $rs->fields['idproducto'];
            if ($rs->fields['tipo_plato'] != '') {
                $tipoplato = trim($rs->fields['tipo_plato']." | ");
            }

            $texto .= $saltolinea.$enfasis."".agregaespacio(texto_tk(formatomoneda($rs->fields['total'], 3, 'N'), 3), 3).'|'.texto_tk($tipoplato.$rs->fields['descripcion'], 37);
            if ($usa_precio == 'S') {
                $texto .= $saltolinea."   Gs.".texto_tk(formatomoneda($rs->fields['subtotal']), 30);
            }
            //$texto=trim($texto);
            // busca si tiene agregado
            $idvt = $rs->fields['idventatmp'];
            $consulta = "
            select tmp_ventares_agregado.*
            from tmp_ventares_agregado
            where 
            idventatmp = $idventatmp
            order by alias desc
            ";
            $rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // genera agregados si tiene
            if (trim($rsag->fields['alias']) != '') {
                $texto .= $saltolinea."   > AGREGADOS:".$saltolinea;
                while (!$rsag->EOF) {
                    $texto .= $enfasis."    + ".texto_tk($rsag->fields['alias'], 36).$saltolinea;
                    $texto .= "      Gs.".texto_tk(formatomoneda($rsag->fields['precio_adicional'] * $rsag->fields['cantidad']), 30).$saltolinea;
                    $rsag->MoveNext();
                }
                $texto = substr($texto, 0, -1);
            }
            //$texto=trim($texto);

            // busca si tiene sacados
            $consulta = "
            select tmp_ventares_sacado.*
            from tmp_ventares_sacado
            where 
            idventatmp = $idventatmp
            order by alias desc
            ";
            $rssac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // genera sacados si tiene
            if (trim($rssac->fields['alias']) != '') {
                $texto .= $saltolinea."   > EXCLUIDOS:".$saltolinea;
                while (!$rssac->EOF) {
                    $texto .= $enfasis."    - ".texto_tk($rssac->fields['alias'], 36).$saltolinea;
                    $rssac->MoveNext();
                }
                $texto = substr($texto, 0, -1);
            }
            if (trim($rs->fields['observacion']) != '') {
                //$texto.=$saltolinea.$enfasis."   *OBS: ".texto_tk($rs->fields['observacion'],35).$saltolinea;
                $texto .= $saltolinea.$enfasis."   *OBS: ".$rs->fields['observacion'].$saltolinea;
            }
            //$texto=trim($texto);
            $rs->MoveNext();
        }


        // combo y combinado
        //and (tmp_ventares.combo = 'S' or tmp_ventares.combinado = 'S' or tmp_ventares.idtipoproducto > 1)
        $consulta = "
        select tmp_ventares.*, tmp_ventares.idtipoproducto, productos.descripcion, tmp_ventares.cantidad as total, tmp_ventares.observacion as observacion,
        (select muestra_grupo_combo from productos WHERE productos.idprod_serial = tmp_ventares.idproducto limit 1) as muestra_grupo_combo
        from tmp_ventares 
        inner join productos on tmp_ventares.idproducto = productos.idprod_serial
        where 
        tmp_ventares.borrado = 'N'
        and tmp_ventares.finalizado = 'S'
        $subfiltroadd
        and tmp_ventares.idempresa = $idempresa
        and tmp_ventares.idsucursal = $idsucursal
        $filtroadd
        and  tmp_ventares.idtipoproducto > 1
        and idtmpventaresagregado is null
        ORDER BY descripcion asc
        ";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        while (!$rs->EOF) {
            $idventatmp = $rs->fields['idventatmp'];
            $idproducto = $rs->fields['idproducto'];
            $muestra_grupo_combo = $rs->fields['muestra_grupo_combo'];
            $texto .= $saltolinea.$enfasis."".agregaespacio(texto_tk(formatomoneda($rs->fields['total'], 3, 'N'), 3), 3).'|'.texto_tk($rs->fields['descripcion'], 37);
            $texto .= $saltolinea."   Gs.".texto_tk(formatomoneda($rs->fields['subtotal']), 30);
            //$texto=trim($texto);
            // busca si es un producto combinado viejo
            if ($rs->fields['idtipoproducto'] == 3) {
                $prod_1 = $rs->fields['idprod_mitad1'];
                $prod_2 = $rs->fields['idprod_mitad2'];
                $consulta = "
                select *
                from productos
                where 
                (idprod_serial = $prod_1 or idprod_serial = $prod_2)
                order by descripcion asc
                ";
                $rspcom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                while (!$rspcom->EOF) {
                    $texto .= $saltolinea.$enfasis."   » MITAD ".texto_tk($rspcom->fields['descripcion'], 31);
                    $rspcom->MoveNext();
                }

            }
            // busca si es un combo
            if ($rs->fields['idtipoproducto'] == 2) {

                if ($muestra_grupo_combo == 'S') {
                    $consulta = "
                    select combos_listas.nombre, productos.descripcion, count(*) as total
                    from productos 
                    inner join tmp_combos_listas on tmp_combos_listas.idproducto = productos.idprod_serial
                    inner join combos_listas on combos_listas.idlistacombo = tmp_combos_listas.idlistacombo
                    where 
                    tmp_combos_listas.idventatmp = $idventatmp
                    group by combos_listas.nombre, productos.descripcion
                    order by combos_listas.idlistacombo asc
                    limit 20
                    ";
                    $rsgrupos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                } else {
                    $consulta = "
                    select count(idprod) as total, descripcion, productos.idprod_serial 
                    from tmp_combos_listas
                    inner join productos on productos.idprod_serial = tmp_combos_listas.idproducto
                    where
                    tmp_combos_listas.idventatmp = $idventatmp
                    group by productos.idprod_serial 
                    order by descripcion asc
                    ";
                    //echo $consulta;
                    $rsgrupos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                }


                while (!$rsgrupos->EOF) {
                    if ($muestra_grupo_combo == 'S') {
                        $nombre_grupo = trim($rsgrupos->fields['nombre']).": ";
                    }
                    $texto .= $saltolinea.$enfasis."   » ".$nombre_grupo.$rsgrupos->fields['total'].' '.texto_tk($rsgrupos->fields['descripcion'], 25);
                    $rsgrupos->MoveNext();
                }

            }
            // busca si es un combinado extendido
            if ($rs->fields['idtipoproducto'] == 4) {
                $consulta = "
                select count(idprod) as total, descripcion, productos.idprod_serial 
                from tmp_combinado_listas
                inner join productos on productos.idprod_serial = tmp_combinado_listas.idproducto_partes
                where
                tmp_combinado_listas.idventatmp = $idventatmp
                group by productos.idprod_serial 
                order by descripcion asc
                ";
                //echo $consulta;
                $rsgrupos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                while (!$rsgrupos->EOF) {
                    $texto .= $saltolinea.$enfasis."   » ".$rsgrupos->fields['total'].' '.texto_tk($rsgrupos->fields['descripcion'], 25);
                    $rsgrupos->MoveNext();
                }

            }

            // busca si tiene agregado
            $idvt = $rs->fields['idventatmp'];
            $consulta = "
            select tmp_ventares_agregado.*
            from tmp_ventares_agregado
            where 
            idventatmp = $idvt
            order by alias desc
            ";
            $rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // genera agregados si tiene
            if (trim($rsag->fields['alias']) != '') {
                $texto .= $saltolinea."   > AGREGADOS:".$saltolinea;
                while (!$rsag->EOF) {
                    $texto .= $enfasis."    + ".texto_tk($rsag->fields['alias'], 36).$saltolinea;
                    $texto .= "      Gs.".texto_tk(formatomoneda($rsag->fields['precio_adicional'] * $rsag->fields['cantidad']), 30).$saltolinea;
                    $rsag->MoveNext();
                }
                $texto = substr($texto, 0, -1);
            }
            //$texto=trim($texto);

            // busca si tiene sacados
            $consulta = "
            select tmp_ventares_sacado.*
            from tmp_ventares_sacado
            where 
            idventatmp = $idvt
            order by alias desc
            ";
            $rssac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // genera sacados si tiene
            if (trim($rssac->fields['alias']) != '') {
                $texto .= $saltolinea."   > EXCLUIDOS:".$saltolinea;
                while (!$rssac->EOF) {
                    $texto .= $enfasis."    - ".texto_tk($rssac->fields['alias'], 36).$saltolinea;
                    $rssac->MoveNext();
                }
                $texto = substr($texto, 0, -1);
            }


            if (trim($rs->fields['observacion']) != '') {
                //$texto.=$saltolinea.$enfasis."   *OBS: ".texto_tk($rs->fields['observacion'],35).$saltolinea;
                $texto .= $saltolinea.$enfasis."   *OBS: ".$rs->fields['observacion'].$saltolinea;
            }
            //$texto=trim($texto);
            $rs->MoveNext();
        }


        // buscar usuario
        $operador = $rscab->fields['idusu'];
        $consulta = "
        select usuario from usuarios where idusu = $operador
        ";
        $rsop = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $operador = $rsop->fields['usuario'];

        // datos de cabecera
        $chapa = texto_tk($rscab->fields['chapa'], 33);
        $telefono_carry = texto_tk($rscab->fields['telefono'], 33);
        $mesa = texto_tk($rscab->fields['idmesa'], 33);
        $mesa = texto_tk($rscab->fields['idmesa'], 33);
        $observacion = $rscab->fields['observacion'];

        // datos cabecera
        $direccion = $rscab->fields['direccion'];
        $telefono = $rscab->fields['telefono'];
        $nombre_deliv = texto_tk($rscab->fields['nombre_deliv'], 26);
        $apellido_deliv = texto_tk($rscab->fields['apellido_deliv'], 26);
        $llevapos = siono($rscab->fields['llevapos']);
        $cambio = formatomoneda($rscab->fields['cambio']);
        $observacion_delivery = $rscab->fields['observacion_delivery'];
        $delivery_costo = formatomoneda($rscab->fields['delivery_costo']);
        $monto = formatomoneda($rscab->fields['monto']);
        $totalpagar = formatomoneda(intval($rscab->fields['monto']) + intval($rscab->fields['delivery_costo']) - intval($descuento));
        $tpagasinformato = intval($rscab->fields['monto']) + intval($rscab->fields['delivery_costo']) - intval($descuento);
        $vuelto = $rscab->fields['cambio'] - ($rscab->fields['monto'] + $rscab->fields['delivery_costo'] - intval($descuento));
        if ($vuelto < 0) {
            $vuelto = 0;
        }
        $vuelto = formatomoneda($vuelto);

        // si no es delivery
        if ($rscab->fields['delivery'] != "S") {
            if ($usa_total == 'S') {
                //$texto_total=$saltolinea.$enfasis."TOTAL GLOBAL: $totalpagar";
            }
            if ($usa_totaldiscreto == 'S') {
                $texto_totaldiscreto = $saltolinea."#$totalpagar";
            }

            $totalesg = $desctxt.$texto_total.$texto_totaldiscreto;
            if (trim($totalesg) != '') {
                // totales
                $texto .= "
----------------------------------------$totalesg";
            }
        } // if($rscab->fields['delivery'] != "S"){

        // si es delivery
        if ($rscab->fields['delivery'] == "S") {
            //echo 'ss';exit;
            $iddomicilio = intval($rscab->fields['iddomicilio']);
            //    echo $iddomicilio;exit;
            if (($iddomicilio > 0) && ($usalistazonas == 'S')) {
                $buscar = "Select describezona,obs,iddomicilio,cliente_delivery_dom.referencia
                from 
                zonas_delivery
                inner join cliente_delivery_dom on cliente_delivery_dom.idzonadel=zonas_delivery.idzonadel
                where  cliente_delivery_dom.iddomicilio=$iddomicilio
                limit 1            
                "    ;
                //echo $buscar;exit;
                $rszon = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $addz = trim($rszon->fields['describezona']).' | '.trim($rszon->fields['obs']);
                $addr = '----------------------------------------';
                $referencia = trim($rszon->fields['referencia']);

            }

            $texto .= "
----------------------------------------
> DELIVERY
$addz 
$addr
NOMBRE      : $nombre_deliv
APELLIDO    : $apellido_deliv
TELEFONO    : 0$telefono
LLEVAR POS  : $llevapos
TOTAL GLOBAL: $totalpagar
PAGA CON    : $cambio
VUELTO      : $vuelto
DIRECCION   : $direccion
REFERENCIA  : $referencia
OBS. DEL.   : $observacion_delivery";

        } // if($rscab->fields['delivery'] == "S"){

        // si tiene mesa buscar el numero
        if ($mesa > 0) {
            $consulta = " 
            select numero_mesa, nombre
            from mesas
            inner join salon on mesas.idsalon = salon.idsalon
            where 
            idmesa = $mesa
            and salon.idsucursal = $idsucursal
            ";
            $rsmes = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $numeromesa = $rsmes->fields['numero_mesa'];
            $salon = $rsmes->fields['nombre'];
        }


        // si tiene mesa mostrar
        if ($numeromesa != '') {
            $mesa_m = $saltolinea.$enfasis."MESA: ".texto_tk($numeromesa, 34).$saltolinea."SALON: ".texto_tk(strtoupper($salon), 33);
        }

        // si tiene chapa mostrar
        if (trim($chapa) != '') {
            if ($usa_chapa == 'S') {
                if ($chapa != '') {
                    $chapa_m = $saltolinea.$enfasis."NOMBRE: ".$chapa;
                }
                if ($telefono_carry != '') {
                    $telefono_carry_m = $saltolinea.$enfasis."TELEFONO: ".$telefono_carry;
                }
            } else {
                $chapa_m = "";
                $chapa = "";
                $telefono_carry_m = "";
                $telefono_carry = "";
            }
        }
        // si tiene observacion mostrar
        if (trim($observacion) != '') {
            if ($usa_obs == 'S') {
                $observacion_m = $saltolinea.$enfasis."OBSERVACION: ".strtoupper($observacion);
            }
        }

        // si es mesa
        if ($impresor_tip == "MES" && $vtaid == 0) {
            $textomesa = "";
            if ($datos_fac_preticket == 'S') {
                $textomesa .= "RUC:____________________________________".$saltolinea;
                $textomesa .= "RAZON SOCIAL:___________________________".$saltolinea;
            }
            if ($propina_preticket == 'S') {
                $textomesa .= "PROPINA:________________________________".$saltolinea;
            }
            if ($datos_fac_preticket == 'S' or $propina_preticket == 'S') {
                $textomesa .= "----------------------------------------".$saltolinea;
            }
        }

        // si es una venta
        if ($vtaid > 0) {
            $idventa = $vtaid;
            $consulta = "
        select * from preferencias_caja limit 1;
        ";
            $rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $muestra_formapago = trim($rsprefcaj->fields['muestra_formapago']);
            //mostrar cotizacion , monto y vuelto recibidos
            $muestra_cotizacion = trim($rsprefcaj->fields['usa_cotizacion']);
            $muestra_recibevuelto = trim($rsprefcaj->fields['muestrarecibe']);

            $buscar = "Select vuelto,recibido from ventas where idventa=$vtaid ";
            $rsvte = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

            if ($muestra_cotizacion == 'S') {
                //Mostraremos la cotizacion en otras monedas
                // muestra solo las monedas con cotizacion cargada en el dia
                $ahorad = date("Y-m-d");
                $consulta = "
                select *,
                        (
                        select cotizaciones.cotizacion
                        from cotizaciones
                        where 
                        cotizaciones.estado = 1 
                        and date(cotizaciones.fecha) = '$ahorad'
                        and tipo_moneda.idtipo = cotizaciones.tipo_moneda
                        order by cotizaciones.fecha desc
                        limit 1
                        ) as cotizacion
                from tipo_moneda 
                where
                estado = 1
                and borrable = 'S'
                and 
                (
                    (
                        borrable = 'N'
                    ) 
                    or  
                    (
                        tipo_moneda.idtipo in 
                        (
                        select cotizaciones.tipo_moneda 
                        from cotizaciones
                        where 
                        cotizaciones.estado = 1 
                        and date(cotizaciones.fecha) = '$ahorad'
                        )
                    )
                )
                order by borrable ASC, descripcion asc
                ";
                $rsmoneda = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $tmoneda = $rsmoneda->RecordCount();
                if ($tmoneda > 0) {

                    $texto .= $saltolinea.'------------OTRAS MONEDAS---------------'.$saltolinea;
                    while (!$rsmoneda->EOF) {
                        $coti = formatomoneda($rsmoneda->fields['cotizacion'], 2, 'N');
                        $monto = formatomoneda($tpagasinformato / $rsmoneda->fields['cotizacion'], 2, 'N');
                        $palabra1 = trim($rsmoneda->fields['descripcion'])." x ($coti) : ".$monto;


                        $addlinea = "$palabra1";
                        $texto .= $addlinea.$saltolinea;
                        $rsmoneda->MoveNext();
                    }
                }
                $texto .= '----------------------------------------'.$saltolinea;
            }
            if ($muestra_recibevuelto == 'S') {
                //Mostrar monto recibido y vuelto
                $recibeplata = floatval($rsvte->fields['recibido']);
                $vuelto = floatval($rsvte->fields['vuelto']);
                $addlinea = "Recibido Gs: ".formatomoneda($recibeplata, 4, 'N')." -> Vuelto : ".formatomoneda($vuelto, 4, 'N');
                $texto .= $addlinea.$saltolinea;
            }




            // si la preferencia dice que muestre
            if ($muestra_formapago == 'S') {

                $consulta = "
            SELECT formas_pago.descripcion as formapago, gest_pagos_det.monto_pago_det
            FROM gest_pagos_det
            inner join gest_pagos on gest_pagos.idpago = gest_pagos_det.idpago
            inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
            where 
            gest_pagos.idventa = $idventa
            ";
                $rsfpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $tpag = $rsfpag->RecordCount();
                if ($tpag > 0) {
                    $fpagotxt .= $saltolinea.'----------------------------------------'.$saltolinea;
                    $fpagotxt .= 'Pagos:'.$saltolinea;
                    while (!$rsfpag->EOF) {
                        $fpagotxt .= agregaespacio(strtoupper($rsfpag->fields['formapago']), 22).': '.agregaespacio_tk(formatomoneda($rsfpag->fields['monto_pago_det']), 16, 'der', 'S').$saltolinea;
                        $rsfpag->MoveNext();
                    }

                    $texto .= $saltolinea.trim($fpagotxt);
                }
            }
        }
        if ($muestra_operador == 'S') {

            $opertxt = "OPERADOR: $operador";

        }
        if (($opertxt != '') or ($mesaychapa != '')) {
            $texto .= "
----------------------------------------
$opertxt $mesaychapa";

        }

        $texto .= $mesa_m;
        $texto .= $chapa_m;
        $texto .= $telefono_carry_m;
        $texto .= $observacion_m;
        $texto .= $creditotxt;
        if ($muestra_sucursal_pie == 'S') {
            $texto .= $sucursaltxt;
        }
        $texto .= "
----------------------------------------
$textomesa$pie_pagina
";
        $texto .= "****************************************".$saltolinea;


    } // if($valido == 'S'){
    //echo $texto;exit;
    $texto = wordwrap($texto, 40, $saltolinea, true);
    $res = [
        'valido' => $valido,
        'errores' => $errores,
        'hay_datos' => $hay_datos,
        'ticket' => $texto
    ];


    return $res;
}
function ticket_producto($parametros_array)
{
    global $conexion;
    global $saltolinea;
    global $ahora;
    global $idempresa;
    global $nombresucursal;
    global $nombreempresa;

    $nombreempresa_centrado = corta_nombreempresa(trim($nombreempresa), 40);

    // inicializar
    $valido = "S";
    $erroes = "";
    $hay_datos = "N";

    // datos entrada
    $idimpresoratk = $parametros_array['idimpresoratk'];
    $idpedido = $parametros_array['idpedido'];
    $idmesa = $parametros_array['idmesa'];
    $impresor_tip = $parametros_array['impresor_tip'];
    $muestra_suc = 'S';
    $idventa = intval($parametros_array['v']);



    // validaciones
    if (intval($idimpresoratk) == 0) {
        $errores .= "-No indico la impresora.".$saltolinea;
        $valido = "N";
    }
    if (intval($idpedido) == 0 && intval($idmesa) == 0) {
        $errores .= "-No indico el pedido.".$saltolinea;
        $valido = "N";
    }

    // traer todos los productos con corte
    $consulta = "
    select 
    tmp_ventares_cab.idventa, tmp_ventares_cab.idtmpventares_cab, tmp_ventares_cab.fechahora,
    tmp_ventares.observacion, productos.idmedida,
    tmp_ventares.idproducto, productos.descripcion, tmp_ventares.cantidad as total, tmp_ventares.subtotal as subtotal,
    (SELECT nombre FROM categorias where id_categoria = productos.idcategoria) as categoria,
    tmp_ventares_cab.idmesa
    from tmp_ventares
    inner join tmp_ventares_cab on tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab
    inner join  productos on productos.idprod_serial = tmp_ventares.idproducto
    where
    tmp_ventares.borrado = 'N'
    and tmp_ventares.finalizado =  'S'
    and tmp_ventares_cab.estado <> 6
    and tmp_ventares_cab.idventa is not null
    and tmp_ventares_cab.idventa = $idventa
    order by productos.descripcion asc
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idmesa = intval($rs->fields['idmesa']);

    $texto .= $saltolinea.$saltolinea;
    while (!$rs->EOF) {
        $cant_prod = intval($rs->fields['total']);
        $idmedida = intval($rs->fields['idmedida']); // 4 unitario
        // si la cantidad es mayor a 1 y ademas es un producto unitario
        if ($cant_prod > 1 && $idmedida == 4) {
            for ($i = 1;$i <= $cant_prod;$i++) {
                $texto .= "----------------------------------------".$saltolinea;
                $texto .= texto_tk($nombreempresa_centrado, 40).$saltolinea;
                $texto .= "----------------------------------------".$saltolinea;
                $texto .= texto_tk("             VALE PRODUCTO", 40).$saltolinea;
                $texto .= "PEDIDO N° ".$rs->fields['idtmpventares_cab']." | CODVTA: ".$rs->fields['idventa']." ".$saltolinea;
                //$texto.="($i/".intval($rs->fields['total']).")";
                $texto .= "FEC PEDIDO: ".date("d/m/Y H:i:s", strtotime($rs->fields['fechahora'])).$saltolinea;
                $texto .= texto_tk('CAT: '.$rs->fields['categoria'], 40).$saltolinea;
                $texto .= agregaespacio(texto_tk(formatomoneda('1', 3, 'N'), 3), 3).'|'.texto_tk($tipoplato.$rs->fields['descripcion'], 37).$saltolinea;
                //if($usa_precio == 'S'){
                $texto .= "   Gs.".texto_tk(formatomoneda($rs->fields['subtotal'] / $rs->fields['total']), 30).$saltolinea;
                //}
                if (trim($rs->fields['observacion']) != '') {
                    //$texto.="   *OBS: ".texto_tk($rs->fields['observacion'],35).$saltolinea;
                    $texto .= "   *OBS: ".$rs->fields['observacion'].$saltolinea;
                }
                $texto .= "----------------------------------------".$saltolinea;
                $texto .= " E-KARU SOFTWARE | restaurante.com.py ".$saltolinea;
                $texto .= "----------------------------------------".$saltolinea;
                $texto .= $saltolinea.$saltolinea;
                $texto .= "[----------------CORTAR----------------]".$saltolinea;
                $texto .= $saltolinea.$saltolinea;
            }


            // si es kilo u otro, o si solo tiene 1 producto
        } else {
            $texto .= "----------------------------------------".$saltolinea;
            $texto .= texto_tk($nombreempresa_centrado, 40).$saltolinea;
            $texto .= "----------------------------------------".$saltolinea;
            $texto .= texto_tk("             VALE PRODUCTO", 40).$saltolinea;
            $texto .= "PEDIDO N° ".$rs->fields['idtmpventares_cab']." | CODVTA: ".$rs->fields['idventa']." ".$saltolinea;
            $texto .= "FEC PEDIDO: ".date("d/m/Y H:i:s", strtotime($rs->fields['fechahora'])).$saltolinea;
            $texto .= texto_tk('CAT: '.$rs->fields['categoria'], 40).$saltolinea;
            $texto .= agregaespacio(texto_tk(formatomoneda($rs->fields['total'], 3, 'N'), 3), 3).'|'.texto_tk($tipoplato.$rs->fields['descripcion'], 37).$saltolinea;
            //if($usa_precio == 'S'){
            $texto .= "   Gs.".texto_tk(formatomoneda($rs->fields['subtotal']), 30).$saltolinea;
            //}
            if (trim($rs->fields['observacion']) != '') {
                //$texto.="   *OBS: ".texto_tk($rs->fields['observacion'],35).$saltolinea;
                $texto .= "   *OBS: ".$rs->fields['observacion'].$saltolinea;
            }
            $texto .= "----------------------------------------".$saltolinea;
            $texto .= " E-KARU SOFTWARE | restaurante.com.py ".$saltolinea;
            $texto .= "----------------------------------------".$saltolinea;
            $texto .= $saltolinea.$saltolinea;
            $texto .= "[----------------CORTAR----------------]".$saltolinea;
            $texto .= $saltolinea.$saltolinea;
        }

        $rs->MoveNext();
    }



    $texto = wordwrap($texto, 40, $saltolinea, true);
    if ($idmesa > 0) {
        $texto = "";
    }


    $res = [
        'valido' => $valido,
        'errores' => $errores,
        'hay_datos' => $hay_datos,
        'ticket' => $texto
    ];


    return $res;
}
?>
