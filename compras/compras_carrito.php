<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");
require_once("../includes/funciones_iva.php");
require_once("../includes/funciones_compras.php");
require_once("../insumos/preferencias_insumos_listas.php");
require_once("./preferencias_compras.php");

// llamando constantes necesarias para el funcionamienot del formulario
//buscando origenes importacion y locales
$consulta = "SELECT idtipo_origen FROM tipo_origen WHERE UPPER(tipo)='LOCAL'";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_tipo_origen_local = intval($rs_guarani->fields["idtipo_origen"]);
if ($id_tipo_origen_local == 0) {
    $errores = "- Por favor cree el Origen LOCAL.<br />";
}

$consulta = "SELECT id_medida, nombre FROM medidas where estado = 1 AND 
medidas.nombre LIKE \"%unidades\" order by nombre asc";
$rs_cajas = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idunidades = $rs_cajas->fields['id_medida'];

$consulta = "SELECT id_medida, nombre FROM medidas where estado = 1 AND 
medidas.nombre LIKE \"%cajas\" order by nombre asc";
$rs_cajas = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcaja = $rs_cajas->fields['id_medida'];

$consulta = "SELECT id_medida, nombre FROM medidas where estado = 1 
AND medidas.nombre LIKE \"%pall%\" order by nombre asc ";
$rs_pallets = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idpallet = $rs_pallets->fields['id_medida'];

//fin de llamados en un futuro agregar un script de variables globales
$productos_alerta = 0;


//buscando moneda nacional
$consulta = "SELECT tipo_moneda.idtipo, tipo_moneda.descripcion as nombre FROM tipo_moneda WHERE nacional='S'";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_nacional = $rs_guarani->fields["idtipo"];
$nombre_moneda_nacional = $rs_guarani->fields["nombre"];



//buscando moneda guarani
$consulta = "SELECT tipo_moneda.idtipo, tipo_moneda.descripcion as nombre FROM tipo_moneda WHERE UPPER(tipo_moneda.descripcion) like \"%GUARANI%\" ";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_guarani = $rs_guarani->fields["idtipo"];
$nombre_moneda_guarani = $rs_guarani->fields["nombre"];


// idconcepto del despacho
$consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%DESPACHO\" ";
$rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto_despacho = intval($rs_conceptos->fields['idconcepto']);

$consulta = "SELECT idconcepto, descripcion FROM cn_conceptos where cn_conceptos.descripcion LIKE \"%FLETE\" ";
$rs_conceptos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto_flete = intval($rs_conceptos->fields['idconcepto']);






//Recibir valores por POST
$agregar = antisqlinyeccion(intval($_POST['agregar']), "int");
$idtransaccion = antisqlinyeccion(intval($idtransaccion), "int");
$eliminar = antisqlinyeccion(intval($_POST['idunico']), "int");
$editar = antisqlinyeccion(intval($_POST['editar']), "int");
$ajustar = antisqlinyeccion(intval($_POST['ajustar']), "int");
$update_cabecera = antisqlinyeccion(intval($_POST['update_cabecera']), "int");
$vencimientos_compra = antisqlinyeccion(intval($_POST['vencimientos_compra']), "int");
$vencimientos_compra_editar = antisqlinyeccion(intval($_POST['vencimientos_compra_editar']), "int");
$vencimientos_compra_borrar = antisqlinyeccion(intval($_POST['vencimientos_compra_borrar']), "int");
$descuento = antisqlinyeccion(intval($_POST['descuento']), "int");
//Preferencias
$buscar = "select * from preferencias where idempresa=$idempresa";
$rstc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$usa_cta_int = trim($rstc->fields['usa_cta_interna']);
//Preferencias compras
$buscar = "Select * from preferencias_compras limit 1";
$rsprefecompras = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$usar_descuentos_compras = trim($rsprefecompras->fields['usar_descuentos_compras']);
//Validaciones
if ($idtransaccion == 0) {
    $idtransaccion = intval($_POST['idtransaccion']);
}
if ($idtransaccion == 0) {
    echo "Error al obtener la transaccion";
    exit;

}
//Borrar temporal cargado
if ($eliminar > 0) {
    $idtransaccion = antisqlinyeccion(intval($_POST['idtransaccion']), "int");
    $consulta = "Select pchar,costo from tmpcompradeta where idregcc=$eliminar";
    $articulo = $conexion->Execute($consulta) or die(errorpg($conexion, $delete));
    if ($articulo->fields['pchar'] == "DESCUENTO") {
        $costo = $articulo->fields['costo'];
        $consulta = "UPDATE tmpcompras SET monto_factura = monto_factura - $costo, descuento=0 WHERE idtran = $idtransaccion";
        $conexion->Execute($consulta) or die(errorpg($conexion, $delete));
    }
    //Articulo - insumo seleccionado
    $delete = "delete from tmpcompradeta where idregcc=$eliminar";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));
    //Ahora de impuestos
    $delete = "Delete from tmpcompradetaimp where idtrandet=$eliminar";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));
}

if ($agregar > 0) {
    //print_r($_POST);exit;

    //Array ( [idtransaccion] => 106 [insumo] => 180 [cantidad] => 20 [precio_compra] => 6000 [agregar] => 1 )

    $vencimiento = ($_POST['vencimiento']);
    $deposito = antisqlinyeccion(intval($_POST['iddeposito']), "int");
    $iva = antisqlinyeccion(intval($_POST['iva']), "float");
    $usa_iva = antisqlinyeccion(intval($_POST['usa_iva']), "text");

    if ($deposito == 0) {
        $deposito = verificar_deposito_insumo([
            'idinsumo' => antisqlinyeccion(intval($_POST['insumo']), "int")
        ])['iddeposito'];
    }

    $parametros_array = [
        'idinsumo' => antisqlinyeccion(intval($_POST['insumo']), "int"),
        'idmedida' => antisqlinyeccion(intval($_POST['idmedida']), "int"),
        'tipo_medida' => antisqlinyeccion(intval($_POST['tipo_medida']), "int"),
        'cantidad' => antisqlinyeccion(floatval($_POST['cantidad']), "float"),
        'cantidad_ref' => antisqlinyeccion(floatval($_POST['cantidad_ref']), "float"),
        'costo_unitario' => antisqlinyeccion(floatval($_POST['precio_compra']), "float"),
        'idtransaccion' => $idtransaccion,
        'lote' => ($_POST['lote']),
        'iddeposito' => $deposito,
        'iva' => $iva,
        'usa_iva' => $usa_iva,
        'iva_variable' => $iva_variable,
        'vencimiento' => $vencimiento
    ];
    // var_dump($parametros_array);exit;


    //print_r($parametros_array);exit;
    $res = validar_carrito_compra($parametros_array);

    if ($res['valido'] == 'N') {
        $valido = $res['valido'];
        $errores .= nl2br($res['errores']);
    } else {
        $res = agregar_carrito_compra($parametros_array);
        $idregcc = $res['idregcc'];
    }

}

if ($editar == 1) {
    $idregcc = antisqlinyeccion(intval($_POST['idregcc']), "int");
    $idinsumo = antisqlinyeccion(intval($_POST['idinsumo']), "int");
    $cantidad = antisqlinyeccion(intval($_POST['cantidad']), "int");
    $costo_unitario = antisqlinyeccion(floatval($_POST['costo_unitario']), "float");
    $idtransaccion = antisqlinyeccion(intval($_POST['idtransaccion']), "int");
    $vencimiento = antisqlinyeccion(trim($_POST['vencimiento']), "date");
    $lote = antisqlinyeccion(trim($_POST['lote']), "text");
    $iddeposito = antisqlinyeccion(intval($_POST['iddeposito']), "int");
    $cantidad_ref = antisqlinyeccion($_POST['cantidad_ref'], "float");
    $idmedida = antisqlinyeccion($_POST['idmedida'], "int");
    $iva_variable = antisqlinyeccion(floatval($_POST['iva_variable']), "float");

    $precio_total = null;
    $precio_total = $cantidad_ref * $costo_unitario;
    $costo_unitario = $precio_total / $cantidad;

    $parametros_array = [
        "idregcc" => $idregcc,//idunico
        "idinsumo" => $idinsumo,
        "cantidad" => $cantidad,
        "costo_unitario" => $costo_unitario,
        "idtransaccion" => $idtransaccion,
        "vencimiento" => $vencimiento,
        "lote" => $lote,
        "iddeposito" => $iddeposito,
        "idmedida" => $idmedida,
        "iva_variable" => $iva_variable
    ];
    $reditar = validar_carrito_compra($parametros_array);
    // echo json_encode($parametros_array);
    if ($reditar['valido'] == 'N') {
        $errores .= nl2br($reditar['errores']);
    }

    // si todo es correcto actualiza
    if ($reditar['valido'] == "S") {
        editar_carrito_compra($parametros_array);
    }
}

if ($ajustar == 1) {
    $valido = "S";
    $select = "select iddeposito from gest_depositos where descripcion like \"NO APLICA\" ";
    $rs_activo = $conexion->Execute($select) or die(errorpg($conexion, $select));
    $deposito_no_aplica = intval($rs_activo->fields['iddeposito']);
    if ($deposito_no_aplica == 0) {
        $valido = "N";
        $errores .= "Favor crear el deposito no aplica en Gestion->Adm Depositos.<br>";
    }


    $idt = antisqlinyeccion(intval($_POST['idtransaccion']), "int");
    $idempresa = antisqlinyeccion(intval($_POST['idempresa']), "int");
    $idusu = antisqlinyeccion(intval($_POST["idusu"]), "int");
    $diferencia = antisqlinyeccion(floatval($_POST["diferencia"]), "float");
    $monto_factura = antisqlinyeccion(floatval($_POST["monto_factura"]), "float");

    $buscar = "
		Select * 
		from tmpcompradeta
		where 
		idemp=$idempresa 
		and idt=$idt
		";
    $rajustar = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $idregcc = intval($rajustar->fields['idregcc']);
    $cantidad = $rajustar->fields['cantidad'];
    // if ($diferencia < 0){
    // 	//cambiar tmcompras el total de la factura
    // 	// $t = $monto_factura - $diferencia; // - diferencia se resta porque el valor es negativo
    // 	// $update="update tmpcompras set monto_factura=$t where idtran=$idt and idempresa = $idempresa";
    // 	// $conexion->Execute($update) or die(errorpg($conexion,$update));

    // 	//cambar el ajuste para que reste

    // }else{
    //encuentra el insumo ajuste

    $consulta = "Select insumos_lista.idinsumo,insumos_lista.descripcion,
		(select nombre from medidas where id_medida=insumos_lista.idmedida and medidas.estado=1) as medida 
		from insumos_lista  
		where UPPER(insumos_lista.descripcion) = 'AJUSTE'";
    $insumo_ajuste = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $vencimiento = "";
    $id_ajuste = $insumo_ajuste->fields["idinsumo"];
    if ($id_ajuste <= 0) {
        $valido = "N";
        $errores .= nl2br("- Necesia crear un insumo AJUSTE.<br>");
    }
    $parametros_array = [
        'idinsumo' => $id_ajuste,
        'cantidad' => 1,
        'costo_unitario' => $diferencia,
        'idtransaccion' => $idtransaccion,
        'vencimiento' => $vencimiento,
        'iddeposito' => $deposito_no_aplica
    ];
    //print_r($parametros_array);exit;
    $res = validar_carrito_compra($parametros_array);
    if ($res['valido'] == 'N' || $valido == "N") {
        $valido = $res['valido'];
        $errores .= nl2br($res['errores']);
    } else {
        $res = agregar_carrito_compra($parametros_array);
        $idregcc = $res['idregcc'];
    }

    // }// fin para que cambie el tmpcompras al ajuste negativo pero esta desactivado


}
if ($descuento == 1) {
    $idt = antisqlinyeccion(intval($_POST['idtransaccion']), "int");
    $idempresa = antisqlinyeccion(intval($_POST['idempresa']), "int");
    $idusu = antisqlinyeccion(intval($_POST["idusu"]), "int");
    $porcentaje = antisqlinyeccion(intval($_POST["porcentaje"]), "float");
    $descuento_valor = antisqlinyeccion(floatval($_POST["descuento_valor"]), "float");
    $monto_factura = antisqlinyeccion(floatval($_POST["monto_factura"]), "float");
    $vencimiento = "";
    $valido = "S";
    $valor_costo = 0;


    $valor_costo = - $descuento_valor;
    //encuentra el insfloat ajuste
    $consulta = "Select insumos_lista.idinsumo,insumos_lista.descripcion,
	(select nombre from medidas where id_medida=insumos_lista.idmedida and medidas.estado=1) as medida 
	from insumos_lista  
	where UPPER(insumos_lista.descripcion) = 'DESCUENTO'
	and insumos_lista.estado='A'";
    $insumo_descuento = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id_descuento = $insumo_descuento->fields["idinsumo"];
    if ($id_descuento <= 0) {
        $valido = "N";
        $errores .= nl2br("- Necesia crear un insumo DESCUENTO.<br>");
    }
    $parametros_array = [
        'idinsumo' => $id_descuento,
        'cantidad' => 1,
        'costo_unitario' => $valor_costo,
        'idtransaccion' => $idtransaccion,
        'vencimiento' => $vencimiento
    ];
    //print_r($parametros_array);exit;
    $res = validar_carrito_compra($parametros_array);
    if ($res['valido'] == 'N' || $valido == "N") {
        $valido = $res['valido'];
        $errores .= nl2br($res['errores']);
    } else {
        $res = agregar_carrito_compra($parametros_array);
        $valor_costo = $monto_factura + $valor_costo; // porque valor ya es negativo
        $update = "
		update tmpcompras 
		set 
		monto_factura=$valor_costo,
		descuento=$descuento_valor 
		where idtran=$idt 
		and idempresa = $idempresa";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        $idregcc = $res['idregcc'];
    }



}

if ($update_cabecera == 1) {

    function limpiacdc($cdc)
    {
        $cdc = trim($cdc);
        $cdc = str_replace(' ', '', $cdc);
        $cdc = htmlentities($cdc);
        $cdc = solonumeros($cdc);
        return $cdc;
    }

    // recibe parametros
    $idproveedor = antisqlinyeccion($_POST['idproveedor'], "int");
    $fecha_compra = antisqlinyeccion($_POST['fecha_compra'], "text");
    // validar formato de factura
    $factura_part = explode("-", trim($_POST['facturacompra']));
    $factura_prov_suc = trim($factura_part[0]);
    $factura_prov_pex = trim($factura_part[1]);
    $factura_prov_nro = trim($factura_part[2]);
    $facturacompleta = trim($factura_prov_suc.$factura_prov_pex.$factura_prov_nro);
    $facturacompra_incrementa = intval($factura_prov_nro);
    $facturacompra_guion = antisqlinyeccion(trim($_POST['facturacompra']), "text");
    $facturacompra = antisqlinyeccion($facturacompleta, "text");
    $monto_factura = antisqlinyeccion($_POST['monto_factura'], "float");
    $idtipocomprobante = antisqlinyeccion(trim($_POST['idtipocomprobante']), "int");
    $cdc = antisqlinyeccion(limpiacdc($_POST['cdc']), "text");
    $timbrado = antisqlinyeccion($_POST['timbrado'], "int");
    $ocnum = antisqlinyeccion($_POST['ocnum'], "text");
    $descripcion = antisqlinyeccion($_POST['descripcion'], "text");
    $vencimiento = antisqlinyeccion($_POST['vencimiento'], "date");
    $sucursal = antisqlinyeccion($_POST['sucursal'], "float");
    $idtipocompra = antisqlinyeccion($_POST['idtipocompra'], "int");
    $vto_timbrado = antisqlinyeccion($_POST['vto_timbrado'], "text");


    $idusu = intval($_POST["idusu"]);
    ////////////////////////////////
    $fechahora = antisqlinyeccion($ahora, "text");
    $estado = antisqlinyeccion(1, "float");
    $totalcompra = antisqlinyeccion(0, "float");

    $moneda = antisqlinyeccion($_POST['idmoneda'], "int");
    $idcot = antisqlinyeccion($_POST['idcot'], "int");
    $idtipo_origen = antisqlinyeccion($_POST['idtipo_origen'], "int");

    $cambio = antisqlinyeccion('0', "float");
    $cambioreal = antisqlinyeccion('0', "float");
    $cambiohacienda = antisqlinyeccion('0', "float");
    $cambioproveedor = antisqlinyeccion('0', "float");
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $idtran = intval($_POST['idtransaccion']);

    if ($preferencias_importacion == "N") {
        $idtipo_origen = $id_tipo_origen_local;
    }


    $consulta = "
	SELECT * 
	FROM tmpcompras
	where
	idtran  = $idtran
	and estado = 1
	";
    $rstran = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // buscar en la base el timbrado
    $consulta = "
	Select * 
	from compras 
	where 
	idproveedor=$idproveedor
	and estado=1 
	order by fechacompra desc 
	limit 1
	";
    //echo $consulta;exit;
    $rstimb = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $timbrado_bd = $rstimb->fields['timbrado'];
    $vto_timbrado_bd = $rstimb->fields['vto_timbrado'];

    // validaciones basicas
    $valido = "S";
    $errores = "";


    $parametros_array = [
        "sucursal" => $sucursal,
        "fechahora" => $fechahora,
        "estado" => $estado,
        "idempresa" => $rstran->fields["idempresa"],
        "tipocompra" => $idtipocompra,
        "fecha_compra" => $fecha_compra,
        "totalcompra" => $totalcompra,
        "monto_factura" => $monto_factura,
        "idproveedor" => $idproveedor,
        "moneda" => $moneda,
        "cambio" => $cambio,
        "cambioreal" => $cambioreal,
        "cambiohacienda" => $cambiohacienda,
        "cambioproveedor" => $cambioproveedor,
        "vencimiento" => $vencimiento,
        "timbrado" => $timbrado,
        "vto_timbrado" => $vto_timbrado,
        "ocnum" => $ocnum,
        "registrado_por" => $registrado_por,
        "registrado_el" => $ahora,
        "facturacompra_guion" => $facturacompra_guion,
        "idtipocomprobante" => $idtipocomprobante,
        "cdc" => $cdc,
        "factura_part" => $factura_part,
        "factura_prov_suc" => $factura_prov_suc,
        "factura_prov_pex" => $factura_prov_pex,
        "factura_prov_nro" => $factura_prov_nro,
        "facturacompleta" => $facturacompleta,
        "facturacompra_incrementa" => $facturacompra_incrementa,
        "facturacompra" => $facturacompra,
        "obliga_cdc" => $obliga_cdc,
        "tipos_comprobantes_vence_ar" => $tipos_comprobantes_vence_ar,
        "idtran" => $idtran,
        "idusu" => $idusu,
        "idcot" => $idcot,
        "idmoneda" => $moneda,
        "idtipo_origen" => $idtipo_origen,
        "descripcion" => $descripcion,
        "edit" => 1
    ];
    // echo $parametros_array;exit;


    // echo json_encode($patrametros_array);exit;
    $respuesta = validar_cabecera_compra($parametros_array);
    if ($respuesta['valido'] == 'N') {
        $errores .= nl2br(($respuesta['errores']));

    }
    // si todo es correcto inserta
    if ($respuesta["valido"] == "S") {
        editar_cabecera_compra($parametros_array);//responde idtran
        if ($idtipocompra == 2) {
            $consulta = "
				select * from tmpcompravenc where idtran=$idtran
				";
            $rstieneven = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $totalvenc = intval($rstieneven->RecordCount());

            if (intval($rstieneven->fields['idtran']) == 0) {
                $consulta = "
					INSERT INTO tmpcompravenc
					 ( idtran, vencimiento, monto_cuota)
					 VALUES
					 ($idtran,$vencimiento,$monto_factura);
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            }
        }
        if ($totalvenc == 1 && intval($idtran) > 0) {

            $consulta = "
			update tmpcompravenc
			set
			vencimiento = $vencimiento,
			monto_cuota = $monto_factura
			where
			idtran=$idtran
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }
        if ($idtipocompra == 1) {
            $consulta = "
				select * from tmpcompravenc where idtran=$idtran
				";
            $rstieneven = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $totalvenc = intval($rstieneven->RecordCount());
            if ($totalvenc > 0) {

                $consulta = "
				delete from tmpcompravenc
				where
				idtran=$idtran
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }

        }
    }
}
if ($vencimientos_compra == 1) {
    // recibe parametros
    $idtran = antisqlinyeccion($_POST['idtransaccion'], "int");
    $vencimiento = antisqlinyeccion($_POST['vencimiento'], "text");
    $monto_cuota = antisqlinyeccion($_POST['monto_cuota'], "float");


    // validaciones basicas
    $valido = "S";
    $errores = "";



    if (trim($_POST['vencimiento']) == '') {
        $valido = "N";
        $errores .= " - El campo vencimiento no puede estar vacio.<br />";
    }
    if (floatval($_POST['monto_cuota']) <= 0) {
        $valido = "N";
        $errores .= " - El campo monto_cuota no puede ser cero o negativo.<br />";
    }


    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		insert into tmpcompravenc
		(idtran, vencimiento, monto_cuota)
		values
		($idtran, $vencimiento, $monto_cuota)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }
}
if ($vencimientos_compra_editar == 1) {
    $vencimiento = antisqlinyeccion($_POST['vencimiento'], "date");
    $monto_cuota = antisqlinyeccion($_POST['monto_cuota'], "float");
    $idvencimiento = antisqlinyeccion($_POST['idvencimiento'], "int");

    // validaciones basicas
    $valido = "S";
    $errores = "";


    if (trim($vencimiento) == '') {
        $valido = "N";
        $errores .= " - El campo vencimiento no puede estar vacio.<br />";
    }
    if (floatval($monto_cuota <= 0)) {
        $valido = "N";
        $errores .= " - El campo monto_cuota no puede ser cero o negativo.<br />";
    }


    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update tmpcompravenc
		set
			vencimiento=$vencimiento,
			monto_cuota=$monto_cuota
		where
			idvencimiento=$idvencimiento
		";

        $respuesta = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }
}
if ($vencimientos_compra_borrar == 1) {

    $idvencimiento = antisqlinyeccion($_POST['idvencimiento'], "int");

    $consulta = "
	delete from  tmpcompravenc
	where
		idvencimiento=$idvencimiento
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}
//Listar contenido del carrito de las compras

$buscar = "
	Select * ,
	( 
		Select nombre from medidas where medidas.id_medida = tmpcompradeta.idmedida ) as medida, (
		select productos.barcode
		from productos 
		inner join insumos_lista on insumos_lista.idproducto = productos.idprod_serial
		where
		tmpcompradeta.idprod = insumos_lista.idinsumo
		) as barcode,
		(
			select insumos_lista.maneja_lote from insumos_lista where tmpcompradeta.idprod = insumos_lista.idinsumo 
		) as maneja_lote,
		(
		select costo 
		from insumos_lista 
		where 
		idinsumo = tmpcompradeta.idprod
		) as ultcosto,
		(
			select cant_medida2 
			from insumos_lista 
			where 
			idinsumo = tmpcompradeta.idprod
		) as cant_medida2,
		(
			select cant_medida3 
			from insumos_lista 
			where 
			idinsumo = tmpcompradeta.idprod
		) as cant_medida3,
	(select iva_describe from tipo_iva where idtipoiva = tmpcompradeta.idtipoiva) as tipo_iva,
	(select descripcion from gest_depositos where iddeposito = tmpcompradeta.iddeposito_tmp) as nombre_deposito
	from tmpcompradeta 
	where idt=$idtransaccion 
	and idemp=$idempresa 
	order by  idregcc asc";
$rsdet = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tdet = $rsdet->RecordCount();
$consulta = "
	SELECT * FROM tmpcompradeta WHERE lote is not null;
";
$cuenta_lote = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$lote_o_vencimiento = $cuenta_lote->RecordCount();



//Verificamos si cambio algo en el costo anterior
$consulta = "
Select * , (
select productos.barcode 
from productos 
inner join insumos_lista on insumos_lista.idproducto = productos.idprod_serial
where
tmpcompradeta.idprod = insumos_lista.idinsumo
) as barcode,
(
select costo 
from insumos_lista 
where 
idinsumo = tmpcompradeta.idprod
) as ultcosto
from tmpcompradeta 
where idt=$idtransaccion 
and idemp=$idempresa 
order by  idregcc desc
limit 1
";

$rsdetult = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tdetuc = $rsdetult->RecordCount();
$numero_items_carrito = 0;

// Para gastos adicionales se obtiene el idcompra_ref
$buscar = "select tmp.idcompra_ref, tmp.moneda from tmpcompras as tmp where tmp.idtran=$idtransaccion and tmp.estado=1";
$rscab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcompra_ref = $rscab->fields['idcompra_ref'];
$monedacab = $rscab->fields['moneda'];

if ($idcompra_ref > 0 && $monedacab == 2) {
    $idmoneda_select = 2;

} else {
    $buscar = "Select cotizaciones.cotizacion, cotizaciones.tipo_moneda as idmoneda_select, tipo_moneda.descripcion as moneda_nombre
from tmpcompras
LEFT JOIN cotizaciones on cotizaciones.idcot = tmpcompras.idcot
LEFT JOIN tipo_moneda on tipo_moneda.idtipo = cotizaciones.tipo_moneda 
where tmpcompras.idtran=$idtransaccion and tmpcompras.estado = 1 ";
    $rscab_cotiza = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $cotizacion = $rscab_cotiza->fields['cotizacion'];
    $moneda_nombre = $rscab_cotiza->fields['moneda_nombre'];
    $idmoneda_select = intval($rscab_cotiza->fields['idmoneda_select']);
}


if ($idmoneda_select == 0) {
    $idmoneda_select = $id_moneda_nacional;

}
$alerta_mensaje = "";




// buscar moneda de la orden
$consulta = "Select 
tmpcompras.moneda as idtipo_moneda, 
tipo_moneda.descripcion as nombre_moneda 
from tmpcompras 
INNER JOIN tipo_moneda on tipo_moneda.idtipo = tmpcompras.moneda 
where 
idtran=$idtransaccion 
and tmpcompras.idempresa=$idempresa 
";
$rs_orden = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtipo_moneda_tmp = $rs_orden->fields['idtipo_moneda'];
$nombre_moneda_tmp = $rs_orden->fields['nombre_moneda'];
?>
<div class="col-md-12 col-xs-12">
	<?php if (isset($errores) && $errores != "") {?>
	<br />
	<div class="alert alert-danger alert-dismissible fade in" role="alert" id="boxErroresEditarArticulo">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close">
			<span aria-hidden="true">×</span>
		</button>
		<strong>Errores:</strong><br /><p id="erroresEditarArticulo"><?php echo $errores?></p>
	</div>
	<?php }?>
	<?php if ($tdetuc > 0) {?>
	 * &Uacute;ltimo Cargado: <?php echo $rsdetult->fields['pchar']; ?> [<?php echo $rsdetult->fields['idprod']?>] - <?php echo $rsdetult->fields['barcode']?>
	 <?php if ($rsdetult->fields['costo'] != $rsdetult->fields['ultcosto'] && $rsdetult->fields['pchar'] != "DESCUENTO" && $rsdetult->fields['pchar'] != "AJUSTE") { ?>
	 <span class="fa fa-warning"></span>&nbsp;<span style="color:#F00;">COSTO CAMBIADO! Costo Factura: <?php echo formatomoneda($rsdetult->fields['costo'], 4, 'N'); ?> | Costo Anterior: <?php echo formatomoneda($rsdetult->fields['ultcosto'], 4, 'N'); ?></span><br />
	 <br />
	<?php }
	 }?>
		<?php if ($preferencias_medidas_referenciales == "S") { ?>
			<div class="div_leyenda"> 
				<div class="leyenda_precios"></div><small>Los precios se expresan en la unidad de medida correspondiente a la cantidad que estás comprando (Medida de Compra) </small>
				<div class="leyenda_medida_compra"></div><small>Medida de Compra</small>
			</div>
		<?php } ?>
	<div class="table-responsive">

		<table class="table table-bordered">
			<thead>
				<tr>
					<th></th>
					<th>Id Insumo</th>
					<th>Descripci&oacute;n</th>
					<?php if ($preferencias_medidas_referenciales == "S") { ?>
						<th class="medida_compra">Medida Compra</th>
					<?php } ?>
					<th>Dep&oacute;sito</th>
					<th>Unidades</th>
					<?php if ($preferencias_medidas_referenciales == "S") { ?>
						<th>Caja</th>
						<th>Pallets</th>
					<?php }
					?>
					<th <?php if ($preferencias_medidas_referenciales == "S") { ?>class="alerta_precios"<?php } ?> >Precio <?php echo $nombre_moneda_nacional; ?></th>
					<?php if (($moneda_nombre != "NULL" || $moneda_nombre != "") && $idmoneda_select != $id_moneda_nacional) { ?>
					<th <?php if ($preferencias_medidas_referenciales == "S") { ?>class="alerta_precios"<?php } ?> >Precio <?php echo $moneda_nombre; ?> </th>
					<?php } ?>
					<th>Sub Total <?php echo $nombre_moneda_nacional; ?></th>
					<?php if (($moneda_nombre != "NULL" || $moneda_nombre != "") && $idmoneda_select != $id_moneda_nacional) { ?>
					<th>Sub Total <?php echo $moneda_nombre; ?></th>
					<?php } ?>
					<?php if ($lote_o_vencimiento > 0) {?>
						<th>Detalles</th>
					<?php }?>
				</tr>
			</thead>
			<tbody>
				<?php
                 $t = 0;
$tiva10 = 0;
$tiva5 = 0;
$texenta = 0;
$tventa10 = 0;
$tventa5 = 0;
while (!$rsdet->EOF) {
    $idunico = intval($rsdet->fields['idregcc']);
    $cant = floatval($rsdet->fields['cantidad']);
    $costo = $rsdet->fields['costo'];
    $subt = floatval($rsdet->fields['subtotal']);
    $idmedida_articulo = floatval($rsdet->fields['idmedida']);
    if ($idmedida_articulo == 0) {
        $idmedida_articulo = $idunidades;
    }
    $t = $t + $subt;
    $iva = intval($rsdet->fields['iva']);
    $tcantidad += $cant;
    $subt_acum += $subt;
    $cantidad = floatval($rsdet->fields['cantidad']);
    $cant_medida2 = floatval($rsdet->fields['cant_medida2']);
    $cant_medida3 = floatval($rsdet->fields['cant_medida3']);

    $pallet = 0;
    $caja = 0;
    if ($cant_medida2 != 0 && $$cant_medida2 != 1 && $cant_medida2 != 0 && $cantidad % $cant_medida2 == 0) {
        $caja = $cantidad / $cant_medida2;
    }
    // echo $caja;
    // echo $cant_medida3;

    if ($caja != 0 && $cant_medida3 != 0 && ($cant_medida2 != 1 && $cant_medida3 != 1)) {
        $pallet = $caja / $cant_medida3;
        $pallet = number_format($pallet, 2, '.', '');
    }
    //  echo $pallet;
    //  echo "Cantidad Unitaria: ".$cant_medida;
    //  echo "Cantidad Caja: ".$cant_medida2;
    //  echo "Cantidad Pallet: ".$cant_medida3;
    //  exit;
    if ($iva == 5) {
        $tm = ($subt / 21);

        $xp = explode('.', $tm);
        $entero = $xp[0];
        $v = substr($xp[1], 0, 1);
        //Compraramos si el primer caracter es smayor a 5
        if (intval($v > 5)) {
            $entero = $entero + 1;
        } else {
            $entero = $entero;
        }
        $ivaf = $entero;
        $tiva5 = $tiva5 + $ivaf;

        $tventa5 = $tventa5 + $subt;
    }
    if ($iva == 10) {
        $tm = ($subt / 11);

        $xp = explode('.', $tm);
        $entero = $xp[0];
        $v = substr($xp[1], 0, 1);
        //Compraramos si el primer caracter es smayor a 5
        if (intval($v > 5)) {
            $entero = $entero + 1;
        } else {
            $entero = $entero;
        }
        $ivaf = $entero;
        $tiva10 = $tiva10 + $ivaf;

        $tventa10 = $tventa10 + $subt;
    }

    $costo_factura = $rsdet->fields['costo'];
    $costo_ultimo = $rsdet->fields['ultcosto'];
    $maneja_lote = intval($rsdet->fields['maneja_lote']);

    if ($maneja_lote == 1 && !isset($rsdet->fields['vencimiento'])) {
        $productos_alerta = 1;
        $alerta_mensaje = " Los productos que están marcados en color rojo no cuentan con información de lote y fecha de vencimiento, los cuales son requisitos obligatorios para dicho producto.<br>";
    }

    $costo_input = "";
    if ($idmedida_articulo == $idcaja) {
        $costo_input = $subt / $caja;
    }
    if ($idmedida_articulo == $idpallet) {
        $costo_input = $subt / $pallet;
    }
    if ($idmedida_articulo == $idunidades) {
        $costo_input = $subt / $cant;
    }
    //  echo $caja;
    //  echo "--".$pallet;
    //  exit;
    ?>
				<tr <?php if ($maneja_lote == 1 && !isset($rsdet->fields['vencimiento'])) { ?> class="alerta_color"<?php } ?>>
					<td align="center">
						<!-- <a href="javascript:void(0);" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Detalle"><span class="fa fa-search"></span></a> -->
						<a href="javascript:void(0);" onclick="editar_articulo(<?php echo $idunico ?>);" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Editar"><span class="fa fa-edit"></span></a>
						<a href="javascript:void(0);" onclick="eliminar_articulo(<?php echo $idunico ?>);" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
					</td>
					<th scope="right" style="text-align: center;"><?php echo $rsdet->fields['idprod'] ?></th>
					<td><?php echo antixss($rsdet->fields['pchar']); ?></td>
					<?php if ($preferencias_medidas_referenciales == "S") { ?>
						<td><?php echo antixss($rsdet->fields['medida']); ?></td>
					<?php } ?>
					<td align="center" >
						<div style>
							<?php echo($rsdet->fields['nombre_deposito']); ?>
						</div>
					</td>
					<td align="center"><?php echo formatomoneda($rsdet->fields['cantidad'], 2, 'N'); ?></td>
					<?php if ($preferencias_medidas_referenciales == "S") { ?>
						<td><?php echo antixss($caja); ?></td>
						<td><?php echo antixss($pallet); ?></td>
					<?php } ?>
					<td align="right">
							<?php echo formatomoneda($costo_input, 0, 'N'); ?>
					</td>
					<?php if (($moneda_nombre != "NULL" || $moneda_nombre != "") && $idmoneda_select != $id_moneda_nacional) { ?>
					<td align="right"><?php echo formatomoneda($costo_input / $cotizacion, 2, 'S'); ?></td>
					<?php } ?>
					<td align="right" ><?php echo formatomoneda($rsdet->fields['subtotal'], 0, 'S'); ?></td>
					<?php if (($moneda_nombre != "NULL" || $moneda_nombre != "") && $idmoneda_select != $id_moneda_nacional) { ?>
					<td align="right" ><?php echo formatomoneda($rsdet->fields['subtotal'] / $cotizacion, 2, 'S'); ?></td>
					<?php } ?>
					<?php if ($lote_o_vencimiento > 0) {?>
						<td>
						Vencimiento: <?php echo $rsdet->fields['vencimiento'] ? date("d/m/Y", strtotime($rsdet->fields['vencimiento'])) : "--" ?> <br> Lote: <?php echo ($rsdet->fields['lote'])   ?> 
						</td>
					<?php }?>
				</tr>

				<?php
    $numero_items_carrito++;
    $rsdet->MoveNext();
}

//update de la cabecera
$update = "update tmpcompras set totalcompra=$t where idtran=$idtransaccion and idempresa = $idempresa";
$conexion->Execute($update) or die(errorpg($conexion, $update));
//echo $update;

?>
				<tr>
						<?php if ($preferencias_medidas_referenciales == "S") { ?>
							<td colspan="5" align="center">Totales:</td>
						<?php } else { ?>
							<td colspan="4" align="center">Totales:</td>
						<?php } ?>
				
					
					<td align="center"><?php echo formatomoneda($tcantidad, 2, 'N'); ?></td>
					<?php if ($preferencias_medidas_referenciales == "S") { ?>
						<td colspan="3"></td>
					<?php } else { ?>
						<td></td>
					<?php } ?>
					<?php if (($moneda_nombre != "NULL" || $moneda_nombre != "") && $idmoneda_select != $id_moneda_nacional) { ?>
					<td></td>
					<?php } ?>
					<td align="right"><?php echo formatomoneda($subt_acum, 0, 'S'); ?></td>
					<?php if (($moneda_nombre != "NULL" || $moneda_nombre != "") && $idmoneda_select != $id_moneda_nacional) { ?>
						<td align="right"><?php echo formatomoneda($subt_acum / $cotizacion, 2, 'S'); ?></td>
					<?php } ?>
				</tr>
			</tbody>
			<?php if ($alerta_mensaje != "") { ?>
				 <div class="alerta_div"><span class="fa fa-warning"></span>&nbsp;<span ><?php echo($alerta_mensaje); ?></span><br /></div>
			<?php } ?>
		</table>
	</div>
</div>
<?php
//Diferencia para ajuste de factura
$consulta = "
select * from tmpcompras where idtran=$idtransaccion
";
$rscompracab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$vencimiento = $rscompracab->fields['vencimiento'];
$totalcompra = $rscompracab->fields['totalcompra'];
$monto_factura = $rscompracab->fields['monto_factura'];
if ($monto_factura < 0) {
    $monto_factura = 0;
}
$diferencia = $monto_factura - $subt_acum;
if (($moneda_nombre != "NULL" || $moneda_nombre != "") && $idmoneda_select != $id_moneda_nacional) {
    $diferencia_moneda = $monto_factura / $cotizacion - $subt_acum / $cotizacion;
}
//Desgloce del IVA
$joinAdd = "";
$selectAdd = "";
if ($preferencias_usa_iva_variable == "S") {
    $joinAdd = " INNER JOIN tmpcompradeta on tmpcompradetaimp.idtrandet = tmpcompradeta.idregcc
	INNER JOIN insumos_lista on insumos_lista.idinsumo = tmpcompradeta.idprod ";
    $selectAdd = " , insumos_lista.idconcepto  ";
}
$consulta = "
select iva_porc_col as iva_porc, sum(monto_col) as subtotal_poriva, sum(ivaml) as subtotal_monto_iva $selectAdd
from tmpcompradetaimp
$joinAdd
where 
idtran = $idtransaccion
group by iva_porc_col
order by iva_porc_col desc
";
//echo $consulta;
$rsivaporc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));







?>
<div class="row">
	<div class="col-md-6 col-xs-12">
		<h2>Desgloce de Factura</h2>
		<div class="table-responsive">
			<table class="table table-striped jambo_table bulk_action">
				<thead>
				<tr class="headings">
					<tr>
						<th colspan="4" align="center">Detalle de factura</th>
					</tr>
					<tr>
						<th>Total Factura</th>
						<th><strong>Total Detalle</strong></th>
						<th><strong>Diferencia</strong></th>
						<?php if ($diferencia != 0 && $numero_items_carrito >= 1) { ?>
							<th><strong>Ajustar</strong></th>
							<?php } ?>
					</tr>
					
				</thead>
				<tbody>
					<tr>
						<th colspan="4" align="center"><?php echo $nombre_moneda_nacional; ?></th>
					</tr>
					<tr>
						<td align="left"><?php echo formatomoneda($monto_factura, 0, 'S')?></td>
						<td align="left"><?php echo formatomoneda($subt_acum, 0, 'S')?></td>
						<td align="left"><?php echo formatomoneda($diferencia, 2, 'N'); ?></td>
						<?php if ($diferencia != 0 && $numero_items_carrito >= 1) { ?>
							<td align="left"><a class="cursor_pointer" onclick="compras_ajuste_auto(<?php echo $diferencia?>,<?php echo $monto_factura?>)">[Ajuste]</a></td>
						<?php } ?>
					</tr>
					<?php if (($moneda_nombre != "NULL" || $moneda_nombre != "") && $idmoneda_select != $id_moneda_nacional) { ?>
						<tr>
							<th colspan="4" align="center"><?php echo $moneda_nombre; ?></th>
						</tr>
						<tr>
							<td align="left"><?php echo formatomoneda($monto_factura / $cotizacion, 2, 'S')?></td>
							<td align="left"><?php echo formatomoneda($subt_acum / $cotizacion, 2, 'S')?></td>
							<td align="left"><?php echo formatomoneda($diferencia_moneda, 2, 'S'); ?></td>
							
						</tr>
					<?php } ?>	
					
				</tbody>
			</table>
		</div>


		<?php
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
if ($idtipo_origen != $id_tipo_origen_importacion) {
    ?>

			<div class="table-responsive">
				<table class="table table-striped jambo_table bulk_action">
					<thead>
					<tr class="headings">
						<tr>
							<th colspan="4" align="center">Liquidacion de Impuesto</th>
						</tr>
						<tr>
							<th>% Impuesto</th>
		
							<th style="text-align: right;" ><strong>Total Factura</strong></th>
							<th style="text-align: right;" ><strong>Monto Impuesto</strong></th>
						</tr>
					</thead>
					<tbody>
					<?php
                    while (!$rsivaporc->EOF) {?>
						<tr>
							<th colspan="4" align="center"><?php echo $nombre_moneda_nacional; ?></th>
						</tr>
						<tr>
							<td align="right"><?php

                        if ($rsivaporc->fields['idconcepto'] != $idconcepto_despacho && $rsivaporc->fields['idconcepto'] != $idconcepto_flete) {
                            if ($rsivaporc->fields['iva_porc'] > 0) {
                                echo agregaespacio(floatval($rsivaporc->fields['iva_porc']).'%', 3);
                            } else {
                                echo "Exento";
                            }
                        } else {
                            echo "Variable Despacho";
                        }
                        ?></td>
		
							<td align="right"><?php echo formatomoneda($rsivaporc->fields['subtotal_poriva'], 0, 'S'); ?></td>
							<td align="right"><?php echo formatomoneda($rsivaporc->fields['subtotal_monto_iva'], 0, 'S'); ?></td>
						</tr>
						<?php if (($moneda_nombre != "NULL" || $moneda_nombre != "") && $idmoneda_select != $id_moneda_nacional) { ?>
							<tr>
								<th colspan="4" align="center"><?php echo $moneda_nombre; ?></th>
							</tr>
							<tr>
								<td align="right"><?php
                            if ($rsivaporc->fields['idconcepto'] != $idconcepto_despacho && $rsivaporc->fields['idconcepto'] != $idconcepto_flete) {
                                if ($rsivaporc->fields['iva_porc'] > 0) {
                                    echo agregaespacio(floatval($rsivaporc->fields['iva_porc']).'%', 3);
                                } else {
                                    echo "Exento";
                                }
                            } else {
                                echo "Variable Despacho";
                            }
						    ?></td>
			
								<td align="right"><?php echo formatomoneda($rsivaporc->fields['subtotal_poriva'] / $cotizacion, 2, 'S'); ?></td>
								<td align="right"><?php echo formatomoneda($rsivaporc->fields['subtotal_monto_iva'] / $cotizacion, 2, 'S'); ?></td>
							</tr>
						<?php } ?>
					<?php
                        $rsivaporc->MoveNext();
                    }
    //$rsivaporc->MoveFirst();
    ?>
					</tbody>
				</table>
			</div>

		<?php } ?>
		<?php
        // si es a credito
        if ($rscompracab->fields['tipocompra'] == 2) {
            $consulta = "
			select * from tmpcompravenc where idtran=$idtransaccion order by vencimiento asc
			";
            $rsvenccomp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            ?>
		<!-- <h2><span class="fa fa-calendar"></span>&nbsp;Vencimientos | <a href="gest_reg_compras_venc.php?idtran=<?php //echo $idtransaccion;?>">[Personalizar]</a></h2> -->
		<h2><span class="fa fa-calendar"></span>&nbsp;Vencimientos | <a onclick="vencimientos_personalizar_modal()" href="javascript:void(0);" class="btn-personalizar">[ Agregar <span  class="fa fa-plus"></span>]</a></h2>
		
		<div class="table-responsive">
	
			<table class="table table-striped">
				<thead>
					<tr>
						<th></th>
						<th align="right" style="text-align: end;">Cuota</th>
						<th align="right" style="text-align: end;">Vencimiento</th>
						<th align="right" style="text-align: end;">Monto</th>
					</tr>
				</thead>
				<tbody>
			  <?php
                    $cuo = 0;
            $totalcuo = 0;
            while (!$rsvenccomp->EOF) {
                $cuo++;
                $totalcuo += $rsvenccomp->fields['monto_cuota'];
                ?>
	
				  <tr>
					  <td align="center" style="text-align: center;">
						  <a href="javascript:void(0);" onclick="vencimientos_personalizar_modal_editar(<?php echo $rsvenccomp->fields['idvencimiento'] ?>);" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Editar"><span class="fa fa-edit"></span></a>
						  <a href="javascript:void(0);" onclick="vencimientos_personalizar_modal_eliminar(<?php echo $rsvenccomp->fields['idvencimiento'] ?>);" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
					  </td>
					<td align="center" style="text-align: end;"><?php echo $cuo; ?></td>
					<td align="center" style="text-align: end;"><?php echo date("d/m/Y", strtotime($rsvenccomp->fields['vencimiento'])); ?></td>
					<td align="right" style="text-align: end;"><?php echo formatomoneda($rsvenccomp->fields['monto_cuota'], 4, 'N'); ?></td>
				</tr>
				<?php $rsvenccomp->MoveNext();
            }?>
				  <tr bgcolor="#CCCCCC" >
					<td align="center"> </td>
					<td align="center"> </td>
					<td align="center"> </td>
					<td align="right"><strong><?php echo formatomoneda($totalcuo, 4, 'N'); ?></strong></td>
				  </tr>
				</tbody>
			</table>
	
		</div>
		<?php } ?>
	
	
	
	
	</div>
	<!-- DESCUENTOS -->
	<?php if ($usar_descuentos_compras == "S") { ?>
	<div class="col-md-6 col-xs-12">
		<?php

	    ?>
		  <h2><span class="fa fa-money"></span>&nbsp;Descuentos</h2>
			
			<div class="table-responsive">
			  <table class="table table-striped">
					<thead>
						<tr>
							<th colspan="2">Monto</th>
							<th >Porcentaje</th>
							<th ></th>
	
						</tr>
					</thead>
	
					<tr id ="descuentos_form">

						
						<td colspan='2'>
							<input type text name="monto" id="monto" onkeyup="cargarPorcentaje(this.value,<?php echo $monto_factura?>)" value="" class="form-control" />
						</td>
						
						<td >
							<div class="input-group " style="display:flex;">
								<input type text name="porcentaje"  aria-describedby="basic-addon3"  class="form-control" aria-describedby="montoHelp" id="porcentaje" onkeyup="cargarMonto(this.value,<?php echo $monto_factura?>)" value=""  class="form-control" />
								<div class="input-group-append">
									<span class="input-group-text" id="basic-addon3">%</span>
								</div>
							</div>
							<small id="montoHelp" class="form-text text-muted">Los valores equivalen al porcenaje ejemplo 10 %.</small>
						</td>
						
						<td ><a href="javascript:void(0);" onclick="agregar_descuento(<?php echo $monto_factura?>);" class="btn btn-dark">[Agregar]</a></td>
					</tr>
					<tr style="background: #F9F9F9;display:none;" id="form_descuento_tipo_moneda"  >
						<?php
	                    // echo $moneda_nombre." ".$idmoneda_select." ".$id_moneda_nacional;exit;
	                    if (($moneda_nombre != "NULL" || $moneda_nombre != "") && $idmoneda_select != $id_moneda_nacional) { ?>
							<td >
								<div class="row" style="margin:0px;">
									<div class="form-group">
										<label class="control-label col-md-6 col-sm-6 col-xs-12">Monto Moneda</label>
										<div class=" col-md-6 col-sm-6 col-xs-12">
											<div class="form-check " id="box_radio_moneda_extranjera_form_descuento" class="col-md-6" style="display:inline-block;">
												<input class="form-check-input" data-hidden-nacional="false"  value="<?php echo $idmoneda_select; ?>" type="radio" name="radio_moneda_form_descuento" id="radio_moneda_extranjera_form_descuento" >
												<label class="form-check-label" id="label_moneda_extranjera" for="radio_moneda_extranjera_form_descuento">
													<?php echo $moneda_nombre; ?>
												</label>
											</div>
											<div class="form-check " id="box_radio_moneda_nacional_form" class="col-md-6" style="display:inline-block;">
												<input  checked class="form-check-input" data-hidden-nacional="true"  value="<?php echo $id_moneda_nacional; ?>" type="radio" name="radio_moneda_form_descuento" id="radio_moneda_nacional_form_descuento" >
												<label class="form-check-label" id="label_moneda_nacional" for="radio_moneda_nacional_form_descuento">
												<?php echo $nombre_moneda_nacional; ?>
												</label>
											</div>
										</div>
									</div>
								</div>
							</td>
							<td></td>
							<td>
							<small class="form-text text-muted">Es fundamental especificar la moneda del monto asociado al descuento.</small>

							</td>
							<td></td>
						<?php } ?>
					</tr>
					
				</table>
			</div>
		<hr />
		
	
	
	
	

	
	</div>
	<br>
	<?php }?>


	<!-- pagos -->
	
	<div class="col-md-6 col-xs-12">
		<?php

	    if ($usa_cta_int == 'S') { //
	        //$buscar="Select * from bancos where muestra_propio='S' order by nombre asc";
	        $buscar = "Select * from cuentas  order by denominacion asc";
	        $rslista1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
	        $buscar = "Select * from formas_pago  order by descripcion asc";
	        $rslista2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
	        ?>
		  <h2><span class="fa fa-money"></span>&nbsp;Pagos</h2>
			<div class="alert alert-danger alert-dismissible " role="alert" style="display:none;" id="erroresjspagos">
				<span id="errorestxtpagos"></span>
	
			</div>
			<div class="table-responsive">
			  <table class="table table-striped">
					<thead>
						<tr>
							<th>Cuenta Interna</th>
							<th>Forma Pago</th>
							<th colspan="2">Monto</th>
	
						</tr>
					</thead>
	
					<tr>
						<td>
						<select name="listacuentas" id="listacuentas" class="form-control">
							<option value="" selected>Seleccionar</option>
							<?php while (!$rslista1->EOF) { ?>
								<option value="<?php echo $rslista1->fields['idcuenta'] ?>"><?php echo $rslista1->fields['denominacion']." CTA:[".$rslista1->fields['cuentanum']."]"  ?></option>
							<?php  $rslista1->MoveNext();
							} ?>
						</select>
						</td>
						<td>
						<select name="listapagos" id="listapagos" class="form-control">
							<option value="" selected>Seleccionar</option>
							<?php while (!$rslista2->EOF) { ?>
								<option value="<?php echo $rslista2->fields['idforma'] ?>"><?php echo $rslista2->fields['descripcion']?></option>
							<?php  $rslista2->MoveNext();
							} ?>
						</select>
						</td>
						<td colspan='2'><input type text name="monto_abonado" id="monto_abonado" value="" class="form-control" /></td>
	
					</tr>
					<tr>
						<td colspan="2"><textarea name="obs" id="obs" placeholder="Indique numero de cheque / otros valores / comentarios que desee" style="width:95%;resize: vertical;"></textarea></td>
						<td colspan="2"><a href="javascript:void(0);" onclick="agregar_cuenta();" class="btn btn-dark">[Agregar]</a></td>
					</tr>
				</table>
			</div>
		<hr />
		<div id="carrito_fpago" class="col-md-12 col-xs-12">
			<?php require_once("compras_carrito_lista.php"); ?>
		</div>
	
	
	
	
	
		<?php } ?>
	
	</div>
</div>
<div class="row">
	<!-- display: flex !important; justify-content: center !important; -->
	<div class="col-12 " style=" display: grid;grid-template-columns: 1fr  1fr;gap: 10px;">
		<?php if ($productos_alerta == 1) { ?>
			<button  class="btn btn-lg btn_insumo_select hover_cancelar pointer_cancel" id="generar_compra" >Finalizar Compra</button>
		<?php } else { ?>
			<button  class="btn  btn-lg btn_insumo_select hover_cancelar " id="generar_compra" onclick="generar_compra()">Finalizar Compra</button>
		<?php } ?>
		<button  class="btn  btn-lg btn_insumo_select hover_finalizar " id="cerrar_compra" onclick="cerrar_compra()">Cerrar Compra</button>
	</div>
</div>
<style>
	/* .btn_finalizar_compra{
		background: #F5A77C;
		color: #fff;
	} */
	.alerta_precios{
		background: #FFC857;
		color: white;
	}
	.medida_compra{
		background: #9BC1BC;
		color: white;
	}
	
	.div_leyenda{
		width: 100%;
		display: flex;
		align-items: center;
	}
	.leyenda_medida_compra{
		width: 10px;
		height: 10px;
		background: #9BC1BC;
		display: inline-block;
		margin: 10px;
	}

	.leyenda_precios{
			width: 10px;
			height: 10px;
			background: #FFC857;
			display: inline-block;
			margin: 10px;
		}
	.alerta_div{
		display: flex;
		align-items: center;
		justify-content: center;
		background: #ce2d4fa8;
		color: white;
		font-weight: bold;
		padding: 1rem;
	}
	.pointer_cancel{
		cursor: no-drop;
		background-color: #cecece;
		border: #cecece solid 1px !important;
		color:#fff !important;
	}
	.hover_finalizar:hover{
		background-color: hsl(20, 80%, 70%);
		color: #fff !important;
		border: hsl(20, 80%, 70%) solid 1px;
	} 

	.hover_cancelar:hover{
		background-color: hsl(210, 50%, 70%);
		color: #fff !important;
		border: hsl(210, 50%, 70%) solid 1px;
	}

	.cursor_pointer{
		cursor: pointer;
	}
	.center-column {
		grid-column: 2; /* Posición en la columna central */
	}

	.center-row {
		grid-row: 2; /* Posición en la segunda fila */
	}
	.btn-personalizar{
		padding: 1rem;
		box-sizing: border-box;
		border: #fff solid 1px;
	}
	/* .btn-personalizar:hover{
		box-sizing: border-box;
		border: #6789A9 solid 1px;
		border-radius: 1rem;
	} */
</style>


	

