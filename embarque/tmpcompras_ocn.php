<?php

$modulo = "42";
$submodulo = "598";
require_once("../includes/rsusuario.php");
require_once("../compras_ordenes/preferencias_compras_ordenes.php");


// if($_POST['idproveedor'] > 0){
// 	$idproveedor=intval($_POST['idproveedor']);
// }
// $idproveedor=intval($idproveedor);
// consulta
// and ocnum not in (select ocnum from compras where ocnum is not null and estado <> 6) TODO: PREFERENCIA PARA ACTIVAR
// and carga_completa='N' TODO: PREFERENCIA PARA CAMBIAR CON UNA ORDEN POR COMPRA

$preferencia_add = null;
if ($preferencias_facturas_multiples == "S") {
    $preferencia_add = " estado=2 and estado_orden=1  ";

} else {
    $preferencia_add = " and ocnum not in (select ocnum from compras where ocnum is not null and estado <> 6) ";
}
$consulta = "
select 
ocnum,
CONCAT('Orden N.: ',ocnum,' | Fecha: ',DATE_FORMAT(fecha,\"%d/%m/%Y\")) as ocdesc,
(select nombre from proveedores where idproveedor = compras_ordenes.idproveedor ) as proveedor
from compras_ordenes 
where 
 $preferencia_add
 order by fecha desc
 limit 50
 ";
//  and compras_ordenes.idproveedor = $idproveedor
//echo $consulta;
// valor seleccionado
$whereadd = null;
$hidden = null;
if (isset($_POST['ocnum'])) {
    $value_selected = htmlentities($_POST['ocnum']);
}
if (isset($ocn)) {
    $value_selected = $ocn;
    $whereadd = " disabled ";
    $hidden = "<input type='hidden' name='ocnHidden' id='ocnHidden' value='$ocn'>";
}
if (!isset($_POST['ocnum']) && !isset($ocn)) {
    $value_selected = $rs->fields['ocnum'];
}
if ($rs->fields['ocnum']) {
    $value_selected = $rs->fields['ocnum'];
    $whereadd = " disabled ";
    $hidden = "<input type='hidden' name='ocnHidden' id='ocnHidden' value='$value_selected'>";

}

// parametros
$parametros_array = [
    'nombre_campo' => 'ocnum',
    'id_campo' => 'ocnum',

    'nombre_campo_bd' => 'ocdesc',
    'id_campo_bd' => 'ocnum',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"'.$whereadd.' ',
    'acciones' => '  ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
echo $hidden;
