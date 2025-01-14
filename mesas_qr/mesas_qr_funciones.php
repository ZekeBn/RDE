<?php

function generarUUID20()
{
    $randomBytes = random_bytes(10); // Generar 10 bytes aleatorios

    // Aplicar una función hash para reducir su tamaño y convertirlo a una cadena legible
    $uuid = substr(hash('sha256', $randomBytes), 0, 20);

    return $uuid;
}
function buscar_mesa_atc($parametros_array)
{
    global $conexion;
    $id_mesa = antisqlinyeccion($parametros_array['id_mesa'], "int");
    $consulta = "
        SELECT idatc,pin FROM mesas_atc WHERE mesas_atc.idmesa = $id_mesa  and mesas_atc.estado = 1
    ";
    $rs_mesas_atc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    return [
        'idatc' => $rs_mesas_atc->fields['idatc'],
        'pin' => $rs_mesas_atc->fields['pin']
    ];
}
function verificar_atc($parametros_array)
{
    global $conexion;
    $whereadd = "";
    $id_mesa = antisqlinyeccion($parametros_array['id_mesa'], "int");
    $id_atc = antisqlinyeccion($parametros_array['id_atc'], "int");
    if ($id_mesa != "NULL") {
        $whereadd .= " and mesas_atc.idmesa = $id_mesa";
    }
    if ($id_atc != "NULL") {
        $whereadd .= " and mesas_atc.idatc = $id_atc";
    }
    $consulta = "
        SELECT idatc,pin FROM mesas_atc WHERE mesas_atc.estado = 1 $whereadd  
    ";
    $rs_mesas_atc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    return [
        'idatc' => $rs_mesas_atc->fields['idatc'],
        'pin' => $rs_mesas_atc->fields['pin']
    ];
}
function buscar_idmesa_atc($parametros_array)
{
    global $conexion;
    $idatc = antisqlinyeccion($parametros_array['idatc'], "int");
    $consulta = "
        SELECT idatc,pin,idmesa FROM mesas_atc WHERE mesas_atc.idatc = $idatc  and mesas_atc.estado = 1
    ";
    $rs_mesas_atc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    return [
        'idmesa' => $rs_mesas_atc->fields['idmesa'],
        'pin' => $rs_mesas_atc->fields['pin']
    ];
}
function buscar_tmp_venta_cab($parametros_array)
{
    global $conexion;
    $idatc = antisqlinyeccion($parametros_array['idatc'], "int");
    $consulta = "SELECT idtmpventares_cab FROM tmp_ventares_cab WHERE idatc = $idatc";
    $rs_user = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idpedido = $rs_user->fields['idtmpventares_cab'];
    return [
        'idpedido' => $idpedido
    ];
}
function verificar_token($parametros_array)
{
    global $conexion;
    $idmozo = antisqlinyeccion($parametros_array['idmozo'], "int");
    $token = antisqlinyeccion($parametros_array['token'], "text");
    $consulta = "SELECT UPPER(token) as token FROM usuarios_mozos WHERE usuarios_mozos.ssuni = $idmozo";
    $rs_user = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $token_user = $rs_user->fields['token'];
    $token = str_replace("'", "", $token);
    return [
        'success' => ($token_user == $token)
    ];
}

function update_mesa_atc_pin($parametros_array)
{
    global $conexion;
    $id_mesa = antisqlinyeccion($parametros_array['id_mesa'], "int");
    $idatc = antisqlinyeccion($parametros_array['idatc'], "int");
    $pin = antisqlinyeccion($parametros_array['pin'], "text");
    $consulta = "
        update mesas_atc
        set
        pin = $pin
        WHERE 
        mesas_atc.idmesa = $id_mesa  
        and mesas_atc.idatc = $idatc
    ";
    $rs_mesas_atc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    return [
        'idatc' => $rs_mesas_atc->fields['idatc'],
        'pin' => $rs_mesas_atc->fields['pin']
    ];
}

function verifiar_pedido($parametros_array)
{

    global $conexion;

    $idatc = antisqlinyeccion($parametros_array['idatc'], "int");
    $estado = antisqlinyeccion($parametros_array['estado'], "int");
    $tipo_pedido = antisqlinyeccion($parametros_array['tipo_pedido'], "int");
    $consulta = "
        SELECT idpedido from mesas_pedidos where idatc = $idatc and estado = $estado and tipo_pedido=$tipo_pedido
    ";
    $rs_mesas_atc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    return [
        'idpedido' => $rs_mesas_atc->fields['idpedido']
    ];
}
function verificar_pedidos_pendientes($parametros_array)
{
    global $conexion;


    $rs_mesas_atc = buscar_mesa_atc($parametros_array);
    $idatc = intval($rs_mesas_atc['idatc']);

    $parametros_array = [
    "idatc" => $idatc
    ];

    $idatc = antisqlinyeccion($parametros_array['idatc'], "int");
    $estado = 1;
    $tipo_pedido = 1;
    $consulta = "
        SELECT idpedido from mesas_pedidos where idatc = $idatc and estado = $estado and tipo_pedido=$tipo_pedido
    ";
    $rs_mesas_pedidos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tipo_pedido = 2;
    $consulta = "
        SELECT idpedido from mesas_pedidos where idatc = $idatc and estado = $estado and tipo_pedido=$tipo_pedido
    ";
    $rs_mesas_atc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    return [
        'idpedido_mesero' => $rs_mesas_pedidos->fields['idpedido'],
        'idpedido_cuenta' => $rs_mesas_atc->fields['idpedido']
    ];
}
function cancelar_pedido($parametros_array)
{
    global $conexion;
    $estado = antisqlinyeccion("6", "int");
    $idpedido = antisqlinyeccion($parametros_array['idpedido'], "int");
    $fecha = antisqlinyeccion($parametros_array['fecha'], "text");

    $consulta = "UPDATE
    mesas_pedidos 
    set
    estado = $estado,
    fechahora_cancelado  = $fecha
    where
    idpedido = $idpedido
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rs) {
        $data = ["success" => true ];
    } else {
        $data = ["success" => false,"error" => "Error: ocurrio un error inesperado." ];
    }
    return $data;
}
function confirmar_pedido($parametros_array)
{
    global $conexion;
    $estado = antisqlinyeccion("2", "int");
    $idpedido = antisqlinyeccion($parametros_array['idpedido'], "int");
    $idmozo = antisqlinyeccion($parametros_array['idmozo'], "int");
    $fecha = antisqlinyeccion($parametros_array['fecha'], "text");

    $consulta = "UPDATE
    mesas_pedidos 
    set
    estado = $estado,
    fechahora_atendido  = $fecha,
    idmozo = $idmozo
    where
    idpedido = $idpedido
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rs) {
        $data = ["success" => true ];
    } else {
        $data = ["success" => false,"error" => "Error: ocurrio un error inesperado." ];
    }
    return $data;
}
function agregar_pedido($parametros_array)
{
    global $conexion;
    $idatc = antisqlinyeccion($parametros_array['idatc'], "int");
    $estado = antisqlinyeccion($parametros_array['estado'], "int");
    $tipo_pedido = antisqlinyeccion($parametros_array['tipo_pedido'], "int");
    $idpedido = antisqlinyeccion($parametros_array['idpedido'], "int");
    $fecha = antisqlinyeccion($parametros_array['fecha'], "text");


    $consulta = "
    insert into mesas_pedidos
    (idpedido, idatc , fecha, estado, tipo_pedido)
    values
    ($idpedido, $idatc, $fecha,$estado, $tipo_pedido)
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rs) {
        $data = ["success" => true ];
    } else {
        $data = ["success" => false,"error" => "Error: ocurrio un error inesperado." ];
    }
    return $data;
}
