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
$id_atc = $_SESSION['idatc'];

$data = "";


$parametros_array = [
    "id_mesa" => $id_mesa,
    "id_atc" => $id_atc
];
$rs_mesas_atc = verificar_atc($parametros_array);
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
if ($idatc > 0 && $idpedido == 0) {

    $idpedido = select_max_id_suma_uno("mesas_pedidos", "idpedido")["idpedido"];
    $parametros_array = [
        "idatc" => $idatc,
        "estado" => $estado,
        "tipo_pedido" => $tipo_pedido,
        "idpedido" => $idpedido,
        "fecha" => $fecha
    ];
    $data = agregar_pedido($parametros_array);

} else {
    if ($idatc == 0) {
        $data = ["success" => false,"error" => "Error: la mesa no esta activa.","logout" => true ];
    }
    if ($idpedido > 0) {
        $data = ["success" => false,"error" => "Error: ya existe un pedido activo.", "logout" => false ];
    }
}
// Set the appropriate headers to indicate JSON content
header('Content-Type: application/json');

// Encode the data array as JSON and output it
echo json_encode($data);
