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
                    <h2>Administracion de Timbrados</h2>
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


<p><a href="timbrados.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
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
<?php if ($soporte == 1) { ?>			  
<strong>Texto para enviar al cliente (solo visible para el soporte):</strong><br />
<div style="width: 100%; border:2px solid #FF0004; padding: 10px;">
  TIMBRADO CARGADO, NO EMITIR  FACTURAS HASTA VERIFICAR!<br />
    <br />
    Favor verificar que est&eacute;n correctamente cargado  los datos:<br />
    Timbrado, inicio vigencia, fin vigencia, sucursal (cod  establecimiento), punto expedici&oacute;n, numero desde, numero hasta, prox factura.<br />
    En gestion &gt; administrar timbrados &gt; detalle  (lupa)<br />
    Si es un timbrado nuevo (Autoimpresor) debe  comenzar siempre en 1, caso contrario un n&uacute;mero m&aacute;s que el ultimo emitido.<br />
  <br />
    Si bien lo ideal es que el cliente cargue el  timbrado, el soporte podr&aacute; hacerlo si se lo solicitan, pero siempre ser&aacute; responsabilidad del cliente verificar que de los datos est&eacute;n completos y  correctamente cargados antes de emitir facturas.<br />
  <br />
    Nuestro soporte con gusto les guiara para que puedan ver los datos cargados en las distintas secciones del sistema y as&iacute;  realizar su verificaci&oacute;n. 
	
  <br />
</div>
<?php } ?>
<hr />
<div class="alert alert-warning alert-dismissible fade in" role="alert">
<strong>Importante:</strong><br />si es un timbrado nuevo y es <strong>AUTOIMPRESOR</strong>, se debe <strong>REINICIAR LA NUMERACION</strong> a cero, en <a href='corregir_correlatividad_factura.php' target='_blank'>contabilidad > proxima factura</a>, para <strong>TODOS</strong> los puntos de expedicion.	
<br />Esto <strong>NO APLICA A PREIMPRESO</strong>.
</div>
<p>
	<a href="timbrados_det_add.php?id=<?php echo $idtimbrado ?>" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar Documento</a> 
	<a href="timbrado_doc_importar.php?id=<?php echo $idtimbrado ?>" class="btn btn-sm btn-default"><span class="fa fa-upload"></span> Carga Masiva</a>
	
	<a href="timbrados_doc_proxfac.php?id=<?php echo $idtimbrado ?>" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Reinicio de Proxima Factura Masiva</a>

</p>
<br />
Documentos: <br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
            <th align="center">Documento</th>
            <th align="center">Tipo</th>
			<th align="center">Sucursal</th>
			<th align="center">Punto expedicion</th>
			<th align="center">Numero Desde</th>
			<th align="center">Numero Hasta</th>
            <th align="center">Ultimo Usado</th>
            <th align="center">Prox Numero</th>
			<th align="center">Comentario</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) {

    // si no existe tanda de comprobante crea
    /*$sucursal=intval($rs->fields['sucursal']);
    $punto_expedicion=intval($rs->fields['punto_expedicion']);

    $ano=date("Y");
    // busca si existe algun registro
    $buscar="
    Select idsuc, numfac as mayor
    from lastcomprobantes
    where
    idsuc=$sucursal
    and pe=$punto_expedicion
    and idempresa = $idempresa
    order by ano desc
    limit 1";
    $rsfactura=$conexion->Execute($buscar) or die(errorpg( $conexion,$buscar));
    //$maxnfac=intval(($rsfactura->fields['mayor'])+1);
    // si no existe inserta
    if(intval($rsfactura->fields['idsuc']) == 0){
        $consulta="
        INSERT INTO lastcomprobantes
        (idsuc, factura, numfac, recibo, numrec, tickete, numtk, ano, pe, numcheque, secuencia_cancela,
        numhoja, hojalevante, idempresa)
        VALUES
        ($sucursal, 0, 0, NULL, 0, NULL, 0, $ano, $punto_expedicion, NULL,
        NULL, 0, '', 1)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    }
      */
    ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="timbrados_det_edit.php?id=<?php echo $rs->fields['idtanda']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="timbrados_det_del.php?id=<?php echo $rs->fields['idtanda']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
            <td align="center"><?php echo antixss($rs->fields['tipo_documento']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['tipoimpreso']); ?></td>
			<td align="center"><?php echo agregacero(intval($rs->fields['sucursal']), 3); ?></td>
			<td align="center"><?php echo agregacero(intval($rs->fields['punto_expedicion']), 3); ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['inicio']); ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['fin']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['ultfactura']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['prox_factura']);
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
  </body>
</html>
