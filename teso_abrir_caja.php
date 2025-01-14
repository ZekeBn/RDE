 <?php
//10/09/2021
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "446";
require_once("includes/rsusuario.php");


$buscar = "Select * from preferencias_caja limit 1";
$rscajp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$itk = trim($rscajp->fields['imprime_tk_pagosxcaja_teso']);

//Verificamos si hay una caja abierta por este usuario
$buscar = "
Select * 
from caja_super 
where 
estado_caja=1 
and cajero=$idusu and tipocaja=2
order by fecha desc 
limit 1
";
$rscaj = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaj->fields['idcaja']);
if ($idcaja == 0) {
    //No existe un registro para la caja, por lo cual
    $fechahoy = date("Y-m-d");
    if (isset($_POST['fecha']) && ($_POST['fecha'] != '')) {
        $fecha = antisqlinyeccion($_POST['fecha'], 'date');
        $fechahoy = str_replace("'", "", $fecha);
    }
}
/*-----------------------------Preferencias y tipo de impresoras-----------------------------------*/
$consulta = "SELECT * FROM preferencias WHERE  idempresa = $idempresa ";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$obligaprov = trim($rspref->fields['obligaprov']);
$impresor = trim($rspref->fields['script_ticket']);
$hab_monto_fijo_chica = trim($rspref->fields['hab_monto_fijo_chica']);
$hab_monto_fijo_recau = trim($rspref->fields['hab_monto_fijo_recau']);
$muestraventasciega = trim($rspref->fields['muestra_ventas_ciega']);
$usacajachica = trim($rspref->fields['usa_cajachica']);
$pagoxcajarec = trim($rspref->fields['pagoxcaja_rec']);
$pagoxcajachica = trim($rspref->fields['pagoxcaja_chic']);


$script_impresora = $impresor;
$impresor = strtolower($impresor);
if ($impresor == '') {
    $impresor = 'http://localhost/impresorweb/ladocliente.php';
}
if ($hab_monto_fijo_chica == 'S' or $hab_monto_fijo_recau == 'S') {
    // montos de caja fijos
    $consulta = "
    SELECT *
    FROM usuarios
    where
    estado = 1
    and idempresa = $idempresa
    and usuarios.idusu = $idusu
    ";
    $rsus = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $monto_fijo_chica = intval($rsus->fields['monto_fijo_chica']);
    $monto_fijo_recau = intval($rsus->fields['monto_fijo_recau']);
}
$buscar = "Select * from impresoratk where idsucursal=$idsucursal and idempresa=$idempresa limit 1";
$rsprint = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tipoimpre = trim($rsprint->fields['tipo_impresora']);
if ($tipoimpre == 'COC') {
    $enlace = 'impresora_selecciona.php';
} else {
    $enlace = 'impresora_selecciona_caja.php';
}
/*---------------------------------------------------------------------------------------------------*/



/*--------------------------------------------------POSTS---------------------------------------------*/
if (isset($_POST['occierrecaja'])) {
    $origen = 2;
    $vcontrol = intval($_POST['occierrecaja']);
    if ($vcontrol > 0) {
        //echo 'llega';exit;
        require_once("caja_cerrar_nuevo.php");
    }

}
//Deliverys no rendidos, aun algunos deben usar
$idpago = intval($_REQUEST['idpago']);
if ($idpago > 0) {

    $ahora = date("Y-m-d H:i:s");


    $consulta = "
        update gest_pagos
        set
            rendido='S',
            fec_rendido='$ahora'
        where
            cajero=$idusu  
            and estado=1 
            and idcaja=$idcaja 
            and rendido ='N'
            and idempresa = $idempresa
            and idpago = $idpago
        ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


}





if (isset($_POST['montoaper']) && intval($_POST['montoaper']) >= 0) {
    require_once("caja_abrir_tesoreria.php");
}
//Entrega de valores (sale plata de la caja)
$operacion = intval($_POST['cual']);
if ($operacion == 1) {
    $idcaja = floatval($_POST['ocidcaja']);
    $dp = floatval($_POST['md']);
    $codigo = md5($_POST['codigoau']);
    $codigo = antisqlinyeccion($codigo, 'clave');
    $obs = antisqlinyeccion($_POST['obs'], 'text');
    $montoentrega = floatval($_POST['montogs']);
    $copias = intval($_POST['canticopias']);
    if ($copias == 0) {
        $copias = 1;
    }


    //print_r($_POST);exit;
    $buscar = "Select * from usuarios_autorizaciones where codauto=$codigo and estado=1";
    $rscod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $autorizaid = intval($rscod->fields['idusu']);
    $imprimetk = trim($rscod->fields['imprimetk']);
    if ($autorizaid > 0) {
        //esta autorizado, le metemos a comparar montos
        //if ($montoentrega > $dp){
        //$errorautoriza="Monto ingresado supera al disponible. No se registra salida de dinero."    ;
        //} else {

        $consulta = "
                insert into gest_pagos
                (idcaja, fecha, medio_pago, total_cobrado,  estado, tipo_pago, idempresa, sucursal, cajero, fechareal, idventa, 
                idtipocajamov,tipomovdinero)
                values
                ($idcaja, '$ahora', 1, $montoentrega, 1, 0, 1, $idsucursal, $idusu, '$ahora', 0, 
                8,'S'
                )
                ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
                select idpago from gest_pagos where idtipocajamov = 8 order by idpago desc limit 1
                ";
        $rsultpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idpago = $rsultpag->fields['idpago'];

        $consulta = "
                INSERT INTO gest_pagos_det
                (idpago, monto_pago_det, idformapago) 
                VALUES 
                ($idpago, $montoentrega, 1)
                ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        //registramos
        $insertar = "Insert into caja_retiros 
                (idcaja,cajero,fecha_retiro,monto_retirado,retirado_por,codigo_autorizacion,estado,obs,idempresa,idsucursal,idpago)
                values
                ($idcaja,$idusu,current_timestamp,$montoentrega,$autorizaid,$codigo,1,$obs,$idempresa,$idsucursal,$idpago)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        $buscar = "Select *,usuario from caja_retiros 
                inner join usuarios on usuarios.idusu=retirado_por 
                where cajero=$idusu order by fecha_retiro desc";
        $rsfr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idret = intval($rsfr->fields['regserialretira']);
        $totalret = intval($rsfr->fields['monto_retirado']);
        $quien = $rsfr->fields['usuario'];
        $obs = $rsfr->fields['obs'];
        if ($imprimetk == 'S') {
            $retirado = 1;
            //Preparamos el tickete
            // centrar nombre empresa
            $nombreempresa_centrado = corta_nombreempresa($nombreempresa);
            $ahorta = date("d-m-Y H:i:s", strtotime($ahora));
            $cajero1 = strtoupper($cajero);
            $texto = "
****************************************
$nombreempresa_centrado
            RETIRO DE VALORES
****************************************
RETIRO ID $idret 
----------------------------------------
FECHA Retiro    : $ahorta
Autorizado por  : $quien
Entregado por   : $cajero1
Monto Retirado  :".formatomoneda($totalret)."
----------------------------------------


Firma Entregado:


Firma Recibido:


$obs
----------------------------------------
";
        } else {
            //No se imprime tickete entrega
            $retirado = 0;
        }





        //}

    } else {
        $errorautoriza = "C&oacute;digo de autorizaci&oacute;n inv&aacute;lido. No se registra salida de dinero."    ;

    }
}
//Recepcionar dinero
if ($operacion == 2) {

    $idcaja = floatval($_POST['ocidcaja']);
    $dp = floatval($_POST['md']);
    $codigo = md5($_POST['codigoau']);
    $codigo = antisqlinyeccion($codigo, 'clave');
    //$codigo=antisqlinyeccion($_POST[''],'text');
    $obs = antisqlinyeccion($_POST['obs'], 'text');
    $montorecibe = floatval($_POST['montogs']);
    $copias = intval($_POST['canticopias']);
    if ($copias == 0) {
        $copias = 1;
    }

    $buscar = "Select * from usuarios_autorizaciones where codauto=$codigo";
    $rscod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $autorizaid = intval($rscod->fields['idusu']);
    $imprimetk = trim($rscod->fields['imprimetk']);
    if ($autorizaid > 0) {

        $consulta = "
            insert into gest_pagos
            (idcaja, fecha, medio_pago, total_cobrado,  estado, tipo_pago, idempresa, sucursal, cajero, fechareal, idventa, 
            idtipocajamov,tipomovdinero)
            values
            ($idcaja, '$ahora', 1, $montorecibe, 1, 0, 1, $idsucursal, $idusu, '$ahora', 0, 
            7,'E'
            )
            ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
            select idpago from gest_pagos where idtipocajamov = 7 order by idpago desc limit 1
            ";
        $rsultpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idpago = $rsultpag->fields['idpago'];

        $consulta = "
            INSERT INTO gest_pagos_det
            (idpago, monto_pago_det, idformapago) 
            VALUES 
            ($idpago, $montorecibe, 1)
            ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        //registramos
        $insertar = "Insert into caja_reposiciones 
            (idcaja,cajero,fecha_reposicion,monto_recibido,entregado_por,codigo_autorizacion,estado,obs,idempresa,idsucursal,idpago)
            values
            ($idcaja,$idusu,current_timestamp,$montorecibe,$autorizaid,$codigo,1,$obs,$idempresa,$idsucursal,$idpago)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

        $buscar = "
SELECT *,
(select usuario from usuarios where idusu = caja_reposiciones.cajero) as cajero,
(select usuario from usuarios where idusu = caja_reposiciones.entregado_por) as quienllevo,
(select sucursales.nombre from sucursales where idsucu = caja_reposiciones.idsucursal) as sucursal
FROM caja_reposiciones
where
cajero=$idusu and estado=1 order by regserialentrega desc limit 1
";
        $rsfr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $td = $rsfr->RecordCount();
        if ($imprimetk == 'S') {
            $retirado = 1;
            //Preparamos el tickete
            $idret = intval($rsfr->fields['regserialentrega']);
            $totalret = intval($rsfr->fields['monto_retirado']);
            $quien = $rsfr->fields['quienllevo'];

            $obs = $rsfr->fields['obs'];
            $retirado = 1;

            $nombreempresa_centrado = corta_nombreempresa($nombreempresa);
            $ahorta = date("d-m-Y H:i:s", strtotime($rsfr->fields['fecha_reposicion']));
            $cajero1 = strtoupper($rsfr->fields['cajero']);
            $totalret = floatval($rsfr->fields['monto_recibido']);
            $hoy = date("d/m/Y H:i:s");
            $texto = "
****************************************
$nombreempresa_centrado
            RECEPCION DE VALORES 
****************************************
RECEPCION ID $idret 
----------------------------------------
Recepcionado el  : $ahorta
Recibido por    : $cajero1
Entregado por   : $quien
Monto Recibido  :".formatomoneda($totalret)."
----------------------------------------


Firma Entregado        Firma Recibido

$obs
----------------------------------------
";
        } else {
            //No se imprime tickete entrega
            $retirado = 0;
        }
    } else {
        $errorautorizav = "C&oacute;digo de autorizaci&oacute;n inv&aacute;lido. No se registra ingreso de dinero."    ;
    }
}


//Pagos x caja
if (isset($_POST['montopagoxcaja']) && intval($_POST['montopagoxcaja']) >= 0) {
    //print_r($_POST);exit;
    $idco = intval($_POST['ocidcajac']);
    $montoabonado = floatval($_POST['montopagoxcaja']);
    $concepto = antisqlinyeccion($_POST['obspago'], 'text');
    $factu = antisqlinyeccion($_POST['nfactu'], 'text');
    $idprovi = intval($_POST['minip']);
    $tipocaja = strtoupper(substr(trim($_POST['tipocajapag']), 0, 1));

    // validaciones de tipo de caja
    // si usa solo caja chica
    if ($rspref->fields['pagoxcaja_chic'] == 'S' && $rspref->fields['pagoxcaja_rec'] == 'N') {
        $tipocaja = "C";
    }
    // si usa solo caja recaudacion
    if ($rspref->fields['pagoxcaja_chic'] == 'N' && $rspref->fields['pagoxcaja_rec'] == 'S') {
        $tipocaja = "R";
    }
    // si usa ambas
    if ($rspref->fields['pagoxcaja_chic'] == 'S' && $rspref->fields['pagoxcaja_rec'] == 'S') {
        // evita hack
        if ($tipocaja != 'R' && $tipocaja != 'C') {
            $tipocaja = "R";
        }
    }
    // si no tiene habilitado ninguno
    if ($rspref->fields['pagoxcaja_chic'] == 'N' && $rspref->fields['pagoxcaja_rec'] == 'N') {
        echo "No tienes permisos para pagos por caja.";
        exit;
    }

    $errores = '';
    if ($montoabonado == 0) {
        $errores = $errores.'* Debe indicar monto abonado. \n';
    }
    if ($concepto == 'NULL') {
        $errores = $errores.'* Debe indicar motivo del pago. \n';
    }
    if (($obligaprov == 'S') && ($idprovi == 0)) {
        $errores = $errores.'* Debe indicar proveedor de factura. \n';

    }
    if ($errores == '') {

        $consulta = "
        insert into gest_pagos
        (idcaja, fecha, medio_pago, total_cobrado,  estado, tipo_pago, idempresa, sucursal, cajero, fechareal, idventa, 
        idtipocajamov,tipomovdinero)
        values
        ($idcaja, '$ahora', 1, $montoabonado, 1, 0, 1, $idsucursal, $idusu, '$ahora', 0, 
        9,'S'
        )
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
        select idpago from gest_pagos where idtipocajamov = 9 order by idpago desc limit 1
        ";
        $rsultpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idpago = $rsultpag->fields['idpago'];

        $consulta = "
        INSERT INTO gest_pagos_det
        (idpago, monto_pago_det, idformapago) 
        VALUES 
        ($idpago, $montoabonado, 1)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        $insertar = "Insert  into pagos_extra 
        (fecha,idcaja,monto_abonado,concepto,idusu,factura,idempresa,idprov,estado,tipocaja,idpago)
        values
        ('$ahora',$idco,$montoabonado,$concepto,$idusu,$factu,$idempresa,$idprovi,1,'$tipocaja',$idpago)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        //echo $insertar;exit;

        $consulta = "
        select max(unis) as unis from pagos_extra where idusu = $idusu
        ";
        $rspag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idpagoex = $rspag->fields['unis'];
        if ($itk == 'S') {
            //echo 'IMprimir';exit;
            header("location: inf_pagosxcaja_imp.php?id=$idpagoex&redir=3");
            exit;
        }
    } else {
        $war = 1;
    }
}

/*----------------------------------------------------------------------*/
//vemos si tiene permitido entregar o recibir plata
$buscar = "Select * from usuarios_autorizaciones where idusu=$idusu and estado =1 order by pkffresgs desc  limit 1";
$rsl = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$imprimetk = trim($rsl->fields['imprimetk']);
if ($imprimetk == 'S') {
    $valorcheck = 1;
} else {
    $valorcheck = 0;
}
//Billetes del sistema
$buscar = "Select * from gest_billetes order by idbillete asc";
$bille = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

//Tipos de moneda
$buscar = "Select * from tipo_moneda where estado=1 order by descripcion asc";
$moneda = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

//Lista de proveedores
$buscar = "Select * from proveedores where estado=1 and idempresa=$idempresa order by nombre asc";
$rspr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

//Formas de pago
$buscar = "select * from formas_pago where estado=1 and muestra_vcaja='S' and idforma > 1 order by descripcion asc";
$rsfp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


//Formas de pago
$buscar = "select * from gest_bancos where estado=1 order by descripcion asc";
$rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


//ultimos movimientos de la caja
$datos = [];
//Retiros(entrega de plata)desde el cajero al supervisor
$buscar = "Select regserialretira,monto_retirado,fecha_retiro,
        (select usuario from usuarios where idusu=caja_retiros.retirado_por) as autorizacion
        from caja_retiros
        where idcaja=$idcaja and cajero=$idusu and estado=1";
$rsretiros = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//echo $buscar;
$tretiros = $rsretiros->RecordCount();
//echo 'llega';exit;
if ($tretiros > 0) {
    $i = 0;
    while (!$rsretiros->EOF) {

        $datos[$i] = [
        "regunico" => $rsretiros->fields['regserialretira'],
        "fecha" => $rsretiros->fields['fecha_retiro'],
        "monto" => $rsretiros->fields['monto_retirado'],
        "autorizado" => $rsretiros->fields['autorizacion'],
        "tipo" => "RETIRO EF",
         "clase" => 1
        ];
        $i = $i + 1;

        $rsretiros->MoveNext();
    }



}

//Reposiciones de Dinero (desde el tesorero al cajero
$buscar = "Select  monto_recibido,regserialentrega,fecha_reposicion,
(select usuario from usuarios where idusu=caja_reposiciones.entregado_por) as autorizacion
 from caja_reposiciones where idcaja=$idcaja and cajero=$idusu and estado=1";
$rsrepo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//echo $buscar;exit;
$trepo = $rsrepo->RecordCount();
if ($trepo > 0) {
    while (!$rsrepo->EOF) {
        $i = $i + 1;
        $datos[$i] = [
        "regunico" => $rsrepo->fields['regserialentrega'],
        "fecha" => $rsrepo->fields['fecha_reposicion'],
        "monto" => $rsrepo->fields['monto_recibido'],
        "autorizado" => $rsrepo->fields['autorizacion'],
        "tipo" => "RECEPCION",
         "clase" => 2
        ];


        $rsrepo->MoveNext();
    }
}
//Pagos x caja
$buscar = "Select estado,unis,fecha,concepto,monto_abonado,(select nombre from proveedores 
where idempresa=$idempresa and idproveedor=pagos_extra.idprov)as provee,factura,anulado_el
,(select usuario from usuarios where idusu=pagos_extra.anulado_por) as quien
from pagos_extra where idusu=$idusu and idcaja=$idcaja and estado <> 6 order by fecha asc";
$rst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$td = $rst->RecordCount();
if ($td > 0) {
    while (!$rst->EOF) {
        $i = $i + 1;
        $datos[$i] = [
        "regunico" => $rst->fields['unis'],
         "fecha" => $rst->fields['fecha'],
        "monto" => $rst->fields['monto_abonado'],
        "autorizado" => $rst->fields['provee']." FC: ".$rst->fields['factura'],
        "tipo" => "PAGO X CAJA",
        "clase" => 3
        ];



        $rst->MoveNext();
    }

}



?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
  </head>

  <body class="nav-md" onLoad="<?php if ($retirado == 1) {?>imprime_retiro();<?php } ?>">
    <div class="container body">
      <div class="main_container">
        <?php require_once("includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
            <?php require_once("includes/lic_gen.php");?>
             <!-- SECCION -->
             <?php if ($idcaja == 0) { ?>
                <div class="row">
                  <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="x_panel">
                        <div class="x_title">
                            <span style="text-align:center; color:#000000;"><h2>Hola ,<span class="fa fa-user"></span>&nbsp;<?php echo $cajero; ?> | est&aacute;s en tesorer&iacute;a.</h2></span>
                            <ul class="nav navbar-right panel_toolbox">
                            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                            </li>
                            </ul>
                        <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                                <h2>Tu caja está cerrada, por favor, indic&aacute; los montos y realiz&aacute; tu apertura.</h2>
                                <form id="form1" name="form1" method="post" action="">
                                <hr />
                                
                                <div class="col-md-4 col-sm-4 form-group">
                                        <label class="control-label col-md-3 col-sm-3 col-xs-12"><h1></h1></label>
                                        <div class="col-md-9 col-sm-9 col-xs-12">
                                            <h1><span class="fa fa-calendar"></span>&nbsp;<?php echo date("d/m/Y", strtotime($fechahoy)); ?></h1>                   
                                        </div>
                                    </div>
                                
                                
                                    <div class="col-md-4 col-sm-4 form-group">
                                        <label class="control-label col-md-3 col-sm-3 col-xs-12"> Abrir con: </label>
                                        <div class="col-md-9 col-sm-9 col-xs-12">
                                            <input type="text" name="montoaper" id="montoaper" value="" placeholder="0" class="form-control" required="">                    
                                        </div>
                                    </div>
                                     <?php if ($usacajachica == 'S') { ?>
                                    <div class="col-md-4 col-sm-4 form-group">
                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Caja Chica: Monto Apetura</label>
                                        <div class="col-md-9 col-sm-9 col-xs-12">
                                            <input type="text" name="recauda" id="recauda" value="<?php echo $monto_fijo_chica; ?>" placeholder="" class="form-control" <?php if ($hab_monto_fijo_chica == 'S') { ?>readonly="readonly" style="background-color:#CCC; border:#FFFFFF; text-align:right;  display:none;"<?php } ?>><?php
                              if ($hab_monto_fijo_chica == 'S') {
                                  echo formatomoneda($monto_fijo_chica, 2, 'N');
                              }
                                         ?>  
                                        </div>
                                    </div>
                                     <?php } ?>
                                    <div class="clearfix"></div>
                                    <br>

                                        <div class="form-group">
                                            <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
                                           <button type="submit" class="btn btn-success"><span class="fa fa-check-square-o"></span> Registrar</button>
                                           
                                            </div>
                                        </div>

                                      <input type="hidden" name="MM_insert" value="form1">
                                              <input type="hidden" name="abrir" id="abrir" value="1" />
                                        <input type="hidden" name="selefe" id="selefe" value="<?php echo $fechahoy ?>" />    
                                    <br>
                                    </form>
                                    <div class="clearfix"></div>
                                    <br><br>
             
                        </div>
                  </div>
                </div>
             
             </div>
             <?php } else { ?>
             <div class="row">
                  <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="x_panel">
                        <div class="x_title">
                            <span style="text-align:center; color:#000000;"><h2>Hola <span class="fa fa-user"></span>&nbsp;<?php echo $cajero; ?>, est&aacute;s administrando tu caja. Id : <?php echo $idcaja ?> para tesoreria</h2></span>
                            <ul class="nav navbar-right panel_toolbox">
                            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                            </li>
                            </ul>
                        <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                                      <div class="col-md-6 col-sm-6 col-xs-12" >
                                      
                                      <?php
                                                 $tdi = 0;
                 //entrada
                 $buscar = "select (select tipo_movimiento from caja_gestion_mov_tipos where idtipocajamov=gest_pagos.idtipocajamov) 
                                                    as clasemov,sum(total_cobrado) as tp from gest_pagos where tipomovdinero='E' and idcaja=$idcaja and estado<> 6 group by idtipocajamov 
                                                    ";
                 $rse = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                 //temporal: hasta resolver la apertura, mostramos el monto en caja_super

                 ?>
                                          <h1>Movimientos de Entrada</h1>
                                          <div class="col-md-6 col-sm-12 form-group has-feedback" >
                                            Apertura Gs: 
                                          </div>
                                           <div class="col-md-6 col-sm-12 form-group has-feedback" style="text-align:right">
                                           <?php echo formatomoneda($rscaj->fields['monto_apertura']);
                 $tdi = $tdi + $rscaj->fields['monto_apertura']; ?>
                                           </div>
                                          <div class="clearfix"></div>
                                          <?php while (!$rse->EOF) { ?>
                                            <div class="col-md-6 col-sm-12 form-group has-feedback">
                                                <?php echo $rse->fields['clasemov']; ?>
                                             </div>
                                          <div class="col-md-6 col-sm-12 form-group has-feedback" style="text-align:right">
                                                <?php echo formatomoneda($rse->fields['tp'], 4, 'N');
                                              $tdi = $tdi + $rse->fields['tp'];  ?>
                                           </div>
                                      <?php $rse->MoveNext();
                                          } ?>
                                      </div>
                                     <div class="col-md-6 col-sm-12 col-xs-12" >
                                       <?php
                                      //salida
                                      $buscar = "select (select tipo_movimiento from caja_gestion_mov_tipos where idtipocajamov=gest_pagos.idtipocajamov) 
                                                as clasemov,sum(total_cobrado) as tp from gest_pagos where tipomovdinero='S' and idcaja=$idcaja  and estado<> 6  group by idtipocajamov
                                                ";
                 $rss = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                 ?>
                                      <h1>Movimientos de Salida</h1>
                                      <?php while (!$rss->EOF) { ?>
                                        <div class="col-md-6 col-sm-6  form-group has-feedback">
                                            <?php echo $rss->fields['clasemov']; ?>
                                         </div>
                                      <div class="col-md-6 col-sm-6  form-group has-feedback" style="text-align:right">
                                            <?php echo formatomoneda($rss->fields['tp'], 4, 'N');
                                          $tdi = $tdi - $rss->fields['tp'];   ?>
                                       </div>
                                      <?php $rss->MoveNext();
                                      } ?>
                                      </div>
                                      <div class="col-md-12 col-xs-12" style="text-align:center"><h4><span class="fa fa-line-chart"></span>&nbsp;Saldo activo: <?php echo formatomoneda($tdi, 4, 'N'); ?></h4></div>
                        </div>
                  </div>
                </div>
             
             
             
            
             
             
             
                <div class="row"><!--------------style="width:65%;"-------->
                 
                      <div class="col-md-12 col-sm-12 col-xs-12" >
                        <div class="x_panel">
                            <div class="x_title">
                                
                                <ul class="nav navbar-right panel_toolbox">
                                <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                </li>
                                </ul>
                            <div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                <div class="col-md-4">
                                            <div class="x_panel">
                                                <div class="x_title">
                                                    <h2>Arqueo : Moneda Nacional <small></small></h2>
                                                    
                                                    <div class="clearfix"></div>
                                                </div>
                                                <div class="x_content">
                                                <div class="alert alert-danger alert-dismissible fade in" id="alertamn" style="display:none" role="alert">
                                                        <strong><span class="fa fa-warning"></span>&nbsp;Errores encontrados:</strong><br />
                                                         <span id="textomo"></span>
                                                    </div>
                                                    <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                        <select name="tipobillete" id="tipobillete" style="height:40px;width:98%;">
                                                            <option value="">Billete</option>
                                                             <?php while (!$bille->EOF) {?>
                                                               <option value="<?php echo $bille->fields['idbillete'] ?>"><?php echo formatomoneda($bille->fields['valor']) ?></option>
                                                               <?php $bille->MoveNext();
                                                             }?>
                                                                                                                     
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                        <input type="text" class="form-control has-feedback-left" name="cantidadbilletes" id="cantidadbilletes" placeholder="Cantidad Billetes/Monedas" required="required">
                                                        <span class="fa fa-money form-control-feedback left" aria-hidden="true"></span>
                                                    </div>
                                                    <div class="clearfix"></div>
                                                    <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                        <input type="text" class="form-control has-feedback-left" name="obsbille" id="obsbille" placeholder="Comentario/obs" required="required">
                                                        <span class="fa fa-comment form-control-feedback left" aria-hidden="true"></span>
                                                    </div>
                                                    <div class="clearfix"></div>
                                                    <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                        <button type="button" onClick="agregabb();" class="btn btn-success"><span class="fa fa-check-square-o"></span> Agregar</button>
                                                        
                                                    </div>
                                                    
                                                </div>
                                                <!-------------------X CONTENT---------->
                                            </div>
                                            <!-------------------X PANEL---------->
                                </div>
                                
                                <div class="col-md-4">
                                            <div class="x_panel">
                                                <div class="x_title">
                                                    <h2>Arqueo : Moneda Extranjera<small></small></h2>
                                                    
                                                    <div class="clearfix"></div>
                                                </div>
                                                <div class="x_content">
                                                    <div class="alert alert-danger alert-dismissible fade in" id="alertaex" style="display:none" role="alert">
                                                        <strong><span class="fa fa-warning"></span>&nbsp;Errores encontrados:</strong><br />
                                                         <span id="textoex"></span>
                                                    </div>
                                                        <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                            <select name="moneda" id="moneda" style="width:98%;height:40px;" onchange="carga_cotizacion(this.value);">
                                                            <option value="" selected="selected">Seleccionar</option>
                                                            <?php while (!$moneda->EOF) {?>
                                                            <option value="<?php echo $moneda->fields['idtipo'] ?>"><?php echo $moneda->fields['descripcion'] ?></option>
                                                            <?php $moneda->MoveNext();
                                                            }?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                            <input type="text" class="form-control has-feedback-left" name="cantidadmonedaex" id="cantidadmonedaex" placeholder="Monto" required="required">
                                                            <span class="fa fa-money form-control-feedback left" aria-hidden="true"></span>
                                                        </div>
                                                        <div class="clearfix"></div>
                                                        <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                            <input type="text" class="form-control has-feedback-left" name="cotiza" id="cotiza" placeholder="Cotizacion" required="required">
                                                            <span class="fa fa-money form-control-feedback left" aria-hidden="true"></span>
                                                        </div>
                                                        <div class="clearfix"></div>
                                                    <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                        <button type="button" onclick="agregabbm();" class="btn btn-success"><span class="fa fa-check-square-o"></span> Agregar</button>
                                                        
                                                    </div>
                                                </div>
                                                <!-------------------X CONTENT---------->
                                            </div>
                                            <!-------------------X PANEL---------->
                                </div>
                                <div class="col-md-4">
                                            <div class="x_panel">
                                                <div class="x_title">
                                                    <h2>Arqueo : Otros Valores <small></small></h2>
                                                
                                                    <div class="clearfix"></div>
                                                </div>
                                                <div class="x_content">
                                                <div class="alert alert-danger alert-dismissible fade in" id="alertaotv" style="display:none" role="alert">
                                                        <strong><span class="fa fa-warning"></span>&nbsp;Errores encontrados:</strong><br />
                                                         <span id="textootros"></span>
                                                    </div>
                                                        <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                            <select name="fpago" id="fpago" style="width:98%;height:40px;" onchange="">
                                                            <option value="" selected="selected">Medio Pago</option>
                                                            <?php while (!$rsfp->EOF) {?>
                                                            <option value="<?php echo $rsfp->fields['idforma'] ?>"><?php echo $rsfp->fields['descripcion'] ?></option>
                                                            <?php $rsfp->MoveNext();
                                                            }?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                            <input type="text" class="form-control has-feedback-left" name="otv" id="otv" placeholder="Monto" required="required">
                                                            <span class="fa fa-money form-control-feedback left" aria-hidden="true"></span>
                                                        </div>
                                                        <div class="clearfix"></div>
                                                        <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                            <select name="selectbanco" id="selectbanco" style="width:98%;height:40px;" onchange="">
                                                            <option value="0" selected="selected">Banco</option>
                                                            <?php while (!$rsb->EOF) {?>
                                                            <option value="<?php echo $rsb->fields['banco'] ?>"><?php echo $rsb->fields['descripcion'] ?></option>
                                                            <?php $rsb->MoveNext();
                                                            }?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                            <input type="text" class="form-control has-feedback-left" name="numcompro" id="numcompro" placeholder="Otros" required="">
                                                            <span class="fa fa-edit form-control-feedback left" aria-hidden="true"></span>
                                                        </div>
                                                        <div class="clearfix"></div>
                                                    <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                        <button type="button" onclick="otrosvalores();" class="btn btn-success"><span class="fa fa-check-square-o"></span> Agregar</button>
                                                        
                                                    </div>
                                                </div>
                                                <!-------------------X CONTENT---------->
                                            </div>
                                            <!-------------------X PANEL---------->
                                </div>
                                
                            </div>
                        </div>
                      </div>
                  
                     
                 
                </div>
            
                <div class="row">
                  <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="x_panel">
                      <div class="x_title">
                        <span style="text-align:center; color:#000000;"><h2><span class="fa fa-line-chart"></span>&nbsp; Resumen Arqueo</h2></span>
                        <ul class="nav navbar-right panel_toolbox">
                          <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                          </li>
                        </ul>
                        <div class="clearfix"></div>
                      </div>
                      <div class="x_content">
                        
                      
                           <div class="col-md-12 col-sm-12 col-xs-12" id="resumendearqueos">
                                    
                                    <?php  require_once("caja_mini_arqueo.php"); ?>
                                
                                
                                    
                          </div>
                          
                      </div>
                    </div>
                  </div>
                </div>
             
                <!-- SECCION -->
                <div class="row">
                  <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="x_panel">
                      <div class="x_title">
                        <span style="text-align:center; color:#000000;"><h2> <span class="fa fa-money"></span>&nbsp;Operaciones sobre valores.</h2></span>
                        <ul class="nav navbar-right panel_toolbox">
                          <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                          </li>
                        </ul>
                        <div class="clearfix"></div>
                      </div>
                      <div class="x_content">

                            
                            <?php if ($idcaja > 0) { ?>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="x_panel">
                                            <div class="x_title">
                                                <h2>Retirar / recibir dinero <small></small></h2>
                                                
                                                <div class="clearfix"></div>
                                            </div>
                                            <div class="x_content">
                                            <form id="entregaval" name="entregaval" action="" method="post">
                                                  <input type="hidden" name="ocidcaja" id="ocidcaja" value="<?php echo $idcaja?>" />
                                                  <input type="hidden" name="cual" id="cual" value="" />
                                                  <input type="hidden" name="md" id="md" value="<?php echo $dispo?>" />
                                                <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                    <input type="text" class="form-control has-feedback-left" id="montogs" name="montogs" required="required" placeholder="Monto">
                                                    <span class="fa fa-money form-control-feedback left" aria-hidden="true"></span>
                                                </div>
                                                <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                    <input type="password" class="form-control has-feedback-left" id="codigoau" name="codigoau" placeholder="Codigo autorizacion">
                                                    <span class="fa fa-key form-control-feedback left" aria-hidden="true"></span>
                                                </div>
                                                <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                    <input type="text" class="form-control has-feedback-left" id="obs" name="obs"  placeholder="Observaciones">
                                                    <span class="fa fa-comment form-control-feedback left" aria-hidden="true"></span>
                                                </div>
                                                <?php if ($imprimetk == 'S') { ?>
                                                <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                    <div class="col-md-3 col-sm-3">
                                                            <input name="prender" id="prender" type="checkbox" value="<?php echo $valorcheck ?>" class="js-switch form-control has-feedback-left" <?php if ($valorcheck == 1) { ?>checked="checked" <?php } ?>>
                                                    </div>
                                                    <div class="col-md-6 col-sm-6">
                                                            <input type="number" value="1" class="form-control " id="canticopias" name="canticopias" placeholder="Copias">
                                                    </div>
                                                </div>
                                                <?php } ?>
                                                <div class="clearfix"></div>
                                                <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                    <button type="button" onclick="envio(1);" class="btn btn-success"><span class="fa fa-check-square-o"></span> Retirar </button>
                                                    <button type="button" onclick="envio(2);" class="btn btn-dark"><span class="fa fa-check-square-o"></span> Recibir </button>
                                                </div>
                                                </form>
                                            </div>
                                            <!-------------------X CONTENT---------->
                                        </div>
                                        <!-------------------X PANEL---------->
                                    </div>
                                    <!-------------------COL---------->
                                    <!-----------------------------------------------ROW-------------------------->
                                    <div class="col-md-4">
                                        <div class="x_panel">
                                            <div class="x_title">
                                                <h2>Pagos por caja <small></small></h2>
                                                
                                                <div class="clearfix"></div>
                                            </div>
                                            <div class="x_content">
                                            <form id="lp" action="" method="post">
                                             <input type="hidden" name="ocidcajac" id="ocidcajac" value="<?php echo $idcaja?>" />
                                              <input type="hidden" name="cual_pago" id="cual_pago" value="5" />
                                              <input type="hidden" name="mdc" id="mdc" value="<?php echo $dispo?>" />
                                             <?php  if ($pagoxcajarec == 'S' or $pagoxcajachica == 'S') { ?>
                                                <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                    <input type="text" class="form-control has-feedback-left" name="montopagoxcaja" id="montopagoxcaja" placeholder="Monto" required="required">
                                                    <span class="fa fa-money form-control-feedback left" aria-hidden="true"></span>
                                                </div>
                                                <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                    <select name="minip" id="minip" style="height:40px;width:98%;">
                                                        <option value="">Proveedor</option>
                                                        <?php while (!$rspr->EOF) {?>
                                                         <option value="<?php echo $rspr->fields['idproveedor']?>"><?php echo $rspr->fields['nombre']?></option>
                                                        <?php $rspr->MoveNext();
                                                        }?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                    <input type="text" name="nfactu" id="nfactu" class="form-control has-feedback-left" placeholder="Factura num">
                                                    <span class="fa fa-key form-control-feedback left" aria-hidden="true"></span>
                                                </div>
                                                <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                    <input type="text" class="form-control has-feedback-left" name="obspago" id="obspago" placeholder="Observaciones">
                                                    <span class="fa fa-comment form-control-feedback left" aria-hidden="true"></span>
                                                </div>
                                                
                                                <div class="clearfix"></div>
                                                <div class="col-md-6 col-sm-6  form-group has-feedback">
                                                    <button type="submit" class="btn btn-success"><span class="fa fa-check-square-o"></span> Registrar</button>
                                                    
                                                </div>
                                             <?php } ?>
                                             </form>
                                            </div>
                                            <!-------------------X CONTENT---------->
                                        </div>
                                        <!-------------------X PANEL---------->
                                    </div>
                                    
                                    
                                    <div class="col-md-4">
                                        <div class="x_panel">
                                            <div class="x_title">
                                                <h2>Ultimos movimientos de la caja <small></small></h2>
                                                
                                                <div class="clearfix"></div>
                                            </div>
                                            <div class="x_content" id="movicaja" style="height:160px; overflow-y:scroll;">
                                                    <table class="table table-bordered">
                                                        <thead>
                                                        <tr>
                                                            <th>Acci&oacute;n</th>
                                                            <th>Tipo Mov</th>
                                                            <th>Monto</th>
                                                            <th>Autorizado / Obs</th>
                                                            <th>Fecha/Hora</th>
                                                            <th></th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        <?php foreach ($datos as $valor) {
                                                            $id = intval($valor['regunico']);
                                                            $url2 = "caja_anular_movimientos.php?tipo=".$valor['clase']."&unicoid=$id";
                                                            $url2 = "javascript:void(0);";
                                                            if ($valor['clase'] == 1) {
                                                                $url1 = "caja_retiros_cajero_rei.php?tipo=1&regser=$id&r=2";
                                                            }
                                                            if ($valor['clase'] == 2) {
                                                                $url1 = "caja_retiros_cajero_rei.php?tipo=2&regser=$id&r=2";
                                                            }
                                                            if ($valor['clase'] == 3) {
                                                                $url1 = "inf_pagosxcaja_imp.php?id=$id&redir=3";
                                                            }
                                                            ?>
                                                        <tr>
                                                            <th scope="row">
                                                            <a href="<?php echo $url1; ?>" data-toggle="tooltip" data-placement="top" title="IMPRIMIR"><span class="fa fa-print"></span></a>&nbsp;&nbsp;
                                                            <a href="<?php echo $url2; ?>" onclick="confirmar(<?php echo $id; ?>,<?php echo $valor['clase'] ?>)" data-toggle="tooltip" data-placement="right" title="ELIMINAR"><span class="fa fa-trash"></span></a>
                                                            </th>
                                                            <th scope="row"><?php echo $valor['tipo']; ?></th>
                                                            <td><?php echo formatomoneda($valor['monto'], 4, 'N'); ?></td>
                                                            <td><?php echo $valor['autorizado']; ?></td>
                                                            <td><?php echo date("d/m/Y H:i:s", strtotime($valor['fecha'])); ?></td>
                                                        </tr>
                                                        <?php } ?>
                                                        </tbody>
                                                    </table>
                                            </div>
                                            <!-------------------X CONTENT---------->
                                        </div>
                                        <!-------------------X PANEL---------->
                                    </div>
                                    
                                    
                                    
                                </div>
                            
                            
                            
                            
                            
                            <?php }//de caja >0?>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="x_panel">
                      <div class="x_title">
                        <span style="text-align:center; color:#000000;"><h2> <span class="fa fa-archive"></span>&nbsp;Verificar y cerrar jornada.</h2></span>
                        <ul class="nav navbar-right panel_toolbox">
                          <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                          </li>
                        </ul>
                        <div class="clearfix"></div>
                      </div>
                      <div class="x_content" id="contenidobalance">
                            <?php require_once("caja_balance_final.php"); ?>
                            
                       </div>
                    </div>
                  </div>
                </div>
                <!-- SECCION --> 
             <?php } ?>
                  <!-- /POPUP -->  
            <div class="modal fade" id="modpop"  role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header"><!-- Modal Header -->
                            <div class="alert alert-danger alert-dismissible fade in" role="alert" id="errorescod" style="display: none">
                                <strong>Errores:</strong><br /><span id="errorescodcuerpo"></span>
                            </div>
                            <span  id="modal_titulo" style="font-weight:bold;"></span>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <input type="hidden" name="octipo" id="octipo" value="" />
                            <input type="hidden" name="ocvalor" id="ocvalor" value="" />
                        </div>

                        <!-- Modal body style="height: 500px; overflow-y: scroll"-->
                        <div class="modal-body" id="modal_cuerpo" >
                            <div align="center">
                                <span id="cuerpodinamico"></span>
                            </div>
                        </div>
                        <!-- Modal footer -->
                        <div class="modal-footer">
                            <div id="conj" style="display:display;">
                                <button type="button" class="btn btn-default" onclick="continuar(2);" data-dismiss="modal">Mejor no</button>
                                <button type="button" class="btn btn-primary" onclick="continuar(1);">Si, metele!</button>
                                <span  id="controlcito" style="display: none"></span>
                            </div>
                            <div id="conj2" style="display:none;">
                                <button type="button" class="btn btn-default" onclick="cerrar(2);" data-dismiss="modal">Mejor no</button>
                                <button type="button" class="btn btn-primary" onclick="cerrar(1);">Si, metele!</button>
                                <span  id="controlcito" style="display: none"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
             <!-- /impresor de ticketes - contenedor -->
            <div  id="impresion_box" hidden="hidden"><textarea readonly id="texto" style="display:none; width:310px; height:500px;" ><?php echo $texto; ?></textarea></div><br />
             <!-- /para almacenar u borrar los billetes y valores de arqueo -->
              <div  id="recarga_local" hidden="hidden"></div>
             
             
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
        <script>
        
        //---------------------------------------------Funciones para arqueo--------------------------//
        function agregabb(){//Agregar  los billetes
            $("#alertamn").hide();
            var errores="";
            var billete=$("#tipobillete").val();
            
            if (billete==""){
                errores=errores+"Debe indicar tipo de billete.<br />";
            }
            
            var cantidad=$("#cantidadbilletes").val();
            if (cantidad=='' || cantidad=='0'){
                errores=errores+"Debe indicar cantidad de monedas/billetes.<br />";
            }
            var sidc=<?php echo $idcaja?>;
            
            if (errores==''){
            
                var parametros = {
                    "billete" : billete,
                    "cantidad" : cantidad,
                    "idcaja"    : sidc,
                    "tipo"        : 1
                };
                 $.ajax({
                        data:  parametros,
                        url:   'caja_mini_arqueo.php',
                        type:  'post',
                        dataType: 'html',
                        beforeSend: function () {
                            $("#resumendearqueos").html("Registrando...");
                        },
                        crossDomain: true,
                        success:  function (response) {
                            $("#resumendearqueos").html(response);        
                            setTimeout(function(){ refrescar_balance(sidc); }, 500);
                        }
                    });
            }else {
                $("#textomo").html(errores);
                $("#alertamn").show();
                
            }
        }
        
        function agregabbm(){//Agregar Monedas Extranjeras
            $("#alertaex").hide();
            var errores="";
            var moneda=$("#moneda").val();
            
            if (moneda==""){
                errores=errores+"Debe indicar tipo de moneda extranjera.<br />";
            }
            var cantidad=$("#cantidadmonedaex").val();
            if (cantidad=='' || cantidad=='0'){
                errores=errores+"Debe indicar cantidad de monedas/billetes.<br />";
            }
            var cotizacion=$("#cotiza").val();
            if (cotizacion==""){
                errores=errores+"Debe indicar cotizacion del dia.<br />";
            }
            var sidc=<?php echo $idcaja?>;
            if (errores==''){
                var parametros = {
                    "moneda" : moneda,
                    "cantidad" : cantidad,
                    "idcaja"    : sidc,
                    "cotizacion" : cotizacion,
                    "tipo"        : 2
                };
                 $.ajax({
                    data:  parametros,
                    url:   'caja_mini_arqueo.php',
                    type:  'post',
                    dataType: 'html',
                    beforeSend: function () {
                        $("#resumendearqueos").html("Registrando...");
                    },
                    crossDomain: true,
                    success:  function (response) {
                        $("#resumendearqueos").html(response);        
                        setTimeout(function(){ refrescar_balance(sidc); }, 500);
                    }
                });
            } else {
                $("#textoex").html(errores);
                $("#alertaex").show();
                
            }
        }
        
        function otrosvalores(){ //Agregar Formas de Pago
            $("#alertaotv").hide();
            var errores="";
            var formapago=$("#fpago").val();
            
            if (formapago==''){
                errores=errores+"Debe indicar forma de pago.<br />";    
            }
            var sidc=<?php echo $idcaja?>;
            var montovalor=$("#otv").val();
            
            if (montovalor==''){
                errores=errores+"Debe indicar monto asociado.<br />";    
            }
            var idbanco=$("#selectbanco").val();
            
            var comenadicional=$("#numcompro").val();
            
            if (errores==''){
                var parametros = {
                        "formapago" : formapago,
                        "monto"     : montovalor,
                        "idcaja"    : sidc,
                        "idbanco"     : idbanco,
                        "adicional"    : comenadicional,
                        "tipo"        : 3
                };
                $.ajax({
                    data:  parametros,
                    url:   'caja_mini_arqueo.php',
                    type:  'post',
                    dataType: 'html',
                    beforeSend: function () {
                        $("#resumendearqueos").html("Registrando...");
                    },
                    crossDomain: true,
                    success:  function (response) {
                        $("#resumendearqueos").html(response);    
                        setTimeout(function(){ refrescar_balance(sidc); }, 500);                        
                    }
                });
            
            } else {
                $("#textootros").html(errores);
                $("#alertaotv").show();
                
            }
            
        }
        function eliminar_valor(cual,valor){//Eliminar 
            var sidc=<?php echo $idcaja?>;
            var parametros = {
                        "idcaja": sidc,
                        "cual"     : cual,
                        "tipo"    : 6,
                        "idserial": valor
            };
            $.ajax({
                data:  parametros,
                url:   'caja_mini_arqueo.php',
                type:  'post',
                dataType: 'html',
                beforeSend: function () {
                    //$("#resumendearqueos").html("Registrando...");
                },
                success:  function (response) {
                    $("#resumendearqueos").html(response);    
                    setTimeout(function(){ refrescar_balance(sidc); }, 500);
                }
            });  
        }
         
        function refrescar_balance(idcaja){
            var parametros = {
                        "idcaja": idcaja
            };
            $.ajax({
                data:  parametros,
                url:   'caja_balance_final.php',
                type:  'post',
                dataType: 'html',
                beforeSend: function () {
                    //$("#resumendearqueos").html("Registrando...");
                },
                success:  function (response) {
                    $("#contenidobalance").html(response);    
                    
                }
            });  
            
        }
        //---------------------------------------------------------------//
            function continuar(cual){
                //Confirma o cancela el borrado de movimientos de valores
                var tipo=$("#octipo").val();
                var unico=$("#ocvalor").val();
                
                if(cual==2){
                    $("#modpop").modal("hide");
                }
                if (cual==1){
                    document.location.href='caja_anular_movimientos.php?tipo='+tipo+"&regunico="+unico+"&ub=1";
                }    
            }
            function confirmar(valorunico,tipo){
                //abre popup de confirmacion para continuar anulando o cancelar
                var clase='';var url='';
                url="caja_anular_movimientos.php?tipo="+tipo+"&valorunico="+valorunico+"&ub=1";
                if (tipo==1){
                    clase=" retiro efectivo?."

                }
                if (tipo==2){
                    clase=" reposicion efectivo?."
                    
                }
                if (tipo==3){
                    clase=" el pago por caja?."
                }
                $("#octipo").val(tipo);
                $("#ocvalor").val(valorunico);
                $("#cuerpodinamico").html("<h1><span class='fa fa-warning'></span><br />Está seguro que desea anular "+clase+"</h1>");
                $("#modpop").modal('show');
            }
            function envio(cual){
                //envia formulario para recibir o retirar
                $("#cual").val(cual);
                $("#entregaval").submit();
            }
            <?php if ($retirado == 1) { ?>
            function imprime_retiro(){
                //imprime el tickete del retiro si esta parametrizado
                var copias=<?php echo $copias ?>;
                var texto = document.getElementById("texto").value;
                var parametros = {
                    "tk" : texto
                };
                 $.ajax({
                        data:  parametros,
                        url:   '<?php echo $script_impresora; ?>',
                        type:  'post',
                        dataType: 'html',
                        beforeSend: function () {
                            $("#impresion_box").html("Enviando Impresion...");
                        },
                        crossDomain: true,
                        success:  function (response) {
                        $("#impresion_box").html(response);        
                        <?php if ($copias > 1) { ?>
                            for (let i = 1; i < copias; i++) {
                                setTimeout(function(){ reimprimirtk(1,texto); }, 1500);
                            }
                        <?php } ?>
                        }
                    });
                
                
            }
            function reimprimirtk(numero,texto){
                //reimprime segun la cantidad de copias seleccionada
                if (numero >0){
                        
                        //var texto = document.getElementById("texto").value;
                        var parametros = {
                            "tk" : texto
                        };
                       $.ajax({
                            data:  parametros,
                            url:   '<?php echo $script_impresora; ?>',
                            type:  'post',
                            dataType: 'html',
                            beforeSend: function () {
                                    $("#impresion_box").html("Enviando Impresion...");
                            },
                            crossDomain: true,
                            success:  function (response) {
                                    $("#impresion_box").html(response);        
                            }
                        });
                    
                } else {
                    
                }
            }
            <?php } ?>
        function mostrarcuadro(cual){
            if(cual==2){
                $("#modal_titulo").html("Cerrando caja");
                $("#cuerpodinamico").html("Esta seguro que desea cerrar?");
                $("#conj").hide();
                $("#conj2").show();
            }
            if(cual==1){
                $("#modal_titulo").html("");
                $("#cuerpodinamico").html("");
                $("#conj").show();
                $("#conj2").hide();
            }
            $("#modpop").modal("show");
        }
        function cerrar(cual){
            if (cual==2){
                
                
            }
            if (cual==1){
                
                $("#cierreform").submit();
                
            }
        }
        </script>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
<link href="vendors/switchery/dist/switchery.min.css" rel="stylesheet">
        <script src="vendors/switchery/dist/switchery.min.js" type="text/javascript"></script>
  </body>
</html>
