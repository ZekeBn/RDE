<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "55";
$dirsup = 'S';
require_once("../includes/rsusuario.php");
require_once("../modelos/producto.php");

//diccionario para poder guardar el array de productos y asi visualizar el estante de una
// manera grafica
$diccionario = [];
$almacenamientos_datos = [];
$iddeposito = intval($_GET['idpo']);
if ($iddeposito > 0) {
    $consulta = "SELECT gest_depositos.descripcion FROM gest_depositos WHERE gest_depositos.iddeposito = $iddeposito";
    $rs_depo_name = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $nombre_deposito = $rs_depo_name->fields['descripcion'];
}


$idalmacto = intval($_GET['id']);
if ($idalmacto == 0) {
    header("location: gest_deposito_almcto_grl.php");
    exit;
}


$consulta = "
select gest_deposito_almcto_grl.*,
(select usuario from usuarios where gest_deposito_almcto_grl.registrado_por = usuarios.idusu) as registrado_por,
(select usuario from usuarios where gest_deposito_almcto_grl.anulado_por = usuarios.idusu) as anulado_por,
gest_depositos.descripcion as deposito
from gest_deposito_almcto_grl 
inner join gest_depositos on gest_depositos.iddeposito = gest_deposito_almcto_grl.iddeposito
where 
gest_deposito_almcto_grl.estado = 1 
and idalmacto = $idalmacto
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idalmacto = intval($rs->fields['idalmacto']);
if ($idalmacto == 0) {
    header("location: gest_deposito_almcto_grl.php");
    exit;
}


$consulta = "SELECT 
gest_depositos_stock_almacto.disponible, 
gest_depositos_stock_almacto.idalm, 
gest_deposito_almcto_grl.idalmacto,
gest_depositos_stock.iddeposito,
gest_depositos_stock_almacto.fila, 
gest_deposito_almcto.tipo_almacenado,
gest_deposito_almcto.filas as filas_almacenamiento,
gest_deposito_almcto.columnas as columnas_almacenamiento,
gest_depositos_stock.lote, 
gest_depositos_stock.vencimiento, 
gest_depositos_stock_almacto.columna, 
gest_depositos_stock_almacto.idpasillo, 
gest_depositos_stock_almacto.disponible, 
medidas.nombre as medida_ref, 
medidas.id_medida as idmedida, 
gest_deposito_almcto_grl.nombre as almacenamiento, 
CONCAT(
  gest_deposito_almcto.nombre, 
  ' ', 
  COALESCE(gest_deposito_almcto.cara, '')
) as tipo_almacenamiento, 
gest_almcto_pasillo.nombre as pasillo, 
insumos_lista.descripcion as insumo 
FROM 
gest_depositos_stock_almacto 
LEFT JOIN gest_almcto_pasillo on gest_almcto_pasillo.idpasillo = gest_depositos_stock_almacto.idpasillo 
INNER JOIN gest_deposito_almcto on gest_deposito_almcto.idalm = gest_depositos_stock_almacto.idalm 
INNER JOIN gest_deposito_almcto_grl on gest_deposito_almcto_grl.idalmacto = gest_deposito_almcto.idalmacto 
INNER JOIN gest_depositos_stock ON gest_depositos_stock.idregseriedptostk = gest_depositos_stock_almacto.idregseriedptostk 
INNER JOIN insumos_lista ON insumos_lista.idinsumo = gest_depositos_stock.idproducto 
INNER JOIN medidas on medidas.id_medida = gest_depositos_stock_almacto.idmedida 
where 
gest_deposito_almcto_grl.idalmacto = $idalmacto
and gest_depositos_stock_almacto.disponible > 0 
and gest_depositos_stock_almacto.estado = 1 
ORDER BY fila, columna,idalm
";
$rs2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$articulos_contador = $rs2->RecordCount();

?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <style>
   .grid-container {
      display: grid;
      
      grid-gap: 10px;
      width: 55vw;
      margin: 2vh;
    }

    .grid-item {
      background-color: #f2f2f2;
      padding: 20px;
      font-size: 30px;
      text-align: center;
    }

    .active_status{
      background-color: #E39774;
      padding: 20px;
      box-shadow: 5px 5px 5px #888888;
      color: #ffffff;
      border: 2px solid #d18d67;
      box-sizing: border-box;
    }
    .estante_container{
      display: flex;
      justify-content: center;
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
                    <h2>Almacenamientos <?php if (isset($nombre_deposito)) { ?> de <?php echo $nombre_deposito ?> <?php } ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	

   

                  

 
                  

                  
<p><a href="gest_deposito_almcto_grl.php<?php if (isset($iddeposito)) { ?>?idpo=<?php echo $iddeposito;
} ?>" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">

		<tr>
			<th align="center">Idalmacto</th>
			<td align="center"><?php echo intval($rs->fields['idalmacto']); ?></td>
		</tr>
		<tr>
			<th align="center">Deposito</th>
			<td align="center"><?php echo antixss($rs->fields['deposito']); ?></td>
		</tr>
		<tr>
			<th align="center">Nombre</th>
			<td align="center"><?php echo antixss($rs->fields['nombre']); ?></td>
		</tr>
		<tr>
			<th align="center">Registrado por</th>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
		</tr>
		<tr>
			<th align="center">Registrado el</th>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			}  ?></td>
		</tr>
	
		


</table>
 </div>
<br />


<?php if ($articulos_contador > 0) { ?>
  <!-- ///////////////////////////////////////////////////////////////////////////////// -->
    <h3>Articulos almacenados</h3>
    <hr>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Articulo</th>
                    <th>disponible</th>
                    <th>fila</th>
                    <th>columna</th>
                    <th>Lote</th>
                    <th>Vencimiento</th>
                    <th>Almacenamiento</th>
                    <th>Tipo Almacenamiento</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($rs2->RecordCount() > 0) {
                    while (!$rs2->EOF) {

                        $disponible = $rs2->fields['disponible'];
                        $fila = $rs2->fields['fila'];
                        $insumo = $rs2->fields['insumo'];
                        $columna = $rs2->fields['columna'];
                        $vencimiento = $rs2->fields['vencimiento'] ? date("d/m/Y", strtotime($rs2->fields['vencimiento'])) : "--";
                        $lote = $rs2->fields['lote'];
                        $tipo_almacenamiento = $rs2->fields['tipo_almacenamiento'];
                        $tipo_almacenado = $rs2->fields['tipo_almacenado'];
                        $filas_almacenamiento = $rs2->fields['filas_almacenamiento'];
                        $columnas_almacenamiento = $rs2->fields['columnas_almacenamiento'];
                        if ($tipo_almacenado == 1) {
                            $producto1 = new Producto($insumo, $disponible, $fila, $columna, $lote, $vencimiento, $tipo_almacenamiento);

                            $clave = $fila." ".$columna;

                            if (floatval($disponible) > 0) {
                                if (array_key_exists($tipo_almacenamiento, $diccionario)) {
                                    if (array_key_exists($clave, $diccionario[$tipo_almacenamiento])) {
                                        $diccionario[$tipo_almacenamiento][$clave][] = $producto1;
                                    } else {
                                        $diccionario[$tipo_almacenamiento][$clave] = [$producto1];
                                    }
                                } else {
                                    $diccionario[$tipo_almacenamiento] = [];
                                    $almacenamientos_datos[$tipo_almacenamiento] = [$filas_almacenamiento,$columnas_almacenamiento];
                                    if (array_key_exists($clave, $diccionario[$tipo_almacenamiento])) {
                                        $diccionario[$tipo_almacenamiento][$clave][] = $producto1;
                                    } else {
                                        $diccionario[$tipo_almacenamiento][$clave] = [$producto1];
                                    }
                                }
                            }
                        }

                        ?>
                <tr>
                    <td align="center"><?php echo antixss($rs2->fields['insumo']); ?></td>
                    <td align="center" style="background-color:#9BC1BC;color:white;" ><?php echo antixss($rs2->fields['disponible']); ?></td>
                    <td align="center" style="background-color: #E39774;color:white;" ><?php echo antixss($rs2->fields['fila']); ?></td>
                    <td align="center" style="background-color: #E39774;color:white;" ><?php echo antixss($rs2->fields['columna']); ?></td>
                    <td align="center"><?php echo antixss($rs2->fields['lote']); ?></td>
                    <td align="center"><?php echo $rs2->fields['vencimiento'] ? date("d/m/Y", strtotime($rs2->fields['vencimiento'])) : "--" ?> </td>                
                    <td align="center"><?php echo antixss($rs2->fields['almacenamiento']); ?></td>
                    <td align="center">
                      <a href="./gest_deposito_almcto_detalles.php?id=<?php echo $rs2->fields['idalm']?>&idpo=<?php echo $rs2->fields['iddeposito']?>&idalmacto=<?php echo $rs2->fields['idalmacto']?>">
                      <?php echo antixss($rs2->fields['tipo_almacenamiento']); ?>
                      </a>
                      
                    </td>
                </tr>
                <?php
                        $rs2->MoveNext();
                    }
                    // echo var_dump($diccionario);exit;
                }
    ?>
            </tbody>
        </table>
    </div>

  <!-- ///////////////////////////////////////////////////////////////////////////// -->
<?php } ?>








                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
<!-- SECCION INICIO  -->

<?php if ($articulos_contador > 0) { ?>
<div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel" >
                  <div class="x_title">
                    <h2>Detalles Estantes</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content" style="display: none;">



                  <!-- ///////////////////////////////////////////////////// -->

<?php if (count($diccionario) > 0) { ?>
    <?php foreach ($diccionario as $almacenamiento_nombre => $almacenamiento) { ?>
      <br>
      <div  role="alert">
      <h2>Esquema de Almacenamiento <?php echo $almacenamiento_nombre;?></h2>
      <hr>
    </div>
      <div class="table-responsive estante_container">
        <?php
        $filas = $almacenamientos_datos[$almacenamiento_nombre][0];
        $columnas = $almacenamientos_datos[$almacenamiento_nombre][1];
        ?>
        <div class="grid-container" style="<?php echo "grid-template-columns: repeat( $columnas,minmax(10vw,1fr));"?>">
          <?php for ($i = $filas; $i >= 1; $i--) {
              for ($j = 1; $j <= $columnas; $j++) {
                  $clave = $i." ".$j;
                  $texto = "";
                  $activo = 0;
                  if (array_key_exists($clave, $almacenamiento)) {
                      foreach ($almacenamiento[$clave] as $productos) {

                          $texto .= $productos->mostrarInformacion();
                          $texto .= "<br>";

                          $activo = 1;
                      }
                  }
                  ?>
            <div data-toggle="tooltip" data-placement="right"  data-html="true" data-original-title="<?php echo $texto; ?>" class="grid-item <?php echo $activo == 1 ? "active_status" : ""; ?>"  ><?php echo $i." ".$j; ?></div>
          <?php
              }
          }
        ?>
        </div>
      </div>


    <?php } ?>
  <?php } ?>
  <?php } ?>

<!-- ////////////////////////////////////////////////////// -->


                  </div>
                </div>
              </div>
            </div>


                  <!-- SECCION FIN  -->
            
            
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
