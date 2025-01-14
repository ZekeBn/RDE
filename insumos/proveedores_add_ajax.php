<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_proveedor.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "24";

require_once("../includes/rsusuario.php");
require_once("../proveedores/preferencias_proveedores.php");
// validaciones basicas
$valido = "S";
$errores = "";

// control de formularios, seguridad para evitar doble envio y ataques via bots


// recibe parametros
$idempresa = antisqlinyeccion(1, "int");
$ruc = antisqlinyeccion($_POST['ruc'], "text");
$nombre = antisqlinyeccion($_POST['nombre'], "text");
$fantasia = antisqlinyeccion($_POST['fantasia'], "text");
$direccion = antisqlinyeccion($_POST['direccion'], "text");
$sucursal = antisqlinyeccion(1, "float");
$comentarios = antisqlinyeccion($_POST['comentarios'], "text");
$web = antisqlinyeccion($_POST['web'], "text");
$telefono = antisqlinyeccion($_POST['telefono'], "text");
$estado = 1;
$email = antisqlinyeccion($_POST['email'], "text");
$contacto = antisqlinyeccion($_POST['contacto'], "text");
$area = antisqlinyeccion($_POST['area'], "text");
$email_conta = antisqlinyeccion($_POST['email_conta'], "text");
$borrable = antisqlinyeccion('S', "text");
$diasvence = antisqlinyeccion(intval($_POST['diasvence']), "int");

//TODO FALTA PREFERENCIA
$dias_entrega = antisqlinyeccion(intval($_POST['dias_entrega']), "int");

$incrementa = null;
if ($proveedores_sin_factura == "S") {

    $incrementa = antisqlinyeccion($_POST['incrementa'], "text");
} else {
    $incrementa = "'N'";
}
$acuerdo_comercial = antisqlinyeccion($_POST['acuerdo_comercial'], "text");
$archivo_acuerdo_comercial = $_FILES['archivo_acuerdo_comercial'];
$acuerdo_comercial_desde = antisqlinyeccion($_POST['ac_desde'], "date");
$acuerdo_comercial_hasta = antisqlinyeccion($_POST['ac_hasta'], "date");
$persona = antisqlinyeccion($_POST['persona'], "int");
// $acuerdo_comercial=str_replace("'","",$acuerdo_comercial);
$acuerdo_comercial_coment = antisqlinyeccion($_POST['acuerdo_comercial_coment'], "text");
$idpais = antisqlinyeccion(intval($_POST['idpais']), "int");
$idmoneda = antisqlinyeccion(intval($_POST['idmoneda']), "int");
$agente_retencion = antisqlinyeccion($_POST['agente_retencion'], "text");
$idtipo_servicio = antisqlinyeccion(intval($_POST['idtipo_servicio']), "int");
$idtipo_origen = antisqlinyeccion(intval($_POST['idtipo_origen']), "int");
$idtipocompra = antisqlinyeccion(intval($_POST['idtipocompra']), "int");
$cuenta_cte_mercaderia = antisqlinyeccion(intval($_POST['cuenta_cte_mercaderia']), "text");
$cuenta_cte_deuda = antisqlinyeccion(intval($_POST['cuenta_cte_deuda']), "text");
$registrado_por = $idusu;
$registrado_el = antisqlinyeccion($ahora, "text");
$idproveedor = select_max_id_suma_uno("proveedores", "idproveedor")["idproveedor"];
$parametros_array = [
    "idproveedor" => $idproveedor,
    "idempresa" => $idempresa,
    "ruc" => $ruc,
    "nombre" => $nombre,
    "fantasia" => $fantasia,
    "direccion" => $direccion,
    "sucursal" => $sucursal,
    "comentarios" => $comentarios,
    "web" => $web,
    "telefono" => $telefono,
    "estado" => $estado,
    "email" => $email,
    "contacto" => $contacto,
    "area" => $area,
    "email_conta" => $email_conta,
    "borrable" => $borrable,
    "diasvence" => $diasvence,
    "dias_entrega" => $dias_entrega,
    "incrementa" => $incrementa,
    "acuerdo_comercial" => $acuerdo_comercial,
    "acuerdo_comercial_coment" => $acuerdo_comercial_coment,
    "archivo_acuerdo_comercial" => $archivo_acuerdo_comercial,
    "acuerdo_comercial_desde" => $acuerdo_comercial_desde,
    "acuerdo_comercial_hasta" => $acuerdo_comercial_hasta,
    "persona" => $persona,
    "idpais" => $idpais, // ya esta
    "idmoneda" => $idmoneda,
    "agente_retencion" => $agente_retencion,
    "idtipo_servicio" => $idtipo_servicio,
    "idtipo_origen" => $idtipo_origen, //ya esta
    "idtipocompra" => $idtipocompra,
    "cuenta_cte_mercaderia" => $cuenta_cte_mercaderia,
    "cuenta_cte_deuda" => $cuenta_cte_deuda,
    "registrado_por" => $registrado_por,
    "registrado_el" => $registrado_el,
    "form_completo" => 1,
];
if ($archivo_acuerdo_comercial['name'] != "") {
    if (is_dir("../gfx/proveedores/acuerdos_comercial")) {

    } else {
        //creamos
        mkdir("../gfx/proveedores", "0777");
        mkdir("../gfx/proveedores/acuerdos_comercial", "0777");

    }
    $date_now = date("YmdHis");
    $extension_archivo = end(explode('.', $archivo_acuerdo_comercial['name']));
    $nombre_archivo = 'prv_'.$date_now.'.'.$extension_archivo;
    $dest_file = "../gfx/proveedores/acuerdos_comercial/$idproveedor/".$nombre_archivo;
    $directorio = "../gfx/proveedores/acuerdos_comercial/$idproveedor";
    $parametros_array["dest_file"] = $dest_file;
    $parametros_array["directorio"] = $directorio;
} else {
    $parametros_array["dest_file"] = null;
    $parametros_array["directorio"] = null;
}
$res = validar_proveedor($parametros_array);
// si todo es correcto inserta
if ($res["valido"] == "S" && $valido == "S") {


    $res = agregar_proveedor($parametros_array);//idproveedor



} else {
    $valido = "N";
    $errores = $res["errores"];
}
echo json_encode(["errores" => $errores,"valido" => $valido]);
