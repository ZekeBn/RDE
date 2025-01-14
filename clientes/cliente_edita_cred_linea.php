<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "126";
require_once("../includes/rsusuario.php");

$id = intval($_GET['id']);
if ($id == 0) {
    echo "No especifico el cliente.";
    exit;
}

//Traemos las preferencias de la empresa
$buscar = "Select * from preferencias where idempresa=$idempresa";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$usaradherente = trim($rspref->fields['usa_adherente']);

$buscar = "Select usa_maxmensual from preferencias_caja";
$rsprefcaj = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$usa_maxmensual = trim($rsprefcaj->fields['usa_maxmensual']);



// si usa adherente asumimos que le permite credito
if ($usaradherente == 'S') {
    $consulta = "
		UPDATE cliente 
		SET 
			permite_acredito='S'
		WHERE
			idcliente=$id
			and idempresa=$idempresa
		";
    //$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
}


$buscar = "
select * 
from cliente 
where 
idcliente = $id 
and idempresa = $idempresa
and estado <> 6
";
$rscli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$borrable = $rscli->fields['borrable'];
$razon_social = $rscli->fields['razon_social'];
$permite_creditobd = $rscli->fields['permite_credito'];


//echo $permite_creditobd; exit;

if ($permite_creditobd == 1) {
    $mostrar_tipocredito = true; // Mostrar el combobox tipocredito
    $mostrar_limite_credito = true; // Mostrar el textbox limite_credito
} else {
    $mostrar_tipocredito = false; // No mostrar el combobox tipocredito
    $mostrar_limite_credito = false; // No mostrar el textbox limite_credito
}


if (intval($rscli->fields['idcliente']) == 0) {
    echo "Cliente inexistente!";
    exit;
}
if ($borrable != 'S') {
    echo "El cliente $razon_social no puede ser editado.";
    exit;
}

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    //print_r($_POST);

    // recibe variables
    // $permite_acredito=antisqlinyeccion($_POST['permite_credito'],"text");
    // $linea_sobregiro=antisqlinyeccion($_POST['linea_sobregiro'],"float");
    // $max_mensual=antisqlinyeccion($_POST['max_mensual'],"float");
    if (isset($_POST['credito'])) {
        $permite_credito = 1;
    } else {
        $permite_credito = 0;
    }
    $idcredito = $_POST['idcredito'];
    $limite_credito = $_POST['limite_credito'];

    //echo $permite_credito, $idcredito, $limite_credito; exit;
    //validar
    $valido = "S";
    if (trim($_POST['idcredito']) == '' && $permite_credito == 1) {
        $errores .= "- Debe indicar el tipo de credito del cliente.<br />";
        $valido = "N";
    }

    if (trim($_POST['limite_credito']) == '' && $permite_credito == 1) {
        $errores .= "- Debe indicar el limite de credito del cliente.<br />";
        $valido = "N";
    }
    // if(floatval($_POST['linea_sobregiro']) > 0 or floatval($_POST['max_mensual']) > 0){
    // 	if(trim($_POST['permite_acredito']) != 'S'){
    // 		$errores.="- Debe indicar que esta permitido a credito si asigno linea de sobregiro y/o maximo mensual.<br />";
    // 		$valido="N";
    // 	}
    // }
    if (trim($_POST['permite_credito']) == 'S') {
        /*if(floatval($_POST['linea_sobregiro']) <= 0){
            //$errores.="- Debe asignar una linea de sobregiro mayor o igual a 0.<br />";
            $errores.="- Debe asignar una linea de sobregiro mayor a 0.<br />";
            $valido="N";
        }
        if(floatval($_POST['max_mensual']) <= 0){
            //$errores.="- Debe asignar un maximo mensual mayor o igual a 0.<br />";
            $errores.="- Debe asignar un maximo mensual mayor a 0.<br />";
            $valido="N";
        }*/
    }

    // si se usa adherentes
    if ($rsco->fields['usa_adherente'] == 'S') {
        // suma todas las lineas y maximos de los adherentes de este titular
        $idcliente = $id;
        $consulta = "
		select sum(maximo_mensual) as maximo_mensual_tot, sum(linea_sobregiro) as linea_sobregiro_tot 
		from adherentes
		where
		idcliente = $idcliente
		 and idempresa = $idempresa	 
		 and adherentes.estado <> 6
		";
        $rstot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $lineasobregiroad = $rstot->fields['linea_sobregiro_tot'];
        $maxmensualad = $rstot->fields['maximo_mensual_tot'];
        if ($lineasobregiroad > floatval($_POST['linea_sobregiro'])) {
            $valido = "N";
            $errores .= " - La sumatoria de lineas de credito de los adherentes (".formatomoneda($lineasobregiroad).") supera la linea de credito del titular (".formatomoneda(floatval($_POST['linea_sobregiro'])).").<br />";
        }
        // if($usa_maxmensual == 'S'){
        // 	if($maxmensualad > floatval($_POST['max_mensual'])){
        // 		$valido="N";
        // 		$errores.=" - La sumatoria de maximos mensuales de los adherentes supera el maximo permitido del titular.<br />";
        // 	}
        // }
    }

    // if($usa_maxmensual != 'S'){
    // 	$max_mensual="999999999999";
    // }

    // actualiza
    if ($valido == 'S') {
        $consulta = "
		UPDATE cliente 
		SET 
			permite_credito=$permite_credito,
			idcredito = $idcredito,
			limite_credito = $limite_credito
		WHERE
			idcliente=$id
			and idempresa=$idempresa
			and estado <> 6
			and estado <> 2
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // registra en el log
        if ($permite_credito == 1) {
            $consulta = "
		INSERT INTO clientes_lineas_log
		(idcliente, permite_acredito, permite_credito, max_mensual,idcredito, linea_sobregiro, registrado_por, registrado_el) 
		VALUES 
		($id,'', $permite_credito, $limite_credito, $idcredito,0, $idusu, '$ahora')
		";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // actualiza saldo sobregiro
            actualiza_saldos_clientes($id);
            /*$consulta="
            update cliente set
            saldo_sobregiro = linea_sobregiro-COALESCE((
                                select sum(cuentas_clientes.saldo_activo) as saldoactivo
                                from cuentas_clientes
                                where
                                cuentas_clientes.idcliente = cliente.idcliente
                                and cuentas_clientes.idempresa = cliente.idempresa
                                and cuentas_clientes.estado <> 6
                              ),0)
            where
            cliente.idempresa = $idempresa
            and cliente.idcliente=$id
            and estado <> 6
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));*/

            //echo $consulta;
            if ($_GET['ref'] == 'ca') {
                if ($usaradherente != 'S') {
                    header("location: cliente.php?id=".$id."");
                    exit;
                } else {
                    header("location: adherentes_credito.php?id=".$id."");
                    exit;
                }
            }
            if ($usaradherente != 'S') {
                header("location: venta_credito.php?id=".$id."&ok=s");
                exit;
            } else {
                if ($_POST['permite_acredito'] == 'S') {
                    header("location: adherentes_credito.php?id=".$id."");
                    exit;
                } else {
                    header("location: venta_credito.php?id=".$id."&ok=s");
                    exit;
                }
            }

        }
    }


}
// establece maximos mensuales automaticamente, parametrizar en preferencias
/*if($database == 'sistema_martaelena_sil' or $database == 'sistema_martaelena_bautista' or $database == 'benditas'){

    // maximos mensuales
    $consulta="
    update cliente set max_mensual = 1000000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $consulta="
    update adherentes set maximo_mensual = 100000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $consulta="
    update adherentes_servicioscom set max_mensual = 10000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    // linea de credito
    $consulta="
    update cliente set linea_sobregiro = 1000000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $consulta="
    update adherentes set linea_sobregiro = 100000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $consulta="
    update adherentes_servicioscom set linea_credito = 10000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    // disponible trucho
    $consulta="
    update cliente set saldo_sobregiro = 1000000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $consulta="
    update adherentes set disponible = 100000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $consulta="
    update adherentes_servicioscom set disponibleserv = 10000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
}*/

?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>

  <script>
	function mostrarCampos() {
        var permite_credito = document.getElementById("checkbox1").checked ? 1 : 0;
        var mensajePermiteCredito = document.getElementById("mensajePermiteCredito");
        var tipocredito = document.getElementById("tipocredito");
        var limiteCreditoInput = document.getElementById("limite_credito");

        if (permite_credito == 0) {
            mensajePermiteCredito.textContent = "No permite crédito, marque para autorizar";
            tipocredito.style.display = "none"; // Ocultar el combobox tipocredito
            limiteCreditoInput.style.display = "none"; // Ocultar el textbox limite_credito
        } else {
            mensajePermiteCredito.textContent = "Permite Crédito";
            tipocredito.style.display = "block"; // Mostrar el combobox tipocredito
            limiteCreditoInput.style.display = "block"; // Mostrar el textbox limite_credito
        }
    }

    // Ejecutar la función mostrarCampos() después de cargar la página
    document.addEventListener("DOMContentLoaded", function() {
        mostrarCampos(); // Llama a la función mostrarCampos al cargar la página
    });

    // Ejecutar la función mostrarCampos cada vez que cambie el estado del checkbox
    document.getElementById("checkbox1").addEventListener("change", mostrarCampos);

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
                    <h2>Linea de Credito</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


	  
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Idcliente</th>
			<th align="center">Tipocliente</th>
			<th align="center">Razon social</th>
			<th align="center">Ruc</th>
			<th align="center">Documento</th>
			<th align="center">Nombre</th>
			<th align="center">Apellido</th>
			<th align="center">Fantasia</th>

		</tr>
	  </thead>
	  <tbody>

		<tr>
			<td align="center"><?php echo intval($rscli->fields['idcliente']); ?></td>
			<td align="center"><?php echo antixss($rscli->fields['tipocliente']); ?></td>
			<td align="center"><?php echo antixss($rscli->fields['razon_social']); ?></td>
			<td align="center"><?php echo antixss($rscli->fields['ruc']); ?></td>
			<td align="center"><?php echo antixss($rscli->fields['documento']); ?></td>
			<td align="center"><?php echo antixss($rscli->fields['nombre']); ?></td>
			<td align="center"><?php echo antixss($rscli->fields['apellido']); ?></td>
			<td align="center"><?php echo antixss($rscli->fields['fantasia']); ?></td>
		</tr>
	  </tbody>
    </table>
</div>
<hr />

					  					  
<?php if ($_GET['ok'] != 's') { ?>    
<form id="form1" name="form1" method="post" action="">     


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">¿Permite Crédito?</label>
    <div class="col-md-9 col-sm-9 col-xs-12 checkbox-container">
        <input type="checkbox" id="checkbox1" name="credito" value="1" onchange="mostrarCampos()" <?php if ($permite_creditobd == 1) {
            echo "checked";
        } ?>>
        <span id="mensajePermiteCredito"><?php echo ($permite_creditobd == 1) ? "Permite Crédito" : "No permite crédito, marque para autorizar" ; ?></span>
    </div>
</div>



<div class="col-md-6 col-sm-6 form-group" id="tipocredito">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo de Crédito</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
      <?php
      // consulta
      $consulta = "
      SELECT idcredito, descripcion
      FROM tipo_credito
      WHERE estado = '1'
      ";

    // valor seleccionado
    if (isset($_POST['idcredito'])) {
        $value_selected = htmlentities($_POST['idcredito']);
    } else {
        $value_selected = htmlentities($rscli->fields['idcredito']);
    }

    // parametros
    $parametros_array = [
      'nombre_campo' => 'idcredito',
      'id_campo' => 'idcredito',

      'nombre_campo_bd' => 'descripcion',
      'id_campo_bd' => 'idcredito',

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

<div class="col-md-6 col-sm-6 form-group" id="limite_credito">
        <label class="control-label col-md-3 col-sm-3 col-xs-24">Limite de Crédito</label>
        <div class="col-md-9 col-sm-9 col-xs-24">
            <input type="text" name="limite_credito" value="<?php  if (isset($_POST['limite_credito'])) {
                echo htmlentities($_POST['limite_credito']);
            } else {
                echo htmlentities($rscli->fields['limite_credito']);
            }?>" placeholder="Limite de Credito" class="form-control"/>
        </div>
    </div>
	
	
<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='cliente.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
	
<?php } else { ?>
<h1 style="font-weight:bold; color:#00C02D;">Registro Exitoso!</h1>
<?php } ?>
	
<div class="clearfix"></div>
<br /><br />


<?php
$consulta = "
select *,
(select usuario from usuarios where clientes_lineas_log.registrado_por = usuarios.idusu) as registrado_por,
(select descripcion from tipo_credito where clientes_lineas_log.idcredito = tipo_credito.idcredito) as tipocredito
from clientes_lineas_log 
where 
idcliente = $id
order by idclientelinealog desc
limit 100
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
<br /><hr /><br />
<strong>Ultimos 100 Cambios:</strong><br />
<div class="table-responsive tablaconborde">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>


			<th align="center">Permite Credito</th>
			<th align="center">Tipo de Credito</th>
			<th align="center">Limite de Credito</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>


			<td align="center"><?php echo ($rs->fields['permite_acredito'] == 1) ? 'NO' : 'SI'; ?></td>
			<td align="center"><?php echo antixss($rs->fields['tipocredito']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['max_mensual']);  ?></td>
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
            

            
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
