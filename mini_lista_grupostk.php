 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";
require_once("includes/rsusuario.php");



// consulta
$consulta = "
SELECT idgrupoinsu, nombre
FROM grupo_insumos
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_POST['idgrupoinsu'])) {
    $value_selected = htmlentities($_POST['idgrupoinsu']);
} else {
    $value_selected = htmlentities($rs->fields['idgrupoinsu']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idgrupoinsu',
    'id_campo' => 'idgrupoinsu',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idgrupoinsu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);


?>
