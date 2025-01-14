<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$dirsup = "S";
$submodulo = "134";
require_once("../includes/rsusuario.php");
require_once("../insumos/preferencias_insumos_listas.php");


$idconteo = $_POST['idconteo'];
$unicose = $_POST['unicose'];
//<?php  require_once("./buscador_conteo_deposito_edit.php");
//id_insumo_select
// (SELECT ) as lote,
$consulta = "SELECT conteo_detalles.*, conteo.iddeposito,
(SELECT gest_deposito_almcto.idalmacto from gest_deposito_almcto where gest_deposito_almcto.idalm = conteo_detalles.idalm ) as idalmacto,
(SELECT gest_deposito_almcto.filas from gest_deposito_almcto where gest_deposito_almcto.idalm = conteo_detalles.idalm ) as filas,
(SELECT gest_deposito_almcto.columnas from gest_deposito_almcto where gest_deposito_almcto.idalm = conteo_detalles.idalm ) as columnas,
(SELECT gest_deposito_almcto.tipo_almacenado from gest_deposito_almcto where gest_deposito_almcto.idalm = conteo_detalles.idalm ) as tipo_almacenado
FROM conteo_detalles
INNER JOIN conteo on conteo.idconteo = conteo_detalles.idconteo
WHERE unicose=$unicose";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// var_dump($rs->fields);exit;
$idinsumo = $rs->fields['idinsumo'];
$descripcion = $rs->fields['descripcion'];
$idmedida_ref = $rs->fields['idmedida_ref'];
$iddeposito = $rs->fields['iddeposito'];
$idalmacto = $rs->fields['idalmacto'];
$dropdown_edit_almacenamiento = true;
$form_edit_div_id = "'#editar_conteo '"; //editar_conteo
$tipo_almacenado = $rs->fields['tipo_almacenado'];
$filas = $rs->fields['filas'];
$columnas = $rs->fields['columnas'];

$buscar = "SELECT id_medida FROM medidas WHERE nombre like '%EDI' ";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$id_cajas_edi = intval($rsd->fields['id_medida']);

$consulta = "SELECT maneja_lote,idmedida2,idmedida3,idmedida,
cant_medida2,cant_medida3,cant_caja_edi,
(SELECT nombre FROM medidas WHERE medidas.id_medida = idmedida2 ) as medida2,
(SELECT nombre FROM medidas WHERE medidas.id_medida = idmedida3 ) as medida3
FROM insumos_lista 
WHERE idinsumo=$idinsumo";
$rs_lote = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idmedida = $rs_lote->fields['idmedida'];
$idmedida2 = $rs_lote->fields['idmedida2'];
$cant_medida2 = $rs_lote->fields['cant_medida2'];
$idmedida3 = $rs_lote->fields['idmedida3'];
$cant_medida3 = $rs_lote->fields['cant_medida3'];
$cant_caja_edi = $rs_lote->fields['cant_caja_edi'];
$maneja_lote = $rs_lote->fields['maneja_lote'];
$medida2 = $rs_lote->fields['medida2'];
$medida3 = $rs_lote->fields['medida3'];




$cantidad_medida_inicial = floatval($rs->fields['cantidad_contada']);
$cantidad_medida2_inicial = 0;
$cantidad_medida3_inicial = 0;
$cantidad_medida_edi_inicial = 0;


if ($idmedida_ref != $idmedida1 && $idmedida_ref != $idmedida2 && $idmedida_ref != $idmedida3 && $idmedida_ref == $id_cajas_edi) {
    $cantidad_medida_edi_inicial = ($cantidad_medida_inicial / $cant_caja_edi);
}
if ($idmedida_ref != $idmedida1 && $idmedida_ref != $idmedida2 && $idmedida_ref == $idmedida3 && $idmedida_ref != $id_cajas_edi) {
    $cantidad_medida3_inicial = $cantidad_medida_inicial / ($cant_medida3 * $cant_medida2);
    $cantidad_medida2_inicial = $cantidad_medida_inicial / ($cant_medida2);
}
if ($idmedida_ref != $idmedida1 && $idmedida_ref == $idmedida2 && $idmedida_ref != $idmedida3 && $idmedida_ref != $id_cajas_edi) {
    $cantidad_medida2_inicial = $cantidad_medida_inicial / $cant_medida2;
}

?>
<script>
   
    function cambiar_vencimiento_modal(valor){
            const selectedOption = valor.options[valor.selectedIndex];
            var vto = selectedOption.getAttribute('data-hidden-value');
            $("#editar_conteo #vencimiento").val(vto);
        }

        function determinarUnidadCompra_modal(){
        var tipos_carga ={"unidad": 1,"bulto":2,"pallet":3, "bulto_edi":4}
        var tipo = tipos_carga["unidad"];
        var medida = document.querySelector('#editar_conteo #cantidad');
        var id_tipo = 0;
        var medida2 =document.querySelector('#editar_conteo #bulto');
        var medida3 = document.querySelector('#editar_conteo #pallet');
        var medida_edi = document.querySelector('#editar_conteo #bulto_edi');
        var cantidad_medida = 0;
        var cantidad_medida2 = 0;
        var cantidad_medida3 = 0;
        var cantidad_edi = 0;
        if(medida){
            cantidad_medida = parseFloat(medida?.value);
        }
        if(medida2){
            cantidad_medida2 = parseFloat(medida2?.value);
        }
        if(medida3){
            cantidad_medida3 = parseFloat(medida3?.value);
        }
        if(medida_edi){
            cantidad_edi = parseFloat(medida_edi?.value);
        } 
        var cantidad_cargada=0;
        var cantidad_ref=0;
        if(cantidad_medida3 != 0){
            tipo = tipos_carga["pallet"];
            id_tipo = <?php echo $idmedida3 ? $idmedida3 : 0; ?>;
            cantidad_cargada = cantidad_medida3;
        }
        if(cantidad_medida2 != 0 && cantidad_medida3 == 0){
            tipo = tipos_carga["bulto"];
            id_tipo =  <?php echo $idmedida2 ? $idmedida2 : 0; ?>;
            cantidad_cargada = cantidad_medida2;
        }
        if(cantidad_edi != 0){
            tipo = tipos_carga["bulto_edi"];
            id_tipo =  <?php echo $id_cajas_edi ? $id_cajas_edi : 0; ?>;
            cantidad_cargada = cantidad_edi;
        }
        if(cantidad_medida != 0 && cantidad_medida2 == 0 && cantidad_medida3 == 0 && cantidad_edi == 0){
                tipo = tipos_carga["unidad"];
                id_tipo =<?php echo $idmedida ? $idmedida : 0; ?>;
                cantidad_cargada = cantidad_medida;
        }
        if(cantidad_medida == 0 && cantidad_medida2 == 0 && cantidad_medida3 == 0 && cantidad_edi == 0){
            tipo = tipos_carga["unidad"];
            id_tipo =<?php echo $idmedida ? $idmedida : 0; ?>;
            cantidad_cargada = cantidad_medida;
        }
        return {"tipo": tipo, "id_tipo": id_tipo};
    }


    function editar_articulo_post(){
        var lote = $("#editar_conteo #lote_valor").val();
        var vencimiento = $("#editar_conteo #vencimiento").val();
        var cantidad = $("#editar_conteo #cantidad").val();
        var idmedida = determinarUnidadCompra_modal()['id_tipo'];
        var idalmacto = $("#editar_conteo #idalmacto").val(); 
        var select_idalm = $("#editar_conteo #idalm")[0];
		var html_idalm = select_idalm.options[select_idalm.selectedIndex];
		var idalm = html_idalm.value;
		var tipo_almacenamiento = html_idalm.getAttribute('data-hidden-value3');
        var fila = convertirAEntero($("#editar_conteo #fila").val());
		var columna = convertirAEntero($("#editar_conteo #columna").val());
		var idpasillo = $("#editar_conteo #idpasillo").val();
		var idconteo = <?php echo intval($idconteo);?>;
		var unicose = <?php echo intval($unicose);?>;

        var valido = "S";
        var errores="";
        <?php if ($maneja_lote == 1) { ?>
            if(lote == '' || lote == undefined ){
                valido="N";
                errores=errores+'- Debe indicar el Lote. \n<br>';	
            }
        <?php } ?>

        if(tipo_almacenamiento==2 && idpasillo ==""){
			errores=errores+"Debe indicar el pasillo al que corresponde el articulo. <br/>";
		}
		if(tipo_almacenamiento==1 && (fila =="" || columna =="")){
			errores=errores+"Debe indicar la fila y columna al que corresponde el articulo. <br/>";
		}


       

        if(valido=="N"){
            alerta_error(errores); //
        }

        if(valido=="S"){
                
                var parametros_array = {
                    "idinsumo"		        : <?php echo $idinsumo; ?>,
                    "cantidad"		        : cantidad,
                    "lote"			        : lote,
                    "vencimiento"	        : vencimiento,
                    "idmedida"		        : idmedida,
                    "idalamcto"		        : idalmacto,
                    "idalm"		  	        : idalm,
                    "fila"		  	        : fila,
                    "columna"		        : columna,
                    "idpasillo"		        : idpasillo,
                    "idconteo"		        : idconteo,
                    "unicose"               : unicose,
                    "tipo_almacenamiento"   : tipo_almacenamiento,
                    "editar"		        : 1
                };
                 
                    // $.ajax({
                    //     data:  parametros_array,
                    //     url:   'verificar_conteo.php',
                    //     type:  'post',
                    //     beforeSend: function () {
                            
                    //     },
                    //     success:  function (response) {
                    //         console.log(response);
                    //         if(JSON.parse(response)["success"]==true){

                    //             errores=errores+JSON.parse(response)["error"];
                    //             alerta_error(errores);
                    //         }else{
                                
                                $.ajax({		  
                                    data:  parametros_array,
                                    url:   'conteo_por_productos_depositos.php',
                                    type:  'post',
                                    cache: false,
                                    timeout: 3000,  // I chose 3 secs for kicks: 5000
                                    crossDomain: true,
                                    beforeSend: function () {
                                        $("#conteo_productos").html('Cargando...');  
                                    },
                                    success:  function (response) {
                                        $("#conteo_productos").html(response);
                                        cerrar_pop();
                                        
                                    },
                                    error: function(jqXHR, textStatus, errorThrown) {
                                    errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
                                    }
                                }).fail( function( jqXHR, textStatus, errorThrown ) {
                                    errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
                                });


                //             }
                //         }
                //     });

                }
    }
    function cerrar_pop(){
				$("#ventanamodal").modal("hide");
    }
    function nl2br (str, is_xhtml) {
        var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>'; // Adjust comment to avoid issue on phpjs.org display
        return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
    }
    function errores_ajax_manejador(jqXHR, textStatus, errorThrown, tipo){
            // error
            if(tipo == 'error'){
                if(jqXHR.status == 404){
                alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
                }else if(jqXHR.status == 0){
                alert('Se ha rechazado la conexión.');
                }else{
                alert(jqXHR.status+' '+errorThrown);
                }
            // fail
            }else{
                if (jqXHR.status === 0) {
                alert('No conectado: verifique la red.');
                } else if (jqXHR.status == 404) {
                alert('Pagina no encontrada [404]');
                } else if (jqXHR.status == 500) {
                alert('Internal Server Error [500].');
                } else if (textStatus === 'parsererror') {
                alert('Requested JSON parse failed.');
                } else if (textStatus === 'timeout') {
                alert('Tiempo de espera agotado, time out error.');
                } else if (textStatus === 'abort') {
                alert('Solicitud ajax abortada.'); // Ajax request aborted.
                } else {
                alert('Uncaught Error: ' + jqXHR.responseText);
            }
        }
    }


    //////////////////////////////////////////////////////////////////////////////////
    function cargarMedida_modal(){
		$('#editar_conteo #bulto').val(0);
		$('#editar_conteo #bulto_edi').val(0);
		$('#editar_conteo #pallet').val(0);
        var cant_unidad = $("#cant").val();


		var medida2_input = document.getElementById('bulto');
		var cant_medida2 = medida2_input.getAttribute('data-hidden-cant');

		var medida3_input = document.getElementById('pallet');
		var cant_medida3 = medida3_input.getAttribute('data-hidden-cant');

		if(  cant_medida2 != undefined && cant_unidad%cant_medida2 == 0 ){
			medida2_input.value = (cant_unidad/cant_medida2);
		}

		if(  cant_medida3 != undefined && (cant_unidad/cant_medida2)%cant_medida3 == 0 ){
			medida3_input.value = ((cant_unidad/cant_medida2)/cant_medida3);
		}
	}
	function cargarMedida2_modal(value,limpiar){
		var medida2_input = document.querySelector('#editar_conteo #bulto');
		var cant_medida = <?php echo $cant_medida2 ? $cant_medida2 : 1; ?>;
		$("#editar_conteo #cantidad").val(value*cant_medida)
		if(limpiar == true){
			$('#editar_conteo #pallet').val(0);
			$('#editar_conteo #bulto_edi').val(0);
		}
        var medida3_input = document.getElementById('pallet');
		var cant_medida3 = medida3_input.getAttribute('data-hidden-cant');

	
		if(  cant_medida3 != undefined && value%cant_medida3 == 0 ){
			medida3_input.value = (value/cant_medida3);
		}

	}
	function cargarMedida3_modal(value){
		var medida2_input = document.querySelector('#editar_conteo #pallet');
		var cant_medida = <?php echo $cant_medida3 ? $cant_medida3 : 1; ?>;
		$("#editar_conteo #bulto").val(value*cant_medida);
		$("#editar_conteo #bulto_edi").val(0);
		cargarMedida2_modal(value*cant_medida,false);
	}
	function cargarMedidaEDI_modal(value){
		var medida2_input = document.querySelector('#editar_conteo #bulto_edi');
		var cant_medida = <?php echo $cant_caja_edi ? $cant_caja_edi : 1; ?>;
		$('#editar_conteo #bulto').val(0);
		$('#editar_conteo #pallet').val(0);
		$("#editar_conteo #cantidad").val(value*cant_medida)
	}

    function seleccionar_almacenamiento_edit(idalmacto){
		var parametros = {
			"idalmacto" 	: idalmacto,
			"iddeposito"	: <?php echo $iddeposito?>,
            "dropdown_edit_almacenamiento":true,
            "form_edit_div_id"  :" #editar_conteo "
		};
		console.log(parametros);
		$.ajax({
			data:  parametros,
			url:   'almacenamiento_dropdown.php',
			type:  'post',
			beforeSend: function () {
			},
			success:  function (response) {
				$("#editar_conteo #dropdow_almacenamiento").html(response);
			}
		});	
	}

   
    function cerrar_errores_articulos_modal(event){
        event.preventDefault();
        $('#boxErroresArticulosModal').removeClass('show');
        $('#boxErroresArticulosModal').addClass('hide');
    }
    function alerta_error(error){
        $("#erroresArticulosModal").html(error);
        $('#boxErroresArticulosModal').addClass('show');
    }

</script>
<div id="editar_conteo">
    
        <div class="alert alert-danger alert-dismissible fade in hide" role="alert" id="boxErroresArticulosModal">
            <button type="button" class="close" onclick="cerrar_errores_articulos_modal(event)" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
            <strong>Errores:</strong><br /><p id="erroresArticulosModal"></p>
        </div>

        <div class="col-md-12 col-xs-12"  style="margin-bottom:.8rem !important;padding:0;">
                <label class="control-label col-md-3 col-sm-3 col-xs-12">Seleccionado</label>
                <div class="col-md-9 col-sm-9 col-xs-12">
                    <input type="text" name="seleccionado" id="seleccionado" value="<?php echo $descripcion; ?>" class="form-control" disabled/>
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
                $value_selected = $rs->fields['lote'];
            }
            // echo $value_selected;exit;


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
                'acciones' => ' onchange="cambiar_vencimiento_modal(this)" "'.$add,
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
                    <input readonly type="date" name="vencimiento" id="vencimiento" value="<?php echo $rs->fields['vencimiento']; ?>" placeholder="" class="form-control" />
                </div>
            </div>
        <?php } ?>
    
    
        <div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
            <label class="control-label col-md-3 col-sm-3 col-xs-12 ">Cantidad </label>
            <div class="col-md-9 col-sm-9 col-xs-12">
                <input onchange="cargarMedida_modal(this.value)" value="<?php echo $cantidad_medida_inicial; ?>" type="text" name="cantidad" id="cantidad" class="form-control" />
                <span id="medidanombre" style="color: red;"></span>
            </div>
        </div>
        <!-- ///////////////// -->
        <!-- MEDIDAS 2  -->
        <?php if ($preferencias_medidas_referenciales == "S") { ?>
            <?php if ($idmedida2 > 0 && $cant_medida2 > 0) { ?>
            
                <div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
                    <label class="control-label col-md-3 col-sm-3 col-xs-12">
                        
                        <div id="medida2"><?php echo $medida2; ?>:</div>
                    </label>
                    <div class="col-md-9 col-sm-9 col-xs-12">
                    <?php if ($cant_medida2 > 0) { ?>
                        <input  class="form-control" onchange="cargarMedida2_modal(this.value,true)"  aria-describedby="cajaHelp" type="text" name="bulto" id="bulto" value="<?php echo $cantidad_medida2_inicial;?>" size="10" />	
                        <?php } else { ?>
                            <input disabled class="form-control" onchange="cargarMedida2_modal(this.value,true)"  aria-describedby="cajaHelp" type="text" name="bulto" id="bulto" value="0" size="10" />	
                    <?php } ?>
                    <small id="cajaHelp"  style="display:none;" class="form-text text-muted">Sin <strong class="medida2_nombre">Medida2</strong> asignadas,favor agregar en insumos.</small>
                    </div>
                </div>
            <?php } ?>
    
    
            <!-- MEDIDAS INICIO 3 -->
            <?php if ($idmedida2 > 0 && $cant_medida2 > 0) { ?>
                <div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
                    <label class="control-label col-md-3 col-sm-3 col-xs-12">
                        <a id="pallet_plus" href="javascript:void(0);" style="display:none;" class="btn btn-sm btn-default">
                            <span class="fa fa-plus"></span>
                        </a>
                        <?php if ($medida3) { ?>
                            <div id="medida3"><?php echo $medida3; ?>:</div>
                        <?php } else { ?>
                            <div id="medida3">Medida3:</div>
                        <?php } ?>
                    </label>
                    <div class="col-md-9 col-sm-9 col-xs-12">
                        <?php if ($cant_medida3 > 0) { ?>
                            <input aria-describedby="palletHelp" onchange="cargarMedida3_modal(this.value)"  type="text" class="form-control" name="pallet" id="pallet" value="<?php echo $cantidad_medida3_inicial;?>" size="10" />
                        <?php } else { ?>
                            <input disabled aria-describedby="palletHelp" onchange="cargarMedida3_modal(this.value)"  type="text" class="form-control" name="pallet" id="pallet" value="0" size="10" />
                        <?php } ?>
                        <small id="palletHelp" style="display:none;" class="form-text text-muted">Sin <strong class="medida2_nombre">Medida3</strong>  asignadas,favor agregar en insumos.</small>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
        <?php if ($preferencias_medidas_edi == "S") { ?>
    
            <!-- MEDIDAS EDI  -->
            <?php if ($cant_caja_edi > 0) { ?>
                <div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;display:none;">
                    <label class="control-label col-md-3 col-sm-3 col-xs-12">
                        <a id="caja_edi_plus" href="javascript:void(0);" style="display:none;" class="btn btn-sm btn-default">
                            <span class="fa fa-plus"></span>
                        </a>
                        <div id="medida2">Cajas EDI:</div>
                    </label>
                    <div class="col-md-9 col-sm-9 col-xs-12">
                        <?php if ($cant_caja_edi > 0) { ?>
                            <input  class="form-control" onchange="cargarMedidaEDI_modal(this.value)"  aria-describedby="cajaEdiHelp" type="text" name="bulto_edi" id="bulto_edi" value="<?php echo $cantidad_medida_edi_inicial;?>" size="10" />	
                        <?php } else { ?>
                            <input disabled class="form-control" onchange="cargarMedidaEDI_modal(this.value)"  aria-describedby="cajaEdiHelp" type="text" name="bulto_edi" id="bulto_edi" value="0" size="10" />	
                        <?php } ?>
                        <small id="cajaEdiHelp"  style="display:none;" class="form-text text-muted">Sin <strong class="medida2_nombre">Cant. Cajas EDI</strong> asignadas,favor agregar en insumos.</small>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
    
        <!-- FIN DE MEDIDAS -->
    
                <div id="dropdow_almacenamiento"><?php require_once("./almacenamiento_dropdown.php"); ?></div>
    
    
        <div class="clearfix"></div>
    
        <div class="col-md-12 col-xs-12"  style="text-align:right;">
            <input type="hidden" name="ocinsumo" id="ocinsumo" value="" />
            <button  class="btn btn-success btn_agregar_insumo" id="btn_agregar" onclick="editar_articulo_post();"><span class="fa fa-plus"></span>&nbsp;Agregar</button>
        </div>
</div>
    <div class="clearfix"></div>

