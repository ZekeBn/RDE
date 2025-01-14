<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$dirsup = "S";
$submodulo = "134";
require_once("../includes/rsusuario.php");

$iddeposito = $_GET['id'];
$idinsumo = $_GET['idinsumo'];

$consulta = "SELECT descripcion from gest_depositos where iddeposito=$iddeposito";
$rs_depositos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$nombre_deposito = $rs_depositos->fields['descripcion'];
$consulta = "SELECT *,
(select descripcion from gest_depositos where iddeposito = conteo.iddeposito)  as deposito,
(select estadoconteo from estado_conteo where idestadoconteo = conteo.estado ) as estadoconteo,
(select descripcion from insumos_lista where insumos_lista.idinsumo = conteo.idinsumo) as articulo,
(
	SELECT GROUP_CONCAT(grupo_insumos.nombre) AS grupos 
	from conteo_grupos 
	inner join grupo_insumos on grupo_insumos.idgrupoinsu = conteo_grupos.idgrupoinsu
	where 
	conteo_grupos.idconteo = conteo.idconteo
) as grupos
from conteo
where
estado <> 6
and conteo_consolidado = 1
and conteo.iddeposito = $iddeposito
and conteo.idinsumo = $idinsumo
order by idconteo desc
limit 100
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


$consulta = "
select *,
(select descripcion from gest_depositos where iddeposito = conteo.iddeposito)  as deposito,
(select estadoconteo from estado_conteo where idestadoconteo = conteo.estado ) as estadoconteo,
(
	SELECT GROUP_CONCAT(grupo_insumos.nombre) AS grupos 
	from conteo_grupos 
	inner join grupo_insumos on grupo_insumos.idgrupoinsu = conteo_grupos.idgrupoinsu
	where 
	conteo_grupos.idconteo = conteo.idconteo
) as grupos
from conteo
where
estado <> 6
and conteo.iddeposito = $iddeposito
and tipo_conteo = 2
and idinsumo = $idinsumo
and conteo_consolidado != 1
and idconteo_ref=0
order by idconteo desc
limit 100
";
$rs2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta_pendiente = "SELECT 
count(*) as pendiente
from 
conteo 
where 
estado <> 6 
and conteo.iddeposito = $iddeposito
and tipo_conteo = 2 
and idinsumo = $idinsumo
and estado = 2
and idconteo_ref=0
";
$rs_pendiente = $conexion->Execute($consulta_pendiente) or die(errorpg($conexion, $consulta_pendiente));
$pendiente = intval($rs_pendiente->fields['pendiente']);


?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <style>
        .enlace-con-bloqueo {
          cursor: no-drop;
        }
  </style>
  </head>
  <script>
    function alerta_modal(titulo,mensaje){
      $('#modal_ventana').modal('show');
      $("#modal_titulo").html(titulo);
      $("#modal_cuerpo").html(mensaje);
    }
    function mostrar_detalle(idconteo){
      var parametros = {
				"idconteo"		  : idconteo
			};
      $.ajax({
					data:  parametros,
					url:   'conteo_por_productos_depositos_det.php',
					type:  'post',
					beforeSend: function () {
						$("#conteo_productos").html('Cargando...');  
					},
					success:  function (response) {
						console.log(response);
						alerta_modal("Detalle del conteo",response)
					}
			});
    }
  </script>

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
                    <h2>Conteo de Stock en Deposito <?php if (isset($nombre_deposito)) {
                        echo $nombre_deposito;
                    } ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

                  <a href="conteo_stock_detalle.php?id=<?php echo $iddeposito; ?>" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
                  

                  <h4>Conteos por Articulo Consolidados</h4>
                  <small>conteos los cuales fueron consolidados.</small>
                  <div class="table-responsive">
                    <table width="100%" class="table table-bordered jambo_table bulk_action">
                    <thead>
                    <tr>
                      <th>Accion</th>
                      <th># Conteo</th>
                      <th>Deposito</th>
                      <th>Idinsumo</th>
                      <th>Articulo</th>
                      <th>Iniciado</th>
                      <th>Modificado</th>
                      <th>Estado</th>
                      <th>Afecta Stock</th>

                      </tr>
                      </thead>
                      <tbody>
                        <?php
                          $i = 1;
while (!$rs->EOF) { ?>
                              <tr>
                                <td>
                          <?php
  $idconteo = $rs->fields['idconteo'];
    $estado = intval($rs->fields['estado']);
    $mostrarbtn = "N";
    $mostrarbtn = "S";
    $link = "conteo_consolidado_det.php?id=$idconteo&iddeposito=$iddeposito&idinsumo=$idinsumo";
    $txtbtn = "Abrir";
    $iconbtn = "plus";
    $tipoboton = "default";

    $link_trash = "conteo_consolidado_edit.php?idconteo=$idconteo";
    $txtbtn_trash = "Reabrir Sub Conteos";
    $iconbtn_trash = "edit";
    $tipoboton_trash = "default";


    $link_search = "conteo_por_producto_reporte.php?id=$idconteo&iddeposito=$iddeposito&idinsumo=$idinsumo";
    $txtbtn_search = "Reporte";
    $iconbtn_search = "search";
    $tipoboton_search = "default";

    if ($mostrarbtn == 'S') {
        ?>
                        <div class="btn-group">
                          <?php if ($estado == 1) { ?>
                            <a href="<?php echo $link; ?>" class="btn btn-sm btn-<?php echo $tipoboton; ?>" title="<?php echo $txtbtn; ?>" data-toggle="tooltip" data-placement="right"  data-original-title="<?php echo $txtbtn; ?>"><span class="fa fa-<?php echo $iconbtn; ?>"></span> <?php echo $txtbtn; ?></a>
                            <a href="<?php echo $link_trash; ?>" class="btn btn-sm btn-<?php echo $tipoboton_trash; ?>" title="<?php echo $txtbtn_trash; ?>" data-toggle="tooltip" data-placement="right"  data-original-title="<?php echo $txtbtn_trash; ?>"><span class="fa fa-<?php echo $iconbtn_trash; ?>"></span> <?php echo $txtbtn_trash; ?></a>
                          <?php } ?>
                          <?php if ($estado == 3) { ?>
                            <a href="<?php echo $link_search; ?>" class="btn btn-sm btn-<?php echo $tipoboton_search; ?>" title="<?php echo $txtbtn_search; ?>" data-toggle="tooltip" data-placement="right"  data-original-title="<?php echo $txtbtn_search; ?>"><span class="fa fa-<?php echo $iconbtn_search; ?>"></span> <?php echo $txtbtn_search; ?></a>
                          <?php } ?>
                        </div>
                        <?php } ?>
                        </td>
                            <td align="center"><?php echo $rs->fields['idconteo']; ?></td>
                            <td align="center"><?php echo $rs->fields['deposito']; ?></td>
                            <td align="center"><?php echo $rs->fields['idinsumo']; ?></td>
                            <td align="center"><?php echo $rs->fields['articulo']; ?></td>
                            <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['inicio_registrado_el'])); ?></td>
                            <td align="center"><?php if ($rs->fields['ult_modif'] != '') {
                                echo date("d/m/Y", strtotime($rs->fields['ult_modif']));
                            } ?></td>
                            <td align="center"><?php echo $rs->fields['estadoconteo']; ?></td>
                            <td align="center"><?php if ($rs->fields['afecta_stock'] == 'S') {
                                echo "SI";
                            } else {
                                echo "NO";
                            } ?></td>
                        </tr>
                        <?php $i++;
    $rs->MoveNext();
} ?>
                      </tbody>
                    </table>
                  </div>
                  <!-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// -->
                  <!-- //////////////////////////////////no finalizados pendientes a consolidar////////////////////////////////////////////// -->
                  <!-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// -->
                  <h4>Sub Conteos por Articulo</h4>
                  <p>
                    <a href="conteo_stock_add.php?id=<?php echo $iddeposito; ?>&idinsumo=<?php echo $idinsumo ?>" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
                    <?php if ($pendiente > 0) { ?>
                      <a href="conteo_consolidado_add.php?id=<?php echo $iddeposito; ?>&idinsumo=<?php echo $idinsumo ?>" class="btn btn-sm btn-default"><span class="fa fa-check-circle-o"></span> Finalizar conteo del deposito</a>
                      <?php } else { ?>
                        <a href="javascript:void(0);" class="enlace-con-bloqueo btn btn-sm btn-default"><span class="fa fa-check-circle-o"></span> Finalizar conteo del deposito</a>
                    <?php } ?>
                  </p>

                  <hr />
                  
                  <div class="table-responsive">
                      <table width="100%" class="table table-bordered jambo_table bulk_action">
                      <thead>
                      <tr>
                        <th>Accion</th>
                        <th># Conteo</th>
                        <th>Iniciado</th>
                        <th>Modificado</th>
                        <th>Finalizado</th>
                        <th>Afecta Stock</th>
                        <th>Estado</th>
                      </tr>
                        </thead>
                        <tbody>
                          <?php
  $i = 1;
while (!$rs2->EOF) { ?>
                                <tr>
                                  <td>
                            <?php
$idconteo = $rs2->fields['idconteo'];
    $estado = $rs2->fields['estado'];
    $mostrarbtn = "N";
    if ($estado == 1) {
        $mostrarbtn = "S";
        $link = "conteo_stock_contar_producto.php?id=".$idconteo."&iddeposito=".$iddeposito."&idinsumo=".$idinsumo;
        $txtbtn = "Abrir";
        $iconbtn = "plus";
        $tipoboton = "success";

        $mostrarbtn = "S";
        $link_trash = "anular_conteo.php?idsub_conteo=".$idconteo;
        $txtbtn_trash = "";
        $iconbtn_trash = "trash";
        $tipoboton_trash = "default";
    }
    if ($estado == 2) {
        $mostrarbtn = "S";
        $link = "reabrir_conteo.php?idsub_conteo=".$idconteo;
        $txtbtn = "Reabrir Conteo";
        $iconbtn = "edit";
        $tipoboton = "success";
        # code...
    }



    if ($mostrarbtn == 'S') {
        ?>
                          <div class="btn-group">
                            <a href="<?php echo $link; ?>" class="btn btn-sm btn-<?php echo $tipoboton; ?>" title="<?php echo $txtbtn; ?>" data-toggle="tooltip" data-placement="right"  data-original-title="<?php echo $txtbtn; ?>"><span class="fa fa-<?php echo $iconbtn; ?>"></span> <?php echo $txtbtn; ?></a>
                            <?php if ($estado == 2) { ?>
                              <a href="javascript:void(0);" onclick="mostrar_detalle(<?php echo $idconteo?>)" class="btn btn-sm btn-default" title="Detalles" data-toggle="tooltip" data-placement="right"  data-original-title="Detalles"><span class="fa fa-search"></span></a>
                              <?php } ?>
                              <?php if ($estado == 1) { ?>
                                <a href="<?php echo $link_trash; ?>" class="btn btn-sm btn-<?php echo $tipoboton_trash; ?>" title="<?php echo $txtbtn_trash; ?>" data-toggle="tooltip" data-placement="right"  data-original-title="<?php echo $txtbtn_trash; ?>"><span class="fa fa-<?php echo $iconbtn_trash; ?>"></span> <?php echo $txtbtn_trash; ?></a>
                              <?php } ?>
                            </div>
                          <?php } ?>
                          </td>
                              <td align="center"><?php echo $rs2->fields['idconteo']; ?></td>
                              <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rs2->fields['inicio_registrado_el'])); ?></td>
                              <td align="center"><?php if ($rs2->fields['ult_modif'] != '') {
                                  echo date("d/m/Y", strtotime($rs2->fields['ult_modif']));
                              } ?></td>
                              <td align="center"><?php if ($rs2->fields['final_registrado_el'] != '') {
                                  echo date("d/m/Y H:i:s", strtotime($rs2->fields['final_registrado_el']));
                              } ?></td>
                              <td align="center"><?php if ($rs2->fields['afecta_stock'] == 'S') {
                                  echo "SI";
                              } else {
                                  echo "NO";
                              } ?></td>
                              <td align="center"><?php echo $rs2->fields['estadoconteo']; ?></td>
                          </tr>
                          <?php $i++;
    $rs2->MoveNext();
} ?>
                        </tbody>
                      </table>
                  </div>
                  <br /><br />







                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            


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
