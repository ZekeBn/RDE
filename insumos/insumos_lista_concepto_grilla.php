<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "63";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("../insumos/preferencias_insumos_listas.php");
require_once("../categorias/preferencias_categorias.php");
$consulta = "select idconcepto
from cn_conceptos 
where 
 estado = 1
 and UPPER(descripcion) like \"VENTA\" 
";
$rs_venta = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idventa = $rs_venta->fields["idconcepto"];

$consulta = "select idconcepto
from cn_conceptos 
where 
estado = 1
and UPPER(descripcion) like \"MERCADERIAS\" 
";
$rs_mercaderia = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idmercaderia = $rs_mercaderia->fields["idconcepto"];


$consulta = "
select *,
(select usuario from usuarios where cn_conceptos.registrado_por = usuarios.idusu) as registrado_por,
(select usuario from usuarios where cn_conceptos.borrado_por = usuarios.idusu) as borrado_por
from cn_conceptos 
where 
 estado = 1 
order by idconcepto asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

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
                    <h2>Concepto</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	

                  
<p>
    <a href="insumos_lista.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
    <a href="insumos_lista_concepto_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>

</p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Descripcion</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) {
    $idconcepto_select = $rs->fields["idconcepto"];
    ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="insumos_lista_concepto_det.php?id=<?php echo $rs->fields['idconcepto']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<?php if ($idventa != $idconcepto_select && $idmercaderia != $idconcepto_select) { ?>
                        <a href="insumos_lista_concepto_edit.php?id=<?php echo $rs->fields['idconcepto']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                        <a href="insumos_lista_concepto_del.php?id=<?php echo $rs->fields['idconcepto']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                    <?php } ?>
				</div>

			</td>
			<td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
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
