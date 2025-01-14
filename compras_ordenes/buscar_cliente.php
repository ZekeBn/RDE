<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");

$valorBusqueda = isset($_POST['bus']) ? $_POST['bus'] : '';

if ($valorBusqueda != '' && is_numeric($valorBusqueda)) {
    $ra = antisqlinyeccion($valorBusqueda, 'int');
    $ra = str_replace("'", "", $ra);
    $add = " AND idcliente LIKE '%$ra%'";
} elseif ($valorBusqueda != '') {
    $ra = antisqlinyeccion($valorBusqueda, 'text');
    $ra = str_replace("'", "", $ra);
    $add = " AND nombre LIKE '%$ra%'";
} else {
    $add = "";
}

$buscar = "
    SELECT idcliente, nombre, email, telefono, ruc, direccion
    FROM cliente 
    WHERE estado = '1'
    $add
    ORDER BY idcliente ASC
";
$resultado = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$clientes = [];

while (!$resultado->EOF) {
    $cliente = [
        'idcliente' => $resultado->fields['idcliente'],
        'nombre' => $resultado->fields['nombre'],
        'email' => $resultado->fields['email'],
        'telefono' => $resultado->fields['telefono'],
        'ruc' => $resultado->fields['ruc'],
        'direccion' => $resultado->fields['direccion']
    ];

    $clientes[] = $cliente;
    $resultado->MoveNext();
}

// Set JSON headers
header('Content-Type: application/json');

// Encode and send JSON response
echo json_encode($clientes);
