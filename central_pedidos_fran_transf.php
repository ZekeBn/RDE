 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "333";
require_once("includes/rsusuario.php");


// validaciones basicas
$valido = "S";
$errores = "";

//http://localhost/ekaru/central_pedidos_fran_transf.php?idpedido=609&idfranquicia=7

// validaciones que nunca deberia de suceder mensajes solo para ambiente de desarrollo
$idpedido = intval($_REQUEST['idpedido']);
if ($idpedido == 0) {
    echo "Codigo de Pedido no enviado!";
    exit;
}
$idfranquicia = intval($_REQUEST['idfranquicia']);
if ($idfranquicia == 0) {
    echo "Codigo de Franquicia no enviado!";
    exit;
}


// validaciones reales
$consulta = "
SELECT idfranquicia, nombre_franquicia, url_franquicia, estado 
FROM franquicia 
where 
estado = 1 
and idfranquicia = $idfranquicia
";
$rsfran = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$url_franquicia = trim(strtolower($rsfran->fields['url_franquicia']));
$url_franquicia_pedidos = $url_franquicia.'api_integra/app_pedidos_simp.php';
//$url_franquicia_pedidos='http://localhost/ekaru/api_integra/app_pedidos_simp.php';
if ($url_franquicia == '') {
    $valido = "N";
    $errores = "- La franquicia seleccionada no tiene url cargada.".$saltolinea;
}



// si todo es correcto envia
if ($valido == "S") {



    //genera pedido en json

    // datos de detalle
    $consulta = "
    SELECT *
    FROM tmp_ventares
    where 
    idtmpventares_cab = $idpedido
    and idtipoproducto <> 5
    and borrado = 'N'
    ";
    $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $i = 0;
    while (!$rsdet->EOF) {
        $idtipoproducto = $rsdet->fields['idtipoproducto'];
        $idventatmp = $rsdet->fields['idventatmp'];
        // armar agregado si aplica
        $consulta = "
         select 
         idtmpventaresagregado, idventatmp, idproducto, idingrediente, precio_adicional, alias, cantidad, fechahora,
         (
         select productos.idprod_serial 
         from ingredientes
         inner join insumos_lista on insumos_lista.idinsumo = ingredientes.idinsumo
         inner join productos on productos.idprod_serial = insumos_lista.idproducto
         where
         ingredientes.idingrediente = tmp_ventares_agregado.idingrediente
         ) as idproducto_ag
         from tmp_ventares_agregado 
         where 
         idventatmp = $idventatmp 
         order by idtmpventaresagregado asc
         ";
        $rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $iag = 0;
        $agregado = [];
        while (!$rsag->EOF) {
            $agregado[$iag]['idproducto'] = $rsag->fields['idproducto_ag'];
            $agregado[$iag]['cantidad'] = floatval($rsag->fields['cantidad']);
            $iag++;
            $rsag->MoveNext();
        }
        if (intval($agregado[0]['idproducto']) == 0) {
            $agregado = "";
        }

        // armar sacado si aplica
        $consulta = "
        SELECT 
        tmp_ventares_sacado.*, insumos_lista.idinsumo
        FROM tmp_ventares_sacado
        inner join ingredientes on ingredientes.idingrediente = tmp_ventares_sacado.idingrediente
        inner join insumos_lista on insumos_lista.idinsumo = ingredientes.idinsumo
        where
        idventatmp = $idventatmp
        order by idtmpventaressacado asc
        ";
        $rssac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $sacados = "";
        while (!$rssac->EOF) {
            $sacados .= $rssac->fields['idinsumo'].",";
            $isac++;
            $rssac->MoveNext();
        }
        $sacados = trim(substr($sacados, 0, -1));

        // armar combo si aplica
        $combo = "";
        if ($idtipoproducto == 2) {
            $consulta = "
            SELECT idlistacombo
            FROM tmp_combos_listas
            where
            idventatmp = $idventatmp
            group by idlistacombo
            order by idlistacombo asc
            ";
            $rscomblis = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $combo = [];
            $icomblis = 0;
            while (!$rscomblis->EOF) {
                $idlistacombo = $rscomblis->fields['idlistacombo'];
                $combo[$icomblis]['idlistacombo'] = $idlistacombo;
                $consulta = "
                select idproducto 
                from tmp_combos_listas
                where
                idventatmp = $idventatmp
                and idlistacombo = $idlistacombo
                ";
                $rscomprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $productos_combo = "";
                while (!$rscomprod->EOF) {
                    $productos_combo .= $rscomprod->fields['idproducto'].",";
                    $rscomprod->MoveNext();
                }
                $productos_combo = substr($productos_combo, 0, -1);
                $combo[$icomblis]['idproducto'] = $productos_combo;
                $icomblis++;
                $rscomblis->MoveNext();
            }


        }

        // armar combinado extendido si aplica
        $combinado_part = "";
        $combinado = [];
        if ($idtipoproducto == 4) {
            $consulta = "
            SELECT idproducto_partes
            FROM tmp_combinado_listas
            where
            idventatmp = $idventatmp
            order by idproducto_partes asc
            ";
            $rscombin = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $combinado_part = "";
            $icombi = 0;
            while (!$rscombin->EOF) {
                $combinado_part .= $rscombin->fields['idproducto_partes'].",";
                $icombi++;
                $rscombin->MoveNext();
            }
            $combinado_part = substr($combinado_part, 0, -1);
            $combinado['idproducto'] = $combinado_part;
        }
        if (intval($combinado['idproducto']) == 0) {
            $combinado = "";
        }

        // armar detalle del pedido
        $pedido_det[$i] = [
            'idproducto' => $rsdet->fields['idproducto'],
            'cantidad' => floatval($rsdet->fields['cantidad']),
            'observacion' => $rsdet->fields['observacion'],
            'agregado' => $agregado,
            'sacado' => $sacados,
            'combo' => $combo,
            'combinado' => $combinado,
        ];
        $i++;
        $rsdet->MoveNext();
    }

    //datos de cabecera
    $consulta = "
    SELECT *,
    (
    SELECT referencia FROM cliente_delivery_dom where iddomicilio = tmp_ventares_cab.iddomicilio
    ) as referencia
    FROM tmp_ventares_cab
    where 
    idtmpventares_cab = $idpedido
    and estado <> 6
    ";
    $rscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // armar la cabecera del pedido
    $pedido_cab = [
        'codigo_externo' => $idpedido,
        'codigo_externo_real' => $rscab->fields['idtrans'],
        'idsucursal' => 1,
        'idcanal' => $rscab->fields['idcanal'],
        'latitud' => $rscab->fields['latitud'],
        'longitud' => $rscab->fields['longitud'],
        'nombre_carry' => $rscab->fields['chapa'],
        'telefono' => $rscab->fields['telefono'],
        'llevapos' => $rscab->fields['llevapos'],
        'monto_pedido' => floatval($rscab->fields['monto']),
        'cambio' => $rscab->fields['cambio'],
        'observacion' => $rscab->fields['observacion'],
        //'idcliente' => 1,
        //'idclientedel' => 1,
        //'iddomicilio' => 1,
        'ruc' => $rscab->fields['ruc'],
        'razon_social' => $rscab->fields['razon_social'],
        'nombre' => $rscab->fields['nombre_deliv'],
        'apellido' => $rscab->fields['apellido_deliv'],
        'direccion' => $rscab->fields['direccion'],
        'referencia' => $rscab->fields['referencia'],

        'detalle' => $pedido_det,
    ];

    //print_r($pedido_cab);exit;

    // convierte pedido a json
    $pedido_cab_json = json_encode($pedido_cab);
    //echo $pedido_cab_json;

    // update usuarios set clave = md5('tansferfran2021++') where usuario = 'PEDIDOSWEB' en todos los locales
    // 84dd136282b187884fbc97d27e95a272
    $post_data = [
        'usuario' => 'PEDIDOSWEB',
        'clave' => 'tansferfran2021++',
        'pedido' => $pedido_cab_json,
    ];
    $parametros_array = [
        'url' => $url_franquicia_pedidos,
        'postdata' => $post_data

    ];
    //print_r($parametros_array);
    $res = abrir_url($parametros_array);
    //print_r($res);exit;
    $respuesta = json_decode($res['respuesta'], true);
    //print_r($respuesta);exit;
    $valido = $respuesta['data']['valido'];
    // si no es valido
    if ($valido != 'S') {
        $valido = "N";
        $errores .= $respuesta['data']['errores'];
        // si no retorno ningun error
        if (trim($errores) == '') {
            $errores .= $res['respuesta'];
            // si hubo un error http por ejemplo 404 o 403 o 500
            if ($res['http_code'] != '200') {
                $errores .= 'Error: '.$res['http_code'].' | '.$res['http_error'];
            }
        }
    }
    $idpedidoexterno = intval($respuesta['data']['idpedido']);
    //print_r($res);exit;
    //$valido="";


}

$idpedidoexterno = intval($respuesta['data']['idpedido']);
if ($idpedidoexterno == 0) {
    $valido = "N";
}
// si todo es valido
if ($idpedidoexterno > 0 && $valido == 'S') {
    // entonces marca como registrado en nuestro sistema
    $consulta = "
    update tmp_ventares_cab 
    set 
    registrado = 'S',
    notificado = 'S',
    fechahora_reg='$ahora',
    anulado_por=$idusu,
    anulado_el='$ahora',
    idpedidoexterno=$idpedidoexterno
    where 
    idtmpventares_cab = $idpedido
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}

// genera array con los datos
$arr = [
'idpedidoexterno' => $idpedidoexterno,
'valido' => $valido,
'errores' => $errores
];

//print_r($arr);

// convierte a formato json
$respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// devuelve la respuesta formateada
echo $respuesta;
exit;
?>
