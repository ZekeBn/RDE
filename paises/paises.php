<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo

$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("../importadora/preferencias_importadora.php");

$consulta = "
select *,
(select usuario from usuarios where paises_propio.registrado_por = usuarios.idusu) as registrado_por,
(select usuario from usuarios where paises_propio.anulado_por = usuarios.idusu) as anulado_por,
(select descripcion  from tipo_moneda where paises_propio.idmoneda = tipo_moneda.idtipo) as moneda,
(select nombre from paises where paises_propio.idpais = paises.idpais) as pais_set
from paises_propio 
where 
 estado = 1 
order by idpais asc
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
                    <h2>Paises</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">




<p>
  <a href="paises_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
  <?php if ($carga_masiva_importacion == "S") { ?>
    <a href="paises_carga_masiva.php" class="btn btn-sm btn-default"><span class="fa fa-sitemap"></span> Carga Masiva</a>
  <?php } ?>

</p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idpais</th>
			<th align="center">Nombre</th>
			
			<th align="center">Pais por Defecto</th>
			<th align="center">Abreviatura</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>
			<th align="center">Moneda</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="paises_det.php?id=<?php echo $rs->fields['idpais']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<a href="paises_edit.php?id=<?php echo $rs->fields['idpais']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<?php if (intval($rs->fields['idpais']) != ($idparaguay)) { ?>
            <a href="paises_del.php?id=<?php echo $rs->fields['idpais']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
          <?php } ?>
        </div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idpais']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['nombre']); ?></td>
			
			<td align="center"><?php echo intval($rs->fields['defecto']) ? "Si" : "No"; ?></td>
			<td align="center"><?php echo antixss($rs->fields['abreviatura']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['moneda']); ?></td>
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
