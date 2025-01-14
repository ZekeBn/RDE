<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "2";
require_once("../includes/rsusuario.php");
require_once("../insumos/preferencias_insumos_listas.php");
function isNullAddChar($palabra)
{
    if ($palabra == "NULL") {
        return "'NULL'";
    } else {
        return $palabra;
    }
}

$idinsumo_select = intval($_GET['idinsumo']);
$buscar = "SELECT id_medida FROM medidas WHERE nombre like '%EDI' ";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$id_cajas_edi = intval($rsd->fields['id_medida']);

// $idtransaccion
// $id_tipo_origen_importacion

//Traemos los insumos de insumos_lista
$buscar = "Select idinsumo,descripcion,maneja_lote,(select nombre from medidas where id_medida=insumos_lista.idmedida and estado=1) as medida, (select nombre from categorias where id_categoria=insumos_lista.idcategoria and estado=1) as categoria,
cant_caja_edi,cant_medida2,cant_medida3,idmedida2,idmedida3,idmedida,
(select nombre from medidas where medidas.id_medida = insumos_lista.idmedida2) as medida2,
(select nombre from medidas where medidas.id_medida = insumos_lista.idmedida3) as medida3,
(select descripcion from sub_categorias where sub_categorias.idsubcate = insumos_lista.idsubcate) as subcate,
( SELECT proveedores_fob.codigo_articulo from proveedores_fob WHERE proveedores_fob.idfob = insumos_lista.cod_fob ) as codigo_origen,
(select descripcion from sub_categorias_secundaria where sub_categorias_secundaria.idsubcate_sec = insumos_lista.idsubcate_sec) as subcate_sec
 from insumos_lista where
 UPPER(insumos_lista.descripcion)  not like \"%DESCUENTO%\" 
 and  UPPER(insumos_lista.descripcion)  not like \"%AJUSTE%\"
 and estado='A' 
 and hab_compra=1 order by descripcion asc
 
 ";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $descripcion = isNullAddChar(trim(antisqlinyeccion($rsd->fields['descripcion'], "text")));
    $medida = isNullAddChar(trim(antisqlinyeccion($rsd->fields['medida'], "text")));
    $idinsumo = intval($rsd->fields['idinsumo']);
    $idmedida = intval($rsd->fields['idmedida']);
    $idmedida2 = intval($rsd->fields['idmedida2']);
    $idmedida3 = intval($rsd->fields['idmedida3']);
    $maneja_lote = intval($rsd->fields['maneja_lote']);

    $categoria = isNullAddChar(antisqlinyeccion($rsd->fields['categoria'], "text"));
    $subcate = isNullAddChar(antisqlinyeccion($rsd->fields['subcate'], "text"));
    $subcate_sec = isNullAddChar(antisqlinyeccion($rsd->fields['subcate_sec'], "text"));
    $medida2 = isNullAddChar(antisqlinyeccion($rsd->fields['medida2'], "text"));
    $medida3 = isNullAddChar(antisqlinyeccion($rsd->fields['medida3'], "text"));
    $codigo_origen = isNullAddChar(antisqlinyeccion($rsd->fields['codigo_origen'], "text"));

    $cant_caja_edi = (floatval($rsd->fields['cant_caja_edi']));
    $cant_medida2 = (floatval($rsd->fields['cant_medida2']));
    $cant_medida3 = (floatval($rsd->fields['cant_medida3']));

    $resultados .= "
	<a href='javascript:void(0);' data-hidden-value=$categoria data-hidden-codorigen=$codigo_origen onclick=\"este_producto({idinsumo: $idinsumo,maneja_lote: $maneja_lote, descripcion: $descripcion, medida:$medida, medida2: $medida2, medida3:$medida3, cant_medida2: $cant_medida2, cant_medida3: $cant_medida3, id_cajas_edi: $id_cajas_edi, idmedida2: $idmedida2, idmedida3: $idmedida3, idmedida: $idmedida, cant_caja_edi: $cant_caja_edi, iddeposito: $iddeposito });\">[$idinsumo]-$descripcion ($medida)</a>
	";
    $resultados2 .= "
	<option href='javascript:void(0);' data-hidden-value=$categoria data-hidden-codorigen=$codigo_origen onclick=\"este_producto({idinsumo: $idinsumo,maneja_lote: $maneja_lote, descripcion: $descripcion, medida:$medida, medida2: $medida2, medida3:$medida3, cant_medida2: $cant_medida2, cant_medida3: $cant_medida3, id_cajas_edi: $id_cajas_edi, idmedida2: $idmedida2, idmedida3: $idmedida3, idmedida: $idmedida, cant_caja_edi: $cant_caja_edi, iddeposito: $iddeposito });\">[$idinsumo]-$descripcion ($medida)</option>
	";
    $rsd->MoveNext();
}
?>
<style>
	.btn_insumo_select{
		color: #6789A9 !important;
		background: white;
		border: #6789A9 solid 1px;
	}
	.btn_insumo_select:hover{
		/* color: #fff !important;
		background: #6D8EAE; */
		color: #6789A9;
		background: #E9EDF1;
	}
	.btn_agregar_insumo{
		background: #71B48D;
    	border: 1px solid #0EAD69;
	}
	.btn_agregar_insumo:hover{
			background: #0EAD69;
			border: 1px solid #0EAD69;
		}
</style>
<div class="col-md-12 col-xs-12" style="margin-bottom:.8rem !important;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12" >Cod. Lote</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="cod_lote" id="cod_lote" value="" placeholder="" class="form-control" onkeypress="buscar_lote(event);" />                    
    </div>
</div>
<div class="col-md-5 hide">
	<div class="col-md-8 col-xs-12" style="display:none;">
		<div class="col-md-9 col-sm-9 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
			<div class="dropdown" id="insumos_dropdown">
			  <button onclick="myFunction()" class="btn btn-rimary btn_insumo_select" id="abrecierra">Buscar Insumo por nombre</button>
			  <input type="text" placeholder="Nombre Insumo" id="myInput" onkeyup="filterFunction()" style="position: absolute;top: 37px;left: 0;z-index: 99999;display:none;" >
			  <div id="myDropdown" class="dropdown-content" style="position: absolute;top: 90px;left: 0;z-index: 99999;width: 261px;max-width: 300px;max-height: 200px;overflow: auto;">
				<?php echo $resultados ?>
			  </div>
			</div>
		
		</div>
	</div>
	<div class="col-md-12 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
		<label class="control-label col-md-3 col-sm-3 col-xs-12" >Insumo</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="insumo_text" id="insumo_text" value="" onkeyup=" return insumo_onchange(event)"  class="form-control"  />                    
		</div>
	</div>
	<div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
		<label class="control-label col-md-3 col-sm-3 col-xs-12" >Codigo Insumo</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="insumo" id="insumo" value=""  onchange="idinsumo_onchange()"  class="form-control"  />                    
		</div>
	</div>
	<div class="col-md-6 col-xs-12" style="margin-bottom:.8rem !important;padding:0;">
		<label class="control-label col-md-3 col-sm-3 col-xs-12" >Cod. Barra</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="codbar" id="codbar" value="" placeholder="" class="form-control" onkeypress="buscar_codbar(event);" />                    
		</div>
	</div>

	<select name="lprod" size="4" id="lprod" style="width:100%;" onkeyup="return select_enter(event)" >
	<?php echo $resultados2 ?>
</select>

</div>
<div class="col-md-12" id="form_buscador">
    <?php require("./buscador_conteo_deposito_form.php");?>
</div>





<script>
	function cerrar_errorestxt(event){
		$("#erroresjs").hide();
	}
    //Funciones nuevas
    function determinarUnidadCompra(){
        var tipos_carga ={"unidad": 1,"bulto":2,"pallet":3, "bulto_edi":4}
        var tipo = tipos_carga["unidad"];
        var medida = document.getElementById('cantidad');
        var id_tipo = medida.getAttribute('data-hidden-id');;
        var medida2 =document.getElementById('bulto');
        var medida3 = document.getElementById('pallet');
        var medida_edi = document.getElementById('bulto_edi');
        var cantidad_medida = 0;
        if(medida){

            cantidad_medida = parseFloat(medida?.value);
        }
        var cantidad_medida2 = 0;
        if(medida2){

            cantidad_medida2 = parseFloat(medida2?.value);
        }

        var cantidad_medida3 = 0;
        if(medida3){

            cantidad_medida3 = parseFloat(medida3?.value);
        }
        var cantidad_edi = 0;
        if(medida_edi){

            cantidad_edi = parseFloat(medida_edi?.value);
        } 
        var cantidad_cargada=0;
        var cantidad_ref=0;
        // console.log(" asd" ,cantidad_medida3 , cantidad_medida2);
        if(cantidad_medida3 != 0){
            tipo = tipos_carga["pallet"];
            id_tipo = medida3.getAttribute('data-hidden-id');
            cantidad_cargada = cantidad_medida3;
        }
        if(cantidad_medida2 != 0 && cantidad_medida3 == 0){
            tipo = tipos_carga["bulto"];
            id_tipo = medida2.getAttribute('data-hidden-id');
            cantidad_cargada = cantidad_medida2;
        }
        if(cantidad_edi != 0){
            tipo = tipos_carga["bulto_edi"];
            id_tipo = medida_edi?.getAttribute('data-hidden-id');
            cantidad_cargada = cantidad_edi;
        }
        if(cantidad_medida != 0 && cantidad_medida2 == 0 && cantidad_medida3 == 0 && cantidad_edi == 0){
                tipo = tipos_carga["unidad"];
                id_tipo = medida?.getAttribute('data-hidden-id');
                cantidad_cargada = cantidad_medida;
            }
        // console.log(tipo);
        return {"tipo": tipo, "id_tipo": id_tipo, "cantidad_cargada": cantidad_cargada};
    }

    function agregar_insumo_carrito(){
        var resp = determinarUnidadCompra();
        agregar_insumo(resp['id_tipo'],resp['tipo']);
        $("#cantidad").focus();
    }
    document.addEventListener("DOMContentLoaded", function() {
        $("#insumo_text").focus();
        $('#boxErroresCompras').on('closed.bs.alert', function () {
            $('#boxErroresCompras').removeClass('show');
            $('#boxErroresCompras').addClass('hide');
        });
        });
        function cerrar_errores_compras(){
            $('#boxErroresCompras').removeClass('show');
            $('#boxErroresCompras').addClass('hide');
	}
    function idinsumo_onchange() {

        // Acciones a realizar cuando el valor del input cambie
        var parametros = {
                    "idinsumo"   :  $('#insumo').val(),
                    "idempresa"  : <?php echo $idempresa;?>
                };
        $.ajax({
                data:  parametros,
                url:   'buscar_insumo.php',
                type:  'post',
                beforeSend: function () {
                    //   $("#carritocompras").html('Cargando...');  
                },
                success:  function (response) {
                    if (JSON.parse(response)["success"] == true) {
                        var medida=JSON.parse(response)["medida"];
                        var medida2=JSON.parse(response)["medida2"];
                        var medida3=JSON.parse(response)["medida3"];
                        var descripcion=JSON.parse(response)["descripcion"];
                        var idinsumo=JSON.parse(response)["idinsumo"];
                        var idmedida=JSON.parse(response)["idmedida"];
                        var idmedida2=JSON.parse(response)["idmedida2"];
                        var idmedida3=JSON.parse(response)["idmedida3"];
                        var id_cajas_edi=JSON.parse(response)["id_cajas_edi"];
                        var cant_medida2=JSON.parse(response)["cant_medida2"];
                        var cant_medida3=JSON.parse(response)["cant_medida3"];
                        var cant_caja_edi=JSON.parse(response)["cant_caja_edi"];
                        var maneja_lote=JSON.parse(response)["usa_lote"];
                        var iddeposito=<?php echo intval($iddeposito) ?>;
                        
                        
                        este_producto({idinsumo: idinsumo, descripcion: descripcion, medida: medida, medida2: medida2, medida3: medida3, cant_medida2: cant_medida2, cant_medida3: cant_medida3, id_cajas_edi: id_cajas_edi, idmedida2: idmedida2, idmedida3: idmedida3, idmedida: idmedida, cant_caja_edi: cant_caja_edi,maneja_lote: maneja_lote, iddeposito: iddeposito})
                        $("#abrecierra").click();
                    } else {
                        $('#boxErroresCompras').removeClass('hide');
                        $("#erroresCompras").html(JSON.parse(response)["errores"]);
                        $('#boxErroresCompras').addClass('show');
                    }
                    
                }
        });

        
    
    }
    function select_enter(event){
        var target = $(event.target);
        var select = document.getElementById("lprod");

        var optionIndexes = [];

        for (var i = 0; i < select.options.length; i++) {
        var option = select.options[i];
        if (getComputedStyle(option).display !== "none") {
            optionIndexes.push(i);
        }
        }

        if(event.keyCode == 38 && select.selectedIndex === optionIndexes[0]){
            $("#insumo_text").focus();
            return false;  
        }
        var select = document.getElementById("lprod");

        select.addEventListener("keydown", function(event) {
        if (event.key === "Enter") {
            var selectedOption = select.options[select.selectedIndex];
            selectedOption.click();
            // Realiza aquí la acción que deseas al presionar Enter
        }
        });
    }

    function insumo_onchange(e){
        e.preventDefault();
        if (e.keyCode === 40) { // Verificar si se presionó la tecla Tab (código 9)
            // Ejecutar tu función aquí
            $("#lprod").focus();
            return false;  
        }
        

        var input, filter, ul, li, a, i;
        input = document.getElementById("insumo_text");
        filter = input.value.toUpperCase();
        div = document.getElementById("lprod");
        a = div.getElementsByTagName("option");
        for (i = 0; i < a.length; i++) {
            txtValue = a[i].textContent || a[i].innerText;
            categoriaValue = a[i].getAttribute('data-hidden-value');
            codigoOrigen = a[i].getAttribute('data-hidden-codorigen');
            if (txtValue.toUpperCase().indexOf(filter) > -1 || categoriaValue.toUpperCase().indexOf(filter) > -1 || codigoOrigen.toUpperCase().indexOf(filter) > -1) {
            a[i].style.display = "";
            } else {
            a[i].style.display = "none";
            }
        }

    }

    function buscar_codbar(e){
        var codbar = $("#codbar").val();
        tecla = (document.all) ? e.keyCode : e.which;
        // tecla enter
        if (tecla==13){
            // selecciona
            este(0,codbar);
        }
    }
    function buscar_lote(e){

        ////////////////////
        //var miString = "I:3;L:2;V:2023-05-05";
        var codLote = $("#cod_lote").val();
        var miArray = codLote.split(';');
        var idinsumo = miArray[0].split(':')[1];
        var lote = miArray[1].split(':')[1];
        var vencimiento = miArray[2]?.split(':')[1];
        ///////////
        ////////
        tecla = (document.all) ? e.keyCode : e.which;
        // tecla enter
        if (tecla==13){
            // selecciona
            var insumo_conteo = <?php echo $idinsumo_select;?>;
            if(insumo_conteo == idinsumo){
                $('#lote_valor').val(lote);
                cambiar_vencimiento($('#lote_valor')[0]);
                var codLote = $("#cod_lote").val("");
                $("#cantidad").focus();
            }
        }
    }
    function este(valor,cbar=''){
            
            var parametros = {
                    "insu"   : valor,
                    "cbar"   : cbar,
                    "p"      : 2
            };
            $.ajax({
                    data:  parametros,
                    url:   'codbar_insumo.php',
                    type:  'post',
                    beforeSend: function () {
                        $("#selecompra").html('Cargando...');  
                    },
                    success:  function (response) {
                        if (JSON.parse(response)["success"] == true) {
                        var medida=JSON.parse(response)["medida"];
                        var descripcion=JSON.parse(response)["descripcion"];
                        var idinsumo=JSON.parse(response)["idinsumo"];
                        
                        
                        $("#ocinsumo").val(idinsumo);
                        $("#medidanombre").html(medida);
                        
                        $("#myInput").val(descripcion);
                        $("#seleccionado").val(idinsumo+'-'+descripcion);
                        $("#cantidad").focus();
                        //   $("#carritocompras").html(response);
                    } else {
                        $('#boxErroresCompras').removeClass('hide');
                        $("#erroresCompras").html(JSON.parse(response)["errores"]);
                        $('#boxErroresCompras').addClass('show');
                    }
                    }
            });
            setTimeout(function(){ controlar(); }, 200);
    }
    function myFunction() {
        
        document.getElementById("myInput").classList.toggle("show");
        document.getElementById("myDropdown").classList.toggle("show");
        div = document.getElementById("myDropdown");
        // a = div.getElementsByTagName("a");
        // for (i = 0; i < a.length; i++) {
        // 	if ( i < 8) {
        // 		a[i].style.display = "block";
        // 	} else{
        // 		a[i].style.display = "none";
        // 	}
        // }
        $("#myInput").focus();

        $(document).mousedown(function(event) {
            var target = $(event.target);
            var myInput = $('#myInput');
            var myDropdown = $('#myDropdown');
            var div = $("#insumos_dropdown");
            var button = $("#abrecierra");
            // Verificar si el clic ocurrió fuera del elemento #my_input
            if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown").length && myInput.hasClass('show')) {
            // Remover la clase "show" del elemento #my_input
            myInput.removeClass('show');
            myDropdown.removeClass('show');
            }
            $("#myInput").keydown(function(event) {
                if (event.which === 9) {
                    $("#myDropdown").children()[0];
                }
            });
        });
    }


    function filterFunction() {
    var input, filter, ul, li, a, i;
    input = document.getElementById("myInput");
    filter = input.value.toUpperCase();
    div = document.getElementById("myDropdown");
    a = div.getElementsByTagName("a");
    for (i = 0; i < a.length; i++) {
        txtValue = a[i].textContent || a[i].innerText;
        categoriaValue = a[i].getAttribute('data-hidden-value');
        codigoOrigen = a[i].getAttribute('data-hidden-codorigen');
        if (txtValue.toUpperCase().indexOf(filter) > -1 || categoriaValue.toUpperCase().indexOf(filter) > -1 || codigoOrigen.toUpperCase().indexOf(filter) > -1) {
        a[i].style.display = "";
        } else {
        a[i].style.display = "none";
        }
    }
    }
    function actualizar_insumo(idinsumo,maneja_lote){
        var parametros = {
                    "idinsumo"   : idinsumo,
                    "maneja_lote": maneja_lote
            };
            $.ajax({
                    data:  parametros,
                    url:   'buscador_conteo_deposito_form.php',
                    type:  'post',
                    beforeSend: function () {
                        $("#form_buscador").html('Cargando...');  
                    },
                    success:  function (response) {
                        $("#form_buscador").html(response);
                    }
            });
    }
    function este_producto(parametros){
        // actualizar_insumo(parametros.idinsumo, parametros.maneja_lote);
        var parametros2 = {
                    "idinsumo"   : parametros.idinsumo,
                    "maneja_lote": parametros.maneja_lote,
                    "iddeposito" : parametros.iddeposito
            };
            $.ajax({
                    data:  parametros2,
                    url:   'buscador_conteo_deposito_form.php',
                    type:  'post',
                    beforeSend: function () {
                        $("#form_buscador").html('Cargando...');  
                    },
                    success:  function (response) {
                        
                        $("#form_buscador").html(response);
                        
                        $("#errorestxt").html("");
                        $("#erroresjs").hide();
                        $("#seleccionado").attr('data-hidden-ocinsumo', parametros.idinsumo);
                        $("#medidanombre").html(parametros.medida);
                        $("#abrecierra").click();
                        $("#myInput").val(parametros.descripcion);
                        $("#seleccionado").val(parametros.idinsumo+'-'+parametros.descripcion);
                        $("#buscador_conteo #cod_lote").focus();
                        $('#cantidad').attr('data-hidden-id', parametros.idmedida);
                        $('input[name="radio_medida"]').filter(':checked').prop('checked', false);
                        $('#seleccionado').attr('data-hidden-lote', parametros.maneja_lote);
                        $('#lote').val('');
                        $('#vencimiento').val(''); 
                        $('#cantidad').val(''); 
                        $('#bulto').val(''); 
                        $('#bulto_edi').val(''); 
                        $('#pallet').val(''); 
                        $('#precio_compra').val(''); 
                        if(parametros.id_cajas_edi == 0  ){
                            $('#boxErroresCompras').removeClass('hide');
                            $("#erroresCompras").html("- Medida Cajas EDI no fue creado.<br>");
                            $('#boxErroresCompras').addClass('show'); 
                        }
                        if( (parametros.medida2) == "NULL" || (parametros.medida2) == "" ){
                            // $('#caja_plus').css('display', 'inline-block');
                            $('#bulto').prop('disabled', true);
                            $('#cajaHelp').css('display', 'inline');
                            $('#bulto').val(0);
                            $('#box_radio_bulto').css('display', 'none');
                        }else{
                            $("#medida2").html(parametros.medida2);
                            $('#bulto').attr('data-hidden-id', parametros.idmedida2);
                            // $(".medida_nombre").html(medida);
                            // $(".medida2_nombre").html(medida2);
                            // $(".medida3_nombre").html(medida3);
                            $('#caja_plus').css('display', 'none');
                            $('#bulto').prop('disabled', false);
                            $('#bulto').attr('data-hidden-cant',parametros.cant_medida2 );
                            $('#cajaHelp').css('display', 'none');
                            $('#box_radio_bulto').css('display', 'block');
                        }
                        if( parametros.cant_caja_edi == 0 ){
                            // $('#caja_plus').css('display', 'inline-block');
                            $('#bulto_edi').prop('disabled', true);
                            $('#cajaEdiHelp').css('display', 'inline');
                            $('#bulto_edi').val(0);
                            $('#box_radio_edi').css('display', 'none');
                        }else{
                            $('#bulto_edi').attr('data-hidden-id', parametros.idcajas_edi);
                            // $(".medida_nombre").html(medida);
                            // $(".medida2_nombre").html(medida2);
                            // $(".medida3_nombre").html(medida3);
                            // $('#caja_plus').css('display', 'none');
                            $('#bulto_edi').prop('disabled', false);
                            $('#bulto_edi').attr('data-hidden-cant',parametros.cant_caja_edi );
                            $('#cajaEdiHelp').css('display', 'none');
                            $('#box_radio_edi').css('display', 'block');
                        }
                        if( parametros.medida3 == "" || parametros.medida3 == "NULL"  ){
                            // $('#pallet_plus').attr('data-hidden-id', id);
                            // $('#pallet_plus').css('display', 'inline-block');
                            $('#pallet').prop('disabled', true);
                            $('#palletHelp').css('display', 'inline');
                            $('#pallet').val(0);
                            $('#box_radio_pallet').css('display', 'none');
                        }else{
                            $('#pallet').attr('data-hidden-id', parametros.idmedida3);
                            $("#medida3").html(parametros.medida3);
                            $('#pallet_plus').css('display', 'none');
                            $('#pallet').prop('disabled', false);
                            $('#pallet').attr('data-hidden-cant',parametros.cant_medida3 );
                            $('#palletHelp').css('display', 'none');
                            $('#box_radio_pallet').css('display', 'block');
                        }
                        
                    }
            });
        
        
        
    }

    function cargarMedida(){
        $('#bulto').val(0);
        $('#bulto_edi').val(0);
        $('#pallet').val(0);
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
    function cargarMedidaEDI(value){
        var medida2_input = document.getElementById('bulto_edi');
        var cant_medida = medida2_input.getAttribute('data-hidden-cant');
        $('#bulto').val(0);
        $('#pallet').val(0);
        $("#cantidad").val(value*cant_medida)
    }
    function cargarMedida2(value,limpiar){
        var medida2_input = document.getElementById('bulto');
        var cant_medida = medida2_input.getAttribute('data-hidden-cant');
        $("#cantidad").val(value*cant_medida)
        if(limpiar == true){
            $('#pallet').val(0);
            $('#bulto_edi').val(0);
        }


        var medida3_input = document.getElementById('pallet');
		var cant_medida3 = medida3_input.getAttribute('data-hidden-cant');

	
		if(  cant_medida3 != undefined && value%cant_medida3 == 0 ){
			medida3_input.value = (value/cant_medida3);
		}
    }
    function cargarMedida3(value){
        var medida2_input = document.getElementById('pallet');
        var cant_medida = medida2_input.getAttribute('data-hidden-cant');
        $("#bulto").val(value*cant_medida);
        $("#bulto_edi").val(0);
        cargarMedida2(value*cant_medida,false);
    }
    //Final funciones nuevas
    window.onload = function(){
        $('#insumo').val(<?php echo intval($idinsumo_select);?>)
        idinsumo_onchange();
        
        
        $("#form_buscador input").keydown(function(event) {
		// Verifica si la tecla presionada es "Enter"
			if (event.keyCode === 13) {
				
				// Cancela el comportamiento predeterminado del formulario
				event.preventDefault();
				
				$("#form_buscador #btn_agregar").click();
			}
		});
        
       
    };
    window.ready = function(){
        $('#insumo').val(<?php echo intval($idinsumo_select);?>)
        idinsumo_onchange();
        
        
        
        $("#form_buscador input").keydown(function(event) {
		// Verifica si la tecla presionada es "Enter"
			if (event.keyCode === 13) {
				
				// Cancela el comportamiento predeterminado del formulario
				event.preventDefault();
				
				$("#form_buscador #btn_agregar").click();
			}
		});
    };
</script>
<div class="clearfix"></div>


