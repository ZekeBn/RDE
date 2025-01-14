<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "224";
require_once("includes/rsusuario.php");

$idtimbrado = intval($_GET['id']);
$consulta = "
select *,
(select usuario from usuarios where timbrado.registrado_por = usuarios.idusu) as registrado_por,
(select usuario from usuarios where timbrado.editado_por = usuarios.idusu) as editado_por,
(select usuario from usuarios where timbrado.borrado_por = usuarios.idusu) as borrado_por
from timbrado 
where 
 estado = 1 
and  idtimbrado = $idtimbrado
order by idtimbrado asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtimbrado = intval($rs->fields['idtimbrado']);
if ($idtimbrado == 0) {
    header("location: timbrados.php");
    exit;
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
                    <h2>Reiniciar Numeracion de Timbrados</h2>
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

<div class="alert alert-danger alert-dismissible fade in" role="alert">
<strong>IMPORTANTE (LEER ANTES DE APLICAR):</strong><br />Si el timbrado antiguo utiliza los mismos puntos de expedicion, primero borralo antes de reiniciar las numeraciones, o se aplicara el reinicio tambien para el timbrado antiguo.</strong>
</div>

                  <p>
	<a href="timbrados_det.php?id=<?php echo $idtimbrado ?>" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a> 

</p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">Idtimbrado</th>
			<th align="center">Timbrado</th>
			<th align="center">Inicio vigencia</th>
			<th align="center">Fin vigencia</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>

			<td align="center"><?php echo intval($rs->fields['idtimbrado']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['timbrado']); ?></td>
			<td align="center"><?php if ($rs->fields['inicio_vigencia'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['inicio_vigencia']));
			} ?></td>
			<td align="center"><?php if ($rs->fields['fin_vigencia'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fin_vigencia']));
			}

    if (strtotime($rs->fields['fin_vigencia']) < strtotime(date("Y-m-d"))) {
        echo "<br /><strong style=\"color: red;\">Vencido!</strong>";
    }
    ?></td>

		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>

<?php

/*
CASE
    WHEN tipoimpreso = 'AUT' THEN 'AUTOIMPRESOR'
    WHEN tipoimpreso = 'PRE' THEN 'PRE IMPRESO'
END as tipoimpreso
*/
$consulta = "
select *,
(select usuario from usuarios where facturas.registrado_por = usuarios.idusu) as registrado_por,
(select tipo_documento from timbrado_tipodocu where timbrado_tipodocu.idtipodocutimbrado = facturas.idtipodocutimbrado) as tipo_documento,


CASE WHEN 
	idtipodocutimbrado = 1
THEN
	(select max(SUBSTRING(REPLACE(factura,'-',''), 7,7)) ultfactura from ventas where idtandatimbrado = facturas.idtanda) 
ELSE
	(select max(SUBSTRING(REPLACE(numero,'-',''), 9,7)) ultnota from nota_credito_cabeza where idtandatimbrado = facturas.idtanda) 
END as ultfactura,


facturas.idtimbradotipo,
(SELECT timbrado_tipo from timbrado_tipo where idtimbradotipo = facturas.idtimbradotipo) as tipoimpreso,

CASE WHEN 
	idtipodocutimbrado = 1
THEN
	(
		SELECT COALESCE(numfac,0)+1
		FROM lastcomprobantes 
		where 
		idsuc=facturas.sucursal
		and pe=facturas.punto_expedicion
		order by ano desc 
		limit 1 
	)
ELSE
	(
		SELECT COALESCE(numero_nc,0)+1
		FROM lastcomprobantes 
		where 
		idsuc=facturas.sucursal
		and pe=facturas.punto_expedicion
		order by ano desc 
		limit 1 
	)
END as prox_factura



from facturas 
where 
 estado = 'A' 
 and idtimbrado = $idtimbrado
order by sucursal asc, punto_expedicion asc, inicio asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>

<hr />

Documentos: <br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			
            <th align="center">Documento</th>
            <th align="center">Tipo</th>
			<th align="center">Sucursal</th>
			<th align="center">Punto expedicion</th>
			<th align="center">Numero Desde</th>
			<th align="center">Numero Hasta</th>
            <th align="center">Ultimo Usado</th>
            <th align="center">Prox Numero</th>
            <th></th>
			<th align="center">Comentario</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) {

    ?>
		<tr>

            <td align="center"><?php echo antixss($rs->fields['tipo_documento']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['tipoimpreso']); ?></td>
			<td align="center"><?php echo agregacero(intval($rs->fields['sucursal']), 3); ?></td>
			<td align="center"><?php echo agregacero(intval($rs->fields['punto_expedicion']), 3); ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['inicio']); ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['fin']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['ultfactura']); ?></td>
            <td align="center" id='box_rei_<?php echo $rs->fields['idtanda']; ?>'><?php echo formatomoneda($rs->fields['prox_factura']);
    // si no es preimpreso verifica
    if (intval($rs->fields['idtimbradotipo']) > 1) {
        // la ultima factura +1 debe ser igual a la proxima si aun no se uso en otro sistema
        if (intval($rs->fields['ultfactura']) + 1 != intval($rs->fields['prox_factura'])) {
            echo "<br /><strong style=\"color: red;\">CUIDADO, podria estar mal asignado, verificar.</strong>";
        }
        // si es preimpreso
    } else {
        // solo si ya se uso con ese timbrado alguna venta ahi valida
        if (intval($rs->fields['ultfactura']) > 0) {
            if (intval($rs->fields['ultfactura']) + 1 != intval($rs->fields['prox_factura'])) {
                echo "<br /><strong style=\"color: red;\">CUIDADO, podria estar mal asignado, verificar.</strong>";
            }
        }
    }
    ?></td>
			<td id='btn_rei_<?php echo $rs->fields['idtanda']; ?>'>
            <?php if (intval($rs->fields['idtimbradotipo']) == 2) { ?>
                <?php if (intval($rs->fields['idtipodocutimbrado']) == 1) { ?>
				<div class="btn-group">
					<a href="javascript:void(0);" onclick="reinicia_proxfac(<?php echo $rs->fields['idtanda']; ?>);" class="btn btn-sm btn-default" title="Reiniciar" data-toggle="tooltip" data-placement="right"  data-original-title="Reiniciar"><span class="fa fa-refresh"></span> Reiniciar Numeracion</a>
					
				</div>
                <?php } ?>
            <?php } else {
                echo 'No aplica para '.antixss($rs->fields['tipoimpreso']);
            } ?>
			</td>
			<td align="center"><?php echo antixss($rs->fields['comentario_punto']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['registrado_el']));
			} ?></td>
		</tr>
<?php $rs->MoveNext();
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

        <!-- footer content -->
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
<script>
function reinicia_proxfac(idtanda){
    //alert(idtanda);
	var direccionurl='timbrados_reinicia_proxfac.php';	
	var parametros = {
        "MM_update" : 'form1',
	    "idtanda" : idtanda
	};
    
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			$("#box_rei_"+idtanda).html('Cargando...');
            $("#btn_rei_"+idtanda).hide();					
		},
		success:  function (response, textStatus, xhr) {
			$("#box_rei_"+idtanda).html(response);
            $("#btn_rei_"+idtanda).show();
		},
		error: function(jqXHR, textStatus, errorThrown) {
			errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
		}
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
	});
}
</script>
  </body>
</html>
