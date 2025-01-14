<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo

$modulo = "42";
$submodulo = "598";
// $modulo="1";
// $submodulo="2";
$dirsup = "S";
require_once("../includes/rsusuario.php");




$consulta_numero_filas = "
select 
count(*) as filas from embarque where
 embarque.estado = 1
";
$rs_filas = $conexion->Execute($consulta_numero_filas) or die(errorpg($conexion, $consulta_numero_filas));
$num_filas = $rs_filas->fields['filas'];
$filas_por_pagina = 30;
$num_pag = intval($_GET['pag']);
$paginas_num_max = ceil($num_filas / $filas_por_pagina);
if (intval($num_filas) > $filas_por_pagina) {
    $limit = "  LIMIT $filas_por_pagina";
}

if (($_GET['pag']) > 0) {
    $numero = (intval($_GET['pag']) - 1) * $filas_por_pagina;
    if ($numero != 0) {
        $offset = " offset $numero";

    }
} else {
    $offset = " ";
    $num_pag = 1;
}



$estado_embarque = antisqlinyeccion($_GET['estado_embarque'], "int");

$whereadd = null;
if (trim($_GET['estado_embarque']) != '') {
    $whereadd .= " and embarque.estado_embarque = $estado_embarque ";
}

$consulta = "
select embarque.*,
(select transporte.descripcion from transporte where transporte.idtransporte = embarque.idtransporte ) as nombre_transporte,
( select puertos.descripcion from puertos where puertos.idpuerto = embarque.idpuerto ) as puerto,
( select vias_embarque.descripcion from vias_embarque where vias_embarque.idvias_embarque = embarque.idvias_embarque ) as vias,
(select usuario from usuarios where embarque.registrado_por = usuarios.idusu) as registrado_por,
(select proveedores.nombre from proveedores where proveedores.idproveedor in (select compras_ordenes.idproveedor from compras_ordenes where compras_ordenes.ocnum = embarque.ocnum)) as proveedor
from embarque
where 
 estado = 1 
 $whereadd
order by idembarque desc $limit $offset
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <style>
    .finalizado{
      background-color: hsl(210, 50%, 70%) !important;
      color: #fff !important;
      border: hsl(210, 50%, 70%) solid 1px !important;
	  }
    .activo{
      background: #C3EB97;
      color: #405467!important;
    }
  </style>
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
                    <h2>Embarque</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	

        <p>
          <!-- <a href="embarque_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a> -->
          <a href="../compras_ordenes/compras_ordenes.php" class="btn btn-sm btn-default"><span class="fa fa-list-ul"></span> Ordenes de compra</a>
          <a href="../compras/gest_reg_compras_resto_new.php" class="btn btn-sm btn-default"><span class="fa fa-list-ul"></span> Registro de Compras</a>
        </p>
<hr />



<form id="form1" name="form1" method="get" action="">



<div class="col-md-6 col-xs-12 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Estado Embarque </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
		<?php

                if (isset($_POST['estado_embarque'])) {
                    $value_selected = htmlentities($_POST['estado_embarque']);
                } else {
                    $value_selected = '1';
                }
// opciones
$opciones = [
'Activo' => '1',
'Finalizado' => '2'
];
// parametros
$parametros_array = [
'nombre_campo' => 'estado_embarque',
'id_campo' => 'estado_embarque',

'value_selected' => $value_selected,

'pricampo_name' => 'Seleccionar...',
'pricampo_value' => '',
'style_input' => 'class="form-control"',
'acciones' => '  ',
'autosel_1registro' => 'S',
'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);

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
<br />



<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idembarque</th>
			<th align="center">Idcompra</th>
			<th align="center">puerto</th>
			<th align="center">transporte</th>
			<th align="center">Vias Embarque</th>
			<th align="center">Proveedor</th>
			<th align="center">Estado Embarque</th>
			<th align="center">Fecha embarque</th>
			<th align="center">Fecha llegada</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="embarque_det.php?id=<?php echo $rs->fields['idembarque']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<a href="embarque_edit.php?id=<?php echo $rs->fields['idembarque']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="embarque_del.php?id=<?php echo $rs->fields['idembarque']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
					<!-- <a href="../compras/compras_detalles.php?id=<?php echo $rs->fields['ocnum']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-list-ul"></span></a> -->
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idembarque']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idcompra']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['puerto']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['nombre_transporte']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['vias']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['proveedor']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['estado_embarque']) == "1" ? "Activo" : "Finalizado"; ?></td>
			<td align="center"><?php if ($rs->fields['fecha_embarque'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_embarque']));
			}  ?></td>
			<td align="center"><?php if ($rs->fields['fecha_llegada'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_llegada']));
			}  ?></td>
		</tr>
<?php

$rs->MoveNext();
} //$rs->MoveFirst();?>


<tr>
    <td align="center" colspan="10">
        <div class="btn-group">
            <?php
            $last_index = 0;
if ($num_pag + 10 > $paginas_num_max) {
    $last_index = $paginas_num_max;
} else {
    $last_index = $num_pag + 10;
}
if ($num_pag != 1) { ?>
                <a href="embarque.php?pag=<?php echo(1);?>" class="btn btn-sm btn-default" title="<?php echo(1);?>"  data-placement="right"  data-original-title="<?php echo(1);?>"><span class="fa fa-chevron-left"></span><span class="fa fa-chevron-left"></span></a>
                <a href="embarque.php?pag=<?php echo($num_pag - 1);?>" class="btn btn-sm btn-default" title="<?php echo($num_pag - 1);?>"  data-placement="right"  data-original-title="<?php echo($num_pag - 1);?>"><span class="fa fa-chevron-left"></span></a>
            <?php }
$inicio_pag = 0;
if ($num_pag != 1 && $num_pag - 5 > 0) {
    $inicio_pag = $num_pag - 5;
} else {
    $inicio_pag = 1;
}
for ($i = $inicio_pag; $i <= $last_index; $i++) {
    ?>
                <a href="embarque.php?pag=<?php echo($i);?>" class="btn btn-sm btn-default <?php echo $i == $num_pag ? " selected_pag " : "" ?>" title="<?php echo($i);?>"  data-placement="right"  data-original-title="<?php echo($i);?>"><?php echo($i);?></a>
                <?php if ($i == $last_index && ($num_pag + 1 <= $paginas_num_max)) {?>
                    <a href="embarque.php?pag=<?php echo($num_pag + 1);?>" class="btn btn-sm btn-default" title="<?php echo($num_pag + 1);?>"  data-placement="right"  data-original-title="<?php echo($num_pag + 1);?>"><span class="fa fa-chevron-right"></span></a>
                    <a href="embarque.php?pag=<?php echo($paginas_num_max);?>" class="btn btn-sm btn-default" title="<?php echo($paginas_num_max);?>"  data-placement="right"  data-original-title="<?php echo($paginas_num_max);?>"><span class="fa fa-chevron-right"></span><span class="fa fa-chevron-right"></span></a>
                <?php } ?>
            <?php } ?>
        </div>
    </td>
</tr>
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
