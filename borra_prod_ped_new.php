 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");


// RECIBE PARAMETROS

$idventatmp = intval($_POST['idventatmp']);
$idtmpventares_cab = intval($_POST['idtmpventares_cab']);
//$idmesa=intval($_POST['mesa']);
$idatc = trim($_POST['idatc']);
$idventatmp = trim($_POST['idventatmp']);
$rechazo = antisqlinyeccion(substr(trim($_POST['rechazo']), 0, 1), "text");
$idmotivorecha = antisqlinyeccion($_POST['idmotivorecha'], "int");
if ($idmotivorecha > 0) {
    $clasechar = "RECHAZO";
}
$idmotivo_elimina = antisqlinyeccion($_POST['idmotivo_elimina'], "int");
if ($idmotivo_elimina > 0) {
    $clasechar = "BORRAR";
}
$cc = substr($clasechar, 0, 2);
$idprod = intval($_POST['idprod']);

if (($idventatmp != '') && ($idatc != '')) {
    $idatc = '';
}

//Comprobar apertura de caja
$parametros_caja_new = [
    'idcajero' => $idusu,
    'idsucursal' => $idsucursal,
    'idtipocaja' => 1
];
$res_caja = caja_abierta_new($parametros_caja_new);
$idcaja = intval($res_caja['idcaja']);
if ($idcaja == 0) {
    echo "<br /><br />Debes tener una caja abierta para borrar un producto.<br /><br />";
    exit;
}

// parametros para la funcion
$parametros_array = [
    'idventatmp' => $idventatmp,
    'idprod' => $_POST['idprod'],
    'idtmpventares_cab' => $_POST['idtmpventares_cab'],
    'idatc' => $idatc,
    'rechazo' => $_POST['rechazo'],
    'idmotivorecha' => $idmotivorecha,
    'idmotivo_elimina' => $idmotivo_elimina,
    'cod' => $_POST['cod'],
    'idcaja' => $idcaja,
    'registrado_por' => $idusu, // cajero, si envio codigo la funcion reemplazara por el propietario del codigo
    'idsucursal' => $idsucursal,
];
//print_r($parametros_array);exit;
// valida los parametros
$res = validar_borrar_producto_pedido($parametros_array);
if ($res['valido'] != 'S') {
    echo nl2br($res['errores']);
    exit;
}

// si todo es valido
if ($res['valido'] == 'S') {
    // registra el borrado
    $res = registrar_borrar_producto_pedido($parametros_array);
    $usu_borra_cod = 0;
    //Registrar LOG
    if ($_POST['cod'] != '') {
        //buscamos el codigo
        $codigo = md5(trim($_POST['cod']));
        $consulta = " select *
        from codigos_borraped 
        where 
        codigo = '$codigo'
        and estado = 1
        and registrado_por in (select idusu from usuarios where estado = 1)
        limit 1
        ";
        $rscod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if (intval($rscod->fields['idusuario']) > 0) {
            $usu_borra_cod = intval($rscod->fields['idusuario']);
        }


    }
    //convertimos el atc a numero
    $idatc = intval($idatc);
    $idventatmp = intval($idventatmp);
    $idtmpventares_cab = intval($idtmpventares_cab);
    $insertar = "Insert into mesas_acciones_log (fechahora,id_usuario,id_usu_pin,accion,clase,idatc,idmesa,porcen,monto,idtmpventares,idtmpventares_cab) values ('$ahora',$idusu,$usu_borra_cod,'$clasechar - IDTMP:$idventatmp ',' $cc',$idatc,0,0,0,$idventatmp,$idtmpventares_cab)";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));



    // si hay mensaje muestra
    echo antixss($res['mensaje']);

    // si hay codigo de accion para el front-end muestra la respuesta esperada para cada codigo de accion
    if ($codigo_accion == 1) {
        echo 'OK';
    }
    if ($codigo_accion == 2) {
        echo '';
    }

}


?>
