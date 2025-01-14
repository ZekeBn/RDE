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
$consulta = "
select tmp_ventares_cab.idtmpventares_cab, tmp_ventares_cab.idsucursal
from tmp_ventares_cab
where 
tmp_ventares_cab.estado <> 6
and tmp_ventares_cab.finalizado = 'S'
and tmp_ventares_cab.registrado = 'N'
and idtmpventares_cab = $idpedido
limit 1
";
///echo $consulta;
$rsped = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
//$idpedido=intval($rsped->fields['idtmpventares_cab']);
$idsucursal_old = intval($rsped->fields['idsucursal']);

?><div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idsucu, nombre
FROM sucursales
where
estado = 1
and idsucu <> $idsucursal_old
order by nombre asc
";

// valor seleccionado
if (isset($_POST['idsucursal_ped'])) {
    $value_selected = htmlentities($_POST['idsucursal_ped']);
} else {
    $value_selected = htmlentities($rs->fields['idsucu']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsucursal_ped',
    'id_campo' => 'idsucursal_ped',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idsucu',

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
       <button type="button" class="btn btn-success" onMouseUp="transfer_sucursal(<?php echo $idpedido ?>)" ><span class="fa fa-check-square-o"></span> Transferir</button>

        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />
