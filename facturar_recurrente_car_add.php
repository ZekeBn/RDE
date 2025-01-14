 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "26";
$submodulo = "310";
require_once("includes/rsusuario.php");





//-------------------------------------------------------------------------------------------------
//************************************ AGREGAR DE A UNA CUENTA ************************************
//-------------------------------------------------------------------------------------------------
if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";


    // recibe parametros
    $iddetalle = antisqlinyeccion($_POST['iddetalle'], "int");
    $monto_abonar = antisqlinyeccion($_POST['monto_abonar'], "float");
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $estado = 1;



    if (intval($_POST['iddetalle']) == 0) {
        $valido = "N";
        $errores .= " - El campo iddetalle no puede ser cero o nulo.<br />";
    }
    if (floatval($_POST['monto_abonar']) <= 0) {
        $valido = "N";
        $errores .= " - El campo monto_abonar no puede ser cero o negativo.<br />";
    }


    // valida que no supere el saldo de la cuenta
    $consulta = "
    select *
    from detalle 
    inner join operacion on operacion.idoperacion = detalle.idoperacion
    where 
    detalle.estado = 1 
    and detalle.iddetalle = $iddetalle
    limit 1
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcliente = $rs->fields['idcliente'];
    if (floatval($_POST['monto_abonar']) > $rs->fields['saldo_cuota']) {
        $valido = "N";
        $errores .= " - El campo monto_abonar no puede superar el saldo de la cuota.<br />";
    }
    if (floatval($_POST['monto_abonar']) > $rs->fields['facturado_cuota_saldo']) {
        $valido = "N";
        $errores .= " - El campo monto_abonar no puede superar el saldo pendiente de facturar.<br />";
    }
    if ($rs->fields['saldo_cuota'] <= 0) {
        $valido = "N";
        $errores .= " - No se puede abonar una cuota cancelada.<br />";
    }
    if ($rs->fields['facturado_cuota_saldo'] <= 0) {
        $valido = "N";
        $errores .= " - No se puede abonar una deuda facturada completamente.<br />";
    }

    $consulta = "
    select idcarritofactutmp 
    from tmp_carrito_factu 
    where
    iddetalle = $iddetalle
    and registrado_por = $idusu
    and estado = 1 
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcarritofactutmp = intval($rs->fields['idcarritofactutmp']);
    if ($idcarritofactutmp > 0) {
        $valido = "N";
        $errores .= " - Ya agregaste esta cuenta, editala o agrega otra.<br />";
    }

    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
        insert into tmp_carrito_factu
        (iddetalle, idcliente,  monto_abonar, registrado_por, registrado_el, estado)
        values
        ($iddetalle, $idcliente, $monto_abonar, $registrado_por, $registrado_el, $estado)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



    }

    // genera array con los datos
    $arr = [
    'valido' => $valido,
    'errores' => $errores
    ];

    //print_r($arr);

    // convierte a formato json
    $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

    // devuelve la respuesta formateada
    echo $respuesta;


}
//-------------------------------------------------------------------------------------------------
//***************************************** AGREGAR TODAS *****************************************
//-------------------------------------------------------------------------------------------------
if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'TODAS') {

    // validaciones basicas
    $valido = "S";
    $errores = "";


    // recibe parametros
    $idcliente = antisqlinyeccion($_POST['idcliente'], "int");
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $estado = 1;



    if (intval($_POST['idcliente']) == 0) {
        $valido = "N";
        $errores .= " - El campo idcliente no puede ser cero o nulo.<br />";
    }



    // si todo es correcto inserta
    if ($valido == "S") {
        $findemes = date("Y-m-").ultimoDiaMes(date("m"), date("Y"));
        $proximomes = date("Y-m-d", strtotime($findemes."+ 1 days"));
        $findemes_proximomes = date("Y", strtotime($proximomes)).'-'.date("m", strtotime($proximomes)).'-'.ultimoDiaMes(date("m"), date("Y"));

        $consulta = "
        insert into tmp_carrito_factu
        (iddetalle, idcliente,  monto_abonar, registrado_por, registrado_el, estado)
        select detalle.iddetalle, operacion.idcliente, detalle.facturado_cuota_saldo, $idusu, '$ahora', 1
        from detalle
        inner join operacion on operacion.idoperacion = detalle.idoperacion
        where
        operacion.idcliente = $idcliente
        and detalle.facturado_cuota_saldo > 0
        and detalle.estado = 1
        and detalle.vencimiento <= '$findemes_proximomes'
        and iddetalle not in (
                        select iddetalle 
                        from tmp_carrito_factu 
                        where 
                        idcliente = $idcliente 
                        and registrado_por = $idusu 
                        and estado = 1 
                        )
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



    }

    // genera array con los datos
    $arr = [
    'valido' => $valido,
    'errores' => $errores
    ];

    //print_r($arr);

    // convierte a formato json
    $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

    // devuelve la respuesta formateada
    echo $respuesta;


}


?>
