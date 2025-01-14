<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "255";
require_once("includes/rsusuario.php");




if (isset($_GET['MM_search']) && $_GET['MM_search'] == 'form1') {
    //echo "a";

    if (trim($_GET['numero']) != '') {
        $numero = antisqlinyeccion($_GET['numero'], "text");
        $whereadd .= " and nota_credito_cabeza.numero = $numero ";
    }

    if (trim($_GET['fecha_nota']) != '') {
        $fecha_nota = antisqlinyeccion($_GET['fecha_nota'], "text");
        $whereadd .= " and nota_credito_cabeza.fecha_nota = $fecha_nota ";
    }

    if (trim($_GET['numero']) != '' or trim($_GET['fecha_nota']) != '') {

        $consulta = "
		select *,
		(select usuario from usuarios where nota_credito_cabeza.registrado_por = usuarios.idusu) as registrado_por
		from nota_credito_cabeza 
		where 
		 estado = 3
		 $whereadd
		order by idnotacred asc
		";
        //echo $consulta;exit;
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
                    <h2>Anular nota credito cliente</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha nota </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="fecha_nota" id="fecha_nota" value="<?php  if (isset($_GET['fecha_nota'])) {
	    echo htmlentities($_GET['fecha_nota']);
	}?>" placeholder="Fecha nota" class="form-control"  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nota de Credito </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="numero" id="numero" value="<?php  if (isset($_GET['numero'])) {
	    echo htmlentities($_GET['numero']);
	}?>" placeholder="Ej: 001-001-0000123" class="form-control"  />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Buscar</button>

        </div>
    </div>

  <input type="hidden" name="MM_search" value="form1" />

<br />
</form>
<div class="clearfix"></div>
<br /><br />
<?php if (isset($_GET['MM_search']) && $_GET['MM_search'] == 'form1') {?>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idnotacred</th>
			<th align="center">Fecha nota</th>
			<th align="center">Numero</th>
			<th align="center">Cliente</th>
			<th align="center">Razon social</th>
			<th align="center">Ruc</th>

		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="nota_credito_cli_anula_del.php?id=<?php echo $rs->fields['idnotacred']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo antixss($rs->fields['idnotacred']); ?></td>

			<td align="center"><?php if ($rs->fields['fecha_nota'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_nota']));
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['numero']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idcliente']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>

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
