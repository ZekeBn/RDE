<?php
/*--------------------------------------------
Insertando proveedor a la db
30/5/2023
---------------------------------------------*/
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_proveedor.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");

$valido = "S";
$error = "";

$agregar_proveedor = $_POST["agregar_proveedor"];
if ($agregar_proveedor == 1) {
    $idproveedor = select_max_id_suma_uno("proveedores", "idproveedor")["idproveedor"];
    $parametros_array = [
        'idproveedor' => $idproveedor,
        'ruc' => antisqlinyeccion($_POST['ruc'], "text"),
        'nombre' => antisqlinyeccion($_POST['nombre'], "text"),
        'fantasia' => antisqlinyeccion($_POST['fantasia'], "text"),
        'diasvence' => antisqlinyeccion(intval($_POST['diasvence']), "int"),
        'incrementa' => antisqlinyeccion($_POST['incrementa'], "text"),
        'acuerdo_comercial' => antisqlinyeccion(($_POST['acuerdo_comercial']), "text"),
        'acuerdo_comercial_coment' => antisqlinyeccion(($_POST['acuerdo_comercial_coment']), "text"),
        'telefono' => antisqlinyeccion(($_POST['telefono']), "text"),
        'email' => antisqlinyeccion(($_POST['email']), "email"),
        'idpais' => antisqlinyeccion(intval($_POST['idpais']), "int"),
        'idmoneda' => antisqlinyeccion(intval($_POST['idmoneda']), "int"),
        'agente_retencion' => antisqlinyeccion(($_POST['agente_retencion']), "text"),
        'idtipo_servicio' => antisqlinyeccion(($_POST['idtipo_servicio']), "int"),
        'idtipo_origen' => antisqlinyeccion(($_POST['idtipo_origen']), "int"),
        "idtipocompra" => antisqlinyeccion(($_POST['idtipocompra']), "int"),
        "cuenta_cte_mercaderia" => antisqlinyeccion(intval($_POST['cuenta_cte_mercaderia']), "text"),
        "cuenta_cte_deuda" => antisqlinyeccion(intval($_POST['cuenta_cte_deuda']), "text"),
        "idempresa" => $idempresa
    ];
    $res = validar_proveedor($parametros_array);
    if ($res['valido'] == 'N') {
        $valido = $res['valido'];
        $errores .= nl2br($res['errores']);
        $respuesta = [
            "success" => false,
            "errores" => $errores
        ];

    } else {
        $res = agregar_proveedor($parametros_array);
        // $idproveedor=$res['idproveedor'];
        $respuesta = [
            "success" => true,
            "idproveedor" => $res['idproveedor']
        ];
    }
    echo json_encode($respuesta);

}
