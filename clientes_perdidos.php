<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "11";
$submodulo = "607";
require_once("includes/rsusuario.php");


$valido = 'N';

if (isset($_GET['MM_search']) && $_GET['MM_search'] == 'form1') {

    $valido = 'S';
    $errores .= '';

    $dias_sincompra = intval($_GET['dias_sincompra']);
    $dias_compra = intval($_GET['dias_compra']);

    if ($dias_sincompra >= $dias_compra) {
        $valido = 'N';
        $errores .= '- Dias sin compras no puede ser superior o igual a dias compra.';
    }

    if ($valido == 'S') {

        $consulta = "
    SELECT c.idcliente, c.ruc, c.razon_social, c.fantasia,
          MAX(date(v2.fecha)) AS fecha_ultima_venta
    FROM cliente c
    LEFT JOIN ventas v2 ON c.idcliente = v2.idcliente
    WHERE c.estado <> 6
    AND c.idcliente NOT IN (
        SELECT DISTINCT v.idcliente
        FROM ventas v
        WHERE v.estado <> 6
        AND DATE(v.fecha) >= DATE(NOW()) - INTERVAL  $dias_sincompra DAY
    )
    AND c.idcliente IN (
        SELECT DISTINCT v2.idcliente
        FROM ventas v2
        WHERE v2.estado <> 6
        AND DATE(v2.fecha) >= DATE(NOW()) - INTERVAL $dias_compra DAY
    )
    GROUP BY   c.idcliente, c.ruc, c.razon_social
    order by fecha_ultima_venta desc
    limit 1000
    ";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        //echo $consulta;
    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());


?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
    <script>
function detalle_cliente(idcliente){

	var direccionurl='clientes_perdidos_det.php';	
	var parametros = {
	  "idcliente" : idcliente
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
      $('#modal_ventana').modal('show');
      $("#modal_titulo").html('Detalle del Cliente');
      $("#modal_cuerpo").html('Cargando...');
		},
		success:  function (response, textStatus, xhr) {
			if(xhr.status === 200){
        $("#modal_cuerpo").html(response);
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
		}
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
	});
	
}
function detallar(idventa){
  $("#idventa_"+idventa).toggle();
  $("#sp_"+idventa).toggleClass('fa fa-chevron-down','fa fa-chevron-up');
  $("#sp_"+idventa).toggleClass('fa fa-chevron-up','fa fa-chevron-down');
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
                    <h2>Clientes Perdidos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

                  <?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="get" action="">

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Compro en los ultimos (dias) *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="numeric" name="dias_compra" id="dias_compra" value="<?php  if (isset($_GET['dias_compra'])) {
	    echo intval($_GET['dias_compra']);
	} else {
	    echo '365';
	}?>" placeholder="dias compra" class="form-control" required="required" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">No realizo compras en los ultimos (dias) *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="numeric" name="dias_sincompra" id="dias_sincompra" value="<?php  if (isset($_GET['dias_sincompra'])) {
	    echo intval($_GET['dias_sincompra']);
	} else {
	    echo '60';
	}?>" placeholder="dias sin compra" class="form-control" required="required" />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Generar Reporte</button>

        </div>
    </div>

  <input type="hidden" name="MM_search" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />

<?php if (isset($_GET['MM_search']) && $_GET['MM_search'] == 'form1' && $valido == 'S') { ?>

<hr />

<strong>Viendo 1000 registros:</strong> Clientes que compraron al menos 1 vez en los ultimos <strong><?php echo $dias_compra; ?></strong> dias, pero no volvieron a comprar en los ultimos <strong><?php echo $dias_sincompra; ?></strong> dias.<br /><br />
                  <div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
        <thead>
            <tr>
                <th></th>
                <th align="center">ID Cliente</th>
                <th align="center">RUC</th>
                <th align="center">Razón Social</th>
                <th align="center">Fantasía</th>
                <th align="center">Fecha Última Venta</th>

            </tr>
        </thead>
        <tbody>
            <?php while (!$rs->EOF) { ?>
            <tr>
                <td>
                    <div class="btn-group">
                        <a href="javascript:detalle_cliente(<?php echo intval($rs->fields['idcliente']); ?>);void(0);" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right" data-original-title="Detalle"><span class="fa fa-search"></span></a>
                        
                    </div>
                </td>
                <td align="center"><?php echo intval($rs->fields['idcliente']); ?></td>
                <td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
                <td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
                <td align="center"><?php echo antixss($rs->fields['fantasia']); ?></td>
                <td align="center"><?php if ($rs->fields['fecha_ultima_venta'] != "") {
                    echo date("d/m/Y", strtotime($rs->fields['fecha_ultima_venta']));
                }  ?></td>
                
            </tr>
            <?php $rs->MoveNext();
            } ?>
        </tbody>
    </table>
</div>
<br />
<?php } ?>

<br /><br />

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
