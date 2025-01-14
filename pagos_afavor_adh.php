 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "430";
require_once("includes/rsusuario.php");

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
select *,
(select usuario from usuarios where pagos_afavor_adh.idusuario = usuarios.idusu) as registrado_por,
(select recibo from cuentas_clientes_pagos_cab where pagos_afavor_adh.idpago_afavor = cuentas_clientes_pagos_cab.idpago_afavor) as recibo
from pagos_afavor_adh 
inner join cliente on cliente.idcliente = pagos_afavor_adh.idcliente
where 
pagos_afavor_adh.estado <> 6
and date(fechahora) >= '$desde'
and date(fechahora) <= '$hasta'
$whereadd
order by idpago_afavor desc

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
    var direccionurl='busqueda_cliente_anticipo.php';        
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
    var direccionurl='busqueda_cliente_anticipo_res.php';        
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
                    <h2>Anticipos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p><a href="pagos_afavor_adh_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a></p>
<hr />


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
            <th align="center">Cliente</th>
            <th align="center">Recibo</th>
            <th align="center">Fecha Anticipo</th>
            <th align="center">Monto</th>
            <th align="center">Saldo</th>


        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="pagos_afavor_adh_det.php?id=<?php echo $rs->fields['idpago_afavor']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>

                </div>

            </td>
            <td align="left"><?php echo antixss($rs->fields['razon_social']); ?> [<?php echo intval($rs->fields['idcliente']); ?>]</td>
            <td align="center"><?php echo antixss($rs->fields['recibo']); ?></td>
            <td align="center"><?php if ($rs->fields['fechahora'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['fechahora']));
            }  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['monto']);  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['saldo']);  ?></td>


        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />




                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->
        
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

        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
