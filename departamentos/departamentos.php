<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo

$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");
$idpais = antisqlinyeccion($_GET['idpais'], "text");

$whereadd = null;
if (trim($_GET['idpais']) != '') {
    $whereadd .= " and departamentos_propio.idpais = $idpais ";
}
$consulta = "
select *,
(select usuario from usuarios where departamentos_propio.registrado_por = usuarios.idusu) as registrado_por,
(select usuario from usuarios where departamentos_propio.anulado_por = usuarios.idusu) as anulado_por,
(select nombre from paises_propio where paises_propio.idpais=departamentos_propio.idpais ) as pais
from departamentos_propio 
where 
 estado = 1 
 $whereadd
order by iddepartamento asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "
select idpais
from paises_propio 
where 
UPPER(nombre)='PARAGUAY' 
limit 1
";
$rs_paraguay = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idparaguay = intval($rs_paraguay->fields['idpais']);


?>
<!DOCTYPE html>
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
                    <h2>Departamentos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	

                  <p><a href="departamentos_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a></p>
<hr />
<form id="form1" name="form1" method="get" action="">



<div class="col-md-6 col-xs-12 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">
			Pais *
		</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<?php

                // consulta

                $consulta = "
				SELECT p.idpais, p.nombre FROM paises_propio p
				WHERE p.estado = 1
				order by nombre asc;
				";

// valor seleccionado
if (isset($_POST['idpais'])) {
    $value_selected = htmlentities($_GET['idpais']);
} else {
    $value_selected = htmlentities($_GET['idpais']);
}



// parametros
$parametros_array = [
    'nombre_campo' => 'idpais',
    'id_campo' => 'idpais',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idpais',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'data_hidden' => 'idmoneda',
    'style_input' => 'class="form-control"',
    'acciones' => '   '.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
		</div>
	</div>




<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Buscar</button>
        </div>
    </div>

<br />
</form>
<div class="clearfix"></div>
<br /><br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Id</th>
			<th align="center">Descripcion</th>
			<th align="center">Pais</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>
		
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="departamentos_det.php?id=<?php echo $rs->fields['iddepartamento']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<a href="departamentos_edit.php?id=<?php echo $rs->fields['iddepartamento']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					
					<?php  if (intval($rs->fields['idpais']) != ($idparaguay)) { ?>
						<a href="departamentos_del.php?id=<?php echo $rs->fields['iddepartamento']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
					<?php } ?>
				
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['iddepartamento']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['pais']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			}  ?></td>
			
		</tr>
<?php

$rs->MoveNext();
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
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
