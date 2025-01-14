<?php
require_once("../includes/conexion.php");

header('Content-Type: application/json');

// Obtiene los datos del cuerpo de la solicitud
$filtros = json_decode(file_get_contents("php://input"), true);

// Construye la consulta base
$consulta = "
    SELECT * 
    FROM pedidos
    where estado = 1";

$rs = $conexion->Execute($consulta) or die(json_encode(["error" => "Error en la consulta"]));

// Crea un arreglo con los datos
$resultados = [];
while (!$rs->EOF) {
    $resultados[] = [
        'idpedido' => $rs->fields['idpedido'],
        'estado' => $rs->fields['estado'],
        'fecPedido' => $rs->fields['fecPedido'],
        'cliente' => $rs->fields['cliente'],
        'montoTotal' => number_format($rs->fields['montoTotal'], 2),
    ];
    $rs->MoveNext();
}

// Devuelve los datos como JSON
echo json_encode($resultados);
?>