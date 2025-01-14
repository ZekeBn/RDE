<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$dirsup = "S";
$modulo = "1";
$submodulo = "613";
require_once("../includes/rsusuario.php");
require_once("../insumos/preferencias_insumos_listas.php");
require_once("../compras/preferencias_compras.php");


$iddevolucion_det = $_POST['iddevolucion_det'];
$iddevolucion = $_POST['iddevolucion'];
$idventa = $_POST['idventa'];
//<?php  require_once("./buscador_conteo_deposito_edit.php");
//id_insumo_select
// (SELECT ) as lote,
$consulta = "SELECT 
devolucion_det.*, 
insumos_lista.descripcion, 
(
  SELECT 
    SUM(ventas_detalles.cantidad) AS cantidad_venta 
  FROM 
    ventas_detalles 
    LEFT JOIN ventas_detalles_lote ON ventas_detalles_lote.ideventadet = ventas_detalles.idventadet 
  WHERE 
    ventas_detalles.idventa = devolucion.idventa 
    AND ventas_detalles.idprod = devolucion_det.idproducto 
    AND (
      devolucion_det.lote = ventas_detalles_lote.lote 
      OR devolucion_det.lote IS NULL
    ) 
    AND (
      DATE_FORMAT(
        devolucion_det.vencimiento, '%Y-%m-%d'
      ) = DATE_FORMAT(
        ventas_detalles_lote.vencimiento, 
        '%Y-%m-%d'
      ) 
      OR devolucion_det.vencimiento IS NULL
    ) 
  GROUP BY 
    ventas_detalles.idprod, 
    ventas_detalles_lote.lote, 
    ventas_detalles_lote.vencimiento
) AS cantidad_vendida,
(
    SELECT SUM(ddet.cantidad) AS cantidad_devuelta
    FROM devolucion_det as ddet
    LEFT JOIN devolucion as d  ON d.iddevolucion = ddet.iddevolucion
    WHERE d.idventa = devolucion.idventa
    and d.iddevolucion != devolucion.iddevolucion
    AND ddet.idproducto = devolucion_det.idproducto
    AND (ddet.lote = devolucion_det.lote
    OR ddet.lote IS NULL)
    AND (DATE_FORMAT(ddet.vencimiento, '%Y-%m-%d') = DATE_FORMAT(
        devolucion_det.vencimiento, '%Y-%m-%d'
        ) 
    OR ddet.vencimiento IS NULL)
    AND d.estado = 3 
    GROUP BY ddet.idproducto,
    ddet.lote,
    ddet.vencimiento
) as cantidad_devuelta
FROM 
devolucion_det 
INNER JOIN insumos_lista on insumos_lista.idproducto = devolucion_det.idproducto 
INNER JOIN devolucion ON devolucion.iddevolucion = devolucion_det.iddevolucion 
WHERE 
devolucion_det.iddevolucion_det = $iddevolucion_det";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// var_dump($rs->fields);exit;

// var_dump( date("d/m/Y",strtotime($rs->fields['vencimiento'])) );exit;
$idproducto = $rs->fields['idproducto'];
$descripcion = $rs->fields['descripcion'];
$idmedida_ref = $rs->fields['idmedida'];
$iddeposito = $rs->fields['iddeposito'];
$comentario = $rs->fields['comentario'];
$cantidad_vendida = $rs->fields['cantidad_vendida'];
$cantidad_devuelta = $rs->fields['cantidad_devuelta'];
$cantidad_restante = $cantidad_vendida - $cantidad_devuelta;

$buscar = "SELECT id_medida FROM medidas WHERE nombre like '%EDI' ";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$id_cajas_edi = intval($rsd->fields['id_medida']);

$consulta = "SELECT maneja_lote,idmedida2,idmedida3,idmedida,
cant_medida2,cant_medida3,cant_caja_edi,
(SELECT nombre FROM medidas WHERE medidas.id_medida = idmedida2 ) as medida2,
(SELECT nombre FROM medidas WHERE medidas.id_medida = idmedida3 ) as medida3
FROM insumos_lista 
WHERE idproducto=$idproducto";
$rs_lote = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// var_dump($rs_lote->fields);exit;
$idmedida = $rs_lote->fields['idmedida'];
$idmedida2 = $rs_lote->fields['idmedida2'];
$cant_medida2 = $rs_lote->fields['cant_medida2'];
$idmedida3 = $rs_lote->fields['idmedida3'];
$cant_medida3 = $rs_lote->fields['cant_medida3'];
$cant_caja_edi = $rs_lote->fields['cant_caja_edi'];
$maneja_lote = $rs_lote->fields['maneja_lote'];
$medida2 = $rs_lote->fields['medida2'];
$medida3 = $rs_lote->fields['medida3'];




$cantidad_medida_inicial = floatval($rs->fields['cantidad']);
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
    $("#editar_conteo  input").keydown(function(event) {
        // Verifica si la tecla presionada es "Enter"
        if (event.keyCode === 13) {
            // Cancela el comportamiento predeterminado del formulario
            event.preventDefault();
            // Envía el formulario
            // $(this).closest("form").submit();
            $("#editar_conteo #btn_agregar").click();
        }
    });

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
        var iddevolucion_det = <?php echo $iddevolucion_det; ?>;
        var iddeposito = $("#editar_conteo #iddeposito").val();
        var comentario = $("#editar_conteo #comentario").val();
        var cantidad_restante = <?php echo $cantidad_restante; ?>;

        var valido = "S";
        var errores="";
        <?php if ($maneja_lote == 1) { ?>
            if(lote == '' || lote == undefined ){
                valido="N";
                errores=errores+'- Debe indicar el Lote. \n<br>';	
            }
        <?php } ?>

        if (cantidad > cantidad_restante) {
            valido="N";
          errores=errores+"No se puede devolver mas cantidad de la que fue declarada en la venta. <br/>";
        }


        if(valido=="N"){
            alerta_error(errores); //
        }

        if(valido=="S"){
                
                var parametros_array = {
                    "idproducto"		    : <?php echo $idproducto; ?>,
                    "cantidad"		        : cantidad,
                    "lote"			        : lote,
                    "comentario"            : comentario,
                    "vencimiento"	        : vencimiento,
                    "idmedida"		        : idmedida,
                    "iddeposito"		    : iddeposito,
                    "iddevolucion_det"		: iddevolucion_det,
                    "iddevolucion"      : <?php echo $iddevolucion ? $iddevolucion : 0 ; ?>,
                    "idventa"			  		  : <?php echo $idventa ? $idventa : 0; ?>,
                    "editar"		        : 1
                };

                console.log(parametros_array);
                 
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
                                /////////////ESTO VER PARA MANDAR EL UPDATE///////////////////////////////////////
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
                                        cerrar_pop(); 
                                        $("#conteo_productos").html(response);
                                    },
                                    error: function(jqXHR, textStatus, errorThrown) {
                                    errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
                                    }
                                }).fail( function( jqXHR, textStatus, errorThrown ) {
                                    errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
                                });
                                ///////////////////////////////////////////////////////////////////

                //             }
                //         }
                //     });

                }
    }
    function cerrar_pop(){
				$("#modal_ventana").modal("hide");
                
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
if (isset($rs->fields['iddeposito'])) {
    $value_selected = htmlentities($rs->fields['iddeposito']);
} else {
    $value_selected = null;
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
                                inner join insumos_lista on insumos_lista.idinsumo = gest_depositos_stock.idproducto
                            WHERE
                                insumos_lista.idproducto = $idproducto
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
                    <input readonly type="date" name="vencimiento" id="vencimiento" value="<?php echo date("Y-m-d", strtotime($rs->fields['vencimiento'])); ?>" placeholder="" class="form-control" />
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


        <div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
            <label class="control-label col-md-3 col-sm-3 col-xs-12">
                <a id="comentario_box" href="javascript:void(0);" style="display:none;" class="btn btn-sm btn-default">
                    <span class="fa fa-plus"></span>
                </a>
                <div>Comentario:</div>
            </label>
            <div class="col-md-9 col-sm-9 col-xs-12">
                <input class="form-control"  aria-describedby="cajaEdiHelp" type="text" name="comentario" id="comentario" value="<?php echo $comentario; ?>" placeholder="Comentario"  size="10" />	
            </div>
        </div>
    
        <!-- FIN DE MEDIDAS -->
    
    
    
        <div class="clearfix"></div>
    
        <div class="col-md-12 col-xs-12"  style="text-align:right;">
            <input type="hidden" name="ocinsumo" id="ocinsumo" value="" />
            <button  class="btn btn-success btn_agregar_insumo" id="btn_agregar" onclick="editar_articulo_post();"><span class="fa fa-plus"></span>&nbsp;Agregar</button>
        </div>
</div>
<div class="clearfix"></div>

