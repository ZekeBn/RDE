<?php

require_once("../includes/conexion.php");

$consulta = "SELECT idinsumo FROM insumos_lista 
WHERE UPPER(insumos_lista.descripcion) LIKE '%DESCUENTO%' 
OR UPPER(insumos_lista.descripcion) LIKE '%AJUSTE%'";
$respuesta_insumos_no_aplica = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$ids_no_aplica = []; // Array para almacenar los IDs_no_aplica obtenidos

while (!$respuesta_insumos_no_aplica->EOF) {
    $ids_no_aplica[] = $respuesta_insumos_no_aplica->fields['idinsumo']; // Agregar el ID al array
    $respuesta_insumos_no_aplica->MoveNext();
}
var_dump($ids_no_aplica);

$consulta = "SELECT compras_detalles.*, insumos_lista.maneja_lote 
        FROM compras_detalles
        INNER JOIN insumos_lista ON insumos_lista.idinsumo = compras_detalles.codprod
        INNER JOIN compras ON compras.idcompra = compras_detalles.idcompra
        WHERE compras.idcompra = 6 
        AND compras_detalles.codprod NOT IN (
            SELECT idinsumo FROM insumos_lista 
            WHERE UPPER(insumos_lista.descripcion) LIKE \"%DESCUENTO%\" 
            OR UPPER(insumos_lista.descripcion) LIKE \"%AJUSTE%\"
        )";
// echo $consulta;
// exit;
$rs_detalles_compras = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$idp = $rs_detalles_compras->fields['id_producto'];

echo "idp= " . $idp;
if (!in_array($idp, $ids_no_aplica)) {
    echo "<br /> No esta";
}
