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
    $descripcion = antisqlinyeccion($_POST['subcategoria_sec'], "text");
    $idsubcate = antisqlinyeccion($_POST['idsubcate'], "int");
    $margen_seguridad = antisqlinyeccion($_POST['margen_seguridad'], "float");
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $idempresa = antisqlinyeccion('1', "int");

    $estado = 1;

    if (intval($_POST['idsubcate']) == 0) {
        $valido = "N";
        $errores .= " - El campo sub categoria no puede estar vacio.<br />";
    }
    if (trim($_POST['subcategoria_sec']) == '') {
        $valido = "N";
        $errores .= " - El campo subcategoria secundaria no puede estar vacio.<br />";
    }

    $consulta = "
	select * from sub_categorias_secundaria where descripcion = $descripcion and idsubcate_sec = $idsubcate  and estado = 1 limit 1
	";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsex->fields['idsubcate_sec'] > 0) {
        $valido = "N";
        $errores .= " - Ya existe otra subcategoria secundaria con el mismo nombre.<br />";
    }


    // si todo es correcto inserta
    if ($valido == "S") {

        $idsubcate_sec = select_max_id_suma_uno("sub_categorias_secundaria", "idsubcate_sec")["idsubcate_sec"];

        $consulta = "
		insert into sub_categorias_secundaria
		(idsubcate_sec,idsubcate, descripcion, idempresa, estado, registrado_por, registrado_el, margen_seguridad)
		values
		($idsubcate_sec, $idsubcate, $descripcion, $idempresa, $estado, $registrado_por, $registrado_el, $margen_seguridad)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		select idsubcate from sub_categorias where estado = 1 order by idsubcate desc limit 1
		";
        $rsins = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idsubcategoria = intval($rsins->fields['idsubcate']);

        $res = [
            'valido' => $valido,
            'errroes' => $errores,
            'idsubcate' => intval($idsubcate),
            'idsubcate_sec' => intval($idsubcate_sec)
        ];

        // convierte a formato json
        $respuesta = json_encode($res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

        // devuelve la respuesta formateada
        echo $respuesta;
        exit;

    }



}



$buscar = "
select *, sub_categorias_secundaria.margen_seguridad,sub_categorias_secundaria.descripcion as nombre_sub_cate_sec, categorias.nombre as categoria, sub_categorias.descripcion as subcategoria,
(select usuario from usuarios where sub_categorias_secundaria.registrado_por = usuarios.idusu) as registrado_por
from sub_categorias_secundaria 
INNER JOIN sub_categorias on sub_categorias.idsubcate = sub_categorias_secundaria.idsubcate
INNER JOIN categorias on categorias.id_categoria = sub_categorias.idcategoria
where 
sub_categorias_secundaria.estado = 1 
order by idsubcate_sec desc
limit 10
"    ;
$prod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

?>
Formulario abreviado para agregar subcategorias secundarias, para mas opciones (foto,editar,borrar,recargos,orden,etc) ir al modulo de categorias: 
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

<div id="form_subcate_sec">
	
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Sub-Categoria Secundaria </label>
		<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="text" name="subcategoria_sec" id="subcategoria_sec" value="" placeholder="Sub-Categoria" class="form-control"  />
	
		</div>
	</div>
	
	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Sub-Categoria *</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
	<?php

    // consulta
    $consulta = "
	SELECT idsubcate, descripcion,idcategoria
	FROM sub_categorias
	where
	estado = 1
	order by descripcion asc
	 ";

// valor seleccionado
if (isset($_POST['idsubcate_sec'])) {
    $value_selected = htmlentities($_POST['idsubcate_sec']);
} else {
    $value_selected = htmlentities($rs->fields['idsubcate_sec']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsubcate',
    'id_campo' => 'idsubcate',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idsubcate',

    'value_selected' => $value_selected,
    'data_hidden' => 'idcategoria',
    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required"  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);



?>
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
	
	
		<div class="form-group">
			<div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	<button type="button" class="btn btn-success" onmouseup="agregar_subcategoria_sec();" ><span class="fa fa-check-square-o"></span> Agregar</button>
			</div>
		</div>
	
	  <input type="hidden" name="MM_insert" value="form1" />
	  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
	<br />
</div>

<div class="clearfix"></div>


<hr />
<strong>Ultimas 10 Agregadas:</strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
      <tr>
	  <th>Codigo</th> 
        <th>Sub-Categoria Secundaria</th>
        <th>Sub-Categoria</th>
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
	  <td align="center"><?php echo trim($prod->fields['idsubcate']) ?></td>
	  <td align="center"><?php echo trim($prod->fields['nombre_sub_cate_sec']) ?></td>
      <td align="center"><?php echo trim($prod->fields['subcategoria']) ?></td>
      <td align="center"><?php echo trim($prod->fields['categoria']) ?></td>
	  <?php if ($margen_seguridad == "S") {?>
			<td align="center"><?php echo formatomoneda(trim($prod->fields['margen_seguridad'])) ?></td>
		<?php } ?>
        </tr>
      <?php $prod->MoveNext();
      } ?>
      </tbody>
</table>
</div>