<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "255";
require_once("includes/rsusuario.php");

require_once("includes/funciones_stock.php");
require_once("includes/funciones_cobros.php");


$idnotacred = intval($_GET['id']);
if ($idnotacred == 0) {
    header("location: nota_credito_cli_anula.php");
    exit;
}

$consulta = "
select *,
(select usuario from usuarios where nota_credito_cabeza.registrado_por = usuarios.idusu) as registrado_por
from nota_credito_cabeza 
where 
 estado = 3
 and idnotacred = $idnotacred
order by idnotacred asc
limit 1
";
//echo $consulta;exit;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idnotacred = intval($rs->fields['idnotacred']);
$idcajamov = intval($rs->fields['idcajamov']);
$idcuentaclientepagcab = intval($rs->fields['idcuentaclientepagcab']);
$fecha_nota = $rs->fields['fecha_nota'];
if ($idnotacred == 0) {
    header("location: nota_credito_cli_anula.php");
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

    if ($idcajamov > 0) {
        $parametros_array_caja['idcajamov'] = $idcajamov;
        $parametros_array_caja['anulado_por'] = $idusu;
        $res = valida_anulacion_caja_mov($parametros_array_caja);
        if ($res['valido'] != 'S') {
            $errores .= $res['errores'];
            $valido = "N";
        }

    }



    // si todo es correcto actualiza
    if ($valido == "S") {




        // anula en gest_pagos
        $consulta = "
		select idcaja from gest_pagos where idnotacred = $idnotacred limit 1
		";
        $rspag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcaja = $rspag->fields['idcaja'];

        if (intval($idcaja) > 0) {
            $consulta = "
			update gest_pagos set estado = 6 where idnotacred = $idnotacred
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            //recalcula caja
            recalcular_caja($idcaja);

        }
        //anula caja gestion idcajamov
        if ($idcajamov > 0) {
            anula_caja_mov($parametros_array_caja);
        }

        // anula pago en cuentas_clientes_pagos_cab
        $consulta = "
		update cuentas_clientes_pagos_cab
		set
			estado = 6,
			anulado_por = $idusu,
			anulado_el = '$ahora'
		where
			idcuentaclientepagcab = $idcuentaclientepagcab
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // anula los pagos de las facturas
        $consulta = "
		update cuentas_clientes_pagos
		set
			estado = 6,
			anulado_por = $idusu,
			anulado_el = '$ahora'
		where
			idcuentaclientepagcab = $idcuentaclientepagcab
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		select * from cuentas_clientes_pagos where estado = 6 and idcuentaclientepagcab = $idcuentaclientepagcab
		";
        $rscta = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $datos_ar = [];
        while (!$rscta->EOF) {
            $idcta = $rscta->fields['idcuenta'];
            $datos_ar = ['idcta' => $idcta];
            // actualiza los saldos de las cuentas
            actualiza_cuentacliente($datos_ar);
            $rscta->MoveNext();
        }



        // mueve stock anulado vuelve a agregar
        $consulta = "
		select *, nota_credito_cuerpo.cantidad, nota_credito_cuerpo.codproducto,
		(
			select idinsumo 
			from productos 
			inner join insumos_lista on insumos_lista.idproducto = productos.idprod_serial
			where 
			idprod_serial = nota_credito_cuerpo.codproducto
		) as idinsumo,
		(
		select 
		sum(subtotal_costo)/sum(cantidad) as pcosto
		from ventas_detalles
		where 
		idventa = nota_credito_cuerpo.idventa
		and idprod = nota_credito_cuerpo.codproducto
		) as pcosto
		from nota_credito_cuerpo 
		where 
		idnotacred = $idnotacred
		and nota_credito_cuerpo.codproducto > 0
		";
        $rscuerpo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        while (!$rscuerpo->EOF) {
            $iddeposito = $rscuerpo->fields['iddeposito'];
            $idinsumo = $rscuerpo->fields['idinsumo'];
            $cantidad = $rscuerpo->fields['cantidad'];
            $pcosto = $rscuerpo->fields['pcosto'];
            $idproducto = $rscuerpo->fields['codproducto'];
            $subtotal = $rscuerpo->fields['subtotal'];
            $idventa = $rscuerpo->fields['idventa'];
            $ruc1 = $rscuerpo->fields['idventa'];

            // mover en base a venta_receta

            // mueve stock en los que aplica (temporal, cambiar a venta receta)
            if ($iddeposito > 0) {
                if ($idinsumo > 0) {
                    descontar_stock_general($idinsumo, $cantidad, $iddeposito);
                    // aumenta stock costo
                    descontar_stock($idinsumo, $cantidad, $iddeposito);
                    // registra el aumento // codrefer es idnotacredito y fechacomprobante es fecha nota de credito
                    movimientos_stock($idinsumo, $cantidad, $iddeposito, 17, '-', $idnotacred, $fecha_nota); // 13 nota de credito cliente



                }
            }



            $rscuerpo->MoveNext();
        }



        // anula nota de credito
        $consulta = "
		update nota_credito_cabeza
		set
			estado = 6,
			anulado_por = $idusu,
			anulado_el = '$ahora'
		where
			idnotacred = $idnotacred
			and estado = 3
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: nota_credito_cli_anula.php");
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
                    <h2>Anular nota credito cliente</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">




<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">Idnotacred</th>
			<th align="center">Fecha nota</th>
			<th align="center">Numero</th>
			<th align="center">Cliente</th>
			<th align="center">Razon social</th>
			<th align="center">Ruc</th>

		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>

			<td align="center"><?php echo antixss($rs->fields['idnotacred']); ?></td>

			<td align="center"><?php if ($rs->fields['fecha_nota'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_nota']));
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['numero']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idcliente']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>

		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<hr />
<form id="form1" name="form1" method="post" action="">



<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
	   <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Anular</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='nota_credito_cli_anula.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>


  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br />



<br /><br /><br />
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
