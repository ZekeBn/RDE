 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";
require_once("includes/rsusuario.php");




$idreceta_detalle = intval($_POST['idreceta_detalle']);
if ($idreceta_detalle == 0) {
    //header("location: recetas_detalles.php");
    echo "No se recibio el idreceta";
    exit;
}

// consulta a la tabla
$consulta = "
select recetas_detalles.* , insumos_lista.idinsumo, medidas.nombre as medida
from recetas_detalles 
inner join ingredientes on ingredientes.idingrediente = recetas_detalles.ingrediente
inner join insumos_lista on insumos_lista.idinsumo = ingredientes.idinsumo
inner join medidas on medidas.id_medida = insumos_lista.idmedida 
where 
idreceta_detalle = $idreceta_detalle
limit 1
";
//echo $consulta;exit;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idreceta_detalle = intval($rs->fields['idreceta_detalle']);
$idinsumo = intval($rs->fields['idprod']);
$medida = antixss($rs->fields['medida']);
if ($idreceta_detalle == 0) {
    //header("location: recetas_detalles.php");
    echo "Receta inexistente o ya fue borrado.";
    exit;
}




if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";



    // recibe parametros
    //$idreceta_detalle=antisqlinyeccion($_POST['idreceta_detalle'],"text");
    //$idreceta=antisqlinyeccion($_POST['idreceta'],"int");
    //$idprod=antisqlinyeccion($_POST['idprod'],"int");
    //$ingrediente=antisqlinyeccion($_POST['ingrediente'],"int");
    $cantidad = antisqlinyeccion($_POST['cantidad_ed'], "text");
    $sacar = antisqlinyeccion($_POST['sacar_ed'], "text");
    //$alias=antisqlinyeccion($_POST['alias'],"text");
    //$idempresa=antisqlinyeccion($_POST['idempresa'],"int");





    if (floatval($_POST['cantidad_ed']) <= 0) {
        $valido = "N";
        $errores .= " - El campo cantidad no puede ser cero o inferior.<br />";
    }
    if (trim($_POST['sacar_ed']) != 'S' && trim($_POST['sacar_ed']) != 'N') {
        $valido = "N";
        $errores .= " - El campo sacar no puede estar vacio.<br />";
    }


    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
        update recetas_detalles
        set
            cantidad=$cantidad,
            sacar=$sacar
        where
            idreceta_detalle = $idreceta_detalle
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
        delete from recetas_detalles where cantidad <= 0
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // genera array con los datos
        $arr = [
            'valido' => $valido,
            'errores' => $errores
        ];

        //print_r($arr);

        // convierte a formato json
        $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

        // devuelve la respuesta formateada
        echo $respuesta;
        exit;

    }

}


$idreceta_detalle = intval($_POST['idreceta_detalle']);
if ($idreceta_detalle == 0) {
    echo "Que estas intentando hacer? ;o)";
    exit;
}
$consulta = "
SELECT *, insumos_lista.idproducto as prodins, recetas_detalles.idprod as prodrec, insumos_lista.costo,
ingredientes.idingrediente
FROM recetas_detalles
inner join ingredientes on recetas_detalles.ingrediente = ingredientes.idingrediente
inner join insumos_lista on insumos_lista.idinsumo = ingredientes.idinsumo
inner join medidas on medidas.id_medida = insumos_lista.idmedida
where
recetas_detalles.idreceta_detalle = $idreceta_detalle
";
$rsrec = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idinsumo = intval($rsrec->fields['idinsumo']);
//echo $consulta;exit;

?><?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>

       <form id="form1" name="form1" method="post" action="">
       
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Ingrediente *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
Select idingrediente,descripcion
from ingredientes 
inner join insumos_lista on insumos_lista.idinsumo=ingredientes.idinsumo
where 
ingredientes.estado = 1
and insumos_lista.estado = 'A'
and insumos_lista.hab_invent = 1
and insumos_lista.idinsumo = $idinsumo
order by descripcion asc
 ";


// valor seleccionado
if (isset($_POST['ingredientes_ed'])) {
    $value_selected = htmlentities($_POST['ingredientes_ed']);
} else {
    $value_selected = htmlentities($rsrec->fields['idingrediente']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'ingredientes_ed',
    'id_campo' => 'ingredientes_ed',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idingrediente',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" readonly ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);


?>            
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Medida *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input name="medida_ed" type="text" disabled="disabled" id="medida_ed"  value="<?php echo $medida ?>" readonly class="form-control" />             
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cantidad *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="cantidad_ed" id="cantidad_ed" value="<?php  if (isset($_POST['cantidad_ed'])) {
        echo htmlentities($_POST['cantidad_ed']);
    } else {
        echo floatval($rs->fields['cantidad']);
    }?>" placeholder="Cantidad" class="form-control" required />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Permite Sacar *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">       
<?php
// valor seleccionado
if (isset($_POST['sacar_ed'])) {
    $value_selected = htmlentities($_POST['sacar_ed']);
} else {
    $value_selected = 'S';
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'sacar_ed',
    'id_campo' => 'sacar_ed',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);

?>           
    </div>
</div>
    
    


<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
       <button type="button" class="btn btn-success" onMouseUp="registra_edicion(<?php echo $idreceta_detalle ?>);" ><span class="fa fa-check-square-o"></span> Registrar</button>

        </div>
    </div>
    

  <input type="hidden" name="MM_update" value="form1" />

<br />
</form>
<div class="clearfix"></div>
<br /><br />
