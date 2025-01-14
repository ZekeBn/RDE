<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "613";
require_once("../includes/rsusuario.php");
require_once("../insumos/preferencias_insumos_listas.php");
require_once("../includes/funciones_devolucion.php");
$idinsumo = $_POST['idinsumo'];
$idproducto = $_POST['idproducto'];
$maneja_lote = intval($_POST['maneja_lote']);

$consulta = "";
$deposito_devolucion_defecto = 0;
$deposito_devolucion_defecto = buscar_deposito_devolucion();
?>
<script>
    function cambiar_vencimiento(valor){
        const selectedOption = valor.options[valor.selectedIndex];
        var vto = selectedOption.getAttribute('data-hidden-value');
        $("#vencimiento").val(vto);
    }
</script>
<div class="alert alert-danger alert-dismissible " role="alert" style="display:none;" id="erroresjs">
    <button type="button" class="close"  aria-label="Close" onclick="cerrar_errorestxt()"><span aria-hidden="true" >×</span></button>

    <span id="errorestxt"></span>

</div>
<div class="col-md-12 col-xs-12 " style="margin-bottom:.8rem !important;padding:0;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Deposito</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
		<?php




            // consulta
            $consulta = "
			SELECT iddeposito, descripcion
			FROM gest_depositos
			where
			estado = 1
			and compras = 0
			and tiposala <> 3
			order by descripcion asc
			";


// valor seleccionado
if (isset($_POST['iddeposito'])) {
    $value_selected = htmlentities($_POST['iddeposito']);
} else {
    $value_selected = null;
    if ($deposito_devolucion_defecto > 0) {
        $value_selected = $deposito_devolucion_defecto;
    }
}

// parametros
$parametros_array = [
    'nombre_campo' => 'iddeposito',
    'id_campo' => 'iddeposito',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'iddeposito',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'S'

];
// construye campo
echo campo_select($consulta, $parametros_array);
?>
    </div>
</div>
	<div class="col-md-12 col-xs-12" id="" style="margin-bottom:.8rem !important;padding:0;">

		<label class="control-label col-md-3 col-sm-3 col-xs-12">Seleccionado</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="seleccionado" id="seleccionado" class="form-control" disabled/>                      
		</div>

		
	</div>

	
	
	<?php if ($maneja_lote == 1) { ?>
        <div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
            <label class="control-label col-md-3 col-sm-3 col-xs-12">Lote</label>
            <div class="col-md-9 col-sm-9 col-xs-12">
            <?php

                // consulta

                $consulta = "
                        SELECT 
                        CONCAT('lote: ',lote,' | vto: ',DATE_FORMAT(vencimiento,\"%d/%m/%Y\")) as lote_nombre,
                        (DATE_FORMAT(vencimiento,\"%Y-%m-%d\")) as lote_valor,
                            lote, 
                            vencimiento, 
                            SUM(disponible) as disponible 
                        FROM 
                            gest_depositos_stock 
                        WHERE 
                            idproducto = $idinsumo
                            and disponible > 0 
                        group by 
                        lote
                        ";

	    // valor seleccionado
	    if (isset($_POST['lote_valor'])) {
	        $value_selected = htmlentities($_POST['lote_valor']);
	    } else {
	        $value_selected = $id_moneda_nacional;
	    }



	    // parametros
	    $parametros_array = [
	        'nombre_campo' => 'lote_valor',
	        'id_campo' => 'lote_valor',

	        'nombre_campo_bd' => 'lote_nombre',
	        'id_campo_bd' => 'lote',

	        'value_selected' => $value_selected,
	        'data_hidden' => 'vencimiento',
	        'pricampo_name' => 'Seleccionar...',
	        'pricampo_value' => '',
	        'style_input' => 'class="form-control"',
	        'acciones' => ' onchange="cambiar_vencimiento(this)" "'.$add,
	        'autosel_1registro' => 'N'

	    ];

	    // construye campo
	    echo campo_select($consulta, $parametros_array);
	    ?>              
            </div>
        </div>
        <div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
            <label class="control-label col-md-3 col-sm-3 col-xs-12" >Vto</label>
            <div class="col-md-9 col-sm-9 col-xs-12">
                <input readonly type="date" name="vencimiento" id="vencimiento" value="" placeholder="" class="form-control" />                    
            </div>
        </div>
    <?php } ?>


	<div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
		<label class="control-label col-md-3 col-sm-3 col-xs-12 ">Cantidad </label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input onchange="cargarMedida(this.value)" type="text" name="cantidad" id="cantidad" class="form-control" />
			<span id="medidanombre" style="color: red;"></span>
		</div>
	</div>
	<!-- ///////////////// -->
	<!-- MEDIDAS 2  -->
	<?php if ($preferencias_medidas_referenciales == "S") { ?>
        <div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
            <label class="control-label col-md-3 col-sm-3 col-xs-12">
                <a id="caja_plus" href="javascript:void(0);" style="display:none;" class="btn btn-sm btn-default">
                    <span class="fa fa-plus"></span>
                </a>
                <div id="medida2">Medida2:</div>
            </label>
            <div class="col-md-9 col-sm-9 col-xs-12">
                <input disabled class="form-control" onchange="cargarMedida2(this.value,true)"  aria-describedby="cajaHelp" type="text" name="bulto" id="bulto" value="0" size="10" />	
                <small id="cajaHelp"  style="display:none;" class="form-text text-muted">Sin <strong class="medida2_nombre">Medida2</strong> asignadas,favor agregar en insumos.</small>
            </div>
        </div>

	
        <!-- MEDIDAS INICIO 3 -->
        <div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
            <label class="control-label col-md-3 col-sm-3 col-xs-12">
                <a id="pallet_plus" href="javascript:void(0);" style="display:none;" class="btn btn-sm btn-default">
                    <span class="fa fa-plus"></span>
                </a>	
                <div id="medida3">Medida3:</div>
            </label>
            <div class="col-md-9 col-sm-9 col-xs-12">
                <input disabled aria-describedby="palletHelp" onchange="cargarMedida3(this.value)"  type="text" class="form-control" name="pallet" id="pallet" value="0" size="10" />
                <small id="palletHelp" style="display:none;" class="form-text text-muted">Sin <strong class="medida2_nombre">Medida3</strong>  asignadas,favor agregar en insumos.</small>
            
            </div>
        </div>
        <?php } ?>
        <?php if ($preferencias_medidas_edi == "S") { ?>

        <!-- MEDIDAS EDI  -->
        <div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;display:none;">
            <label class="control-label col-md-3 col-sm-3 col-xs-12">
                <a id="caja_edi_plus" href="javascript:void(0);" style="display:none;" class="btn btn-sm btn-default">
                    <span class="fa fa-plus"></span>
                </a>
                <div id="medida2">Cajas EDI:</div>
            </label>
            <div class="col-md-9 col-sm-9 col-xs-12">
                <input disabled class="form-control" onchange="cargarMedidaEDI(this.value)"  aria-describedby="cajaEdiHelp" type="text" name="bulto_edi" id="bulto_edi" value="0" size="10" />	
                <small id="cajaEdiHelp"  style="display:none;" class="form-text text-muted">Sin <strong class="medida2_nombre">Cant. Cajas EDI</strong> asignadas,favor agregar en insumos.</small>
            </div>
        </div>
	<?php } ?>

    <div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
            <label class="control-label col-md-3 col-sm-3 col-xs-12">
                <a id="comentario_box" href="javascript:void(0);" style="display:none;" class="btn btn-sm btn-default">
                    <span class="fa fa-plus"></span>
                </a>
                <div>Comentario:</div>
            </label>
            <div class="col-md-9 col-sm-9 col-xs-12">
                <input class="form-control"  aria-describedby="cajaEdiHelp" type="text" name="comentario" id="comentario" placeholder="Comentario"  size="10" />	
            </div>
    </div>

	<!-- FIN DE MEDIDAS -->

    <!-- <div id="dropdow_almacenamiento"><?php //require_once("./almacenamiento_dropdown.php");?></div> -->
	
	
	<div class="clearfix"></div>

	<div class="col-md-12 col-xs-12"  style="text-align:right;">
		<input type="hidden" name="ocinsumo" id="ocinsumo" value="" />
		<button  class="btn btn-success btn_agregar_insumo" id="btn_agregar" onclick="agregar_insumo_carrito();"><span class="fa fa-plus"></span>&nbsp;Agregar</button>
	</div>
