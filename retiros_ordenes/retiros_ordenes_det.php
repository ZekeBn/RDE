<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_devolucion.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "613";

$dirsup = "S";
require_once("../includes/rsusuario.php");
$boton_editar_deposito = 1;
$idorden_retiro = $_GET['id'];

$consulta = "
SELECT retiros_ordenes.idorden_retiro, devolucion.idventa, ventas.fecha, ventas.factura,ventas.venta_registrada_el,
tipo_transaccion_set.tipo_transaccion_set, ventas.total_venta,
(select usuario from usuarios where ventas.registrado_por = usuarios.idusu) as registrado_por
from retiros_ordenes 
INNER JOIN devolucion on devolucion.iddevolucion = retiros_ordenes.iddevolucion
INNER JOIN ventas on ventas.idventa = devolucion.idventa
INNER JOIN tipo_transaccion_set on tipo_transaccion_set.idtipotranset = ventas.idtipotranset
where 
retiros_ordenes.idorden_retiro = $idorden_retiro 
";
$rs2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

function format_estado($estado)
{
    if ($estado == 1) {
        return "Pendiente";
    }
    if ($estado == 3) {
        return "Finalizado";
    }

}


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";


    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }

    $_SESSION['form_control'] = md5(rand());
    // echo var_dump($_POST);exit;

    $iddeposito_select = intval($_POST['iddeposito']);
    if ($iddeposito_select == 0) {

        $consulta = "
      SELECT COALESCE(SUM(devolucion_det.iddevolucion_det),0) as depositos
      FROM retiros_ordenes 
      INNER JOIN devolucion on devolucion.iddevolucion = retiros_ordenes.iddevolucion
      INNER JOIN devolucion_det on devolucion_det.iddevolucion = devolucion.iddevolucion
      WHERE 
      retiros_ordenes.idorden_retiro=$idorden_retiro
      and devolucion.estado = 3
      and (devolucion_det.iddeposito is null or devolucion_det.iddeposito = 0) 
      ";

        $rs_depo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $deposito_faltante = intval($rs_depo->fields['depositos']);
        if ($deposito_faltante > 0) {
            $errores .= "- Existen Articulos sin depositos asignados.<br />";
            $valido = "N";
        }

    }

    $parametros_array = [
      "iddeposito" => $_POST['iddeposito'],
      "idorden_retiro" => $idorden_retiro,
      "idempresa" => $idempresa
    ];
    if ($valido == "S") {
        // echo json_encode($parametros_array);exit;
        devolver_producto($parametros_array);
        //cerrando orden
        $update = "UPDATE retiros_ordenes 
              SET estado = 3,
              modificado_el='$ahora'
              WHERE idorden_retiro = $idorden_retiro
              ";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        header("location: retiros_ordenes.php");
        exit;
    }




}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <script>
    function editar_deposito_compra(event,iddevolucion_det,idorden_retiro){
			event.preventDefault();
			var parametros = {
					"iddevolucion_det"   	  : iddevolucion_det,
					"idorden_retiro"		    : idorden_retiro
			};
      console.log(parametros);
			$.ajax({		  
				data:  parametros,
				url:   'editar_deposito_devolucion_modal.php',
				type:  'post',
				cache: false,
				timeout: 3000,  // I chose 3 secs for kicks: 3000
				crossDomain: true,
				beforeSend: function () {	
									
				},
				success:  function (response) {
          console.log(response);
          
          alerta_modal("Deposito de Articulo",response)
					
				}
			});
    }

    function alerta_modal(titulo,mensaje){
      $('#modal_ventana').modal('show');
      $("#modal_titulo").html(titulo);
      $("#modal_cuerpo").html(mensaje);
    }

  </script>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Retiros de Devoluciones</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	

 




<?php if (trim($errores) != "") { ?>
  <div class="alert alert-danger alert-dismissible fade in" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">×</span>
    </button>
    <strong>Errores:</strong>
    <br />
    <?php echo $errores; ?>
  </div>
<?php } ?>


<div class="table-responsive">
    <h2>Venta</h2>
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">Idorden retiro</th>
			<th align="center">Id Venta</th>
			<th align="center">Fecha Venta</th>
			<th align="center">Factura</th>
			<th align="center">Condicion</th>
			<th align="center">Monto Factura</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>
		</tr>
	  </thead>
	  <tbody>
      <?php while (!$rs2->EOF) { ?>
          <tr>
            
            <td align="center"><?php echo intval($rs2->fields['idorden_retiro']); ?></td>
            <td align="center"><?php echo intval($rs2->fields['idventa']); ?></td>
            <td align="center"><?php echo($rs2->fields['fecha']); ?></td>
            <td align="center"><?php echo antixss($rs2->fields['factura']); ?></td>
            <td align="center"><?php echo antixss($rs2->fields['tipo_transaccion_set']); ?></td>
            <td align="center"><?php echo formatomoneda($rs2->fields['total_venta']); ?></td>
            <td align="center"><?php echo antixss($rs2->fields['registrado_por']); ?></td>
            <td align="center"><?php echo antixss($rs2->fields['venta_registrada_el']); ?></td>
          </tr>
      <?php $rs2->MoveNext();
      } //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />


<div id="articulo_retirar_list">
  <?php require_once("articulos_retirar_list.php"); ?>
</div>
<br />

<div class="col-md-12">
  <div class="alert alert-info" role="alert">
    Atencion: El ingreso de los art&iacute;culos a dep&oacute;sitos asignados, se encuentra activo.Si desea dar ingreso a un dep&oacute;sito diferente,<br />
    seleccione de la lista desplegable , de esta forma,los art&iacute;culos ser&aacute;n ingresados al dep&oacute;sito seleccionado, y no a los establecios previamente.
  </div>
  <form id="form1" name="form1" method="post" action="">

    <div style="display: grid;place-items: center;">
      <div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Deposito </label>
        <div class="col-md-9 col-sm-9 col-xs-12">
          <?php
            // consulta
            $consulta = "
            SELECT iddeposito, descripcion
            FROM gest_depositos
            where
            estado = 1
            and tiposala <> 3
            order by descripcion asc
            ";
// valor seleccionado
if (isset($_POST['iddeposito'])) {
    $value_selected = htmlentities($_POST['iddeposito']);
} else {
    $value_selected = htmlentities($rs->fields['iddeposito']);
}
// parametros
$parametros_array = [
  'nombre_campo' => 'iddeposito',
  'id_campo' => 'iddeposito',

  'nombre_campo_bd' => 'descripcion',
  'id_campo_bd' => 'iddeposito',

  'value_selected' => $value_selected,

  'pricampo_name' => 'Seleccionar...',
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
    </div>
    <div class="form-group">
      <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-4">
        <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
        <button type="button" class="btn btn-primary" onMouseUp="document.location.href='retiros_ordenes.php'"><span class="fa fa-ban"></span> Cancelar</button>
      </div>
    </div>

    <div class="clearfix"></div>
    <input type="hidden" name="MM_update" value="form1" />
    <input type="hidden" name="idorden_retiro" value="<?php echo htmlentities($idorden_retiro); ?>" />
    <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
    <br />
  </form>
</div>

<div class="clearfix"></div>



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
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
