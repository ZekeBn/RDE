<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "55";
$dirsup = 'S';
require_once("../includes/rsusuario.php");
require_once("../modelos/producto.php");
$diccionario = [];

$idalm = intval($_GET['id']);
if ($idalm == 0) {
    header("location: gest_deposito_almcto.php");
    exit;
}

// consulta a la tabla
$consulta = "
select gest_deposito_almcto.*,
(select usuario from usuarios where gest_deposito_almcto.registrado_por = usuarios.idusu) as registrado_por_nombre,
gest_deposito_almcto_grl.nombre as nombre_almacenamiento
from gest_deposito_almcto
INNER JOIN gest_deposito_almcto_grl on gest_deposito_almcto_grl.idalmacto = gest_deposito_almcto.idalmacto
where 
gest_deposito_almcto.idalm = $idalm
and gest_deposito_almcto.estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$filas = $rs->fields['filas'];
$columnas = $rs->fields['columnas'];
// echo json_encode($rs->fields);exit;
$idalm = intval($rs->fields['idalm']);
if ($idalm == 0) {
    header("location: gest_deposito_almcto.php");
    exit;
}

$idalmacto = intval($_GET['idalmacto']);
if ($idalmacto > 0) {
    $consulta = "SELECT gest_deposito_almcto_grl.nombre FROM gest_deposito_almcto_grl WHERE gest_deposito_almcto_grl.idalmacto = $idalmacto";
    $rs_almacto_nombre = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $nombre_almacenamiento = $rs_almacto_nombre->fields['nombre'];
}

$iddeposito = intval($_GET['idpo']);
if ($iddeposito > 0) {
    $consulta = "SELECT gest_depositos.descripcion FROM gest_depositos WHERE gest_depositos.iddeposito = $iddeposito";
    $rs_depo_name = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $nombre_deposito = $rs_depo_name->fields['descripcion'];
}








$consulta = "SELECT 
gest_depositos_stock_almacto.disponible, 
gest_depositos_stock_almacto.idalm, 
gest_depositos_stock_almacto.fila, 
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
and gest_deposito_almcto.idalm = $idalm
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
  <script>
   function tipo_almacenamiento(value){
      var cara = $("#cara");
      var filas = $("#filas");
      var columnas = $("#columnas");
      var nombre = $("#nombre");
      if (value == 2){
        cara.attr("disabled",true);
        cara.val("");
        filas.attr("disabled",true);
        filas.val("");
        columnas.attr("disabled",true);
        nombre.attr("disabled",true);
        columnas.val("");
        nombre.val("APILADO");
      }
      if (value == 1){
        cara.attr("disabled",false);
        filas.attr("disabled",false);
        columnas.attr("disabled",false);
        nombre.attr("disabled",false);
      }
    }
    window.onload = function() {
      var tipo = $("#tipo_almacenado option:selected").val() ;
      tipo_almacenamiento(tipo);
    }
    window.ready = function() {
      var tipo = $("#tipo_almacenado option:selected").val() ;
      tipo_almacenamiento(tipo);
    }
  </script>
  <style>
    
    .grid-container {
      display: grid;
      grid-template-columns: repeat(<?php echo $columnas; ?>,minmax(10vw,1fr));
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
                    <h2>Almacenamiento detalles  <?php if (isset($nombre_almacenamiento)) { ?> para <?php echo $nombre_almacenamiento ?> <?php } ?></h2>
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
<button type="button" class="btn btn-default" onMouseUp="document.location.href='gest_deposito_almcto.php<?php if (isset($iddeposito)) { ?>?idpo=<?php echo $iddeposito;
} ?><?php if (isset($iddeposito)) { ?>&idalmacto=<?php echo $idalmacto;
} ?>'"><span class="fa fa-reply"></span> Atras</button>



<!-- /////////////////////////////////////////////////////////////// -->


<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">

		<tr>
			<th align="center">Almacenamiento </th>
			<td align="center"><?php echo antixss($rs->fields['nombre_almacenamiento']); ?></td>
		</tr>
		<tr>
			<th align="center">Tipo almacenado</th>
			<td align="center"><?php echo antixss($rs->fields['tipo_almacenado'] == 1 ? "Estante" : "Apilado"); ?></td>
		</tr>
		<tr>
			<th align="center">Tipo Almacenado Nombre</th>
			<td align="center"><?php echo antixss($rs->fields['nombre']); ?></td>
		</tr>
		
		<tr>
			<th align="center">Cara</th>
			<td align="center"><?php echo antixss($rs->fields['cara']); ?></td>
		</tr>
		<tr>
			<th align="center">Filas</th>
			<td align="center"><?php echo antixss($rs->fields['filas']); ?></td>
		</tr>
		<tr>
			<th align="center">Columnas</th>
			<td align="center"><?php echo antixss($rs->fields['columnas']); ?></td>
		</tr>
    <tr>
			<th align="center">Registrado el</th>
			<td align="center"><?php echo formatofecha("d/m/Y", $rs->fields['registrado_el']); ?></td>
		</tr>
    <tr>
			<th align="center">Registrado por</th>
			<td align="center"><?php echo antixss($rs->fields['registrado_por_nombre']); ?></td>
		</tr>
		
  </table>
 </div>
<br />


<?php if ($articulos_contador > 0) { ?>
  <h2>Productos Almacenados</h2>

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
                    $producto1 = new Producto($insumo, $disponible, $fila, $columna, $lote, $vencimiento);
                    $clave = $fila." ".$columna;

                    if (floatval($disponible) > 0) {
                        if (array_key_exists($clave, $diccionario)) {
                            $diccionario[$clave][] = $producto1;
                        } else {
                            $diccionario[$clave] = [$producto1];
                        }
                    }
                    ?>
          <tr>
              <td align="center"><?php echo antixss($insumo); ?></td>
              <td align="center"><?php echo antixss($disponible); ?></td>
              <td align="center" style="background-color: #E39774;color:white;" ><?php echo antixss($fila); ?></td>
              <td align="center" style="background-color: #E39774;color:white;" ><?php echo antixss($columna); ?></td>
              <td align="center"><?php echo antixss($lote); ?></td>
              <td align="center"><?php echo $vencimiento; ?> </td>                
          </tr>
          <?php
                            $rs2->MoveNext();
                }
            }
    ?>
      </tbody>
    </table>
  </div>

  
  <?php } ?>

  <?php if ($rs->fields['tipo_almacenado'] == 1) { ?>
    <h2>Esquema de Almacenamiento</h2>
    <div class="table-responsive estante_container">
      <div class="grid-container">
        <?php for ($i = $filas; $i >= 1; $i--) {
            for ($j = 1; $j <= $columnas; $j++) {
                $clave = $i." ".$j;
                $texto = "";
                $activo = 0;
                if (array_key_exists($clave, $diccionario)) {
                    foreach ($diccionario[$clave] as $productos) {

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


<!-- ////////////////////////////////////////////////////// -->
<div class="form-group">
<div class="col-md-12 col-sm-12 col-xs-12 text-center">
    
 <button type="button" class="btn btn-default" onMouseUp="document.location.href='gest_deposito_almcto.php<?php if (isset($iddeposito)) { ?>?idpo=<?php echo $iddeposito;
 } ?><?php if (isset($iddeposito)) { ?>&idalmacto=<?php echo $idalmacto;
 } ?>'"><span class="fa fa-reply"></span> Regresar</button>
    </div>
</div>
<div class="clearfix"></div>
<br /><br />

















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
