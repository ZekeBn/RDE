<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "81";
$dirsup = "S";
require_once("../includes/rsusuario.php");

// si envio bandera para agregar
if ($_POST['add'] == 'S') {

    // validaciones basicas
    $valido = "S";
    $errores = "";


    // recibe parametros
    $subcategoria = antisqlinyeccion($_POST['subcategoria'], "text");
    $idcategoria = antisqlinyeccion($_POST['categoria'], "int");

    if (intval($_POST['categoria']) == 0) {
        $valido = "N";
        $errores .= " - El campo categoria no puede estar vacio.<br />";
    }
    if (trim($_POST['subcategoria']) == '') {
        $valido = "N";
        $errores .= " - El campo subcategoria no puede estar vacio.<br />";
    }

    $consulta = "
	select * from sub_categorias where descripcion = $subcategoria and idcategoria = $idcategoria  and estado = 1 limit 1
	";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsex->fields['idsubcate'] > 0) {
        $valido = "N";
        $errores .= " - Ya existe otra subcategoria con el mismo nombre.<br />";
    }


    // si todo es correcto inserta
    if ($valido == "S") {


        $consulta = "
		insert into sub_categorias
		(idcategoria, descripcion, idempresa, estado, describebanner, orden, muestrafiltro, borrable, recarga_porc)
		values
		($idcategoria, $subcategoria, 1, 1, '', 0, 'S', 'S', 0)
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
            'idcategoria' => intval($idcategoria),
            'idsubcategoria' => intval($idsubcategoria)
        ];

        // convierte a formato json
        $respuesta = json_encode($res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

        // devuelve la respuesta formateada
        echo $respuesta;
        exit;

    }



}



$buscar = "
SELECT * , categorias.nombre as categoria
FROM sub_categorias
inner join categorias on categorias.id_categoria = sub_categorias.idcategoria
where
sub_categorias.estado = 1
order by idsubcate desc
limit 10
"    ;
$prod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

?>
Formulario abreviado para agregar subcategorias, para mas opciones (foto,editar,borrar,recargos,orden,etc) ir al modulo de categorias: 
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Sub-Categoria </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="subcategoria" id="subcategoria" value="" placeholder="Sub-Categoria" class="form-control"  />                    

	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Categoria *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php

// consulta
$consulta = "
SELECT id_categoria, nombre
FROM categorias
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_POST['categoria'])) {
    $value_selected = htmlentities($_POST['categoria']);
} else {
    $value_selected = htmlentities($rs->fields['categoria']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'categoria',
    'id_campo' => 'categoria',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'id_categoria',

    'value_selected' => $value_selected,

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
    

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
<button type="button" class="btn btn-success" onmouseup="agregar_subcategoria();" ><span class="fa fa-check-square-o"></span> Agregar</button>
        </div>
    </div>

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
        <th>Sub-Categoria</th>
        <th>Categoria</th>

        </tr>
        </thead>
        <tbody>
      <?php while (!$prod->EOF) {




          ?>
      <tr>
	  <td align="center"><?php echo trim($prod->fields['idsubcate']) ?></td>
        <td align="center"><?php echo trim($prod->fields['descripcion']) ?></td>
      <td align="center"><?php echo trim($prod->fields['categoria']) ?></td>
        </tr>
      <?php $prod->MoveNext();
      } ?>
      </tbody>
</table>
</div>