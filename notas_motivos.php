<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");

//print_r($_POST);
$agregar = intval($_POST['agregar']);
if ($agregar == 1) {
    $describe = antisqlinyeccion($_POST['descripcion'], 'text');
    //echo $describe;
    $buscar = "select *  from nota_cred_motivos where estado=1 and descripcion=$describe";
    $rsme = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    //echo $buscar;
    if ($rsme->fields['descripcion'] == '') {
        $insertar = "Insert into nota_cred_motivos (estado,descripcion,registrado_por) values (1,$describe,$idusu)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        $resp = 1;
    } else {
        $resp = 'El nombre ingresado ya existe en el sistema y se encuentra activo.';

    }


}






// consulta
$consulta = "
SELECT idmotivo, descripcion
FROM nota_cred_motivos
where
estado = 1
order by descripcion asc
 ";

// valor seleccionado
if (isset($_POST['motivonc'])) {
    $value_selected = htmlentities($_POST['motivonc']);
} else {
    //$value_selected=htmlentities($rs->fields['idsucu']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'motivonc',
    'id_campo' => 'motivonc',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idmotivo',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?><?php
$buscar = "select *  from nota_cred_motivos where estado=1";
$rsl = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tr = $rsl->RecordCount();

if ($tr > 0) {

    ?>
 <input type="hidden" name="ocrespu" id="ocrespu" value="<?php echo $resp; ?>" />
<?php } ?>