 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "196";
require_once("includes/rsusuario.php");


require_once("includes/funciones_mesas.php");



$encontrado = 'N';
$pago_valido = 'N';

$hook_alias = antisqlinyeccion($_POST['hook_alias'], "text");
$idpostmp = antisqlinyeccion($_POST['idpostmp'], "int");

// buscar si existe
$consulta = "
SELECT 
`id`, `hook_alias`, `status`, `response_code`, `response_description`, `amount`, `currency`,
`installment_number`, `description`, `date_time`, `ticket_number`, `authorization_code`,
`commerce_name`, `branch_name`, `account_type`, `card_last_numbers`, `bin`, `merchant_code`, `payer`
FROM `confirmacion_pago_entidad` 
WHERE
hook_alias = $hook_alias
order by id desc
limit 1
";
//echo $consulta;exit;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id = intval($rs->fields['id']);
$status = antixss($rs->fields['status']);
// encontro registro
if ($id > 0) {

    $consulta = "
    update confirmacion_pago_entidad
    set
    idpostmp = $idpostmp
    where
    id = $id
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $encontrado = 'S';
    $id = intval($rs->fields['id']);
    // si fallo el pago
    if ($status == 'failed') {
        $response_description = $rs->fields['response_description']; // "Insuficiencia de fondos"
        $response_code = $rs->fields['response_code']; // "51"
        $mensaje = "NO SE PUDO CONCRETAR EL PAGO: ".$response_description.' ['.$response_code.'].';
        // verificar si es confirmed
    } elseif ($status == 'confirmed') {
        $mensaje = "PAGO EXITOSO!";
        $pago_valido = 'S';
    } else {
        $mensaje = "Status no identificado: ".antixss($status);
        $encontrado = 'N';
    }



}
/*
$encontrado='S';
$mensaje='Insuficiencia de fondos';
$pago_valido='N';*/
// genera array con los datos
$arr = [
    'encontrado' => $encontrado,
    'pago_valido' => antixss($pago_valido), // failed
    'mensaje' => $mensaje,
    'idconfirmapago' => $id,
];

//print_r($arr);

// convierte a formato json
$respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// devuelve la respuesta formateada
echo $respuesta;





?>
