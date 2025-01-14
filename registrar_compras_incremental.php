 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "209";
require_once("includes/rsusuario.php");



$idproveedor = intval($_POST['idproveedor']);

$consulta = "
select * 
from proveedores 
where 
idproveedor = $idproveedor
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$incremental = trim($rs->fields['incrementa']);

if ($incremental == 'S') {
    // actualiza el numero de factura
    $consulta = "
    update facturas_proveedores 
    set 
    fact_num = CAST(substring(factura_numero from 7 for 9) as UNSIGNED)
    where 
    fact_num is null
    and id_proveedor = $idproveedor
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // busca el mayor
    $consulta = "
    select fact_num 
    from facturas_proveedores 
    where 
    id_proveedor = $idproveedor
    order by fact_num desc
    limit 1
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $fact_num_new = $rs->fields['fact_num'] + 1;

    // genera array con los datos
    $arr = [
        'incremental' => 'S',
        'fact_suc' => '001',
        'fact_pexp' => '001',
        'fact_num' => agregacero($fact_num_new, 7),
    ];

} else {

    // busca datos de la ultima factura
    $consulta = "
    select vtotimbrado, timbrado , idtipocomprobante
    from facturas_proveedores 
    where 
    id_proveedor = $idproveedor
    order by id_factura desc
    limit 1
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // genera array con los datos
    $arr = [
        'incremental' => 'N',
        'timbrado' => $rs->fields['timbrado'],
        'vtotimbrado' => $rs->fields['vtotimbrado'],
        'idtipocomprobante' => $rs->fields['idtipocomprobante'],

    ];
}

//print_r($arr);

// convierte a formato json
$respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// devuelve la respuesta formateada
echo $respuesta;

?>
