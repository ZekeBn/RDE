<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");
require_once("../includes/funciones_iva.php");
require_once("../includes/funciones_compras.php");
require_once("../insumos/preferencias_insumos_listas.php");



//buscando moneda nacional
$consulta = "SELECT tipo_moneda.idtipo, tipo_moneda.descripcion as nombre FROM tipo_moneda WHERE nacional='S'";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_nacional = $rs_guarani->fields["idtipo"];
$nombre_moneda_nacional = $rs_guarani->fields["nombre"];

//Recibir valores por POST
$agregar = antisqlinyeccion(intval($_POST['agregar']), "int");
$idtransaccion = antisqlinyeccion(intval($idtransaccion), "int");
$eliminar = antisqlinyeccion(intval($_POST['idunico']), "int");
$editar = antisqlinyeccion(intval($_POST['editar']), "int");
$ajustar = antisqlinyeccion(intval($_POST['ajustar']), "int");
$update_cabecera = antisqlinyeccion(intval($_POST['update_cabecera']), "int");
$vencimientos_compra = antisqlinyeccion(intval($_POST['vencimientos_compra']), "int");
$vencimientos_compra_editar = antisqlinyeccion(intval($_POST['vencimientos_compra_editar']), "int");
$vencimientos_compra_borrar = antisqlinyeccion(intval($_POST['vencimientos_compra_borrar']), "int");
$descuento = antisqlinyeccion(intval($_POST['descuento']), "int");
//Preferencias
$buscar = "select * from preferencias where idempresa=$idempresa";
$rstc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$usa_cta_int = trim($rstc->fields['usa_cta_interna']);
//Preferencias compras
$buscar = "Select * from preferencias_compras limit 1";
$rsprefecompras = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$usar_descuentos_compras = trim($rsprefecompras->fields['usar_descuentos_compras']);
//Validaciones
if ($idtransaccion == 0) {
    $idtransaccion = intval($_POST['idtransaccion']);
}
if ($idtransaccion == 0) {
    echo "Error al obtener la transaccion";
    exit;

}
//Borrar temporal cargado


if ($agregar > 0) {

    $vencimiento = antisqlinyeccion(($_POST['vencimiento']), "date");
    $deposito = antisqlinyeccion(intval($_POST['iddeposito']), "int");

    if ($deposito == 0) {
        $deposito = verificar_deposito_insumo([
            'idinsumo' => antisqlinyeccion(intval($_POST['insumo']), "int")
        ])['iddeposito'];
    }

    $parametros_array = [
        'idinsumo' => antisqlinyeccion(intval($_POST['insumo']), "int"),
        'idmedida' => antisqlinyeccion(intval($_POST['idmedida']), "int"),
        'tipo_medida' => antisqlinyeccion(intval($_POST['tipo_medida']), "int"),
        'cantidad' => antisqlinyeccion(floatval($_POST['cantidad']), "float"),
        'cantidad_ref' => antisqlinyeccion(floatval($_POST['cantidad_ref']), "float"),
        'costo_unitario' => antisqlinyeccion(floatval($_POST['precio_compra']), "float"),
        'idtransaccion' => $idtransaccion,
        'lote' => antisqlinyeccion(($_POST['lote']), "text"),
        'iddeposito' => $deposito,
        'vencimiento' => $vencimiento
    ];
    // var_dump($parametros_array);exit;

    //buscando moneda nacional
    $whereadd = "";
    if ($parametros_array['lote'] == "" || $parametros_array['lote'] == "NULL") {
        $whereadd .= " and lote is  NULL";
    } else {
        $lote = $parametros_array['lote'];
        $whereadd .= " and lote = $lote";
    }
    if ($parametros_array['vencimiento'] == "" || $parametros_array['vencimiento'] == "NULL") {
        $whereadd .= " and vencimiento is  NULL";
    } else {
        $vencimiento = $parametros_array['vencimiento'];
        $whereadd .= " and vencimiento = $vencimiento";
    }

    $costo = $parametros_array['costo_unitario'];
    $idinsumo = $parametros_array['idinsumo'];
    $consulta = "SELECT idregcc FROM tmpcompradeta where costo=$costo and idprod=$idinsumo $whereadd  ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idregcc = intval($rs->fields["idregcc"]);
    $respuesta = "";
    if ($idregcc > 0) {
        $respuesta = [
            "success" => true,
            "idregcc" => $idregcc
        ];
    } else {
        $respuesta = [
            "success" => false

        ];
    }

    echo json_encode($respuesta);



}

if ($editar > 0) {

    $vencimiento = antisqlinyeccion(($_POST['vencimiento']), "date");
    $deposito = antisqlinyeccion(intval($_POST['iddeposito']), "int");

    if ($deposito == 0) {
        $deposito = verificar_deposito_insumo([
            'idinsumo' => antisqlinyeccion(intval($_POST['insumo']), "int")
        ])['iddeposito'];
    }
    $idregcc = antisqlinyeccion(intval($_POST['idregcc']), "int");
    $parametros_array = [
        'idinsumo' => antisqlinyeccion(intval($_POST['insumo']), "int"),
        'idmedida' => antisqlinyeccion(intval($_POST['idmedida']), "int"),
        'tipo_medida' => antisqlinyeccion(intval($_POST['tipo_medida']), "int"),
        'cantidad' => antisqlinyeccion(floatval($_POST['cantidad']), "float"),
        'cantidad_ref' => antisqlinyeccion(floatval($_POST['cantidad_ref']), "float"),
        'costo_unitario' => antisqlinyeccion(floatval($_POST['costo_unitario']), "float"),
        'idtransaccion' => $idtransaccion,
        'lote' => antisqlinyeccion(($_POST['lote']), "text"),
        'iddeposito' => $deposito,
        'vencimiento' => $vencimiento
    ];
    // var_dump($parametros_array);exit;

    //buscando moneda nacional
    $whereadd = "";
    if ($parametros_array['lote'] == "" || $parametros_array['lote'] == "NULL") {
        $whereadd .= " and lote is  NULL";
    } else {
        $lote = $parametros_array['lote'];
        $whereadd .= " and lote = $lote";
    }
    if ($parametros_array['vencimiento'] == "" || $parametros_array['vencimiento'] == "NULL") {
        $whereadd .= " and vencimiento is  NULL";
    } else {
        $vencimiento = $parametros_array['vencimiento'];
        $whereadd .= " and vencimiento = $vencimiento";
    }
    $costo = $parametros_array['costo_unitario'];
    $idinsumo = $parametros_array['idinsumo'];
    $consulta = "SELECT idregcc FROM tmpcompradeta where costo=$costo $whereadd and idprod=$idinsumo and idregcc != $idregcc  ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idregcc_respuesta = intval($rs->fields["idregcc"]);
    $respuesta = "";
    if ($idregcc_respuesta > 0) {
        $respuesta = [
            "success" => true,
            "idregcc" => $idregcc
        ];
    } else {
        $respuesta = [
            "success" => false

        ];
    }
    echo json_encode($respuesta);



}
