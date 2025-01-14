<?php

/*-----------------------------PARA USAR CON TIPO DE VENTA SUPERMERCADOS-----------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");


//print_r($_POST);exit;
$n = intval($_POST['n']);
if ($n == 1) {
    //registrar
    $valido = 'S';
    $errores = '';
    $tc = intval($_POST['tc']);

    $nombres = antisqlinyeccion(substr($_POST['nom'], 0, 45), 'text');
    $apellidos = antisqlinyeccion(substr($_POST['ape'], 0, 45), 'text');
    if ($tc == 1) {
        $razon = substr(str_replace("'", "", $nombres).' '.str_replace("'", "", $apellidos), 0, 45);
    } else {
        $razon = antisqlinyeccion(substr($_POST['rz1'], 0, 45), 'text');
        $razon = str_replace("'", "", $razon);
    }
    $dc = intval($_POST['dc']);
    $ruc = antisqlinyeccion($_POST['ruc'], 'text');
    $direclie = antisqlinyeccion($_POST['dire'], 'text');
    $mailcliente = antisqlinyeccion($_POST['mailcliente'], 'text');
    $telfo = intval($_POST['telfo']);
    $carnet_diplomatico = "NULL";
    $diplomatico = antisqlinyeccion($_POST['ruc_especial'], 'text');
    if ($_POST['ruc_especial'] != 'S' && $_POST['ruc_especial'] != 'N') {
        $diplomatico = "'N'";
    }
    if ($_POST['ruc_especial'] == 'S') {
        $carnet_diplomatico = antisqlinyeccion($_POST['ruc'], 'text');
    }

    // validar digito verificador del ruc
    $rucar = strtoupper(trim($_POST['ruc']));
    $ruc_array = explode("-", $rucar);
    $ruc_pri = $ruc_array[0];
    $ruc_dv = $ruc_array[1];


    // busca el ruc de hacienda
    $consulta = "
	select idcliente, ruc from cliente where borrable = 'N' limit 1
	";
    $rsruc_pred = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $ruc_pred = trim($rsruc_pred->fields['ruc']);
    $idcliehac = intval($rsex->fields['idcliente']);


    /*echo $ruc_pred;
    echo $rucar;
    exit;*/
    // si no es el ruc de hacienda
    if ($ruc_pred != $rucar) {
        // busca si ya esta registrado este ruc
        $consulta = "
		select * from cliente where estado <> 6 and ruc = $ruc limit 1
		";
        $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idclieex = intval($rsex->fields['idcliente']);
        // si envio documento
        if ($dc > 0) {
            // busca si hay otro cliente con este documento pero ruc diferente
            $consulta = "
			select * from cliente where estado <> 6 and documento = $dc and ruc <> $ruc limit 1
			";
            $rsexdoc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idclieexdoc = intval($rsexdoc->fields['idcliente']);
            if ($idclieexdoc > 0) {
                $valido = "N";
                $errores .= "- Ya existe otro cliente con la cedula indicada.".$saltolinea;
                $idclieex = ""; // vacia para no devolver nada y tirar error
            }
        }


        // si ya existe el cliente devuelve el id
        if ($idclieex > 0) {
            $arr = [
            'valido' => $valido,
            'errores' => $errores,
            'idcliente' => $idclieex,
            ];
            // convierte a formato json
            $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

            // devuelve la respuesta formateada
            echo $respuesta;
            exit;
        }

        // si es el ruc de hacienda
    } else {

        // si envio documento
        if ($dc > 0) {
            $consulta = "
			select * from cliente where estado <> 6 and documento = $dc limit 1
			";
            $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idclieex = intval($rsex->fields['idcliente']);
            if ($idclieex > 0) {
                $valido = "N";
                $errores .= "- Ya existe otro cliente con la cedula indicada.".$saltolinea;
            }
            // si no mando documento y es el ruc de hacienda no se puede registrar
        } else {
            // devuelve el id de hacienda
            /*if($idcliehac > 0){
                $arr = array(
                'valido' => 'S',
                'errores' => '',
                'idcliente' => $idcliehac,
                );
                // convierte a formato json
                $respuesta=json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

                // devuelve la respuesta formateada
                echo $respuesta; exit;*/
            // por seguridad
            //}else{
            $valido = "N";
            $errores .= "- No puedes registrar este ruc sin un documento.".$saltolinea;
            $arr = [
            'valido' => 'N',
            'errores' => $errores,
            'idcliente' => $idcliehac,
            ];
            // convierte a formato json
            $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

            // devuelve la respuesta formateada
            echo $respuesta;
            exit;
            //}
        }
    }
    /*if($_POST['ruc_especial'] != 'S'){
        if($ruc_pri <= 0){
            $errores.="- El ruc no puede ser cero o menor.".$saltolinea;
            $valido="N";
        }
        if(strlen($ruc_dv) < 1){
            $errores.="- No se indico el digito verificador del ruc.".$saltolinea;
            $valido="N";
        }
        if(strlen($ruc_dv) > 1){
            $errores.="- El digito verificador del ruc no puede tener mas de 1 digito.".$saltolinea;
            $valido="N";
        }
        if(calcular_ruc($ruc_pri) <> $ruc_dv){
            $digitocor=calcular_ruc($ruc_pri);
            $errores.="- El digito verificador del ruc no corresponde a la cedula el digito debia ser $digitocor para la cedula $ruc_pri.".$saltolinea;
            $valido="N";
        }
    }*/


    // validaciones persona fisica
    /*if ($tc==1){

    // validaciones persona juridica
    }else{

    }*/

    // si no es valido
    /*if($valido == 'N'){
        $arr = array(
        'valido' => 'N',
        'errores' => $errores,
        );
        // convierte a formato json
        $respuesta=json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

        // devuelve la respuesta formateada
        echo $respuesta; exit;
    }*/
    // tipo cliente
    if ($tc == 1) {
        $razon = substr(str_replace("'", "", $nombres).' '.str_replace("'", "", $apellidos), 0, 45);
    } else {
        $razon = antisqlinyeccion(substr($_POST['rz1'], 0, 45), 'text');
        $razon = str_replace("'", "", $razon);
    }
    $parametros_array = [
        'idclientetipo' => $_POST['tc'],
        'ruc' => $_POST['ruc'],
        'razon_social' => $razon,
        'documento' => $_POST['dc'],
        'fantasia' => $_POST['fantasia'],
        'nombre' => $_POST['nom'],
        'apellido' => $_POST['ape'],
        'idvendedor' => '',
        'sexo' => '',
        'nombre_corto' => $_POST['nombre_corto'],
        'idtipdoc' => $_POST['idtipdoc'],


        'telefono' => $_POST['telfo'],
        'celular' => $_POST['celular'],
        'email' => $_POST['mailcliente'],
        'direccion' => $_POST['dire'],
        'comentario' => $_POST['comentario'],
        'fechanac' => $_POST['fechanac'],

        'ruc_especial' => $_POST['ruc_especial'],
        'idsucursal' => $idsucursal,
        'idusu' => $idusu,


    ];

    //echo $errores.'-';exit;
    $res = validar_cliente($parametros_array);
    if ($res['valido'] != 'S') {
        $valido = $res['valido'];
        $errores = nl2br($res['errores']);
        //echo $errores;exit;
    }
    //echo $errores.'-';exit;
    // si todo es valido
    if ($valido == 'S') {

        //busca el proximo id
        /*$buscar="select max(idcliente) as mayor from cliente";
        $rsmay=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
        $mayor=intval($rsmay->fields['mayor'])+1;
        // inserta el cliente
        $insertar="Insert into cliente
        (idcliente,idempresa,nombre,apellido,ruc,documento,direccion,celular,razon_social,diplomatico,carnet_diplomatico)
        values
        ($mayor,$idempresa,$nombres,$apellidos,$ruc,$dc,$direclie,$telfo,'$razon',$diplomatico,$carnet_diplomatico)";
        $conexion->Execute($insertar) or die(errorpg($conexion,$insertar));

        // busca el id insertado
        $buscar="Select idcliente from cliente where idcliente=$mayor limit 1";
        $rscli=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
        //$tcli=$rscli->RecordCount();
        //echo $rscli->fields['idcliente'];

        $arr = array(
        'valido' => 'S',
        'errores' => $errores,
        'idcliente' => $rscli->fields['idcliente'],
        );*/
        $res = registrar_cliente($parametros_array);
        $idcliente = $res['idcliente'];

        // convierte a formato json
        $respuesta = json_encode($res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

        // devuelve la respuesta formateada
        echo $respuesta;
        exit;
    }
    if ($valido == 'N') {
        // convierte a formato json
        $respuesta = json_encode($res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

        // devuelve la respuesta formateada
        echo $respuesta;
        exit;
    }

}
