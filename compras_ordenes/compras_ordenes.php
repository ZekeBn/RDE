<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");

require_once("../compras_ordenes/preferencias_compras_ordenes.php");
require_once("../proveedores/preferencias_proveedores.php");


$consulta = "
select *,
(select embarque.idembarque from embarque where embarque.ocnum = compras_ordenes.ocnum and embarque.idcompra IS NULL) as idembarque,
(select usuario from usuarios where compras_ordenes.registrado_por = usuarios.idusu) as registrado_por,
(select usuario from usuarios where compras_ordenes.generado_por = usuarios.idusu) as generado_por,
(select usuario from usuarios where compras_ordenes.borrado_por = usuarios.idusu) as borrado_por,
(select nombre from proveedores where idproveedor = compras_ordenes.idproveedor ) as proveedor
from compras_ordenes 
where 
 estado = 1 
order by ocnum asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

//Buscando embarque unico para esa orden


?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <script>
   
      function  habilitar_edit(ocnum){
      var parametros = {
            "ocnum"   : ocnum
            };
        $.ajax({
                    data:  parametros,
                    url:   'habilitar_edit_compras_orden.php',
                    type:  'post',
                    beforeSend: function () {
                          // $("#selecompra").html('Cargando...');  
                    },
                    success:  function (response) {
                // $("#selecompra").html(response);
                // $("#cantidad").focus();
                if(JSON.parse(response)['success'] == true){
                }
              }
            });	
            // await sleep(500);
            document.location.href='compras_ordenes_det.php?id='+ocnum;
    }
  </script>
  <style>
    .sin_embarque{
      background: #ce2d4fa8;
		  font-weight: bold;
    }
    .sin_embarque:hover{
      background: #ce2d4f;
    	color: #000;
		  font-weight: bold;
    }

    
    .con_embarque{
      background: #D7FFAB;
		  font-weight: bold;
    }

    .con_embarque:hover{
      background: #C3EB97;
    	color: #000;
		  font-weight: bold;
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
                    <h2>Ordenes de Compra en Proceso de Carga</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                    </li>
                  </ul>
                  <div class="clearfix"></div>
                </div>
                <div class="x_content">
                  
                  
                  
                  
                  <p><a href="compras_ordenes_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar Orden De Compra Nueva </a></p>
                  <hr />
                  <small>Para poder visualizar las órdenes de compra, es necesario finalizar las órdenes de carga primero.<small>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Orden Nº</th>
			<th align="center">Fecha Orden</th>
			<th align="center">Generado por</th>
			<th align="center">Tipocompra</th>
			<th align="center">Fecha entrega</th>
			<th align="center">Proveedor</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) {
    $idembarque = intval($rs->fields['idembarque']);
    $class_css = "";
    if ($idembarque > 0) {

        $class_css = "con_embarque";
    } else {
        $class_css = "sin_embarque";

    }
    ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="compras_ordenes_det.php?id=<?php echo $rs->fields['ocnum']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<a href="compras_ordenes_edit.php?id=<?php echo $rs->fields['ocnum']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="compras_ordenes_del.php?id=<?php echo $rs->fields['ocnum']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
          <?php if ($proveedores_importacion == "S" && intval($rs->fields['ocnum_ref']) > 0) { ?>
            <a href="javascript:void(0);" class="btn btn-sm btn-default <?php echo $class_css;?>" title="Embarque" data-toggle="tooltip"  onmouseup="document.location.href='../embarque/embarque_add.php?ocn=<?php echo $rs->fields['ocnum']; ?>&path=ordenes'"><span class="fa fa-ship"></span></a>		
          <?php } ?>
        </div>

			</td>
			<td align="center"><?php echo intval($rs->fields['ocnum']); ?></td>
			<td align="center"><?php echo date("d/m/Y", strtotime($rs->fields['fecha'])); ?></td>
			<td align="center"><?php echo antixss($rs->fields['generado_por']); ?></td>
			<td align="center"><?php if (intval($rs->fields['tipocompra']) == 2) {
			    echo "Credito";
			} else {
			    echo "Contado";
			}?></td>
			<td align="center"><?php if ($rs->fields['fecha_entrega'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_entrega']));
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['proveedor']); ?></td>
		</tr>
<?php $rs->MoveNext();
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
            



        
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2  >Ordenes de Compra Pendiente</h2>
                    
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                    </li>
                  </ul>
                  <div class="clearfix"></div>
                </div>
                <div class="x_content">
                  <h6>Las siguientes ordenes poseen productos en transito.</h6>

                  <?php

                  $preferencia_add = null;
if ($preferencias_facturas_multiples == "S") {
    // $preferencia_add=" carga_completa='N' ";
    //por el momento el multiorden no funcionara para varias facturas
    $preferencia_add = "estado=2 and estado_orden=1 and ocnum_ref IS NULL  ";

} else {
    $preferencia_add = "estado=2 and estado_orden=1 and ocnum not in (select ocnum from compras where ocnum is not null and estado <> 6) ";
}
$consulta = "
                  select *,
                  (select embarque.idembarque from embarque where embarque.ocnum = compras_ordenes.ocnum) as idembarque,
                  (select usuario from usuarios where compras_ordenes.registrado_por = usuarios.idusu) as registrado_por,
                  (select usuario from usuarios where compras_ordenes.generado_por = usuarios.idusu) as generado_por,
                  (select usuario from usuarios where compras_ordenes.borrado_por = usuarios.idusu) as borrado_por,
                  (select nombre from proveedores where idproveedor = compras_ordenes.idproveedor ) as proveedor
                  from compras_ordenes 
                  where 
                  $preferencia_add
                  order by ocnum desc
                  limit 50
                  ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>

                  <div class="table-responsive">
                      <table width="100%" class="table table-bordered jambo_table bulk_action">
                      <thead>
                      <tr>
                        <th></th>
                        <th align="center">Orden Nº</th>
                        <th align="center">Fecha Orden</th>
                        <th align="center">Generado por</th>
                        <th align="center">Tipocompra</th>
                        <th align="center">Fecha entrega</th>
                        <th align="center">Proveedor</th>
                      </tr>
                      </thead>
                      <tbody>
                  <?php while (!$rs->EOF) {
                      $idembarque = intval($rs->fields['idembarque']);
                      $class_css = "";
                      if ($idembarque > 0) {

                          $class_css = "con_embarque";
                      } else {
                          $class_css = "sin_embarque";

                      }
                      ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="inf_ocdetallev2.php?idoc=<?php echo $rs->fields['ocnum']; ?>" target="_blank" class="btn btn-sm btn-default" title="Imprimir PDF A4" data-toggle="tooltip" data-placement="right"  data-original-title="Imprimir PDF A4"><span class="fa fa-print"></span></a>

          <?php if ($ocultar_tk_vincular == "N") { ?>
            <a href="compras_ordenes_tk.php?id=<?php echo $rs->fields['ocnum']; ?>"  class="btn btn-sm btn-default" title="Imprimir TK" data-toggle="tooltip" data-placement="right"  data-original-title="Imprimir TK"><span class="fa fa-print"></span></a>
            <a href="compras_ordenes_vincula.php?id=<?php echo $rs->fields['ocnum']; ?>"  class="btn btn-sm btn-default" title="Vincular" data-toggle="tooltip" data-placement="right"  data-original-title="Vincular"><span class="fa fa-link"></span></a>
					<?php } ?>
					<?php //if( $preferencias_facturas_multiples == "S" ){?>
            <a href="compras_ordenes_det_finalizado.php?id=<?php echo $rs->fields['ocnum']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<?php if ($preferencias_facturas_multiples == "S") { ?>
            <a style="display:none;" ref="javascript:void(0);" onclick="habilitar_edit(<?php echo $rs->fields['ocnum']; ?>);"  class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
          <?php } ?>
          <?php //if($proveedores_importacion == "S"){?>
            <!-- <a href="javascript:void(0);" class="btn btn-sm btn-default <?php echo $class_css;?>" title="Embarque" data-toggle="tooltip"  onmouseup="document.location.href='../embarque/embarque_add.php?ocn=<?php echo $rs->fields['ocnum']; ?>'"><span class="fa fa-ship"></span></a>		 -->
          <?php //}?>

        </div>

			</td>
			<td align="center"><?php echo intval($rs->fields['ocnum']); ?></td>
			<td align="center"><?php echo date("d/m/Y", strtotime($rs->fields['fecha'])); ?></td>
			<td align="center"><?php echo antixss($rs->fields['generado_por']); ?></td>
			<td align="center"><?php if (intval($rs->fields['tipocompra']) == 2) {
			    echo "Credito";
			} else {
			    echo "Contado";
			}?></td>
			<td align="center"><?php if ($rs->fields['fecha_entrega'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_entrega']));
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['proveedor']); ?></td>
		</tr>
<?php $rs->MoveNext();
                  } //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />




            <!-- SECCION -->
        


            
          </div>
        </div>
        <!-- /page content -->
        <?php if ($facturas_finalizadas == 'S') {?>   
        <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2  >Ordenes de Compra Finalizadas</h2>                    
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                  <div class="clearfix"></div>
                </div>
                <div class="x_content">
                  <h6>Las siguientes ordenes ya fueron completadas </h6>

                  <?php


                        $preferencia_add = null;
            if ($preferencias_facturas_multiples == "S") {
                // $preferencia_add=" carga_completa='N' ";
                //por el momento el multiorden no funcionara para varias facturas
                $preferencia_add = "estado=2 and estado_orden=2  and ocnum_ref IS NULL ";

            } else {
                $preferencia_add = "estado=2 and ocnum not in (select ocnum from compras where ocnum is not null and estado <> 6) ";
            }




            $consulta_numero_filas = "
                        select 
                        count(*) as filas from compras_ordenes 
                        where 
                        $preferencia_add
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



















            $consulta = "
                          select *,
                          (select embarque.idembarque from embarque where embarque.ocnum = compras_ordenes.ocnum) as idembarque,
                          (select usuario from usuarios where compras_ordenes.registrado_por = usuarios.idusu) as registrado_por,
                          (select usuario from usuarios where compras_ordenes.generado_por = usuarios.idusu) as generado_por,
                          (select usuario from usuarios where compras_ordenes.borrado_por = usuarios.idusu) as borrado_por,
                          (select nombre from proveedores where idproveedor = compras_ordenes.idproveedor ) as proveedor
                          from compras_ordenes 
                          where 
                          $preferencia_add
                          order by ocnum desc $limit $offset
                          
                          ";
            $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            ?>

                  <div class="table-responsive">
                      <table width="100%" class="table table-bordered jambo_table bulk_action">
                      <thead>
                      <tr>
                        <th></th>
                        <th align="center">Orden Nº</th>
                        <th align="center">Fecha Orden</th>
                        <th align="center">Generado por</th>
                        <th align="center">Tipocompra</th>
                        <th align="center">Fecha entrega</th>
                        <th align="center">Proveedor</th>
                      </tr>
                      </thead>
                      <tbody>
                          <?php while (!$rs->EOF) {
                              $idembarque = intval($rs->fields['idembarque']);
                              $class_css = "";
                              if ($idembarque > 0) {

                                  $class_css = "con_embarque";
                              } else {
                                  $class_css = "sin_embarque";

                              }
                              ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="inf_ocdetallev2.php?idoc=<?php echo $rs->fields['ocnum']; ?>" target="_blank" class="btn btn-sm btn-default" title="Imprimir A4" data-toggle="tooltip" data-placement="right"  data-original-title="Imprimir A4"><span class="fa fa-print"></span></a>
					<?php if ($ocultar_tk_vincular == "N") { ?>
          <a href="compras_ordenes_tk.php?id=<?php echo $rs->fields['ocnum']; ?>"  class="btn btn-sm btn-default" title="Imprimir TK" data-toggle="tooltip" data-placement="right"  data-original-title="Imprimir TK"><span class="fa fa-print"></span></a>
          <a href="compras_ordenes_vincula.php?id=<?php echo $rs->fields['ocnum']; ?>"  class="btn btn-sm btn-default" title="Vincular" data-toggle="tooltip" data-placement="right"  data-original-title="Vincular"><span class="fa fa-link"></span></a>
					<?php } ?>
          <?php //if( $preferencias_facturas_multiples == "S" ){?>
					<?php if ($preferencias_facturas_multiples == "S") { ?>
            <a href="compras_ordenes_det_finalizado.php?id=<?php echo $rs->fields['ocnum']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
            <a style="display:none;" ref="javascript:void(0);" onclick="habilitar_edit(<?php echo $rs->fields['ocnum']; ?>);"  class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
          <?php } ?>
          
        </div>

			</td>
			<td align="center"><?php echo intval($rs->fields['ocnum']); ?></td>
			<td align="center"><?php echo date("d/m/Y", strtotime($rs->fields['fecha'])); ?></td>
			<td align="center"><?php echo antixss($rs->fields['generado_por']); ?></td>
			<td align="center"><?php if (intval($rs->fields['tipocompra']) == 2) {
			    echo "Credito";
			} else {
			    echo "Contado";
			}?></td>
			<td align="center"><?php if ($rs->fields['fecha_entrega'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_entrega']));
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['proveedor']); ?></td>
		</tr>
<?php $rs->MoveNext();
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
                <a href="compras_ordenes.php?pag=<?php echo(1);?>" class="btn btn-sm btn-default" title="<?php echo(1);?>"  data-placement="right"  data-original-title="<?php echo(1);?>"><span class="fa fa-chevron-left"></span><span class="fa fa-chevron-left"></span></a>
                <a href="compras_ordenes.php?pag=<?php echo($num_pag - 1);?>" class="btn btn-sm btn-default" title="<?php echo($num_pag - 1);?>"  data-placement="right"  data-original-title="<?php echo($num_pag - 1);?>"><span class="fa fa-chevron-left"></span></a>
            <?php }
            $inicio_pag = 0;
            if ($num_pag != 1 && $num_pag - 5 > 0) {
                $inicio_pag = $num_pag - 5;
            } else {
                $inicio_pag = 1;
            }
            for ($i = $inicio_pag; $i <= $last_index; $i++) {
                ?>
                <a href="compras_ordenes.php?pag=<?php echo($i);?>" class="btn btn-sm btn-default <?php echo $i == $num_pag ? " selected_pag " : "" ?>" title="<?php echo($i);?>"  data-placement="right"  data-original-title="<?php echo($i);?>"><?php echo($i);?></a>
                <?php if ($i == $last_index && ($num_pag + 1 <= $paginas_num_max)) {?>
                    <a href="compras_ordenes.php?pag=<?php echo($num_pag + 1);?>" class="btn btn-sm btn-default" title="<?php echo($num_pag + 1);?>"  data-placement="right"  data-original-title="<?php echo($num_pag + 1);?>"><span class="fa fa-chevron-right"></span></a>
                    <a href="compras_ordenes.php?pag=<?php echo($paginas_num_max);?>" class="btn btn-sm btn-default" title="<?php echo($paginas_num_max);?>"  data-placement="right"  data-original-title="<?php echo($paginas_num_max);?>"><span class="fa fa-chevron-right"></span><span class="fa fa-chevron-right"></span></a>
                <?php } ?>
            <?php } ?>
        </div>
    </td>
</tr>
	  </tbody>
    </table>
</div>
<?php } ?>
<br />



                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 



        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
