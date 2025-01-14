<?php

function verificar_insumos($parametros_array)
{
    global $conexion;
    global $ahora;
    global $saltolinea;
    global $rsco;
    require_once("preferencias_insumos_listas.php");
    require_once("../categorias/preferencias_categorias.php");
    // validaciones basicas
    $valido = "S";
    $errores = "";

    $consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%MERCADERIAS\" ";
    $rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idconcepto_mercaderia = $rs_conceptos->fields['idconcepto'];

    $consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%DESPACHO\" ";
    $rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idconcepto_despacho = intval($rs_conceptos->fields['idconcepto']);



    $consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%FLETE\" ";
    $rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idconcepto_flete = intval($rs_conceptos->fields['idconcepto']);




    $consulta = "SELECT id_medida, nombre FROM medidas where estado = 1 AND 
    medidas.nombre LIKE \"%cajas\" order by nombre asc";
    $rs_cajas = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcaja = $rs_cajas->fields['id_medida'];
    $consulta = "SELECT id_medida, nombre FROM medidas where estado = 1 
    AND medidas.nombre LIKE \"%pall%\" order by nombre asc ";
    $rs_pallets = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idpallet = $rs_pallets->fields['id_medida'];

    $usa_concepto = $rsco->fields['usa_concepto'];
    $idtipoiva_venta_pred = $rsco->fields['idtipoiva_venta_pred'];
    $idtipoiva_compra_pred = $rsco->fields['idtipoiva_compra_pred'];

    $descripcion = antisqlinyeccion($parametros_array['descripcion'], 'text');
    $idconcepto = antisqlinyeccion($parametros_array['idconcepto'], 'int');
    $idmedida = antisqlinyeccion($parametros_array['idmedida'], 'int');
    $idtipoiva_compra = antisqlinyeccion($parametros_array['idtipoiva_compra'], 'int');
    $idgrupoinsu = antisqlinyeccion($parametros_array['idgrupoinsu'], 'int');
    $hab_compra = antisqlinyeccion($parametros_array['hab_compra'], 'int');
    $hab_invent = antisqlinyeccion($parametros_array['hab_invent'], 'int');
    $solo_conversion = antisqlinyeccion($parametros_array['solo_conversion'], 'int');
    $cuentacont = antisqlinyeccion($parametros_array['cuentacont'], 'int');
    $rendimiento_porc = antisqlinyeccion($parametros_array['rendimiento_porc'], 'float');

    if ($descripcion == "NULL") {
        $valido = "N";
        $errores .= " - El campo descripcion no puede estar vacio.<br />";
    }

    if ($usa_concepto == 'S') {
        if ($idconcepto == 0) {
            $valido = "N";
            $errores .= " - El campo concepto no puede ser cero o nulo.<br />";
        }
    }

    if ($idmedida == 0) {
        $valido = "N";
        $errores .= " - El campo medida no puede ser cero o nulo.<br />";
    }


    if ($idconcepto_despacho != $idconcepto && $idconcepto_flete != $idconcepto) {
        if ($idtipoiva_compra == "NULL") {
            $valido = "N";
            $errores .= " - El campo iva compra no puede estar vacio.<br />";
        }
    } else {
        $idtipoiva_compra = 0;
        $tipoiva_compra = 0;
    }

    if ($idgrupoinsu == 0) {
        $valido = "N";
        $errores .= " - El campo grupo stock no puede estar vacio.<br />";
    }

    if ($hab_compra == '') {
        $valido = "N";
        $errores .= " - El campo habilita compra debe completarse.<br />";
    }

    if ($hab_invent == '') {
        $valido = "N";
        $errores .= " - El campo habilita inventario debe completarse.<br />";
    }
    if ($hab_compra > 0) {
        if ($solo_conversion == 0) {
            if ($hab_invent == 0) {
                $valido = "N";
                $errores .= " - Cuando se habilita compra tambien debe habilitarse inventario.<br />";
            }
        }
    }
    // validar que no existe un producto con el mismo nombre
    $consulta = "
	select * from productos where descripcion = $descripcion and borrado = 'N' limit 1
	";
    $rsexpr = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // si existe producto
    $idprod_serial = $rsexpr->fields['idprod_serial'] ? $rsexpr->fields['idprod_serial'] : "";
    if ($idprod_serial > 0) {
        $errores .= "- Ya existe un producto con el mismo nombre.<br />";
        $valido = 'N';
    }
    // validar que no hay insumo con el mismo nombre
    $buscar = "Select * from insumos_lista where descripcion=$descripcion and estado = 'A' limit 1";
    $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    if ($rsb->fields['idinsumo'] > 0) {
        $errores .= "* Ya existe un articulo con el mismo nombre.<br />";
        $valido = 'N';
    }


    /////////////////

    if ($idconcepto_despacho != $idconcepto && $idconcepto_flete != $idconcepto) {
        // iva compra
        $consulta = "
		select * 
		from tipo_iva
		where 
		idtipoiva = $idtipoiva_compra
		";
        $rsiva = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $tipoiva_compra = $rsiva->fields['iva_porc'];
        $idtipoiva_compra = $rsiva->fields['idtipoiva'];

        $contabilidad = intval($rsco->fields['contabilidad']);
        if ($contabilidad == 1) {
            if ($hab_compra == '1') {
                if ($cuentacont == 0) {
                    $valido = "N";
                    $errores .= "- Debe indicar la cuenta contable para compras del producto, cuando el producto esta habilitado para compras.<br />";
                }
            }
        }
    }
    /////////////////////
    if ($rendimiento_porc <= 0) {
        $valido = "N";
        $errores .= " - El campo rendimiento no puede ser cero o negativo.<br />";
    }
    if ($rendimiento_porc > 100) {
        $valido = "N";
        $errores .= " - El campo rendimiento no puede ser mayor a 100.<br />";
    }




    $res = [
        'valido' => $valido,
        'errores' => $errores
    ];

    return $res;

}

function insumo_convertir_producto($parametros_array)
{

    global $conexion;
    global $idusu;
    global $idempresa;
    global $idsucursal;
    global $ahora;

    $idinsumo = intval($parametros_array['idinsumo']);
    $costo = floatval($parametros_array['costo']);//0
    $idtipoiva_compra = intval($parametros_array['idtipoiva']);
    $tipoiva = $parametros_array['iva_porc'];
    $cod_bar = ($parametros_array['bar_code']);
    $idcategoria = intval($parametros_array['idcategoria']);
    $idsubcate = intval($parametros_array['idsubcate']);
    $idsubcate_sec = intval($parametros_array['idsubcate_sec']);
    $margen_categoria = floatval($parametros_array['margen_seguridad_categoria']);//0
    $margen_sub_categoria = floatval($parametros_array['margen_seguridad_sub_categorias']);//0
    $margen_sub_categoria_secundaria = floatval($parametros_array['margen_seguridad_sub_categorias_secundaria']);//0
    $tipo = 'PRODU';
    // datos globales
    $idmedida = intval($parametros_array['idmedida']);//unidad
    $descripcion = $parametros_array['descripcion'];



    $facompra = $parametros_array['fcompra'];//NULL
    $fecompra = $parametros_array['fecompra'];//NULL
    //$codprod=antisqlinyeccion($parametros_array['codprod'],'text');
    $nombre = antisqlinyeccion($descripcion, 'text');
    $cantidad = antisqlinyeccion($parametros_array['cantidad'], 'float');
    $barcode = $cod_bar;
    $idmarca = antisqlinyeccion($parametros_array['idmarca'], 'int');
    $idtipoiva = $idtipoiva_compra;
    $costo = 0;
    $tipoprecio = 0;
    $pventa = 0;
    $idgrupoinsu = 0;
    $pminimo = 0;
    $pmaximo = 0;
    $listaprecio = "NULL";
    $p1 = floatval($parametros_array['p1']);
    $p2 = floatval(0);
    $p3 = floatval(0);

    $d1 = 0;
    $provee = 1;
    $catego = $idcategoria;
    $subcatego = $idsubcate;
    $medida = antisqlinyeccion($idmedida, 'int');
    //$combinado=antisqlinyeccion($_POST['combinado'],'text');
    $ubicacion = 0;
    $imagen = 'NULL';
    $keyword = 'NULL';
    $vencimiento = 0;
    $garantia = 0;
    $valido = 'S';
    $errores = '';
    //$tipo=trim(htmlentities($_POST['tipo']));
    $tipo = 'PRODU';

    $combo = antisqlinyeccion('N', 'text');
    $combinado = antisqlinyeccion('N', 'text');
    $idtipoproducto = 1;
    $combinado_tipoprecio = antisqlinyeccion('', 'int');
    $combinado_maxitem = antisqlinyeccion('', 'int');
    $combinado_minitem = antisqlinyeccion('', 'int');

    if (intval($idtipoiva) == 0) {
        $errores = $errores."* Debe indicar tipo de iva.<br />";
        $valido = 'N';
    }

    // validar que no existe un producto con el mismo nombre
    $consulta = "
	select * from productos where descripcion = $nombre and idempresa = $idempresa and borrado = 'N'
	";
    $rsexpr = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // validar que no exista un insumo con el mismo nombre


    if ($rsexpr->fields['idprod_serial'] > 0) {
        $errores = $errores."* Ya existe un producto con este nombre.<br />";
        $valido = 'N';
    }


    if ($barcode != '') {
        $permite_duplicar_cbar = "N"; // traer de preferencias
        if ($permite_duplicar_cbar == 'N') {
            $consulta = "
			select * from productos where barcode = $barcode and borrado = 'N' limit 1
			";
            $rsbarcode = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            if ($rsbarcode->fields['idprod_serial'] > 0) {
                $errores = $errores."* Ya existe otro producto con el mismo codigo de barras.<br />";
                $valido = 'N';
            }
        }
    }




    // conversiones
    $consulta = "SELECT max(idprod_serial) as mayor FROM productos";
    $rsprox = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $proxid = intval($rsprox->fields['mayor']) + 1;
    $codprod = antisqlinyeccion($proxid, "text");
    $codp = $proxid;
    $idproducto = $proxid;


    if ($valido == 'S') {

        $inserta = "
		insert into productos
		(idempresa,idprod,idprod_serial,descripcion,costo_actual,idcategoria,idmedida,ubicacion,
		imagen,controla_vencimiento,controla_garantia,registrado_el,keywords,idproveedor,disponible,
		precio_min,precio_max,lista_precios,precio_venta,registrado_por,idsubcate,facturacompra,fechacompra,idtipoiva,tipoiva,
		p1,p2,p3,desc1,sucursal,combinado,idpantallacocina,idimpresoratk,barcode,combo,idtipoproducto,combinado_tipoprecio,combinado_maxitem,idmarca,combinado_minitem,descuento,idgen)
		values 
		($idempresa,$codprod,$codprod,$nombre,$costo,$catego,$medida,$ubicacion,$imagen,$vencimiento,
		$garantia,current_timestamp,$keyword,$provee,$cantidad,$pminimo,$pmaximo,$listaprecio,$pventa,$idusu,$subcatego,
		$facompra,$fecompra,$idtipoiva,$tipoiva,$p1,$p2,$p3,$d1,$idsucursal,$combinado,0,0,$barcode,$combo,$idtipoproducto,$combinado_tipoprecio,$combinado_maxitem,$idmarca,$combinado_minitem,0,0)";
        $conexion->Execute($inserta) or die(errorpg($conexion, $inserta));

        // si no usa receta
        //if($usa_receta == 'N'){
        if ($tipo == 'PRODU') {

            $consulta = "
			update insumos_lista 
			set
			idproducto = $idproducto
			where
			idinsumo = $idinsumo
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            //Ingrediente
            $insertar = "Insert ignore into ingredientes (idinsumo,estado,idempresa) values ($idinsumo,1,$idempresa)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
            //trae el que acaba de insertar
            $buscar = "Select max(idingrediente) as ingre from ingredientes where idempresa = $idempresa and idinsumo = $idinsumo";
            $rsg = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idingrediente = intval($rsg->fields['ingre']);


            //Receta
            $insertar = "Insert into recetas
			(nombre,estado,creado_por,fecha_creacion,ultimo_cambio,ultimo_cambio_por,idproducto,idempresa)
			values
			($nombre,1,$idusu,'$ahora','$ahora',$idusu,$idproducto,$idempresa)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
            //trae el que acaba de insertar
            $buscar = "Select max(idreceta) as mayor from recetas ";
            $rsf1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idreceta = $rsf1->fields['mayor'];


            //Detalle de la receta
            $insertar = "Insert into recetas_detalles
			(idreceta,idprod,ingrediente,cantidad,sacar,alias,idempresa)
			values
			($idreceta,'$idproducto',$idingrediente,1,'N',$nombre,$idempresa)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));




        }



        //De inmediato registramos la tabla de costos
        $inserta = "insert into costo_productos 
		(idempresa,id_producto,registrado_el,precio_costo,idproveedor,cantidad,numfactura)
		values
		($idempresa,$codprod,current_timestamp,$costo,$provee,$cantidad,$facompra)";
        $conexion->Execute($inserta) or die(errorpg($conexion, $inserta));

        if ($vencimiento == 1) {
            for ($i = 1;$i <= $cantidad;$i++) {
                $insertar = "Insert into productos_vencimiento
				(idprod,idempresa,sucursal,factura)
				values
				($codprod,$idempresa,1,$facompra)";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
            }


        }

        // busca las sucursales que tienen 1 sola impresora
        $consulta = "
		select *
		from 	(
				SELECT idsucursal, idimpresoratk, idempresa, count(*) as total 
				FROM impresoratk 
				where 
				idempresa = $idempresa
				and tipo_impresora = 'COC'
				 group by idsucursal
				 ) as impresoras_suc
		where
		total = 1
		and idempresa = $idempresa
		";
        $rssucimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // recorre las sucursales que tienen 1 sola impresora
        while (!$rssucimp->EOF) {

            // trae la impresora de esa sucursal
            $idimpresoratk = $rssucimp->fields['idimpresoratk'];

            // busca si existe en producto_impresora
            $consulta = "
			SELECT * 
			FROM producto_impresora
			where
			idproducto = $proxid
			and idempresa = $idempresa
			and idimpresora = $idimpresoratk
			";
            $rsprodimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // si no existe agrega
            if (intval($rsprodimp->fields['idproducto']) == 0) {

                $consulta = "
				INSERT INTO producto_impresora 
				(idproducto, idimpresora, idempresa) 
				VALUES 
				($proxid, $idimpresoratk, $idempresa);
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            }


            $rssucimp->MoveNext();
        }


        // insertamos en sucursales
        $consulta = "
		INSERT IGNORE INTO productos_sucursales (idproducto, idsucursal, idempresa, precio, activo_suc) 
		select idprod_serial as idproducto, sucursales.idsucu as idsucursal, empresas.idempresa as idempresa, p1 as precio, 1 as activo_suc
		from 
		productos, sucursales, empresas 
		where 
		productos.idprod_serial 
		not in 
		(select productos_sucursales.idproducto from productos_sucursales where idempresa = empresas.idempresa and idsucursal = sucursales.idsucu )
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
		and idproducto = $proxid
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // generamos el producto para todas las franquicias
        $consulta = "
		INSERT INTO productos_franquicias
		(idproducto,idfranquicia) 
		select 
		$idproducto,idfranquicia
		from franquicia
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        $idproducto = $proxid;
        // busca si el producto es solo conversion
        $consulta = "
		select * from insumos_lista where idproducto = $idproducto
		";
        $rsins = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // si es solo conversion
        if ($rsins->fields['solo_conversion'] == 1) {
            // desactiva para la venta
            $consulta = "
			update productos_sucursales set activo_suc = 0 where idproducto = $idproducto
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }


        $insertado = 'S';

        //echo "aca";exit;
        return ["valido" => $valido,"errores" => $errores];
        // header("location: gest_insumos_edit.php?id=".$idinsumo);
        // exit;

    }




}
