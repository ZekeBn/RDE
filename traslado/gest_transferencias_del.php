<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "222";
require_once("includes/rsusuario.php");

// funciones para stock
require_once("includes/funciones_stock.php");

$idtanda = intval($_GET['id']);

$consulta = "
select * 
from usuarios 
where 
idusu = $idusu
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$editar_traslado = $rs->fields['editar_traslado'];




/*$consulta="
select gest_depositos_mov.idproducto, gest_depositos_mov.cantidad, gest_depositos_mov.recibio_destino,
(select descripcion from insumos_lista where idinsumo  = gest_depositos_mov.idproducto) as producto,
gest_transferencias.idtanda, gest_transferencias.origen, gest_transferencias.fecha_transferencia
from gest_depositos_mov
inner join gest_transferencias on gest_transferencias.idtanda = gest_depositos_mov.idtanda
where
gest_transferencias.estado = 3
and gest_transferencias.idtanda=$idtanda
and gest_depositos_mov.estado = 1
and gest_depositos_mov.recibio_destino = 'N'
order by (select descripcion from insumos_lista where idinsumo  = gest_depositos_mov.idproducto) asc
";
//echo $consulta;exit;
$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));*/
$consulta = "
select *
from gest_transferencias 
where 
(gest_transferencias.estado = 1 or gest_transferencias.estado = 3 )
and gest_transferencias.estado <> 2
and gest_transferencias.estado <> 6
and gest_transferencias.idtanda=$idtanda
";
//echo $consulta;exit;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtanda = intval($rs->fields['idtanda']);
$idpedidorepo = intval($rs->fields['idpedidorepo']);
$estado_transfer = intval($rs->fields['estado']);
if ($idtanda == 0) {
    header("location: gest_transferencias.php");
    exit;
}
// valida deposito de transito
$consulta = "
select * from gest_depositos where tiposala = 3 order by iddeposito asc limit 1
";
$rstran = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito_transito = intval($rstran->fields['iddeposito']);
if ($iddeposito_transito == 0) {
    echo "Deposito de transito inexistente.";
    exit;
}




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

    // si no tiene permiso para editar
    if ($editar_traslado != 'S') {
        if ($estado_transfer != 1) { // 1 activo 2 finalizado 3 transito
            $valido = "N";
            $errores .= "- Tu usuario no tiene permiso para anular una transferencia finalizada.<br />";
        }
    }


    // si todo es correcto actualiza
    if ($valido == "S") {
        $consulta = "
		select gest_depositos_mov.idproducto, gest_depositos_mov.cantidad, gest_depositos_mov.recibio_destino,
		(select descripcion from insumos_lista where idinsumo  = gest_depositos_mov.idproducto) as producto, 
		gest_transferencias.idtanda, gest_transferencias.origen, gest_transferencias.fecha_transferencia
		from gest_depositos_mov 
		inner join gest_transferencias on gest_transferencias.idtanda = gest_depositos_mov.idtanda
		where 
		gest_transferencias.estado = 3 
		and gest_transferencias.idtanda=$idtanda
		and gest_depositos_mov.estado = 1
		and gest_depositos_mov.recibio_destino = 'N'
		order by (select descripcion from insumos_lista where idinsumo  = gest_depositos_mov.idproducto) asc
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // recorre y retorna al deposito
        while (!$rs->EOF) {


            $iddeposito_origen = intval($rs->fields['origen']);
            $fecha_transferencia = $rs->fields['fecha_transferencia'];
            $idinsumo_traslado = intval($rs->fields['idproducto']);
            $cantidad_traslado = floatval($rs->fields['cantidad']);
            $cantidad_old = $cantidad_traslado;

            // descontar insumo de stock general transito
            $consulta = "
			UPDATE gest_depositos_stock_gral 
			SET 
			disponible=(disponible-$cantidad_traslado)
			WHERE 
			idempresa=$idempresa 
			and iddeposito=$iddeposito_transito
			and idproducto=$idinsumo_traslado
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            movimientos_stock($idinsumo_traslado, $cantidad_traslado, $iddeposito_transito, 3, '-', $idtanda, $fecha_transferencia);

            // aumentar insumo de stock general origen
            $consulta = "
			UPDATE gest_depositos_stock_gral 
			SET 
			disponible=(disponible+$cantidad_traslado)
			WHERE 
			idempresa=$idempresa 
			and iddeposito=$iddeposito_origen
			and idproducto=$idinsumo_traslado
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            movimientos_stock($idinsumo_traslado, $cantidad_traslado, $iddeposito_origen, 4, '+', $idtanda, $fecha_transferencia);

            // trasladamos con costos y depositos especificos
            traslada_stock($idinsumo_traslado, $cantidad_traslado, $iddeposito_transito, $iddeposito_origen);

            $rs->MoveNext();
        } $rs->MoveFirst();


        // si hay pedido de reposicion marcamos como enviado
        if ($idpedidorepo > 0) {

            $consulta = "
			update compras_pedidos
			set
				estado = 2
			where
				idpedido = $idpedidorepo
				and estado = 3
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        }


        $consulta = "
		update gest_transferencias
		set
			estado = 6
		where
			idtanda = $idtanda
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: gest_transferencias.php");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());


$consulta = "
select gest_depositos_mov.idproducto, gest_depositos_mov.cantidad, gest_depositos_mov.recibio_destino,
(select descripcion from insumos_lista where idinsumo  = gest_depositos_mov.idproducto) as producto, 
gest_transferencias.idtanda, gest_transferencias.origen, gest_transferencias.fecha_transferencia
from gest_depositos_mov 
inner join gest_transferencias on gest_transferencias.idtanda = gest_depositos_mov.idtanda
where 
gest_transferencias.estado = 3 
and gest_transferencias.idtanda=$idtanda
and gest_depositos_mov.estado = 1
and gest_depositos_mov.recibio_destino = 'N'
order by (select descripcion from insumos_lista where idinsumo  = gest_depositos_mov.idproducto) asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

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
              <!--<div class="title_left">
                <h3>Plain Page</h3>
              </div>-->

              <!--<div class="title_right">
                <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                  <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search for...">
                    <span class="input-group-btn">
                      <button class="btn btn-default" type="button">Go!</button>
                    </span>
                  </div>
                </div>
              </div>-->
            </div>

            <div class="clearfix"></div>
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Borrar Transferencia en Transito</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                      <!--<li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                        <ul class="dropdown-menu" role="menu">
                          <li><a href="#">Settings 1</a>
                          </li>
                          <li><a href="#">Settings 2</a>
                          </li>
                        </ul>
                      </li>
                      <li><a class="close-link"><i class="fa fa-close"></i></a>
                      </li>-->
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">



<p><a href="gest_transferencias.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<strong>Esta accion no se puede Deshacer, esta seguro?</strong><br />
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idtanda *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="" name="idtanda" id="idtanda" value="<?php  if (isset($_POST['idtanda'])) {
	    echo htmlentities($_POST['idtanda']);
	} else {
	    echo htmlentities($rs->fields['idtanda']);
	}?>" placeholder="Idtanda" class="form-control" required readonly disabled="disabled" />                    
	</div>
</div>


<div class="clearfix"></div>
<br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Idproducto</th>
            <th align="center">Producto</th>
            <th align="center">Cantidad</th>
			<th align="center">Recibio destino</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>

            <td align="center"><?php echo antixss($rs->fields['idproducto']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['producto']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['cantidad']);  ?></td>
			
			<td align="center"><?php echo antixss($rs->fields['recibio_destino']); ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
	   <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_transferencias.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />

</form>
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
