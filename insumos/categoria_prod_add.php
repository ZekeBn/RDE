<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "81";
$dirsup = "S";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("../categorias/preferencias_categorias.php");

// si envio bandera para agregar
if ($_POST['add'] == 'S') {

    // validaciones basicas
    $valido = "S";
    $errores = "";


    // recibe parametros
    $categoria = antisqlinyeccion($_POST['categoria'], "text");
    $margen_seguridad = antisqlinyeccion($_POST['margen_seguridad'], "float");


    if (trim($_POST['categoria']) == '') {
        $valido = "N";
        $errores .= " - El campo categoria no puede estar vacio.<br />";
    }

    $consulta = "
	select * from categorias where nombre = $categoria  and estado = 1 limit 1
	";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsex->fields['id_categoria'] > 0) {
        $valido = "N";
        $errores .= " - Ya existe otra categoria con el mismo nombre.<br />";
    }


    // si todo es correcto inserta
    if ($valido == "S") {

        // busca el proximo id
        $consulta = "
		select max(id_categoria) as proxid from categorias
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcategoria = intval($rs->fields['proxid']) + 1;

        $consulta = "
		insert into categorias
		(id_categoria, nombre, ab, orden, textobanner, imgbanner, estado, borrable, especial, idsucursal, idempresa, muestra_self, recarga_porc, muestra_ped, muestra_menu, margen_seguridad)
		values
		($idcategoria, $categoria, NULL, 0, '', '', 1, 'S', 'N', $idsucursal, $idempresa, 'S', 0, 'S', 'S',$margen_seguridad)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $res = [
            'valido' => $valido,
            'errroes' => $errores,
            'idcategoria' => intval($idcategoria)
        ];

        // convierte a formato json
        $respuesta = json_encode($res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

        // devuelve la respuesta formateada
        echo $respuesta;
        exit;

    }



}



$buscar = "
SELECT * 
FROM categorias
where
estado = 1
and idempresa = $idempresa
order by id_categoria desc
limit 10
"	;
$prod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

?>
Formulario abreviado para agregar categorias, para mas opciones (foto,editar,borrar,subcategorias,orden,etc) ir al modulo de categorias: 
<a href="gest_categoria_productos.php" target="_blank" class="btn btn-sm btn-default"><span class="fa fa-external-link"></span></a>
<br />
y luego hacer click en: <a href="#" onmouseup="recargar_categoria(0);" class="btn btn-sm btn-default"><span class="fa fa-refresh"></span> Actualizar Formulario</a>
<hr />
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="categoria" id="categoria" value="" placeholder="Categoria" class="form-control"  />                    
    <br />
    <button type="button" class="btn btn-success" onmouseup="agregar_categoria();" ><span class="fa fa-check-square-o"></span> Agregar</button>
	</div>
</div>

<?php if ($margen_seguridad == "S") {?>
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Margen seguridad </label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="margen_seguridad" id="margen_seguridad" value="<?php  if (isset($_POST['margen_seguridad'])) {
			    echo floatval($_POST['margen_seguridad']);
			} else {
			    echo floatval($rs->fields['margen_seguridad']);
			}?>" placeholder="Margen seguridad" class="form-control" required="required" />                    
		</div>
	</div>
<?php } ?>


  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />

<div class="clearfix"></div>


<hr />
<strong>Ultimas 10 Agregadas:</strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
      <tr>
	  <th>Codigo</th>
        <th>Categoria</th>
		<?php if ($margen_seguridad == "S") {?>
			<th>Margen Seguridad</th>
		<?php } ?>


        </tr>
        </thead>
        <tbody>
      <?php while (!$prod->EOF) {




          ?>
      <tr>
	  	<td align="center"><?php echo trim($prod->fields['id_categoria']) ?></td>
        <td align="center"><?php echo trim($prod->fields['nombre']) ?></td>
		<?php if ($margen_seguridad == "S") {?>
			<td align="center"><?php echo trim($prod->fields['margen_seguridad']) ?></td>
		<?php } ?>
	  </tr>
      <?php $prod->MoveNext();
      } ?>
      </tbody>
</table>
</div>