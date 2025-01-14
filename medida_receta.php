 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";

require_once("includes/rsusuario.php");

$id = intval($_POST['id']);
if (intval($id) == 0) {
    echo "Ingrediente inexistente!";
    exit;
}


$consulta = "
SELECT * 
FROM insumos_lista 
inner join medidas on medidas.id_medida = insumos_lista.idmedida
inner join ingredientes on insumos_lista.idinsumo = ingredientes.idinsumo
where
ingredientes.idingrediente = $id 
and insumos_lista.idempresa = $idempresa
and ingredientes.estado = 1
";
//echo $consulta;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
//echo $rs->fields['nombre'];
$response = [
    "medida" => $rs->fields['nombre'],
    "rendimiento_porc" => $rs->fields['rendimiento_porc']
];
echo json_encode($response);
?>
