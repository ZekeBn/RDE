 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "476";
require_once("includes/rsusuario.php");


if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-").'01';
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}
$whereadd = "";



if (isset($_GET['origen'])) {


    if (intval($_GET['origen']) > 0) {
        $origen = intval($_GET['origen']);
        $whereadd .= " and gest_transferencias.origen=$origen ";
    }
    if (intval($_GET['destino']) > 0) {
        $destino = intval($_GET['destino']);
        $whereadd .= " and gest_transferencias.destino=$destino ";
    }


    if (intval($_GET['idtanda']) > 0) {
        $idtanda = intval($_GET['idtanda']);
        $whereadd = " and gest_transferencias.idtanda = $idtanda ";
    }

    $buscar = "
    select 
    gest_transferencias.fecha_transferencia as fechahora,
    (select descripcion from gest_depositos where iddeposito=gest_transferencias.origen) as origen,
    gest_transferencias.idtanda,
    (select descripcion from gest_depositos where iddeposito=gest_transferencias.destino) as destino
    from  gest_transferencias 
    where 
    date(gest_transferencias.fecha_transferencia) >= '$desde' 
    and date(gest_transferencias.fecha_transferencia) <= '$hasta'
    $whereadd
    order by fechahora asc
    ";
    $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    /*
        $buscar="
        select iddeposito,gest_depositos_mov.fechahora as fechahora,tipomov,


        (select descripcion from gest_depositos where iddeposito=gest_depositos_mov.origen) as origen,
        gest_depositos_mov.idtanda,
        (select descripcion from gest_depositos where iddeposito=gest_depositos_mov.destino) as destino

        from  gest_depositos_mov
        where
        date(gest_depositos_mov.fechahora) >= '$desde'
        and date(gest_depositos_mov.fechahora) <= '$hasta'
        $whereadd
        order by fechahora asc
        ";
        $rsb=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));*/

}

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
<form id="form1" name="form1" method="get" action="">


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Origen *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT iddeposito, descripcion
FROM gest_depositos
where
estado = 1
order by descripcion asc
 ";

// valor seleccionado
if (isset($_GET['origen'])) {
    $value_selected = htmlentities($_GET['origen']);
} else {
    $value_selected = 0;
}

// parametros
$parametros_array = [
    'nombre_campo' => 'origen',
    'id_campo' => 'origen',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'iddeposito',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '0',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>    </div>
</div>



<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Destino *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT iddeposito, descripcion
FROM gest_depositos
where
estado = 1
order by descripcion asc
 ";

// valor seleccionado
if (isset($_GET['destino'])) {
    $value_selected = htmlentities($_GET['destino']);
} else {
    $value_selected = 0;
}

// parametros
$parametros_array = [
    'nombre_campo' => 'destino',
    'id_campo' => 'destino',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'iddeposito',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '0',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Desde *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="desde" id="desde" value="<?php   echo htmlentities($desde);   ?>" placeholder="Fecha transferencia" class="form-control" required />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Hasta *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="hasta" id="hasta" value="<?php  echo htmlentities($hasta);  ?>" placeholder="Fecha transferencia" class="form-control" required />                    
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    
    <label class="control-label col-md-3 col-sm-3 col-xs-12">
    Cod Tanda </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="idtanda" id="idtanda" value="<?php  echo htmlentities($_GET['idtanda']);  ?>" placeholder="Codigo de Tanda de Traslado (Opcional)" class="form-control"  />                    
    </div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Buscar</button>
        </div>
    </div>

<br />
</form>
<div class="clearfix"></div>
<br /><br />
<?php if (isset($_GET['origen'])) {?>
<hr />


<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr >
            <th  align="center" ></th>
          
            <th  align="center" ><strong>Tanda</strong></th>
            <th  align="center" ><strong>Fecha</strong></th>
            <th  align="center" ><strong>Origen</strong></th>
            <th  align="center" ><strong>Destino</strong></th>
        </tr>
        </thead>
        <tbody>
<?php
        $ant = "";
    while (!$rsb->EOF) {

        ?>

        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="traslado_stock_adm_edit.php?id=<?php echo $rsb->fields['idtanda']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                </div>

            </td>
            <td align="center"><?php echo $rsb->fields['idtanda'] ?></td>
          <td align="center"><?php echo date("d/m/Y", strtotime($rsb->fields['fechahora']))?></td>
            
            <td align="center"><?php echo $rsb->fields['origen'] ?></td>
            <td align="center"><?php echo ($rsb->fields['destino']) ?></td>
        </tr>
        <?php
                $ant = $rsb->fields['productoc'];
        $rsb->MoveNext();
    }?>
        <tr style="background-color:#CCC; font-weight:bold;">
          <td align="center">Totales</td>
             <td align="center"></td>
            <td align="center"></td>
            <td align="center"></td>
            <td align="center"></td>

        </tr>
      </tbody>
    </table>
</div>
<?php } ?>
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
