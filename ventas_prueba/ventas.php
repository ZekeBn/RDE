<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// echo "hola";exit;
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";

$dirsup = "S";
require_once("../includes/rsusuario.php");


$pagina_actual = $_SERVER['REQUEST_URI'];
$urlParts = parse_url($pagina_actual);



// Verificar si hay parámetros GET
if (isset($urlParts['query'])) {
    // Convertir los parámetros GET en un arreglo asociativo
    parse_str($urlParts['query'], $queryParams);

    // Eliminar el parámetro 'pag' (si existe)
    unset($queryParams['pag']);
    // Reconstruir los parámetros GET sin 'pag'
    $newQuery = http_build_query($queryParams);
    // Reconstruir la URL completa
    if (isset($newQuery) == false || empty($newQuery)) {
        $newUrl = $urlParts['path'].'?' ;
    } else {
        $newUrl = $urlParts['path'] . '?' . $newQuery .'&';
    }

    $pagina_actual = $newUrl;
} else {
    $pagina_actual = $urlParts['path'].'?' ;
}


// paginado del index

$limit = "";
$consulta_numero_filas = "
select 
count(*) as filas from   ventas
";
$rs_filas = $conexion->Execute($consulta_numero_filas) or die(errorpg($conexion, $consulta_numero_filas));
$num_filas = $rs_filas->fields['filas'];
$filas_por_pagina = 20;
$paginas_num_max = ceil($num_filas / $filas_por_pagina);

$limit = "  LIMIT $filas_por_pagina";


$num_pag = intval($_GET['pag']);
$offset = null;
if (($_GET['pag']) > 0) {
    $numero = (intval($_GET['pag']) - 1) * $filas_por_pagina;
    $offset = " offset $numero";
} else {
    $offset = " ";
    $num_pag = 1;
}
////////////////////////////////


$consulta = "
select *
from ventas 
where 
 estado = 1 
order by idventa asc
$limit $offset
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
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
                    <h2>Datos Plantilla</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                  <p><a href="ventas_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a></p>
                  <input type="date" class="desde"  name="desde">
                  <input type="date" class="hasta" name="hasta">
                  <p><a href="#" class="btn btn-sm btn-default btn-generar-grafico"><span class="fa fa-pie_chart"></span></a></p>
                  
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idventa</th>
			<th align="center">Fecha</th>
			<th align="center">Idatc</th>
			<th align="center">Fechasola</th>
			<th align="center">Idcaja</th>
			<th align="center">Idcliente</th>
			<th align="center">Ruc</th>
			<th align="center">Ruchacienda</th>
			<th align="center">Dv</th>
			<th align="center">Carnet diplomatico</th>
			<th align="center">Razon social</th>
			<th align="center">Factura</th>
			<th align="center">Idclientedel</th>
			<th align="center">Iddomicilio</th>
			<th align="center">Total venta</th>
			<th align="center">Idmesa</th>
			<th align="center">Tipo venta</th>
			<th align="center">Idempresa</th>
			<th align="center">Sucursal</th>
			<th align="center">Idtransaccion</th>
			<th align="center">Trackid</th>
			<th align="center">Idsalida</th>
			<th align="center">Registrado por</th>
			<th align="center">Segurogs</th>
			<th align="center">Otrosgs</th>
			<th align="center">Hojalevante</th>
			<th align="center">Numhoja</th>
			<th align="center">Totaliva10</th>
			<th align="center">Totaliva5</th>
			<th align="center">Texe</th>
			<th align="center">Idpedido</th>
			<th align="center">Recibo</th>
			<th align="center">Descporc</th>
			<th align="center">Descneto</th>
			<th align="center">Total cobrado</th>
			<th align="center">Monto iva</th>
			<th align="center">Formapago</th>
			<th align="center">Estado</th>
			<th align="center">Deliv</th>
			<th align="center">Totalcobrar</th>
			<th align="center">Costo total ven</th>
			<th align="center">Anulado por</th>
			<th align="center">Anulado el</th>
			<th align="center">Moneda</th>
			<th align="center">Tipoimpresion</th>
			<th align="center">Vendedor</th>
			<th align="center">Obs</th>
			<th align="center">Idcanal</th>
			<th align="center">Idzona</th>
			<th align="center">Operador pedido</th>
			<th align="center">Recibido</th>
			<th align="center">Vuelto</th>
			<th align="center">Idadherente</th>
			<th align="center">Idserviciocom</th>
			<th align="center">Idmozo</th>
			<th align="center">Iddelivery</th>
			<th align="center">Detalle agrupado</th>
			<th align="center">Idtandatimbrado</th>
			<th align="center">Timbrado</th>
			<th align="center">Factura sucursal</th>
			<th align="center">Factura puntoexpedicion</th>
			<th align="center">Impreso</th>
			<th align="center">Diplomatico</th>
			<th align="center">Finalizo correcto</th>
			<th align="center">Codtran</th>
			<th align="center">Idtrans</th>
			<th align="center">Texto opcional venta</th>
			<th align="center">Year f</th>
			<th align="center">Month f</th>
			<th align="center">Week f</th>
			<th align="center">Weekday f</th>
			<th align="center">Hour f</th>
			<th align="center">Iddeposito</th>
			<th align="center">Venta registrada el</th>
			<th align="center">Excluye repven</th>
			<th align="center">Idwebpedido</th>
			<th align="center">Idventaprinc</th>
			<th align="center">Idmotorista</th>
			<th align="center">Idcanalventa</th>
			<th align="center">Codpedido externo</th>
			<th align="center">Idsucursal clie</th>
			<th align="center">Idapp</th>
			<th align="center">Idsalon asig</th>
			<th align="center">Factura vto</th>
			<th align="center">Ocnumero</th>
			<th align="center">Obs varios</th>
			<th align="center">Idtipotran</th>
			<th align="center">Id indicador presencia</th>
			<th align="center">Cdc</th>
			<th align="center">Idtipotranset</th>
			<th align="center">Idtipooperacionset</th>
			<th align="center">Electronica ok</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
				<!-- 	<a href="ventas_det.php?id=<?php echo $rs->fields['idventa']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<a href="ventas_edit.php?id=<?php echo $rs->fields['idventa']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="ventas_del.php?id=<?php echo $rs->fields['idventa']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a> -->
          
        </div>

			</td>
			<td align="center"><?php echo antixss($rs->fields['idventa']); ?></td>
			<td align="center"><?php if ($rs->fields['fecha'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha']));
			}  ?></td>
			<td align="center"><?php echo intval($rs->fields['idatc']); ?></td>
			<td align="center"><?php if ($rs->fields['fechasola'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fechasola']));
			} ?></td>
			<td align="center"><?php echo intval($rs->fields['idcaja']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idcliente']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['ruchacienda']); ?></td>
			<td align="center"><?php echo intval($rs->fields['dv']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['carnet_diplomatico']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['factura']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idclientedel']); ?></td>
			<td align="center"><?php echo intval($rs->fields['iddomicilio']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['total_venta']);  ?></td>
			<td align="center"><?php echo intval($rs->fields['idmesa']); ?></td>
			<td align="center"><?php echo intval($rs->fields['tipo_venta']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idempresa']); ?></td>
			<td align="center"><?php echo intval($rs->fields['sucursal']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['idtransaccion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['trackid']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idsalida']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php echo intval($rs->fields['segurogs']); ?></td>
			<td align="center"><?php echo intval($rs->fields['otrosgs']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['hojalevante']); ?></td>
			<td align="center"><?php echo intval($rs->fields['numhoja']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['totaliva10']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['totaliva5']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['texe']);  ?></td>
			<td align="center"><?php echo intval($rs->fields['idpedido']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['recibo']); ?></td>
			<td align="center"><?php echo intval($rs->fields['descporc']); ?></td>
			<td align="center"><?php echo intval($rs->fields['descneto']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['total_cobrado']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['monto_iva']);  ?></td>
			<td align="center"><?php echo intval($rs->fields['formapago']); ?></td>
			<td align="center"><?php echo intval($rs->fields['estado']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['deliv']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['totalcobrar']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['costo_total_ven']);  ?></td>
			<td align="center"><?php echo intval($rs->fields['anulado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['anulado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['anulado_el']));
			}  ?></td>
			<td align="center"><?php echo intval($rs->fields['moneda']); ?></td>
			<td align="center"><?php echo intval($rs->fields['tipoimpresion']); ?></td>
			<td align="center"><?php echo intval($rs->fields['vendedor']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['obs']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idcanal']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idzona']); ?></td>
			<td align="center"><?php echo intval($rs->fields['operador_pedido']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['recibido']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['vuelto']);  ?></td>
			<td align="center"><?php echo intval($rs->fields['idadherente']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idserviciocom']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idmozo']); ?></td>
			<td align="center"><?php echo intval($rs->fields['iddelivery']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['detalle_agrupado']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idtandatimbrado']); ?></td>
			<td align="center"><?php echo intval($rs->fields['timbrado']); ?></td>
			<td align="center"><?php echo intval($rs->fields['factura_sucursal']); ?></td>
			<td align="center"><?php echo intval($rs->fields['factura_puntoexpedicion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['impreso']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['diplomatico']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['finalizo_correcto']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['codtran']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idtrans']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['texto_opcional_venta']); ?></td>
			<td align="center"><?php echo intval($rs->fields['year_f']); ?></td>
			<td align="center"><?php echo intval($rs->fields['month_f']); ?></td>
			<td align="center"><?php echo intval($rs->fields['week_f']); ?></td>
			<td align="center"><?php echo intval($rs->fields['weekday_f']); ?></td>
			<td align="center"><?php echo intval($rs->fields['hour_f']); ?></td>
			<td align="center"><?php echo intval($rs->fields['iddeposito']); ?></td>
			<td align="center"><?php if ($rs->fields['venta_registrada_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['venta_registrada_el']));
			}  ?></td>
			<td align="center"><?php echo intval($rs->fields['excluye_repven']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idwebpedido']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idventaprinc']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idmotorista']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idcanalventa']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['codpedido_externo']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idsucursal_clie']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idapp']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idsalon_asig']); ?></td>
			<td align="center"><?php if ($rs->fields['factura_vto'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['factura_vto']));
			} ?></td>
			<td align="center"><?php echo intval($rs->fields['ocnumero']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['obs_varios']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idtipotran']); ?></td>
			<td align="center"><?php echo intval($rs->fields['id_indicador_presencia']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['cdc']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idtipotranset']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idtipooperacionset']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['electronica_ok']); ?></td>
		</tr>
<?php
$total_venta_acum += $rs->fields['total_venta'];
    $totaliva10_acum += $rs->fields['totaliva10'];
    $totaliva5_acum += $rs->fields['totaliva5'];
    $texe_acum += $rs->fields['texe'];
    $total_cobrado_acum += $rs->fields['total_cobrado'];
    $monto_iva_acum += $rs->fields['monto_iva'];
    $totalcobrar_acum += $rs->fields['totalcobrar'];
    $costo_total_ven_acum += $rs->fields['costo_total_ven'];
    $recibido_acum += $rs->fields['recibido'];
    $vuelto_acum += $rs->fields['vuelto'];

    $rs->MoveNext();
} //$rs->MoveFirst();?>
<tr>
	<td align="center" colspan="20">
		<div class="btn-group">
			<?php
            $last_index = 0;
if ($num_pag + 10 > $paginas_num_max) {
    $last_index = $paginas_num_max;
} else {
    $last_index = $num_pag + 10;
}
if ($num_pag != 1) { ?>
				<a href="<?php echo $pagina_actual ?>pag=<?php echo($num_pag - 1);?>" class="btn btn-sm btn-default" title="<?php echo($num_pag - 1);?>"  data-placement="right"  data-original-title="<?php echo($num_pag - 1);?>"><span class="fa fa-arrow-left"></span></a>
			<?php }
$inicio_pag = 0;
if ($num_pag != 1 && $num_pag - 5 > 0) {
    $inicio_pag = $num_pag - 5;
} else {
    $inicio_pag = 1;
}
for ($i = $inicio_pag; $i <= $last_index; $i++) {
    ?>
				<a href="<?php echo $pagina_actual ?>pag=<?php echo($i);?>" class="btn btn-sm btn-default <?php echo $i == $num_pag ? " selected_pag " : "" ?>" title="<?php echo($i);?>"  data-placement="right"  data-original-title="<?php echo($i);?>"><?php echo($i);?></a>
				<?php if ($i == $last_index && ($num_pag + 1 < $paginas_num_max)) {?>
					<a href="<?php echo $pagina_actual ?>pag=<?php echo($num_pag + 1);?>" class="btn btn-sm btn-default" title="<?php echo($num_pag + 1);?>"  data-placement="right"  data-original-title="<?php echo($num_pag + 1);?>"><span class="fa fa-arrow-right"></span></a>
				<?php } ?>
			<?php } ?>
		</div>
	</td>
</tr>
	  </tbody>
	  <tfoot>
		<tr>
			<td>Totales</td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td align="center"><?php echo formatomoneda($total_venta_acum); ?></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td align="center"><?php echo formatomoneda($totaliva10_acum); ?></td>
			<td align="center"><?php echo formatomoneda($totaliva5_acum); ?></td>
			<td align="center"><?php echo formatomoneda($texe_acum); ?></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td align="center"><?php echo formatomoneda($total_cobrado_acum); ?></td>
			<td align="center"><?php echo formatomoneda($monto_iva_acum); ?></td>
			<td></td>
			<td></td>
			<td></td>
			<td align="center"><?php echo formatomoneda($totalcobrar_acum); ?></td>
			<td align="center"><?php echo formatomoneda($costo_total_ven_acum); ?></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td align="center"><?php echo formatomoneda($recibido_acum); ?></td>
			<td align="center"><?php echo formatomoneda($vuelto_acum); ?></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
		</tr>
	  </tfoot>
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
<script>
  $('.btn-generar-grafico').on('click', function	() {
    var desde =  $('.desde').val();
    var hasta =  $('.hasta').val();
    console.log(desde, hasta);
    window.location.href = 'ventas_grafico.php?desde='+desde+'&hasta='+hasta;
  });
</script>
  </body>
</html>
