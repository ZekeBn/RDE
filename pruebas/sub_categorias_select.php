<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");

print_r($_POST);

// consulta
$id = null;

$consulta1 = "
SELECT idsubcate, descripcion
FROM sub_categorias
where
estado = 1
order by descripcion asc
";
if (isset($_POST["idcategoria"])) {
    $id = intval($_POST["idcategoria"]);


    $consulta1 = "
  SELECT idsubcate, descripcion
  FROM sub_categorias
  where
  estado = 1
  and idcategoria = $id 
  order by descripcion asc
  ";
    // echo $consulta1;
}

// valor seleccionado
if (isset($_POST['idsubcate'])) {
    $value_selected = htmlentities($_POST['idsubcate']);
} else {
    $value_selected = htmlentities($rs->fields['idsubcate']);
}

// parametros
$parametros_array = [
  'nombre_campo' => 'idsubcate',
  'id_campo' => 'idsubcate',

  'nombre_campo_bd' => 'descripcion',
  'id_campo_bd' => 'idsubcate',

  'value_selected' => $value_selected,

  'pricampo_name' => 'Seleccionar...',
  'pricampo_value' => '',
  'style_input' => 'class="form-control"',
  'acciones' => '   ',
  'autosel_1registro' => 'S'

];

// construye campo

echo campo_select($consulta1, $parametros_array);
