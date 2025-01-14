 <?php
/*---------------------------------------------------------
01/08/2022:
Se trabaja y mejora la edicion de pedidos
Se agregan telefono, email, lupa para visualizar los pedidos
25/01/23 Se corrige timestam en BD (scar los valores por defecto en los campos fecha para catering)

03/08/2023
if(($_GET['idsucu']) > 0){
    $idsuc=intval($_GET['idsucu']);
    $whereadd.=" and ventas.sucursal = $idsuc";

}
se agrega el filtro por sucursal, pero por permisos en nueva tabla de sucursales_permisos_paneles
sucursales_permisos_panel



-----------------------------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");

// nombre del modulo al que pertenece este archivo
$modulo = "29";
$submodulo = "354";
require_once("includes/rsusuario.php");
require_once("includes/funciones_cobros.php");
/*-------------------------------------------------
Revertir anulados
----------------------------------------------*/

$msg = '';
$revertir = 0;
$seleccionados = 0;
$idrev = intval($_REQUEST['idt']);
if ($revertir > 0 && $idrev > 0) {
    $update = "update pedidos_eventos set anulado_por=NULL, anulado_el=NULL,estado=3 where idtransaccion=$idrev";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
    $msg = "Se ha restaurado la Transaccion $idrev. Verifique.";
}

$existenpedidos = 0;
$add = "";
$orden = "order by pedidos_eventos.evento_para,hora_entrega asc ";
$where = "where pedidos_eventos.estado=3 ";
if ($_REQUEST['desde'] != '') {
    $desde = antisqlinyeccion($_REQUEST['desde'], 'date');
    $add .= " and date(evento_para)>=$desde ";
}
if ($_REQUEST['hasta'] != '') {
    $hasta = antisqlinyeccion($_REQUEST['hasta'], 'date');
    $add .= " and date(pedidos_eventos.evento_para)<=$hasta ";
}
if ($_REQUEST['desdee'] != '') {
    $desdeemision = antisqlinyeccion($_REQUEST['desdee'], 'date');
    $add .= " and date(pedidos_eventos.registrado_el)>=$desdeemision ";
}
if ($_REQUEST['hastae'] != '') {
    $hastaemision = antisqlinyeccion($_REQUEST['hastae'], 'date');
    $add .= " and date(pedidos_eventos.registrado_el)<=$hastaemision ";
}
if ($_REQUEST['nombreape'] != '') {
    $orden = " ";
    $nombre = antisqlinyeccion($_REQUEST['nombreape'], 'text');
    $nsolo = str_replace("'", "", $nombre);
    $add .= " and (select razon_social from cliente where idcliente=pedidos_eventos.id_cliente_solicita)  like ('%$nsolo%') ";
}
if ($_REQUEST['cliente_ped'] != '') {
    $ex = explode("|", $_REQUEST['cliente_ped']);
    $idsolicita = intval($ex[0]);
    $add .= " and pedidos_eventos.id_cliente_solicita=$idsolicita ";

}
if ($_REQUEST['dc'] != '') {
    $documento = antisqlinyeccion($_REQUEST['dc'], 'int');
    $add .= " and cliente.documento=$documento ";
}

if ($_REQUEST['dcestado'] != '') {
    $estapedido = antisqlinyeccion($_REQUEST['dcestado'], 'int');
    $add .= " and pedidos_eventos.estado_pedido_int=$estapedido ";
}
if ($_REQUEST['dcestadocuenta'] != '') {
    $estapedido = antisqlinyeccion($_REQUEST['dcestadocuenta'], 'int');


    switch ($estapedido) {
        case 1:
            $whereadd .= "and pedidos_eventos.saldo_evento>0 ";
            break;
        case 2:
            $whereadd .= "and pedidos_eventos.saldo_evento<=0 ";
            break;
        case 3:
            $whereadd .= "and pedidos_eventos.saldo_evento<=0 ";
            break;
        case 4:
            $whereadd .= "and (pedidos_eventos.saldo_evento>0 and pedidos_eventos.cobrado_evento>0 and pedidos_eventos.monto_pedido <> pedidos_eventos.cobrado_evento )  ";
            break;

        default:
    }

}






if ($_REQUEST['dcestadostock'] != '') {
    $estapedido = antisqlinyeccion($_REQUEST['dcestadostock'], 'int');
    $desde = date("Y-m-d");
    ;
    $addfiltro .= " and date(evento_para) < '$desde' ";

    switch ($estapedido) {
        case 1:
            $whereadd .= "and pedidos_eventos.saldo_evento<=0 and pedidos_eventos.detallado=1 and pedidos_eventos.descontado=0 $addfiltro";
            break;
        case 2:
            $whereadd .= "and pedidos_eventos.saldo_evento<=0 and pedidos_eventos.detallado=2 and pedidos_eventos.descontado=1 $addfiltro ";
            break;
        default:

    }

}

if ($_REQUEST['email'] != '') {
    $email = antisqlinyeccion($_REQUEST['email'], 'email');
    $add .= " and cliente.email=$email ";
}
if ($_REQUEST['transa'] != '') {
    $idtt = intval($_REQUEST['transa']);
    $add .= " and pedidos_eventos.idtransaccion=$idtt ";
}

if ($_REQUEST['idvalor'] != '') {
    $orden = '';
    $idorden = intval($_REQUEST['idvalor']);
    if ($idorden == 1) {
        $campo_orden = " monto_evento DESC ";
        $orden = " order by  $campo_orden ";
    }
    if ($idorden == 2) {
        $campo_orden = " cobrado_evento DESC ";
        $orden = " order by  $campo_orden ";
    }
    if ($idorden == 3) {
        $campo_orden = " saldo_evento DESC ";
        $orden = " order by  $campo_orden ";
    }
}
if (($_GET['idsucu']) > 0) {
    $idsuc = intval($_GET['idsucu']);
    $add .= " and pedidos_eventos.idsucursal = $idsuc";

}

/*-----------------------------*/
//contabilizamos si hay al meeenos un permiso ya usamos el filtro
$buscar = "Select count(*) as total from sucursales_permisos_panel where idusuario=$idusu";
$rpermisos = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
if (intval($rpermisos->fields['total'] > 0)) {
    $limitasucu = 1;

} else {
    $limitasucu = 0;


}
if ($limitasucu > 0) {
    $addlimita = " and pedidos_eventos.idsucursal in (select idsucursal from sucursales_permisos_panel where idusuario=$idusu) ";

} else {
    $addlimita = "";
}


///verificamos nuevamente el saldo al recargar la pagina
$consulta = "
select *
from tmp_detalles_eventos_factu 
where 
estado <> 6
";
$rsaallverificaevento = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
while (!$rsaallverificaevento->EOF) {
    $parametros_array_evento['idevento'] = intval($rsaallverificaevento->fields['idevento']);
    //actualizamos nuevamente los saldos de los eventos en el archivo temporarl
    actualiza_saldo_evento($parametros_array_evento);
    $rsaallverificaevento->MoveNext();
}

/*-----------------------------*/
///verificamos si se agrego datos al carrito
$consulta = " 
select * from tmp_detalles_eventos_factu 
where estado = 1 and ualta = $idusu
";
$rscarritofactu = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$seleccionado = $rscarritofactu->RecordCount();



//////////////////////////////////////////////
//listado de pedidos
$buscar = "Select estado_pedido_int,cliente.razon_social,pedidos_eventos.estado,
cliente.fantasia,cliente.nombre,cliente.apellido,monto_pedido,
regid,idpresupuesto,evento_para,hora_entrega,dire_entrega,
ubicacion_comp as ubicacion,idtransaccion,monto_evento,nombre_evento,
(select email from cliente where idcliente=pedidos_eventos.id_cliente_solicita) as email,
(select telefono from cliente where idcliente=pedidos_eventos.id_cliente_solicita) as telefono,
(select usuario from usuarios where idusu=pedidos_eventos.ultimo_cambio_por
) as quiencambio,
(select mail from sucursal_cliente where idsucursal_clie=pedidos_eventos.id_cliente_sucu_pedido) as mailsucursal,
(select telefono from sucursal_cliente where idsucursal_clie=pedidos_eventos.id_cliente_sucu_pedido) as telefonosucursal,
ultimo_cambio,
(select razon_social 
        from cliente where idcliente=pedidos_eventos.id_cliente_solicita)
        as  solicitado_por,
    (select idcliente 
        from cliente where idcliente=pedidos_eventos.id_cliente_solicita) as idclientereal,
    (select pasado from produccion_orden_new where idunico=regid limit 1) as pasado,
    pedidos_eventos.registrado_el,confirmado_pedido_el as confirmadoel,
    (select usuario 
    from usuarios where idusu=pedidos_eventos.registrado_por) as quien,
    pedidos_eventos.cobrado_evento, pedidos_eventos.saldo_evento,
    (
        SELECT estado_evento
        FROM pedidos_eventos_estado
        where
        idestadoevento = pedidos_eventos.estado_pedido_int

    ) as estado_evento
    from pedidos_eventos 
    inner join cliente on cliente.idcliente=pedidos_eventos.id_cliente_solicita
        where 
        regid >0
        and pedidos_eventos.estado_pedido_int <> 6
        and pedidos_eventos.estado <> 6
        
        $whereadd $addlimita
        $add 
    $orden 
    
    limit 400";
//echo $buscar;exit;
$rga = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tpedidos = $rga->RecordCount();
//echo $buscar;
/*
$url_des="cat_panel_pedidos_csv.php?desde=".
antixss($_REQUEST['desde']).
"&hasta=".antixss($_REQUEST['hasta'].
"&desdee=".antixss($_REQUEST['desdee']).
"&hastae=".antixss($_REQUEST['hastae']).
"&nombreape=".antixss($_REQUEST['nombreape']).
"&cliente_ped=".antixss($_REQUEST['cliente_ped']).
"&dc=".antixss($_REQUEST['dc']).
"&dcestado=".antixss($_REQUEST['dcestado']).
"&email=".antixss($_REQUEST['email']).
"&transa=".antixss($_REQUEST['transa']);
*/
$url_des = "cat_panel_pedidos_csv.php".parametros_url();

$buscar = "select sum(monto_evento) as totalsuma from pedidos_eventos  
$where $addlimita
$add ";
//echo $buscar;exit;
$rssuma = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$sub_total_pedido = $rssuma->fields['totalsuma'];


//echo $buscar;
?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>

    <!--<script data-require="jquery@2.2.4" data-semver="2.2.4" src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
  </script>-->


  

  </head>
  <body class="nav-md">
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
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Administraci&oacute;n de Pedidos </h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                  <?php
                  if ($msg != '') {?>
                  <div class="col-md-12">
                  <span style="color: red; font-size:14px;">ATENCION: &nbsp;<?php echo $msg; ?></span>
                  </div>
                  <hr />
                  <?php } ?>
                  <form id="f1" action="" method="get">
                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Desde </label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                                <input type="date" name="desde" id="desde" value="<?php echo antixss($_REQUEST['desde']); ?>" placeholder="Desde" class="form-control" >    
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Hasta </label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                                <input type="date" name="hasta" id="hasta" value="<?php echo antixss($_REQUEST['hasta']); ?>" placeholder="Hasta" class="form-control" >    
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Emisi&oacute;n Desde </label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                                <input type="date" name="desdee" id="desdee" value="<?php echo antixss($_REQUEST['desdee']); ?>" placeholder="Desde" class="form-control" >    
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Emisi&oacute;n Hasta </label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                                <input type="date" name="hastae" id="hastae" value="<?php echo antixss($_REQUEST['hastae']); ?>" placeholder="Hasta" class="form-control" >    
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Cliente Pedido </label>
                            <div class="col-md-9 col-sm-9 col-xs-12"> 
                                <input type="text" name="cliente_ped" id="cliente_ped" onClick="busca_cliente('cliente_ped',<?php echo '1'; ?>);" value="" placeholder="" class="form-control" readonly >  
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Raz&oacute;n Social </label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                                <input type="text" name="nombreape" id="nombreape" value="<?php echo antixss($_REQUEST['nombreape']); ?>" placeholder="" class="form-control" >  
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Estado Evento </label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                                <?php
// consulta
$consulta = "
SELECT idestadoevento , estado_evento
FROM pedidos_eventos_estado
where
estado = 1
order by estado_evento asc
 ";

// valor seleccionado
if (isset($_REQUEST['dcestado'])) {
    $value_selected = htmlentities($_REQUEST['dcestado']);
} else {
    $value_selected = '';
}

// parametros
$parametros_array = [
    'nombre_campo' => 'dcestado',
    'id_campo' => 'dcestado',

    'nombre_campo_bd' => 'estado_evento',
    'id_campo_bd' => 'idestadoevento',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Estado de Cuenta </label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                                <input style="display:none;" type="text" name="dcuenta" id="dcuenta" value="<?php echo antixss($_REQUEST['dcuenta']); ?>" placeholder="" class="form-control" >                    
                                <select name="dcestadocuenta" id="dcestadocuenta" class="form-control">
                                    <option value="" selected="selected">Seleccionar estado</option>
                                    <option value="1" <?php if ($_REQUEST['dcestadocuenta'] == 1) {  ?>selected="selected"<?php } ?>>Solo con Saldo</option>
                                    <option value="2" <?php if ($_REQUEST['dcestadocuenta'] == 2) {  ?>selected="selected"<?php } ?>>Saldo Cero</option>
                                    <option value="3" <?php if ($_REQUEST['dcestadocuenta'] == 3) {  ?>selected="selected"<?php } ?>>Cobrado Total</option>
                                    <option value="4" <?php if ($_REQUEST['dcestadocuenta'] == 4) {  ?>selected="selected"<?php } ?>>Cobrado Parcial</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Estado de Stock </label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                                <input style="display:none;" type="text" name="dstock" id="dstock" value="<?php echo $_REQUEST['dstock'] ?>" placeholder="" class="form-control" >                    
                                <select name="dcestadostock" id="dcestadostock" class="form-control">
                                    <option value="" selected="selected">Seleccionar estado</option>
                                    <option value="1" <?php if ($_REQUEST['dcestadostock'] == 1) {  ?>selected="selected"<?php } ?>>No descontado</option>
                                    <option value="2" <?php if ($_REQUEST['dcestadostock'] == 2) {  ?>selected="selected"<?php } ?>>Descontado</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Transacci&oacute;n </label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                                <input type="text" name="transa" id="transa" value="<?php echo antixss($_REQUEST['transa']); ?>" placeholder="" class="form-control" >                    
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Ordenar por Valores </label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                                    <select name="idvalor" id="idvalor" class="form-control">
                                        <option value="" selected="selected">Seleccionar</option>
                                        <option value="1" <?php if ($_REQUEST['idvalor'] == 1) {
                                            echo "selected='selected' ";
                                        }?>>Monto evento</option>
                                        <option value="2" <?php if ($_REQUEST['idvalor'] == 2) {
                                            echo "selected='selected' ";
                                        }?>>Saldo evento</option>
                                        <option value="3" <?php if ($_REQUEST['idvalor'] == 3) {
                                            echo "selected='selected' ";
                                        }?>>Cobrado evento</option>
                                    </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-sm-6 form-group">
                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal Pedido(carga)</label>
                            <div class="col-md-9 col-sm-9 col-xs-12">
                        <?php
                        // consulta
                        $consulta = "
                        SELECT idsucu, nombre
                        FROM sucursales
                        where
                        estado = 1 and idsucu in(select idsucursal from sucursales_permisos_panel where idusuario=$idusu)
                        order by nombre asc
                         ";

// valor seleccionado
if (isset($_GET['idsucu'])) {
    $value_selected = htmlentities($_GET['idsucu']);
} else {
    $value_selected = htmlentities($rs->fields['idsucu']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsucu',
    'id_campo' => 'idsucu',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idsucu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODAS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
                            </div>
                        </div>

                        
                        
                        
                        <div class="form-group">
                            <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-5">
                                <button type="submit" class="btn btn-default"><span class="fa fa-search"></span> Filtrar</button>&nbsp;
                                <button type="button" class="btn btn-dark" onclick="recargarpag();"><span class="fa fa-trash"></span> Limpiar filtros</button>
                            </div>
                        </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
                  
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Pedidos registrados</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content"> 

                 <a href="javascript:void(0);" class="btn btn-success" onclick="abrirnuevo();" ><span class="fa fa-plus-square"></span>&nbsp;&nbsp;Nuevo 
                </a>
                 <a href="catering/anticipos_multiples.php" target="_blank" class="btn btn-default" ><span class="fa fa-search"></span>&nbsp;&nbsp;Anticipos Multiples
                </a>

                 <a href="<?php echo $url_des ?>" class="btn btn-default" target="_blank" onclick="generar_xls();" ><span class="fa fa-file-excel-o"></span>&nbsp;&nbsp;Descargar XLS 
                </a>&nbsp;&nbsp;
                 <a href="javascript:void(0);" class="btn btn-default"  onclick="mostrar_anulados();" ><span class="fa fa-trash-o"></span>&nbsp;&nbsp;Ver Anulados </a>
                 <a onclick="consultar_eventos_mini_factu_valida();" id ="factsel" disabled="false"  class="btn btn-success"  ><span class="fa fa-check-square-o"></span>&nbsp;&nbsp;Facturar Seleccionados</a>
                
                 <?php if (intval($_REQUEST['dcestadostock']) == 1) { ?>
                    <a href="cat_adm_pedidos_descuento_stock_visor.php<?php  echo parametros_url()?>" id ="factsel"  class="btn btn-sm btn-danger"  ><span class="fa fa-trash"></span>&nbsp;&nbsp;Descontar Stock</a>
                    
                    <?php } ?>
                
                

        
                    <hr />
                    <div align="center" id="resultados">
                        <?php require_once("facturar_catering_car_tmp_factu_desdepedi.php");?>
                    </div>    
                    
            
            <?php
        //Las columnas a ser mostradas, deben estar habilitadas previamente para el usuario activo, por lo cual buscamos los permisos establecidos para el panel en curso

        $buscar = "Select * from visor_pedidos_parametros where idusu=$idusu";
$rl = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tcolu = $rl->RecordCount();
if ($tcolu > 0) {
    $c1 = 0;
    $c2 = 0;
    $c3 = 0;
    $c4 = 0;
    $c5 = 0;
    $c6 = 0;
    $c7 = 0;
    $c8 = 0;
    $c9 = 0;
    $c10 = 0;
    $c11 = 0;
    $c12 = 0;
    $c1 = intval($rl->fields['col_1']);
    $c2 = intval($rl->fields['col_2']);
    $c3 = intval($rl->fields['col_3']);
    $c4 = intval($rl->fields['col_4']);
    $c5 = intval($rl->fields['col_5']);
    $c6 = intval($rl->fields['col_6']);
    $c7 = intval($rl->fields['col_7']);
    $c8 = intval($rl->fields['col_8']);
    $c9 = intval($rl->fields['col_9']);
    $c10 = intval($rl->fields['col_10']);
    $c11 = intval($rl->fields['col_12']);
    $c12 = 1;
    $anticipo = trim($rl->fields['anticipo']);
    ?><?php if ($tpedidos > 0) {?>
            <div class="table-responsive" id="select_pedi">
                <table width="100%" class="table table-bordered jambo_table bulk_action" id="tablabb">
                <thead>
                    <tr class="headings">
                        <?php if ($c1 > 0) { ?>
                        <?php if ($anticipo == 'S') { ?>
                        <th width="8%" class="column-title">Anticipos / Cobros</th>
                    <?php } else { ?>
                    
                    <?php } ?>
                    <th ></th>
                    <th >Selec</th>
                        <th >Acciones</th>
                        <?php } ?>
                        <th class="column-title">Id Pedido / Cliente</th>
                        <th class="column-title">Estado</th>
                        <?php if ($c2 > 0) { ?>
                        <th class="column-title">Fecha Evento </th>
                        <?php } ?>
                        
                        <?php if ($c3 > 0) { ?>
                        <th class="column-title">Hora Evento </th>
                        <?php } ?>
                        <?php if ($c4 > 0) { ?>
                        <th style="width: 320px;">Direcci&oacute;n </th>
                        <?php } ?>
                        <?php if ($c5 > 0) { ?>
                        <th class="column-title">Tel&eacute;fono </th>
                        <?php } ?>
                        <?php if ($c6 > 0) { ?>
                        <th class="column-title">Email</th>
                        <?php } ?>
                        <?php if ($c7 > 0) { ?>
                        <th class="column-title">Monto Pedido</th>
                        <?php } ?>
                        <?php if ($c8 > 0) { ?>
                        <th class="sorting" tabindex="0" aria-controls="datatable-checkbox" rowspan="1" colspan="1" aria-label="Name: activate to sort column ascending" style="width: 95.2px;">
                        
                        Monto Abonado</th>
                        <?php } ?>
                        <?php if ($c9 > 0) { ?>
                        <th class="column-title" class="sortable">Saldo Activo</th>
                        <?php } ?>
                        <?php if ($c10 > 0) { ?>
                        <th class="column-title">Ubicacion</th>
                        <?php } ?>
                        <?php if ($c11 > 0) { ?>
                        <th class="column-title">Fecha Carga</th>
                        <?php } ?>
                        <?php if ($c12 > 0) { ?>
                        <th class="column-title">Registrado por</th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
            $sub_total_pedido = 0;
        $sub_total_saldo = 0;
        $sub_total_abonado = 0;
        $existenpedidos = 0;
        while (!$rga->EOF) {
            $existenpedidos++;
            //verificamos si el pedido posee al menos una orden para marcarla en verde y si su estado primario no es modificado
            $idreg = intval($rga->fields['regid']);
            $idtransaccion = intval($rga->fields['idtransaccion']);
            /*------------CAMBIO DE FORMAS Y COLORES DEL PEDIDO SEGUN ESTADO----------------------*/
            $qca = trim($rga->fields['quiencambio']);
            $pasado = 0;
            if ($rga->fields['pasado'] == '' or $rga->fields['pasado'] == 'N') {
                $color = "#F23131";

            } else {
                //ya existe
                $color = "green";
                $pasado = 1;
            }
            if ($qca != '' && $pasado == 1) {
                $colortr = "";
            } else {
                $colortr = "";
            }
            if ($pasado == 1 && $rga->fields['quiencambio'] != '') {
                $colortr = "yellow";
            }
            $pasado = trim($rga->fields['pasado']);
            $nombre_evento = trim($rga->fields['nombre_evento']);
            //Verificamos los estados del pedido
            if ($rga->fields['estado_pedido_int'] == 1) {
                $color = "black";
                $colortr = "white";
                $pedidochar = "Presupuesto";
            }
            if ($rga->fields['estado_pedido_int'] == 3) {
                $color = "green";
                $colortr = "white";
                $pedidochar = "Confirmado";
            }
            if ($rga->fields['estado_pedido_int'] == 4) {
                $color = "green";
                $colortr = "yellow";
                $pedidochar = "Produccion";
            }
            $anticipo = 'S';

            //recalculamos
            $update = "
                    update pedidos_eventos_detalles set subtotal=(precio_venta*cantidad) where subtotal=0 and cantidad > 0 and precio_venta >0 
                    and idtransaccion=$idtransaccion
                    ";
            //$conexion->Execute($update) or die(errorpg($conexion,$update));

            $update = "
                    update pedidos_eventos  set monto_evento=monto_pedido,monto_sin_descuento=monto_pedido where monto_evento<monto_pedido  
                    and idtransaccion=$idtransaccion
                    ";
            //$conexion->Execute($update) or die(errorpg($conexion,$update));


            //recalculamos el saldo_evento
            $update = "
                    update pedidos_eventos  set saldo_evento=(monto_pedido-cobrado_evento) where idtransaccion=$idtransaccion";
            //$conexion->Execute($update) or die(errorpg($conexion,$update));


            //Traemos los valores recalculados si existieren;
            $buscar = "Select * from pedidos_eventos where idtransaccion=$idtransaccion";
            $rsm1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $monto_pedido_actual = floatval($rsm1->fields['monto_sin_descuento']);//Monto del pedido
            $totalentregas = floatval($rsm1->fields['cobrado_evento']);//Cobrado del pedido
            $saldo_evento = floatval($rsm1->fields['saldo_evento']);//Saldo del pedido
            $descontado = floatval($rsm1->fields['descuento_neto']);
            $monto_pedido_actual = $monto_pedido_actual - $descontado;

            $sub_total_pedido = $sub_total_pedido + $monto_pedido_actual;
            $sub_total_saldo = $sub_total_saldo + $saldo_evento;
            $sub_total_abonado = $sub_total_abonado + $totalentregas;



            ?>

                <tr class="" style="color:<?php echo $color; ?>;background-color:<?php echo $colortr ?>;">
                    <td>
                        <div class="btn-group">
                        <?php if ($anticipo == 'S') { ?>        
                            <a onclick="verificar_saldo_evento(<?php echo $rga->fields['regid']?>,1)" class="btn btn-sm btn-default" title="Registrar Anticipos" data-toggle="tooltip" data-placement="right"  data-original-title="Registrar Anticipos"><span class="fa fa-money"></span></a>
                            <a onclick = "verificar_saldo_evento(<?php echo $rga->fields['regid']?>,2)" class="btn btn-sm btn-default" title="Facturar" data-toggle="tooltip" data-placement="right"  data-original-title="Facturar"><span class="fa fa-money"></span></a>
                            <!--<a href="pagos_afavor_adh_add.php?idevento=<?php echo $rga->fields['regid']?>" class="btn btn-sm btn-default" title="Registrar Anticipos" data-toggle="tooltip" data-placement="right"  data-original-title="Registrar Anticipos"><span class="fa fa-money"></span></a>
                            <a href="facturar_catering/facturar_catering.php?idevento=<?php echo $rga->fields['regid']?>" class="btn btn-sm btn-default" title="Facturar" data-toggle="tooltip" data-placement="right"  data-original-title="Facturar"><span class="fa fa-money"></span></a>-->
                            <!--<a href="javascript:void(0);" class="btn btn-sm btn-default" title="Cobrar" data-toggle="tooltip" data-placement="right"  data-original-title="Cobrar"><span class="fa fa-money"></span></a>-->
                            <!--<a href="cat_adm_pedidos_new.php?reg=<?php echo $rga->fields['regid']?>&t=1" class="btn btn-sm btn-default" title="Cobro a confirmar" data-toggle="tooltip" data-placement="right"  data-original-title="Cobro a confirmar"><span class="fa fa-cc"></span></a>-->
                        <?php } ?>
                            
                        </div>
                    </td>
                    <td> <a href="javascript:void(0);"  id="selped_<?php echo $rga->fields['regid']; ?>" class="btn btn-default" title="Seleccionar"  onclick="verificar_seleccionados(<?php echo $rga->fields['regid']; ?>)" ><span class="fa fa-plus"></span></a></td>
                    
                    
                    
                    <?php if ($c1 > 0) { ?>
                    <td> 
                        <?php if (intval($pasado == 1)) {  ?>
                        <div class="col-md-1">
                            
                            <a href="cat_adm_pedidos_genera_pdf_nuevo.php?reg=<?php echo $rga->fields['regid']?>&t=1" target="_blank"  >
                                <span class="fa fa-file-pdf-o"></span>
                            </a>
                    
                            
                        </div>
                        <?php } ?>
                        
                        <div class="col-md-1">
                            <a href="javascript:void(0);" onclick="eliminar_pedido(<?php echo $rga->fields['regid'] ?>)"><span class="fa fa-trash"></span></a>
                            <a style="display:none;" id="rped_<?php echo $rga->fields['regid'] ?>"  href="cat_pedidos_new.php?edl=<?php echo $rga->fields['idtransaccion']?>&sc=99"  title="Anular pedido" data-toggle="tooltip" data-placement="right"  data-original-title="Anular pedido"><span class="fa fa-trash"></span></a>
                        </div>
                        <?php if (intval($pasado == 0)) {  ?>
                        <div class="col-md-1">
                            
                            <a href="cat_adm_pedidos_pasar_produccion.php?idpanel=<?php echo $rga->fields['regid']?>" target="_blank"   >
                                <span class="fa fa-crosshairs"></span>
                            </a>
                            
                        </div>
                        <?php  } ?>
                        
                        <div class="col-md-1">
                            <a href="cat_pedidos_new_editar.php?id=<?php echo $rga->fields['regid']; ?>"  title="Editar Cuerpo" data-toggle="tooltip" data-placement="right"  data-original-title="Editar Cuerpo" target="_blank"><span class="fa fa-edit"></span></a>
                            <a href="javascript:void(0);" onclick="cambiar_estado('<?php echo $pedidochar ?>','<?php echo $nombre_evento ?>');" target="_self" title="Cambiar Estado"><span class="fa fa-wrench"></span>&nbsp;</a>
                        </div>
                        <div class="col-md-1">
                            <a href="cat_pedidos_new_verificar_final.php?idt=<?php echo $rga->fields['idtransaccion']?>&t=1" target="_blank"><span class="fa fa-search"></span>
                            </a>
                        </div>
                        <div class="col-md-1">
                            <a href="cat_pedidos_envia_mail.php?idt=<?php echo $rga->fields['idtransaccion']?>&tipo=1"  title="Enviar pedido por mail" data-toggle="tooltip" data-placement="right"  data-original-title="Email"><span class="fa fa-envelope"></span></a>
                        </div>
                    </td>
                    <?php } ?>
                    <td class="column-title"><?php echo $rga->fields['nombre_evento']?></td>
                    <td class="column-title"><?php
            echo antixss($rga->fields['estado_evento']);


            ?>
                    
                </td>
                    <?php if ($c2 > 0) { ?>
                    <td class="column-title">
                        <?php echo date("d/m/Y", strtotime($rga->fields['evento_para'])); ?>
                            <input type="hidden" name="fechaseg_<?php echo $rga->fields['regid']?>"
                            id="fechaseg_<?php echo $rga->fields['regid']?>" value="<?php echo $rga->fields['evento_para'] ?>" /></td>
                    <?php } ?>
                    <?php if ($c3 > 0) { ?>
                    <td class="column-title"><?php echo $rga->fields['hora_entrega']; ?></td>
                    <?php } ?>
                    <?php if ($c4 > 0) { ?>
                    <td  ><?php echo $rga->fields['dire_entrega']; ?></td>
                    <?php } ?>
                    <?php if ($c5 > 0) { ?>
                    <td class="column-title">
                    <?php
                    if ($rga->fields['telefonosucursal'] != '') {
                        echo $rga->fields['telefonosucursal'];
                    } else {

                        echo $rga->fields['telefono'];
                    }
                        ?>
                    </td>
                    <?php } ?>
                    <?php if ($c6 > 0) { ?>
                    <td class="column-title">
                    <?php
                           if ($rga->fields['mailsucursal'] != '') {
                               echo $rga->fields['mailsucursal'];
                           } else {
                               echo $rga->fields['email'];
                           }
                        ?>
                    </td>
                    <?php } ?>
                    <?php if ($c7 > 0) { ?>
                    <td class="column-title" align="right"><?php echo formatomoneda($monto_pedido_actual, 4, 'N'); ?></td>
                    <?php } ?>
                    <?php if ($c8 > 0) { ?>
                    <td class="column-title"><?php echo formatomoneda($totalentregas, 4, 'N'); ?></td>
                    <?php } ?>
                    <?php if ($c9 > 0) { ?>
                    <td class="column-title"><?php echo formatomoneda($saldo_evento, 4, 'N'); ?></td>
                    <?php } ?>
                    <?php if ($c10 > 0) { ?>
                    <td class="column-title"><a href="<?php echo trim($rga->fields['ubicacion']); ?>" target="_blank"><?php echo trim($rga->fields['ubicacion']); ?></a></td>
                    <?php } ?>
                    <?php if ($c11 > 0) { ?>
                    <td class="column-title"><?php echo date("d/m/Y H:i:s", strtotime($rga->fields['registrado_el'])); ?></td>
                    <?php } ?>
                    <?php if ($c12 > 0) { ?>
                    <td class="column-title"><?php echo $rga->fields['quien']; ?></td>
                    <?php } ?>
                  </tr>
                <?php $rga->MoveNext();
        }?>
                <?php if ($c7 > 0) { ?>
                <tr>
                    <td colspan="14" align="center">
                        <table style="border: 1px;width: 300px;">
                            <tr>
                                <td style="color:black;">Monto Evento</td>
                                <td style="color:black;"><?php echo formatomoneda($sub_total_pedido); ?></td>
                            </tr>
                            <tr>
                                <td style="color:black;">Abonado</td>
                                <td style="color:black;"> <?php echo formatomoneda($sub_total_abonado); ?></td>
                            </tr>
                            <tr>
                                <td style="color:black;">Saldo Evento</td>
                                <td style="color:red;"><strong><?php echo formatomoneda($sub_total_saldo); ?></strong></td>
                            </tr>
                        </table>
                    </td>
                
                
                </tr>
                                
                <?php } ?>
                
                
                
                </tbody>
                </table>
                </div>
                <?php } else {
                    $existenpedidos = 0;?>
                <span class="fa fa-warning"></span>&nbsp;&nbsp;<h2>No existen pedidos confirmados.</h2>
                <?php }?>
            <?php } else { ?>
            <span class="fa fa-warning"></span>&nbsp;&nbsp;El usuario no posee permisos para ver columnas.
            <?php } ?>
                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
              <!--
            <div class="modal fade" id="acciones"  role="dialog" aria-labelledby="titulo" aria-hidden="true" >
            <div class="modal-dialog modal-dialog-centered" role="document" >
              <div class="modal-content" style="width: 800px;">

                <!-- Modal Header 
                <div class="modal-header">
                    
                    <span  id="variadotit" style="font-weight:bold;"></span>
                   <button type="button" class="close" data-dismiss="modal"></button>
                </div>

                <!-- Modal body 
                <div class="modal-body" id="acciones_cuerpo" style="min-height:250px; overflow-y: scroll">
                  
                  
                </div>
                    
                <!-- Modal footer 
                <div class="modal-footer">
            
Monto  descuento            6.293.000
                
                </div>

              </div>
            </div>
          </div>--> 
          
          
           <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">

                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                  </button>
                  <h4 class="modal-title" id="modal_titulo">Titulo</h4>
                </div>
                <div class="modal-body" id="modal_cuerpo">
                ...
                </div>
                <div class="modal-footer"  id="modal_pie">
                  
                  <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>&nbsp;
                 
                </div>

              </div>
            </div>
          </div> 
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
      <script>
        actualizar_carro_factmultiple();


function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
function nl2br (str, is_xhtml) {
  var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>'; // Adjust comment to avoid issue on phpjs.org display

  return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}
function verificar_seleccionados(idevento){
    var tmpidevento; 
    <?php
    $idevento = intval($_GET["idevento"]);
?>
            tmpidevento = idevento;

        var parametros = {
            "tmpidevento" :tmpidevento,
            "tmpagregar" :1
        };
        
        $.ajax({ 
            data:  parametros,
            url:   'catering/cat_eventos_factu.php',
            type:  'post',
            beforeSend: function () {

            },
            success:  function (response) {
                if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
                    if(obj.valido == 'S'){
                        // hacer algo
                        actualizar_carro_factmultiple();

                    }else{
                        $("#modal_titulo").html("Atencion");
                        $("#modal_cuerpo").html(nl2br(obj.errores));      
                        $("#modal_ventana").modal("show");    
                    }
                }else{
                    alert(response);    
                }
            }
    }); 
  }
  function redirect_factu(tmpidevento){
 window.location = "facturar_catering/facturar_catering.php?idevento="+tmpidevento
 }
 function redirect_anticipos(tmpidevento){
 window.location = "pagos_afavor_adh_add.php?idevento="+tmpidevento
 }

  function verificar_saldo_evento(idevento,tipo){
    var tmpidevento; 
    <?php
$idevento = $_GET["idevento"];
?>
            tmpidevento = idevento;

        var parametros = {
            "tmpidevento" :tmpidevento,
            "tmpagregar" :0
        };
        
        $.ajax({ 
            data:  parametros,
            url:   'catering/cat_eventos_factu.php',
            type:  'post',
            beforeSend: function () {

            },
            success:  function (response) {
                if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
                    if(obj.valido == 'S'){

                        // hacer algo
                    if(tipo==1){
                        redirect_anticipos(tmpidevento);

                    }else{

                        redirect_factu(tmpidevento);
                    }    

                    }else{
                        $("#modal_titulo").html("Atencion");
                        $("#modal_cuerpo").html(nl2br(obj.errores));      
                        $("#modal_ventana").modal("show");    
                    }
                }else{
                    alert(response);    
                }
            }
    }); 
  }
  function actualizar_carro_factmultiple(){
    $.ajax({
            url:   'facturar_catering_car_tmp_factu_desdepedi.php',
            type:  'get',
            beforeSend: function () {

            },
            success:  function (response) {
                $("#resultados").html(response);
                consultar_eventos_mini_factu();
            }
    }); 
  }

  function consultar_eventos_mini_factu(){
    $.ajax({
            url:   'facturar_catering_car_tmp_factu_desdepedi_consulta.php',
            type:  'get',
            beforeSend: function () {

            },
            success:  function (response) {
                if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
        
                    if(obj.valido == 'S'){
                        $("#factsel").attr("disabled", false);

                    }else{
                        $("#factsel").attr("disabled", true);
                    }
                }else{
                    alert(response);    
                }
            }
    }); 
  }
function redirect() {
    window.location.href = "facturar_catering/facturar_catering_all.php";
}

  function consultar_eventos_mini_factu_valida(){

    $.ajax({
            url:   'facturar_catering_car_tmp_factu_desdepedi_consulta.php',
            type:  'get',
            beforeSend: function () {

            },
            success:  function (response) {
                if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
        
                    if(obj.valido == 'S'){
                     redirect();

                    }else{
                        alert("No existe ningun evento a facturar, favor seleccióne al menos un evento.");
                        //$("#modal_titulo").html("Atencion");
                        //$("#modal_cuerpo").html(nl2br("No existe ningun evento a Facturar"));      
                        //$("#modal_ventana").modal("show");     
                    
                    }
                }else{
                    alert(response);    
                }
            }
    }); 
  }

  function consultar_eventos_mini_stock_valida(){
    var existenpedidos;
    existenpedidos = <?php echo $existenpedidos; ?>;
    if(existenpedidos>0){
        alert("Van a ser descontados del stock "+existenpedidos+" pedidos con sus respectivos detalles de Productos ");
    }else{
        alert("No existe ningun pedido a facturar, favor seleccióne al menos un evento.");
    }            
}

  function borrar_carrito_evento(idevento){
            var parametros = {
                "id" :idevento,
            };
            $.ajax({
                    data:  parametros,
                    url:   'facturar_catering_car_tmp_factu_desdepedi_del.php',
                    type:  'post',
                    beforeSend: function () {

                    },
                    success:  function (response) {
                        actualizar_carro_factmultiple();
                    }
            }); 
      }
      function cambiar_estado(valor1,valor2){
            $("#modal_titulo").html("Cambiar estado de Pedido");
            var parametros = {
                "valor1" :valor1,
                "valor2" :valor2
            };
            $.ajax({
                    data:  parametros,
                    url:   'cat_mini_cambiar_estado.php',
                    type:  'post',
                    beforeSend: function () {

                    },
                    success:  function (response) {
                        $("#modal_cuerpo").html(response);      
                        $("#modal_ventana").modal("show");
                    }
            }); 
      }
      function mostrar_anulados(){
            $("#modal_titulo").html("Ultimos pedidos anulados");
            var parametros = {
                
            };
            $.ajax({
                    data:  parametros,
                    url:   'cat_mini_ver_anulados.php',
                    type:  'post',
                    beforeSend: function () {

                    },
                    success:  function (response) {
                        $("#modal_cuerpo").html(response);      
                        $("#modal_ventana").modal("show");
                    }
            }); 
      }
      function confirmar_nuevamente(){
          
          $("#pf").show();
          
      }
    function proceder_final(pedido,idt){
            var estadonew=$("#idsele").val();
              $("#modal_titulo").html("Cambiar estado de Pedido");
                var parametros = {
                    "pedido" :pedido,
                    "cambiar":1,
                    "estado_nuevo": estadonew,
                    "idt":idt
                };
                $.ajax({
                        data:  parametros,
                        url:   'cat_mini_cambiar_estado.php',
                        type:  'post',
                        beforeSend: function () {

                        },
                        success:  function (response) {
                            if(response=='LISTO'){  
                                $("#modal_ventana").modal("hide");
                            }
                        }
                }); 
          
          
      }
      function eliminar_pedido(idreg){
          
          $("#modal_ventana").modal("show");
          $("#modal_titulo").html("Anular Pedido?");
          var mensaje='Esta seguro que desea anular el siguiente pedido?<br /><br /><br /><div class="clearfix"></div>';
          var botones_ini='<div class="form-group"><div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">';
          var boton_anular='<button type="button" class="btn btn-danger" onMouseUp="document.location.href=\'cat_pedidos_new.php?edl='+idreg+'&sc=99\'"><span class="fa fa-trash-o"></span> Borrar</button>';
          var boton_cancelar='<button type="button" class="btn btn-primary" onMouseUp="$(\'#modal_ventana\').modal(\'hide\');"><span class="fa fa-ban"></span> Cancelar</button></div></div>';
          var botones_fin='</div></div><div class="clearfix"></div><br /><br />';
          
          $("#modal_cuerpo").html(mensaje+botones_ini+boton_anular+boton_cancelar+botones_fin);
          

      }
      function abrirnuevo(){
          
         window.open("cat_pedidos_new.php","_self"); 
          
          
      }
       function recargarpag(){
          
         window.open("cat_adm_pedidos_new.php","_self"); 
          
          
      }
      function mostrarpanel(regunico){
          var parametros = {
                "valorunico" :regunico
            };
            $.ajax({
                    data:  parametros,
                    url:   'cat_mini_pedido_adm.php',
                    type:  'post',
                    beforeSend: function () {

                    },
                    success:  function (response) {
                        $("#acciones_cuerpo").html(response);
                        $("#acciones").modal("show");
                    }
            });
      }

      
      function cargar(idinsumo,cpr,idreg){
          
          var valido='';
          valido='S';
          var errores=''; 
          var obssnew=$("#obs_"+idinsumo).val();
          var fechasegura=$("#fechaseg_"+idreg).val();
         // alert("Fechaaseg"+fechasegura);
          var fechaprod=$("#fechap_"+idinsumo).val();
          var horaprod=$("#horapp_"+idinsumo).val();
          var comentario_cpr=$("#comentacentral_"+idinsumo).val();
          if (fechaprod==''){
              errores=errores+"* Debe indicar la fecha de produccion";
              valido='N';
          }
           if (horaprod==''){
              errores=errores+"* Debe indicar la hora de produccion";
              //valido='N';
          }
          var d1 = Date.parse("'"+fechasegura+"'");
         
          var d2 = Date.parse(fechaprod);
          if (d1 < d2) {
              errores=errores+"* La fecha de produccion no debe superar al evento";
            //valido='N';
           }
          // if (valido=='S'){
               var parametros = {
                   "idinsumo" : idinsumo,
                    "cpr" :cpr,
                    "obsn"          : obssnew,
                    "fechasegura" : fechasegura,
                   "fechaprodu"  :fechaprod,
                   "horaprod"    :horaprod,
                   "comentario_cpr" : comentario_cpr,
                   "idunicopedido": idreg
                };
                $.ajax({
                        data:  parametros,
                        url:   'cat_udpmini.php',
                        type:  'post',
                        beforeSend: function () {

                        },
                        success:  function (response) {
                            //alert(response);
                            $("#actualizadatosg").html(response);
                            if(response==1){
                                $("#acciones").modal("hide");
                            }

                        }
                });
          // } else {
             //  alert('VALIDO:'+valido);
            //   alert(errores);
           //}
          
          
      }
      function busca_cliente(idcampo,tipo){
            var direccionurl='busqueda_cliente_cat.php';
            $("#procede_bt").hide();
            var sigue='S';
            //sigue=controlar_req();
            //alert(sigue);
            if (sigue=='S'){
                var parametros = {
                  "m" : '1',
                  "idcampo" : idcampo,
                  "tipo"    : tipo
                };
                $.ajax({          
                    data:  parametros,
                    url:   direccionurl,
                    type:  'post',
                    beforeSend: function () {
                        $('#modal_ventana').modal('show');
                        $("#modal_titulo").html('Busqueda de Cliente');
                        $("#modal_cuerpo").html('Cargando...');                
                    },
                    success:  function (response) {
                        $("#modal_cuerpo").html(response);
                    }
                });
            } else {
                //Alert('Debe indicar fecha y hora del evento');
            }
        }
    function busca_cliente_res(tipo,idcampo,tipo){
    //alert(tipo);
    $("#procede_bt").hide();
    var ruc = $("#ruc_cat").val();
    var razon_social = $("#razon_social_cat").val();
    var fantasia = $("#fantasia_cat").val();
    var documento = $("#documento_cat").val();
    var email = $("#email_cat").val();
    if(tipo == 'ruc'){
        razon_social = '';
        fantasia = '';
        documento = '';
        $("#razon_social_cat").val('');
        $("#fantasia_cat").val('');
        $("#documento_cat").val('');
    }
    if(tipo == 'razon_social'){
        ruc = '';
        fantasia = '';
        documento = '';
        $("#ruc_cat").val('');
        $("#fantasia_cat").val('');
        $("#documento_cat").val('');
    }
    if(tipo == 'fantasia'){
        ruc = '';
        razon_social = '';
        documento = '';
        $("#ruc_cat").val('');
        $("#razon_social_cat").val('');
        $("#documento_cat").val('');
    }
    if(tipo == 'documento'){
        ruc = '';
        razon_social = '';
        fantasia = '';
        $("#ruc_cat").val('');
        $("#razon_social_cat").val('');
        $("#fantasia_cat").val('');
    }
    if(tipo == 'email'){
        ruc = '';
        razon_social = '';
        fantasia = '';
        $("#ruc_cat").val('');
        $("#razon_social_cat").val('');
        $("#fantasia_cat").val('');
    }
    var direccionurl='busqueda_cliente_res_cat.php';        
    var parametros = {
      "ruc"            : ruc,
      "razon_social"   : razon_social,
      "fantasia"          : fantasia,
      "documento"      : documento,
      "idcampo"        : idcampo,
      "tipo"           :tipo
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $("#busqueda_cli").html('Cargando...');                
        },
        success:  function (response) {
            $("#busqueda_cli").html(response);
        }
    });
}
function seleccionar_item(id,idcampo,tipo){
    var valor = $("#idsucursal_clie_"+id).val();
    //alert(valor);
    if(IsJsonString(valor)){
        var obj = jQuery.parseJSON(valor);
        var idcliente = obj.idcliente;
        var idsucursal_clie = obj.idsucursal_clie;
        var idcampo = obj.idcampo;
        var ruc = obj.ruc;
        var razon_social = obj.razon_social;
        var nombres = obj.nombres;
        var apellidos = obj.apellidos;
        
        $("#cliente_ped").val(obj.idcliente+'|'+obj.idsucursal_clie+'|'+'|'+obj.sucuvirtual);
        $("#cliente_fac").val(obj.idcliente+'|'+obj.idsucursal_clie+'|'+obj.razon_social);        
        //$("#"+idcampo).val(idcliente+'|'+idsucursal_clie+'|'+razon_social);
        $('#modal_ventana').modal('hide');
        var cli_ped = $("#cliente_ped").val();
        
        


    }else{
        alert("Error: "+valor);    
    }

}
      </script>
<?php require_once("includes/footer_gen.php"); ?>
      <script>
    <?php if (intval($_REQUEST['idr']) > 0) { ?>       
      $(document).ready(function() {
                       
                        mostrarpanel(<?php echo antixss($_REQUEST['idr']) ?>);
                    
            }); 
            
          <?php }?>
         
      </script>
  </body>
</html>
