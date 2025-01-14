<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

// funciones para stock
require_once("includes/funciones_stock.php");


if ($_POST['inserta'] == 'S') {


    $valido = "S";
    $errores = "";


    // recibe parametros
    $idmesa = intval($_POST['idmesa']);
    $idcliente = intval($_POST['idcliente']);
    $condicion_venta = intval($_POST['condventa']);
    $idatc = intval($_POST['idatc']);

    // genera los parametros para enviar a la funcion
    $parametros_array = [
        'idcliente' => $idcliente,
        'idmesa' => $idmesa,
        'tipoventa' => $condicion_venta,
        'idatc' => $idatc
        // etc etc ......................


    ];


    // si no es una mesa
    if ($idmesa == 0) {

        // valida el pedido
        $res = validar_pedido($parametros_array);
        if ($res['valido'] == 'N') {
            $valido = $res['valido'];
            $errores .= $res['errores'];
        }

    }



    // valida la venta
    validar_venta($parametros_array);
    if ($res['valido'] == 'N') {
        $valido = $res['valido'];
        $errores .= $res['errores'];
    }

    // si todo es valido
    if ($valido == 'S') {

        // registra el pedido
        $res = registrar_pedido($parametros_array);
        $idpedido = intval($res['idpedido']);

        if ($idmesa == 0) {
            // agrega datos del pedido para venta
            $parametros_array_add = [
                'idpedido' => $idpedido
            ];
            $parametros_array_ven = array_merge($parametros_array, $parametros_array_add);

        }


        // registra la venta
        $res = registrar_venta($parametros_array_ven);

        // id venta registrado
        $idventa = intval($res['idventa']);


        // respuesta json
        $arr = [
            'errores' => '',
            'valido' => 'S',
            'idventa' => $idventa
        ];



    } else {

        // respuesta json
        $arr = [
            'errores' => $errores,
            'valido' => 'N'
        ];

    }



    // convierte a formato json
    $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

    // devuelve la respuesta formateada
    echo $respuesta;



}
