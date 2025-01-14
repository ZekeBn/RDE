 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "333";
require_once("includes/rsusuario.php");


$idpedido = intval($_POST['idpedido']);
if ($idpedido == 0) {
    echo "Pedido inexistente!";
    exit;
}
//print_r($_POST);exit;


?><div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Franquicia *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idfranquicia, nombre_franquicia
FROM franquicia
where
estado = 1
and url_franquicia is not null
order by nombre_franquicia asc
 ";

// valor seleccionado
if (isset($_POST['idfranquicia'])) {
    $value_selected = htmlentities($_POST['idfranquicia']);
} else {
    $value_selected = htmlentities($rs->fields['idfranquicia']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idfranquicia',
    'id_campo' => 'idfranquicia',

    'nombre_campo_bd' => 'nombre_franquicia',
    'id_campo_bd' => 'idfranquicia',

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
    </div>
</div><div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
       <button type="button" class="btn btn-success" onMouseUp="transfer_franquicia(<?php echo $idpedido ?>)" ><span class="fa fa-check-square-o"></span> Transferir</button>

        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />
