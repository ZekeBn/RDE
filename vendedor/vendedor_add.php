<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// echo "hola";exit;
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";

$dirsup = "S";
require_once("../includes/rsusuario.php");

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
    $tipovendedor = $_POST['tipovendedor'];
    $idtipodoc = $_POST['idtipodoc'];
    $nrodoc = $_POST['nrodoc'];
    $nomape = $_POST['nomape'];
    $nombres = $_POST['nombres'];
    $apellidos = $_POST['apellidos'];
    $estado = $_POST['estado'];
    $estado = 1;
    $idempresa = $_POST['idempresa'];
    $carnet = $_POST['carnet'];
    $pin = $_POST['pin'];
    $registradopor = $idusu;
    $registradoel = $ahora;


    $parametros_array = [
        "tipovendedor" => $tipovendedor,
        "idtipodoc" => $idtipodoc,
        "nrodoc" => $nrodoc,
        "nomape" => $nomape,
        "nombres" => $nombres,
        "apellidos" => $apellidos,
        "estado" => $estado,
        "idempresa" => $idempresa,
        "carnet" => $carnet,
        "pin" => $pin,
    'idusu' => $idusu,
    'ahora' => $ahora
    ];

    // si todo es correcto actualiza
    if ($valido == "S") {
        $res = vendedor_add($parametros_array);
        if ($res["valido"] == "S") {
            header("location: vendedor.php");
            exit;
        } else {
            $errores .= $res["errores"];
        }

    } else {
        $errores .= $res["errores"];
    }


}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

// se puede mover esta funcion al archivo funciones_vendedor.php y realizar un require_once
function vendedor_add($parametros_array)
{
    global $conexion;
    global $saltolinea;

    // validaciones basicas
    $valido = "S";
    $errores = "";


    $tipovendedor = antisqlinyeccion($parametros_array['tipovendedor'], "int");
    $idtipodoc = antisqlinyeccion($parametros_array['idtipodoc'], "int");
    $nrodoc = antisqlinyeccion($parametros_array['nrodoc'], "int");
    $nomape = antisqlinyeccion($parametros_array['nomape'], "text");
    $nombres = antisqlinyeccion($parametros_array['nombres'], "text");
    $apellidos = antisqlinyeccion($parametros_array['apellidos'], "text");
    $estado = antisqlinyeccion($parametros_array['estado'], "text");
    $idempresa = antisqlinyeccion($parametros_array['idempresa'], "int");
    $carnet = '';
    $pin = '';
    $registrado_por = antisqlinyeccion($parametros_array['idusu'], "int");
    $registrado_el = antisqlinyeccion($parametros_array['ahora'], "int");


    if (intval($parametros_array['tipovendedor']) == 0) {
        $valido = "N";
        $errores .= " - El campo tipovendedor no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['idtipodoc']) == 0) {
        $valido = "N";
        $errores .= " - El campo idtipodoc no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['nrodoc']) == 0) {
        $valido = "N";
        $errores .= " - El campo nrodoc no puede ser cero o nulo.<br />";
    }
    /*
    nomape
        if(trim($parametros_array['nomape']) == ''){
            $valido="N";
            $errores.=" - El campo nomape no puede estar vacio.<br />";
        }
    */
    if (trim($parametros_array['nombres']) == '') {
        $valido = "N";
        $errores .= " - El campo nombres no puede estar vacio.<br />";
    }
    /*
    apellidos
        if(trim($parametros_array['apellidos']) == ''){
            $valido="N";
            $errores.=" - El campo apellidos no puede estar vacio.<br />";
        }
    */
    if (trim($parametros_array['estado']) == '') {
        $valido = "N";
        $errores .= " - El campo estado no puede estar vacio.<br />";
    }
    if (intval($parametros_array['idempresa']) == 0) {
        $valido = "N";
        $errores .= " - El campo idempresa no puede ser cero o nulo.<br />";
    }
    /*
    carnet
        if(trim($parametros_array['carnet']) == ''){
            $valido="N";
            $errores.=" - El campo carnet no puede estar vacio.<br />";
        }
    */
    /*
    pin
        if(trim($parametros_array['pin']) == ''){
            $valido="N";
            $errores.=" - El campo pin no puede estar vacio.<br />";
        }
    */


    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		insert into vendedor
		(tipovendedor, idtipodoc, nrodoc, nomape, nombres, apellidos, estado, idempresa, carnet, pin, motivo, registrado_por, registrado_el)
		values
		($tipovendedor, $idtipodoc, $nrodoc, $nomape, $nombres, $apellidos, $estado, $idempresa, $carnet, $pin, $registrado_por, $registrado_el)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }


    return ["errores" => $errores,"valido" => $valido];
}



?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>

<script>

function mostrarOcultarCampoTexto() {
    var motivoLabel = document.getElementById("motivo_label");
    var motivoInput = document.getElementById("motivo");
    var opcionActiva = document.querySelector('input[name="opcion"]:checked');
    var estadoCliente = null;

    if (opcionActiva) {
        estadoCliente = opcionActiva.value;

        if (estadoCliente === "1") {
            motivoLabel.style.display = "none";
            motivoInput.style.display = "none";
        } else {
            motivoLabel.style.display = "block";
            motivoInput.style.display = "block";
        }
    }
}

</script>

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
                    <h2>Registro de Vendedores</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                  <!-- AQUI SE COLOCA EL HTML -->

<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">

<div class="col-md-6 col-sm-6 form-group" style="display:none">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Cod. Persona</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="codpersona" id="codpersona" value="<?php  if (isset($_POST['codpersona'])) {
	    echo intval($_POST['codpersona']);
	}?>" placeholder="Cod. Persona" class="form-control" required="required" disabled />                    
	</div>
</div>

<div class="clearfix"></div>

<div class="col-md-6 col-sm-6 form-group" id="tipocredito">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo de Vendedor</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
      <?php
      // consulta
      $consulta = "
      SELECT idtipovendedor, descripcion
      FROM tipo_vendedor
      order by descripcion asc
      ";

// valor seleccionado
if (isset($_POST['idtipovendedor'])) {
    $value_selected = htmlentities($_POST['idtipovendedor']);
} else {
    $value_selected = htmlentities($rscli->fields['idtipovendedor']);
}

// parametros
$parametros_array = [
  'nombre_campo' => 'idtipovendedor',
  'id_campo' => 'idtipovendedor',

  'nombre_campo_bd' => 'descripcion',
  'id_campo_bd' => 'idtipovendedor',

  'value_selected' => $value_selected,

  'pricampo_name' => 'Seleccionar...',
  'pricampo_value' => '',
  'style_input' => 'class="form-control"',
  'acciones' => '   ',
  'autosel_1registro' => 'S'
];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
  </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo de Documento</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php

    // consulta
    $consulta = "
    SELECT idtipodoc, descripcion_larga
    FROM tipodocumento
    order by descripcion_larga asc
     ";

// valor seleccionado
if (isset($_POST['codigo_zona'])) {
    $value_selected = htmlentities($_POST['codigo_zona']);
} else {
    $value_selected = htmlentities($rs->fields['codigo_zonat']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtipodoc',
    'id_campo' => 'idtipodoc',

    'nombre_campo_bd' => 'descripcion_larga',
    'id_campo_bd' => 'idtipodoc',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '   ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>          
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nro. Documento</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nrodoc" id="nrodoc" value="<?php  if (isset($_POST['nrodoc'])) {
	    echo intval($_POST['nrodoc']);
	}?>" placeholder="Nro. Documento" class="form-control" required="required" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombres *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombres" id="nombres" value="<?php  if (isset($_POST['nombres'])) {
	    echo htmlentities($_POST['nombres']);
	}?>" placeholder="Nombres" class="form-control" required="required" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Apellidos </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="apellidos" id="apellidos" value="<?php  if (isset($_POST['apellidos'])) {
	    echo htmlentities($_POST['apellidos']);
	}?>" placeholder="Apellidos" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Zonas </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php

    // consulta
    $consulta = "
    SELECT codigo_zona, descripcion
    FROM zona_vendedor
    order by descripcion asc
     ";

// valor seleccionado
if (isset($_POST['codigo_zona'])) {
    $value_selected = htmlentities($_POST['codigo_zona']);
} else {
    $value_selected = htmlentities($rs->fields['codigo_zonat']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'codigo_zona',
    'id_campo' => 'codigo_zona',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'codigo_zona',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '   ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>          
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Edo. Vendedor</label>
    <div class="col-md-9 col-sm-9 col-xs-12 checkbox-container">
        <input type="radio" name="opcion" value="1" onchange="mostrarOcultarCampoTexto()" <?php if (isset($_POST["opcion"]) && $_POST["opcion"] == "1") {
            echo "checked";
        } ?> checked> Activo<br>
        <input type="radio" name="opcion" value="0" onchange="mostrarOcultarCampoTexto()" <?php if (isset($_POST["opcion"]) && $_POST["opcion"] == "0") {
            echo "checked";
        } ?>> Inactivo <br>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12" id="motivo_label" style="display:none;">Motivo</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="motivo" id="motivo" value="<?php  if (isset($_POST['motivo'])) {
            echo htmlentities($_POST['motivo']);
        }?>" placeholder="Motivo" class="form-control" style="display:none;">                    
    </div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='../clientes/cliente_vend.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />

<?php
$consulta = "
select *, 
(select descripcion from tipo_vendedor where tipo_vendedor.idtipovendedor=vendedor.tipovendedor) as descripcion,
(select descripcion from zona_vendedor where zona_vendedor.codigo_zona=vendedor.codigo_zona) as zona_desc
from vendedor
where 
estado='A'
and idempresa=1
order by idvendedor desc
limit 10
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
<br /><hr /><br />
<strong>Ultimos 10 registros:</strong><br />
<div class="table-responsive tablaconborde">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>


		    <th align="center">Tipo Vendedor</th>
			<th align="center">Codigo</th>
			<th align="center">Cod. Persona </th>
			<th align="center">Doc. de Identidad</th>
			<th align="center">Nombre y Apellido</th>
			<th align="center">Zona</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>


      		<td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['codigo_vendedor']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['codigo_persona']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['nrodoc']); ?></td>
      		<td align="center"><?php echo antixss($rs->fields['nomape']); ?></td>
			  <td align="center"><?php echo antixss($rs->fields['zona_desc']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			}  ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />

<br /><br />
                  </div>
                </div>
              </div>
            </div>

            <!-- SECCION --> 
            

            
            
            

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
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
