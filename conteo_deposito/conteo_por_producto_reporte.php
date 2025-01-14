<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$dirsup = "S";
$submodulo = "134";
require_once("../includes/rsusuario.php");


$iddeposito = intval($_GET['iddeposito']);
$idinsumo = intval($_GET['idinsumo']);
$idconteo = intval($_GET['id']);
$num_articulos_pendientes = 0;
$consulta = "SELECT descripcion from gest_depositos where iddeposito=$iddeposito";
$rs_depositos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$nombre_deposito = $rs_depositos->fields['descripcion'];
//////////////////////////////////////////////////////////////////////////////////////////
$consulta = "SELECT count(conteo_detalles.unicose) as idconteo_ref
from conteo_detalles
INNER JOIN conteo on conteo.idconteo = conteo_detalles.idconteo
LEFT JOIN gest_almcto_pasillo on gest_almcto_pasillo.idpasillo = conteo_detalles.idpasillo
INNER JOIN gest_deposito_almcto on gest_deposito_almcto.idalm = conteo_detalles.idalm
INNER JOIN gest_deposito_almcto_grl on gest_deposito_almcto_grl.idalmacto = gest_deposito_almcto.idalmacto
INNER JOIN medidas on medidas.id_medida = conteo_detalles.idmedida_ref
where 
conteo.idconteo_ref = $idconteo
and conteo.estado = 2
ORDER BY conteo_detalles.idconteo, conteo_detalles.idalm
";
$rs_sub_conteo_detalles = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$num_detalles = intval($rs_sub_conteo_detalles->fields['idconteo_ref']);
if ($num_detalles == 0) {
    $location = "conteo_por_producto_detalle.php?id=".$iddeposito."&idinsumo=".$idinsumo;
    header("location: $location");
    exit;
}


$consulta = "select * from 
                conteo 
            where
                idconteo = $idconteo
                and idempresa = $idempresa
";
$rs_conteo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$conteo_estado = $rs_conteo->fields['estado'];
$idconteo = intval($rs_conteo->fields['idconteo']);

if ($idconteo == 0 || $conteo_estado == 0) {
    $location = "conteo_por_producto_detalle.php?id=".$iddeposito."&idinsumo=".$idinsumo;
    header("location: $location");
    exit;
}

if ($idinsumo == 0) {
    $location = "conteo_stock_detalle.php?id=".$iddeposito;
    header("location: $location");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
<script>
	function cerar_articulos(parametros) {
		console.log(parametros.idalms);
		event.preventDefault();
		var parametros_crear = {
			"idalms"		  : parametros.idalms,
			"iddeposito"		  : parametros.iddeposito,
			"idinsumo"		  : parametros.idinsumo,
			"idconteo"	: parametros.idconteo,
		};
		$.ajax({
				data:  parametros_crear,
				url:   'cerar_articulos_restantes.php',
				type:  'post',
				beforeSend: function () {
					// $("#conteo_productos").html('Cargando...');  
				},
				success:  function (response) {
					console.log(response);
					// if (JSON.parse(response)['success']==true) {
					// 	var url  = 'conteo_por_producto_detalle.php?id=<?php // echo $iddeposito?>&idinsumo=<?php // echo $idinsumo;?>';
					// 	document.location.href = url;
					// }
					location.reload();
				}
		});
	}
	function guardar_conteo(event, afecta_stock){
		event.preventDefault();
		
			var parametros = {
				"idconteo"		  	: <?php echo $idconteo; ?>,
				"mueve_stock"	  	: afecta_stock ? 1 : 0,
				"consolidar_conteo"	: 1
			};
			
			$.ajax({
					data:  parametros,
					url:   'guardar_conteo.php',
					type:  'post',
					beforeSend: function () {
						$("#conteo_productos").html('Cargando...');  
					},
					success:  function (response) {
						console.log(response);
						if (JSON.parse(response)['success']==true) {
							var url  = 'conteo_por_producto_detalle.php?id=<?php echo $iddeposito?>&idinsumo=<?php echo $idinsumo; ?>';
							document.location.href = url;
						}
					}
			});
	}
	function volver_atras(event){
		event.preventDefault();
		var url  = 'conteo_por_producto_detalle.php?id=<?php echo $iddeposito?>&idinsumo=<?php echo $idinsumo; ?>';
		document.location.href = url;
	}
	function convertirAEntero(cadena) {
		// Intentar convertir la cadena en un número entero
		const numero = parseInt(cadena);

		// Verificar si la conversión fue exitosa
		if (!isNaN(numero)) {
			return numero; // La conversión fue exitosa, retornar el número entero
		} else {
			return 0; // La conversión falló, retornar 0
		}
	}
	
	function mantiene_session(){
		var f=new Date();
		cad=f.getHours()+":"+f.getMinutes()+":"+f.getSeconds(); 
		var parametros = {
					"ses" : cad,
		};
		$.ajax({
					data:  parametros,
					url:   'mantiene_session.php',
					type:  'post',
					beforeSend: function () {
					},
					success:  function (response) {
						//alert(response);
					}
			});	
	}
	
	function solonumerosypuntoycoma(e)
			{
				var keynum = window.event ? window.event.keyCode : e.which;
				if ((keynum == 8) || (keynum == 46) || (keynum == 190) || (keynum == 110) || (keynum == 188))
				return true;
			
				return /\d/.test(String.fromCharCode(keynum));
			}

	
	function reabrir_conteo(unicose){
		var parametros = {
                "idsub_conteo"   : unicose
				
		};
		$.ajax({
                data:  parametros,
                url:   'conteo_consolidado_edit.php?idsub_conteo='+unicose,
                type:  'get',
                beforeSend: function () {
                    //   $("#conteo_productos").html('Cargando...');  
                },
                success:  function (response) {
					console.log(response);
					//   $("#conteo_productos").html(response);
					// location.reload();
					var url  = 'conteo_por_producto_detalle.php?id=<?php echo $iddeposito?>&idinsumo=<?php echo $idinsumo?>';
					document.location.href = url;

                }
        });

	}

	
</script>
<style>

.enlace-con-bloqueo {
          cursor: no-drop;
}
	.div_leyenda{
		width: 100%;
		display: flex;
		align-items: center;
		/* justify-content: center; */
	}
	.leyenda_alerta{
		width: 10px;
		height: 10px;
		background: #EA6153;
		display: inline-block;
		margin: 10px;
	}
	.mt-1{
		margin-top: 20px !important;
	}
	input:focus, select:focus {
		border: #add8e6 solid 3px !important; /* Este es un tono de azul pastel */
	}
</style>
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
                    <h2>Conteo <?php echo "Deposito ".$nombre_deposito; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p>
	<a href="conteo_por_producto_detalle.php?id=<?php echo $iddeposito?>&idinsumo=<?php echo $idinsumo; ?>" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
</p>

<hr />


<div class="table-responsive">
  <table width="100%" class="table table-bordered jambo_table bulk_action">
    <tr>
      <th align="center">Idconteo</th>
      <td align="center"><?php echo intval($rs_conteo->fields['idconteo']); ?></td>
    </tr>

    <tr>
      <th align="center">Fecha Inicio</th>
      <td align="center"><?php echo antixss($rs_conteo->fields['fecha_inicio']); ?></td>
    </tr>

    <tr>
      <th align="center">Ultima Modificacion</th>
      <td align="center"><?php echo antixss($rs_conteo->fields['ult_modif']); ?></td>
    </tr>
  </table>
</div>
<br />


<div>
	

			
			<div class="col-md-12 table-responsive " id="conteo_productos">
			<div class="">
				<table class="table table-bordered">
					<thead>
						<tr>
							<th>Producto</th>
							<th>Cantidad(Unidades)</th>
							<th>diferencia(Unidades)</th>
							<th>Vencimiento</th>
							<th>Almacenamiento</th>
							<th>Almacenado en</th>
							<th>Fila</th>
							<th>Columna</th>
							<th>Pasillo</th>
							<th>Medida_ref</th>
						</tr>
					</thead>
					<tbody>
						<?php
                        $i = 0;

$consulta = "SELECT conteo_detalles.*,
						(SELECT usuarios.nombres from usuarios where usuarios.idusu = conteo.iniciado_por ) as iniciado_por,
						medidas.nombre as medida_ref, gest_deposito_almcto_grl.nombre as almacenamiento, CONCAT(gest_deposito_almcto.nombre,' ',COALESCE(gest_deposito_almcto.cara, ''))  as tipo_almacenamiento, gest_almcto_pasillo.nombre as pasillo
						from conteo_detalles
						INNER JOIN conteo on conteo.idconteo = conteo_detalles.idconteo
						LEFT JOIN gest_almcto_pasillo on gest_almcto_pasillo.idpasillo = conteo_detalles.idpasillo
						INNER JOIN gest_deposito_almcto on gest_deposito_almcto.idalm = conteo_detalles.idalm
						INNER JOIN gest_deposito_almcto_grl on gest_deposito_almcto_grl.idalmacto = gest_deposito_almcto.idalmacto
						INNER JOIN medidas on medidas.id_medida = conteo_detalles.idmedida_ref
						where 
						conteo.idconteo_ref = $idconteo
						and conteo.estado = 2
						ORDER BY conteo_detalles.idconteo, conteo_detalles.idalm, 
						tipo_almacenamiento,
						fila,
						columna
						";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if ($rs->RecordCount() > 0) {
    while (!$rs->EOF) {
        $unicose_det = $rs->fields['unicose'];
        ?>
						<?php if ($i != $rs->fields['idconteo']) {
						    $i = $rs->fields['idconteo'];

						    ?>
							<tr style="background:#337AB7;" >
								<td style="background:#337AB7;color:white;">Idconteo:<?php echo antixss($rs->fields['idconteo']); ?></td>
								<td align="center" colspan="3" style="background:#337AB7;color:white;">Registrado por: <?php echo antixss($rs->fields['iniciado_por']); ?></td>
								<td style="background:#337AB7;color:white;" colspan="6" align="right">
								</td>
							</tr>
						<?php


						}

        ?>
						<tr >
							
							<td><?php echo antixss($rs->fields['descripcion']); ?></td>
							<td align="center"><?php echo formatomoneda($rs->fields['cantidad_contada'], 2, 'N'); ?></td>
							<td align="center"><?php echo formatomoneda($rs->fields['diferencia'], 2, 'N'); ?></td>
							<td> Vencimiento: <?php echo $rs->fields['vencimiento'] ? date("d/m/Y", strtotime($rs->fields['vencimiento'])) : "--" ?> <br> Lote: <?php echo ($rs->fields['lote'])   ?> </td>
							<td><?php echo antixss($rs->fields['almacenamiento']); ?></td>
							<td><?php echo antixss($rs->fields['tipo_almacenamiento']); ?></td>
							<td><?php echo antixss($rs->fields['fila']); ?></td>
							<td><?php echo antixss($rs->fields['columna']); ?></td>
							<td><?php echo antixss($rs->fields['pasillo']); ?></td>
							<td><?php echo antixss($rs->fields['medida_ref']); ?></td>
							
						</tr>

						<?php
        $rs->MoveNext();
    }
}
//lo mismo que al buscar por almacenamiento del while
// pero ahora se tiene que buscar en todos los almacenamientos
// del deposito seleccionado y compararlo con el conteo para
//verificar que no exista algun almacenamiento olvidado o mercaderia
// perdida
$consulta = "SELECT DISTINCT(conteo_detalles.idalm) 
								FROM conteo_detalles
								INNER JOIN conteo ON conteo.idconteo = conteo_detalles.idconteo
								where 
								conteo.idconteo_ref=$idconteo
								and estado=2
					";
$rs_conteo_deposito = $conexion->GetCol($consulta) or die(errorpg($conexion, $consulta));
$consulta = "SELECT DISTINCT(gest_depositos_stock_almacto.idalm) as idalm 
					FROM gest_depositos_stock_almacto
					INNER JOIN gest_deposito_almcto on gest_deposito_almcto.idalm = gest_depositos_stock_almacto.idalm
					INNER JOIN gest_deposito_almcto_grl on gest_deposito_almcto_grl.idalmacto = gest_deposito_almcto.idalmacto
					INNER JOIN gest_depositos_stock ON gest_depositos_stock.idregseriedptostk = gest_depositos_stock_almacto.idregseriedptostk
					where 
					gest_deposito_almcto_grl.iddeposito = $iddeposito
					and gest_depositos_stock.idproducto = $idinsumo
					and gest_depositos_stock_almacto.disponible > 0
					and gest_depositos_stock_almacto.estado = 1";

$rs_stock_idalm = $conexion->GetCol($consulta) or die(errorpg($conexion, $consulta));
$result = array_diff($rs_stock_idalm, $rs_conteo_deposito);
$longitud = count($result);
if ($longitud > 0) {

    ?>
						
						
						<?php
}


?>
					
					</tbody>
				</table>
			</div>
			</div>
			<div class="col-md-12 col-xs-12"  style="text-align:center;">
				<input type="hidden" name="ocinsumo" id="ocinsumo" value="" />

				<button  class="btn btn-secondary " style="width:15vw;"  onclick="volver_atras(event)"><span class="fa fa-reply"></span>&nbsp;Atras</button>

			</div>
                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 


			<!-- POPUP OCULTO  -->
			<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="ventanamodal">
				<div class="modal-dialog modal-lg">
				  <div class="modal-content">
					<div class="modal-header">
					  <h4 class="modal-title" id="titulov"></h4>
					</div>
					<div class="modal-body" id="cuerpov" >
						
					</div>
					<div class="modal-footer"  id="piev">
					  
					  <button type="button" id="cerrarpop" style="display:none;" class="btn btn-default" data-dismiss="modal">Cerrar</button>&nbsp;
					  
					</div>

				  </div>
				</div>
			</div>
			<!-- FIN POPUP -->
            
            
            
            
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
