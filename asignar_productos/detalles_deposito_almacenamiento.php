<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../modelos/producto.php");
// Modulo y submodulo respectivamente


/////////////////////////////////////
///////////////////////////////////////////////
$diccionario = [];
$almacenamientos_datos = [];
function guardar_diccionario($clave, &$diccionario, $valor)
{
    if (array_key_exists($clave, $diccionario)) {
        $diccionario[$clave][] = $valor;
    } else {
        $diccionario[$clave] = [$valor];
    }
}
// http://localhost/desarrollo/asignar_productos/gest_deposito_productos_new.php?idpo=4

$modulo = "1";
$submodulo = "55";
$dirsup = 'S';
require_once("../includes/rsusuario.php");

if (intval($seleccion_por_defecto) == 0) {
    // $seleccion_por_defecto=$_POST['boton_datos'];
    $seleccion_por_defecto = 1;
}
$idpo = intval($_GET['idpo']);
if ($idpo == 0) {
    $idpo = intval($iddeposito);
}
if ($idpo == 0) {
    header("Location:gest_adm_depositos.php");
    exit;
}
$iddeposito = $idpo;

$whereadd = null;
if (trim($_GET['idalmacto']) != '') {
    $idalmacto = trim($_GET['idalmacto']);
    $whereadd .= " and gest_depositos_stock_almacto.idalmacto = $idalmacto ";
}


$consulta = "SELECT 
gest_depositos_stock_almacto.disponible, 
gest_depositos_stock_almacto.idalm, 
gest_depositos_stock_almacto.fila, 
gest_deposito_almcto.tipo_almacenado,
gest_depositos_stock_almacto.columna,
gest_deposito_almcto.filas as filas_almacenamiento,
gest_deposito_almcto.columnas as columnas_almacenamiento, 
gest_depositos_stock.lote, 
gest_depositos_stock.vencimiento, 
gest_depositos_stock_almacto.idpasillo, 
gest_depositos_stock_almacto.disponible, 
gest_deposito_almcto_grl.idalmacto,
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
gest_deposito_almcto_grl.iddeposito = $iddeposito
$whereadd
and gest_depositos_stock_almacto.disponible > 0 
and gest_depositos_stock_almacto.estado = 1 
ORDER BY idalm, fila, columna
";
$rs3 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$articulos_contador = $rs3->RecordCount();




?>
<script>
	function activarBoton(boton){
		var parentDiv = boton.parentNode;
		var buttons = parentDiv.getElementsByTagName('button');
		for (var i = 0; i < buttons.length; i++) {
			if (buttons[i] !== boton) {
				buttons[i].classList.remove("active");
		}
    	}
		boton.classList.add("active");
		// console.log(boton.dataset.hiddenValue);
		if (boton.dataset.hiddenValue == 1) {
			$("#tabla_datos").removeClass("hide");
			$("#box_graficos_estantes").addClass("hide");
		}
		
		if (boton.dataset.hiddenValue == 2) {
			$("#tabla_datos").addClass("hide");
			$("#box_graficos_estantes").removeClass("hide");
		
		}
	}
	
</script>

<div style="color:white;">.</div>
<?php if (intval($articulos_contador) > 0) { ?>
<div style="width:100%;">
  <div class="btn-group" role="group" aria-label="Basic example" style="display: grid;grid-template-columns: repeat(2, 1fr);">
    <button id="button_data_table" type="button" onclick="activarBoton(this)" class="btn btn-default hover_cancelar <?php echo $seleccion_por_defecto == 1 ? "active" : ""?>" data-hidden-value="1" onclick="handleClick(this)">Ver en Tabla</button>
    <button id="button_data_graphic" type="button" onclick="activarBoton(this)" class="btn btn-default hover_cancelar <?php echo $seleccion_por_defecto == 2 ? "active" : ""?>" data-hidden-value="2" onclick="handleClick(this)">Ver en Gr&aacute;fico de Estantes</button>
  </div>
</div>

    <hr>
    <div id="tabla_datos" class="table-responsive <?php echo $seleccion_por_defecto == 2 ? "hide" : "" ?>">
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
                if ($rs3->RecordCount() > 0) {
                    while (!$rs3->EOF) {


                        $disponible = $rs3->fields['disponible'];
                        $fila = $rs3->fields['fila'];
                        $insumo = $rs3->fields['insumo'];
                        $columna = $rs3->fields['columna'];
                        $vencimiento = $rs3->fields['vencimiento'] ? date("d/m/Y", strtotime($rs3->fields['vencimiento'])) : "--";
                        $lote = $rs3->fields['lote'];
                        $almacenamiento = $rs3->fields['almacenamiento'];
                        $tipo_almacenamiento = $rs3->fields['tipo_almacenamiento'];
                        $tipo_almacenado = $rs3->fields['tipo_almacenado'];
                        $filas_almacenamiento = $rs3->fields['filas_almacenamiento'];
                        $columnas_almacenamiento = $rs3->fields['columnas_almacenamiento'];
                        if ($tipo_almacenado == 1) { //estante
                            $producto1 = new Producto($insumo, $disponible, $fila, $columna, $lote, $vencimiento, $tipo_almacenamiento);

                            $clave = $fila." ".$columna;
                            if (floatval($disponible) > 0) {


                                if (array_key_exists($almacenamiento, $diccionario)) {

                                    ///////////////////////////////////////////////////////
                                    if (array_key_exists($tipo_almacenamiento, $diccionario[$almacenamiento])) {
                                        guardar_diccionario($clave, $diccionario[$almacenamiento][$tipo_almacenamiento], $producto1);
                                    } else {
                                        $diccionario[$almacenamiento][$tipo_almacenamiento] = [];
                                        $almacenamientos_datos[$almacenamiento][$tipo_almacenamiento] = [$filas_almacenamiento,$columnas_almacenamiento];
                                        guardar_diccionario($clave, $diccionario[$almacenamiento][$tipo_almacenamiento], $producto1);
                                    }
                                    //////////////////////////////////////////////////////

                                } else {
                                    $diccionario[$almacenamiento] = [];
                                    $diccionario[$almacenamiento][$tipo_almacenamiento] = [];
                                    $almacenamientos_datos[$almacenamiento][$tipo_almacenamiento] = [$filas_almacenamiento,$columnas_almacenamiento];
                                    guardar_diccionario($clave, $diccionario[$almacenamiento][$tipo_almacenamiento], $producto1);

                                }

                            }
                        }
                        ?>
                <tr>
                    <td align="center"><?php echo antixss($rs3->fields['insumo']); ?></td>
                    <td align="center" style="background-color:#9BC1BC;color:white;"><?php echo antixss($rs3->fields['disponible']); ?></td>
                    <td align="center" style="background-color: #E39774;color:white;" ><?php echo antixss($rs3->fields['fila']); ?></td>
                    <td align="center" style="background-color: #E39774;color:white;" ><?php echo antixss($rs3->fields['columna']); ?></td>
                    <td align="center"><?php echo antixss($rs3->fields['lote']); ?></td>
                    <td align="center"><?php echo $rs3->fields['vencimiento'] ? date("d/m/Y", strtotime($rs3->fields['vencimiento'])) : "--" ?> </td>                
                    <td align="center"><?php echo antixss($rs3->fields['almacenamiento']); ?></td>
                    <td align="center"><?php echo antixss($rs3->fields['tipo_almacenamiento']); ?></td>
                </tr>
                <?php
                        $rs3->MoveNext();
                    }
                }
    ?>
            </tbody>
        </table>
    </div>
  <!-- ///////////////////////////////////////////////////////////////////////////// -->
<?php } ?>

<br>
<div class="clearfix"></div>
<div class="<?php echo $seleccion_por_defecto == 1 ? "hide" : "" ?>" id="box_graficos_estantes">
	
	
	<?php   if (count($diccionario) > 0) { ?>


    <?php foreach ($diccionario as $deposito_nombre => $almacenamiento) { ?>
		  <br>
		  <div  role="alert">
		    <h2>Tipo Almacenamiento <?php echo $deposito_nombre;?></h2>
		    <hr>
		  </div>
      <?php foreach ($almacenamiento as $almacenamiento_nombre => $objeto_almacenamiento) { ?>
        <h2>Esquema Almacenamiento <?php echo $almacenamiento_nombre;?></h2>
		    <div class="table-responsive estante_container">
        <?php
          $filas = $almacenamientos_datos[$deposito_nombre][$almacenamiento_nombre][0];
          $columnas = $almacenamientos_datos[$deposito_nombre][$almacenamiento_nombre][1];
          ?>
			  <div class="grid-container" style="<?php echo "grid-template-columns: repeat( $columnas,minmax(10vw,1fr));"?>">
			    <?php for ($i = $filas; $i >= 1; $i--) {
			        for ($j = 1; $j <= $columnas; $j++) {
			            $clave = $i." ".$j;
			            $texto = "";
			            $activo = 0;
			            if (array_key_exists($clave, $objeto_almacenamiento)) {
			                foreach ($objeto_almacenamiento[$clave] as $productos) {

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
</div>
