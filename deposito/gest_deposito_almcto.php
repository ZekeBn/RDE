<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "55";
$dirsup = 'S';
require_once("../includes/rsusuario.php");

$idalmacto = intval($_GET['idalmacto']);
$whereadd = "";
if ($idalmacto > 0) {
    $consulta = "SELECT gest_deposito_almcto_grl.nombre FROM gest_deposito_almcto_grl WHERE gest_deposito_almcto_grl.idalmacto = $idalmacto";
    $rs_almacto_nombre = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $nombre_almacenamiento = $rs_almacto_nombre->fields['nombre'];
    $whereadd = " and gest_deposito_almcto_grl.idalmacto = $idalmacto ";
}

$iddeposito = intval($_GET['idpo']);
if ($iddeposito > 0) {
    $consulta = "SELECT gest_depositos.descripcion FROM gest_depositos WHERE gest_depositos.iddeposito = $iddeposito";
    $rs_depo_name = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $nombre_deposito = $rs_depo_name->fields['descripcion'];
}


$consulta = "
select gest_deposito_almcto.*,
(select usuario from usuarios where gest_deposito_almcto.registrado_por = usuarios.idusu) as registrado_por,
(select usuario from usuarios where gest_deposito_almcto.anulado_por = usuarios.idusu) as anulado_por,
gest_deposito_almcto_grl.nombre as almacenamiento
from gest_deposito_almcto
inner join gest_deposito_almcto_grl on gest_deposito_almcto.idalmacto = gest_deposito_almcto_grl.idalmacto
where 
gest_deposito_almcto.estado = 1 
$whereadd
order by idalm asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


$consulta = "
select gest_almcto_pasillo.*,
(select usuario from usuarios where gest_almcto_pasillo.registrado_por = usuarios.idusu) as registrado_por,
(select usuario from usuarios where gest_almcto_pasillo.anulado_por = usuarios.idusu) as anulado_por,
gest_deposito_almcto_grl.nombre as almacenamiento
from gest_almcto_pasillo 
inner join gest_deposito_almcto_grl on gest_almcto_pasillo.idalmacto = gest_deposito_almcto_grl.idalmacto
where 
gest_almcto_pasillo.estado = 1
$whereadd 
order by idpasillo asc
";
$rs2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




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
                    <h2>Almacenamiento detalles  <?php if (isset($nombre_almacenamiento)) { ?> para <?php echo $nombre_almacenamiento ?> <?php } ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	

   

                  

    
                    <p>
                      <a href="gest_deposito_almcto_grl.php<?php if (isset($iddeposito)) { ?>?idpo=<?php echo $iddeposito;
                      } ?>" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
                    </p>

                  
                  
                  
                    
                    









                    
<p>
  <a href="gest_almcto_pasillo_add.php<?php if (isset($idalmacto)) { ?>?idalmacto=<?php echo $idalmacto;
  } ?><?php if (isset($iddeposito)) { ?>&idpo=<?php echo $iddeposito;
  } ?>" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar Pasillos</a>
  <h4>Pasillos del Almacenamiento</h4>
  <small>Agregue los pasillos referentes a este Almacenamiento</small>
</p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Almacenamiento</th>
			<th align="center">Nombre</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs2->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<!-- <a href="gest_almcto_pasillo_det.php?id=<?php echo $rs2->fields['idpasillo']; ?><?php if (isset($iddeposito)) { ?>&idpo=<?php echo $iddeposito;
					} ?><?php if (isset($idalmacto)) { ?>&idalmacto=<?php echo $idalmacto;
					} ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a> -->
					<a href="gest_almcto_pasillo_edit.php?id=<?php echo $rs2->fields['idpasillo']; ?><?php if (isset($iddeposito)) { ?>&idpo=<?php echo $iddeposito;
					} ?><?php if (isset($idalmacto)) { ?>&idalmacto=<?php echo $idalmacto;
					} ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="gest_almcto_pasillo_del.php?id=<?php echo $rs2->fields['idpasillo']; ?><?php if (isset($iddeposito)) { ?>&idpo=<?php echo $iddeposito;
					} ?><?php if (isset($idalmacto)) { ?>&idalmacto=<?php echo $idalmacto;
					} ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo antixss($rs->fields['almacenamiento']); ?></td>
			<td align="center"><?php echo antixss($rs2->fields['nombre']); ?></td>
			<td align="center"><?php echo antixss($rs2->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs2->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs2->fields['registrado_el']));
			}  ?></td>
		</tr>
<?php

$rs2->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
	 
    </table>
</div>
<br />


                  
                  
                  
                  
                  
                    <p>
                    <a href="gest_deposito_almcto_add.php<?php if (isset($idalmacto)) { ?>?idalmacto=<?php echo $idalmacto;
                    } ?><?php if (isset($iddeposito)) { ?>&idpo=<?php echo $iddeposito;
                    } ?>" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar Tipos de Almacenamiento</a>
                    <h4>Tipos de Almacenamiento</h4>
                    <small>Agregue cuantos Estantes necesite pero solo un tipo Apilado</small>
                  </p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Almacenamiento</th>
			<th align="center">Nombre</th>
			<th align="center">Tipo almacenado</th>
			<th align="center">Cara</th>
			<th align="center">Filas</th>
			<th align="center">Columnas</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="gest_deposito_almcto_detalles.php?id=<?php echo $rs->fields['idalm']; ?><?php if (isset($iddeposito)) { ?>&idpo=<?php echo $iddeposito;
					} ?><?php if (isset($iddeposito)) { ?>&idalmacto=<?php echo $idalmacto;
					} ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<a href="gest_deposito_almcto_edit.php?id=<?php echo $rs->fields['idalm']; ?><?php if (isset($iddeposito)) { ?>&idpo=<?php echo $iddeposito;
					} ?><?php if (isset($iddeposito)) { ?>&idalmacto=<?php echo $idalmacto;
					} ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="gest_deposito_almcto_del.php?id=<?php echo $rs->fields['idalm']; ?><?php if (isset($iddeposito)) { ?>&idpo=<?php echo $iddeposito;
					} ?><?php if (isset($iddeposito)) { ?>&idalmacto=<?php echo $idalmacto;
					} ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo antixss($rs->fields['almacenamiento']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['nombre']); ?></td>
			<td align="center"><?php echo antixss(intval($rs->fields['tipo_almacenado']) == 1 ? "Estante" : "Apilado"); ?></td>
			<td align="center"><?php echo antixss($rs->fields['cara']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['filas']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['columnas']); ?></td>
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
