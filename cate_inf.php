 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "11";
$submodulo = "302";

require_once("includes/rsusuario.php");


// consulta
$consulta = "
SELECT id_categoria, nombre
FROM categorias
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_REQUEST['idcategoria'])) {
    $value_selected = htmlentities($_REQUEST['idcategoria']);
} else {
    $value_selected = htmlentities($rs->fields['idcategoria']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idcategoria',
    'id_campo' => 'idcategoria',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'id_categoria',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  onchange="subcategorias(this.value);"; ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);



?>
