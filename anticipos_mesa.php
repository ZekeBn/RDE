 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "216";
require_once("includes/rsusuario.php");

$idmesa = intval($_GET['idmesa']);
if ($idmesa == 0) {
    echo "No se indico el idmesa.";
    exit;
}
$consulta = "
select mesas.numero_mesa, salon.idsucursal, salon.nombre as salon
from mesas 
inner join salon on salon.idsalon = mesas.idsalon
where
idmesa=$idmesa
limit 1
";
$rsmes = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if ($idsucursal <> $rsmes->fields['idsucursal']) {
    echo "La mesa no corresponde a la misma sucursal en la cual esta logueado tu usuario.";
    exit;
}

if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-d");
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}
$idcliente = intval($_GET['idcliente']);
if ($idcliente > 0) {
    $whereadd .= " and pagos_afavor_adh.idcliente = $idcliente ";
}

$consulta = "
select pagos_afavor_adh.*, cliente.razon_social, cuentas_clientes_pagos_cab.recibo,
(select usuario from usuarios where pagos_afavor_adh.idusuario = usuarios.idusu) as registrado_por,
(select nomape from adherentes where  idadherente = pagos_afavor_adh.idadherente) as adherente,
(select nombre_servicio from servicio_comida where idserviciocom = pagos_afavor_adh.idserviciocom) as nombre_servicio
from pagos_afavor_adh 
inner join cliente on cliente.idcliente = pagos_afavor_adh.idcliente
inner join cuentas_clientes_pagos_cab on cuentas_clientes_pagos_cab.idpago_afavor = pagos_afavor_adh.idpago_afavor
where 
 pagos_afavor_adh.estado = 1 
and date(pagos_afavor_adh.fechahora) >= '$desde'
and date(pagos_afavor_adh.fechahora) <= '$hasta'
 $whereadd
order by date(pagos_afavor_adh.fechahora) asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

if ($idcliente > 0) {
    // consulta a la tabla
    $consulta = "
    select * 
    from cliente 
    where 
    idcliente = $idcliente
    and estado = 1
    limit 1
    ";
    $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcliente = intval($rscli->fields['idcliente']);
}


?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script>
function alerta_modal(titulo,mensaje){
    $('#modal_ventana').modal('show');
    $("#modal_titulo").html(titulo);
    $("#modal_cuerpo").html(mensaje);

    
}
function busca_cliente(){
    var direccionurl='busqueda_cliente_anticipo_mesa.php';        
    var parametros = {
      "m" : '1'          
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $('#modal_ventana').modal('show');
            $("#modal_titulo").html('Busqueda de Cliente');
            $("#modal_cuerpo").html('Cargando...');    
            $("#idcliente").val('');            
        },
        success:  function (response) {
            $("#modal_cuerpo").html(response);
        }
    });
    
}
function busca_cliente_res(tipo){
    var ruc = $("#ruc").val();
    var razon_social = $("#razon_social").val();
    if(tipo == 'ruc'){
        razon_social = '';
        $("#razon_social").val('');
    }
    if(tipo == 'razon_social'){
        ruc = '';
        $("#ruc").val('');
    }
    var direccionurl='busqueda_cliente_anticipo_mesa_res.php';        
    var parametros = {
      "ruc"            : ruc,
      "razon_social"   : razon_social
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
function seleccionar_item(idcliente,descricion){
    $("#idcliente").val(idcliente+' - '+descricion);
    $('#modal_ventana').modal('hide');
    //document.location.href='pagos_afavor_adh_add.php?id='+idcliente;
}

</script>
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
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Aplicar Anticipo a Mesa #<?php echo $rsmes->fields['idsucursal'] ?> Salon <?php echo antixss($rsmes->fields['salon']) ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<form id="form1" name="form1" method="get" action="">

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Desde *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="desde" id="desde" value="<?php  echo $desde; ?>" placeholder="Desde" class="form-control" required />                    

    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Hasta *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="hasta" id="hasta" value="<?php echo $hasta; ?>" placeholder="Hasta" class="form-control" required />                    

    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cliente *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="idcliente" id="idcliente" value="<?php  if (intval($idcliente) > 0) {
        echo antixss($rscli->fields['idcliente'].' - '.$rscli->fields['razon_social']);
    } ?>" placeholder="Click para buscar..." class="form-control" onMouseUp="busca_cliente()"  required readonly />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Mesa *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="mesa" id="mesa" value="Mesa: <?php echo $rsmes->fields['idsucursal'] ?> | Salon: <?php echo antixss($rsmes->fields['salon']) ?>" class="form-control"  required readonly />                    
    <input type="hidden" name="idmesa" id="idmesa" value="<?php  echo $idmesa; ?>" class="form-control"  required readonly />                    
    </div>
</div>


<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Buscar</button>

        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Id Anticipo</th>
            <th align="center">Recibo</th>
            <th align="center">Fecha</th>
            <th align="center">Monto</th>
            <th align="center">Saldo</th>
            <th align="center">Cliente</th>
            <th align="center">Adherente</th>
            <th align="center">Servicio</th>
            <th align="center">Registrado por</th>
            <th align="center">Registrado el</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="anticipos_mesa_aplica.php?id=<?php echo $rs->fields['idpago_afavor']; ?>&idmesa=<?php  echo $idmesa; ?>" class="btn btn-sm btn-success" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-check-square-o"></span> Aplicar</a>
                </div>

            </td>
            <td align="center"><?php echo intval($rs->fields['idpago_afavor']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['recibo']); ?></td>
            <td align="center"><?php if ($rs->fields['fechahora'] != "") {
                echo date("d/m/Y", strtotime($rs->fields['fechahora']));
            }  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['monto']);  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['saldo']);  ?></td>
            <td align="center"><?php echo antixss($rs->fields['razon_social']); ?> [<?php echo intval($rs->fields['idcliente']); ?>]</td>
            <td align="center"><?php if (intval($rs->fields['idadherente']) > 0) {
                echo antixss($rs->fields['adherente']); ?> [<?php echo intval($rs->fields['idadherente']); ?>]<?php } ?></td>
            <td align="center"><?php if (intval($rs->fields['idserviciocom']) > 0) {
                echo antixss($rs->fields['nombre_servicio']); ?> [<?php echo intval($rs->fields['idserviciocom']); ?>]<?php } ?></td>

            <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
            <td align="center"><?php if ($rs->fields['registrado_el'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
            }  ?></td>
        </tr>
<?php
$monto_acum += $rs->fields['monto'];
    $saldo_acum += $rs->fields['saldo'];

    $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
      <tfoot>
        <tr>
            <td>Totales</td>
            <td></td>
            <td></td>
            <td></td>
            <td align="center"><?php echo formatomoneda($monto_acum); ?></td>
            <td align="center"><?php echo formatomoneda($saldo_acum); ?></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>


        </tr>
      </tfoot>
    </table>
</div>
<br />


<br /><br />

                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            

            
        <!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
                   <h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
                Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        
        </div>
    </div>
</div>
        <!-- POPUP DE MODAL OCULTO -->        
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
