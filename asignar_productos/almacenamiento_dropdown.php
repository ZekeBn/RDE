<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "2";
require_once("../includes/rsusuario.php");
if (intval($_GET['iddeposito']) > 0) {
    $iddeposito = intval($_GET['iddeposito']);
}
if (intval($_POST['iddeposito']) > 0) {
    $iddeposito = intval($_POST['iddeposito']);
}
if (intval($_POST['idalmacto']) > 0) {
    $idalmacto = intval($_POST['idalmacto']);
}
if (($_POST['dropdown_edit_almacenamiento']) == "true") {
    $dropdown_edit_almacenamiento = true;
}
if (($_POST['form_edit_div_id']) != "") {
    $form_edit_div_id = ($_POST['form_edit_div_id']);
}
// echo json_encode($dropdown_edit_almacenamiento);exit;


if ($iddeposito == 0) {
    $iddeposito = intval($_POST['iddeposito']);
}
?>
<script>
    
    window.onload = function(){
        select_tipo_almacenamiento($("#idalm")[0])
    };
    window.ready = function(){
        select_tipo_almacenamiento($("#idalm")[0])
    };


    function select_tipo_almacenamiento(selectElement){
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        var tipo_almacenamiento = selectedOption.getAttribute('data-hidden-value3');
        var idalmacto = selectedOption.getAttribute('data-hidden-value4');
        var filas = parseInt(selectedOption.getAttribute('data-hidden-value'));
        var columnas = parseInt(selectedOption.getAttribute('data-hidden-value2'));
        $("#idalmacto").val(idalmacto);
        if(tipo_almacenamiento == 1){
            $("#idpasillo_box").css('display', 'none');
            $("#idpasillo").val('');
            $("#box_fila").css('display', 'block');
            $("#box_columna").css('display', 'block');
        }else if(tipo_almacenamiento == 2){
            $("#idpasillo_box").css('display', 'block');
            $("#box_fila").css('display', 'none');
            $("#box_columna").css('display', 'none');
        }else{
            $("#idpasillo_box").css('display', 'none');
        }
        selectFila = $('#fila');
        selectColumna = $('#columna');
        selectFila.empty();
        selectColumna.empty();

        // Llenar el select con opciones del 1 al número introducido
        var option = document.createElement('option');
        option.text = "Seleccionar...";
        selectFila.append(option);
        option = document.createElement('option');
        option.text = "Seleccionar...";
        selectColumna.append(option);
        for (let i = 1; i <= filas; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.text = i;
            selectFila.append(option);
        }
        for (let i = 1; i <= columnas; i++) {
            const option = $('<option></option>');
            option.val(i);
            option.text(i);
            selectColumna.append(option);
        }
    }
    function select_tipo_almacenamiento_edit(selectElement){
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        var tipo_almacenamiento = selectedOption.getAttribute('data-hidden-value3');
        var idalmacto = selectedOption.getAttribute('data-hidden-value4');
        var filas = parseInt(selectedOption.getAttribute('data-hidden-value'));
        var columnas = parseInt(selectedOption.getAttribute('data-hidden-value2'));
        $("#idalmacto").val(idalmacto);
        var id_formulario = <?php echo isset($form_edit_div_id) ? $form_edit_div_id : "''" ?>;
        if(tipo_almacenamiento == 1){
            $(id_formulario + "#idpasillo_box").css('display', 'none');
            $(id_formulario + "#idpasillo").val('');
            $(id_formulario + "#box_fila").css('display', 'block');
            $(id_formulario + "#box_columna").css('display', 'block');
        }else if(tipo_almacenamiento == 2){
            $(id_formulario + "#idpasillo_box").css('display', 'block');
            $(id_formulario + "#box_fila").css('display', 'none');
            $(id_formulario + "#box_columna").css('display', 'none');
        }else{
            $(id_formulario + "#idpasillo_box").css('display', 'none');

        }
        selectFila = $(id_formulario + '#fila');
        selectColumna = $(id_formulario + '#columna');
        selectFila.empty();
        selectColumna.empty();

        // Llenar el select con opciones del 1 al número introducido
        var option = document.createElement('option');
        option.text = "Seleccionar...";
        selectFila.append(option);
        option = document.createElement('option');
        option.text = "Seleccionar...";
        selectColumna.append(option);
        for (let i = 1; i <= filas; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.text = i;
            selectFila.append(option);
        }
        for (let i = 1; i <= columnas; i++) {
            const option = $('<option></option>');
            option.val(i);
            option.text(i);
            selectColumna.append(option);
        }
    }
  
</script>

<div class="col-md-6 col-xs-12 form-group" style="padding:0px;">
    <label class="control-label col-md-4 col-sm-4 col-xs-12" style="overflow-wrap: break-word;">Almacenamiento *:</label> 
    <!-- overflow-wrap: break-word; -->
    <div class="col-md-8 col-sm-8 col-xs-12">
        <?php
            // consulta

            $consulta = "SELECT idalmacto, nombre FROM gest_deposito_almcto_grl where iddeposito = $iddeposito  and estado = 1
            ";

// valor seleccionado
if (isset($_POST['idalmacto'])) {
    $value_selected = htmlentities($_POST['idalmacto']);
} else {
    $value_selected = $rs->fields['idalmacto'];
}

$funcion_onchange_javascript = "";
if ($dropdown_edit_almacenamiento) {
    $funcion_onchange_javascript = ' onchange="seleccionar_almacenamiento_edit(this.value);" ';
} else {
    $funcion_onchange_javascript = ' onchange="seleccionar_almacenamiento(this.value);" ';
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idalmacto',
    'id_campo' => 'idalmacto',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idalmacto',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' '.$funcion_onchange_javascript.' required="required" "',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);


?>
    </div>
</div>


<div class="col-md-6 col-xs-12 form-group" style="padding:0px;">
    <label class="control-label col-md-4 col-sm-4 col-xs-12" style="overflow-wrap: break-word;">Tipo Almacenamiento *</label>
    <div class="col-md-8 col-sm-8 col-xs-12">
        <?php
    // consulta

    // SELECT tipo_almacenado,cara,filas,columnas,idalm,nombre FROM gest_deposito_almcto WHERE 1
    $whereadd = "";
if ($idalmacto > 0) {
    $whereadd = " and gest_deposito_almcto_grl.idalmacto = $idalmacto ";
}

$consulta = "SELECT CONCAT(gest_deposito_almcto.nombre,' ',COALESCE(gest_deposito_almcto.cara, ' ')) as nombre_alma,gest_deposito_almcto_grl.idalmacto,gest_deposito_almcto.tipo_almacenado, gest_deposito_almcto.cara, gest_deposito_almcto.filas, gest_deposito_almcto.columnas, gest_deposito_almcto.idalm, gest_deposito_almcto.nombre 
            FROM gest_deposito_almcto
            INNER JOIN gest_deposito_almcto_grl on gest_deposito_almcto_grl.idalmacto = gest_deposito_almcto.idalmacto
            WHERE gest_deposito_almcto_grl.iddeposito = $iddeposito
            and gest_deposito_almcto.estado = 1
            $whereadd
            ";
// echo $consulta;exit;
// valor seleccionado
if (isset($_POST['idalm'])) {
    $value_selected = htmlentities($_POST['idalm']);
} else {
    $value_selected = $rs->fields['idalm'];
}



$funcion_onchange_javascript = "";
if ($dropdown_edit_almacenamiento) {
    $funcion_onchange_javascript = ' onchange="select_tipo_almacenamiento_edit(this)" ';
} else {
    $funcion_onchange_javascript = ' onchange="select_tipo_almacenamiento(this)" ';
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idalm',
    'id_campo' => 'idalm',

    'nombre_campo_bd' => 'nombre_alma',
    'id_campo_bd' => 'idalm',

    'value_selected' => $value_selected,
    'data_hidden' => 'filas',
    'data_hidden2' => 'columnas',
    'data_hidden3' => 'tipo_almacenado',
    'data_hidden4' => 'idalmacto',
    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' '.$funcion_onchange_javascript.'  required="required" "'.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);


?>
    </div>
</div>

<!-- ///////////////////////////pasillo////////// -->

    <div id="idpasillo_box" class="col-md-6 col-xs-12 form-group" style="padding:0px;<?php if ($tipo_almacenado == 2) {
        echo 'display:block;';
    } else {
        echo 'display:none;';
    } ?>">
        <label class="control-label col-md-4 col-sm-4 col-xs-12" style="overflow-wrap: break-word;">Pasillo </label>
        <div class="col-md-8 col-sm-8 col-xs-12">
            <?php
                // consulta

                // SELECT tipo_almacenado,cara,filas,columnas,idalm,nombre FROM gest_deposito_almcto WHERE 1
                $whereadd = "";
if ($idalmacto > 0) {
    $whereadd = " and gest_deposito_almcto_grl.idalmacto = $idalmacto ";
}

$consulta = "SELECT gest_almcto_pasillo.idpasillo, gest_almcto_pasillo.nombre 
                FROM gest_almcto_pasillo
                INNER JOIN gest_deposito_almcto_grl on gest_deposito_almcto_grl.idalmacto = gest_almcto_pasillo.idalmacto
                WHERE gest_deposito_almcto_grl.iddeposito = $iddeposito
                and gest_almcto_pasillo.estado = 1
                $whereadd
                ";
// echo $consulta;exit;
// valor seleccionado
if (isset($_POST['idpasillo'])) {
    $value_selected = htmlentities($_POST['idpasillo']);
} else {
    $value_selected = $rs->fields['idpasillo'];
}



// parametros
$parametros_array = [
    'nombre_campo' => 'idpasillo',
    'id_campo' => 'idpasillo',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idpasillo',

    'value_selected' => $value_selected,
    'data_hidden' => 'filas',
    'data_hidden2' => 'columnas',
    'data_hidden3' => 'tipo_almacenado',
    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  required="required" "'.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);


?>
        </div>
    </div>



<!-- ///////////////////////////////////////////////////////////////////////////////////////////// -->
<!-- //////////////////////////////////FILAS Y COLUMNAS ////////////////////////////////////////// -->
<!-- ///////////////////////////////////////////////////////////////////////////////////////////// -->

    <div id="box_fila" class="col-md-6 col-xs-12 form-group " style="padding:0px;<?php if ($tipo_almacenado == 1) {
        echo 'display:block;';
    } else {
        echo 'display:none;';
    } ?>">
        <label class="control-label col-md-4 col-sm-4 col-xs-12" style="overflow-wrap: break-word;">Fila</label>
        <div class="col-md-8 col-sm-8 col-xs-12">
           <select class="form-control" name="fila" id="fila">
            <option value="">Seleccionar...</option>
            <?php  for ($i = 1; $i <= $filas; $i++) { ?>
                <?php if ($rs->fields['fila'] == $i) { ?>
                
                    <option value="<?php echo $i; ?>" selected><?php echo $i; ?></option>
                    <?php } else { ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php } ?>
            <?php } ?>
        </select>
    </div>
    </div>
    <div id="box_columna" class="col-md-6 col-xs-12 form-group " style="padding:0px;<?php if ($tipo_almacenado == 1) {
        echo 'display:block;';
    } else {
        echo 'display:none;';
    } ?>">
        <label class="control-label col-md-4 col-sm-4 col-xs-12" style="overflow-wrap: break-word;">Columna</label>
        <div class="col-md-8 col-sm-8 col-xs-12">
            <select class="form-control" name="columna" id="columna">
               <option value="">Seleccionar...</option>
               <?php  for ($i = 1; $i <= $columnas; $i++) { ?>
                    <?php if ($rs->fields['columna'] == $i) { ?>
                        <option selected value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php } else { ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php } ?>
                <?php } ?>
           </select>
        </div>
    </div>

<!-- ///////////////////////////////////////////////////////////////////////////////////////////// -->
<!-- //////////////////////////////////FILAS Y COLUMNAS ////////////////////////////////////////// -->
<!-- ///////////////////////////////////////////////////////////////////////////////////////////// -->