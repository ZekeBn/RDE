 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

require_once("includes/funciones_bancard_qr.php");

$idpostmp = intval($_REQUEST['idpostmp']);
$hook_alias = trim($_REQUEST['hook_alias']);
$forzar_reversa = trim($_REQUEST['forzar_reversa']); // opcional enviar: S

$consulta = "
select codigo_sucursal_bancard from sucursales where idsucu = $idsucursal
";
$rssuc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$codigo_sucursal_bancard = $rssuc->fields['codigo_sucursal_bancard'];


if ($forzar_reversa == 'S') {
    // llamar a la funcion de reversa
    $parametros_array = [
        'idpostmp' => $idpostmp,
        'hook_alias' => $hook_alias,
        'idusu' => $idusu,
        'codigo_sucursal_bancard' => $codigo_sucursal_bancard,
    ];
    $res = bancard_reversa($parametros_array);
    echo $res['respuesta'];
} else {
    $hook_alias_sql = antisqlinyeccion($hook_alias, 'textbox');
    // busca si hay un pago en la BD
    $consulta = "
    SELECT idbancardqr FROM `bancard_qr` where idestado_qr in (3,5) and hook_alias = $hook_alias_sql limit 1
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idbancardqr = intval($rs->fields['idbancardqr']);
    // si hay llama a la reversa
    if ($idbancardqr > 0) {
        // llamar a la funcion de reversa
        $parametros_array = [
            'idpostmp' => $idpostmp,
            'hook_alias' => $hook_alias,
            'idusu' => $idusu,
            'codigo_sucursal_bancard' => $codigo_sucursal_bancard,
        ];
        $res = bancard_reversa($parametros_array);
        echo $res['respuesta'];
        // si no hay pago no envia la reversa a bancard
    } else {

        echo '
        {
            "status": "success",
                "reverse": {
                    "status": "success",
                    "response_code": "00",
                    "response_description": "No es necesario reversa, pago no encontrado en el sistema, solicitud de reversa no enviada a bancard."
                }
        }
        ';

    }



} // //if($forzar_reversa == 'S'){

?>
