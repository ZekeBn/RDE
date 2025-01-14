<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "162";
$dirsup = "S";

require_once("../includes/rsusuario.php");

$ahorad = date("Y-m-d");
$consulta = "
select *,
(select descripcion from gest_depositos where iddeposito = conteo.iddeposito)  as deposito,
(select estadoconteo from estado_conteo where idestadoconteo = conteo.estado ) as estadoconteo
from conteo
where
estado <> 6
and (estado = 1 or estado = 2)
and conteo.idempresa = $idempresa
and conteo.idsucursal = $idsucursal
and conteo.iniciado_por = $idusu
and conteo.fecha_inicio = '$ahorad'
order by idconteo desc
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
                    <h2>Conteo de Stock PDV</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    
                  

	<div class="clear"></div>
		<div class="cuerpo">
			<div class="colcompleto" id="contenedor">


 				<div class="divstd">

<br />
<p align="center">(Inventario Ciego)</p>
<br />
<p align="center"><a href="conteo_stock_add_pdv.php">[Nuevo Conteo]</a></p>
<p align="center">&nbsp;</p>
<div class="table-responsive">
  <table width="900" border="1" class="table table-bordered jambo_table bulk_action" >
    <tbody>
      <tr>
        <td align="center" bgcolor="#F8FFCC"><strong># Conteo</strong></td>
        <td align="center" bgcolor="#F8FFCC"><strong>Deposito</strong></td>
        <td align="center" bgcolor="#F8FFCC"><strong>Iniciado</strong></td>
        <td align="center" bgcolor="#F8FFCC"><strong>Estado</strong></td>
        <td align="center" bgcolor="#F8FFCC"><strong>Accion</strong></td>
        </tr>
  <?php
  $i = 1;
while (!$rs->EOF) { ?>
      <tr>
        <td align="center"><?php echo $rs->fields['idconteo']; ?></td>
        <td align="center"><?php echo $rs->fields['deposito']; ?></td>
        <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['inicio_registrado_el'])); ?></td>
        <td align="center"><?php echo $rs->fields['estadoconteo']; ?></td>
        <td align="center" style="height:30px;"><?php
  $mostrarbtn = "N";
    if ($rs->fields['estado'] == 1) {
        $mostrarbtn = "S";
        $link = "conteo_stock_contar_pdv.php?id=";
        $txtbtn = "Abrir";
    }

    if ($mostrarbtn == 'S') {
        ?><input type="button" name="button" id="button" value="<?php echo $txtbtn?>" onmouseup="document.location.href='<?php echo $link.$rs->fields['idconteo']; ?>'" class="btn btn-sm btn-default" /><?php } ?></td>
        </tr>
  <?php $i++;
    $rs->MoveNext();
} ?>
    </tbody>
  </table>
</div>
<p align="center">&nbsp;</p>
          </div> 
			<!-- contenedor -->
   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
  

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
