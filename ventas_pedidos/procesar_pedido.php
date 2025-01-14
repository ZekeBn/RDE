<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Leer los datos enviados desde JavaScript (formulario + tabla)
    $data = json_decode(file_get_contents('php://input'), true);

    if (!empty($data['cliente']) && !empty($data['productos'])) {
        try {
            // Obtener datos del cliente
            $idcliente = antisqlinyeccion($data['cliente'], 'int');

            $consultaCliente = "SELECT nombre, apellido, direccion, documento, celular 
                                FROM cliente WHERE idcliente = '$idcliente'";
            $rsCliente = $conexion->Execute($consultaCliente) or die(errorpg($conexion, $consultaCliente));

            if ($rsCliente->EOF) {
                throw new Exception('Cliente no encontrado en la base de datos.');
            }

            $clienteData = $rsCliente->fields;
            $nombres = $clienteData['nombre'];
            $apellidos = $clienteData['apellido'];
            $direccionentrega = $clienteData['direccion'];
            $documento = $clienteData['documento'];
            $celular = $clienteData['celular'];

            // Datos generales para la cabecera
            $fechapedido = date('Y-m-d H:i:s');
            $idempresa = 1; // Supongamos que es un valor fijo
            $medio_pedido = isset($data['medio_pedido']) ? antisqlinyeccion($data['medio_pedido'], 'int') : 1;
            $forma_pago = isset($data['forma_pago']) ? antisqlinyeccion($data['forma_pago'], 'int') : 1;
            $registrado_por = 1; // Suponemos un usuario por defecto
            $estado = 0; // Estado inicial
            $totalgs = 0.0;

            // Insertar en la tabla `pedidos`
            $consultaPedido = "INSERT INTO pedidos (
                fechapedido, idempresa, idcliente, direccionentrega, medio_pedido, forma_pago, registrado_por,
                estado, nombres, apellidos, documento, celular, direccion, totalgs
            ) VALUES (
                '$fechapedido', '$idempresa', '$idcliente', '$direccionentrega', '$medio_pedido', '$forma_pago',
                '$registrado_por', '$estado', '$nombres', '$apellidos', '$documento', '$celular', '$direccionentrega', '$totalgs'
            )";

            $conexion->Execute($consultaPedido) or die(errorpg($conexion, $consultaPedido));

            // Obtener el ID del pedido reciÃ©n creado
            $idPedido = $conexion->Insert_ID();

            // Insertar los productos en la tabla `pedidos_detalles`
            foreach ($data['productos'] as $producto) {
                $idprod = antisqlinyeccion($producto['idprod'], 'string');
                $cantidad = antisqlinyeccion($producto['cant_uni'], 'int');
                $precio_venta = antisqlinyeccion($producto['precio_unitario'], 'float');
                $subtotal = $cantidad * $precio_venta;
                $totalgs += $subtotal;

                $consultaDetalle = "INSERT INTO pedidos_detalles (
                    idpedido, cantidad, idprod, precio_venta, subtotal
                ) VALUES (
                    '$idPedido', '$cantidad', '$idprod', '$precio_venta', '$subtotal'
                )";

                $conexion->Execute($consultaDetalle) or die(errorpg($conexion, $consultaDetalle));
            }

            // Actualizar el total en la tabla `pedidos`
            $consultaActualizarPedido = "UPDATE pedidos SET totalgs = $totalgs WHERE idpedido = '$idPedido'";
            $conexion->Execute($consultaActualizarPedido) or die(errorpg($conexion, $consultaActualizarPedido));

            echo json_encode(['status' => 'success', 'message' => 'Pedido registrado exitosamente.']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error al registrar el pedido: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
    }
}
?>