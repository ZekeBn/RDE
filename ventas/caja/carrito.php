<?php

require_once("../../includes/conexion.php");
require_once("../../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
$dirsup_sec = "S";
require_once("../../includes/rsusuario.php");
//se agrega la posibilidad de usar plantilla para agregar automaticamnete al carrito

//print_r($_POST);exit;


function redondear_precio($precio, $ceros = 1, $direccion = 'N')
{
    // direccion  A: hacia arriba // B: hacia abajo N: Normal
    if ($direccion == 'A') {
        $precio_redondeado = ceil($precio / 10 ** $ceros) * (10 ** $ceros);
    } elseif ($direccion == 'B') {
        $precio_redondeado = floor($precio / 10 ** $ceros) * (10 ** $ceros);
    } else {
        $precio_redondeado = round($precio / 10 ** $ceros) * (10 ** $ceros);
    }
    return $precio_redondeado;
}
function codigo_pesable($codigo_plu)
{
    global $conexion;
    $consulta = "
	select 
	cod_plu_desde, cod_plu_cantdigit, 
	cant_plu_entero_desde, cant_plu_entero_cantdigit, 
	cant_plu_entero_unit_desde, cant_plu_entero_unit_cantdigit,
	cant_plu_decimal_desde, cant_plu_decimal_cantdigit
	from preferencias_caja
	limit 1
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $tamano_codigo = strlen($codigo_plu);
    /*
    ejemplo
    $cod_plu_desde=3;
    $cod_plu_cantdigit=4;
    $cant_plu_entero_desde=7;
    $cant_plu_entero_cantdigit=3;
    $cant_plu_decimal_desde=10;
    $cant_plu_decimal_cantdigit=3;
    */
    $cod_plu_desde = intval($rs->fields['cod_plu_desde']);
    $cod_plu_cantdigit = intval($rs->fields['cod_plu_cantdigit']);
    $cant_plu_entero_desde = intval($rs->fields['cant_plu_entero_desde']);
    $cant_plu_entero_cantdigit = intval($rs->fields['cant_plu_entero_cantdigit']);
    $cant_plu_entero_unit_desde = intval($rs->fields['cant_plu_entero_unit_desde']);
    $cant_plu_entero_unit_cantdigit = intval($rs->fields['cant_plu_entero_unit_cantdigit']);
    $cant_plu_decimal_desde = intval($rs->fields['cant_plu_decimal_desde']);
    $cant_plu_decimal_cantdigit = intval($rs->fields['cant_plu_decimal_cantdigit']);


    // fijo
    $codigo_pesable = substr($codigo_plu, 0, 2);
    $codigo_plu_producto = substr($codigo_plu, $cod_plu_desde - 1, $cod_plu_cantdigit);
    $codigo_plu_producto_orig = $codigo_plu_producto;
    $descarte = substr($codigo_plu, -1);

    // pesable
    if ($codigo_pesable == 20) {
        $cant_producto_entero = substr($codigo_plu, $cant_plu_entero_desde - 1, $cant_plu_entero_cantdigit);
        $cant_producto_decimal = substr($codigo_plu, $cant_plu_decimal_desde - 1, $cant_plu_decimal_cantdigit);
        $cant_producto = $cant_producto_entero.'.'.$cant_producto_decimal;
    }
    // unitario
    if ($codigo_pesable == 21) {
        $cant_producto_entero = substr($codigo_plu, $cant_plu_entero_unit_desde - 1, $cant_plu_entero_unit_cantdigit);
        //$cant_producto_decimal=substr($codigo_plu,$cant_plu_decimal_desde-1,$cant_plu_decimal_cantdigit);
        $cant_producto_decimal = 0;
        //echo $cant_producto_entero;exit;
        $cant_producto = $cant_producto_entero.'.'.$cant_producto_decimal;
    }

    // conversiones si es unitario

    $codigo_plu_producto = antisqlinyeccion(intval($codigo_plu_producto), "text");
    //echo $codigo_plu_producto;exit;
    $consulta = "
	select idprod_serial from productos where codplu = $codigo_plu_producto and borrado = 'N' limit 1
	";
    $rsplu = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $res = [
        'codigo_pesable' => $codigo_pesable,
        'codigo_plu_producto' => $codigo_plu_producto_orig,
        'cant_producto' => $cant_producto,
        'cant_entero' => $cant_producto_entero,
        'cant_decimal' => $cant_producto_decimal,
        'descarte' => $descarte,
        'idproducto' => $rsplu->fields['idprod_serial'],
        'cantidad' => $cant_producto,
    ];

    return  $res;
}

//Debe venir parametrizado, la cantidad de digitos que van a definir la cantidad tomada de la etiqueta para el peso
$tfinaldigpeso = 5;
//tomamos x defecto del post y solo cambia si es pesable
$producto = trim($_REQUEST['prod']);
$cantidad = antisqlinyeccion($_POST['cant'], "float");
$lote = ($_POST['lote']);
$vencimiento = ($_POST['vencimiento']);
if (strlen($_POST['cant'].'') >= 13) {
    $cantidad = 1;
}

// si no se recibio el parametro de omitir sobreventa
if ($_POST['omite_sobreventa'] != 'S') {
    $idprod_dispara = intval($producto);
    // sobreventa
    $consulta = "
	SELECT sobreventa.idsobreventa 
	FROM sobreventa
	inner join sobreventa_locales on sobreventa_locales.idsobreventa = sobreventa.idsobreventa
	WHERE
	sobreventa.idproducto_disparador = $idprod_dispara
	and sobreventa_locales.idsucursal = $idsucursal
	order by sobreventa.idsobreventa asc
	limit 1
	";
    $rssob = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idsobreventa = intval($rssob->fields['idsobreventa']);
    // si encuentra devuelve mensaje para preguntar
    if ($idsobreventa > 0) {
        // genera array con los datos
        $arr = [
            'idsobreventa' => $idsobreventa,
            'valido' => 'S',
            'errores' => ''
        ];

        //print_r($arr);

        // convierte a formato json
        $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

        // devuelve la respuesta formateada
        echo $respuesta;
        exit;
    }


} // if($_POST['omite_sobreventa'] != 'S'){


// si es una edicion
$idpedidocat = antisqlinyeccion($_POST['idpedidocat'], "int");
$idtmpventares_cab = antisqlinyeccion($_POST['idtmpventares_cab'], "int");

$partir = intval($_REQUEST['partir']);
$descarte = substr(trim($_REQUEST['prod']), -1, 1);

$omite_activosuc = substr(trim($_REQUEST['omite_activosuc']), 0, 1);
$activo_suc_add = "";
if ($omite_activosuc != 'S') {
    $activo_suc_add = " and productos_sucursales.activo_suc = 1 ";
}

//AGREGADO: Teclas de acceso inmediato a producto, para uso con ventas tipo registradora
$teclaespecial = intval($_POST['tecla']);
$idlistaprecio = intval($_SESSION['idlistaprecio']);
$idcanalventa = intval($_SESSION['idcanalventa']);
$idclienteprevio = intval($_SESSION['idclienteprevio']);
if ($idclienteprevio > 0) {
    $consulta = "
	select idcliente, idvendedor, idcanalventacli
	from cliente 
	where 
	idcliente = $idclienteprevio
	and estado <> 6 
	limit 1
	";
    $rscprev = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idclienteprevio = $rscprev->fields['idcliente'];
    if ($idclienteprevio > 0) {
        $idcliente = $idclienteprevio;
        $idvendedor = intval($rscprev->fields['idvendedor']);
        $idcanalventa = intval($rscprev->fields['idcanalventacli']);
    }
}
if ($idcanalventa > 0) {
    $consulta = "
	select idlistaprecio, idcanalventa, canal_venta 
	from canal_venta 
	where 
	idcanalventa = $idcanalventa 
	and estado = 1
	limit 1
	";
    $rscv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idlistaprecio = intval($rscv->fields['idlistaprecio']);
}

$seladd_lp = " productos_sucursales.precio ";
if ($idlistaprecio > 0) {
    $joinadd_lp = " inner join productos_listaprecios on productos_listaprecios.idproducto = productos.idprod_serial ";
    $whereadd_lp = "
	and productos_listaprecios.idsucursal = $idsucursal 
	and productos_listaprecios.idlistaprecio = $idlistaprecio 
	and productos_listaprecios.estado = 1
	";
    $seladd_lp = " productos_listaprecios.precio ";
}



if ($teclaespecial > 0) {
    //buscamos el codigo de producto de esa tecla
    $buscar = "Select idprodserial from productos_codigos_rapidos
	where orden=$teclaespecial ";
    $rstmppo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $producto = $rstmppo->fields['idprodserial'];

}

/*
CODIGO DE BARRA: 2000097003157
20 - pesable o unitario, unitario: 21 pesable: 20
00097 - codigo de producto
00315 - peso (2,3) 2 Enteros con 3 Decimales
7 - descarte (final de cadena esta al pedo pero tiene que estar si o si)
*/

//Traemos las preferencias para la empresa
$buscar = "Select * from preferencias where idempresa=$idempresa ";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$carry_out = trim($rspref->fields['carry_out']);
$alerta_ventas = trim($rspref->fields['alerta_ventas']);
$forzar_agrupacion = trim($rspref->fields['forzar_agrupacion']);
$agrupa_ventadet = trim($rspref->fields['agrupa_ventadet']);
$max_items_factura = intval($rspref->fields['max_items_factura']);

$consulta = "
select bloquea_carrito_maxitems, agrupar_productos_carrito
from preferencias_caja
limit 1
";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$bloquea_carrito_maxitems = trim($rsprefcaj->fields['bloquea_carrito_maxitems']);
$agrupar_productos_carrito = trim($rsprefcaj->fields['agrupar_productos_carrito']);
if ($partir == 2) {

    //proviene del uso de cod de barras  etiquetas desde el panel central y traemos la preferencia de cod plu pesable
    $buscar = "Select codplu_pesable,codplu_unitario,total_numeros_cod from preferencias where idempresa=$idempresa";
    $rsbb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $codigo_pesable = intval($rsbb->fields['codplu_pesable']);
    $codplu_unitario = intval($rsbb->fields['codplu_unitario']);
    //echo $codigo_pesable;exit;
    //total de digitos que componen la cadena
    $bb = strlen($codigo_pesable);
    //Si esta definido el codigo pesable
    if ($codigo_pesable > 0) {

        //sse definio y vemos el tamanho de los numeros que componen el codigo en la etiqueta
        /*$cantcodigo=intval($rsbb->fields['total_numeros_cod']);
        //segun éste numero, cortamos la cadena
        if ($cantcodigo==0){
            //No se definio, y usamos x defecto 5 mas los dos del cod pesable
            $cantcodigo=5+$bb;
        } else {
            $cantcodigo=$cantcodigo+$bb;
        }
        //obtenido el total gral de digitos del codigo, inclyendo el PLU, extraemos
        $cadena=$producto;
        //echo $cadena;exit;
        //extraemos el inicial para ver si es pesable
        $cadenapesa=substr($cadena,0,$bb);
        //echo $descarte;*/

        //extraemos el inicial para ver si es pesable
        $cadenapesa = substr($producto, 0, 2);
        //echo $cadenapesa;exit;
        if (($cadenapesa == $codigo_pesable or $cadenapesa == $codplu_unitario) && $descarte >= 0) {
            //echo $producto;
            $resultado = codigo_pesable($producto);
            //print_r($resultado);exit;
            $cantipeso = $resultado['cantidad'];
            $codigoprod = $resultado['idproducto'];

            //es pesable y extraemos el id del producto y la cantidad
            /*$codigoprod=substr($cadena,$bb,($cantcodigo-$bb));
            //echo $codigoprod;exit;
            $cc=strlen($codigoprod);
            //echo $cc;exit;
            //Inicio para cantidad
            $cc=$cc+$bb;
            $cantipeso=substr($cadena,$cc,$tfinaldigpeso);
            //echo $cantipeso;exit;
            if (intval($cantipeso<1000)){
                $cantipeso=floatval('00.'.substr($cantipeso,2,4));
                //$cantipeso=floatval($cantipeso);
            }else{
                $numeradorp=substr($cantipeso,0,2); // numerador
                $decimalesp=substr($cantipeso,2,3); // numerador
                $cantipeso=$numeradorp.'.'.$decimalesp; // peso completo
                $cantipeso=floatval($cantipeso);
            }*/
            //echo $cantipeso;exit;

            //No esta como pesable de acuerdo a la cadena definida
        } else {
            //	echo 'NO PESABLE';exit;
            // busca como codigo de barra
            $consulta = "
			select productos.idprod_serial,productos.idmedida, productos.idtipoproducto, $seladd_lp as precio
			from productos 
			inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
			$joinadd_lp
			where
			productos.idprod_serial is not null
			and productos.idempresa = $idempresa
			and productos.barcode = '$producto'
			and productos.borrado = 'N'
			
			and productos_sucursales.idsucursal = $idsucursal 
			and productos_sucursales.idempresa = $idempresa
			$activo_suc_add
			
			$whereadd_lp
			
			order by productos.descripcion asc
			";
            $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            //echo $consulta;exit;
            // existe codigo de barra
            if (intval($rs->fields['idprod_serial']) > 0) {
                $codigoprod = $rs->fields['idprod_serial'];
                $cantipeso = floatval($_POST['cant']);
                //echo $cantipeso;exit;

            } else {
                //No esta como pesable de acuerdo a la cadena definida ni existe codigo de barra
                $codigoprod = intval($_POST['prod']);
                $cantipeso = floatval($_POST['cant']);
            }
        }

    } else {
        //echo 'No hay codigo pesable';exit;
    }
    //Terminado todo,como entro x partir asignamos
    $producto = intval($codigoprod);
    $cantidad = antisqlinyeccion(floatval($cantipeso), "float");
    if (strlen($cantidad.'') >= 13) {
        $cantidad = 1;
    }
    //echo $cantipeso;exit;
}

//exit;
//$producto=antisqlinyeccion($_POST['prod'],"int");
//$cantidad=antisqlinyeccion($_POST['cant'],"float");
$precio = antisqlinyeccion($_POST['precio'], "float");
$fechahora = antisqlinyeccion(date("Y-m-d H:i:s"), "text");
$usuario = $idusu;
$idsucursal = $idsucursal;
$idempresa = $idempresa;
$receta_cambiada = antisqlinyeccion("N", "text");
$registrado = antisqlinyeccion("N", "text");
$borrado = antisqlinyeccion("N", "text");
$combinado = antisqlinyeccion("N", "text");
;
$prod_1 = "NULL";
$prod_2 = "NULL";
$subtotal = 0;
// buscar producto si es combinado

if ($_POST['prod_1'] > 0 && $_POST['prod_2'] > 0) {
    $prod_1 = antisqlinyeccion($_POST['prod_1'], "int");
    $prod_2 = antisqlinyeccion($_POST['prod_2'], "int");

    $consulta = "
	select (sum($seladd_lp)/2) as precio, productos.idmedida, productos.idtipoproducto
	from productos 
	inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
	$joinadd_lp
	where
	idprod_serial is not null
	and (productos.idprod_serial = $prod_1 or productos.idprod_serial = $prod_2)
	and productos.borrado = 'N'
	
	and productos_sucursales.idsucursal = $idsucursal 
	$activo_suc_add
	
	$whereadd_lp
	
	order by productos.descripcion asc
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $precio = antisqlinyeccion(redondear_tresceros($rs->fields['precio']), "int");
    $subtotal = $precio;
    $combinado = antisqlinyeccion("S", "text");
    //$idtipoproducto=3;
    $idtipoproducto = 1;
} else {
    $idplantilla = intval($_POST['idplantilla']);
    if ($idplantilla == 0) {
        $consulta = "
		select productos.idmedida, productos.idtipoproducto, $seladd_lp as precio
		from productos 
		inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
		$joinadd_lp
		where
		productos.idprod_serial is not null
		and productos.idempresa = $idempresa
		and productos.idprod_serial = $producto
		and productos.borrado = 'N'
		
		and productos_sucursales.idsucursal = $idsucursal 
		and productos_sucursales.idempresa = $idempresa
		$activo_suc_add
		
		$whereadd_lp
		
		order by productos.descripcion asc
		";
        //echo $consulta;exit;

        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $precio = antisqlinyeccion($rs->fields['precio'], "float");
        $medida = intval($rs->fields['idmedida']);
        $idtipoproducto = $rs->fields['idtipoproducto'];
        if ($idtipoproducto == 3) {
            $idtipoproducto = 1;
        }
        if ($medida == 2) {
            $desconsolida_forzar = "'S'";
        } else {
            $desconsolida_forzar = 'NULL';
        }
        //echo $idtipoproducto;exit;
        //KILLO y porcion
        if ($medida != 4) {
            //Precio en kls a gramos
            $preciogr = ($precio / 1000);
            $subtotal = (($preciogr * 1000) * $cantidad);
        } else {
            if ($cantidad == 0) {
                $cantidad = 1;
            }
            $subtotal = ($precio * $cantidad);
        }
    }
}
$subtotal = round($subtotal);
if (!function_exists("isNatural")) {
    function isNatural($var)
    {
        return preg_match('/^[0-9]+$/', (string )$var) ? true : false;
    }
}

if ($producto > 0) {



    if ($forzar_agrupacion != 'S' && $agrupa_ventadet != 'S') {
        $consulta = "select count(*) as totalitems 
		from (

		select productos.descripcion, sum(cantidad) as total, sum(precio) as totalprecio, sum(subtotal) as subtotal,
		(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
		(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado
		from tmp_ventares 
		inner join productos on tmp_ventares.idproducto = productos.idprod_serial
		where 
		registrado = 'N'
		and tmp_ventares.usuario = $idusu
		and tmp_ventares.borrado = 'N'
		and tmp_ventares.finalizado = 'N'
		and tmp_ventares.idsucursal = $idsucursal
		and tmp_ventares.idempresa = $idempresa
		group by descripcion, receta_cambiada

		) as carrito 
		limit 1";
        $rstotalitems = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $totalitems = intval($rstotalitems->fields['totalitems']);

        // busca si el producto a agrega no esta ya agregado al carrito
        $consulta = "
		select productos.descripcion, sum(cantidad) as total, sum(precio) as totalprecio, sum(subtotal) as subtotal,
		(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
		(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado
		from tmp_ventares 
		inner join productos on tmp_ventares.idproducto = productos.idprod_serial
		where 
		registrado = 'N'
		and tmp_ventares.usuario = $idusu
		and tmp_ventares.borrado = 'N'
		and tmp_ventares.finalizado = 'N'
		and tmp_ventares.idsucursal = $idsucursal
		and tmp_ventares.idempresa = $idempresa
		and idproducto = $producto
		group by descripcion, receta_cambiada
		";
        $rsprodcargado = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if (trim($rsprodcargado->fields['descripcion']) == '') {
            $totalitems_new = $totalitems + 1;
        } else {
            $totalitems_new = $totalitems;
        }
        if ($bloquea_carrito_maxitems == 'S') {
            if ($totalitems_new > $max_items_factura) {
                //echo "La cantidad de items supera la cantidad maxima que entra en la factura.";
                $arr = [
                'valido' => 'N',
                'errores' => "La cantidad de items supera la cantidad maxima ($max_items_factura) que entra en la factura."
                ];
                // convierte a formato json
                $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

                // devuelve la respuesta formateada
                echo $respuesta;
                exit;

            }
        }
    }

    if ($codigo_pesable > 0) {
        // redondeo
        $consulta = "
		select redondear_subtotal, redondeo_ceros, redondear_direccion
		from preferencias_caja
		limit 1
		";
        $rsred = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if ($rsred->fields['redondear_subtotal'] == 'S') {
            $subtotal_redondo = redondear_precio($subtotal, $rsred->fields['redondeo_ceros'], $rsred->fields['redondear_direccion']);
            $precio = $subtotal_redondo / $cantidad;
            $subtotal = $subtotal_redondo;
        }
    }

    if ($idlistaprecio > 0) {
        $lista_precio_my = $idlistaprecio;
    } else {
        $lista_precio_my = "NULL";
    }

    //echo $medida;
    //  forzar agrupacion

    if ($agrupar_productos_carrito == 'S') {

        // busca si la unidad de medida del producto es unitaria
        if ($medida == 4) {
            // si es unitaria valida que no tenga decimales
            if (!isNatural($cantidad)) {
                //echo "Error! No puede fraccionar un producto unitario.";
                //exit;
                $arr = [
                'valido' => 'N',
                'errores' => "No puede fraccionar un producto unitario. dd".$medida
                ];
                // convierte a formato json
                $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
                // devuelve la respuesta formateada
                echo $respuesta;
                exit;
            }
            if ($cantidad < 1) {
                //echo "Error! un producto unitario debe tener una cantidad compuesta por un numero natural mayor a 0.";
                //exit;
                $arr = [
                'valido' => 'N',
                'errores' => "Error! un producto unitario debe tener una cantidad compuesta por un numero natural mayor a 0."
                ];
                // convierte a formato json
                $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
                // devuelve la respuesta formateada
                echo $respuesta;
                exit;
            }
        }


        $consulta = "
		select idventatmp
		from tmp_ventares 
		where 
		registrado = 'N'
		and usuario = $usuario
		and idproducto = $producto
		and borrado = 'N'
		and finalizado = 'N'
		and idsucursal = $idsucursal
		limit 1
		";
        $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idventatmp = intval($rsex->fields['idventatmp']);

        // si existe actualiza (se usa por idventatmp en vez de idproducto por seguridad si hay 2 registros del mismo producto)
        if (intval($rsex->fields['idventatmp']) > 0) {
            $consulta = "
			update tmp_ventares
			set 
			cantidad = cantidad+$cantidad
			where 
			idventatmp = $idventatmp
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $consulta = "
			update tmp_ventares
			set
				subtotal=((cantidad*precio)-descuento)
			where
				idventatmp = $idventatmp
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // si no existe inserta
        } else {

            if ($cantidad > 0) {
                // inserta 1 vez
                $consulta = "
				INSERT INTO tmp_ventares
				(idproducto, idtipoproducto, cantidad, precio, fechahora, usuario, registrado, idsucursal, idempresa, receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,subtotal,idlistaprecio,idpedidocat,idtmpventares_cab, desconsolida_forzar) 
				VALUES 
				($producto, $idtipoproducto,$cantidad,$precio, $fechahora,$usuario, $registrado, $idsucursal, $idempresa, $receta_cambiada, $borrado, $combinado, $prod_1, $prod_2,$subtotal,$lista_precio_my,$idpedidocat,$idtmpventares_cab, $desconsolida_forzar)
				;
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }

        }
    }
    if ($agrupar_productos_carrito != 'S') {

        // busca si la unidad de medida del producto es unitaria
        if ($medida == 4) {

            // si es unitaria valida que no tenga decimales
            if (!isNatural($cantidad)) {
                //echo "Error! No puede fraccionar un producto unitario.";
                //exit;
                $arr = [
                'valido' => 'N',
                'errores' => "Error! No puede fraccionar un producto unitario."
                ];
                // convierte a formato json
                $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
                // devuelve la respuesta formateada
                echo $respuesta;
                exit;

            }
            if ($cantidad < 1) {
                //echo "Error! un producto unitario debe tener una cantidad compuesta por un numero natural mayor a 0.";
                //exit;
                $arr = [
                'valido' => 'N',
                'errores' => "Error! un producto unitario debe tener una cantidad compuesta por un numero natural mayor a 0."
                ];
                // convierte a formato json
                $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
                // devuelve la respuesta formateada
                echo $respuesta;
                exit;
            }
            // para evitar while gigante que colapse el sistema,
            //si permitimos esto se debe registar en cantidad con 1 solo registro y no se podra variar la receta
            /*if($cantidad > 1000){
                echo "Error! no se puede vender una cantidad tan grande.";
                exit;
            }*/
            //echo 'a'.$idtipoproducto;
            // si son menos de 10 genera 1 registro por cada uno

            if ($cantidad < 10) {
                // si es unitaria y la cantidad es mayor a 1 genera 1 registro por cada uno
                for ($i = 1;$i <= $cantidad;$i++) {
                    //echo $i;

                    $consulta = "
					INSERT INTO tmp_ventares
					(idproducto, idtipoproducto, cantidad, precio, fechahora, usuario, registrado, idsucursal, idempresa, receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,subtotal,idlistaprecio,idpedidocat,idtmpventares_cab, desconsolida_forzar,lote,vencimiento) 
					VALUES 
					($producto, $idtipoproducto,1,$precio, $fechahora,$usuario, $registrado, $idsucursal, $idempresa, $receta_cambiada, $borrado, $combinado, $prod_1, $prod_2,$precio,$lista_precio_my,$idpedidocat,$idtmpventares_cab, $desconsolida_forzar,'$lote','$vencimiento')
					;
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    //echo $consulta;
                }
            } else {
                if ($cantidad > 0) {
                    // inserta 1 vez
                    $consulta = "
					INSERT INTO tmp_ventares
					(idproducto, idtipoproducto, cantidad, precio, fechahora, usuario, registrado, idsucursal, idempresa, receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,subtotal,idlistaprecio,idpedidocat,idtmpventares_cab,desconsolida_forzar,lote,vencimiento) 
					VALUES 
					($producto, $idtipoproducto,$cantidad,$precio, $fechahora,$usuario, $registrado, $idsucursal, $idempresa, $receta_cambiada, $borrado, $combinado, $prod_1, $prod_2,$subtotal,$lista_precio_my,$idpedidocat,$idtmpventares_cab,$desconsolida_forzar,'$lote','$vencimiento')
					;
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                }
            }

        } else { // if ($medida==4){
            if ($cantidad > 0) {
                // inserta 1 vez
                $consulta = "
				INSERT INTO tmp_ventares
				(idproducto, idtipoproducto, cantidad, precio, fechahora, usuario, registrado, idsucursal, idempresa, receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,subtotal,idlistaprecio,idpedidocat,idtmpventares_cab,desconsolida_forzar) 
				VALUES 
				($producto, $idtipoproducto,$cantidad,$precio, $fechahora,$usuario, $registrado, $idsucursal, $idempresa, $receta_cambiada, $borrado, $combinado, $prod_1, $prod_2,$subtotal,$lista_precio_my,$idpedidocat,$idtmpventares_cab,$desconsolida_forzar)
				;
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }
        } // if ($medida==4){
    }



    // planilla solo para catering
} else { // if ($producto > 0){

    //si no hay idproducto, agarramos y vemos si vino de plantilla de productos


    $idplantilla = intval($_POST['idplantilla']);
    if ($idplantilla > 0) {
        $idsucursal = intval($_SESSION['idsucursal']);

        $buscar = "
			Select 
			idmedida,idtipoproducto,
			(select precio from productos_sucursales where productos_sucursales.idproducto=plantilla_articulos_det.idproducto and idsucursal=$idsucursal $activo_suc_add) as precio,
			plantilla_articulos_det.idproducto,cantidad,descripcion 
			from plantilla_articulos_det
			inner join productos on productos.idprod_serial=plantilla_articulos_det.idproducto
			where 
			idplantillaart=$idplantilla 
			and productos.borrado = 'N' 
			order by descripcion asc";
        //echo $buscar;exit;

        $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        //echo $buscar;exit;




        while (!$rsb->EOF) {
            //creamos el reg en tmpventares
            $producto = intval($rsb->fields['idproducto']);
            $cantidad = floatval($rsb->fields['cantidad']);
            $precio = floatval($rsb->fields['precio']);
            $idtipoproducto = floatval($rsb->fields['idtipoproducto']);
            $fechahora = date("Y-m-d H:i:s");
            $borrado = antisqlinyeccion($_POST['borrado'], 'text');
            $combinado = antisqlinyeccion($_POST['combinado'], 'text');
            $prod_1 = antisqlinyeccion($_POST['idprod1'], 'text');
            $prod_2 = antisqlinyeccion($_POST['idprod2'], 'text');
            $idmedida = intval($rsb->fields['idmedida']);
            if ($medida != 4) {
                //Precio en kls a gramos
                $preciogr = ($precio / 1000);
                $subtotal = (($preciogr * 1000) * $cantidad);
            } else {
                if ($cantidad == 0) {
                    $cantidad = 1;
                }
                $subtotal = ($precio * $cantidad);
            }

            $receta_cambiada = 'N';
            $consulta = "
				select idventatmp
				from tmp_ventares 
				where 
				registrado = 'N'
				and usuario = $idusu
				and idproducto = $producto
				and borrado = 'N'
				and finalizado = 'N'
				and idsucursal = $idsucursal
				limit 1
				";
            $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idventatmp = intval($rsex->fields['idventatmp']);
            // si existe actualiza (se usa por idventatmp en vez de idproducto por seguridad si hay 2 registros del mismo producto)
            if (intval($rsex->fields['idventatmp']) > 0) {
                $consulta = "
					update tmp_ventares
					set 
					cantidad = cantidad+$cantidad
					where 
					idventatmp = $idventatmp
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                $consulta = "
					update tmp_ventares
					set
						subtotal=((cantidad*precio)-descuento)
					where
						idventatmp = $idventatmp
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                // si no existe inserta
            } else {

                if ($cantidad > 0) {
                    // inserta 1 vez
                    $consulta = "
						INSERT INTO tmp_ventares
						(idproducto, idtipoproducto, cantidad, precio, fechahora, usuario, registrado, idsucursal, idempresa, receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,subtotal,idlistaprecio,idpedidocat,idtmpventares_cab) 
						VALUES 
						($producto, $idtipoproducto,$cantidad,$precio, '$fechahora',$idusu, 'N', $idsucursal, $idempresa, '$receta_cambiada', 'N', 'N', $prod_1, $prod_2,$subtotal,1,$idpedidocat,$idtmpventares_cab)
						;
						";


                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                }

            }

            $rsb->MoveNext();
        }

    }





}



// buscar cantidad total de ese producto y responder
$consulta = "
select 
sum(cantidad) as total
from tmp_ventares 
where 
registrado = 'N'
and usuario = $usuario
and idproducto = $producto
and borrado = 'N'
and finalizado = 'N'
and idsucursal = $idsucursal
and idempresa = $idempresa
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

echo floatval($rs->fields['total']);
