<?php

function validar_carrito_compra($parametros_array)
{
    require_once("../compras/preferencias_compras.php");
    global $conexion;
    global $ahora;
    global $saltolinea;

    // validaciones basicas
    $valido = "S";
    $errores = "";

    $idinsumo = intval($parametros_array['idinsumo']);
    $cantidad = $parametros_array['cantidad'];
    $costo_unitario = $parametros_array['costo_unitario'];
    $idtransaccion = $parametros_array['idtransaccion'];
    $lote = $parametros_array['lote'];
    $vencimiento = $parametros_array['vencimiento'];
    // campo nuevo de iva variable
    $iva = antisqlinyeccion($parametros_array['iva'], 'float');
    // enviado por concepot de insumos si es flete o despacho
    // en un campo que se tiene que cambiar para el select posterior
    $usa_iva = antisqlinyeccion($parametros_array['usa_iva'], 'text');
    $precio_venta_unitario = $parametros_array['precio_venta_unitario'];

    // solo si envio precio de venta busca en las preferencias
    if (trim($precio_venta_unitario) != '') {
        $consulta = "
		select permite_precio_venta_manual 
		from preferencias_compras 
		limit 1
		";
        $rsprefcomp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $permite_precio_venta_manual = $rsprefcomp->fields['permite_precio_venta_manual'];
        // si no se permite cambio de precio de venta manual
        if ($permite_precio_venta_manual == 'N') {
            if (trim($precio_venta_unitario) != '') {
                $valido = "N";
                $errores .= " - No se permite cambiar el precio de venta segun tus preferencias de compra, deje en blanco este campo, no complete con 0 ni con ningun numero.".$saltolinea;
            }
        }
    }

    if (($usa_iva == "true" || $usa_iva == "TRUE") && ($iva == 0 || $iva == "NULL")) {
        $valido = "N";
        $errores .= " - El articulo requiere que ingrese el valor del IVA.".$saltolinea;
    }

    if (intval($idinsumo) == 0) {
        $valido = "N";
        $errores .= " - No se envio ningun articulo.".$saltolinea;
    }
    if (floatval($cantidad) <= 0) {
        $valido = "N";
        $errores .= " - No se envio ninguna cantidad.".$saltolinea;
    }
    // el costo puede ser negativo en caso de descuentos
    // comentado para permitir costo 0
    ////////////preferencia aqui
    if ($costo_cero == "N") {
        if (floatval($costo_unitario) == 0) {

            $valido = "N";
            $errores .= " - No se envio ningun costo unitario.".$saltolinea;

        }
    }
    if (intval($idtransaccion) == 0) {
        $valido = "N";
        $errores .= " - No se envio la transaccion.".$saltolinea;
    }


    // valida que exista el insuumo
    $consulta = "
	select idinsumo from insumos_lista where estado = 'A' and idinsumo = $idinsumo
	";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsex->fields['idinsumo']) == 0) {
        $valido = "N";
        $errores .= " - Articulo Inexistente.".$saltolinea;
    }

    // valida queu exista la transaccion y que no esta finalizada
    $consulta = "
	SELECT idtran FROM tmpcompras where estado = 1
	";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsex->fields['idtran']) == 0) {
        $valido = "N";
        $errores .= " - Transaccion Inexistente.".$saltolinea;
    }




    $res = [
        'valido' => $valido,
        'errores' => $errores
    ];

    return $res;

}
function agregar_carrito_compra($parametros_array)
{

    global $conexion;
    global $ahora;
    global $saltolinea;

    $idinsumo = intval($parametros_array['idinsumo']);
    $cantidad = floatval($parametros_array['cantidad']);
    $costo = floatval($parametros_array['costo_unitario']);
    $idtransaccion = intval($parametros_array['idtransaccion']);
    $lote = antisqlinyeccion($parametros_array['lote'], 'text');
    $idmedida = antisqlinyeccion($parametros_array['idmedida'], 'int');
    $tipo_medida = antisqlinyeccion($parametros_array['tipo_medida'], 'text');
    $vencimiento = antisqlinyeccion($parametros_array['vencimiento'], 'text');
    $iddeposito = antisqlinyeccion($parametros_array['iddeposito'], 'int');
    $cantidad_ref = antisqlinyeccion($parametros_array['cantidad_ref'], 'float');
    $iva = antisqlinyeccion($parametros_array['iva'], 'float');
    $idempresa = 1;
    $idregcc = null;
    $precio_venta_unitario = antisqlinyeccion($parametros_array['precio_venta_unitario'], 'float');
    $cambia_pventa = antisqlinyeccion($parametros_array['cambia_pventa'], 'text');

    if ($lote == "'NULL'") {
        $lote = "NULL";
    }
    // busca en tabla de conversioens si hay el producto sino iguala
    $consulta = "
	select * 
	from compras_conversion 
	where 
	idproducto_origen = $idinsumo 
	and estado <> 6
	order by idconversioncompra desc
	limit 1
	";
    //echo $consulta;
    $rsc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsc->fields['idconversioncompra'] > 0) {
        $idproducto_conv = $rsc->fields['idproducto_destino'];

        $consulta = "
		select idmedida, tipoiva 
		from insumos_lista
		where 
		idinsumo = $idproducto_conv
		";
        $rs2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $iva_compra_porc = $rs2->fields['tipoiva'];
        // para conversion
        $cantidad_destino = floatval($rsc->fields['cantidad_destino']);
        $cantidad_origen = floatval($rsc->fields['cantidad_origen']);
        $subtotal = $costo * $cantidad; //del producto origen

        // calculos de conversiones
        $cantidad_conv = round(($cantidad * $cantidad_destino) / $cantidad_origen, 3);
        $precio_conv = round($subtotal / $cantidad_conv, 0);
        $subtotal_conv = $cantidad_conv * $precio_conv;
        $idmedida_conv = $rs2->fields['idmedida'];

    } else {
        $cantidad_conv = $cantidad;
        $precio_conv = $costo;
        $subtotal_conv = $cantidad_conv * $precio_conv;
        $idmedida_conv = $idmedida;
        $idproducto_conv = $idinsumo;
    }
    $cantidad = $cantidad_conv;
    $costo = $precio_conv;
    $subt = $costo * $cantidad;//aca estaba una variable $cant, que no hay mas arriba
    $idinsumo = $idproducto_conv;

    // datos del insumo convertido
    $buscar = "Select  * from insumos_lista where idinsumo=$idinsumo";
    $rsde = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $pchar = antisqlinyeccion($rsde->fields['descripcion'], 'text');
    $tipoiva = intval($rsde->fields['tipoiva']);
    $idtipoiva = intval($rsde->fields['idtipoiva']);
    $idconcepto = antisqlinyeccion($rsde->fields['idconcepto'], 'int');


    // Buscamos si el producto ya existe en el temporal
    // comentado para permitir dos productos con el mismo nombre
    // $buscar="
    // Select *
    // from tmpcompradeta
    // where
    // idprod=$idinsumo
    // and idt=$idtransaccion
    // ";
    // $rr=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    // if (intval($rr->fields['idprod'])==0){
    if ($costo < 0) {
        $subt = $costo;

    } else {
        ////////////////////////////////
        if ($tipo_medida == 2 || $tipo_medida == 3 || $tipo_medida == 4) {
            $subt = $costo;
            $costo = ($subt) / $cantidad;
        } else {
            $subt = $cantidad * $costo;
        }

    }

    if ($cantidad_ref > 0) {
        $subt = $cantidad_ref * $costo;
        $costo = $subt / $cantidad;
    }
    //No existe en tmp e  insertamos
    $select_add = "";
    $insert_add = "";
    if ($cambia_pventa != "NULL") {
        $select_add = ",cambia_pventa";
        $insert_add = ", $cambia_pventa";
    }
    $insertar = "
		Insert into tmpcompradeta
		(idprod,idconcepto,idemp,cantidad,costo,pchar,sucursal,existe,idt,subtotal,idtipoiva,iva,categoria,subcate,precioventa,preciomin,
		preciomax,listaprecios,medida,p1,p2,p3,costo2,vencimiento,lote,iddeposito_tmp,idmedida $select_add)
		values
		($idinsumo,$idconcepto,$idempresa,$cantidad,$costo,$pchar,1,1,$idtransaccion,$subt,$idtipoiva,$tipoiva,0,0,$precio_venta_unitario,0,
		0,'',0,$costo,0,0,0,$vencimiento,$lote,$iddeposito,$idmedida $insert_add)
		";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
    $consulta = "
		select idregcc 
		from tmpcompradeta
		 where 
		idt = $idtransaccion 
		order by idregcc desc 
		limit 1
		";
    $rsprox = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idregcc = $rsprox->fields['idregcc'];
    $subtotal = $subt;
    if (intval($idtipoiva) == 0) {
        $idtipoiva = 1;
    }

    $parametros_array = [
        'idtipoiva' => $idtipoiva,
        'monto_ivaincluido' => $subtotal
    ];

    ///////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////
    ///////////////quitar iva si es que es importacion

    $consulta = "SELECT idtipo_origen FROM tipo_origen WHERE  UPPER(tipo)='IMPORTACION'";
    $rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id_tipo_origen_importacion = intval($rs_guarani->fields["idtipo_origen"]);
    $consulta = "
		select idtipo_origen 
		from tmpcompras
		where 
		idtran = $idtransaccion  
		limit 1
		";
    $rs_tipo_origen = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idtipo_origen = $rs_tipo_origen->fields['idtipo_origen'];

    if ($iva != "NULL" && $iva != 0) {
        $gravadoml_despacho = $subt - $iva;
        $consulta = "
				INSERT INTO tmpcompradetaimp
				(idtran, idtrandet, iva_porc_col, monto_col, 
				gravadoml, ivaml, exento) 
				VALUES 
				($idtransaccion, $idregcc, 0, $subt,
				$gravadoml_despacho,  $iva,  'N'
				)
				";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    } else {

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
					INSERT INTO tmpcompradetaimp
					(idtran, idtrandet, iva_porc_col, monto_col, 
					gravadoml, ivaml, exento) 
					VALUES 
					($idtransaccion, $idregcc, $iva_porc_col, $monto_col,
					$gravadoml,  $ivaml,  '$exento'
					)
					";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        }

    }

    // foreach($res_iva as $iva_linea){




    //}	// if (intval($rr->fields['idprod'])==0){ SE COMENTA PARA PERMITIR DOS ARTICULOS (GRATIFICACION O DESC)

    $res = [
        'idregcc' => $idregcc
    ];
    return $res;



}
function borrar_carrito_compra($parametros_array)
{
    global $conexion;
    $borrar = $parametros_array["borrar"];
    $idempresa = $parametros_array["idempresa"];
    $delete = "Delete from tmpcompradeta where idregcc=$borrar  and idemp = $idempresa";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));
    $delete = "Delete from tmpcompradetaimp where idtrandet=$borrar";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));
}
function editar_carrito_compra($parametros_array)
{
    global $conexion;

    $cantidad = $parametros_array["cantidad"];
    $idinsumo = $parametros_array["idinsumo"];
    $costo = $parametros_array["costo_unitario"];
    $idtransaccion = $parametros_array["idtransaccion"];
    $subtotal = $cantidad * $costo;
    $p1 = $costo;
    $idregcc = $parametros_array["idregcc"];
    $idtipoiva = $parametros_array["idtipoiva"];
    $vencimiento = $parametros_array["vencimiento"];
    $lote = $parametros_array["lote"];
    $iddeposito = $parametros_array["iddeposito"];
    $iva_variable = $parametros_array["iva_variable"];
    $idmedida = intval($parametros_array["idmedida"]);// por formulario se valida pero en los que tengan



    $consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%DESPACHO\" ";
    $rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idconcepto_despacho = intval($rs_conceptos->fields['idconcepto']);

    $consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%FLETE\" ";
    $rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idconcepto_flete = intval($rs_conceptos->fields['idconcepto']);


    // el formulario viejo se rompera por lo tanto se agrega la validacion aqui para procesos viejos

    $consulta = "
		select idconcepto
		from tmpcompradeta 
		where 
		idt = $idtransaccion
		and idregcc = $idregcc
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idconcepto = floatval($rs->fields['idconcepto']);
    // echo $idconcepto == $idconcepto_flete ?"si es flete " : "no  es flete";
    // echo $idconcepto == $idconcepto_despacho ?"si es despacho " : "no  es despacho";
    //   exit;
    $consulta = "";

    if ($idmedida == 0) {
        $consulta = "
			update tmpcompradeta
			set
				cantidad=$cantidad,
				costo=$costo,
				subtotal=$subtotal,
				p1=$p1,
				vencimiento=$vencimiento,
				lote=$lote,				
				iddeposito_tmp=$iddeposito
			WHERE
				idregcc = $idregcc
			";

    } else {
        $consulta = "
			update tmpcompradeta
			set
				cantidad=$cantidad,
				costo=$costo,
				subtotal=$subtotal,
				p1=$p1,
				vencimiento=$vencimiento,
				lote=$lote,
				iddeposito_tmp=$iddeposito,
				idmedida=$idmedida
			WHERE
				idregcc = $idregcc
			";


    }
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    //$subtotal=$costo;
    if (intval($idtipoiva) == 0) {
        $idtipoiva = 1;
    }
    if ($idconcepto != $idconcepto_flete && $idconcepto != $idconcepto_despacho) {
        $consulta = "
				delete from tmpcompradetaimp where idtrandet = $idregcc
				";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
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
				INSERT tmpcompradetaimp
				(idtran, idtrandet, iva_porc_col, monto_col, 
				gravadoml, ivaml, exento) 
				VALUES 
				($idtransaccion, $idregcc, $iva_porc_col, $monto_col,
				$gravadoml,  $ivaml,  '$exento'
				)
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        }
    } else {
        $consulta_ivavariable = "
			update tmpcompradetaimp
			set
				ivaml=$iva_variable,
				gravadoml = $costo-$iva_variable
			WHERE
				idtran = $idtransaccion
				and idtrandet = $idregcc
			";
        //echo $consulta;exit;
        //echo $idregcc; exit;
        $conexion->Execute($consulta_ivavariable) or die(errorpg($conexion, $consulta_ivavariable));

    }
}



function validar_cabecera_compra(&$parametros_array)
{

    require_once("./preferencias_compras.php");


    global $conexion;
    global $ahora;
    global $obliga_oc;
    global $multimoneda_local;
    global $preferencias_importacion;
    // validaciones basicas
    $valido = "S";
    $errores = "";
    if ($preferencias_importacion == "S") {
        $idmoneda = intval($parametros_array['idmoneda']);
        if ($idmoneda == 0) {
            $valido = "N";
            $errores .= "- Favor seleccione una moneda.<br />";
        }
    }

    // TODO: rellenar la tabla tipo_origen  antes de subir el codigo
    $consulta = "SELECT idtipo_origen FROM tipo_origen WHERE  UPPER(tipo)='IMPORTACION'";
    $rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id_tipo_origen_importacion = intval($rs_guarani->fields["idtipo_origen"]);
    if (isset($parametros_array["new"]) || isset($parametros_array["edit"])) {
        if ($parametros_array['idtipo_origen'] != $id_tipo_origen_importacion) {

            if (intval($parametros_array['timbrado']) == 0) {
                $valido = "N";
                $errores .= " - El campo timbrado no puede ser cero o nulo.<br />";
            }

            if (intval($parametros_array['idtipocomprobante']) > 0) {
                $idtipocomprobante = $parametros_array['idtipocomprobante'];
                $consulta = "
				select vence_timbrado 
				from tipo_comprobante
				where 
				idtipocomprobante  = $idtipocomprobante
				";

                $rscomp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                if (trim($rscomp->fields['vence_timbrado']) == 'S') {
                    if (trim($parametros_array['vto_timbrado']) == '' || $parametros_array['vto_timbrado'] == 'NULL') {
                        $valido = "N";
                        $errores .= " - El campo vencimiento timbrado no puede estar vacio.<br />";
                    }
                } else {
                    $parametros_array["vto_timbrado"] = "NULL";
                }
            }

            if (intval($parametros_array["factura_prov_suc"]) == 0) {
                $valido = "N";
                $errores .= " - Factura numero carece de sucursal.<br />";
            } else {
                if (strlen(trim($parametros_array["factura_prov_suc"])) != 3) {
                    $valido = "N";
                    $errores .= " - La sucursal de la factura no puede tener mas de 3 digitos.<br />";
                }
            }

            if (intval($parametros_array["factura_prov_pex"]) == 0) {
                $valido = "N";
                $errores .= " - Factura numero carece de punto de expedicion.<br />";
            } else {
                if (strlen(trim($parametros_array["factura_prov_pex"])) != 3) {
                    $valido = "N";
                    $errores .= " - El punto de expedicion de la factura no puede tener mas de 3 digitos.<br />";
                }
            }

            if (intval($parametros_array["factura_prov_nro"]) == 0) {
                $valido = "N";
                $errores .= " - Factura numero no tiene numeracion.<br />";
            } else {
                if (strlen(trim($parametros_array["factura_prov_nro"])) > 9) {
                    $valido = "N";
                    $errores .= " - El numero de la factura no puede tener mas de 9 digitos.<br />";
                }
                if (strlen(trim($parametros_array["factura_prov_nro"])) < 7) {
                    $valido = "N";
                    $errores .= " - El numero de la factura no puede tener menos de 7 digitos.<br />";
                }
            }
            if (strlen(trim($parametros_array["facturacompleta"])) > 15) {
                $valido = "N";
                $errores .= " - La factura completa no puede tener mas de 15 digitos.<br />";
            }
            if (strlen(trim($parametros_array["facturacompleta"])) < 13) {
                $valido = "N";
                $errores .= " - La factura completa no puede tener menos de 13 digitos.<br />";
            }
            if (solonumeros($parametros_array["facturacompleta"]) != $parametros_array["facturacompleta"]) {
                $valido = "N";
                $errores .= " - La factura contiene caracteres no permitidos, solo debe contener numeros y guion (-).<br />";
            }



        }
        if ($parametros_array['idtipo_origen'] == $parametros_array['id_tipo_origen_importacion']) {
            $idmoneda = $parametros_array['idmoneda'];
            $consulta = "SELECT cotiza from tipo_moneda where idtipo = $idmoneda";
            $rscotiza = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $cotiza_moneda = intval($rscotiza -> fields['cotiza']);
            if ($cotiza_moneda == 1 && ($parametros_array['idcot'] == "NULL" || $parametros_array['idcot'] == "")) {
                $valido = "N";
                $q = $parametros_array['idcot'];
                $errores .= " - $cotiza_moneda    La $q  cotizacion debe ser cargada verifiquelo.<br />";
            }

        }
        if ($multimoneda_local == "S") {
            $idmoneda = $parametros_array['idmoneda'];
            $consulta = "SELECT cotiza from tipo_moneda where idtipo = $idmoneda";
            $rscotiza = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $cotiza_moneda = intval($rscotiza -> fields['cotiza']);
            if ($cotiza_moneda == 1 && ($parametros_array['idcot'] == "NULL" || $parametros_array['idcot'] == "")) {
                $valido = "N";
                $errores .= "- La cotizacion debe ser cargada verifiquelo.<br />";
            }
        }

        if ($obliga_oc == "S" && ($parametros_array['ocnum'] == "NULL" || $parametros_array['ocnum'] == "")) {
            $valido = "N";
            $errores .= " - Es necesario agregar la orden de compra a esta factura.<br />";
        }

        if (floatval($parametros_array['sucursal']) <= 0) {
            $valido = "N";
            $errores .= " - Debe indicar la sucursal.<br />";
        }
        if (intval($parametros_array['tipocompra']) == 0) {
            $valido = "N";
            $errores .= " - Debe indicar si la compra fue al contado o a credito.<br />";
        }
        if (trim($parametros_array['fecha_compra']) == '' || trim($parametros_array['fecha_compra']) == 'NULL') {
            $valido = "N";
            $errores .= " - El campo fecha compra no puede estar vacio.<br />";
        }
        if (trim($parametros_array['facturacompra']) == '' || trim($parametros_array['facturacompra']) == 'NULL') {
            $valido = "N";
            $errores .= " - El campo factura numero no puede estar vacio.<br />";
        }
        if (floatval($parametros_array['monto_factura']) < 0) {
            $valido = "N";
            $errores .= " - El campo monto_factura no puede ser negativo.<br />";
        }
        if (intval($parametros_array['idproveedor']) == 0) {
            $valido = "N";
            $errores .= " - Debe indicar el proveedor.<br />";
        }

        // valicaciones por tipo de comprobantes
        ///////////////////////////////////////////////////////
        ///////////////////////////////////////////////////////
        ///////////////////////////////////////////////////////
        if ($parametros_array['idtipo_origen'] != "NULL" && $parametros_array['idtipo_origen'] != $id_tipo_origen_importacion) {
            if (intval($parametros_array['idtipocomprobante']) <= 0) {
                $valido = "N";
                $errores .= " - Debe indicar el  tipo de comprobante.<br />";
            }

        }
        if ($parametros_array['idtipo_origen'] == "NULL") {

            if (intval($parametros_array['idtipocomprobante']) <= 0) {
                $valido = "N";
                $errores .= " - Debe indicar el  tipo de comprobante.<br />";
            }

        }

        // valicaciones por tipo de comprobantes
        if ($parametros_array["obliga_cdc"] == 'S') {
            // si es una factura electronica
            if (intval($parametros_array['idtipocomprobante']) == 4) {
                if (trim($parametros_array['cdc']) == '') {
                    $valido = "N";
                    $errores .= " - Debe indicar el CDC cuando es una factura electronica.<br />";
                }
            }
        }

        // validaciones BD
        if (strtotime($parametros_array['fecha_compra']) > strtotime($parametros_array["fechahasta_habilita"])) {
            $fechadesde_txt = $parametros_array["fechadesde_txt"];
            $fechahasta_txt = $parametros_array["fechahasta_txt"];
            $valido = "N";
            $errores .= " - La fecha de compra no puede ser superior al periodo habilitado entre $fechadesde_txt y $fechahasta_txt.<br />";
        }
        if (strtotime($parametros_array['fecha_compra']) < strtotime($parametros_array["fechadesde_habilita"])) {
            $fechadesde_txt = $parametros_array["fechadesde_txt"];
            $fechahasta_txt = $parametros_array["fechahasta_txt"];
            $valido = "N";
            $errores .= " - La fecha de compra no puede ser inferior al periodo habilitado entre $fechadesde_txt y $fechahasta_txt.<br />";
        }
        if (strtotime($parametros_array['fecha_compra']) > strtotime(date("Y-m-d"))) {
            $valido = "N";
            $errores .= " - La fecha de compra no puede ser superior a hoy.<br />";
        }
        // si envio vencimiento de timbrado
        if (trim($parametros_array['vto_timbrado']) != '') {
            if (in_array(intval($parametros_array['idtipocomprobante']), $parametros_array["tipos_comprobantes_vence_ar"])) {
                if (strtotime($parametros_array['fecha_compra']) > strtotime($parametros_array['vto_timbrado'])) {
                    $vto_timbrado_txt = date("d/m/Y", strtotime($parametros_array['vto_timbrado']));
                    $fecha_compra_txt = date("d/m/Y", strtotime($parametros_array['fecha_compra']));
                    $valido = "N";
                    $parametros_array["vto_timbrado_txt"] = $vto_timbrado_txt;
                    $parametros_array["fecha_compra_txt"] = $fecha_compra_txt;
                    $errores .= " - El timbrado vencio ($vto_timbrado_txt) antes de la fecha de compra ($fecha_compra_txt).<br />";
                }
            }
        }
        // si envio orden de compra
        //agrege  NULL porque el antisqlinyeccion text agrega un null
        if (trim($parametros_array['ocnum']) != '' && trim($parametros_array['ocnum']) != "NULL") {
            $ocnum = $parametros_array["ocnum"];
            // valida que no este registrada la orden de compra con otra compra y que este finalizada la orden
            $consulta = "
			select ocnum, idcompra
			from compras 
			where
			ocnum = $ocnum
			and estado <> 6
			limit 1
			";
            $rsoccom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // valida que no este registrada la orden de compra con otra compra y que este finalizada la orden
            $consulta = "
			select ocnum, idtran
			from tmpcompras 
			where
			ocnum = $ocnum
			and estado <> 6
			limit 1
			";
            $rsoccomtmp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            if (intval($rsoccomtmp->fields['ocnum']) > 0 && intval($parametros_array["edit"]) != 1) {
                $idtran_tmp = $rsoccomtmp->fields['idtran'];
                $valido = "N";
                $errores .= " - Existe un compra en proceso de carga con el mismo numero de orden de compra, idtran: $idtran_tmp.<br />";
            }

            // valida que coincida con el proveedor
            $consulta = "
			select *
			from compras_ordenes 
			where 
			ocnum = $ocnum
			limit 1
			";
            //echo $consulta;exit;
            $rsocval = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            if ($parametros_array['idproveedor'] != $rsocval->fields['idproveedor']) {
                $valido = "N";
                $errores .= " - La orden de compra indicada no pertenece al proveedor seleccionado.<br />";
            }
            if ($rsocval->fields['estado'] == 1) {
                $valido = "N";
                $errores .= " - La orden de compra indicada aun no fue finalizada.<br />";
            }
            if ($rsocval->fields['estado'] == 3) {
                $valido = "N";
                $errores .= " - La orden de compra indicada ya fue utilizada.<br />";
            }
            if ($rsocval->fields['estado'] == 6) {
                $valido = "N";
                $errores .= " - La orden de compra indicada fue anulada.<br />";
            }
            if ($rsocval->fields['estado'] != 1 && $rsocval->fields['estado'] != 2 && $rsocval->fields['estado'] != 3 && $rsocval->fields['estado'] != 6) {
                $valido = "N";
                $errores .= " - La orden de compra indicada tiene un estado no valido.<br />";
            }
        }
        // por si esta factura tiene problemas de carga
        $facturacompra = $parametros_array["facturacompra"];
        $idproveedor = $parametros_array["idproveedor"];
        $timbrado = $parametros_array["timbrado"];
        $idtran = $parametros_array["idtran"];
        $consulta = "
		update facturas_proveedores 
		set 
		estado = 6,
		comentario = 'ANULA AUTO'
		where 
		factura_numero = $facturacompra 
		and id_proveedor = $idproveedor
		and estado <> 6
		and idcompra is null
		and idgasto is null
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		select * 
		from facturas_proveedores 
		where 
		factura_numero = $facturacompra 
		and id_proveedor = $idproveedor
		and timbrado = $timbrado
		and estado <> 6
		limit 1
		";
        $rsfacex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $fecha_compra_fac = date("d/m/Y", strtotime($rsfacex->fields['fecha_compra']));
        if (intval($rsfacex->fields['id_factura']) > 0) {
            $valido = "N";
            $errores .= " - La factura indicada ya existe, no se puede duplicar, Cod: ".intval($rsfacex->fields['id_factura']).", fecha factura: '$fecha_compra_fac'.<br />";
        }
        if (!isset($parametros_array["edit"])) {
            $consulta = "
			select * 
			from tmpcompras 
			where 
			facturacompra = $facturacompra 
			and proveedor = $idproveedor
			and estado <> 6
			and idtran <> $idtran
			limit 1
			";
            $rsfacex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            if (intval($rsfacex->fields['idtran']) > 0) {
                $valido = "N";
                $errores .= " - Ya existe esta factura en otra transaccion en proceso de carga, idtran: ".intval($rsfacex->fields['idtran']).".<br />";
            }
        }

        // valida que no exista la transaccion
        $consulta = "
		select idtran from compras where idtran = $idtran limit 1
		";
        $rstranex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if ($rstranex->fields['idtran'] > 0) {
            $valido = "N";
            $errores .= " - La transaccion que intentas registrar ya fue utilizada en otra compra.<br />";
        }

        // si la compra es a credito
        if (intval($parametros_array['tipocompra']) == 2) {
            if (strtotime($parametros_array['vencimiento']) < strtotime($parametros_array['fecha_compra'])) {
                $valido = "N";
                $errores .= " - El vencimiento de la factura no puede ser inferior a la fecha de compra.<br />";
            }
            if (trim($parametros_array['vencimiento']) == '' || trim($parametros_array['vencimiento']) == 'NULL') {
                $valido = "N";
                $errores .= " - El vencimiento de la factura no puede estar vacio si es a credito.<br />";
            }
        }
        // conversiones

        // si la compra es al contado
        if (intval($parametros_array['tipocompra']) == 1) {
            $parametros_array["vencimiento"] = $parametros_array["fecha_compra"];
        }
        // si no es una factura electronica
        if (intval($parametros_array['idtipocomprobante']) != 4) {
            $parametros_array["cdc"] = "NULL";
        }
    }

    if (isset($parametros_array['delete'])) {
        $errores = "";
        $valido = "S";
        $idtran = intval($parametros_array['get_idtran']);
        $consulta = "
		select * from tmpcompradeta where idt = $idtran limit 1
		";
        $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if (intval($rsdet->fields['idt']) > 0) {
            $errores .= "- No se puede borrar por que tiene articulos cargados, eliminelos primero.<br />";
            $valido = "N";
        }
    }

    $res = [
        'valido' => $valido,
        'errores' => $errores
    ];

    return $res;

}
function registrar_cabecera_compra($parametros_array)
{
    global $conexion;
    //que pasa con los que no tienen las preferencias ver luego
    require_once("../compras/preferencias_compras.php");
    global $multimoneda_local;
    $idtran = $parametros_array['idtran'];
    $sucursal = $parametros_array['sucursal'];
    $ahora = $parametros_array['fechahora'];
    $estado = $parametros_array['estado'];
    $idtipocompra = $parametros_array['tipocompra'];
    $fecha_compra = $parametros_array['fecha_compra'];
    $facturacompra = $parametros_array['facturacompra'];
    $facturacompra_guion = $parametros_array['facturacompra_guion'];
    $facturacompra_incrementa = $parametros_array['facturacompra_incrementa'];
    $totalcompra = $parametros_array['totalcompra'];
    $monto_factura = $parametros_array['monto_factura'];
    $cambio = $parametros_array['cambio'];
    $cambioreal = $parametros_array['cambioreal'];
    $cambiohacienda = $parametros_array['cambiohacienda'];
    $cambioproveedor = $parametros_array['cambioproveedor'];
    $vencimiento = $parametros_array['vencimiento'];
    $timbrado = $parametros_array['timbrado'];
    $vto_timbrado = $parametros_array['vto_timbrado'];
    $ocnum = $parametros_array['ocnum'];
    $registrado_por = $parametros_array['registrado_por'];
    $registrado_el = $parametros_array['registrado_el'];
    $idtipocomprobante = $parametros_array['idtipocomprobante'];
    $cdc = $parametros_array['cdc'];
    $idusu = $parametros_array['idusu'];
    $idproveedor = $parametros_array['idproveedor'];
    $moneda = $parametros_array['moneda'];
    $idempresa = $parametros_array['idempresa'];
    $descripcion = $parametros_array['descripcion'];
    $idtipo_origen = $parametros_array['idtipo_origen'];
    $idcot = $parametros_array['idcot'];
    $idcompra_ref = $parametros_array['idcompra_ref'];
    $id_tipo_origen_importacion = $parametros_array['id_tipo_origen_importacion'];

    ///verificar

    if ($id_tipo_origen_importacion != $idtipo_origen) {
        if ($multimoneda_local == "N") {
            $idcot = 0;
            $moneda = $parametros_array['id_moneda_nacional'];
        }
    } else {
        if ($timbrado == "NULL") {
            $timbrado = 0;
        }
    }
    $idembarque = 0;


    $consulta = "
	INSERT INTO transacciones_compras
	(idempresa, numero, estado, sucursal, idcliente, fecha, tipo, idusu)
	values
	($idempresa, $idtran, 1, $sucursal, 0, $ahora, NULL, $idusu)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    //	if ($idcot == 0 || is_null($idcot)){
    //		$consulta = "select idcot from cotizaciones order by 1 desc limit 1";
    //		$rsidcot = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    //		$idcot = intval($rsidcot->fields['idcot']);
    //	}

    $consulta = "
	insert into tmpcompras
	(idtran, sucursal, idempresa, fechahora, estado, tipocompra, fecha_compra, facturacompra, facturacompra_guion, facturacompra_incrementa, totalcompra, monto_factura, proveedor, moneda, cambio, cambioreal, cambiohacienda, cambioproveedor, vencimiento, timbrado, vto_timbrado, ocnum, registrado_por, registrado_el,
	idtipocomprobante, cdc, descripcion, idtipo_origen, idcot,idcompra_ref)
	values
	($idtran, $sucursal, $idempresa, $ahora, $estado, $idtipocompra, $fecha_compra, $facturacompra, $facturacompra_guion, $facturacompra_incrementa, $totalcompra, $monto_factura, $idproveedor, $moneda, $cambio, $cambioreal, $cambiohacienda, $cambioproveedor, $vencimiento, $timbrado, $vto_timbrado, $ocnum, $registrado_por, $registrado_el,
	$idtipocomprobante, $cdc, $descripcion, $idtipo_origen, $idcot, $idcompra_ref
	)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    if ($ocnum != "NULL" && $ocnum > 0) {
        $consulta_embarque = "SELECT emb.idembarque
		FROM tmpcompras as tmp 
		INNER JOIN compras_ordenes as co on tmp.ocnum = co.ocnum
		LEFT JOIN embarque as emb on emb.ocnum = co.ocnum
		WHERE tmp.ocnum = $ocnum
		";
        $rsembarque = $conexion->Execute($consulta_embarque) or die(errorpg($conexion, $consulta_embarque));
        $idembarque = intval($rsembarque->fields['idembarque']);
        if ($idembarque > 0) {
            $update = "
			UPDATE 
				embarque
			set
				idcompra=$idtran
			where
				idembarque = $idembarque
			";
            $conexion->Execute($update) or die(errorpg($conexion, $update));


        }
    }

    $res = [
        'idtran' => $idtran
    ];
    return $res;



    // si es credito
    /*header("location: tmpcompras_cred.php");
    exit;*/

}
function editar_cabecera_compra($parametros_array)
{
    require_once("../compras/preferencias_compras.php");
    global $conexion;
    global $preferencias_importacion;
    global $multimoneda_local;


    $consulta = "SELECT idtipo_origen FROM tipo_origen WHERE  UPPER(tipo)='IMPORTACION'";
    $rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id_tipo_origen_importacion = intval($rs_guarani->fields["idtipo_origen"]);


    //buscando moneda nacional
    $consulta = "SELECT tipo_moneda.idtipo, tipo_moneda.descripcion as nombre FROM tipo_moneda WHERE nacional='S'";
    $rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id_moneda_nacional = $rs_guarani->fields["idtipo"];
    $nombre_moneda_nacional = $rs_guarani->fields["nombre"];

    // global $ahora;
    ///facturaguion actualizar
    $idtran = $parametros_array['idtran'];
    $sucursal = $parametros_array['sucursal'];
    $ahora = $parametros_array['fechahora'];
    $idtipocompra = $parametros_array['tipocompra'];
    $fecha_compra = $parametros_array['fecha_compra'];
    $facturacompra = $parametros_array['facturacompra'];
    $facturacompra_incrementa = $parametros_array['facturacompra_incrementa'];
    $totalcompra = $parametros_array['totalcompra'];
    $monto_factura = $parametros_array['monto_factura'];
    $cambio = $parametros_array['cambio'];
    $cambioreal = $parametros_array['cambioreal'];
    $cambiohacienda = $parametros_array['cambiohacienda'];
    $cambioproveedor = $parametros_array['cambioproveedor'];
    $vencimiento = $parametros_array['vencimiento'];
    $timbrado = $parametros_array['timbrado'];
    $vto_timbrado = $parametros_array['vto_timbrado'];
    $ocnum = $parametros_array['ocnum'];
    $idtipocomprobante = $parametros_array['idtipocomprobante'];
    $cdc = $parametros_array['cdc'];
    $idusu = $parametros_array['idusu'];
    $idproveedor = $parametros_array['idproveedor'];
    $moneda = $parametros_array['moneda'];
    $idempresa = $parametros_array['idempresa'];
    $fechahora = $parametros_array['fechahora'];
    $facturacompra_guion = $parametros_array['facturacompra_guion'];
    $descripcion = $parametros_array['descripcion'];
    $idtipo_origen = $parametros_array['idtipo_origen'];
    $idcot = $parametros_array['idcot'];

    if ($preferencias_importacion != "S") {
        $moneda = 0;
    }

    if ($multimoneda_local == "S") {
        if ($id_tipo_origen_importacion == $idtipo_origen) {

            if ($timbrado == "NULL") {
                $timbrado = 0;
            }
        }
    } else {
        if ($id_tipo_origen_importacion != $idtipo_origen) {
            $idcot = 0;
            $moneda = $id_moneda_nacional;
        } else {
            if ($timbrado == "NULL") {
                $timbrado = 0;
            }
        }

    }

    $search_oc = "select tmpcompras.ocnum from tmpcompras where idtran = $idtran";
    $rs_oc = $conexion->Execute($search_oc) or die(errorpg($conexion, $search_oc));
    $ocnum_viejo = $rs_oc ->fields['ocnum'];
    $ocnum_numero = str_replace("'", "", $ocnum);
    if (intval($ocnum_viejo) != intval($ocnum_numero) && intval($ocnum_numero) != 0) {
        $delete = "delete from tmpcompradeta where idt = $idtran";
        $conexion->Execute($delete) or die(errorpg($conexion, $delete));

        $delete = "delete from tmpcompradetaimp where idtran = $idtran";
        $conexion->Execute($delete) or die(errorpg($conexion, $delete));


    }



    $consulta = "
		update tmpcompras
		set
			sucursal=$sucursal,
			idempresa=$idempresa,
			fechahora=$fechahora,
			tipocompra=$idtipocompra,
			fecha_compra=$fecha_compra,
			facturacompra=$facturacompra,
			facturacompra_incrementa=$facturacompra_incrementa,
			facturacompra_guion=$facturacompra_guion,
			totalcompra=$totalcompra,
			monto_factura=$monto_factura,
			proveedor=$idproveedor,
			moneda=$moneda,
			cambio=$cambio,
			cambioreal=$cambioreal,
			cambiohacienda=$cambiohacienda,
			cambioproveedor=$cambioproveedor,
			vencimiento=$vencimiento,
			timbrado=$timbrado,
			vto_timbrado=$vto_timbrado,
			ocnum=$ocnum,
			registrado_por=$idusu,
			registrado_el=$ahora,
			cdc=$cdc,
			idtipocomprobante=$idtipocomprobante,
			descripcion=$descripcion,
			idtipo_origen = $idtipo_origen,
			idcot = $idcot
		where
			idtran = $idtran
		";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    if ($ocnum != "NULL" && floatval(str_replace('\'', '', $ocnum)) > 0) {
        $consulta_embarque = "SELECT emb.idembarque
			FROM tmpcompras as tmp 
			INNER JOIN compras_ordenes as co on tmp.ocnum = co.ocnum
			LEFT JOIN embarque as emb on emb.ocnum = co.ocnum
			WHERE tmp.ocnum = $ocnum and emb.estado = 1 and emb.estado_embarque = 1
			";
        $rsembarque = $conexion->Execute($consulta_embarque) or die(errorpg($conexion, $consulta_embarque));
        $idembarque = intval($rsembarque->fields['idembarque']);
        if ($idembarque > 0) {
            $update = "
				UPDATE 
					embarque
				set
					idcompra=$idtran
				where
					idembarque = $idembarque
				";
            $conexion->Execute($update) or die(errorpg($conexion, $update));


        }
    }
    $res = [
        'idtran' => $idtran
    ];
    return $res;

}
function borrar_cabecera_compra($parametros_array)
{
    global $conexion;
    global $idusu;
    global $ahora;
    $ahora = antisqlinyeccion($ahora, "date");
    $idtran = $parametros_array["get_idtran"];
    $idempresa = $parametros_array["idempresa"];
    $consulta = "
		update tmpcompras
		set
		estado = 6,
		anulado_el = $ahora,
		anulado_por = $idusu
		where
			idtran = $idtran
			and estado = 1
			and idempresa = $idempresa
		";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
		update transacciones_compras
		set
		estado = 6
		where
			numero = $idtran
			and estado = 1
			and idempresa = $idempresa
		";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // $consulta = "
    // delete from  tmpcompradeta
    // where
    // 	idt = $idtran
    // 	and estado = 1
    // ";
    // $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
}
function anular_cabecera_compra($parametros_array)
{

    global $conexion;
    $idtran = $parametros_array["idtran"];

    $consulta = "
	update tmpcompras
	set
		estado = 6
	where
		idtran = $idtran
		";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}
function validar_compra(&$parametros_array)
{
    require_once("../compras/preferencias_compras.php");
    global $preferencias_importacion;
    global $conexion;
    global $ahora;


    require_once("../compras_ordenes/preferencias_compras_ordenes.php");


    // validaciones basicas
    $valido = "S";
    $errores = "";
    $idt = $parametros_array['idt'];
    $ocnum = $parametros_array['ocnum'];

    if ($ocnum != "NULL" && $ocnum != "" && $ocnum != 0 && $preferencias_facturas_multiples == "S") {

        $consulta = "SELECT idtipo_origen FROM tipo_origen WHERE  UPPER(tipo)='IMPORTACION'";
        $rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $id_tipo_origen_importacion = intval($rs_guarani->fields["idtipo_origen"]);
        if ($id_tipo_origen_importacion == 0) {
            $errores = "- Por favor cree el Origen IMPORTACON.<br />";
        }

        $consulta_embarque = "SELECT emb.idembarque
			FROM tmpcompras as tmp 
			INNER JOIN compras_ordenes as co on tmp.ocnum = co.ocnum
			LEFT JOIN embarque as emb on emb.ocnum = co.ocnum 
			WHERE tmp.ocnum = $ocnum
			";
        $rsembarque = $conexion->Execute($consulta_embarque) or die(errorpg($conexion, $consulta_embarque));
        $idembarque = intval($rsembarque->fields['idembarque']);

        $idtipo_origen = $parametros_array['idtipo_origen'];
        if ($id_tipo_origen_importacion == $idtipo_origen && $idembarque == 0) {
            $valido = "N";
            $errores .= "- No existe un Embarque asociado y es una Compra de Importacion.<br />";
        }



        //validadndo si es que recibe mas insumos de los solicitados en la orden de compra
        // 	$consulta = "SELECT cmp_dt.descripcion as item, cmp_dt.idprod, cmp_dt.cant_transito  , (cmp_dt.cant_transito - COALESCE(cmp_comprado.total_comprado, 0)) AS cantidad_faltante
        // 	FROM compras_ordenes_detalles AS cmp_dt
        // 	INNER JOIN compras_ordenes AS cmp ON cmp.ocnum = cmp_dt.ocnum
        // 	LEFT JOIN (
        // 		SELECT cmp_det.idprod, SUM(cmp_det.cantidad) AS total_comprado
        // 		FROM tmpcompradeta AS cmp_det
        // 		INNER JOIN tmpcompras AS cmp ON cmp.idtran = cmp_det.idt AND cmp.ocnum = $ocnum
        // 		GROUP BY cmp_det.idprod
        // 	) AS cmp_comprado ON cmp_comprado.idprod = cmp_dt.idprod
        // 	WHERE cmp_dt.ocnum = $ocnum";
        // 	$orden_items_faltantes = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
        // 	while (!$orden_items_faltantes->EOF){
        // 		if( floatval($orden_items_faltantes->fields['cantidad_faltante']) < 0 && floatval($orden_items_faltantes ->fields['cant_transito']) != 0 ){
        // 			$valido="N";
        // 			$cantidad_sobrepasada = - floatval($orden_items_faltantes->fields['cantidad_faltante']);
        // 			$nombre = $orden_items_faltantes->fields['item'];
        // 			$errores.="- Se ha excedido  la cantidad solicitada en $cantidad_sobrepasada ( $nombre). Por favor, realice una edición en la cantidad de la compra o, en caso de querer editar la orden de compra, proceda con la edición y luego recargue la página.<br /> - Orden numero   $ocnum editela realizando un click <a target='_blank' href='..\compras_ordenes\compras_ordenes.php'>Aqui!</a><br />";
        // 		}
        // 		$orden_items_faltantes->MoveNext();
        // 	}
    }
    //fin de validacion para las ordenes
    $consulta = "SELECT tmpcompradeta.lote, tmpcompradeta.vencimiento, insumos_lista.maneja_lote
	FROM tmpcompradeta
	INNER JOIN insumos_lista on insumos_lista.idinsumo = tmpcompradeta.idprod
	where
	 idt = $idt and tmpcompradeta.vencimiento is null and  insumos_lista.maneja_lote =1
	";
    $rs_maneja_lote = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rs_maneja_lote->fields['maneja_lote'] == 1) {
        $valido = "N";
        $errores .= "- Hay articulos lote y vencimientos los cuales son obligatorios, editelos.<br />";
    }

    $consulta = "
		SELECT idregcc 
		FROM tmpcompradeta
		where
		idregcc not in (
		select tmpcompradetaimp.idtrandet 
		from tmpcompradetaimp 
		where 
		tmpcompradetaimp.idtran = tmpcompradeta.idt
		)
		and idt = $idt
		limit 1
		";
    $rsimpfalta = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ///////////////// verificar preferencia importacion
    if ($preferencias_importacion == "S") {
        if ($rsimpfalta->fields['idregcc'] > 0 && $id_tipo_origen_importacion != $idtipo_origen) {
            $valido = "N";
            $errores .= "- Hay articulos sin impuesto cargado, editelos.<br />";
        }
    } else {
        if ($rsimpfalta->fields['idregcc'] > 0) {
            $valido = "N";
            $errores .= "- Hay articulos sin impuesto cargado, editelos.<br />";
        }
    }


    // validaciones
    if ($parametros_array['monto_factura'] != $parametros_array['totalcompra']) {
        $valido = "N";
        $errores .= "- El Monto de Factura no coincide con la sumatoria de productos cargados, favor verifique.<br />";
    }


    // validaciones si es a credito
    if ($parametros_array['tipocompra'] == 2) {
        if ($parametros_array['monto_cuota_venc'] <= 0) {
            $valido = "N";
            $errores .= "- Debe cargar los vencimientos cuando la factura es a credito.<br />";
        }

        // Redondear ambos valores
        $monto_cuota_venc_truncado = floor($parametros_array['monto_cuota_venc'] * 100) / 100;
        $monto_factura_truncado = floor($parametros_array['monto_factura'] * 100) / 100;

        // Comparar los valores redondeados
        if ($monto_factura_truncado != $monto_cuota_venc_truncado) {
            $valido = "N";
            // $monto_cuota_venc=$parametros_array['monto_cuota_venc'];
            // $monto_factura=$parametros_array['monto_factura'];
            // $monto_cuota_venc $monto_factura
            $errores .= "- El $monto_cuota_venc_redondeado $monto_factura_redondeado monto de factura no coincide con la sumatoria de vencimientos, favor verifique.<br />";
        }
        // toma el primer vencimiento como vencimiento de factura
        $parametros_array['vencimientofac'] = antisqlinyeccion($parametros_array['vencimientomin'], 'date');

    }

    //Buscamos la factura
    $factura = $parametros_array['factura'];
    $idprov = $parametros_array['idprov'];
    $timbrado = $parametros_array['timbrado'];
    $buscar = "
	Select * 
	from compras 
	where 
	facturacompra=$factura 
	and idproveedor=$idprov 
	and timbrado = $timbrado 
	and estado=1
	";
    $controla = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $registradofac = date("d/m/Y H:i:s", strtotime($controla->fields['registrado']));
    if (trim($controla->fields['facturacompra']) != '') {
        $valido = "N";
        $errores .= "- Factura duplicada, la factura que intentas cargar ya fue cargada el: $registradofac.<br />";
    }

    // buscar si ya existe factura
    $consulta = "
	Select * 
	from facturas_proveedores  
	where 
	id_proveedor=$idprov 
	and factura_numero=$factura
	and timbrado=$timbrado
	and estado <> 6
	limit 1
	";
    $rscon = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rscon->fields['factura_numero'] != '') {
        $valido = "N";
        $errores .= " La factura Numero: $factura ya se encuentra registrada y activa para el proveedor seleccionado.";
    }

    $res = [
        'valido' => $valido,
        'errores' => $errores
    ];

    return $res;

}
function registrar_compra($parametros_array)
{

    require_once("../compras_ordenes/preferencias_compras_ordenes.php");
    require_once("../proveedores/preferencias_proveedores.php");
    global $conexion;
    global $ahora;
    global $saltolinea;
    global $preferencias_facturas_multiples;
    global $proveedores_importacion;


    //Buscamos total de iva
    $idt = $parametros_array['idt'];
    $idempresa = $parametros_array['idempresa'];
    $faltante_guardar = intval($parametros_array['faltante']);
    $buscar = "Select  sum(subtotal) as tcompra10 
	from tmpcompradeta where idt=$idt  and idemp = $idempresa and iva=10";
    $rsiva10 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tiva10 = intval($rsiva10->fields['tcompra10']);
    $buscar = "Select  sum(subtotal) as tcompra5 
	from tmpcompradeta where idt=$idt  and idemp = $idempresa and iva=5";
    $rsiva5 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $buscar = "Select  sum(subtotal) as exe 
	from tmpcompradeta where idt=$idt  and idemp = $idempresa and iva=0";
    $rsivaex = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $iva10 = intval($rsiva10->fields['tcompra10'] / 11);
    $iva5 = intval($rsiva5->fields['tcompra5'] / 21);

    $excenta = intval($rsivaex->fields['exe']);



    if ($parametros_array['moneda'] == 0) {
        $parametros_array['moneda'] = 1;

    }
    $cambio = $parametros_array['cambio'];


    $buscar = "Select max(idcompra) as mayor from compras";
    $rsmay = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idcompra = intval($rsmay->fields['mayor']) + 1;
    //Registramos
    $idprov = $parametros_array['idprov'];
    $idcot = $parametros_array['idcot'];
    $idtipo_origen = $parametros_array['idtipo_origen'];
    $idsucursal = $parametros_array['idsucursal'];
    $fechacompra = $parametros_array['fechacompra'];
    $factura = $parametros_array['factura'];
    $idusu = $parametros_array['idusu'];
    $totalcompra = $parametros_array['totalcompra'];
    $tipocompra = $parametros_array['tipocompra'];
    $moneda = $parametros_array['moneda'];
    $vencimientofac = $parametros_array['vencimientofac'];
    $timbrado = $parametros_array['timbrado'];
    $timbradovenc = $parametros_array['timbradovenc'];
    $facturacompra_incrementatmp = $parametros_array['facturacompra_incrementatmp'];
    $ocnum = $parametros_array['ocnum'];
    $idtipocomprobante = $parametros_array['idtipocomprobante'];
    $cdc = $parametros_array['cdc'];
    $descripcion = $parametros_array['descripcion'];
    $idcompra_ref = $parametros_array['idcompra_ref'];



    //	if ($idcot == 0 || is_null($idcot)){
    //		$consulta = "select idcot from cotizaciones order by 1 desc limit 1";
    //		$rsidcot = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    //		$idcot = intval($rsidcot->fields['idcot']);
    //	}

    $descuento = $parametros_array['descuento'];
    $insertar = "Insert into compras 
	(	
	idtran,idcompra,idproveedor,sucursal,idempresa,fechacompra,facturacompra,registrado_por,total,iva10,
	iva5,exenta,registrado,tipocompra,moneda,cambio,vencimiento,timbrado,vto_timbrado,facturacompra_incrementa,
	ocnum, idtipocomprobante, cdc,obsfactura, descripcion,idcot,idtipo_origen,descuento,idcompra_ref
	)
	values
	(
	$idt,$idcompra,$idprov,$idsucursal,$idempresa,'$fechacompra',$factura,$idusu,$totalcompra,
	$iva10,$iva5,$excenta,'$ahora',$tipocompra,$moneda,$cambio,$vencimientofac,$timbrado,$timbradovenc,$facturacompra_incrementatmp,
	$ocnum, $idtipocomprobante, $cdc, $descripcion, $descripcion, $idcot, $idtipo_origen,$descuento,$idcompra_ref
	)";

    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

    /// Verificar si el pedido fue completado en su totalidad o si existen
    // productos pendientes



    //Deposito de compras
    $tipolegal = 1; //NO ME ACURDO PARA QUE SE USABA
    //De inmediato, insertamos en el deposito para ser procesado por el encargado, antes de cualquier cosa
    $insertar = "
	 insert into gest_depositos_compras 
	 (fecha_compra,idproveedor,factura_numero,registrado_por,tipo,idcompra,fechareg,idempresa,fecha_revision)
	 values
	 ('$fechacompra',$idprov,$factura,$idusu,$tipolegal,$idcompra,'$ahora',$idempresa,'$ahora')";

    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

    //Generamos los detalles
    $buscar = "Select * from tmpcompradeta where idt=$idt  and idemp = $idempresa";

    $rscuerpo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


    ////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////
    // VERIFICANDO ORDEN Y CREANDO SI HACE FALTA

    if ($preferencias_facturas_multiples == "S" && intval($ocnum) > 0) {
        /////////////////////////
        $buscar = "SELECT ocnum_ref from compras_ordenes where ocnum = $ocnum ";
        // echo $buscar;exit;
        $rs_verificar_ref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $ocnum_ref = intval($rs_verificar_ref->fields['ocnum_ref']);

        ///////////////////////////////////
        $buscar = "SELECT cmp_dt.ocseria,cmp_dt.idprod,cmp_dt.descripcion as item, 
		cmp_dt.idprod, cmp_dt.cant_transito, 
		(cmp_dt.cant_transito - COALESCE(cmp_comprado.total_comprado, 0)) AS cantidad_faltante
		FROM compras_ordenes_detalles AS cmp_dt
		INNER JOIN compras_ordenes AS cmp ON cmp.ocnum = cmp_dt.ocnum
		LEFT JOIN (
			SELECT cmp_det.idprod, SUM(cmp_det.cantidad) AS total_comprado
			FROM tmpcompradeta AS cmp_det
			INNER JOIN tmpcompras AS cmp ON cmp.idtran = cmp_det.idt 
			AND cmp.ocnum = $ocnum and cmp.idtran = $idt
			GROUP BY cmp_det.idprod
			) AS cmp_comprado ON cmp_comprado.idprod = cmp_dt.idprod
		WHERE cmp_dt.ocnum = $ocnum ";
        // echo $buscar;exit;
        $carga_completa = "'S'";////////
        $rsorden_verificacion = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        while (!$rsorden_verificacion->EOF) {
            $idprod = intval($rsorden_verificacion->fields['idprod']);
            if ($idprod > 0) {
                $faltante = floatval($rsorden_verificacion->fields['cantidad_faltante']);
                $ocseria = intval($rsorden_verificacion->fields['ocseria']);
                if ($faltante != 0) {
                    $carga_completa = "'N'";////////
                }

                //la orden original edita la cantidad en transito
                $update = "
					UPDATE 
						compras_ordenes_detalles
					set
						cant_transito = $faltante
					where
						ocnum = $ocnum
						and ocseria = $ocseria
						and idprod = $idprod
				";
                $conexion->Execute($update) or die(errorpg($conexion, $update));
            }
            $rsorden_verificacion -> MoveNext();
        }
        $add_carga = "";
        if ($carga_completa == "'S'" || $ocnum_ref > 0) {
            $add_carga = ",estado_orden=2";
        }
        //si es carga completa agrega a la tabla de ordenes que se cerro
        $update = "
		UPDATE 
			compras_ordenes
		set
			carga_completa=$carga_completa
			$add_carga
		where
			ocnum = $ocnum
		";
        $conexion->Execute($update) or die(errorpg($conexion, $update));



        /////////////////inicio de creacion de orden asociada

        //////////////////////////////////////////////////////////
        //////////// creando orden nueva

        if ($ocnum_ref == 0 and $ocnum != 0) {
            $ocnum_nuevo = select_max_id_suma_uno("compras_ordenes", "ocnum")["ocnum"];
            $consulta = "
			insert into compras_ordenes
			(ocnum, fecha, generado_por, tipocompra, fecha_entrega, idproveedor, estado, forma_pago, cant_dias, inicia_pago, registrado_por, registrado_el,idtipo_moneda,idcot,idtipo_origen,ocnum_ref,carga_completa,estado_orden)
			select
			$ocnum_nuevo, fecha, $idusu, tipocompra, '$ahora', idproveedor, 2, 0, 0, NULL, $idusu, '$ahora',idtipo_moneda,$idcot,idtipo_origen,ocnum,'S',2
			from compras_ordenes where ocnum = $ocnum 
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            ///////////creando detalle de


            $buscar = "Select descripcion from  insumos_lista where idinsumo=$idprod";
            $rsdes = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $des = str_replace("'", "", trim($rsdes->fields['descripcion']));
            // TODO: VERIFICAR SI YA HAY UNA COMPRA DE ESTA ORDEN  add_prodtmp_new.php
            $insertar = "Insert into compras_ordenes_detalles 
			(ocnum,idprod,cantidad,cant_transito,precio_compra,descripcion,precio_compra_total,idmedida,descuento)
				select
				$ocnum_nuevo,idprod,cantidad,0,costo,pchar,subtotal,idmedida,0 from tmpcompradeta where idt=$idt";

            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


            //actualiza la cabecera con el monto
            if ($proveedores_importacion == "S") {
                $buscar = "
				Select compras_ordenes.costo_ref, cotizaciones.cotizacion,
				(
					select sum(precio_compra_total) 
					from compras_ordenes_detalles 
					where compras_ordenes_detalles.ocnum = compras_ordenes.ocnum
				) as suma_total 
				from compras_ordenes 
				left JOIN cotizaciones 
				on compras_ordenes.idcot = cotizaciones.idcot  
				where ocnum=$ocnum_nuevo and compras_ordenes.estado = 2";
                $rsocnum = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $precio_total = floatval($rsocnum->fields['suma_total']);
                $cotizacion_referencia = floatval($rsocnum->fields['cotizacion']);
                $costo_referencia = floatval($rsocnum->fields['costo_ref']);
                if ($cotizacion_referencia > 0) {
                    $precio_total = ($precio_total * $cotizacion_referencia);
                    $buscar = "UPDATE compras_ordenes 
					set 
					costo_ref = $precio_total
					where ocnum=$ocnum_nuevo";
                    $rsocnum = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                }
            }
        }
        //////////////////////////////////////////////
        //////////////////////////////////////////////
        //////////////////////////////////////////////
        ///////////////fin de creacion de orden

        ////////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////////
        // PREFERENCIAS EMBARQUE
        $consulta = "SELECT emb.idembarque
						FROM compras as tmp 
						INNER JOIN compras_ordenes as co on tmp.ocnum = co.ocnum
						LEFT JOIN embarque as emb on emb.ocnum = co.ocnum
						WHERE tmp.ocnum = $ocnum";
        $rsembarque = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idembarque = intval($rsembarque->fields['idembarque']);
        if ($idembarque > 0) {
            $update = "
				UPDATE 
					embarque
				set
					idcompra=$idcompra,
					estado_embarque=2
				where
					idembarque = $idembarque
				";
            $conexion->Execute($update) or die(errorpg($conexion, $update));
        }


    }
    if ($preferencias_facturas_multiples == "N" && intval($ocnum) > 0) {
        //si es carga completa agrega a la tabla de ordenes que se cerro
        $update = "
		UPDATE 
			compras_ordenes
		set
		 estado_orden=2
		where
			ocnum = $ocnum
		";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
    }



    ////// fin d creacion de orden

    //Lista la cabecera seguimos con los detalles
    while (!$rscuerpo->EOF) {

        $idp = antisqlinyeccion($rscuerpo->fields['idprod'], 'text');
        $cant = $rscuerpo->fields['cantidad'];
        $costo = floatval($rscuerpo->fields['costo']);
        $costo2 = floatval($rscuerpo->fields['costo2']);
        $lote = antisqlinyeccion($rscuerpo->fields['lote'], 'text');
        $vencimiento_tmp = antisqlinyeccion($rscuerpo->fields['vencimiento'], 'date');
        $iddeposito_compra = intval($rscuerpo->fields['iddeposito_tmp']);
        $subtotal = floatval($rscuerpo->fields['subtotal']);
        $tipoiva = floatval($rscuerpo->fields['iva']);
        $idtipoiva = intval($rscuerpo->fields['idtipoiva']);
        $idconcepto = antisqlinyeccion($rscuerpo->fields['idconcepto'], "int");
        $idregcc = intval($rscuerpo->fields['idregcc']);
        $idmedida = intval($rscuerpo->fields['idmedida']);

        //Vemos si el producto existe pero en el stock global

        $buscar = "Select * from productos_stock_global where idproducto=$idp  and idempresa = $idempresa";
        $rsbuscar = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        $ee = trim($rsbuscar->fields['idproducto']);
        if ($ee == '') {
            //no existe y registramos
            $insertar = "Insert into productos_stock_global (idproducto,disponible,tipo,idempresa) values ($idp,$cant,1,$idempresa) ";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

            //tabla de costos

            //almacenamos en costos
            $inserta = "insert into costo_productos 
			(idempresa,id_producto,registrado_el,precio_costo,idproveedor,cantidad,numfactura,
			costo2,disponible,idcompra,fechacompra,modificado_el,lote,vencimiento)
			values
			($idempresa,$idp,'$ahora',$costo,$idprov,$cant,$factura,$costo2,
			$cant,$idcompra,'$fechacompra','$ahora', $lote, $vencimiento_tmp)";
            $conexion->Execute($inserta) or die(errorpg($conexion, $inserta));

        } else {
            //ya existe y updateamos
            $update = "update productos_stock_global set disponible=(disponible+$cant) where idproducto=$idp  and idempresa = $idempresa";
            $conexion->Execute($update) or die(errorpg($conexion, $update));

            //almacenamos en costos
            $inserta = "insert into costo_productos 
			(idempresa,id_producto,registrado_el,precio_costo,idproveedor,cantidad,numfactura,
			costo2,disponible,idcompra,fechacompra, modificado_el,lote,vencimiento)
			values
			($idempresa,$idp,'$ahora',$costo,$idprov,$cant,$factura,$costo2,$cant,$idcompra,'$fechacompra','$ahora', $lote, $vencimiento_tmp)";
            $conexion->Execute($inserta) or die(errorpg($conexion, $inserta));

        }

        //registramos el detalle de la compra
        $insertar = "Insert into compras_detalles
		(idcompra,idconcepto,codprod,cantidad,costo,idtrans,costo2,subtotal,idempresa,iva,idtipoiva,iddeposito_compra,vencimiento,lote,idmedida)
		values
		($idcompra,$idconcepto,$idp,$cant,$costo,$idt,$costo2,$subtotal,$idempresa,$tipoiva,$idtipoiva,$iddeposito_compra,$vencimiento_tmp,$lote,$idmedida)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

        $consulta = "select idregs from compras_detalles where idcompra = $idcompra order by idregs desc limit 1";
        $rslast = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idregs = $rslast->fields['idregs'];

        $consulta = "
		update tmpcompradetaimp 
		set 
		idcompradet = $idregs
		 where 
		 idtrandet = $idregcc
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // actualizamos el ultimo costo en insumos, puede no ser de este registro de compras por eso usamos por fecha desc
        $consulta = "
		update insumos_lista 
		set costo = 
				COALESCE((
				SELECT compras_detalles.costo
				FROM compras
				inner join compras_detalles on compras_detalles.idcompra = compras.idcompra
				where 
				compras_detalles.codprod = $idp
				and compras.idempresa = $idempresa
				order by compras.fechacompra desc
				limit 1
				),0)
		where
		idinsumo = $idp
		and idempresa = $idempresa
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        /*
        global
        update insumos_lista set costo = COALESCE(( SELECT compras_detalles.costo FROM compras inner join compras_detalles on compras_detalles.idcompra = compras.idcompra where compras_detalles.codprod = insumos_lista.idinsumo order by compras.fechacompra desc limit 1 ),0)
        where costo <= 0
        */

        $rscuerpo->MoveNext();
    }



    // TODO: PREFERENCIA ORDEN MULTICOMPRA
    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////

    if ($preferencias_facturas_multiples == "S" && intval($ocnum) > 0) {
        ////////////////////////////////////////////////////
        $buscar = "SELECT ocnum_ref from compras_ordenes where ocnum = $ocnum ";
        // echo $buscar;exit;
        $rs_verificar_ref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $ocnum_ref = intval($rs_verificar_ref->fields['ocnum_ref']);

        //////////actualizando cantidad en transito de orden madre
        $consulta = "SELECT cmp_dt.ocseria,cmp_dt.idprod,cmp_dt.descripcion as item, 
		cmp_dt.idprod, cmp_dt.cantidad, 
		(cmp_dt.cantidad - COALESCE(cmp_comprado.total_comprado, 0)) AS cantidad_faltante
		FROM compras_ordenes_detalles AS cmp_dt
		INNER JOIN compras_ordenes AS cmp ON cmp.ocnum = cmp_dt.ocnum
		LEFT JOIN (
			SELECT cmp_det.codprod, SUM(cmp_det.cantidad) AS total_comprado
			FROM compras_detalles AS cmp_det
			INNER JOIN compras AS cmp ON cmp.idcompra = cmp_det.idcompra
            INNER JOIN compras_ordenes as co ON co.ocnum = cmp.ocnum
			AND co.ocnum_ref = $ocnum_ref 
			GROUP BY cmp_det.codprod
			) AS cmp_comprado ON cmp_comprado.codprod = cmp_dt.idprod
		WHERE cmp.ocnum = $ocnum_ref";

        $carga_completa = "'S'";////////
        $rsorden_madre_verificacion = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        while (!$rsorden_madre_verificacion->EOF) {
            $idprod = intval($rsorden_madre_verificacion->fields['idprod']);
            if ($idprod > 0) {
                $faltante = floatval($rsorden_madre_verificacion->fields['cantidad_faltante']);
                $ocseria = intval($rsorden_madre_verificacion->fields['ocseria']);
                if ($faltante != 0) {
                    $carga_completa = "'N'";////////
                }

                //la orden original edita la cantidad en transito
                $update = "
					UPDATE 
						compras_ordenes_detalles
					set
						cant_transito = $faltante
					where
						ocnum = $ocnum_ref
						and ocseria = $ocseria
						and idprod = $idprod
				";
                $conexion->Execute($update) or die(errorpg($conexion, $update));
            }
            $rsorden_madre_verificacion -> MoveNext();
        }
        $add_carga = "";
        if ($carga_completa == "'S'") {
            $add_carga = ",estado_orden=2";
        }
        //si es carga completa agrega a la tabla de ordenes que se cerro
        $update = "
		UPDATE 
			compras_ordenes
		set
			carga_completa=$carga_completa
			$add_carga
		where
			ocnum = $ocnum_ref
		";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
    }



    //Crear cuenta a credito
    //if ($tipocompra==2){
    //credito
    $buscar = "select * from compras where idcompra=$idcompra";
    $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $bc = $buscar = "Select max(idcta) as mayor from cuentas_empresa";
    $rsm = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $mayor = intval($rsm->fields['mayor']) + 1;
    $idcta = $mayor;

    $factura = antisqlinyeccion($rs->fields['facturacompra'], 'text');
    $fechacompra = antisqlinyeccion($rs->fields['fechacompra'], 'date');
    $iva10 = floatval($rs->fields['iva10']);
    $iva5 = floatval($rs->fields['iva5']);
    $ex = floatval($rs->fields['exenta']);
    $totalc = floatval($rs->fields['total']);
    $idprov = intval($rs->fields['idproveedor']);

    // importacion

    $idtipo_origen = intval($rs->fields['idtipo_origen']);


    $buscar = "SELECT tipo_origen.idtipo_origen FROM tipo_origen WHERE UPPER(tipo_origen.tipo) like '%IMPORTACION%' ";
    $rs_importacion = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idorigen_importacion = $rs_importacion->fields['idtipo_origen'];


    // fin de importacion
    // credito
    if ($tipocompra == 2) {
        $tipo = 1;
    }
    // contado
    if ($tipocompra == 1) {
        $tipo = 2;
        $vencimientofac = "NULL";
    }

    ////////////////////
    if ($proveedores_importacion == "S") {
        if ($idorigen_importacion == $idtipo_origen) {
            $iva10 = 0;
            $iva5 = 0;
            $ex = 0;
        }
    }


    $insertar = "Insert into cuentas_empresa 
				(idcta,facturanum,fechacompra,totalcompra,totaliva10,totaliva5,totalex,registrado_por,registradoel,
				idproveedor,saldo_activo,estado,clase,idempresa,factura_venc,tipo,idcompra)
				values
				($mayor,$factura,$fechacompra,$totalc,$iva10,$iva5,$ex,$idusu,'$ahora',
				$idprov,$totalc,1,1,$idempresa,$vencimientofac,$tipo,$idcompra)";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


    //}


    // para generar cuentas version nueva
    $consulta = "
	INSERT INTO facturas_proveedores
	(idcompra, tipo_factura, id_proveedor, fecha_compra, fecha_carga, usuario_carga, factura_numero, fecha_valida, validado_por, total_factura, total_iva10, total_iva5, total_exenta, anulado_por, anulado_el, vencimiento_factura, estado, total_iva, estado_carga, timbrado, vtotimbrado, saldo_factura, cobrado_factura, quita_factura, iddeposito, idsucursal_fact, idtipocomprobante, cdc) 
	select idcompra, tipocompra tipo_factura, idproveedor as id_proveedor, fechacompra as fecha_compra, registrado as fecha_carga, registrado_por as usuario_carga, facturacompra as factura_numero, NULL as fecha_valida, NULL as validado_por, total as total_factura, 0 as total_iva10, 0 as total_iva5, 0 as total_exenta, NULL as anulado_por, NULL as anulado_el, vencimiento as vencimiento_factura, 1 as estado, iva10+iva5 as total_iva, 3 as estado_carga, timbrado, vto_timbrado as vtotimbrado, total as saldo_factura, 0 as cobrado_factura, 0 as quita_factura, 0 as iddeposito, sucursal, idtipocomprobante, cdc
	from compras 
	where 
	estado <> 6
	and idcompra = $idcompra
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    /// ojito aca que estoy editando codigo
    if ($proveedores_importacion == "S") {

        if ($idtipo_origen != $idorigen_importacion) {
            $consulta = "
			update facturas_proveedores 
			set 
			fact_num = CAST(substring(factura_numero from 7 for 9) as UNSIGNED)
			where 
			fact_num is null
			and idcompra = $idcompra
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        } else {
            $consulta = "SELECT factura_numero
			from facturas_proveedores 
			where 
			 idcompra = $idcompra
			";
            $rs_fac_num = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $fac_num_verificar = $rs_fac_num->fields["factura_numero"];
            $length = strlen($fac_num_verificar);
            // Verificar si la cadena tiene el formato "###-###-#######"
            // echo $fac_num_verificar;exit;
            if ($length == 9) {
                $consulta = "
				update facturas_proveedores 
				set 
				fact_num = CAST(substring(factura_numero from 7 for 9) as UNSIGNED)
				where 
				fact_num is null
				and idcompra = $idcompra
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            } elseif ($length <= 9 && $length > 0) {
                $consulta = "
				update facturas_proveedores 
				set 
				fact_num = CAST(factura_numero as UNSIGNED)
				where 
				fact_num is null
				and idcompra = $idcompra
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            } else {
            }
            ///////////////////////////////
        }
    } else {

        $consulta = "
		update facturas_proveedores 
		set 
		fact_num = CAST(substring(factura_numero from 7 for 9) as UNSIGNED)
		where 
		fact_num is null
		and idcompra = $idcompra
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    }

    ///fin de edicion de importacion
    ////////////////////////////////
    ////////////////////////////////



    // credito
    if ($tipocompra == 2) {
        // operaciones_proveedores
        $consulta = "
		INSERT INTO operaciones_proveedores
		(idcta, idproveedor, idfactura, monto_factura, abonado_factura, quita_factura, saldo_factura, estado, fecha_factura, fecha_cancelacion, fecha_ultimopago, fecha_prox_vencimiento, saldo_atrasado, idperiodo, plazo_periodo, plazo_periodo_remanente, plazo_periodo_abonado, dias_atraso, max_atraso, prom_atraso, monto_cuota) 
		select idcta, idproveedor, 
		(select id_factura from facturas_proveedores where facturas_proveedores.idcompra = cuentas_empresa.idcompra and estado <> 6)  as idfactura, 
		totalcompra as monto_factura, 0 as abonado_factura, 0 as quitafactura, totalcompra as saldo_factura, 1 as estado, fechacompra as fecha_factura,
		NULL as fecha_cancelacion, NULL as fecha_ultimopago, factura_venc as fecha_prox_vencimiento, 0 as saldo_atrasado, 11 as idperiodo, 1 as plazo_periodo, 1 as plazo_periodo_remanente, 0 as plazo_periodo_abonado, 0 as dias_atraso, 0 as max_atraso, 0 as prom_atraso, totalcompra as monto_cuota
		from cuentas_empresa 
		where
		idcompra = $idcompra
		and estado <> 6
		and (select tipo_factura from facturas_proveedores where facturas_proveedores.idcompra = cuentas_empresa.idcompra and estado <> 6) = 2;
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		select idoperacionprov 
		from operaciones_proveedores 
		where 
		idcta = $idcta 
		and estado <> 6
		order by idoperacionprov desc
		limit 1
		";
        $rsop = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idoperacionprov = $rsop->fields['idoperacionprov'];

        // construye detalle
        $consulta = "
		select idvencimiento, idtran, vencimiento, monto_cuota 
		from tmpcompravenc 
		where 
		idtran = $idt
		order by vencimiento asc
		";
        $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $i = 0;
        while (!$rsdet->EOF) {
            $i++;
            $monto_cuota = $rsdet->fields['monto_cuota'];
            $vencimiento = $rsdet->fields['vencimiento'];
            $consulta = "
			INSERT INTO operaciones_proveedores_detalle
			(idoperacionprov, periodo, monto_cuota, cobra_cuota, quita_cuota, saldo_cuota, vencimiento, fecha_can, fecha_ultpago, dias_atraso, dias_pago, estado_saldo) 
			VALUES 
			($idoperacionprov,$i,$monto_cuota,0,0,$monto_cuota,'$vencimiento',NULL, NULL, 0, NULL, 1)
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $rsdet->MoveNext();
        }

    } // if ($tipocompra==2){


    // ahora solo falta tocar en anular compras y luego en orden de pago y anular orden de pago


    $consulta = "
	select max(id_factura) as id_factura 
	from facturas_proveedores 
	where 
	idcompra = $idcompra 
	and estado <> 6
	";
    $rsfac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id_factura = $rsfac->fields['id_factura'];

    $consulta = "
	select permite_precio_venta_manual 
	from preferencias_compras 
	limit 1
	";
    $rsprefcomp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $permite_precio_venta_manual = $rsprefcomp->fields['permite_precio_venta_manual'];
    //echo $permite_precio_venta_manual;
    if ($permite_precio_venta_manual == 'S') {
        // primero hacer backup de los precios viejos
        $consulta = "
		select *, insumos_lista.idproducto as idprodsuc
		from tmpcompradeta 
		inner join insumos_lista on insumos_lista.idinsumo = tmpcompradeta.idprod
		where
		insumos_lista.idproducto  is not null
		and tmpcompradeta.idt=$idt 
		and tmpcompradeta.cambia_pventa = 'S'
		";
        $rscompraprec = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // recorre y hace un backup de los precios viejos
        while (!$rscompraprec->EOF) {
            $idprodsuc = intval($rscompraprec->fields['idprodsuc']);
            $precioventa = floatval($rscompraprec->fields['precioventa']);
            $consulta = "
			INSERT INTO `cambio_precio_compra_log`
			(idcompra, idproducto, idsucursal, activo_suc, precio_anterior, precio_nuevo) 
			SELECT $idcompra, idproducto, idsucursal, activo_suc, precio, $precioventa
			FROM productos_sucursales
			WHERE
			idproducto = $idprodsuc
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $rscompraprec->MoveNext();
        }
        $consulta = "
		update productos_sucursales 
		set
		precio = (
				select precioventa
				from tmpcompradeta 
				inner join insumos_lista on insumos_lista.idinsumo = tmpcompradeta.idprod
				where
				insumos_lista.idproducto = productos_sucursales.idproducto
				and insumos_lista.idproducto  is not null
				and tmpcompradeta.idt=$idt 
				limit 1
				)
		where
		(
			select precioventa
			from tmpcompradeta 
			inner join insumos_lista on insumos_lista.idinsumo = tmpcompradeta.idprod
			where
			insumos_lista.idproducto = productos_sucursales.idproducto
			and insumos_lista.idproducto  is not null
			and tmpcompradeta.idt=$idt 
			limit 1
		) is not null
		and idproducto in (
			select insumos_lista.idproducto
			from tmpcompradeta 
			inner join insumos_lista on insumos_lista.idinsumo = tmpcompradeta.idprod
			where
			insumos_lista.idproducto = productos_sucursales.idproducto
			and insumos_lista.idproducto  is not null
			and tmpcompradeta.idt=$idt 
			and tmpcompradeta.cambia_pventa = 'S'
		)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        //echo $consulta;
    }
    //exit;


    //Por ultimo, marcamos la transaccion
    $update = "Update transacciones_compras set estado=3 where numero=$idt ";
    $conexion->Execute($update) or die(errorpg($conexion, $update));

    //Eliminamos los Temporales
    $delete = "delete from  tmpcompras  where idtran=$idt and idempresa = $idempresa";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));

    $delete = "delete from  tmpcompradeta  where idt=$idt and idemp = $idempresa";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));

    $delete = "delete from  tmpcompravenc  where idtran=$idt";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));




    // inserta detalle de factura
    $consulta = "
	INSERT INTO facturas_proveedores_compras
	(idcompradet,id_factura, 
	idconcepto, idproducto, cantidad, precio, 
	subtotal, idmoneda, lote, vencimiento,  
	estado, monto_iva, iva_porc, idtipoiva) 
	select 
	idregs,
		(
		select id_factura from facturas_proveedores 
		where 
		facturas_proveedores.idcompra = compras_detalles.idcompra 
		limit 1
		), 
	idconcepto, compras_detalles.codprod, cantidad, compras_detalles.costo, 
	compras_detalles.subtotal, 1, compras_detalles.lote, compras_detalles.vencimiento, 
	1, 0, compras_detalles.iva,  compras_detalles.idtipoiva
	from compras_detalles
	where
	idcompra = $idcompra
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
	INSERT INTO facturas_proveedores_det_impuesto
	(id_factura, codreferdet, 
	idtipoiva, iva_porc_col, monto_col,
	gravadoml, ivaml, exento)
	select 
	facturas_proveedores_compras.id_factura, facturas_proveedores_compras.pkss,
	compras_detalles.idtipoiva, tmpcompradetaimp.iva_porc_col, tmpcompradetaimp.monto_col,
	tmpcompradetaimp.gravadoml, tmpcompradetaimp.ivaml, tmpcompradetaimp.exento
	from facturas_proveedores_compras
	inner join compras_detalles on facturas_proveedores_compras.idcompradet = compras_detalles.idregs
	inner join tmpcompradetaimp on tmpcompradetaimp.idcompradet = compras_detalles.idregs
	where
	id_factura = $id_factura
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $delete = "delete from  tmpcompradetaimp  where idtran=$idt";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));

    // calcula el IVA
    $consulta = "
	update  facturas_proveedores_compras
	set 
	monto_iva = (subtotal-((subtotal)/(1+iva_porc/100)))
	where
	id_factura = $id_factura
	";
    $rsfac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // inserta en conceptos
    $consulta = "
	INSERT INTO cn_conceptos_mov
	(
	idconcepto, codrefer, fecha_comprobante, 
	registrado_el, registrado_por, estado, idconceptomovtipo, 
	year_comprobante, monto_comprobante, iva_comprobante
	)
	select 
	facturas_proveedores_compras.idconcepto, pkss, facturas_proveedores.fecha_compra, 
	facturas_proveedores.fecha_carga, facturas_proveedores.usuario_carga, 1, 1,
	YEAR(facturas_proveedores.fecha_compra), subtotal, facturas_proveedores_compras.monto_iva
	from facturas_proveedores_compras
	inner join facturas_proveedores on facturas_proveedores.id_factura = facturas_proveedores_compras.id_factura
	where
	facturas_proveedores_compras.id_factura = $id_factura
	and facturas_proveedores.idcompra > 0
	and facturas_proveedores_compras.idconcepto is not null
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // busca si tiene activada la preferencia de recargo de precio de venta basado en el costo
    $consulta = "Select usa_recargo_precio_costo from preferencias_caja limit 1";
    $rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $usa_recargo_precio_costo = $rsprefcaj->fields['usa_recargo_precio_costo'];
    // si tiene activado
    if ($usa_recargo_precio_costo == 'S') {
        $consulta = "
		SELECT idproducto, compras_detalles.costo, productos.recargo_auto_costo,
		
		CASE productos.redondeo_direccion_recauto
			WHEN 'A' THEN 
				CEIL(((compras_detalles.costo*(productos.recargo_auto_costo/100))
				+compras_detalles.costo)/POW(10,redondeo_ceros_recauto))
				*(POW(10,redondeo_ceros_recauto)) 
			WHEN 'B' THEN 
				FLOOR(((compras_detalles.costo*(productos.recargo_auto_costo/100))
				+compras_detalles.costo)/POW(10,redondeo_ceros_recauto))
				*(POW(10,redondeo_ceros_recauto)) 
			ELSE
				ROUND(((compras_detalles.costo*(productos.recargo_auto_costo/100))
				+compras_detalles.costo)/POW(10,redondeo_ceros_recauto))
				*(POW(10,redondeo_ceros_recauto)) 
		END as precio_redondeado
		
		
		FROM compras_detalles
		inner join insumos_lista on insumos_lista.idinsumo = compras_detalles.codprod
		inner join productos on productos.idprod_serial = insumos_lista.idproducto
		WHERE
		productos.recargo_auto_costo > 0
		and idcompra = $idcompra
		";
        $rscambioprecio = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        while (!$rscambioprecio->EOF) {

            $idproducto = $rscambioprecio->fields['idproducto'];
            $precio = $rscambioprecio->fields['precio_redondeado'];


            $consulta = "
			update productos_sucursales 
			set
			precio = $precio
			where
			idproducto = $idproducto
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // actualizar lista precios 1
            $consulta = "
			update productos_listaprecios
			set
			precio = COALESCE((
			select precio 
			from productos_sucursales 
			where
			productos_sucursales.idproducto = productos_listaprecios.idproducto
			and productos_sucursales.idsucursal = productos_listaprecios.idsucursal
			),0),
			reg_por = $idusu,
			reg_el = '$ahora'
			where
			idlistaprecio = 1
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // actualizar lista precios con recargo automatico // ((precio*(lista_precios_venta.recargo_porc/100))+precio)
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
			and idproducto = $idproducto
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $rscambioprecio->MoveNext();
        }

    } // if($usa_recargo_precio_costo == 'S'){





    $res = [
        'idcompra' => $idcompra
    ];
    return $res;

}
function verificar_deposito_insumo($parametros_array)
{
    global $conexion;
    $idproducto = $parametros_array['idinsumo'];
    $consulta = "select iddeposito,max(disponible) from gest_depositos_stock_gral where idproducto=$idproducto limit 1";
    $respuesta = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $res = [
        'iddeposito' => $respuesta->fields['iddeposito']
    ];
    return $res;
}
function usa_cotizacion($idcompra)
{
    global $conexion;
    $cot = [];
    //consulta el campo en compras para verificar si usa la cotizacion de despacho
    //por defecto al crear la compra este compo esta en N pero al agregar una cotizacoin
    // de despacho este cambia a S en la compra  no asi en los gastos asociados
    $consulta = "SELECT usa_cot_despacho from compras where idcompra=$idcompra";
    $rs_usa_cot_despacho = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cot['usa_cot_despacho'] = ($rs_usa_cot_despacho->fields['usa_cot_despacho']);


    //verifica si existe la cotizacion de despacho
    $consulta = "SELECT despacho.cotizacion from despacho WHERE despacho.idcompra=$idcompra and estado=1 ";
    $rs_detalles_despacho = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cot['cot_despacho'] = floatval($rs_detalles_despacho->fields['cotizacion']);


    //busca la cotizacion de la moneda si no existe idcot la moneda no cotiza pero en la tabla de compras
    // existe una columna moneda la cual nos indica que moneda es
    $consulta = "SELECT cotizaciones.cotizacion from cotizaciones
	WHERE cotizaciones.idcot = (SELECT compras.idcot from compras where idcompra = $idcompra) 
	";
    $rs_detalles_compra = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cot['cot_compra'] = floatval($rs_detalles_compra->fields['cotizacion']);
    if ($cot['cot_compra'] == 0) {
        $consulta = "select cotizacion from cotizaciones where cotizaciones.fecha = 
		(select compras.fechacompra from compras where idcompra = $idcompra)  order by cotizaciones.idcot desc limit 1";
        $gscotiz = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $cot['cot_compra'] = floatval($gscotiz->fields['cotizacion']);
    }

    return $cot;

}
function verificar_compra($parametros_array)
{
    global $conexion;
    global $ahora;

    require_once('../insumos/preferencias_insumos_listas.php');
    require_once("../compras/preferencias_compras.php");
    global $preferencias_costo_promedio;
    global $preferencias_importacion;


    /* //Paso2 Actualizar ubicacion en costo_productos (SE HACE ABAJO EN EL WHLE)
    $update="Update costo_productos set ubicacion=$deposito where idcompra=$idcompra";
    $conexion->Execute($update) or die(errorpg($conexion,$update));
    */
    $id_factura = $parametros_array["id_factura"];
    $fecha_compra = $parametros_array["fecha_compra"];

    $idprov = 0;
    $idprov = intval($parametros_array["id_proveedor"]);
    if ($idprov == 0) {
        $idprov = $parametros_array["idprov"];
    }

    $factura_numero = $parametros_array["factura_numero"];
    $fecha_compra = $parametros_array["fecha_compra"];
    $idcompra = $parametros_array["idcompra"];
    $usar_depositos_asignados = $parametros_array["usar_depositos_asignados"];
    $deposito = $parametros_array["deposito"];
    $iddeposito = $parametros_array["iddeposito"];
    $tiposala = $parametros_array["tiposala"];
    $idempresa = $parametros_array["idempresa"];
    $idsucursal = $parametros_array["idsucursal"];
    $idusu = $parametros_array["idusu"];


    $cot_array = [];
    if ($preferencias_importacion == "S") {

        $cot_array = usa_cotizacion($idcompra);

    }
    // array de insumos que no se aplican el gasto asociado
    $consulta = "SELECT idinsumo FROM insumos_lista 
    WHERE UPPER(insumos_lista.descripcion) LIKE '%DESCUENTO%' 
    OR UPPER(insumos_lista.descripcion) LIKE '%AJUSTE%'";
    $respuesta_insumos_no_aplica = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $ids_no_aplica = []; // Array para almacenar los IDs_no_aplica obtenidos

    while (!$respuesta_insumos_no_aplica->EOF) {
        $ids_no_aplica[] = $respuesta_insumos_no_aplica->fields['idinsumo']; // Agregar el ID al array
        $respuesta_insumos_no_aplica->MoveNext();
    }



    ///////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////
    //Paso 3: stock gral de productos While


    $buscar = "";

    $parametros_gastos = [
        "idcompra_ref" => $idcompra
    ];
    $response_gastos = relacionar_gastos($parametros_gastos);

    if ($preferencias_importacion == "S") {
        $buscar = "SELECT compras_detalles.*, insumos_lista.maneja_lote 
		from compras_detalles 
		INNER JOIN insumos_lista on insumos_lista.idinsumo = compras_detalles.codprod 
		where idcompra=$idcompra order by idregs asc";
    } else {
        $buscar = "Select * from compras_detalles where idcompra=$idcompra order by idregs asc";
    }
    $rs2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    // inicia la verificacion por cada insumo
    while (!$rs2->EOF) {

        $idp = antisqlinyeccion($rs2->fields['codprod'], 'int');
        $costo = floatval($rs2->fields['costo']);

        $cantidad = floatval($rs2->fields['cantidad']);

        //si no viene null
        $lote = antisqlinyeccion($rs2->fields['lote'], 'text');
        $vencimiento = antisqlinyeccion($rs2->fields['vencimiento'], 'date');

        $idcompra = antisqlinyeccion($rs2->fields['idcompra'], 'int');
        /*$convertir=intval($rs2->fields['convertir']);
        if ($convertir==2){
            $cantidad=$cantidad*1000;

        }*/
        $buscar = "Select descripcion from insumos_lista where idinsumo=$idp";
        $rspr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $pchar = antisqlinyeccion($rspr->fields['descripcion'], 'text');


        $consulta = "SELECT usa_cot_despacho from compras where idcompra = $idcompra ";
        $rs_usa_cot_despacho = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $usa_cot_despacho = ($rs_usa_cot_despacho->fields['usa_cot_despacho']);


        $consulta = "SELECT despacho.cotizacion from despacho WHERE despacho.idcompra=$idcompra and estado=1";
        $rs_detalles_despacho = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $cotizacion_despacho = floatval($rs_detalles_despacho->fields['cotizacion']);


        $consulta = "SELECT cotizaciones.cotizacion from cotizaciones
		WHERE cotizaciones.idcot = ( SELECT compras.idcot from compras where idcompra = $idcompra ) 
		";
        $rs_detalles_compra = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $cotizacion_compra = floatval($rs_detalles_compra->fields['cotizacion']);

        if ($usa_cot_despacho == "S") {
            $costo = ($costo / $cotizacion_compra) * $cotizacion_despacho;
        }

        $costo = sprintf('%.3f', $costo);

        $parametros_array = [
            "lote" => $lote,
            "vencimiento" => $vencimiento,
            "idp" => $idp,
            "costo" => $costo,
            "cantidad" => $cantidad,
            "idcompra" => $idcompra
        ];
        $idseriepkcos = buscar_idseriepkcos_costo_productos($parametros_array);


        $buscar = "Select costo_promedio from costo_productos where idseriepkcos=$idseriepkcos";
        $rs_costo_prod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $costo_promedio = $rs_costo_prod->fields['costo_promedio'];






        if ($usar_depositos_asignados == 'S') {
            if (isset($rs2->fields['iddeposito_compra']) && $rs2->fields['iddeposito_compra'] > 0 && $deposito == 0) {

                $iddeposito = intval($rs2->fields['iddeposito_compra']);
                $deposito = intval($rs2->fields['iddeposito_compra']);
                // echo $deposito." ".($rs2->fields['iddeposito_compra']);
            }
        }
        $buscar = "select * from gest_depositos_stock_gral where idproducto=$idp and iddeposito=$deposito";

        $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idprod_depo = intval($rsb->fields['idproducto']);

        if ($idprod_depo > 0) {
            //ya existe en el stock gral, damos update nomas
            $update = "";
            if ($preferencias_importacion == "S") {
                $update = "Update gest_depositos_stock_gral set disponible=disponible+$cantidad, costo_promedio=$costo_promedio where idproducto=$idp and iddeposito=$deposito";
            } else {
                $update = "Update gest_depositos_stock_gral set disponible=disponible+$cantidad where idproducto=$idp and iddeposito=$deposito";
            }
            $conexion->Execute($update) or die(errorpg($conexion, $update));
            movimientos_stock($idp, $cantidad, $iddeposito, 1, '+', $id_factura, $fecha_compra);

        } else {
            //$tiposala=1;//para forzar 	ue sea deposito siempre
            //no existe, insert
            $insertar = "Insert into gest_depositos_stock_gral
				(iddeposito,idproducto,disponible,tipodeposito,last_transfer,estado,descripcion,idempresa, costo_promedio)
				values
				($iddeposito,$idp,$cantidad,$tiposala,'$ahora',1,$pchar,$idempresa, $costo_promedio)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
            movimientos_stock($idp, $cantidad, $iddeposito, 1, '+', $id_factura, $fecha_compra);

        }

        /*-------------------------------------------------INSERTAR EN DEPOSITO---------------------------------------*/

        $buscar = "Select idseriepkcos,descripcion,
		lote,vencimiento,numfactura as facturanum,id_producto,precio_costo
		from costo_productos 
		inner join insumos_lista on insumos_lista.idinsumo=costo_productos.id_producto
		where idseriepkcos=$idseriepkcos
		";

        $rstm = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        // si no existe inserta
        if (intval($rstm->fields['id_producto']) == 0) {
            //es imposible que entre aqui ya que costo_productos es iniciado al realizar la compra
            //es una seguridad que ya existia anteriormente pero si entra aqui por algun motivo
            //verificaremos nuevamente los gastos relacionados
            //para script viejo no afecata lote y vencimiento ya que permite nulo
            $inserta = "insert into costo_productos 
				(idempresa,id_producto,registrado_el,precio_costo,idproveedor,cantidad,numfactura,
				costo2,disponible,idcompra,fechacompra,modificado_el,lote,vencimiento)
				values
				($idempresa,$idp,'$ahora',$costo,$idprov,'$cantidad','$factura_numero',
				0,'$cantidad',$idcompra,'$fecha_compra','$ahora',$lote,$vencimiento)";

            $conexion->Execute($inserta) or die(errorpg($conexion, $inserta));




        }



        //echo $buscar;
        //exit;
        $serie = intval($rstm->fields['idseriepkcos']);
        $idproductocostos = antisqlinyeccion($rstm->fields['id_producto'], 'int');


        $subpro = intval($rstm->fields['subprod']);
        $produccion = intval($rstm->fields['produccion']);
        $pchar = antisqlinyeccion($rstm->fields['descripcion'], 'text');
        $lote = antisqlinyeccion($rstm->fields['lote'], 'text');
        $factura = antisqlinyeccion($rstm->fields['facturanum'], 'text');
        $nota = antisqlinyeccion($rstm->fields['notanum'], 'text');
        $pcosto = floatval($rstm->fields['precio_costo']);
        $vto = antisqlinyeccion($rstm->fields['vencimiento'], 'text');
        //No existe y damos de alta

        $insertar = "Insert into gest_depositos_stock
		(idproducto,idseriecostos,disponible,cantidad,iddeposito,
		subproducto,produccion,lote,vencimiento,recibido_el,
		autorizado_por,verificado_por,verificado_el,facturanum,notanum,descripcion,costogs,idempresa)
		values
		($idproductocostos,$serie,$cantidad,$cantidad,$iddeposito,$subpro,$produccion,$lote,$vencimiento,
		'$ahora',$idusu,$idusu,'$ahora',$factura,$nota,$pchar,$pcosto,$idempresa)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

        //echo $insertar."<br />";


        /*-----------------------------------------------------------------------------------------------------------*/
        //Por ultimo, hacemos efectivo el ingreso en costo_productos
        $update = "Update costo_productos set disponible=$cantidad,ubicacion=$iddeposito,modificado_el='$ahora' where idseriepkcos=$idseriepkcos ";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        $rs2->MoveNext();
    }


    // actualizamos el proveedor en insumos_lista
    $consulta = "
	update insumos_lista 
	set 
	idproveedor = (select idproveedor from compras where idcompra = $idcompra)
	where
	estado = 'A'
	and idinsumo in (select codprod from compras_detalles where idcompra=$idcompra)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    //Actualizmos la revision en compras
    $update = "
	update gest_depositos_compras 
	set 
	revisado_por=$idusu,
	fecha_revision='$ahora',
	iddeposito=$deposito
	where 
	idcompra=$idcompra 
	";
    $conexion->Execute($update) or die(errorpg($conexion, $update));


    // registra la revision en compras en tabla nueva
    $consulta = "
	update facturas_proveedores 
	set 
	iddeposito = $iddeposito,
	fecha_valida = '$ahora',
	validado_por = $idusu
	where
	idcompra = $idcompra
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}

function despacho_verificar($id)
{
    global $conexion;
    $buscar = "
	Select iddespacho from despacho where idcompra = $id 
	limit 1
	";
    //echo $buscar;
    $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $iddespacho = intval($rs->fields['iddespacho']);
    $res = [
        "success" => false
    ];
    if ($iddespacho > 0) {
        $res = [
            "success" => true
        ];
    }
    return $res;
}



function compra_importacion($id)
{
    global $conexion;

    ////////////////////////////////////////////

    $consulta = "SELECT idtipo_origen FROM tipo_origen WHERE  UPPER(tipo)='IMPORTACION'";
    $rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id_tipo_origen_importacion = intval($rs_guarani->fields["idtipo_origen"]);
    if ($id_tipo_origen_importacion == 0) {
        $errores = "- Por favor cree el Origen IMPORTACON.<br />";
    }

    ///////////////////////////////////////////////
    ///////////////////////////////////////////
    $buscar = "
	Select idcompra, idtipo_origen from compras where idcompra = $id 
	limit 1
	";
    //echo $buscar;
    $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idcompra = intval($rs->fields['idcompra']);
    $idtipo_origen = intval($rs->fields['idtipo_origen']);
    $res = [
        "success" => false
    ];

    if ($idtipo_origen == $id_tipo_origen_importacion) {
        $res = [
            "success" => true
        ];
    }


    return $res;
}
function relacionar_gastos($parametros_array)
{

    require_once("../compras_ordenes/preferencias_compras_ordenes.php");
    require_once("../proveedores/preferencias_proveedores.php");
    global $conexion;
    global $ahora;
    global $saltolinea;
    global $preferencias_facturas_multiples;
    global $proveedores_importacion;

    $idcompra_ref = $parametros_array['idcompra_ref'];


    $precio_costo = "";
    $costo_promedio = "";
    $costo_cif = "";

    if (intval($idcompra_ref) > 0) {
        //gastos asociados obteniendo totalidad

        $consulta = "SELECT despacho.cotizacion from despacho WHERE despacho.idcompra=$idcompra_ref and estado=1";
        $rs_detalles_despacho = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $cotizacion_despacho = floatval($rs_detalles_despacho->fields['cotizacion']);

        $consulta = "SELECT idcompra 
		from compras 
		where idcompra_ref=$idcompra_ref
		and estado != 6";


        $rs_id_array_gastos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $rs_id_array_gastos = $rs_id_array_gastos->GetArray();
        $gastos_totales = 0;
        //array con facturas  de gastos asociados es decir facturas referenciadas a
        // la factura con idcompra idcompra_ref
        // donde idcompra_ref es la propia compra la cual se desea dar ingreso  a stock
        foreach ($rs_id_array_gastos as $key => $value) {
            $idcompra_gasto = $value['idcompra'];
            $consulta = "SELECT usa_cot_despacho from compras where idcompra= $idcompra_gasto ";
            $rs_usa_cot_despacho = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $usa_cot_despacho = ($rs_usa_cot_despacho->fields['usa_cot_despacho']);


            $consulta = "SELECT cotizaciones.cotizacion from cotizaciones
			WHERE cotizaciones.idcot = ( SELECT compras.idcot from compras where idcompra = $idcompra_gasto ) 
			";
            $rs_detalles_compra = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $cotizacion_gasto = floatval($rs_detalles_compra->fields['cotizacion']);
            $consulta = "SELECT compras_detalles.subtotal as gastos,
			compras.iva5,
			compras.iva10,
			insumos_lista.descripcion,
			facturas_proveedores_det_impuesto.ivaml,
			facturas_proveedores_det_impuesto.gravadoml
			from compras_detalles
			INNER JOIN compras on compras_detalles.idcompra = compras.idcompra
			LEFT JOIN insumos_lista on   insumos_lista.idinsumo=compras_detalles.codprod
			left JOIN facturas_proveedores_det_impuesto on facturas_proveedores_det_impuesto.id_factura = compras.idcompra 
			where compras_detalles.idcompra = $idcompra_gasto
			and compras_detalles.codprod not in (
			SELECT idinsumo FROM insumos_lista 
			WHERE UPPER(insumos_lista.descripcion) like \"%DESCUENTO%'\" 
			or  UPPER(insumos_lista.descripcion) like \"%AJUSTE%\" )";
            $rs_gastos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $subtotal = 0;
            while (!$rs_gastos->EOF) {
                $subtotal = $rs_gastos->fields['gastos'];
                $iva5 = floatval($rs_gastos->fields['iva5']);
                $iva10 = floatval($rs_gastos->fields['iva10']);
                $gravadoml = floatval($rs_gastos->fields['gravadoml']);
                $producto = ($rs_gastos-> fields ['descripcion']);
                $iva_valor = floatval($iva5) != 0 ? floatval($iva5) : floatval($iva10);
                $subtotal = floatval($subtotal) - $iva_valor;
                //cambiar por concepto de despacho y flete
                if ($producto == "DESPACHO" || $producto == "SERVICIO DE FLETE") {
                    $subtotal = $gravadoml;
                    $iva_valor = $ivaml;
                }
                $sumsubtotal = $subtotal;
                //$valor_subtotal_gastos_compra+=$subtotal;
                // guardar_diccionario($idmoneda, $facturas, $factura);
                $aux1 = 0;
                if ($usa_cot_despacho == "S") {
                    $gastos_totales += (floatval($sumsubtotal) / $cotizacion_gasto) * $cotizacion_despacho;
                    $aux1 = (floatval($sumsubtotal) / $cotizacion_gasto) * $cotizacion_despacho;
                } else {
                    $gastos_totales += (floatval($sumsubtotal));
                    $aux1 = (floatval($sumsubtotal));
                }
                //echo $aux1."<br     />";
                $rs_gastos->MoveNext();

                //modificado con Don Ramon
                //echo "a-".$producto."b-". $usa_cot_despacho. "c-". $aux1."<br     >";
            }
        }
        //echo $gastos_totales;

        // desde aqui se obtiene gastos_totales








        //Compra obteniendo totalidad
        $consulta = "SELECT usa_cot_despacho from compras where idcompra= $idcompra_ref ";
        $rs_usa_cot_despacho = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $usa_cot_despacho = ($rs_usa_cot_despacho->fields['usa_cot_despacho']);


        $consulta = "SELECT cotizaciones.cotizacion from cotizaciones
		WHERE cotizaciones.idcot = ( SELECT compras.idcot from compras where idcompra = $idcompra_ref ) 
		";
        $rs_detalles_compra = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $cotizacion_compra = floatval($rs_detalles_compra->fields['cotizacion']);



        $consulta = "SELECT SUM(subtotal) as total 
		from compras_detalles 
		where idcompra=$idcompra_ref
		and compras_detalles.codprod not in (
		SELECT idinsumo FROM insumos_lista 
		WHERE UPPER(insumos_lista.descripcion) like \"%DESCUENTO%'\" 
		or  UPPER(insumos_lista.descripcion) like \"%AJUSTE%\" )";
        $rs_compra = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if ($usa_cot_despacho == "S") {
            $total_compra = ($rs_compra->fields['total'] / $cotizacion_compra) * $cotizacion_despacho;
        } else {
            $total_compra = $rs_compra->fields['total'];
        }





        //Obteniendo productos
        $consulta = "SELECT compras_detalles.*, insumos_lista.maneja_lote 
		from compras_detalles
		INNER JOIN insumos_lista on insumos_lista.idinsumo = compras_detalles.codprod
		INNER JOIN compras on compras.idcompra = compras_detalles.idcompra
		where compras.idcompra = $idcompra_ref 
		and compras_detalles.codprod not in (
		SELECT idinsumo FROM insumos_lista 
		WHERE UPPER(insumos_lista.descripcion) like \"%DESCUENTO%\" 
		or  UPPER(insumos_lista.descripcion) like \"%AJUSTE%\" 
		)
		";
        $rs_detalles_compras = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        while (!$rs_detalles_compras->EOF) {

            if ($usa_cot_despacho == "S") {
                $total_producto = ($rs_detalles_compras->fields['subtotal'] / $cotizacion_compra) * $cotizacion_despacho;
                $costo = ($rs_detalles_compras->fields['costo'] / $cotizacion_compra) * $cotizacion_despacho;
            } else {
                $total_producto = $rs_detalles_compras->fields['subtotal'];
                $costo = $rs_detalles_compras->fields['costo'];
            }
            $idregs = $rs_detalles_compras->fields['idregs'];
            $lote = antisqlinyeccion($rs_detalles_compras->fields['lote'], "text");
            $vencimiento = antisqlinyeccion($rs_detalles_compras->fields['vencimiento'], "date");
            $cantidad = $rs_detalles_compras->fields['cantidad'];
            $codprod = $rs_detalles_compras->fields['codprod'];
            $gastos = (($total_producto) / ($total_compra)) * $gastos_totales;
            $precio_costo = ($gastos + $total_producto) / $cantidad;
            $whereadd = "";
            if ($lote != "NULL" && $vencimiento != "NULL") {
                $whereadd = " and vencimiento=$vencimiento and lote=$lote ";
            } else {
                $whereadd = "and vencimiento is NULL and lote is NULL ";
            }
            $consulta = "
			update
				compras_detalles
			set
				gastos=$gastos
			where
				idregs = $idregs
			";

            ////////////////////anterior
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $update = "Update costo_productos set costo_cif=$precio_costo, modificado_el='$ahora', precio_costo=$costo where id_producto=$codprod and idcompra=$idcompra_ref $whereadd  ";

            $conexion->Execute($update) or die(errorpg($conexion, $update));



            ////////////////////////////////////////////////////////////////////////////////////////////
            ////////////////////////////////////////////////////////////////////////////////////////////
            ////////////////////////////////////////////////////////////////////////////////////////////



            $idp = antisqlinyeccion($rs_detalles_compras->fields['codprod'], 'int');
            // $costo=floatval($rs_detalles_compras->fields['costo']);

            $cantidad = floatval($rs_detalles_compras->fields['cantidad']);
            $lote = antisqlinyeccion($rs_detalles_compras->fields['lote'], 'text');
            $vencimiento = antisqlinyeccion($rs_detalles_compras->fields['vencimiento'], 'date');
            $idcompra = antisqlinyeccion($rs_detalles_compras->fields['idcompra'], 'int');

            $buscar = "Select descripcion from insumos_lista where idinsumo=$idp";
            $rspr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $pchar = antisqlinyeccion($rspr->fields['descripcion'], 'text');



            //////////////////////costo promoedio




            // actualmente las compras pasan por la tabla costo_productos y dan
            // ingreso a gest_depositos_stock_gral y a sus respectivas tablas relacionadas
            // el costo dif fob y costo promedio se encuentra en costo_productos el historico, y a
            // forma de acceder rapido el ultimo valor e guarda en gest_deposito_stock_gral
            $costo = sprintf('%.3f', $costo);

            $parametros_array = [
                "lote" => $lote,
                "vencimiento" => $vencimiento,
                "idp" => $idp,
                "costo" => $costo,
                "cantidad" => $cantidad,
                "idcompra" => $idcompra
            ];
            $idseriepkcos = buscar_idseriepkcos_costo_productos($parametros_array);



            if (!in_array($idp, $ids_no_aplica)) {




                $consulta = "SELECT  costo_promedio from gest_depositos_stock_gral where idproducto=$idp";
                $rs_costo_promedio = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $costo_promedio = floatval($rs_costo_promedio->fields['costo_promedio']);

                $consulta = "select idcompra, precio_costo, costo_cif, cantidad_stock from costo_productos where idseriepkcos=$idseriepkcos";
                $rs_costo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $precio_costo = floatval($rs_costo->fields['precio_costo']);
                $costo_cif = floatval($rs_costo->fields['costo_cif']);
                $idcompra = intval($rs_costo->fields['idcompra']);
                $cantidad_stock = intval($rs_costo->fields['cantidad_stock']);


                $consulta = "SELECT despacho.cotizacion from despacho WHERE despacho.idcompra=$idcompra and estado=1 ";

                $rs_detalles_despacho = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $cotizacion_despacho = floatval($rs_detalles_despacho->fields['cotizacion']);
                $consulta = "SELECT cotizaciones.cotizacion from cotizaciones
				WHERE cotizaciones.idcot = (SELECT compras.idcot from compras where idcompra = $idcompra) 
				
				";

                $rs_detalles_compra = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $cotizacion_compra = floatval($rs_detalles_compra->fields['cotizacion']);


                $total_producto = $rs_detalles_compras->fields['subtotal'];
                $idregs = $rs_detalles_compras->fields['idregs'];
                $cantidad = $rs_detalles_compras->fields['cantidad'];
                $codprod = $rs_detalles_compras->fields['codprod'];
                if ($cotizacion_compra > 0 && $cotizacion_despacho > 0) {

                    $consulta = "SELECT usa_cot_despacho from compras where idcompra=$idcompra";
                    $rs_usa_cot_despacho = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    $usa_cot_despacho = floatval($rs_usa_cot_despacho->fields['usa_cot_despacho']);

                    if ($usa_cot_despacho == "S") {
                        $precio_costo = ($precio_costo / $cotizacion_compra) * $cotizacion_despacho;
                        $costo_cif = ($costo_cif / $cotizacion_compra) * $cotizacion_despacho;
                    } else {
                        $precio_costo = ($precio_costo);
                        $costo_cif = ($costo_cif);
                    }
                }
                $costo = null;
                if ($costo_cif == 0) {
                    $costo = $precio_costo;
                } else {
                    $costo = $costo_cif;
                }
                $buscar = "SELECT gest_depositos_stock_gral.idproducto,
					SUM(gest_depositos_stock_gral.disponible) as cantidad 
					from gest_depositos_stock_gral 
					where gest_depositos_stock_gral.estado = 1  
					and gest_depositos_stock_gral.idproducto= $codprod GROUP BY
					idproducto";
                $rs_deposito = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $cantidad_deposito = floatval($rs_deposito->fields['cantidad']);
                $respuesta01 = "NADA";
                if ($costo_cif > 0) {
                    if ($cantidad_deposito == 0 && $cantidad_stock >= 0) {
                        $costo_promedio = $costo_cif;
                        $consulta = "UPDATE costo_productos set costo_promedio=$costo_promedio, costo_cif=$costo,cantidad_stock=$cantidad_deposito,modificado_el='$ahora' WHERE idseriepkcos = $idseriepkcos ";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                        $respuesta01 = "IF PRIMARIO". $costo_promedio;
                    } elseif ($cantidad_deposito != 0) {
                        $c1 = ($costo_promedio * $cantidad_deposito);
                        $c2 = ($cantidad * $costo);
                        $c3 = ($cantidad + $cantidad_deposito);
                        $costo_promedio = ($c1 + $c2) / $c3;
                        $respuesta01 = "ELSE IF".$costo_promedio;
                        $consulta = "UPDATE costo_productos set costo_promedio=$costo_promedio, costo_cif=$costo,cantidad_stock=$cantidad_deposito,modificado_el='$ahora' WHERE idseriepkcos = $idseriepkcos ";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                        $respuesta01 = "ELSE IF".$costo_promedio;
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
						idinsumo = $codprod
						and idempresa = $idempresa
						";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                }
            };




            /////////////////////////////////////////////////////////////////////////////////
            /////////////////////////////////////////////////////////////////////////////////
            /////////////////////////////////////////////////////////////////////////////////
            /////////////////////////////////////////////////////////////////////////////////
            /////////////////////////////////////////////////////////////////////////////////
            $rs_detalles_compras->MoveNext();
        }

    }
    return ["c3" => $c3,"c2" => $c2,"c1" => $c1,"cantidad_deposito" => $cantidad_deposito,"costo_cif" => $costo_cif,"respuesta" => $respuesta01, "costo_promedio" => $costo_promedio, "idseriepkcos" => $idseriepkcos];
}
function buscar_idseriepkcos_costo_productos($parametros_array)
{
    global $conexion;


    $lote = $parametros_array['lote'];
    $vencimiento = $parametros_array['vencimiento'];
    $idp = $parametros_array['idp'];
    $costo = $parametros_array['costo'];
    $cantidad = $parametros_array['cantidad'];
    $idcompra = $parametros_array['idcompra'];


    $whereadd = "";
    if ($lote != "NULL" && $vencimiento != "NULL") {
        $whereadd = "and vencimiento=$vencimiento
		and lote=$lote ";
    } else {
        $whereadd = "and vencimiento is NULL and lote is NULL ";
    }
    //idseriepkcos es la llave primaria de costo_productos el cual fue mostrado en el esquema entidad relacion actual
    $consulta = "SELECT  idseriepkcos 
	from costo_productos 
	where id_producto=$idp 
	and  precio_costo=$costo 
	and cantidad = $cantidad 
	$whereadd
	and idcompra = $idcompra";
    $rs_id_costo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idseriepkcos = $rs_id_costo->fields['idseriepkcos'];

    return $idseriepkcos;
}

function monto_compra($idcompra)
{
    global $conexion;
    $consulta = "select sum(precio_compra_total) as pct from compras_ordenes_detalles as cod where cod.ocnum = $idcompra";
    //echo("<script>alert(" . $consulta . "');</script>");
    $rsmonto_compra = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $monto_factura = $rsmonto_compra->fields['pct'];
    return $monto_factura;
}
