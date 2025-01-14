<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";

$dirsup = "S";
require_once("../includes/rsusuario.php");

$consulta = "
SELECT 
    TRIM(b.descripcion) as producto,
    ROUND(b.costo_promedio, 2) as promedio,
    ROUND(b.disponible) as disponible,
    a.descripcion as deposito,
    c.lote as lote,
    c.vencimiento as vencimiento
FROM 
    gest_depositos a 
INNER JOIN 
    gest_depositos_stock_gral b ON a.iddeposito = b.iddeposito
INNER JOIN
    gest_depositos_stock c on a.iddeposito = c.iddeposito
";

$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$resultados = [];

while (!$rs->EOF) {
    $producto = $rs->fields['producto'];
    $promedio = $rs->fields['promedio'];
    $disponible = $rs->fields['disponible'];
    $deposito = $rs->fields['deposito'];
    $lote = $rs->fields['lote'];
    $vencimiento = $rs->fields['vencimiento'];

    $fila = [
        'producto' => $producto,
        'promedio' => $promedio,
        'disponible' => $disponible,
        'deposito' => $deposito,
        'lote' => $lote,
        'vencimiento' => $vencimiento
    ];

    // Agregar el arreglo a la lista de resultados
    $resultados[] = $fila;

    $rs->MoveNext();
}

// Cerrar el conjunto de resultados
$rs->Close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

    <table>
        <tr>
            <th>Producto</th>
            <th>Promedio</th>
            <th>Disponible</th>
            <th>Deposito</th>
            <th>Lote</th>
            <th>Vencimiento</th>
        </tr>
        <?php foreach ($resultados as $fila): ?>
            <tr>
                <td><?php echo $fila['producto']; ?></td>
                <td><?php echo $fila['promedio']; ?></td>
                <td><?php echo $fila['disponible']; ?></td>
                <td><?php echo $fila['deposito']; ?></td>
                <td><?php echo $fila['lote']; ?></td>
                <td><?php echo $fila['vencimiento']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

</body>
</html>

