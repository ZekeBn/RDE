<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_compras.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");

$idtran = intval($_GET['id']);


if ($idtran == 0) {
    header("location: gest_reg_compras_resto_new.php");
    exit;
}
$consulta = "
select *,
(select usuario from usuarios where tmpcompras.registrado_por = usuarios.idusu) as registrado_por,
(select nombre from sucursales where idsucu = tmpcompras.sucursal) as sucursal,
(select nombre from proveedores where proveedores.idproveedor = tmpcompras.proveedor) as proveedor,
(select tipocompra from tipocompra where idtipocompra = tmpcompras.tipocompra) as tipocompra
from tmpcompras 
where 
 estado = 1 
 and idtran = $idtran
order by idtran asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtran = intval($rs->fields['idtran']);
if ($idtran == 0) {
    header("location: gest_reg_compras_resto_new.php");
    exit;
}


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // recibe parametros
    $sucursal = antisqlinyeccion($_POST['sucursal'], "int");

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
    // control de formularios, seguridad para evitar doble envio y ataques via bots
    $parametros_array = [
        "get_idtran" => $_GET['id'],
        "delete" => 1,
        "idempresa" => $idempresa
    ];
    $respuesta = validar_cabecera_compra($parametros_array);

    // si todo es correcto actualiza
    if ($valido == "S" && $respuesta["valido"] == "S") {
        borrar_cabecera_compra($parametros_array);
        header("location: gest_reg_compras_resto_new.php");
        exit;
    } else {
        $errores .= $respuesta['errores'];
    }

}


// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
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
                    <h2>Borrar Compra</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Idtran</th>
			<th align="center">Proveedor</th>
			<th align="center">Fecha compra</th>
			<th align="center">Factura</th>
			<th align="center">Condicion</th>
			<th align="center">Monto factura</th>
			<th align="center">Orden Num.</th>
			<th align="center">Sucursal</th>
            <th align="center">Registrado por</th>
            <th align="center">Registrado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php //while(!$rs->EOF){?>
		<tr>

			<td align="right"><?php echo formatomoneda($rs->fields['idtran']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['proveedor']); ?></td>
			<td align="center"><?php if ($rs->fields['fecha_compra'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_compra']));
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['facturacompra']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['tipocompra']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['monto_factura']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['ocnum']); ?></td>
			<td align="right"><?php echo antixss($rs->fields['sucursal']);  ?></td>
            <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['registrado_el']));
			} ?></td>
		</tr>
<?php //$rs->MoveNext(); } //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />

<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idtran *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="" name="idtran" id="idtran" value="<?php  if (isset($_POST['idtran'])) {
	    echo htmlentities($_POST['idtran']);
	} else {
	    echo htmlentities($rs->fields['idtran']);
	}?>" placeholder="Idtran" class="form-control" required="required" readonly="readonly" disabled="disabled" />                    
	</div>
</div>



<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
			<button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
			
			<button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_reg_compras_resto_new.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>


  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>


                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
