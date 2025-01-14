<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "126";
require_once("includes/rsusuario.php");

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
    $permite_acredito = antisqlinyeccion($_POST['permite_credito'], "text");
    $linea_sobregiro = antisqlinyeccion($_POST['linea_sobregiro'], "float");
    $max_mensual = antisqlinyeccion($_POST['max_mensual'], "float");


    //validar
    $valido = "S";
    if (trim($_POST['permite_credito']) == '') {
        $errores .= "- Debe indicar si permite a credito.<br />";
        $valido = "N";
    }
    if (floatval($_POST['linea_sobregiro']) > 0 or floatval($_POST['max_mensual']) > 0) {
        if (trim($_POST['permite_acredito']) != 'S') {
            $errores .= "- Debe indicar que esta permitido a credito si asigno linea de sobregiro y/o maximo mensual.<br />";
            $valido = "N";
        }
    }
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
        if ($usa_maxmensual == 'S') {
            if ($maxmensualad > floatval($_POST['max_mensual'])) {
                $valido = "N";
                $errores .= " - La sumatoria de maximos mensuales de los adherentes supera el maximo permitido del titular.<br />";
            }
        }
    }

    if ($usa_maxmensual != 'S') {
        $max_mensual = "999999999999";
    }

    // actualiza
    if ($valido == 'S') {
        $consulta = "
		UPDATE cliente 
		SET 
			permite_credito=$permite_credito,
			max_mensual = $max_mensual,
			linea_sobregiro = $linea_sobregiro
		WHERE
			idcliente=$id
			and idempresa=$idempresa
			and estado <> 6
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // registra en el log
        $consulta = "
		INSERT INTO clientes_lineas_log
		(idcliente, permite_acredito, max_mensual, linea_sobregiro, registrado_por, registrado_el) 
		VALUES 
		($id,$permite_acredito, $max_mensual, $linea_sobregiro, $idusu, '$ahora')
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
	<?php require_once("includes/head_gen.php"); ?>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Permite Credito *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php

     // valor seleccionado
if (isset($_POST['permite_credito'])) {
    $value_selected = htmlentities($_POST['permite_credito']);
} else {
    $value_selected = $rscli->fields['permite_credito'];
}
    // opciones
    $opciones = [
        'SI' => '1',
        'NO' => '0'
    ];
    // parametros
    $parametros_array = [
        'nombre_campo' => 'permite_credito',
        'id_campo' => 'permite_credito',

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
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Limite de Credito *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="limite_credito" id="limite_credito" value="<?php
      if (isset($_POST['limite_credito'])) {
          echo antixss($_POST['limite_credito']);
      } else {
          echo intval($rscli->fields['limite_credito|']);
      }
    ?>" placeholder="App" class="form-control" required="required" />                    
	</div>
</div>
<?php if ($usa_maxmensual == 'S') {?>
	<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Maximo Mensual *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="max_mensual" id="max_mensual" value="<?php
    if (isset($_POST['max_mensual'])) {
        echo antixss($_POST['max_mensual']);
    } else {
        echo intval($rscli->fields['max_mensual']);
    }
    ?>" placeholder="App" class="form-control" required="required" />                    
	</div>
</div>
<?php } ?>
	
	
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
(select usuario from usuarios where clientes_lineas_log.registrado_por = usuarios.idusu) as registrado_por
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


			<th align="center">Permite acredito</th>
            <?php if ($usa_maxmensual == 'S') {?>
			<th align="center">Max mensual</th>
            <?php } ?>
			<th align="center">Linea sobregiro</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>


			<td align="center"><?php echo siono($rs->fields['permite_acredito']); ?></td>
             <?php if ($usa_maxmensual == 'S') {?>
			<td align="right"><?php echo formatomoneda($rs->fields['max_mensual']);  ?></td>
            <?php } ?>
			<td align="right"><?php echo formatomoneda($rs->fields['linea_sobregiro']);  ?></td>
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
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
