<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo

$modulo = "42";
$submodulo = "599";
$dirsup = "S";
require_once("../includes/rsusuario.php");



$iddespacho = intval($_GET['id']);
if ($iddespacho == 0) {
    header("location: despacho.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from despacho 
where 
iddespacho = $iddespacho
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddespacho = intval($rs->fields['iddespacho']);
if ($iddespacho == 0) {
    header("location: despacho.php");
    exit;
}






$buscar = "SELECT idtipo_servicio FROM `tipo_servicio` WHERE UPPER(tipo) = UPPER('DESPACHANTE') and estado=1";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$iddespachante_valor = intval($rsd->fields['idtipo_servicio']);
$buscar = "SELECT * FROM `tipo_origen` WHERE UPPER(tipo) = UPPER('importacion')";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idtipo_origen_importacion = intval($rsd->fields['idtipo_origen']);





$idcompra_get = intval($rs->fields['idcompra']);
$idcompra_text = null;
$class_idcompra = "";
if ($idcompra_get > 0) {
    $idcompra_valor_select = $idcompra_post > 0 ? $idcompra_post : $idcompra_get;

    $buscar_compra = "Select compras.idtran,compras.ocnum,
  proveedores.nombre as proveedor, compras.ocnum, 
  compras.idcompra
  from gest_depositos_compras
  inner join proveedores on proveedores.idproveedor=gest_depositos_compras.idproveedor
  inner join usuarios on usuarios.idusu=gest_depositos_compras.registrado_por 
  inner join compras on compras.idcompra = gest_depositos_compras.idcompra and compras.idtipo_origen = $idtipo_origen_importacion
  where 
  revisado_por=0 
  and compras.estado <> 6
  and compras.idcompra = $idcompra_valor_select
  order by fecha_compra desc 
  ";

    $rsd_get = $conexion->Execute($buscar_compra) or die(errorpg($conexion, $buscar_compra));
    $nombre_get = trim(antixss($rsd_get->fields['proveedor']));
    $idcompra_get = trim(antixss($rsd_get->fields['idcompra']));
    $ocnum_get = trim(antixss($rsd_get->fields['ocnum']));

    $idcompra_text = "[$idcompra_get]-$nombre_get-$ocnum_get";
}



$buscar = "Select compras.idtran,compras.ocnum,
proveedores.nombre as proveedor, compras.ocnum, 
compras.idcompra
from gest_depositos_compras
inner join proveedores on proveedores.idproveedor=gest_depositos_compras.idproveedor
inner join usuarios on usuarios.idusu=gest_depositos_compras.registrado_por 
inner join compras on compras.idcompra = gest_depositos_compras.idcompra and compras.idtipo_origen = $idtipo_origen_importacion
where 
revisado_por=0 
and compras.estado <> 6
order by fecha_compra desc 
";

$resultados_compras = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idtran = trim(antixss($rsd->fields['idtran']));
    $nombre = trim(antixss($rsd->fields['proveedor']));
    $idcompra = trim(antixss($rsd->fields['idcompra']));
    $ocnum = trim(antixss($rsd->fields['ocnum']));
    $resultados_compras .= "
	<a class='a_link_proveedores'  href='javascript:void(0);'  onclick=\"cambia_idcompra($idcompra, '$nombre', $ocnum);\">[$idcompra]-$nombre-$ocnum</a>
	";

    $rsd->MoveNext();
}


//buscando moneda nacional
$consulta = "SELECT idtipo FROM `tipo_moneda` WHERE nacional='S' ";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_nacional = $rs_guarani->fields["idtipo"];

if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots


    // recibe parametros
    $idaduana = antisqlinyeccion($_POST['idaduana'], "int");
    $iddespachante = antisqlinyeccion($_POST['iddespachante'], "int");
    $idcompra = antisqlinyeccion($_POST['idcompra'], "int");
    $fecha_despacho = antisqlinyeccion($_POST['fecha_despacho'], "date");
    $tipo_moneda = antisqlinyeccion($_POST['tipo_moneda'], "int");
    $cotizacion = antisqlinyeccion($_POST['cotizacion'], "float");
    $comentario = antisqlinyeccion($_POST['comentario'], "text");
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $estado = antisqlinyeccion("1", "float");




    if (intval($_POST['tipo_moneda']) == 0) {
        $valido = "N";
        $errores .= " - El campo Moneda no puede ser nulo.<br />";
    }
    if (intval($_POST['iddespachante']) == 0) {
        $valido = "N";
        $errores .= " - El campo Despachante no puede ser nulo.<br />";
    }
    if (intval($_POST['idaduana']) == 0) {
        $valido = "N";
        $errores .= " - El campo Aduana no puede ser nulo.<br />";
    }
    if (intval($_POST['idcompra']) == 0) {
        $valido = "N";
        $errores .= " - El campo Compra no puede ser nulo.<br />";
    }
    $idmoneda = $tipo_moneda;
    $consulta = "SELECT cotiza from tipo_moneda where idtipo = $idmoneda";
    $rscotiza = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cotiza_moneda = intval($rscotiza -> fields['cotiza']);

    if (floatval($_POST['cotizacion']) <= 0 && $cotiza_moneda == 1) {
        $valido = "N";
        $errores .= " - El campo Cotizacion no puede ser cero o negativo.<br />";
    }
    if ($cotiza_moneda != 1) {
        $cotizacion = 0;
    }
    if (floatval($_POST['fecha_despacho']) == "") {
        $valido = "N";
        $errores .= " - El campo Fecha Compra no puede ser nulo.<br />";
    }
    /*
    registrado_por
    */
    /*
    registrado_el
    */
    /*
    estado
        if(floatval($_POST['estado']) <= 0){
            $valido="N";
            $errores.=" - El campo estado no puede ser cero o negativo.<br />";
        }
    */
    /*
    anulado_el
        if(trim($_POST['anulado_el']) == ''){
            $valido="N";
            $errores.=" - El campo anulado_el no puede estar vacio.<br />";
        }
    */

    /*
    comentario
        if(trim($_POST['comentario']) == ''){
            $valido="N";
            $errores.=" - El campo comentario no puede estar vacio.<br />";
        }
    */

    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		update despacho
		set
    iddespacho=$iddespacho,
    tipo_moneda=$tipo_moneda,
    iddespachante=$iddespachante,
    idcompra=$idcompra,
    fecha_despacho=$fecha_despacho,
    cotizacion=$cotizacion,
    comentario=$comentario
		where
			iddespacho = $iddespacho
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        header("location: despacho.php");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());



?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <script>

    function verificar_cotizacion_moneda(){
      var parametros = {
      "idmoneda"   : $("#tipo_moneda").val()
      };
      $.ajax({
        data:  parametros,
        url:   '../cotizaciones/cotizaciones_verificar_cotiza.php',
        type:  'post',
        beforeSend: function () {
          
        },
        success:  function (response) {
          console.log(response);
          if(JSON.parse(response)['success']==false){
            
          }else{
            
            var cotiza = JSON.parse(response)['cotiza'];
            console.log(cotiza);
            if(cotiza == true){
              
              $('#cotizacion').prop("disabled", false);
              
            
             

            }else{
             
              $('#cotizacion').prop('disabled', true);
            }
            
          
          }
        }
      });
    }


    function agregar_proveedor(event){
      event.preventDefault();
      
      var parametros = {
          "idtransaccion"   : "idtransaccion",
          "idunico"		  : "idunico"
      };

      $("#titulov").html("Agregar Proveedores");
      $.ajax({		  
        data:  parametros,
        url:   'agregar_proveedor_modal.php',
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {	
          
        },
        success:  function (response) {
          $("#ventanamodal").modal("show");
          $("#cuerpov").html(response);	
          
        }
      });

    }
    function filterFunction2(event) {
      event.preventDefault();
      var input, filter, ul, li, a, i;
      input = document.getElementById("myInput2");
      filter = input.value.toUpperCase();
      div = document.getElementById("myDropdown2");
      a = div.getElementsByTagName("a");
      for (i = 0; i < a.length; i++) {
        txtValue = a[i].textContent || a[i].innerText;
     

        if (txtValue.toUpperCase().indexOf(filter) > -1 ) {
            a[i].style.display = "block";
        } else {
            a[i].style.display = "none";
        }
              
      }
    }
    function myFunction2(event) {
              event.preventDefault();
              document.getElementById("myInput2").classList.toggle("show");
              document.getElementById("myDropdown2").classList.toggle("show");
              div = document.getElementById("myDropdown2");
              $("#myInput2").focus();
           
        
      $(document).mousedown(function(event) {
        var target = $(event.target);
        var myInput = $('#myInput2');
        var myDropdown = $('#myDropdown2');
        var div = $("#lista_compras");
        var button = $("#iddepartameto");
        // Verificar si el clic ocurrió fuera del elemento #my_input
        if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown2").length && myInput.hasClass('show')) {
        // Remover la clase "show" del elemento #my_input
        myInput.removeClass('show');
        myDropdown.removeClass('show');
        }
        
      });
    }
    function cambia_idcompra(idcompra,nombre,ocnum){
      var texto = "["+idcompra+"]-"+nombre+"-"+ocnum;
      $('#idcompra').html($('<option>', {
              value: idcompra,
              text: texto
      }));
     
          
          // Seleccionar opción
          $('#idcompra').val(idcompra);
         
          var myInput = $('#myInput2');
          var myDropdown = $('#myDropdown2');
          myInput.removeClass('show');
          myDropdown.removeClass('show');	
          
    }
    window.onload = function() {
          
          $('#idcompra').on('mousedown', function(event) {
              // Evitar que el select se abra
              event.preventDefault();
          });
          verificar_cotizacion_moneda();


        
          <?php

          if (intval($iddespachante_valor) == 0) {
              echo "
            
            
            alerta('info','- No cuenta con un Tipo Servicio DESPACHANTE favor crearlo.<br>','Alerta');
            ";
          }

?>


  
      };



      function alerta( clase ,error,titulo){
        var alertaClase = 'alert-' + clase;
        // if (clase == "info"){
        //   $('#modal_ventana').removeClass('alert-danger');
        // }else{
        //   $('#modal_ventana').removeClass('alert-info');
        // }
        $('#modal_titulo').html(titulo);
        // $('#modal_ventana').addClass(alertaClase);
        $('#modal_ventana').removeClass('hide');
        $("#modal_cuerpo").html(error);
        $('#modal_ventana').addClass('show');
        
      }


      function cerrar_errores_proveedor(event){
          event.preventDefault();
          $('#modal_ventana').removeClass('show');
          $('#modal_ventana').addClass('hide');
        }
  </script>

  <style type="text/css">
          #lista_ciudades,#lista_compras {
              width: 100%;
          }
        
          .a_link_proveedores{
              display: block;
              padding: 0.8rem;
          }	
          .a_link_proveedores:hover{
              color:white;
              background: #73879C;
          }
          .dropdown_proveedores{
              position: absolute;
              top: 70px;
              left: 0;
              z-index: 99999;
              width: 100% !important;
              overflow: auto;
              white-space: nowrap;
              background: #fff !important;
              border: #c2c2c2 solid 1px;
          }
          .dropdown_proveedores_input{ 
              position: absolute;
              top: 37px;
              left: 0;
              z-index: 99999;
              display:none;
              width: 100% !important;
              padding: 5px !important;
          }
          .btn_proveedor_select{
              border: #c2c2c2 solid 1px;
              color: #73879C;
              width: 100%;
          }
    
  </style>

  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Editar Tipo Cambio Despacho</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	

 




                  
                  
                  <?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">
<p>Obs: Es recomendable hacerlo desde el modulo de compras haciendo click al icono de Despacho, para seleccionar correctamente la compra asociada</p>
<div class="col-md-6 col-xs-12 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Aduana *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<?php

      // consulta

      $consulta = "
				SELECT idaduana, descripcion
				FROM aduana
				where
				estado = 1
				order by descripcion asc
				";

// valor seleccionado
if (isset($_POST['idaduana'])) {
    $value_selected = htmlentities($_POST['idaduana']);
} else {
    $value_selected = $rs->fields['idaduana'];
}


// parametros
$parametros_array = [
    'nombre_campo' => 'idaduana',
    'id_campo' => 'idaduana',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idaduana',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' "'.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
			</div>
</div>





<div class="col-md-6 col-xs-12 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Despachante * </label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<?php

// consulta

$consulta = "
				SELECT nombre, idproveedor
				FROM proveedores
				where
				estado = 1 
        and idtipo_servicio = $iddespachante_valor
				order by nombre asc
				";


// valor seleccionado
if (isset($_POST['iddespachante']) && intval($_POST['iddespachante']) > 0) {
    $value_selected = htmlentities($_POST['iddespachante']);
} else {
    $value_selected = $rs->fields['iddespachante'];
}


// parametros
$parametros_array = [
    'nombre_campo' => 'iddespachante',
    'id_campo' => 'iddespachante',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idproveedor',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' "'.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
			</div>
</div>

<div class="row" style="margin:0;padding:0;">
  <div class="col-md-6 col-xs-12 form-group">
      <label class="control-label col-md-3 col-sm-3 col-xs-12">Compra *</label>
      <div class="col-md-9 col-sm-9 col-xs-12">
          <div class="" style="display:flex;">
              <div class="dropdown " id="lista_compras">
                  <select onclick="myFunction2(event)"  class="form-control" <?php echo $class_idcompra;?> id="idcompra" name="idcompra">
                  <option value="" disabled selected></option>
                  <?php if ($idcompra_get) { ?>
                      <option value="<?php echo $idcompra_get ?>" selected><?php echo $idcompra_text; ?></option>
                  <?php } ?>
              </select>
                  <input class="dropdown_proveedores_input col-md-9 col-sm-9 col-xs-12"type="text" placeholder="Nombre Ciudad" id="myInput2" onkeyup="filterFunction2(event)" >
                  <div id="myDropdown2" class="dropdown-content hide dropdown_proveedores links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
                      <?php echo $resultados_compras ?>
                    </div>
                    <small>-- [idcompra]-proveedor-ocnum --<br> verificar en Registro de compras</small>
                </div>
                <!-- <a  href="javascript:void(0);" onclick="agregar_proveedor(event);" class="btn btn-sm btn-default">
                      <span  class="fa fa-plus"></span> Agregar
                  </a> -->
          </div>
      </div>
  </div>
  
  <div class="col-md-6 col-sm-6 form-group">
  			<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha compra *</label>
  			<div class="col-md-9 col-sm-9 col-xs-12">
  			  <input type="date" name="fecha_despacho" id="fecha_despacho" value="<?php  if (isset($_POST['fecha_despacho'])) {
  			      echo htmlentities($_POST['fecha_despacho']);
  			  } else {
  			      echo date("Y-m-d");
  			  }?>" placeholder="Fecha compra" class="form-control" required onBlur="validar_fecha(this.value);" />
  			</div>
    </div>
</div>




<!-- ///////////////////////////////////////////////////////////////////////////////////// -->



<div class="col-md-6 col-xs-12 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Moneda * </label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<?php

                // consulta

                $consulta = "
				SELECT idtipo, descripcion
				FROM tipo_moneda
				where
				estado = 1
				order by descripcion asc
				";

// valor seleccionado
if (isset($POST['tipo_moneda'])) {
    $value_selected = htmlentities($POST['tipo_moneda']);
} else {
    $value_selected = $rs->fields['tipo_moneda'];
}


// parametros
$parametros_array = [
    'nombre_campo' => 'tipo_moneda',
    'id_campo' => 'tipo_moneda',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idtipo',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" onchange="verificar_cotizacion_moneda(this.value)" "'.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
			</div>
</div>

<!-- ////////////////////////////////////////////////////////////////////////////////////// -->

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Cotizacion *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="cotizacion" id="cotizacion" value="<?php  if (isset($_POST['cotizacion'])) {
	    echo floatval($_POST['cotizacion']);
	} else {
	    echo floatval($rs->fields['cotizacion']);
	}?>" placeholder="Cotizacion" class="form-control" required="required" />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Comentario </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="comentario" id="comentario" value="<?php  if (isset($_POST['comentario'])) {
	    echo htmlentities($_POST['comentario']);
	} else {
	    echo htmlentities($rs->fields['comentario']);
	}?>" placeholder="Comentario" class="form-control"  />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='despacho.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
<small style="font-size:0.9rem;">OBS.:<br>ocnum*:Orden de Compra Numero</small>
</form>
<div class="clearfix"></div>
<br /><br />






                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            

            
            
            
          </div>
        </div>
        <!-- /page content -->
		  
        <!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg fade in  hide" tabindex="-1"  role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg alert">
        <div class="modal-content">
        
            <div class="modal-header">
            	<button type="button" class="close" onclick="cerrar_errores_proveedor(event)"><span aria-hidden="true">×</span></button>
           		<h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
            	Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
            </div>
        
        </div>
    </div>
</div>
        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
