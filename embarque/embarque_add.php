<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo

$modulo = "42";
$submodulo = "598";
// $modulo="1";
// $submodulo="2";
$dirsup = "S";
require_once("../includes/rsusuario.php");

$ocn = intval($_GET['ocn']);
$path = ($_GET['path']);
$ocn_ref = intval($_GET['ocn_ref']);
$idcompra = intval($_GET['idcompra']);
$consulta = "SELECT idembarque FROM embarque WHERE ocnum = $ocn ";

$respuesta_embarque = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idembarque = intval($respuesta_embarque->fields['idembarque']);
if ($idembarque > 0) {
    header("location: embarque_det.php?id=$idembarque");
    exit;
}



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
    // $idcompra=antisqlinyeccion($_POST['idcompra'],"int");
    $idpuerto = antisqlinyeccion($_POST['idpuerto'], "int");
    $idtransporte = antisqlinyeccion($_POST['idtransporte'], "int");
    $idvias_embarque = antisqlinyeccion($_POST['idvias_embarque'], "int");
    $descripcion = antisqlinyeccion($_POST['descripcion'], "text");
    $fecha_embarque = antisqlinyeccion($_POST['fecha_embarque'], "text");
    $fecha_llegada = antisqlinyeccion($_POST['fecha_llegada'], "text");
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $estado_embarque = antisqlinyeccion($_POST['estado_embarque'], "int");
    $ocnum = antisqlinyeccion($_POST['ocnum'], "int");
    $estado = 1;
    if ($ocn > 0) {
        $ocnum = $ocn;
    }


    // if(intval($_POST['idcompra']) == 0){
    // 	$valido="N";
    // 	$errores.=" - El campo idcompra no puede ser cero o nulo.<br />";
    // }
    if (intval($_POST['idpuerto']) == 0) {
        $valido = "N";
        $errores .= " - El campo idpuerto no puede ser cero o nulo.<br />";
    }
    if (intval($_POST['idtransporte']) == 0) {
        $valido = "N";
        $errores .= " - El campo idtransporte no puede ser cero o nulo.<br />";
    }
    if (intval($_POST['idvias_embarque']) == 0) {
        $valido = "N";
        $errores .= " - El campo idvias_embarque no puede ser cero o nulo.<br />";
    }
    if ($ocnum == 0) {
        $valido = "N";
        $errores .= " - El campo orden de compra no puede ser cero o nulo.<br />";
    }

    if (trim($_POST['fecha_embarque']) == '') {
        $valido = "N";
        $errores .= " - El campo fecha_embarque no puede estar vacio.<br />";
    }
    if (trim($_POST['fecha_llegada']) == '') {
        $valido = "N";
        $errores .= " - El campo fecha_llegada no puede estar vacio.<br />";
    }


    // si todo es correcto inserta
    if ($valido == "S") {
        $idembarque = select_max_id_suma_uno("embarque", "idembarque")["idembarque"];
        $consulta = "
		insert into embarque
		(idembarque,ocnum, estado_embarque, idpuerto, idtransporte, idvias_embarque, descripcion, fecha_embarque, fecha_llegada, registrado_por, registrado_el, estado, idcompra)
		values
		($idembarque, $ocnum, $estado_embarque, $idpuerto, $idtransporte, $idvias_embarque, $descripcion, $fecha_embarque, $fecha_llegada, $registrado_por, $registrado_el, $estado, $idcompra)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "UPDATE compras_ordenes
		SET 
		estado = 2
		WHERE 
		ocnum = $ocnum
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if ($ocn_ref != 0) {
            header("location: ../compras_ordenes/compras_ordenes_det_finalizado.php?id=$ocn_ref");
        } elseif ($path == "ordenes") {
            header("location: ../compras_ordenes/compras_ordenes.php");
        } elseif ($path == "ocnum_det") {
            header("location: ../compras_ordenes/compras_ordenes_det.php?id=$ocn");
        } else {
            header("location: embarque.php");
        }
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
	function recargar_puerto_select(){
		var parametros = {
			"buscar"   : 1
		};
		$.ajax({
			data:  parametros,
			url:   './select_puerto.php',
			type:  'post',
			beforeSend: function () {
				
			},
			success:  function (response) {
				$("#box_puerto").html(response);
			}
		});
		
	}
	function abrir_modal_puerto(){
		document.getElementById('preloader-overlay').style.display = 'flex';

		var parametros = {
			"buscar"   : 1
		};
		$.ajax({
			data:  parametros,
			url:   './modal_puertos.php',
			type:  'post',
			beforeSend: function () {
				
			},
			success:  function (response) {
			document.getElementById('preloader-overlay').style.display = 'none';

					alerta_modal("Agregar Puerto", response);
			}
		});

	}
    function cambiar_vias_embarque(selectElement){
      const selectedOption = selectElement.options[selectElement.selectedIndex];
      const idViasEmbarque = selectedOption.dataset.hiddenValue;
      // console.log(idViasEmbarque);
      $("#idvias_embarque").val(idViasEmbarque)

    }
	function cerrar_pop(){
		$("#modal_ventana").modal("hide");
	}

	function alerta_modal(titulo,mensaje){
		$('#modal_ventana').modal('show');
		$("#modal_titulo").html(titulo);
		$("#modal_cuerpo").html(mensaje);
	}

  </script>
  <style>
	 /* /////////////////////////// */
	 #preloader-overlay {
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background-color: rgba(0, 0, 0, 0.5);
		z-index: 9999;
		display: none;
		align-items: center;
		justify-content: center;
		}

		.lds-facebook {
		display: inline-block;
		position: relative;
		width: 80px;
		height: 80px;
		}

		.lds-facebook div {
		display: inline-block;
		position: absolute;
		left: 8px;
		width: 16px;
		background: #fff;
		animation: lds-facebook 1.2s cubic-bezier(0, 0.5, 0.5, 1) infinite;
		}

		.lds-facebook div:nth-child(1) {
		left: 8px;
		animation-delay: -0.24s;
		}

		.lds-facebook div:nth-child(2) {
		left: 32px;
		animation-delay: -0.12s;
		}

		.lds-facebook div:nth-child(3) {
		left: 56px;
		animation-delay: 0;
		}

		@keyframes lds-facebook {
		0% {
			top: 8px;
			height: 64px;
		}
		50%,
		100% {
			top: 24px;
			height: 32px;
		}
		}
	/* /////////////////////// */
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
                    <h2>Embarque</h2>
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

<!-- <div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idcompra *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idcompra" id="idcompra" value="<?php  //if(isset($_POST['idcompra'])){ echo intval($_POST['idcompra']); }else{ echo intval($rs->fields['idcompra']); }?>" placeholder="Idcompra" class="form-control" required="required" />                    
	</div>
</div> -->


<div class="col-md-6 col-xs-12 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12"><a href="javascript:void(0)" class="btn btn-xs btn-default" onclick="abrir_modal_puerto()"><span class="fa fa-plus"></span></a>Puerto *</label>
	<div id="box_puerto" class="col-md-9 col-sm-9 col-xs-12">
		<?php require_once("./select_puerto.php"); ?>
	</div>
</div>


<div class="col-md-6 col-xs-12 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Transporte *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
		<?php

                // consulta

                $consulta = "
				SELECT idtransporte, idvias_embarque, descripcion
				FROM transporte
				where
				estado = 1
				order by descripcion asc
				";

// valor seleccionado
if (isset($_POST['idtransporte'])) {
    $value_selected = htmlentities($_POST['idtransporte']);
} else {
    $value_selected = $rs->fields['idtransporte'];
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtransporte',
    'id_campo' => 'idtransporte',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idtransporte',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
          'data_hidden' => 'idvias_embarque',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" onchange="cambiar_vias_embarque(this)" "'.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>




<div class="col-md-6 col-xs-12 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Vias embarque *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<?php

        // consulta

        $consulta = "
				SELECT idvias_embarque, descripcion
				FROM vias_embarque
				where
				estado = 1
				order by descripcion asc
				";

// valor seleccionado
if (isset($_POST['idvias_embarque'])) {
    $value_selected = htmlentities($_POST['idvias_embarque']);
} else {
    $value_selected = $rs->fields['idvias_embarque'];
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idvias_embarque',
    'id_campo' => 'idvias_embarque',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idvias_embarque',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" "'.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
      </div>
</div>


<div class="col-md-6 col-xs-12 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Estado Embarque *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
		<?php

if (isset($_POST['estado_embarque'])) {
    $value_selected = htmlentities($_POST['estado_embarque']);
} else {
    $value_selected = '1';
}
// opciones
$opciones = [
'Activo' => '1',
'Finalizado' => '2'
];
// parametros
$parametros_array = [
'nombre_campo' => 'estado_embarque',
'id_campo' => 'estado_embarque',

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







  <div class="col-md-6 col-sm-12 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Descripcion </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="descripcion" id="descripcion" value="<?php  if (isset($_POST['descripcion'])) {
        echo htmlentities($_POST['descripcion']);
    } else {
        echo htmlentities($rs->fields['descripcion']);
    }?>" placeholder="Descripcion" class="form-control" />                    
    </div>
  </div>




<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha embarque *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="fecha_embarque" id="fecha_embarque" value="<?php  if (isset($_POST['fecha_embarque'])) {
	    echo htmlentities($_POST['fecha_embarque']);
	} else {
	    echo htmlentities(date('Y-m-d'));
	}?>" placeholder="Fecha embarque" class="form-control" required="required" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha llegada Estimada*</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="fecha_llegada" id="fecha_llegada" value="<?php  if (isset($_POST['fecha_llegada'])) {
	    echo htmlentities($_POST['fecha_llegada']);
	} else {
	    echo htmlentities($rs->fields['fecha_llegada']);
	}?>" placeholder="Fecha llegada" class="form-control" required="required" />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Orden Compra</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<?php require_once("tmpcompras_ocn.php"); ?>
			</div>
</div>



<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='<?php if ($ocn_ref != 0) { ?>../compras_ordenes/compras_ordenes_det_finalizado.php?id=<?php echo $ocn_ref ?><?php } elseif ($path == "ordenes") {?>../compras_ordenes/compras_ordenes.php<?php } elseif ($path == "ocnum_det") {?>../compras_ordenes/compras_ordenes_det.php?id=<?php echo $ocn ?><?php } else { ?>embarque.php<?php } ?>'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
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
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        
            <div class="modal-header">
            	<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
           		<h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
            	Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
            	<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        
        </div>
    </div>
</div>
        <!-- POPUP DE MODAL OCULTO -->
		<div id="preloader-overlay">
			<div class="lds-facebook">
				<div></div>
				<div></div>
				<div></div>
			</div>
		</div>


        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
