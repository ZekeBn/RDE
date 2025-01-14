<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "436";
require_once("includes/rsusuario.php");



$idclienteagrupa = intval($_GET['id']);
if ($idclienteagrupa == 0) {
    header("location: cliente_agrupacion.php");
    exit;
}

// consulta a la tabla
$consulta = "
select *
from cliente_agrupacion 
where 
idclienteagrupa = $idclienteagrupa
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idclienteagrupa = intval($rs->fields['idclienteagrupa']);
if ($idclienteagrupa == 0) {
    header("location: cliente_agrupacion.php");
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
    //$idclienteagrupa=antisqlinyeccion($_POST['idclienteagrupa'],"int");
    $idcliente = antisqlinyeccion($_POST['idcliente'], "int");
    $estado = 1;
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");




    /*if(intval($_POST['idclienteagrupa']) == 0){
        $valido="N";
        $errores.=" - El campo agrupacion no puede estar vacio.<br />";
    }*/
    if (intval($_POST['idcliente']) == 0) {
        $valido = "N";
        $errores .= " - El campo cliente no puede estar vacio.<br />";
    }


    $consulta = "
	select * 
	from cliente_agrupacion_det
	where 
	idcliente = $idcliente 
	and idclienteagrupa = $idclienteagrupa 
	and estado = 1 
	limit 1
	";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsex->fields['idcliente']) > 0) {
        $valido = "N";
        $errores .= " - El cliente ya existe para esta agrupacion.<br />";
    }


    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		insert into cliente_agrupacion_det
		(idclienteagrupa, idcliente, estado, registrado_por, registrado_el)
		values
		($idclienteagrupa, $idcliente, $estado, $registrado_por, $registrado_el)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: cliente_agrupacion_det.php?id=$idclienteagrupa");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());





?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
	<script>
function alerta_modal(titulo,mensaje){
	$('#modal_ventana').modal('show');
	$("#modal_titulo").html(titulo);
	$("#modal_cuerpo").html(mensaje);

	
}
function busca_cliente(){
	var direccionurl='busqueda_cliente_agrup.php';		
	var parametros = {
	  "m" : '1'		  
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		beforeSend: function () {
			$('#modal_ventana').modal('show');
			$("#modal_titulo").html('Busqueda de Cliente');
			$("#modal_cuerpo").html('Cargando...');				
		},
		success:  function (response) {
			$("#modal_cuerpo").html(response);
		}
	});
	
}
function busca_cliente_res(tipo){
	var ruc = $("#ruc").val();
	var razon_social = $("#razon_social").val();
	if(tipo == 'ruc'){
		razon_social = '';
		$("#razon_social").val('');
	}
	if(tipo == 'razon_social'){
		ruc = '';
		$("#ruc").val('');
	}
	var direccionurl='busqueda_cliente_agrup_res.php';		
	var parametros = {
	  "ruc"            : ruc,
	  "razon_social"   : razon_social
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		beforeSend: function () {
			$("#busqueda_cli").html('Cargando...');				
		},
		success:  function (response) {
			$("#busqueda_cli").html(response);
		}
	});
}
function seleccionar_item(idcliente,descricion){
	$("#idcliente").val(idcliente+' - '+descricion);
	$('#modal_ventana').modal('hide');
}
</script>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Agrupacion de Clientes</h2>
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

<p><a href="cliente_agrupacion.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">Idclienteagrupa</th>
			<th align="center">Agrupacion</th>
			<th align="center">Estado</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>
			<th align="center">Borrado por</th>
			<th align="center">Borrado el</th>
		</tr>
	  </thead>
	  <tbody>

		<tr>
			<td align="center"><?php echo intval($rs->fields['idclienteagrupa']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['agrupacion']); ?></td>
			<td align="center"><?php echo intval($rs->fields['estado']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['borrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['borrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['borrado_el']));
			}  ?></td>
		</tr>

	  </tbody>
    </table>
</div>
<br />

<form id="form1" name="form1" method="post" action="">

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Agrupacion *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
// consulta
$consulta = "
SELECT idclienteagrupa , agrupacion
FROM cliente_agrupacion
where
estado = 1
and idclienteagrupa = $idclienteagrupa
order by agrupacion asc
 ";

// valor seleccionado
if (isset($_POST['idclienteagrupa'])) {
    $value_selected = htmlentities($_POST['idclienteagrupa']);
} else {
    $value_selected = htmlentities($rs->fields['idclienteagrupa']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idclienteagrupa',
    'id_campo' => 'idclienteagrupa',

    'nombre_campo_bd' => 'agrupacion',
    'id_campo_bd' => 'idclienteagrupa',

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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Cliente *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idcliente" id="idcliente" value="<?php  if (intval($idcliente) > 0) {
	    echo antixss($rscli->fields['idcliente'].' - '.$rscli->fields['razon_social']);
	} ?>" placeholder="Click para buscar..." class="form-control" onMouseUp="busca_cliente()"  required readonly />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-default" ><span class="fa fa-plus"></span> Agregar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<hr />
<?php
$consulta = "
select cliente_agrupacion_det.*, cliente.razon_social, cliente.fantasia,
(select cliente_agrupacion.agrupacion from cliente_agrupacion where idclienteagrupa = cliente_agrupacion_det.idclienteagrupa) as agrupacion,
(select razon_social from cliente where idcliente  = cliente_agrupacion_det.idcliente) as cliente,
(select usuario from usuarios where cliente_agrupacion_det.registrado_por = usuarios.idusu) as registrado_por
from cliente_agrupacion_det 
inner join cliente on cliente.idcliente = cliente_agrupacion_det.idcliente
where 
 cliente_agrupacion_det.estado = 1 
 and cliente.estado = 1 
 and idclienteagrupa = $idclienteagrupa
order by idclienteagrupadet asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>


			<th align="center">Cliente</th>
			<th align="center">Agrupacion</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
				
					<a href="cliente_agrupacion_det_del.php?id=<?php echo $rs->fields['idclienteagrupadet']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo antixss($rs->fields['cliente']); ?> [<?php echo intval($rs->fields['idcliente']); ?>]</td>
			<td align="center"><?php echo antixss($rs->fields['agrupacion']); ?> [<?php echo intval($rs->fields['idclienteagrupa']); ?>]</td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			}  ?></td>
		</tr>
<?php

$rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />



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

        <!-- footer content -->
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
