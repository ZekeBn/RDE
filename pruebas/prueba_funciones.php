<?php

include('../lib/php-simple-html-dom-parser/Src/Sunra/PhpSimple/HtmlDomParser.php');
use Sunra\PhpSimple\HtmlDomParser;

require_once("../includes/funciones.php");
require_once("../includes/conexion.php");
// generar_cabecera_compra();
// echo "eso fue la funcion";
$data_transaccion = [
    'idempresa' => 1,
    'idusu' => 1,
    'idcliente' => 1,
    'estado' => 1,
    'sucursal' => 1,
    'fecha' => "2023-05-16",
    'tipo' => 1,
];

// echo $data["data"];
$res = generar_transaccion($data_transaccion);
// $res=generar_transaccion($parametros_array);
if ($res["sucess"]) {
    $numero = $res["data"];
    $data = [
        'idproveedor' => 1,
        'sucursal' => 1,
        'idtran' => $numero,
        'idempresa' => 1,
        'fechacompra' => 1,
        'facturacompra' => 1,
        'facturacompra_incrementa' => 1,
        'registrado_por' => 1,
        'total' => 1,
        'iva10' => 1,
        'iva5' => 1,
        'exenta' => 1,
        'registrado' => 1,
        'estado' => 1,
        'tipocompra' => 1,
        'moneda' => 1,
        'cambio' => 1,
        'comprastock' => 1,
        'cambioreal' => 1,
        'cambiohacienda' => 1,
        'cambioproveedor' => 1,
        'anulado_el' => 1,
        'anulado_por' => 1,
        'vencimiento' => 1,
        'timbrado' => 1,
        'vto_timbrado' => 1,
        'notacredito' => 1,
        'creado_auto' => 1
    ];
    echo json_encode(generar_cabecera_compra($data));
}
