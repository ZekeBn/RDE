<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("./mesas_qr_funciones.php");
require_once("./mesas_preferencias.php");
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
$errores = "";

$tipo_pedido = $_POST['tipo_pedido'];
$id_mesa = $_POST['id_mesa'];
$idmozo = $_POST['idmozo'];

$data = "";


$parametros_array = [
    "id_mesa" => $id_mesa
];
$rs_mesas_atc = buscar_mesa_atc($parametros_array);
$idatc = intval($rs_mesas_atc['idatc']);
$fecha = $ahora;
$estado = 1;

$parametros_array = [
    "idatc" => $idatc,
    "estado" => $estado,
    "tipo_pedido" => $tipo_pedido
];
$rs_verificar_pedido = verifiar_pedido($parametros_array);
$idpedido = intval($rs_verificar_pedido['idpedido']);
if ($idatc > 0 && $idpedido > 0) {

    $parametros_array = [
        "idpedido" => $idpedido,
        "fecha" => $fecha,
        "idmozo" => $idmozo

    ];
    $data = confirmar_pedido($parametros_array);

} else {
    if ($idatc == 0) {
        $data = ["success" => true,"error" => "Error: la mesa no esta activa." ];
    }
    if ($idpedido == 0) {
        $data = ["success" => false,"error" => "Error: no existe un pedido activo." ];
    }
}
// Set the appropriate headers to indicate JSON content
header('Content-Type: application/json');

// Encode the data array as JSON and output it
echo json_encode($data);
