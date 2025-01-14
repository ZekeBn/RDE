 <?php
function generar_qr_pago($parametros_array)
{

    global $conexion;
    global $idusu;

    $max_propina = 450000;

    $monto = $parametros_array['monto'];
    $monto_propina = floatval($parametros_array['monto_propina']);
    $descripcion = $parametros_array['descripcion'];
    $idpostmp = antisqlinyeccion($parametros_array['idpostmp'], "int");
    $codigo_sucursal_bancard = intval($parametros_array['codigo_sucursal_bancard']);
    // si se envio como parametro el usuario omite la variable global
    if (intval($parametros_array['idusu']) > 0) {
        $idusu = intval($parametros_array['idusu']);
    }
    //$monto=1;

    $consulta = "
    select usuario, clave, prefijo_usu, ambiente, codigo_comercio from bancard_qr_preferencias
    ";
    $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $ambiente = $rspref->fields['ambiente'];
    $codigo_comercio = $rspref->fields['codigo_comercio'];

    if (intval($codigo_comercio) == 0) {
        echo "-No se completo el codigo de comercio (commerces).";
        exit;
    }
    if (intval($codigo_sucursal_bancard) == 0) {
        echo "-No se completo el codigo de sucursal de bancard (branches).";
        exit;
    }
    if (floatval($monto) == 0) {
        echo "-El monto enviado no puede ser cero.";
        exit;
    }
    if (trim($descripcion) == '') {
        echo "-La descripcion enviada no puede estar vacia.";
        exit;
    }
    if (intval($idpostmp) == 0) {
        echo "-El idpostmp enviado no puede ser cero.";
        exit;
    }
    if (trim($ambiente) != 'P' && trim($ambiente) != 'T') {
        echo "-La ambiente cargado no tiene un valor permitido.";
        exit;
    }
    if (intval($idusu) == 0) {
        echo "-El idusu enviado no puede ser cero.";
        exit;
    }
    if (floatval($monto_propina) > $max_propina) {
        $max_propina_txt = formatomoneda($max_propina);
        echo "-El monto maximo permitido por Bancard para propinas es de $max_propina_txt.";
        exit;
    }


    $credenciales = autorization_basic_bancard();
    $auth = $credenciales['auth']; // YXBwcy9uc256dnpOMldrV2lpUGF1d1JmcEhnbTZxSHRTWnU2WDp2QldXcm8obDlEaWlreWUpTnNnOFJORkUzYngrKW5WY282WEZIT3FI
    if ($credenciales['valido'] != 'S') {
        echo $credenciales['errores'];
        exit;
    }

    // produccion
    if ($ambiente == 'P') {
        $url_genera_qr = 'https://comercios.bancard.com.py/external-commerce/api/0.1/commerces/'.$codigo_comercio.'/branches/'.$codigo_sucursal_bancard.'/selling/generate-qr-express';
        // test
    } else {
        $url_genera_qr = 'https://desa.infonet.com.py:8035/external-commerce/api/0.1/commerces/'.$codigo_comercio.'/branches/'.$codigo_sucursal_bancard.'/selling/generate-qr-express';
    }





    /*$post_fields='{
        "amount": '.$monto.',
        "description": "'.$descripcion.'"
    }';*/

    // PARAMETROS BASICOS
    $data['amount'] = $monto;
    $data['description'] = $descripcion;

    // PARAMETROS ADICIONALES
    // si envio propina
    if ($monto_propina > 0) {
        // calculos
        $amount_buy = $monto - $monto_propina; // monto de la compra sin propina
        $amount_change = $monto_propina; // monto de la propina

        // agrega parametros
        //$data = json_decode($post_fields, true);
        // Agregar parametros adicionales
        $data['amount_buy'] = $amount_buy;
        $data['amount_change'] = $amount_change;
        $data['qr_source'] = 'QR-Change';
        // Convertir nuevamente a JSON
        //$post_fields = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    }
    // convertir parametros a Json
    $post_fields = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);


    $headers_fields = [
        'Authorization: Basic '.$auth,
        'Content-Type: application/json',

    ];
    //'Cookie: MYSESSION=!bIaMLyq+W+QX1dWZvTYyRuBfR76n0JIjeW5lPak0b8mcyh5sNtIoV4gOq3bbdhJUxB7Z3I1M8J62pw==; TS01bb64b7=01020cbaaf498341c16a51a78413cc716522446a76f4195977e3c9d198a4a4f968508a6055f5b6e2dea72699da5828aadc0f5ba6d8'
    $headers_json = json_encode($headers_fields, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    // LOG
    $ahora = date("Y-m-d H:i:s");
    $header_sql = antisqlinyeccion($headers_json, "textbox");
    $json_sql = antisqlinyeccion($post_fields, "textbox");
    $url_genera_qr_sql = antisqlinyeccion($url_genera_qr, "textbox");
    $ambiente_sql = antisqlinyeccion($ambiente, "textbox");
    $consulta = "
    insert into log_bancard_qr_pant
    (json_enviado, json_recibido, header_recibido, header_enviado, fechahora, idpostmp, hook_alias, servicio, url, ambiente)
    values
    ($json_sql, NULL, NULL, $header_sql, '$ahora', $idpostmp, NULL, 'GENERA_QR', $url_genera_qr_sql, $ambiente_sql)
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
    select idlog from log_bancard_qr_pant where fechahora = '$ahora' order by idlog desc limit 1
    ";
    $rslog = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idlog = intval($rslog->fields['idlog']);
    // LOG



    $curl = curl_init();

    curl_setopt_array($curl, [
      CURLOPT_URL => $url_genera_qr, // 'https://desa.infonet.com.py:8035/external-commerce/api/0.1/commerces/233225/branches/5/selling/generate-qr-express'
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => $post_fields,
      CURLOPT_HTTPHEADER => $headers_fields,
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE); // Obtener el código de respuesta HTTP
    $httpError = curl_error($curl); // Obtener el mensaje de error

    curl_close($curl);

    //$json = json_encode($data, JSON_PRETTY_PRINT);
    //$json = json_encode($response, JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    $json = $response;
    $res = [
        'respuesta' => $result,
        'http_code' => $httpCode,
        'http_error' => $httpError
    ];
    if (trim($response) == '') {
        $response = $res;
        $json = json_encode($response, JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    }

    $response_sql = antisqlinyeccion($json, "textbox");
    $respuesta = json_decode($json, true);
    $qr_hook_alias = antisqlinyeccion(substr($respuesta['qr_express']['hook_alias'], 0, 45), "textbox");

    $consulta = "
    UPDATE  log_bancard_qr_pant
    SET 
    json_recibido = $response_sql,
    hook_alias = $qr_hook_alias
    where
    idlog = $idlog
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    if (trim($respuesta['qr_express']['hook_alias']) != '') {
        $consulta = "
        INSERT INTO `bancard_qr`
        (`hook_alias`, `idestado_qr`, idlog, `registrado_por`, `registrado_el`, idpostmp) 
        VALUES 
        ($qr_hook_alias,1,$idlog,$idusu,'$ahora', $idpostmp)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }





    return $response;

}


function bancard_reversa($parametros_array)
{

    global $conexion;
    global $ahora;

    $valido = 'S';
    $errores = "";


    $idpostmp = intval($parametros_array['idpostmp']);
    $hook_alias = trim($parametros_array['hook_alias']);
    $idusu = intval($parametros_array['idusu']);
    $codigo_sucursal_bancard = intval($parametros_array['codigo_sucursal_bancard']);

    $hook_alias_sql = antisqlinyeccion($hook_alias, "textbox");

    $consulta = "
    select usuario, clave, prefijo_usu, ambiente, codigo_comercio from bancard_qr_preferencias
    ";
    $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $ambiente = $rspref->fields['ambiente'];
    $codigo_comercio = $rspref->fields['codigo_comercio'];



    if ($hook_alias == '') {
        $res = [
            'status' => 'error',
            'messages' => [
                                'dsc' => 'No se envio el hook_alias'
                        ],
        ];
        $errores = json_encode($res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        $valido = 'N';
    }
    if ($idpostmp == 0) {
        $res = [
            'status' => 'error',
            'messages' => [
                                'dsc' => 'No se envio el idpostmp'
                        ],
        ];
        $errores = json_encode($res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        $valido = 'N';
    }

    if ($valido == 'S') {


        // produccion
        if ($ambiente == 'P') {
            $url_reversa = 'https://comercios.bancard.com.py/external-commerce/api/0.1/commerces/'.$codigo_comercio.'/branches/'.$codigo_sucursal_bancard.'/selling/payments/revert/'.$hook_alias;
            // test
        } else {
            $url_reversa = 'https://desa.infonet.com.py:8035/external-commerce/api/0.1/commerces/'.$codigo_comercio.'/branches/'.$codigo_sucursal_bancard.'/selling/payments/revert/'.$hook_alias;
        }

        //$url_reversa=$rspref->fields['url_reversa'].$hook_alias; //'https://desa.infonet.com.py:8035/external-commerce/api/0.1/commerces/233225/branches/5/selling/payments/revert/'.$hook_alias;
        $url_reversa_sql = antisqlinyeccion($url_reversa, "textbox");

        // LOG
        $ambiente_sql = antisqlinyeccion($ambiente, "textbox");
        $consulta = "
        insert into log_bancard_qr_pant
        (json_enviado, json_recibido, fechahora, idpostmp, hook_alias, servicio, url, ambiente)
        values
        (NULL, NULL, '$ahora', $idpostmp, $hook_alias_sql, 'REVERSA', $url_reversa_sql, $ambiente_sql)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "
        select idlog from log_bancard_qr_pant where hook_alias = $hook_alias_sql order by idlog  desc limit 1
        ";
        $rslog = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idlog = intval($rslog->fields['idlog']);
        // LOG

        $credenciales = autorization_basic_bancard();
        $auth = $credenciales['auth']; // YXBwcy9uc256dnpOMldrV2lpUGF1d1JmcEhnbTZxSHRTWnU2WDp2QldXcm8obDlEaWlreWUpTnNnOFJORkUzYngrKW5WY282WEZIT3FI
        if ($credenciales['valido'] != 'S') {
            echo $credenciales['errores'];
            exit;
        }


        $consulta = "
        insert into confirmacion_pago_entidad_revert
        (idpostmp, hook_alias, registrado_el, registrado_por)
        values
        ($idpostmp, $hook_alias_sql, '$ahora', $idusu)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



        $curl = curl_init();

        curl_setopt_array($curl, [
        CURLOPT_URL => $url_reversa,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic '.$auth,
        ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        $response_sql = antisqlinyeccion($response, "textbox");
        $consulta = "
        UPDATE  log_bancard_qr_pant
        SET 
        json_recibido = $response_sql
        where
        idlog = $idlog
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    } else {
        $response = $errores;
    }


    $res = ['respuesta' => $response];
    return $res;
}
function autorization_basic_bancard()
{
    global $conexion;

    $consulta = "
    select usuario, clave, prefijo_usu from bancard_qr_preferencias
    ";
    $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $autorizacion = base64_encode($rspref->fields['prefijo_usu'].$rspref->fields['usuario'].':'.$rspref->fields['clave']);

    $valido = 'S';
    $errores = "";

    if (trim($rspref->fields['usuario']) == '') {
        $valido = 'N';
        $errores .= '- El campo Usuario de Bancard (Clave Publica) no fue completado.<br />';
    }
    if (trim($rspref->fields['clave']) == '') {
        $valido = 'N';
        $errores .= '- El campo Clave de Bancard (Clave Privada) no fue completado.<br />';
    }
    if (trim($rspref->fields['prefijo_usu']) == '') {
        $valido = 'N';
        $errores .= '- El campo prefijo_usu de Bancard no fue completado.<br />';
    }


    $respuesta = [
        'usuario' => $rspref->fields['usuario'],
        'clave' => $rspref->fields['clave'],
        'prefijo' => $rspref->fields['prefijo_usu'],
        'auth' => $autorizacion,
        'valido' => $valido,
        'errores' => $errores,
    ];

    return $respuesta;
}
function qr_tmp_monto_add($parametros_array)
{
    global $conexion;
    global $saltolinea;
    global $saltolinea;

    // validaciones basicas
    $valido = "S";
    $errores = "";



    $monto_abonar = antisqlinyeccion($parametros_array['monto_abonar'], "float");
    $monto_propina = antisqlinyeccion($parametros_array['monto_propina'], "float");
    //$total_abonar=antisqlinyeccion($parametros_array['total_abonar'],"float");
    $idatc = antisqlinyeccion($parametros_array['idatc'], "int");
    $idpulsera = antisqlinyeccion($parametros_array['idpulsera'], "int");
    $registrado_por = antisqlinyeccion($parametros_array['registrado_por'], "int");
    $registrado_el = antisqlinyeccion($parametros_array['registrado_el'], "text");
    $total_abonar = $monto_abonar + $monto_propina;


    if (floatval($parametros_array['monto_abonar']) <= 0) {
        $valido = "N";
        $errores .= " - El campo monto_abonar no puede ser cero o negativo.".$saltolinea;
    }
    /*if(floatval($parametros_array['monto_propina']) <= 0){
        $valido="N";
        $errores.=" - El campo monto_propina no puede ser cero o negativo.".$saltolinea;
    }*/
    /*if(intval($parametros_array['idatc']) == 0){
        $valido="N";
        $errores.=" - El campo idatc no puede ser cero o nulo.".$saltolinea;
    }*/


    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
        insert into qr_tmp_monto
        (monto_abonar, monto_propina, total_abonar, idatc, idpulsera, registrado_por, registrado_el)
        values
        ($monto_abonar, $monto_propina, $total_abonar, $idatc, $idpulsera, $registrado_por, $registrado_el)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // obtiene el insertado
        $consulta = "
        select idqrtmpmonto from qr_tmp_monto where registrado_por = $registrado_por order by idqrtmpmonto desc limit 1
        ";
        $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idqrtmpmonto = intval($rsmax->fields['idqrtmpmonto']);



    }


    return ["errores" => $errores,"valido" => $valido,"idqrtmpmonto" => $idqrtmpmonto];
}
?>
