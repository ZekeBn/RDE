<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");

// Recibir los datos del POST
$data = json_decode(file_get_contents("php://input"), true);

$idPedido = $data['idPedido'] ?? null;
$usuario = $data['usuario'] ?? '';
$fecha = $data['fecha'] ?? '';

if ($idPedido) {
    // Si el campo de fecha está vacío, eliminar el registro
    if (empty($fecha)) {
        $consulta = "DELETE FROM autorizaciones WHERE idpedido = ?";
        $params = [$idPedido];
    } else {
        // Insertar o actualizar la autorización
        $consulta = "REPLACE INTO autorizaciones (idpedido, usuario, fecha) VALUES (?, ?, ?)";
        $params = [$idPedido, $usuario, $fecha];
    }

    $stmt = $conexion->Prepare($consulta);
    $result = $conexion->Execute($stmt, $params);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Datos guardados correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar los datos.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID del pedido no válido.']);
}
?>
