<?php
require_once("../includes/conexion.php");

header('Content-Type: application/json');

// Obtiene los datos del cuerpo de la solicitud
$filtros = json_decode(file_get_contents("php://input"), true);

// Construye la consulta base
$consulta = "   SELECT p.estado,
           p.tip_comprobante,
           p.ser_comprobante,
           p.nro_comprobante,
           p.fec_comprobante,
           p.cod_cliente,
           p.cod_vendedor,
           p.cod_lista_precio,
           p.cod_moneda,
           p.tot_comprobante,
           p.cod_usuario,
           p.fec_estado,
           p.autoriza_ctacte_usu,
           p.autoriza_ctacte_fec
    FROM pedidos_cab p"; // Agregamos join para cliente

$condiciones = [];

// Aplica filtros según los datos recibidos
if (!empty($filtros['cod_sucursal'])) {
    $condiciones[] = "p.cod_sucursal = " . $conexion->qstr($filtros['cod_sucursal']);
}
if (!empty($filtros['fec_comprobante'])) {
    $condiciones[] = "DATE(p.fec_comprobante) = " . $conexion->qstr($filtros['fec_comprobante']);
}
if (!empty($filtros['nro_comprobante'])) {
    $condiciones[] = "p.nro_comprobante = " . $conexion->qstr($filtros['nro_comprobante']);
}
if (!empty($filtros['cod_cliente'])) {
    $condiciones[] = "c.nombre LIKE " . $conexion->qstr("%" . $filtros['cod_cliente'] . "%");
}

// Agrega las condiciones a la consulta si existen
if (!empty($condiciones)) {
    $consulta .= " WHERE " . implode(" AND ", $condiciones);
}

// Ordena los resultados por fecha de pedido descendente
$consulta .= " ORDER BY p.fec_comprobante DESC";

try {
    $rs = $conexion->Execute($consulta);
    
    if (!$rs) {
        throw new Exception("Error al ejecutar la consulta.");
    }

    // Crea un arreglo con los datos
    $resultados = [];
    while (!$rs->EOF) {
        $resultados[] = [
            'estado' => $rs->fields['estado'],
            'serie' => $rs->fields['serie'],
            'nro' => $rs->fields['nro'],
            'fecPedido' => $rs->fields['fecPedido'],
            'cliente' => $rs->fields['cliente'],
            'montoTotal' => number_format($rs->fields['montoTotal'], 2),
        ];
        $rs->MoveNext();
    }

    // Devuelve los datos como JSON
    echo json_encode($resultados);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
    