 <?php
require_once("includes/funciones.php");
$tpago = intval($_POST['mediopago']);

//print_r($_POST);

//$generico="44444401-7";
?>
 <div  id="agrega_clie"  style="min-height: 450px; width: 100%;">

<div class="col-md-6 col-sm-6 form-group">
	<div class="col-md-9 col-sm-9 col-xs-12"> 
<a href="javascript:void(0);"  class="btn btn-sm btn-default" onClick="retornar(<?php echo $tpago ?>,<?php echo intval($_POST['idpedido']);?>);"><span class="fa fa-reply"></span> Regresar</a>
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">

    <div class="btn-group" data-toggle="buttons">

    <input type="radio" name="fisi" id="r1" value="1" checked="checked"   class="flat" onClick="cambia(this.value)"> &nbsp; Persona &nbsp;

    <input type="radio" name="fisi" id="r2" value="2"  class="flat" onClick="cambia(this.value)" > Empresa

    </div>

</div>
   <!-- <a href="javascript:void(0);" onMouseUp="carga_ruc_h(<?php echo intval($_POST['idpedido']);?>);" class="btn btn-sm btn-default" title="Buscar en la SET" data-toggle="tooltip" data-placement="right"  data-original-title="Buscar en la SET" style="float:left;"><span class="fa fa-search"></span></a> -->
<div class="clearfix"></div>

        <hr />    
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12"> RUC * </label>
	<div class="col-md-9 col-sm-9 col-xs-12 input-group mb-3">
        <input type="text" name="ruccliente" id="ruccliente" value="" placeholder="RUC" class="form-control". style="width:80%"  />
        <div class="input-group-append">
        	<button class="btn btn-outline-secondary" type="button" onMouseUp="carga_ruc_h(<?php echo intval($_POST['idpedido']);?>);" title="Buscar en la SET" data-toggle="tooltip" data-placement="right"  data-original-title="Buscar en la SET"><span class="fa fa-search"></span></button>
        </div>        
	</div>
</div>



<div class="col-md-6 col-sm-6 form-group" style="display:none;" id="rz1_box">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Razon Social *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="rz1" id="rz1" value="" placeholder="Razon Social" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="nombreclie_box">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombres *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombreclie" id="nombreclie" value="" placeholder="Nombres" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="apellidos_box">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Apellidos *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="apellidos" id="apellidos" value="" placeholder="Apellidos" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="cedula_box">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Cedula </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="cedula" id="cedula" value="" placeholder="Cedula" class="form-control"  />                    
	</div>
</div>

<div class="clearfix"></div>

<div class="col-md-6 col-sm-6 form-group" id="telefonoclie_box">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Telefono </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="telefonoclie" id="telefonoclie" value="" placeholder="Tel&eacute;fono" class="form-control"  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group" id="direccioncliente_box">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Direccion </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="direccioncliente" id="direccioncliente" value="" placeholder="Direccion" class="form-control"  />                    
	</div>
</div>
	 
<div class="col-md-6 col-sm-6 form-group" id="mailcliente_box">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">E-MAIL </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="mailcliente" id="mailcliente" value="" placeholder="E-Mail" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="ruc_especial_box">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Ruc Especial </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['ruc_especial'])) {
    $value_selected = htmlentities($_POST['ruc_especial']);
} else {
    $value_selected = 'N';
}
// opciones
$opciones = [
    'RUC NORMAL' => 'N',
    'RUC ESPECIAL (DIPLOMATICOS, ONG, ETC)' => 'S',
];
// parametros
$parametros_array = [
    'nombre_campo' => 'ruc_especial',
    'id_campo' => 'ruc_especial',

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

<div class="clearfix"></div><br /><br />

<div class="form-group">
    <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
	<button type="submit"  name="reg" class="btn btn-success" onClick="nclie(<?php echo $tpago ?>,<?php echo intval($_POST['idpedido']);?>);"><span class="fa fa-check-square-o"></span> Registrar Cliente</button>
    </div>
</div>
    
    


<input  type="hidden" name="tipopagoselec" id="tipopagoselec" value="<?php echo $tpago?>" /> 

<input type="hidden" name="idpedido" id="idpedido"  style="height:30px;width:100%" value="<?php echo intval($_POST['idpedido']); ?>" />
<input type="hidden" name="mediopagooc" id="mediopagooc" value="<?php echo $tpago; ?>" />



</div>