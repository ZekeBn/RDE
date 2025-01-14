 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "476";
require_once("includes/rsusuario.php");


$idtanda = intval($_GET['id']);

$buscar = "
select 
gest_transferencias.fecha_transferencia,
(select descripcion from gest_depositos where iddeposito=gest_transferencias.origen) as origen,
gest_transferencias.idtanda,
(select descripcion from gest_depositos where iddeposito=gest_transferencias.destino) as destino
from  gest_transferencias 
where 
gest_transferencias.idtanda = $idtanda
and estado <> 6
order by fecha_transferencia asc
";
$rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

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
    $fecha_transferencia = antisqlinyeccion($_POST['fecha_transferencia'], "text");





    if (trim($_POST['fecha_transferencia']) == '') {
        $valido = "N";
        $errores .= " - El campo fecha_transferencia no puede estar vacio.<br />";
    }


    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
        update gest_transferencias
        set
            fecha_transferencia=$fecha_transferencia
        where
            idtanda = $idtanda
            and estado <> 6
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
        update stock_movimientos 
        set
        fecha_comprobante=$fecha_transferencia
        where
        codrefer = $idtanda
        and (tipomov = 3 or  tipomov = 4)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        header("location: traslado_stock_adm.php?idtanda=".$idtanda);
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

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
                    <h2>Traslados de Stock administrar</h2>
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

<hr />


<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr >
            
          
            <th  align="center" ><strong>Tanda</strong></th>
            <th  align="center" ><strong>Fecha</strong></th>
            <th  align="center" ><strong>Origen</strong></th>
            <th  align="center" ><strong>Destino</strong></th>
        </tr>
        </thead>
        <tbody>
<?php
        $ant = "";
//while (!$rsb->EOF){

?>

        <tr>

            <td align="center"><?php echo $rsb->fields['idtanda'] ?></td>
          <td align="center"><?php echo date("d/m/Y", strtotime($rsb->fields['fecha_transferencia']))?></td>
            
            <td align="center"><?php echo $rsb->fields['origen'] ?></td>
            <td align="center"><?php echo ($rsb->fields['destino']) ?></td>
        </tr>
        <?php

        //$rsb->MoveNext();}?>
        <tr style="background-color:#CCC; font-weight:bold;">
          <td align="center">Totales</td>
        
            <td align="center"></td>
            <td align="center"></td>
            <td align="center"></td>

        </tr>
      </tbody>
    </table>
</div>

                      
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha transferencia *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="fecha_transferencia" id="fecha_transferencia" value="<?php  if (isset($_POST['fecha_transferencia'])) {
        echo htmlentities($_POST['fecha_transferencia']);
    } else {
        echo date("Y-m-d", strtotime($rsb->fields['fecha_transferencia']));
    }?>" placeholder="Fecha transferencia" class="form-control" required="required" />                    
    </div>
</div>


<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='traslado_stock_adm.php?idtanda=<?php echo $idtanda; ?>'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />
<br /><br /><br /><br /><br /><br />

                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->
        

<!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="cuadro_pop">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
              </button>
              <h4 class="modal-title" id="myModalLabel">Titulo</h4>
            </div>

            <div class="modal-body" id="modal_cuerpo">
                Cuerpo
            </div>

            <div class="modal-footer">
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
