<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_compras.php");
require_once("../includes/funciones_proveedor.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");
require_once("./preferencias_compras.php");


$pag = intval($_POST['pag']);
$fecha_cotizacion = antisqlinyeccion($_POST['fecha_cotizacion'], "date");
if ($idmoneda == 0) {
    $idmoneda = $_POST['idmoneda'];
}
$whereadd = "";
// echo $fecha_cotizacion;exit;
if ($fecha_cotizacion != "NULL") {
    $whereadd = " and cotizaciones.fecha = $fecha_cotizacion";
}



$consulta_numero_filas = "
select 
count(*) as filas 
from cotizaciones 
inner join tipo_moneda on tipo_moneda.idtipo = cotizaciones.tipo_moneda
where
cotizaciones.estado = 1
and tipo_moneda.estado = 1
and cotizaciones.tipo_moneda = $idmoneda
order by cotizaciones.fecha desc
";
$rs_filas = $conexion->Execute($consulta_numero_filas) or die(errorpg($conexion, $consulta_numero_filas));
$num_filas = $rs_filas->fields['filas'];
$filas_por_pagina = 10;
$num_pag = intval($_POST['pag']);
$paginas_num_max = ceil($num_filas / $filas_por_pagina);
if (intval($num_filas) > $filas_por_pagina) {
    $limit = "  LIMIT $filas_por_pagina";
}

if (($_POST['pag']) > 0) {
    $numero = (intval($_POST['pag']) - 1) * $filas_por_pagina;
    if ($numero != 0) {
        $offset = " offset $numero";

    }
} else {
    $offset = " ";
    $num_pag = 1;
}



$consulta = "SELECT *,
tipo_moneda.borrable, tipo_moneda.descripcion,
(select usuario from usuarios where cotizaciones.registrado_por = usuarios.idusu) as registrado_por
FROM cotizaciones
inner join tipo_moneda on tipo_moneda.idtipo = cotizaciones.tipo_moneda
where
cotizaciones.estado = 1
and tipo_moneda.estado = 1
and cotizaciones.tipo_moneda = $idmoneda
$whereadd
order by cotizaciones.fecha desc
 $limit $offset
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
<script>
    function agregar_cotizacion_modal(idcot,cotizacion,fecha){
        $('#idcot').html($('<option>', {
            value: idcot,
            text: cotizacion
        }));
        $("#idcot").css('border', 'none');
        $('#idcot').val(idcot);
        $('#idcotizacion').val(idcot);
        $("#fecha_cotizacion_text").html("Fecha Cotizacion: "+fecha);
        $("#fecha_cotizacion_text").removeClass("hide");
        cerrar_pop_ventanas();
    }
    function cerrar_pop_ventanas(){
		$("#modal_ventana").modal("hide");
		$("#dialogobox").modal("hide");
	}
    function cambiar_pag(i){
		event.preventDefault();
			
			var parametros = {
                "idmoneda"  : <?php echo $idmoneda; ?>,
                "pag"       : i
			};

			$("#titulov").html("Buscar Orden de Compra");
			$.ajax({		  
				data:  parametros,
				url:   'grilla_cotizaciones_select.php',
				type:  'post',
				cache: false,
				timeout: 3000,  // I chose 3 secs for kicks: 3000
				crossDomain: true,
				beforeSend: function () {	
					
				},
				success:  function (response) {
					$("#box_grilla_cotizacion_select").html(response);	
					
				}
			});
	}
</script>



<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Cotizacion(venta)</th>
			<th align="center">fecha</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) {
    $idcotizacion = $rs->fields['idcot'];
    $cot_venta = $rs->fields['cotizacion'];
    ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="javascript:void(0)" onclick="agregar_cotizacion_modal(<?php echo $idcotizacion; ?>,<?php echo $cot_venta; ?>,'<?php echo date('d/m/Y', strtotime($rs->fields['fecha'])); ?>')" class="btn btn-sm btn-default" title="Select" data-toggle="tooltip" data-placement="right"  data-original-title="Select"><span class="fa fa-check"></span>Seleccionar</a>
					<!-- <a href="../compras/compras_detalles.php?id=<?php echo $rs->fields['ocnum']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-list-ul"></span></a> -->
				</div>

			</td>
			<td align="center"><?php echo antixss($rs->fields['cotizacion']); ?></td>
			<td align="center"><?php echo antixss(date("d/m/Y", strtotime($rs->fields['fecha']))); ?></td>
		</tr>
		

<?php

    $rs->MoveNext();
} //$rs->MoveFirst();?>
<?php if ($fecha_cotizacion == "NULL") { ?>
    <tr>
        <td align="center" colspan="10">
            <div class="btn-group">
                <?php
                $last_index = 0;
    if ($num_pag + 10 > $paginas_num_max) {
        $last_index = $paginas_num_max;
    } else {
        $last_index = $num_pag + 10;
    }
    if ($num_pag != 1) { ?>
                    <a href="javascript:void(0);" onclick="cambiar_pag(<?php echo(1);?>)" class="btn btn-sm btn-default" title="<?php echo(1);?>"  data-placement="right"  data-original-title="<?php echo(1);?>"><span class="fa fa-chevron-left"></span><span class="fa fa-chevron-left"></span></a>
                    <a href="javascript:void(0);" onclick="cambiar_pag(<?php echo($num_pag - 1);?>)" class="btn btn-sm btn-default" title="<?php echo($num_pag - 1);?>"  data-placement="right"  data-original-title="<?php echo($num_pag - 1);?>"><span class="fa fa-chevron-left"></span></a>
                <?php }
    $inicio_pag = 0;
    if ($num_pag != 1 && $num_pag - 5 > 0) {
        $inicio_pag = $num_pag - 5;
    } else {
        $inicio_pag = 1;
    }
    for ($i = $inicio_pag; $i <= $last_index; $i++) {
        ?>
                    <a href="javascript:void(0);" onclick="cambiar_pag(<?php echo($i);?>)" class="btn btn-sm btn-default <?php echo $i == $num_pag ? " selected_pag " : "" ?>" title="<?php echo($i);?>"  data-placement="right"  data-original-title="<?php echo($i);?>"><?php echo($i);?></a>
                    <?php if ($i == $last_index && ($num_pag + 1 <= $paginas_num_max)) {?>
                        <a  href="javascript:void(0);" onclick="cambiar_pag(<?php echo($num_pag + 1);?>)" class="btn btn-sm btn-default" title="<?php echo($num_pag + 1);?>"  data-placement="right"  data-original-title="<?php echo($num_pag + 1);?>"><span class="fa fa-chevron-right"></span></a>
                        <a href="javascript:void(0);"  onclick="cambiar_pag(<?php echo($paginas_num_max);?>)" class="btn btn-sm btn-default" title="<?php echo($paginas_num_max);?>"  data-placement="right"  data-original-title="<?php echo($paginas_num_max);?>"><span class="fa fa-chevron-right"></span><span class="fa fa-chevron-right"></span></a>
                    <?php } ?>
                <?php } ?>
            </div>
        </td>
    </tr>
<?php } ?>
	  </tbody>
	 
    </table>
</div>