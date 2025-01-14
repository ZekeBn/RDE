<?php
require_once("../includes/conexion.php");

header('Content-Type: application/json');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true); // Decodificar JSON
    $idPedido = isset($data['idPedido']) ? intval($data['idPedido']) : null;
    $nuevoEstado  = isset($data['nuevoEstado']) ? intval($data['nuevoEstado']) : null;
    
    if ($idPedido && $nuevoEstado) {
        $query = "UPDATE pedidos SET estado = ? WHERE idpedido = ?";
        $stmt = $conexion->Prepare($query);

        try {
            $rs = $conexion->Execute($stmt, [$nuevoEstado, $idPedido]);

            if ($rs) {
                echo json_encode(['status' => 'success', 'message' => 'Pedido actualizado correctamente.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el pedido.']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Excepción al actualizar el pedido: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
exit;
?>