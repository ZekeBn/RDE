<?php



function validar_producto($parametros_array)
{

    // recibe parametros
    global $ahora;
    global $conexion;
    global $saltolinea;
    global $rsco; // preferencias
    $usa_concepto = $rsco->fields['usa_concepto'];
    $contabilidad = $rsco->fields['contabilidad'];

    $descripcion = antisqlinyeccion($parametros_array['descripcion'], "text");
    $barcode = antisqlinyeccion($parametros_array['barcode'], "text");


    if (intval($parametros_array['registrado_por']) == 0) {
        $valido = "N";
        $errores .= " - El campo registrado_por no puede ser cero o nulo.<br />";
    }
    if (trim($parametros_array['registrado_el']) == '') {
        $valido = "N";
        $errores .= " - El campo registrado_el no puede ser cero o nulo.<br />";
    }

    if (intval($parametros_array['idtipoproducto']) == 0) {
        $valido = "N";
        $errores .= " - El campo tipo producto no puede ser cero o nulo.<br />";
    }
    if (trim($parametros_array['descripcion']) == '') {
        $valido = "N";
        $errores .= " - El campo producto no puede estar vacio.<br />";
    }

    if (trim($parametros_array['precio_venta']) == '' or floatval($parametros_array['precio_venta']) < 0) {
        $valido = "N";
        $errores .= " - El campo precio venta no puede estar vacio ni ser negativo.<br />";
    }
    if (trim($parametros_array['costo']) == '' or floatval($parametros_array['costo']) < 0) {
        $valido = "N";
        $errores .= " - El campo costo no puede estar vacio ni ser negativo.<br />";
    }
    if (intval($parametros_array['idcategoria']) == 0) {
        $valido = "N";
        $errores .= " - El campo categoria no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['idmedida']) == 0) {
        $valido = "N";
        $errores .= " - El campo medida no puede estar vacio.<br />";
    }
    /*
    if(intval($parametros_array['idproveedor']) == 0){
        $valido="N";
        $errores.=" - El campo idproveedor no puede ser cero o nulo.<br />";
    }*/

    if (intval($parametros_array['idtipoiva']) == 0) {
        $valido = "N";
        $errores .= " - El campo iva venta no puede estar vacio.<br />";
    }
    if (intval($parametros_array['idtipoiva_compra']) == 0) {
        $valido = "N";
        $errores .= " - El campo iva compra no puede estar vacio.<br />";
    }
    if (intval($parametros_array['idsubcate']) == 0) {
        $valido = "N";
        $errores .= " - El campo sub categoria no puede ser cero o nulo.<br />";
    }
    if (trim($parametros_array['favorito']) == '') {
        $valido = "N";
        $errores .= " - El campo favorito no puede estar vacio.<br />";
    }
    if (trim($parametros_array['web_muestra']) == '') {
        $valido = "N";
        $errores .= " - El campo mostrar en web no puede estar vacio.<br />";
    }
    /*
    idmarca
        if(intval($parametros_array['idmarca']) == 0){
            $valido="N";
            $errores.=" - El campo idmarca no puede ser cero o nulo.<br />";
        }
    */


    if (trim($parametros_array['muestra_self']) == '') {
        $valido = "N";
        $errores .= " - El campo mostrar self service no puede estar vacio.<br />";
    }
    if (trim($parametros_array['muestra_vianda']) == '') {
        $valido = "N";
        $errores .= " - El campo mostrar vianda no puede estar vacio.<br />";
    }
    if (trim($parametros_array['muestra_pedido']) == '') {
        $valido = "N";
        $errores .= " - El campo mostrar en menu digital no puede estar vacio.<br />";
    }


    // validaciones por tipo de producto

    // combinado extendido
    if ($parametros_array['idtipoproducto'] == 4) {


        if (intval($parametros_array['combinado_maxitem']) == 0) {
            $valido = "N";
            $errores .= " - El campo cantidad maxima no puede ser cero o nulo.<br />";
        }

        if (intval($parametros_array['combinado_tipoprecio']) == 0) {
            $valido = "N";
            $errores .= " - El campo tipo de precio no puede ser cero o nulo.<br />";
        }

        if (intval($parametros_array['combinado_minitem']) == 0) {
            $valido = "N";
            $errores .= " - El campo cantidad minima no puede ser cero o nulo.<br />";
        }



    }
    if ($usa_concepto == 'S') {
        if (intval($parametros_array['idconcepto']) == 0) {
            $valido = "N";
            $errores .= " - El campo concepto no puede ser cero o nulo.<br />";
        }
    }
    if (intval($parametros_array['idgrupoinsu']) == 0) {
        $valido = "N";
        $errores .= " - El campo grupo stock no puede estar vacio.<br />";
    }

    if (trim($parametros_array['hab_compra']) == '') {
        $valido = "N";
        $errores .= " - El campo habilita compra debe completarse.<br />";
    }

    if (trim($parametros_array['hab_invent']) == '') {
        $valido = "N";
        $errores .= " - El campo habilita inventario debe completarse.<br />";
    }
    if ($parametros_array['hab_compra'] > 0) {
        if (intval($parametros_array['solo_conversion']) == 0) {
            if (intval($parametros_array['hab_invent']) == 0) {
                $valido = "N";
                $errores .= " - Cuando se habilita compra tambien debe habilitarse inventario.<br />";
            }
        }
    }
    // si envio stock minimo o ideal en matriz
    if ($stock_minimo > 0 or $stock_ideal > 0) {
        if ($stock_ideal < $stock_minimo) {
            $valido = "N";
            $errores .= " - El campo stock ideal no puede ser menor al stock minimo en matriz.<br />";
        }

    }
    // si envio stock minimo o ideal en sucursales
    if ($stock_minimo_suc > 0 or $stock_ideal_suc > 0) {
        if ($stock_ideal_suc < $stock_minimo_suc) {
            $valido = "N";
            $errores .= " - El campo stock ideal no puede ser menor al stock minimo en sucursales.<br />";
        }

    }

    if ($parametros_array['precio_abierto'] == 'S') {
        if (floatval($parametros_array['precio_min']) < 0) {
            $valido = "N";
            $errores .= " - El campo precio min no puede ser negativo.<br />";
        }
        if (floatval($parametros_array['precio_max']) <= 0) {
            $valido = "N";
            $errores .= " - El campo precio max no puede ser cero o negativo.<br />";
        }
        if (floatval($parametros_array['precio_min']) > floatval($parametros_array['precio_max'])) {
            $valido = "N";
            $errores .= " - El campo precio min no puede ser mayor al precio max.<br />";
        }
    }


    // validar que no existe un producto con el mismo nombre
    $consulta = "
	select * from productos where descripcion = $descripcion  and borrado = 'N'
	";
    $rsexpr = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsexpr->fields['idprod_serial'] > 0) {
        $valido = "N";
        $errores .= " - Ya existe otro producto con el mismo nombre.<br />";
    }

    // validar que no exista un insumo con el mismo nombre
    $consulta = "
	select * from insumos_lista where descripcion = $descripcion and estado = 'A'
	";
    $rsexin = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsexin->fields['idinsumo'] > 0) {
        $valido = "N";
        $errores .= " - Ya existe otro articulo($descripcion) con el mismo nombre.<br />";
    }
    // codigo de barras
    if (trim($parametros_array['barcode']) != '') {
        $permite_duplicar_cbar = "N"; // traer de preferencias
        if ($permite_duplicar_cbar == 'N') {
            $consulta = "
			select * from productos where barcode = $barcode and borrado = 'N' limit 1
			";
            $rsbarcode = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            if ($rsbarcode->fields['idprod_serial'] > 0) {
                $errores = $errores."- Ya existe otro producto con el mismo codigo de barras.<br />";
                $valido = 'N';
            }
        }
    }

    // iva venta
    $idtipoiva = intval($parametros_array['idtipoiva']);
    $consulta = "
	select * 
	from tipo_iva
	where 
	idtipoiva = $idtipoiva
	";
    $rsiva = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tipoiva = $rsiva->fields['iva_porc'];
    $idtipoiva = $rsiva->fields['idtipoiva'];
    $iguala_compra_venta = $rsiva->fields['iguala_compra_venta'];

    // iva compra
    $idtipoiva_compra = intval($parametros_array['idtipoiva_compra']);
    $consulta = "
	select * 
	from tipo_iva
	where 
	idtipoiva = $idtipoiva_compra
	";
    $rsiva = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tipoiva_compra = $rsiva->fields['iva_porc'];
    $idtipoiva_compra = $rsiva->fields['idtipoiva'];

    if ($iguala_compra_venta == 'S') {
        if ($idtipoiva <> $idtipoiva_compra) {
            $valido = "N";
            $errores .= "-El iva compra y venta debe ser el mismo para el tipo de iva venta seleccionado.<br />";
        }
    }
    // si esta activa la contabilidad
    if ($contabilidad == 1) {
        /*if(intval($parametros_array['idplancuentadet_venta']) == 0){
            $valido="N";
            $errores.="-Debe indicar la cuenta contable para ventas del producto.<br />";
        }*/
        if (intval($parametros_array['hab_compra']) == 1) {
            if (intval($parametros_array['idplancuentadet_compra']) == 0) {
                $valido = "N";
                $errores .= "-Debe indicar la cuenta contable para compras del producto, cuando el producto esta habilitado para compras.<br />";
            }
        }
    }
    if (intval($parametros_array['idmedida_referencial']) > 0) {
        if (floatval($parametros_array['cantidad_referencial']) <= 0) {
            $valido = "N";
            $errores .= "-Debe indicar la cantidad referencial cuando se carga una medida referencial.<br />";
        }
    }




    $res = [
        'valido' => $valido,
        'errores' => $errores
    ];

    return $res;

}

function agregar_producto($parametros_array)
{

    // recibe parametros
    global $ahora;
    global $conexion;
    global $saltolinea;
    global $rsco; // preferencias

    // productos
    $registrado_por = antisqlinyeccion($parametros_array['registrado_por'], "int");
    $registrado_el = antisqlinyeccion($parametros_array['registrado_el'], "text");
    $idusu = $registrado_por;
    $idtipoproducto = antisqlinyeccion($parametros_array['idtipoproducto'], "int");
    $barcode = antisqlinyeccion($parametros_array['barcode'], "text");
    $codplu = antisqlinyeccion($parametros_array['codplu'], "int");
    $descripcion = antisqlinyeccion($parametros_array['descripcion'], "text");
    $descripcion_larga = antisqlinyeccion($parametros_array['descripcion_larga'], "text");
    $precio_venta = antisqlinyeccion($parametros_array['precio_venta'], "float");
    $idcategoria = antisqlinyeccion($parametros_array['idcategoria'], "int");
    $idmedida = antisqlinyeccion($parametros_array['idmedida'], "int");
    $ubicacion = antisqlinyeccion('', "text");
    $imagen = antisqlinyeccion('', "text");
    $controla_vencimiento = antisqlinyeccion('', "text");
    $controla_garantia = antisqlinyeccion('', "text");
    $keywords = antisqlinyeccion('', "text");
    $idcpr = antisqlinyeccion($parametros_array['idcentroprod'], "int");
    $idproveedor = antisqlinyeccion(intval($parametros_array['idproveedor']), "int");
    $disponible = antisqlinyeccion($parametros_array['disponible'], "float");
    $precio_min = antisqlinyeccion($parametros_array['precio_min'], "float");
    $precio_max = antisqlinyeccion($parametros_array['precio_max'], "float");
    $lista_precios = antisqlinyeccion('', "text");
    $idtipoiva = antisqlinyeccion($parametros_array['idtipoiva'], "int");
    $idtipoiva_compra = antisqlinyeccion($parametros_array['idtipoiva_compra'], "int");
    $sucursal = antisqlinyeccion($parametros_array['sucursal'], "int");
    $idsubcate = antisqlinyeccion($parametros_array['idsubcate'], "int");
    $descuento = antisqlinyeccion(0, "int");
    $actualizado = $registrado_el;
    $facturacompra = antisqlinyeccion('', "text");
    $fechacompra = antisqlinyeccion('', "text");
    $p1 = antisqlinyeccion($parametros_array['p1'], "float");
    $desc1 = antisqlinyeccion('', "float");
    $p2 = 0;
    $p3 = 0;
    $promo = 0;
    $describeprodu = antisqlinyeccion('', "text");
    $mostrar = 0;
    $idgen = 0;
    $favorito = antisqlinyeccion($parametros_array['favorito'], "text");
    $borrado = antisqlinyeccion('N', "text");
    $produccion = 0;
    $web_nuevo = antisqlinyeccion($parametros_array['web_nuevo'], "text");
    $web_carousel = antisqlinyeccion($parametros_array['web_carousel'], "text");
    $web_destacado = antisqlinyeccion($parametros_array['web_destacado'], "text");
    $web_muestra = antisqlinyeccion($parametros_array['web_muestra'], "text");
    $idmarca = antisqlinyeccion($parametros_array['idmarca'], "int");
    $codproveedor = antisqlinyeccion('', "text");
    $combinado_maxitem = antisqlinyeccion($parametros_array['combinado_maxitem'], "int");
    $combinado_tipoprecio = antisqlinyeccion($parametros_array['combinado_tipoprecio'], "int");
    $combinado_minitem = antisqlinyeccion($parametros_array['combinado_minitem'], "int");
    $muestra_self = antisqlinyeccion($parametros_array['muestra_self'], "text");
    $hab_venta = antisqlinyeccion('S', "text");
    $muestra_vianda = antisqlinyeccion($parametros_array['muestra_vianda'], "text");
    $muestra_pedido = antisqlinyeccion($parametros_array['muestra_pedido'], "text");
    $excluye_reporteventa = antisqlinyeccion($parametros_array['excluye_reporteventa'], "int");
    $restaurado_por = antisqlinyeccion('', "int");
    $restaurado_el = antisqlinyeccion('', "text");
    $idempresa = 1;
    $idimpresoratk = antisqlinyeccion(intval($parametros_array['idimpresoratk']), "int");
    $idpantallacocina = 0;
    $idplancuentadet_venta = antisqlinyeccion($parametros_array['idplancuentadet_venta'], "int");
    $idplancuentadet_compra = antisqlinyeccion($parametros_array['idplancuentadet_compra'], "int");
    $idagrupacionprod = antisqlinyeccion($parametros_array['idagrupacionprod'], "int");
    $costo_referencial = antisqlinyeccion(floatval($parametros_array['costo_referencial']), "text");

    // insumos
    $idconcepto = antisqlinyeccion($parametros_array['idconcepto'], "int");
    $idcategoria = antisqlinyeccion($parametros_array['idcategoria'], "int");
    $idsubcate = antisqlinyeccion($parametros_array['idsubcate'], "int");
    $idmedida = antisqlinyeccion($parametros_array['idmedida'], "int");
    $costo = antisqlinyeccion($parametros_array['costo'], "float");
    //$tipoiva=antisqlinyeccion($parametros_array['tipoiva'],"int");
    $mueve_stock = antisqlinyeccion('S', "text");
    $paquete = antisqlinyeccion($parametros_array['paquete'], "text");
    $cant_paquete = antisqlinyeccion($parametros_array['cant_paquete'], "float");
    $estado = antisqlinyeccion('A', "text");
    $idgrupoinsu = antisqlinyeccion($parametros_array['idgrupoinsu'], "int");
    $ajuste = antisqlinyeccion('N', "text");
    $fechahora = $registrado_el;
    $registrado_por_usu = $registrado_por;
    $hab_compra = antisqlinyeccion($parametros_array['hab_compra'], "int");
    $hab_invent = antisqlinyeccion($parametros_array['hab_invent'], "int");
    $acepta_devolucion = antisqlinyeccion($parametros_array['acepta_devolucion'], "text");
    $aplica_regalia = antisqlinyeccion($parametros_array['aplica_regalia'], "text");
    $solo_conversion = antisqlinyeccion($parametros_array['solo_conversion'], "int");
    $respeta_precio_sugerido = antisqlinyeccion($parametros_array['respeta_precio_sugerido'], "text");

    // stock minimo matriz
    $idubicacion = antisqlinyeccion($parametros_array['idubicacion'], "int");
    $idbandeja = antisqlinyeccion($parametros_array['idbandeja'], "int");
    $idorden = antisqlinyeccion($parametros_array['idorden'], "int");
    $idpasillo = antisqlinyeccion($parametros_array['idpasillo'], "int");
    $stock_minimo = antisqlinyeccion($parametros_array['stock_minimo'], "float");
    $stock_ideal = antisqlinyeccion($parametros_array['stock_ideal'], "float");

    // stock minimo sucursales
    $idubicacion_suc = antisqlinyeccion($parametros_array['idubicacion_suc'], "int");
    $idbandeja_suc = antisqlinyeccion($parametros_array['idbandeja_suc'], "int");
    $idorden_suc = antisqlinyeccion($parametros_array['idorden_suc'], "int");
    $idpasillo_suc = antisqlinyeccion($parametros_array['idpasillo_suc'], "int");
    $stock_minimo_suc = antisqlinyeccion($parametros_array['stock_minimo_suc'], "float");
    $stock_ideal_suc = antisqlinyeccion($parametros_array['stock_ideal_suc'], "float");

    $idmedida_referencial = antisqlinyeccion($parametros_array['idmedida_referencial'], "int");
    $cantidad_referencial = antisqlinyeccion($parametros_array['cantidad_referencial'], "float");
    $idprodexterno = antisqlinyeccion($parametros_array['idprodexterno'], "int");
    $recargo_auto_costo = antisqlinyeccion(floatval($parametros_array['recargo_auto_costo']), "float");

    $precio_abierto = antisqlinyeccion(trim($parametros_array['precio_abierto']), "text");



    // conversiones
    $combinado = antisqlinyeccion('N', "text");
    $combo = antisqlinyeccion('N', "text");
    if ($idtipoproducto == 2) {
        $combo = antisqlinyeccion('S', "text");
    }
    if ($idtipoproducto == 3) {
        $combinado = antisqlinyeccion('S', "text");
    }
    if ($idtipoproducto > 1) {
        $hab_compra = "0";
        $hab_invent = "0";
    }
    if (trim($parametros_array['precio_abierto']) == '') {
        $precio_abierto = antisqlinyeccion('N', "text");
    }

    // iva venta
    $consulta = "
	select * 
	from tipo_iva
	where 
	idtipoiva = $idtipoiva
	";
    $rsiva = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tipoiva = $rsiva->fields['iva_porc'];
    $idtipoiva = $rsiva->fields['idtipoiva'];
    $iguala_compra_venta = $rsiva->fields['iguala_compra_venta'];

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

    /*if($iguala_compra_venta == 'S'){
        if($idtipoiva <> $idtipoiva_compra){
            $valido="N";
            $errores.="-El iva compra y venta debe ser el mismo para el tipo de iva venta seleccionado.<br />";
        }
    }*/
    /*echo $valido;
    echo $errores;
    exit;*/

    // idproducto proximo
    $consulta = "SELECT max(idprod_serial) as mayor FROM productos";
    $rsprox = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idproducto = intval($rsprox->fields['mayor']) + 1;

    // registra en productos
    $consulta = "
	insert into productos
	(idprod, idprod_serial, idtipoproducto, barcode, codplu, descripcion, descripcion_larga, precio_venta, costo_actual, idcategoria, idmedida, ubicacion, imagen, controla_vencimiento, controla_garantia, registrado_el, keywords, idproveedor, disponible, precio_min, precio_max, lista_precios, registrado_por, idtipoiva, tipoiva, sucursal, idsubcate, descuento, actualizado, facturacompra, fechacompra, p1, desc1, p2, p3, promo, describeprodu, mostrar, idgen, combinado, combo, favorito, idpantallacocina, idimpresoratk, borrado, produccion, web_nuevo, web_carousel, web_destacado, web_muestra, idmarca, codproveedor, combinado_maxitem, combinado_tipoprecio, combinado_minitem, muestra_self, hab_venta, muestra_vianda, muestra_pedido, excluye_reporteventa, restaurado_por, restaurado_el, idempresa,idcpr,idplancuentadet,idmedida_referencial,cantidad_referencial,idprodexterno, recargo_auto_costo,
	precio_abierto)
	values
	($idproducto, $idproducto, $idtipoproducto, $barcode, $codplu, $descripcion, $descripcion_larga, $precio_venta, $costo, $idcategoria, $idmedida, $ubicacion, $imagen, $controla_vencimiento, $controla_garantia, $registrado_el, $keywords, $idproveedor, $disponible, $precio_min, $precio_max, $lista_precios, $registrado_por, $idtipoiva, $tipoiva, 1, $idsubcate, $descuento, $actualizado, $facturacompra, $fechacompra, $precio_venta, 0, 0, 0, $promo, $describeprodu, $mostrar, $idgen, $combinado, $combo, $favorito, $idpantallacocina, $idimpresoratk, $borrado, $produccion, $web_nuevo, $web_carousel, $web_destacado, $web_muestra, $idmarca, $codproveedor, $combinado_maxitem, $combinado_tipoprecio, $combinado_minitem, $muestra_self, $hab_venta, $muestra_vianda, $muestra_pedido, $excluye_reporteventa, $restaurado_por, $restaurado_el, $idempresa,$idcpr,$idplancuentadet_venta,
	$idmedida_referencial,$cantidad_referencial,$idprodexterno, $recargo_auto_costo,
	$precio_abierto)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // idinsumo proximo
    $consulta = "select max(idinsumo) as mayor from insumos_lista";
    $rsmayor = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idinsumo = intval($rsmayor->fields['mayor']) + 1;

    // registra en insumos
    $consulta = "
	insert into insumos_lista
	(idinsumo,idproducto, descripcion, idconcepto, idcategoria, idsubcate, idmarcaprod, idmedida, produccion, costo, idtipoiva, tipoiva, mueve_stock, paquete, cant_paquete, estado, idempresa, idgrupoinsu, ajuste, fechahora, registrado_por_usu, hab_compra, hab_invent, idproveedor, acepta_devolucion, aplica_regalia, solo_conversion, respeta_precio_sugerido, idprodexterno, restaurado_por, restaurado_el, idcentroprod,idplancuentadet,idagrupacionprod,costo_referencial)
	values
	($idinsumo,$idproducto, $descripcion, $idconcepto, $idcategoria, $idsubcate, $idmarca, $idmedida, $produccion, $costo, $idtipoiva_compra, $tipoiva_compra, $mueve_stock, $paquete, $cant_paquete, $estado, $idempresa, $idgrupoinsu, $ajuste, $fechahora, $registrado_por_usu, $hab_compra, $hab_invent, $idproveedor, $acepta_devolucion, $aplica_regalia, $solo_conversion, $respeta_precio_sugerido, $idprodexterno, $restaurado_por, $restaurado_el, $idcpr,$idplancuentadet_compra,$idagrupacionprod,$costo_referencial)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    //Ingredientes
    $consulta = "Insert into ingredientes (idinsumo,estado,idempresa) values ($idinsumo,1,1)";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    //trae el que acaba de insertar
    $consulta = "Select max(idingrediente) as ingre from ingredientes where idinsumo = $idinsumo";
    $rsg = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idingrediente = intval($rsg->fields['ingre']);


    // producto o agregado
    if ($idtipoproducto == 1 or $idtipoproducto == 6) {

        //Receta
        $consulta = "Insert into recetas
		(nombre,estado,creado_por,fecha_creacion,ultimo_cambio,ultimo_cambio_por,idproducto,idempresa)
		values
		($descripcion,1,$registrado_por,'$ahora','$ahora',$idusu,$idproducto,$idempresa)";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        //trae el que acaba de insertar
        $consulta = "Select max(idreceta) as mayor from recetas ";
        $rsf1 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idreceta = $rsf1->fields['mayor'];

        //Detalle de la receta
        $consulta = "
		Insert into recetas_detalles
		(idreceta,idprod,ingrediente,cantidad,sacar,alias,idempresa)
		values
		($idreceta,'$idproducto',$idingrediente,1,'N',$descripcion,$idempresa)";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }

    //De inmediato registramos la tabla de costos
    $idproveedor = intval($idproveedor);
    $consulta = "insert into costo_productos 
	(idempresa,id_producto,registrado_el,precio_costo,idproveedor,cantidad,numfactura)
	values
	($idempresa,$idproducto,'$ahora',$costo,$idproveedor,0,NULL)";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // si envio impresora
    if ($idimpresoratk > 0) {
        $consulta = "
		INSERT INTO producto_impresora 
		(idproducto, idimpresora, idempresa) 
		VALUES 
		($idproducto, $idimpresoratk, $idempresa);
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "
		select idsucursal
		from impresoratk
		where
		idimpresoratk = $idimpresoratk
		";
        $rssucimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idsucursal_imp = intval($rssucimp->fields['idsucursal']);
    }

    $consulta = "
	select asigna_impre_auto, asigna_pantalla_auto, asigna_serviciocom_auto from preferencias_caja limit 1
	";
    $rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $asigna_impre_auto = $rsprefcaj->fields['asigna_impre_auto'];
    $asigna_pantalla_auto = $rsprefcaj->fields['asigna_pantalla_auto'];
    $asigna_serviciocom_auto = $rsprefcaj->fields['asigna_serviciocom_auto'];



    // si esta activa la asignacion automatica de impresoras
    if ($asigna_impre_auto == 'S') {
        $consulta = "
		select idsucu, nombre, 
		COALESCE((SELECT idimpresoratk FROM impresoratk where borrado = 'N' and idsucursal = sucursales.idsucu and tipo_impresora = 'COC' order by idimpresoratk asc limit 1),0) as idimpresora
		from sucursales
		";
        $rssucasig = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // recorre las sucursales y agrega
        while (!$rssucasig->EOF) {
            $idimpresora_asig = intval($rssucasig->fields['idimpresora']);
            $idsucu_asig = intval($rssucasig->fields['idsucu']);
            // si la sucursal tiene impresora de cocina y no es de la misma sucursal asigno por post arriba
            if ($idimpresora_asig > 0 && $idsucu_asig != intval($idsucursal_imp)) {
                // loguear agregados
                $consulta = "
				insert into producto_impresora_log
				(idproducto, idimpresora, accion, registrado_por, registrado_el)
				VALUES 
				($idproducto,$idimpresora_asig,'A',$registrado_por, '$ahora')
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                // agregar a la primera impresora de todas las sucursales
                $consulta = "
				insert ignore into producto_impresora
				(idproducto, idimpresora, idempresa) 
				VALUES 
				($idproducto,$idimpresora_asig,1)
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }
            $rssucasig->MoveNext();
        }
    }
    // si esta activa la asignacion automatica de pantallas
    if ($asigna_pantalla_auto == 'S') {
        $consulta = "
		select idsucu, nombre, 
		COALESCE((select idpantallacocina from pantalla_cocina where borrado = 'N' and idsucursal = sucursales.idsucu order by idpantallacocina asc limit 1),0) as idpantallacocina
		from sucursales
		";
        $rssucasig = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // recorre las sucursales y agrega
        while (!$rssucasig->EOF) {
            $idpantallacocina_asig = intval($rssucasig->fields['idpantallacocina']);
            if ($idimpresora_asig > 0) {
                // loguear agregados
                $consulta = "
				insert into producto_pantalla_log
				(idproducto, idpantalla, accion, registrado_por, registrado_el)
				VALUES 
				($idproducto,$idpantallacocina_asig,'A',$registrado_por, '$ahora')
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                // agregar a la primera pantalla de todas las sucursales
                $consulta = "
				insert ignore into producto_pantalla
				(idproducto, idpantalla, idempresa) 
				VALUES 
				($idproducto,$idpantallacocina_asig,1)
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }
            $rssucasig->MoveNext();
        }

    }


    // insertamos en precios de sucursales
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

    // lista precios normal
    $consulta = "
	INSERT IGNORE INTO productos_listaprecios
	( idlistaprecio, idproducto, idsucursal, estado, 
	precio, reg_por, reg_el) 
	select 
	lista_precios_venta.idlistaprecio, productos_sucursales.idproducto, idsucursal, 1, 
	(precio*(lista_precios_venta.recargo_porc/100))+precio, $idusu, '$ahora'
	from productos_sucursales, lista_precios_venta
	where
	productos_sucursales.idproducto = $idproducto
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

    // insertamos en productos favoritos
    $consulta = "
	insert IGNORE into favoritos_sucursal (idsucursal, idproducto, favorito, orden)
	select sucursales.idsucu as idsucursal, productos.idprod_serial as idproducto, productos.favorito, 999 as orden
	from productos, sucursales
	where
	productos.idprod_serial = $idproducto
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

    // precios costo proveedores
    if ($idproveedor > 0) {
        $consulta = "
		insert into lista_precios_costo_proveedores
		(idproveedor, idinsumo, idproducto, precio_costo, registrado_por, registrado_el, editado_por, editado_el)
		values
		($idproveedor, $idinsumo, $idproducto, $costo, $registrado_por, $registrado_el, NULL, NULL)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }


    // stock minimo matriz
    if ($stock_minimo > 0 or $stock_ideal > 0) {

        $consulta = "
		insert into stock_minimo
		(iddeposito, idubicacion, idbandeja, idorden, idpasillo, idinsumo, stock_minimo, stock_actual, stock_actual_positivo, stock_ideal, idsucursal, idempresa, ult_actualizacion)
		select iddeposito, $idubicacion, $idbandeja, $idorden, $idpasillo, $idinsumo, $stock_minimo, 0, 0, $stock_ideal, idsucursal, 1, '$ahora'
		from gest_depositos 
		where 
		tiposala = 2
		and idsucursal = (
						select idsucu 		
						from sucursales 
						where 
						estado = 1 
						and matriz = 'S' 
						limit 1
						)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }

    // stock minimo sucursales
    if ($stock_minimo_suc > 0 or $stock_ideal_suc > 0) {

        $consulta = "
		insert into stock_minimo
		(iddeposito, idubicacion, idbandeja, idorden, idpasillo, idinsumo, stock_minimo, stock_actual, stock_actual_positivo, stock_ideal, idsucursal, idempresa, ult_actualizacion)
		select iddeposito, $idubicacion_suc, $idbandeja_suc, $idorden_suc, $idpasillo_suc, $idinsumo, $stock_minimo_suc, 0, 0, $stock_ideal_suc, idsucursal, 1, '$ahora'
		from gest_depositos 
		where 
		tiposala = 2
		and idsucursal in (
						select idsucu 		
						from sucursales 
						where 
						estado = 1 
						and matriz = 'N' 
						order by idsucursal asc
						)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    }
    // asignar a servicio de comida
    $consulta = "
	INSERT IGNORE INTO producto_serviciocom
	(idproducto, idserviciocom, idempresa) 
	select productos.idprod_serial, servicio_comida.idserviciocom, 1
	from productos, servicio_comida
	where
	productos.borrado = 'N'
	and servicio_comida.estado = 'A'
	and productos.idprod_serial = $idproducto
	";
    //$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

    if ($asigna_serviciocom_auto == 'S') {
        $consulta = "
		select * 
		from servicio_comida
		where 
		estado = 'A'
		order by idserviciocom asc
		";
        $rsserv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        while (!$rsserv->EOF) {
            $idserviciocom = $rsserv->fields['idserviciocom'];

            // agrega
            $consulta = "
			INSERT INTO producto_serviciocom
			(idproducto, idserviciocom, idempresa) 
			VALUES 
			($idproducto,$idserviciocom,1)
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // loguear agregado
            $consulta = "
			insert into producto_serviciocom_log
			(idproducto, idserviciocom, accion, registrado_por, registrado_el)
			values
			($idproducto, $idserviciocom, 'A', $idusu, '$ahora')
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $rsserv->MoveNext();
        }
    }

    $res = [
        'idproducto' => $idproducto,
        'idinsumo' => $idinsumo
    ];

    return $res;
}

function modificar_producto($parametros_array)
{

    // recibe parametros
    global $ahora;
    global $conexion;
    global $saltolinea;
    global $rsco; // preferencias
    $idinsumo = 0;
    $idproductoimp = antisqlinyeccion($parametros_array['idproductoimp'], "int");
    $codigo_articulo = antisqlinyeccion($parametros_array['codigo_articulo'], "int");
    $codigo_producto = antisqlinyeccion($parametros_array['codigo_producto'], "int");
    $codigo_barras = antisqlinyeccion($parametros_array['codigo_barras'], "text");
    $codigo_pesable = antisqlinyeccion($parametros_array['codigo_pesable'], "int");
    $codigo_externo = antisqlinyeccion($parametros_array['codigo_externo'], "text");
    $articulo_nombre = antisqlinyeccion($parametros_array['articulo_nombre'], "text");
    $descripcion = antisqlinyeccion($parametros_array['descripcion'], "text");
    $idmedida = antisqlinyeccion($parametros_array['idmedida'], "int");
    $idcategoria = antisqlinyeccion($parametros_array['idcategoria'], "int");
    $idsubcate = antisqlinyeccion($parametros_array['idsubcate'], "int");
    $id_grupo_stock = antisqlinyeccion($parametros_array['id_grupo_stock'], "int"); //solo existe en Insumos_lista
    $idproveedor = antisqlinyeccion($parametros_array['idproveedor'], "int");
    $idconcepto = antisqlinyeccion($parametros_array['idconcepto'], "int");//solo existe en Insumos_lista
    $habilita_compra = antisqlinyeccion($parametros_array['habilita_compra'], "text");//solo existe en Insumos_lista
    $habilita_inventario = antisqlinyeccion($parametros_array['habilita_inventario'], "text");//solo existe en Insumos_lista
    $acepta_devolucion = antisqlinyeccion($parametros_array['acepta_devolucion'], "text");//solo existe en Insumos_lista
    $solo_conversion = antisqlinyeccion($parametros_array['solo_conversion'], "text");
    $iva = antisqlinyeccion($parametros_array['iva'], "int");
    $idcentroprod = antisqlinyeccion($parametros_array['idcentroprod'], "int");//solo existe en Insumos_lista
    $idagrupacionprod = antisqlinyeccion($parametros_array['idagrupacionprod'], "int"); //solo existe en Insumos_lista
    $cuenta_contable_compra_cod_interno = antisqlinyeccion($parametros_array['cuenta_contable_compra_cod_interno'], "int");
    $cuenta_contable_compra_nro = antisqlinyeccion($parametros_array['cuenta_contable_compra_nro'], "int");
    $cuenta_contable_compra_descripcion = antisqlinyeccion($parametros_array['cuenta_contable_compra_descripcion'], "int");
    $rendimiento = antisqlinyeccion($parametros_array['redimiento'], "int");//solo existe en Insumos_lista
    $arti_para_venta = antisqlinyeccion('S', "text");

    $precio_min = antisqlinyeccion($parametros_array['precio_min'], "int");
    $precio_max = antisqlinyeccion($parametros_array['precio_max'], "int");
    $idsucursal = antisqlinyeccion($parametros_array['idsucursal'], "int");
    $precio_sucursal = antisqlinyeccion($parametros_array['precio_sucursal'], "int");
    $activo_sucursal = antisqlinyeccion($parametros_array['activo_sucursal'], "text");
    $modificado_por = antisqlinyeccion($parametros_array['modificado_por'], "int");
    $modificado_el = antisqlinyeccion($parametros_array['modificado_el'], "int");
    $idusu = $modificado_por;
    $cod_impresora = antisqlinyeccion($parametros_array['cod_impresora'], "int");
    $cod_impresora_old = antisqlinyeccion($parametros_array['cod_impresora_old'], "int");
    $nombre_impresora = antisqlinyeccion($parametros_array['nombre_impresora'], "text");


    // conversiones
    if ($parametros_array['habilita_compra'] == 'SI') {
        $habilita_compra = 1;
    } else {
        $habilita_compra = 0;
    }
    if ($parametros_array['habilita_inventario'] == 'SI') {
        $habilita_inventario = 1;
    } else {
        $habilita_inventario = 0;
    }
    if ($parametros_array['activo_sucursal'] == 'SI') {
        $activo_sucursalint = 1;
    } else {
        $activo_sucursalint = 0;
    }
    if ($parametros_array['acepta_devolucion'] == 'SI') {
        $acepta_devolucion = antisqlinyeccion('S', "text");
    } else {
        $acepta_devolucion = antisqlinyeccion('N', "text");
    }
    if (trim($parametros_array['precio_venta']) == 'SI') {
        $precio_abierto = antisqlinyeccion('S', "text");
    } else {
        $precio_abierto = antisqlinyeccion('N', "text");
    }
    if (trim($parametros_array['solo_conversion']) == 'SI') {
        $solo_conversionint = 1;
    } else {
        $solo_conversionint = 0;
    }

    // Consultamos para obtener el idtipoiva
    $consulta = "
	select * 
	from tipo_iva
	where 
	iva_porc = $iva
	order by idtipoiva asc
	limit 1
	";
    $rsiva = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idtipoiva = $rsiva->fields['idtipoiva'];

    //Actualizamos la Tabla Productos
    $consulta = "
	update productos set idtipoiva = $idtipoiva,tipoiva = $iva,
	barcode=$codigo_barras,codplu=$codigo_pesable,
	idprodexterno= $codigo_externo,descripcion =$articulo_nombre,
	descripcion_larga = $descripcion,idmedida= $idmedida,
	idcategoria = $idcategoria,idsubcate = $idsubcate,
	idproveedor = $idproveedor,hab_venta = $arti_para_venta,
	precio_min =$precio_min,precio_max=$precio_max,
	restaurado_el = NOW(),restaurado_por= $modificado_por,
	idproductoimp_edit = $idproductoimp,
	precio_abierto=$precio_abierto where idprod = $codigo_producto ";
    $rsactualiza = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    //Actualizamos la Tabla Insumos_lista
    $consulta = "
	update insumos_lista set idtipoiva = $idtipoiva,tipoiva = $iva,
	idprodexterno= $codigo_externo,descripcion =$articulo_nombre,
	idmedida= $idmedida,hab_compra = $habilita_compra,
	hab_invent= $habilita_inventario,rendimiento_porc = $rendimiento,
	idcategoria = $idcategoria,idsubcate = $idsubcate,
	idproveedor = $idproveedor,idgrupoinsu = $id_grupo_stock,
	idconcepto = $idconcepto,acepta_devolucion =$acepta_devolucion,
	restaurado_el = NOW(),restaurado_por= $modificado_por,
	solo_conversion=$solo_conversionint,idinsumoimp_edit = $idproductoimp,
	idcentroprod = $idcentroprod,idagrupacionprod = $idagrupacionprod
	where idinsumo = $codigo_articulo ";
    $rsactualiza = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // NO SE DEBE ACTUALIZAR ESTA TABLA
    // Actualizamos la Tabla cn_plancuentas_detalles
    /*
    $consulta="
    update cn_plancuentas_detalles set idplan = $cuenta_contable_compra_cod_interno,cuenta = $cuenta_contable_compra_descripcion where idserieun = $cuenta_contable_compra_nro ";
    $rsactualiza=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    */

    // trae el precio original para el log
    $buscar = "Select * from productos_sucursales where idproducto=$codigo_producto and  idsucursal = $idsucursal" ;
    $rslp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $porig = floatval($rslp->fields['precio']);
    $anteac = intval($rslp->fields['activo_suc']);
    $nuevoac = $activo_sucursalint;
    $precio = $precio_sucursal;

    //Actualizamos la Tabla productos_sucursal
    $consulta = "
	update productos_sucursales set precio = $precio_sucursal ,activo_suc = $activo_sucursalint  where idproducto = $codigo_producto and idsucursal = $idsucursal ";
    $rsactualiza = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    //////////////////////////////////////////////////////////////////////////////

    //Actualizamos las impresiones si existen codigos diferentes
    if ($cod_impresora > 0 && $cod_impresora <> $cod_impresora_old) {

        // borrar el producto para esa impresora anteriorn
        $consulta = "
		delete from producto_impresora where idimpresora = $cod_impresora_old and idproducto = $codigo_producto			  
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // loguear borrado para la impresora anterior
        $consulta = "
		insert into producto_impresora_log
		(idproducto, idimpresora, accion, registrado_por, registrado_el)
		values
		($codigo_producto, $cod_impresora_old, 'B', $modificado_por, '$ahora')
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // registra la nueva impresora asignada
        $consulta = "
		INSERT INTO producto_impresora
		(idproducto, idimpresora, idempresa) 
		VALUES 
		($codigo_producto,$cod_impresora,1)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // loguear la nueva impresora asignada
        $consulta = "
		insert into producto_impresora_log
		(idproducto, idimpresora, accion, registrado_por, registrado_el)
		values
		($codigo_producto, $cod_impresora, 'A', $modificado_por, '$ahora')
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }

    $consulta = "
	update productos_listaprecios
	set
	estado = $activo_sucursalint
	where
	idsucursal = $idsucursal
	and idproducto = $codigo_producto
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // loguear activacion y desactivacion
    if ($anteac != $nuevoac) {
        $cambioactivo = 1;
    } else {
        $cambioactivo = 0;
    }
    //logueamos x cambio de precio
    if ($precio != $porig) {

        $insertar = "Insert into cambios_precios (fecha,cambiado_por,valor_orig,nuevo_precio,idsucursal,activo_ant,activo_nuevo,idproducto,cambio_activo) 
		values
		('$ahora',$idusu,$porig,$precio,$idsucursal,$anteac,$nuevoac,$codigo_producto,$cambioactivo)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

    } else {
        //logueamos x cambio de estado
        if ($anteac != $nuevoac) {

            $insertar = "Insert into cambios_precios (fecha,cambiado_por,valor_orig,nuevo_precio,idsucursal,activo_ant,activo_nuevo,idproducto,cambio_activo) 
			values
			('$ahora',$idusu,$porig,$precio,$idsucursal,$anteac,$nuevoac,$codigo_producto,$cambioactivo)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        }
    }

    // desactiva para la venta si es solo conversion
    if ($solo_conversionint == 1) {
        $consulta = "
		update productos_sucursales set activo_suc = 0 where idproducto = $codigo_producto
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }












    $res = [
        'idproducto' => $codigo_producto,
        'idinsumo' => $codigo_articulo
    ];

    return $res;
}


function validar_producto_a_modificar($parametros_array)
{



    global $conexion;



    $descripcion = antisqlinyeccion($parametros_array['descripcion'], "text");
    $codigo_articulo = antisqlinyeccion($parametros_array['codigo_articulo'], "text");
    $codigo_producto = antisqlinyeccion($parametros_array['codigo_producto'], "text");
    $barcode = antisqlinyeccion($parametros_array['barcode'], "text");
    $valido = "S";
    $errores = "";
    if (intval($parametros_array['modificado_por']) == 0) {
        $valido = "N";
        $errores .= " - El campo modificado_por no puede ser cero o nulo.<br />";
    }
    if (trim($parametros_array['modificado_el']) == '') {
        $valido = "N";
        $errores .= " - El campo modificado_el no puede ser cero o nulo.<br />";
    }
    if (trim($parametros_array['habilita_compra']) == 'SI' || trim($parametros_array['habilita_compra']) == 'NO') {

    } else {
        $valido = "N";
        $errores .= " - El campo habilita_compra debe completarse con SI o NO.<br />";
    }

    if (trim($parametros_array['habilita_inventario']) == 'SI' || trim($parametros_array['habilita_inventario']) == 'NO') {

    } else {
        $valido = "N";
        $errores .= " - El campo habilita_inventario debe completarse con SI o NO.<br />";
    }

    if (trim($parametros_array['acepta_devolucion']) == 'SI' || trim($parametros_array['acepta_devolucion']) == 'NO') {

    } else {
        $valido = "N";
        $errores .= " - El campo acepta_devolucion debe completarse con SI o NO.<br />";
    }

    if (trim($parametros_array['solo_conversion']) == 'SI' || trim($parametros_array['solo_conversion']) == 'NO') {

    } else {
        $valido = "N";
        $errores .= " - El campo solo_conversion debe completarse con SI o NO.<br />";
    }

    if (trim($parametros_array['solo_conversion']) == 'SI') {
        if (trim($parametros_array['activo_sucursal']) == 'NO') {
            //$valido="N";
            //$errores.=" - El producto debe estar desactivado cuando se marca como solo conversion.<br />";
        }
    }

    if (trim($parametros_array['arti_para_venta']) == 'SI' || trim($parametros_array['arti_para_venta']) == 'NO') {

    } else {
        $valido = "N";
        $errores .= " - El campo arti_para_venta debe completarse con SI o NO.<br />";
    }

    if (trim($parametros_array['activo_sucursal']) == 'SI' || trim($parametros_array['activo_sucursal']) == 'NO') {

    } else {
        $valido = "N";
        $errores .= " - El campo activo_sucursal debe completarse con SI o NO.<br />";
    }

    if (intval($parametros_array['idsucursal']) == 0) {
        $valido = "N";
        $errores .= " - El campo idsucursal no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['codigo_articulo']) == 0) {
        $valido = "N";
        $errores .= " - El campo codigo_articulo no puede ser cero o nulo.<br />";
    }


    if (trim($parametros_array['precio_venta']) == 'SI' || trim($parametros_array['precio_venta']) == 'NO') {

    } else {
        $valido = "N";
        $errores .= " - El campo precio_venta debe completarse con SI o NO ffffff.<br />";
    }

    if (trim($parametros_array['precio_venta']) == 'SI') {
        if (floatval($parametros_array['precio_min']) < 0) {
            $valido = "N";
            $errores .= " - El campo precio min no puede ser negativo.<br />";
        }
        if (floatval($parametros_array['precio_max']) <= 0) {
            $valido = "N";
            $errores .= " - El campo precio max no puede ser cero o negativo.<br />";
        }
        if (floatval($parametros_array['precio_min']) > floatval($parametros_array['precio_max'])) {
            $valido = "N";
            $errores .= " - El campo precio min no puede ser mayor al precio max.<br />";
        }
    }


    //Verificamos si no existen codigos duplicados en el archivo a procesar
    $consulta = " select count(*) as total from productos 
where trim(descripcion)=$descripcion and idprod <> $codigo_producto and borrado = 'N'";
    $rsverificanombre = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsverificanombre->fields['total']) >= 1) {


        while (!$rsverificanombre->EOF) {
            $dup_txt = "";
            $dup_txt .= antixss($rsverificanombre->fields['descripcion']).' ('.$rsverificanombre->fields['total'].'),';


            $dup_txt = substr($dup_txt, 0, -1);

            $errores .= "- Existen Nombres duplicados en la tabla Productos, cargados en el Archivo: '$dup_txt'.<br />";
            $valido = "N";
            $rsverificanombre->MoveNext();
        }
    }
    $res = [
        'valido' => $valido,
        'errores' => $errores
    ];

    return $res;

}


function validar_insumos_a_modificar($parametros_array)
{

    // recibe parametros
    global $conexion;

    $descripcion = antisqlinyeccion($parametros_array['descripcion'], "text");
    $codigo_articulo = antisqlinyeccion($parametros_array['codigo_articulo'], "text");
    $codigo_producto = antisqlinyeccion($parametros_array['codigo_producto'], "text");
    $valido = "S";
    $errores = "";
    if (intval($parametros_array['modificado_por']) == 0) {
        $valido = "N";
        $errores .= " - El campo modificado_por no puede ser cero o nulo.<br />";
    }
    if (trim($parametros_array['modificado_el']) == '') {
        $valido = "N";
        $errores .= " - El campo modificado_el no puede ser cero o nulo.<br />";
    }
    if (trim($parametros_array['habilita_compra']) == 'SI' || trim($parametros_array['habilita_compra']) == 'NO') {

    } else {
        $valido = "N";
        $errores .= " - El campo habilita_compra debe completarse con SI o NO.<br />";
    }

    if (trim($parametros_array['habilita_inventario']) == 'SI' || trim($parametros_array['habilita_inventario']) == 'NO') {

    } else {
        $valido = "N";
        $errores .= " - El campo habilita_inventario debe completarse con SI o NO.<br />";
    }

    if (trim($parametros_array['acepta_devolucion']) == 'SI' || trim($parametros_array['acepta_devolucion']) == 'NO') {

    } else {
        $valido = "N";
        $errores .= " - El campo acepta_devolucion debe completarse con SI o NO.<br />";
    }

    if (trim($parametros_array['solo_conversion']) == 'SI' || trim($parametros_array['solo_conversion']) == 'NO') {

    } else {
        $valido = "N";
        $errores .= " - El campo solo_conversion debe completarse con SI o NO.<br />";
    }

    if (intval($parametros_array['idsucursal']) == 0) {
        $valido = "N";
        $errores .= " - El campo idsucursal no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['codigo_articulo']) == 0) {
        $valido = "N";
        $errores .= " - El campo codigo_articulo no puede ser cero o nulo.<br />";
    }

    //Verificamos si no existen codigos duplicados en el archivo a procesar
    $consulta = " select count(*) as total from insumos_lista 
where trim(descripcion)=$descripcion and idinsumo <> $codigo_articulo and estado = 'A'";
    $rsverificanombre = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsverificanombre->fields['total']) >= 1) {


        while (!$rsverificanombre->EOF) {
            $dup_txt = "";
            $dup_txt .= antixss($rsverificanombre->fields['descripcion']).' ('.$rsverificanombre->fields['total'].'),';


            $dup_txt = substr($dup_txt, 0, -1);

            $errores .= "- Existen Nombres duplicados en la tabla Insumos_lista, cargados en el Archivo: '$dup_txt'.<br />";
            $valido = "N";
            $rsverificanombre->MoveNext();
        }
    }

    //Verificamos si no existen codigos duplicados en el archivo a procesar
    $consulta = " select count(*) as total from productos 
where trim(descripcion)=$descripcion and idprod <> $codigo_producto and borrado = 'N'";
    $rsverificanombre = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsverificanombre->fields['total']) >= 1) {


        while (!$rsverificanombre->EOF) {
            $dup_txt = "";
            $dup_txt .= antixss($rsverificanombre->fields['descripcion']).' ('.$rsverificanombre->fields['total'].'),';


            $dup_txt = substr($dup_txt, 0, -1);

            $errores .= "- Existen Nombres duplicados en la tabla Productos, cargados en el Archivo: '$dup_txt'.<br />";
            $valido = "N";
            $rsverificanombre->MoveNext();
        }
    }


    $res = [
        'valido' => $valido,
        'errores' => $errores
    ];

    return $res;

}


function modificar_insumos($parametros_array)
{

    // recibe parametros
    global $ahora;
    global $conexion;
    $idinsumoimp = antisqlinyeccion($parametros_array['idinsumoimp'], "int");
    $codigo_articulo = antisqlinyeccion($parametros_array['codigo_articulo'], "int");
    $codigo_producto = antisqlinyeccion($parametros_array['codigo_producto'], "int");
    $codigo_barras = antisqlinyeccion($parametros_array['codigo_barras'], "text");
    $codigo_pesable = antisqlinyeccion($parametros_array['codigo_pesable'], "int");
    $codigo_externo = antisqlinyeccion($parametros_array['codigo_externo'], "text");
    $articulo_nombre = antisqlinyeccion($parametros_array['articulo_nombre'], "text");
    $descripcion = antisqlinyeccion($parametros_array['descripcion'], "text");
    $idmedida = antisqlinyeccion($parametros_array['idmedida'], "int");
    $idcategoria = antisqlinyeccion($parametros_array['idcategoria'], "int");
    $idsubcate = antisqlinyeccion($parametros_array['idsubcate'], "int");
    $id_grupo_stock = antisqlinyeccion($parametros_array['id_grupo_stock'], "int"); //solo existe en Insumos_lista
    $idproveedor = antisqlinyeccion($parametros_array['idproveedor'], "int");
    $idconcepto = antisqlinyeccion($parametros_array['idconcepto'], "int");//solo existe en Insumos_lista
    $habilita_compra = antisqlinyeccion($parametros_array['habilita_compra'], "text");//solo existe en Insumos_lista
    $habilita_inventario = antisqlinyeccion($parametros_array['habilita_inventario'], "text");//solo existe en Insumos_lista
    $acepta_devolucion = antisqlinyeccion($parametros_array['acepta_devolucion'], "text");//solo existe en Insumos_lista
    $solo_conversion = antisqlinyeccion($parametros_array['solo_conversion'], "text");
    $iva = antisqlinyeccion($parametros_array['iva'], "int");
    $idcentroprod = antisqlinyeccion($parametros_array['idcentroprod'], "int");//solo existe en Insumos_lista
    $idagrupacionprod = antisqlinyeccion($parametros_array['idagrupacionprod'], "int"); //solo existe en Insumos_lista
    $cuenta_contable_compra_cod_interno = antisqlinyeccion($parametros_array['cuenta_contable_compra_cod_interno'], "int");
    $cuenta_contable_compra_nro = antisqlinyeccion($parametros_array['cuenta_contable_compra_nro'], "int");
    $cuenta_contable_compra_descripcion = antisqlinyeccion($parametros_array['cuenta_contable_compra_descripcion'], "int");
    $rendimiento = antisqlinyeccion($parametros_array['redimiento'], "int");//solo existe en Insumos_lista
    $modificado_por = antisqlinyeccion($parametros_array['modificado_por'], "int");
    $cod_impresora = antisqlinyeccion($parametros_array['cod_impresora'], "int");
    $cod_impresora_old = antisqlinyeccion($parametros_array['cod_impresora_old'], "int");
    $contabilidad = antisqlinyeccion($parametros_array['contabilidad'], "int");

    // conversiones
    if ($parametros_array['habilita_compra'] == 'SI') {
        $habilita_compra = 1;
    } else {
        $habilita_compra = 0;
    }
    if ($parametros_array['habilita_inventario'] == 'SI') {
        $habilita_inventario = 1;
    } else {
        $habilita_inventario = 0;
    }

    if ($parametros_array['acepta_devolucion'] == 'SI') {
        $acepta_devolucion = antisqlinyeccion('S', "text");
    } else {
        $acepta_devolucion = antisqlinyeccion('N', "text");
    }

    if (trim($parametros_array['solo_conversion']) == 'SI') {
        $solo_conversionint = 1;
    } else {
        $solo_conversionint = 0;
    }

    // Consultamos para obtener el idtipoiva
    $consulta = "
	select * 
	from tipo_iva
	where 
	iva_porc = $iva
	order by idtipoiva asc
	limit 1
	";
    $rsiva = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idtipoiva = $rsiva->fields['idtipoiva'];

    //Actualizamos la Tabla Productos
    $consulta = "
	update productos set idtipoiva = $idtipoiva,tipoiva = $iva,
	barcode=$codigo_barras,codplu=$codigo_pesable,
	idprodexterno= $codigo_externo,descripcion =$articulo_nombre,
	descripcion_larga = $descripcion,idmedida= $idmedida,
	idcategoria = $idcategoria,idsubcate = $idsubcate,
	idproveedor = $idproveedor,
	restaurado_el = NOW(),restaurado_por= $modificado_por
	 where idprod = $codigo_producto ";
    $rsactualiza = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    //Actualizamos la Tabla Insumos_lista
    $consulta = "
	update insumos_lista set idtipoiva = $idtipoiva,tipoiva = $iva,
	idprodexterno= $codigo_externo,descripcion =$articulo_nombre,
	idmedida= $idmedida,hab_compra = $habilita_compra,
	hab_invent= $habilita_inventario,rendimiento_porc = $rendimiento,
	idcategoria = $idcategoria,idsubcate = $idsubcate,
	idproveedor = $idproveedor,idgrupoinsu = $id_grupo_stock,
	idconcepto = $idconcepto,acepta_devolucion =$acepta_devolucion,
	restaurado_el = NOW(),restaurado_por= $modificado_por,
	solo_conversion=$solo_conversionint,idinsumoimp_edit = $idinsumoimp,
	idcentroprod = $idcentroprod,idagrupacionprod = $idagrupacionprod
	where idinsumo = $codigo_articulo ";
    $rsactualiza = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    if ($contabilidad == 1) {
        $idcuentacontable = intval($cuenta_contable_compra_descripcion);
        //echo $idcuentacontable;exit;
        if ($idcuentacontable > 0) {
            $buscar = "Select idsercuenta,trim(cuenta)as cuentacont from cn_articulos_vinculados 
			inner join cn_plancuentas_detalles on cn_plancuentas_detalles.idserieun=cn_articulos_vinculados.idsercuenta
			where cn_articulos_vinculados.idinsumo=$codigo_articulo ";
            $rscon1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $actual = trim($rscon1->fields['cuentacont']);
            //echo $actual;exit;
            if ($actual != $idcuentacontable) {

                $buscar = "Select * from cn_plancuentas_detalles where cuenta=$idcuentacontable and estado <> 6";
                $rsvv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $idplan = intval($rsvv->fields['idplan']);
                $idsercuenta = intval($rsvv->fields['idserieun']);


                //actualizamos
                if ($actual != '') {
                    $update = "update cn_articulos_vinculados set idsercuenta=$idsercuenta,idplancuenta=$idplan where idinsumo=$codigo_articulo ";
                    $conexion->Execute($update) or die(errorpg($conexion, $update));
                } else {
                    //insertar
                    $insertar = "Insert into cn_articulos_vinculados
					(idinsumo,idplancuenta,idsercuenta,vinculado_el,vinculado_por) 
					values 
					($codigo_articulo,$idplan,$idsercuenta,current_timestamp,$modificado_por)";
                    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
                }

            }


        }

    }
    // ESTA TABLA NO SE DEBE ACTUALIZAR
    //Actualizamos la Tabla cn_plancuentas_detalles
    /*$consulta="
    update cn_plancuentas_detalles set idplan = $cuenta_contable_compra_cod_interno,cuenta = $cuenta_contable_compra_descripcion where idserieun = $cuenta_contable_compra_nro ";
    $rsactualiza=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    */

    //////////////////////////////////////////////////////////////////////////////

    // LAS IMPRESORAS NO TIENEN RELACION CON LOS INSUMOS
    //Actualizamos las impresiones si existen codigos diferentes
    /*if($cod_impresora>0 && $cod_impresora <> $cod_impresora_old){
        // borrar el producto para esa impresora anteriorn
        $consulta="
        delete from producto_impresora where idimpresora = $cod_impresora_old and idproducto = $codigo_articulo
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

        // loguear borrado para la impresora anterior
        $consulta="
        insert into producto_impresora_log
        (idproducto, idimpresora, accion, registrado_por, registrado_el)
        values
        ($codigo_producto, $cod_impresora_old, 'B', $modificado_por, '$ahora')";
        $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

        // registra la nueva impresora asignada
        $consulta="
        INSERT INTO producto_impresora
        (idproducto, idimpresora, idempresa)
        VALUES
        ($codigo_producto,$cod_impresora,1)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

        // loguear la nueva impresora asignada
        $consulta="
        insert into producto_impresora_log
        (idproducto, idimpresora, accion, registrado_por, registrado_el)
        values
        ($codigo_producto, $cod_impresora, 'A', $modificado_por, '$ahora')
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    }
    */

    if ($solo_conversionint == 1) {
        $consulta = "
		update productos_sucursales set activo_suc = 0 where idproducto = $codigo_producto
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }


    $res = [
        'idproducto' => $codigo_producto,
        'idinsumo' => $codigo_articulo
    ];

    return $res;
}
