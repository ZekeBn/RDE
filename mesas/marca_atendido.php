 <?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
require_once("../includes/rsusuario.php");


$idpedido = intval($_POST['idpedido']);
if ($idpedido == 0) {
    echo "No se indico el idpedido.";
    exit;
}

$consulta = "
SELECT 
    estado
FROM 
    mesas_pedidos 
WHERE 
    idpedido=$idpedido
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$estado = intval($rs->fields['estado']);
if ($estado == 1) {
    $consulta = "UPDATE mesas_pedidos set estado = 2, fechahora_atendido='$ahora' where idpedido=$idpedido
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rs) {
        $data = ["success" => true, "valido" => 'S' ];
    }
} else {
    $data = ["success" => false,"error" => "Error: El pedido con id: $idpedido ya fue atendido estado: $estado","valido" => 'N' ];
}

// Set the appropriate headers to indicate JSON content
//header('Content-Type: application/json');

// Encode the data array as JSON and output it
echo json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

?>
