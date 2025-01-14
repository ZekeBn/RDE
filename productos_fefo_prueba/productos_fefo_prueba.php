<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_stock_fefo.php");
// nombre del modulo al que pertenece este archivo
$modulo = "42";
$submodulo = "578";

$dirsup = "S";
require_once("../includes/rsusuario.php");


//Traemos las preferencias para la empresa
$buscar = "Select * from preferencias where idempresa=$idempresa ";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Para obligar voucher / numero o cualquier cosa
$obliga_adicional = trim($rspref->fields['obliga_adicional']);
$texto_adicional = trim($rspref->fields['txt_adicional']);
$factura_obliga = trim($rspref->fields['factura_obliga']);


// preferencias caja
$consulta = "
SELECT 
usa_canalventa, usa_clienteprevio, avisar_quedanfac, usa_ventarapida, usa_ventarapidacred 
FROM preferencias_caja 
limit 1
";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$avisar_quedanfac = trim($rsprefcaj->fields['avisar_quedanfac']);
$usa_canalventa = trim($rsprefcaj->fields['usa_canalventa']);
$usa_clienteprevio = trim($rsprefcaj->fields['usa_clienteprevio']);
$usa_ventarapidacred = trim($rsprefcaj->fields['usa_ventarapidacred']);
$usa_ventarapida = trim($rsprefcaj->fields['usa_ventarapida']);

if ($usa_ventarapida == 'S') {

    $tkvr = 2; // ticket
    if ($factura_obliga == 'S') {
        $tkvr = 1; // factura
        $ano = date("Y");
        // busca si existe algun registro
        $buscar = "
		Select idsuc, numfac as mayor 
		from lastcomprobantes 
		where 
		idsuc=$factura_suc 
		and pe=$factura_pexp 
		and idempresa=$idempresa 
		order by ano desc 
		limit 1";
        $rsfactura = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        //$maxnfac=intval(($rsfactura->fields['mayor'])+1);
        // si no existe inserta
        if (intval($rsfactura->fields['idsuc']) == 0) {
            $consulta = "
			INSERT INTO lastcomprobantes
			(idsuc, factura, numfac, recibo, numrec, tickete, numtk, ano, pe, numcheque, secuencia_cancela, 
			numhoja, hojalevante, idempresa) 
			VALUES
			($factura_suc, 0, 0, NULL, 0, NULL, 0, $ano, $factura_pexp, NULL, 
			NULL, 0, '', $idempresa)
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }
        $ultfac = intval($rsfactura->fields['mayor']);
        if ($ultfac == 0) {
            $maxnfac = 1;
        } else {
            $maxnfac = intval(($rsfactura->fields['mayor']) + 1);
        }
        $parte1 = intval($factura_suc);
        $parte2 = intval($factura_pexp);
        if ($parte1 == 0 or $parte2 == 0) {
            $parte1f = '001';
            $parte2f = '001';
        } else {
            $parte1f = agregacero($parte1, 3);
            $parte2f = agregacero($parte2, 3);
        }

    } else {
        $parte1f = "''";
        $parte2f = "''";
        $maxnfac = "''";
    }
}






$consulta = "
select idcliente from cliente where borrable = 'N' limit 1
";
$rsclidef = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idclientedef = intval($rsclidef->fields['idcliente']);

$idclienteprevio = intval($_SESSION['idclienteprevio']);
if ($idclienteprevio > 0) {
    $consulta = "
	select idcliente, idvendedor, idcanalventacli
	from cliente 
	where 
	idcliente = $idclienteprevio
	and estado <> 6 
	limit 1
	";
    $rscprev = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idclienteprevio = $rscprev->fields['idcliente'];
    if ($idclienteprevio > 0) {
        $idcliente = $idclienteprevio;
        $idvendedor = intval($rscprev->fields['idvendedor']);
        $idcanalventa = intval($rscprev->fields['idcanalventacli']);
    }
}

$idlistaprecio = intval($_SESSION['idlistaprecio']);
$idcanalventa = intval($_SESSION['idcanalventa']);
$idclienteprevio = intval($_SESSION['idclienteprevio']);
if ($idclienteprevio > 0) {
    $consulta = "
	select idcliente, idvendedor, idcanalventacli
	from cliente 
	where 
	idcliente = $idclienteprevio
	and estado <> 6 
	limit 1
	";
    $rscprev = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idclienteprevio = $rscprev->fields['idcliente'];
    if ($idclienteprevio > 0) {
        $idcliente = $idclienteprevio;
        $idvendedor = intval($rscprev->fields['idvendedor']);
        $idcanalventa = intval($rscprev->fields['idcanalventacli']);
    }
}
if ($idcanalventa > 0) {
    $consulta = "
	select idlistaprecio, idcanalventa, canal_venta 
	from canal_venta 
	where 
	idcanalventa = $idcanalventa 
	and estado = 1
	limit 1
	";
    $rscv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idlistaprecio = intval($rscv->fields['idlistaprecio']);
}
//echo $idlistaprecio;


$vencimiento = '2023-09-20';
$usar_lote = "S";
$mini = "o";
$parametros_array = [
    "vencimiento" => $vencimiento,
    "joinadd_lp" => $joinadd_lp,
    "mini" => $mini,
    "whereadd_lp" => $whereadd_lp,
    "order_vencimiento" => $order_vencimiento,
    "mini" => $mini,
    "usar_lote" => $usar_lote,
    "idlistaprecio" => $idlistaprecio
];
$rs = consultar_stock_fefo_fecha($parametros_array);
// echo($rs);exit;
// var_dump($rs->fields);exit;
////////////////////////////////////////////
////////////////////////////////////////////
////descontar stock detalles y costo_productos
$parametros_array = [
    "idinsumo" => 1,
    "cantidad" => 39,
    "iddeposito" => 1,
    "vencimiento" => $vencimiento,
    "lote" => 6,
];

// descontar_stock($parametros_array);
/////////////////////////////////////////////
/////////////////////////////////////////////
$parametros_array = [
    "idinsumo_aumentar" => 1,
    "cantidad_aumentar" => 11,
    "costo_unitario" => 5000,
    "iddeposito" => 1,
    "vencimiento" => "'2023-09-20'",
    "lote" => 6,
];

// aumentar_stock($parametros_array);



//////////////////////////////////////

$consulta = "SELECT
	 tmp_ventares.*, productos.descripcion, sum(cantidad) as total, sum(precio) as totalprecio, sum(subtotal) as subtotal,
	(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
	(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado,
	tmp_ventares.idtipoproducto, tmp_ventares.idprod_mitad2, tmp_ventares.idprod_mitad1,
	(select muestra_grupo_combo from productos WHERE productos.idprod_serial = tmp_ventares.idproducto limit 1) as muestra_grupo_combo
	from tmp_ventares 
	inner join productos on tmp_ventares.idproducto = productos.idprod_serial
	where 
	registrado = 'N'
	and tmp_ventares.usuario = $idusu
	and tmp_ventares.borrado = 'N'
	and tmp_ventares.finalizado = 'N'
	and tmp_ventares.idsucursal = $idsucursal
	and tmp_ventares.idempresa = $idempresa
	and tmp_ventares.idtipoproducto not in (2,3,4)

	and (
		select tmp_ventares_agregado.idventatmp 
		from tmp_ventares_agregado 
		WHERE 
		tmp_ventares_agregado.idventatmp = tmp_ventares.idventatmp
		limit 1
	) is null
	and (
		select tmp_ventares_sacado.idventatmp 
		from tmp_ventares_sacado 
		WHERE 
		tmp_ventares_sacado.idventatmp = tmp_ventares.idventatmp
		limit 1
	) is null
	and tmp_ventares.observacion is null
	and tmp_ventares.desconsolida_forzar is null

	group by descripcion, receta_cambiada
";
$rs2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$cc = 0;
?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
	<script>

		window.onload = function() {

			// agregon(2,1,5000,11,'2023-11-20');
		}
		function agregon(producto,insumo,precio,cantidad,vencimiento){
	

				//document.getElementById('cv_'+posicion).hidden='hidden';
				//var cantidad=document.getElementById('cvender_'+posicion).value;

				var prod1='';
				var prod2='';
				
				if (cantidad==''){
					cantidad=1;

				}
				var parametros = {
						"prod" : producto,
						"insu" : insumo,
						"cant" : cantidad,
						"precio" : precio,
						"prod_1" : prod1,
						"prod_2" : prod2,
						"vencimiento" : vencimiento
				};
				console.log((parametros));
				$.ajax({
							data:  parametros,
							url:   'carrito.php',
							type:  'post',
							beforeSend: function () {
								
							},
							success:  function (response) {
								// $("#cv_"+posicion).show();
								// $("#carrito").html(response);
								console.log(response);

								// var stock = parseInt($("#hidden_stock_cantidad_"+producto).html());
								// var vende_sin_stock = $("#hidden_stock_cantidad_"+producto).attr('data-hidden-value');
								// var valor = stock - response;
								// if (valor <= 0){
								// 	if(vende_sin_stock =="false"){

								// 		$("#cv_" + posicion).addClass("hide");
								// 	}
								// 	// $("#stock_cantidad_"+id).css("display", "none");
								// 	$("#stock_cantidad_"+producto).html(stock - response);
								// }else{

								// 	$("#stock_cantidad_"+producto).html(stock - response);
								// }
								// actualiza_carrito();
							}
					});




		}

		function venta_rapida(){
			var htmlcarr = $("#carrito").html();
			var parametros = {
				"pedido"         : '',
				"idzona"         : '', // zona costo delivery
				"idadherente"    : '',
				"idservcom"      : '', // servicio comida
				"banco"          : '',
				"adicional"      : '', // numero de cheque, tarjeta, etc
				"condventa"      : 1, // credito o contado
				"mediopago"      : 1, // forma de pago
				"fac_suc"        : <?php echo $parte1f ?>,
				"fac_pexp"       : <?php echo $parte2f ?>,
				"fac_nro"        : <?php echo $maxnfac ?>,
				"domicilio"      : '',
				"llevapos"       : 'N',
				"cambiode"       : '',
				"observadelivery": '',
				"observacion"    : '',
				"mesa"           : 0,
				"canal"          : 2, // delivery, carry out, mesa, caja
				"fin"            : 3,
				"idcliente"      : <?php echo  $idclientedef ?>,// cocinado si  borrado no finalizado si  venta track id seguir generando  para la venta
				"monto_recibido" : 0,
				"descuento"      : 0,
				"motivo_descuento": '',
				"chapa"          : '',
				"montocheque"    : 0,
				"idvendedor"     : '',
				"iddeposito"     : '',
				"idmotorista"    : '',
				"idcanalventa"   : '',
				"fecha_venta"    : '',
				"json"           : 'S',
				"codigo_vrapida" : ''
			};
				
			$.ajax({
			data:  parametros,
			url:   'registrar_venta.php',
			type:  'post',
				beforeSend: function () {
						$("#carrito").html("<br /><br />Registrando...<br /><br />");
				},
				success:  function (response) {
					//$("#carrito").html(response);
						
					if(IsJsonString(response)){
						var obj = jQuery.parseJSON(response);
						//borra_carrito();
						<?php $script = "script_central_impresion.php";?>
						//alert(obj.error);
						if(obj.error == ''){
								document.body.innerHTML='<meta http-equiv="refresh" content="0; url=<?php echo $script?>?tk=<?php echo $tkvr; ?>&clase=1&v='+obj.idventa+'<?php echo $redirbus2; ?>">';
						}else{
							/*alertar_redir('NO SE REGISTRO LA VENTA',obj.error,'error','ACEPTAR','gest_ventas_resto_caja.php');*/
							alertar('NO SE REGISTRO LA VENTA',obj.error,'error','ACEPTAR');
							$("#carrito").html(htmlcarr);

						}
					}else{
						alert(response);
					}
				}
			});

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
                    <h2>Prueba fefo</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	

   
<p><a href="aduana_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">idproducto</th>
			<th align="center">Descripcion</th>
			<th align="center">cantidad</th>
			
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="aduana_det.php?id=<?php echo $rs->fields['idaduana']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<a href="aduana_edit.php?id=<?php echo $rs->fields['idaduana']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="aduana_del.php?id=<?php echo $rs->fields['idaduana']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idprod_serial']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
			<td align="center"><?php echo($rs->fields['disponible']); ?></td>

		</tr>
<?php

        $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
	  
    </table>
</div>
<br />

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th height="29"><strong>Producto</strong></th>
			<th align="center"><strong>Cant.</strong></th>
			<th align="center"><strong>lote</strong></th>
			<th align="center"><strong>vto.</strong></th>
			<th align="center"><strong>Total</strong></th>
			<th width="50" align="center">&nbsp;</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs2->EOF) {
    $cc = $cc + 1;
    $total = $rs2->fields['subtotal'];
    $totalacum += $total;
    $cantacum += $rs2->fields['total'];
    $des = str_replace("'", "", $rs2->fields['descripcion']);
    $muestra_grupo_combo = $rs2->fields['muestra_grupo_combo'];

    // 1 producto 2 combo 3 combinado 4 combinado extendido
    $idtipoproducto = $rs2->fields['idtipoproducto'];
    $idprod_mitad1 = $rs2->fields['idprod_mitad1'];
    $idprod_mitad2 = $rs2->fields['idprod_mitad2'];
    $idventatmp = $rs2->fields['idventatmp'];
    if ($idtipoproducto == 3) {
        $consulta = "
		select * 
		from productos 
		where 
		(idprod_serial = $idprod_mitad1 or idprod_serial = $idprod_mitad2)
		limit 2
		";
        $rsmit = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }
    // combinado extendido
    if ($idtipoproducto == 4) {
        $consulta = "
		select * 
		from productos 
		inner join tmp_combinado_listas on tmp_combinado_listas.idproducto_partes = productos.idprod_serial
		where 
		tmp_combinado_listas.idventatmp = $idventatmp
		limit 20
		";
        $rsmit = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }
    // combo
    if ($idtipoproducto == 2) {
        if ($muestra_grupo_combo == 'S') {
            $consulta = "
			select combos_listas.nombre, productos.descripcion, count(*) as total
			from productos 
			inner join tmp_combos_listas on tmp_combos_listas.idproducto = productos.idprod_serial
			inner join combos_listas on combos_listas.idlistacombo = tmp_combos_listas.idlistacombo
			where 
			tmp_combos_listas.idventatmp = $idventatmp
			group by combos_listas.nombre, productos.descripcion
			order by combos_listas.idlistacombo asc
			limit 20
			";
            $rsmit = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        } else {
            $consulta = "
			select productos.descripcion , count(*) as total
			from productos 
			inner join tmp_combos_listas on tmp_combos_listas.idproducto = productos.idprod_serial
			where 
			tmp_combos_listas.idventatmp = $idventatmp
			group by productos.descripcion
			limit 20
			";
            $rsmit = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }

    }

    ?>
		<tr id="tr_<?php echo $cc;?>" <?php if ($idtipoproducto == 5) { ?> style="display:none;"<?php } ?>>
      <td height="30"  id="td_<?php echo $cc;?>"><?php

     // echo $idtipoproducto;
      if ($idtipoproducto == 5) {
          echo "&nbsp;&nbsp;(+) ";
      }

    echo Capitalizar($rs2->fields['descripcion']); ?><?php

    // combinado y combinado extendido
    if ($idtipoproducto == 3 or $idtipoproducto == 4) {
        $i = 0;
        while (!$rsmit->EOF) {
            $i++;
            echo "<br />&nbsp;&nbsp;> Parte $i: ".Capitalizar($rsmit->fields['descripcion']);
            $rsmit->MoveNext();
        }

    }
    // combo
    if ($idtipoproducto == 2) {
        $i = 0;
        while (!$rsmit->EOF) {
            $total = $rsmit->fields['total'];
            $i++;

            if ($muestra_grupo_combo == 'S') {
                $nombre_grupo = trim($rsmit->fields['nombre']).": ";
            }
            echo "<br />&nbsp;&nbsp;> ".$nombre_grupo.$total." x ".Capitalizar($rsmit->fields['descripcion']);
            $rsmit->MoveNext();
        }

    }

    ?><input type="hidden" name="onp_<?php echo $cc;?>" id="onp_<?php echo $cc;?>"  value="<?php echo $rs2->fields['idproducto']; ?>"/><?php
if (trim($rs2->fields['observacion']) != '') {
    echo "<br />&nbsp;&nbsp;<strong>* OBS:</strong> ".antixss($rs2->fields['observacion']);
}
    ?></td>
      <td align="center" style="cursor:pointer;" onclick="edita_cant(<?php echo $rs2->fields['idproducto']; ?>);"><?php echo formatomoneda($rs2->fields['total'], 3, 'N'); ?></td>
	  <td align="center"><?php echo antixss($rs2->fields['lote']); ?></td>
      <td align="center"><?php echo antixss(date("d/m/Y", strtotime($rs2->fields['vencimiento']))); ?></td> 
	  <td align="center"><?php echo formatomoneda($rs2->fields['subtotal'], 0, 'N'); ?></td>
      <td align="center">
      
				<div class="btn-group">


					<a href="javascript:void(0);" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar" onClick="borrar('<?php echo $rs2->fields['idproducto']; ?>','<?php echo Capitalizar($des); ?>');"><span class="fa fa-trash-o"></span></a>
				</div>
      </td>
    </tr>
<?php

            $rs2->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
	  
    </table>
</div>
<br />


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
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
