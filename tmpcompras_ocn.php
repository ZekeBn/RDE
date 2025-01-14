<?php

if ($_POST['idproveedor'] > 0) {
    $idproveedor = intval($_POST['idproveedor']);
}
$idproveedor = intval($idproveedor);
// consulta
$consulta = "
select 
ocnum,
CONCAT('Orden N.: ',ocnum,' | Fecha: ',DATE_FORMAT(fecha,\"%d/%m/%Y\")) as ocdesc,
(select nombre from proveedores where idproveedor = compras_ordenes.idproveedor ) as proveedor
from compras_ordenes 
where 
 estado = 2 
 and ocnum not in (select ocnum from compras where ocnum is not null and estado <> 6)
 and compras_ordenes.idproveedor = $idproveedor
 
order by fecha desc
limit 50
";
//echo $consulta;
// valor seleccionado
if (isset($_POST['ocnum'])) {
    $value_selected = htmlentities($_POST['ocnum']);
} else {
    $value_selected = htmlentities($rstran->fields['ocnum']);
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
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
