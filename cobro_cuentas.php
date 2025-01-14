<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "278";
require_once("includes/rsusuario.php");

//echo "Modulo en Actualizacion"; exit;

if (isset($_GET['e']) && $_GET['e'] == '1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    /*if($_SESSION['form_control'] != $_POST['form_control']){
        $errores.="- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido="N";
    }
    if(trim($_POST['form_control']) == ''){
        $errores.="- Control del formularios no activado.<br />";
        $valido="N";
    }
    $_SESSION['form_control'] = md5(rand());*/
    // control de formularios, seguridad para evitar doble envio y ataques via bots

    $ruc = antisqlinyeccion($_GET['ruc'], "text");
    $razon_social = antisqlinyeccion($_GET['razon_social'], "like");
    $documento = antisqlinyeccion($_GET['documento'], "text");
    $fantasia = antisqlinyeccion($_GET['fantasia'], "like");

    if (trim($_GET['ruc']) != '') {
        $whereadd .= " and ruc = $ruc ";
    }
    if (trim($_GET['razon_social']) != '') {
        $whereadd .= " and razon_social like '%$razon_social%' ";
    }
    if (trim($_GET['documento']) != '') {
        $whereadd .= " and documento = $documento ";
    }
    if (trim($_GET['fantasia']) != '') {
        $whereadd .= " and fantasia like '%$fantasia%' ";
    }


    // si todo es correcto
    if ($valido == "S") {

        $consulta = "
		select * 
		from cliente 
		where 
		estado = 1
		$whereadd
		limit 50
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }

}



?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
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
                    <h2>Cobrar cuentas a credito</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="get" action="">




<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Ruc </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="ruc" id="ruc" value="<?php  if (isset($_GET['ruc'])) {
	    echo htmlentities($_GET['ruc']);
	}?>" placeholder="Ruc" class="form-control"  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Razon social </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="razon_social" id="razon_social" value="<?php  if (isset($_GET['razon_social'])) {
	    echo htmlentities($_GET['razon_social']);
	}?>" placeholder="Razon social" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Documento </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="documento" id="documento" value="<?php  if (isset($_GET['documento'])) {
	    echo htmlentities($_GET['documento']);
	}?>" placeholder="Documento" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre Fantasia </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="fantasia" id="fantasia" value="<?php  if (isset($_GET['fantasia'])) {
	    echo htmlentities($_GET['fantasia']);
	}?>" placeholder="Nombre de Fantasia" class="form-control"  />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Buscar</button>

        </div>
    </div>


  <input type="hidden" name="e" value="1" />
<br />
</form>
<div class="clearfix"></div>
<br /><hr /><br />


<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idcliente</th>
			<th align="center">Ruc</th>
			<th align="center">Razon social</th>
			<th align="center">Nombre</th>
			<th align="center">Apellido</th>
			<th align="center">Documento</th>
			<th align="center">Nombre Fantasia</th>

			<th align="center">Linea Credito</th>
            <th align="center">Saldo Linea</th>
			<th align="center">Linea mensual</th>
			<th align="center">Saldo mensual</th>
		</tr>
	  </thead>
	  <tbody>
<?php
if ($rs->fields['idcliente'] > 0) {
    while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="cobro_cuentas_det.php?id=<?php echo $rs->fields['idcliente']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idcliente']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['nombre']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['apellido']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['documento']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['fantasia']); ?></td>

			<td align="center"><?php echo formatomoneda($rs->fields['linea_sobregiro']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['saldo_sobregiro']); ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['max_mensual']); ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['saldo_mensual']); ?></td>
		</tr>
<?php $rs->MoveNext();
    } //$rs->MoveFirst();

}
?>
	  </tbody>
    </table>
</div>
<br />


                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Ultimos 10 Cobros</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<a href="cobro_cuentas_busq.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Busqueda</a>
<hr />
<?php

$consulta = "
select * ,
(select usuario from usuarios where cuentas_clientes_pagos_cab.registrado_por = usuarios.idusu) as registrado_por,
(select razon_social from cliente where cliente.idcliente = cuentas_clientes_pagos_cab.idcliente) as cliente
from cuentas_clientes_pagos_cab 
where 
estado = 1
and notanum is null
order by registrado_el desc
limit 10
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
<?php if ($rs->fields['idcuentaclientepagcab'] > 0) { ?>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Razon Social</th>
			<th align="center">Monto abonado</th>
			<th align="center">Fecha pago</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>

			<th align="center">Recibo</th>

		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="cobro_cuentas_imp.php?id=<?php echo $rs->fields['idcuentaclientepagcab']; ?>" class="btn btn-sm btn-default" title="Imprimir Ticket" data-toggle="tooltip" data-placement="right"  data-original-title="Imprimir Ticket"><span class="fa fa-print"></span></a>
					<a href="cobro_cuentas_imp_pre.php?id=<?php echo $rs->fields['idcuentaclientepagcab']; ?>" class="btn btn-sm btn-default" title="Imprimir Pre Impreso" data-toggle="tooltip" data-placement="right"  data-original-title="Imprimir Pre Impreso"><span class="fa fa-print"></span></a>
                    <a href="cobro_cuentas_imp_pdf.php?id=<?php echo $rs->fields['idcuentaclientepagcab']; ?>" target="_blank" class="btn btn-sm btn-default" title="Descargar Ticket PDF" data-toggle="tooltip" data-placement="right"  data-original-title="Descargar Ticket PDF"><span class="fa fa-file-pdf-o"></span></a>
                    <a href="cobro_cuentas_imp_recibo_pdf.php?id=<?php echo $rs->fields['idcuentaclientepagcab']; ?>" target="_blank" class="btn btn-sm btn-default" title="Descargar A4 PDF" data-toggle="tooltip" data-placement="right"  data-original-title="Descargar A4 PDF"><span class="fa fa-file"></span></a>
				</div>

			</td>
			<td align="center"><?php echo antixss($rs->fields['cliente']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['monto_abonado']);  ?></td>
			<td align="center"><?php if ($rs->fields['fecha_pago'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_pago']));
			}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			}  ?></td>

			<td align="center"><?php echo antixss($rs->fields['recibo']); ?></td>

		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />
<?php } ?>


                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
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
