<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$dirsup = "S";
$submodulo = "134";
require_once("../includes/rsusuario.php");
/*
para corregir errores pasados cuando no ser registraba, no correr por que tomara los precios actuales y borrara los pasados si hubo cambio de precios
update conteo_detalles set diferencia = cantidad_contada-(cantidad_sistema+cantidad_venta) where diferencia = 0;
update conteo_detalles
set precio_venta = (
                    select p1 from productos
                    inner join insumos_lista on insumos_lista.idproducto = productos.idprod_serial
                    where
                    insumos_lista.idinsumo = conteo_detalles.idinsumo
                    and insumos_lista.idproducto is not null
                    )
where
precio_venta = 0;
// este si se puede correr
update `conteo_detalles` set diferencia_pv = precio_venta*diferencia;
// este si se puede correr
*/


$idconteo = intval($_GET['id']);
if (intval($idconteo) == 0) {
    header("location: conteo_stock.php");
    exit;
}

$consulta = "
select *,
(select descripcion from gest_depositos where iddeposito = conteo.iddeposito)  as deposito,
(select estadoconteo from estado_conteo where idestadoconteo = conteo.estado ) as estadoconteo,
(select usuario from usuarios where idusu = conteo.iniciado_por) as usuario,
(select usuario from usuarios where idusu = conteo.finalizado_por) as usuariofin
from conteo
where
estado <> 6
and estado = 3
and idconteo = $idconteo
and fecha_final is not  null
and idempresa = $idempresa
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito = intval($rs->fields['iddeposito']);
if (intval($rs->fields['idconteo']) == 0) {
    header("location: conteo_stock.php");
    exit;
}
$consulta = "
select *,
(SELECT nombre FROM grupo_insumos where idgrupoinsu = insumos_lista.idgrupoinsu) as grupo,
(SELECT nombre FROM medidas where id_medida = insumos_lista.idmedida) as medida,
(select cantidad_sistema from conteo_detalles where idconteo = $idconteo and idinsumo = insumos_lista.idinsumo) as stock,
(select cantidad_contada from conteo_detalles where idconteo = $idconteo and idinsumo = insumos_lista.idinsumo) as cantidad_contada,
(select diferencia from conteo_detalles where idconteo = $idconteo and idinsumo = insumos_lista.idinsumo) as diferencia,
(select diferencia_pc from conteo_detalles where idconteo = $idconteo and idinsumo = insumos_lista.idinsumo) as diferencia_pc,
(select diferencia_pv from conteo_detalles where idconteo = $idconteo and idinsumo = insumos_lista.idinsumo) as diferencia_pv,
(select cantidad_venta from conteo_detalles where idconteo = $idconteo and idinsumo = insumos_lista.idinsumo) as cantidad_venta,
/*(select p1 from productos where idprod_serial = insumos_lista.idproducto) as pventa,*/
(select precio_venta from conteo_detalles where idconteo = $idconteo and idinsumo = insumos_lista.idinsumo) as pventa,
(select precio_costo from conteo_detalles where idconteo = $idconteo and idinsumo = insumos_lista.idinsumo) as pcosto
from insumos_lista 
where 
insumos_lista.idgrupoinsu in (SELECT idgrupoinsu FROM conteo_grupos where idconteo = $idconteo)
and insumos_lista.idempresa = $idempresa
and insumos_lista.estado = 'A'
and insumos_lista.hab_invent = 1
order by (SELECT nombre FROM grupo_insumos where idgrupoinsu = insumos_lista.idgrupoinsu) asc, descripcion asc
";
$rs2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$fechastock = $rs->fields['final_registrado_el'];
$fechastockini = $rs->fields['inicio_registrado_el'];


//subtotales
while (!$rs2->EOF) {
    $idinsumo = $rs2->fields['idinsumo'];
    $idgrupoinsu = $rs2->fields['idgrupoinsu'];

    // calculos
    $stock_sis = $rs2->fields['stock'];
    $contabilizado = $rs2->fields['cantidad_contada'];
    $totalvalorizadopv = $rs2->fields['diferencia_pv'];
    $totalvalorizadopc = $rs2->fields['diferencia_pc'];
    $totalvalorizadopv_st = $rs2->fields['cantidad_contada'] * $rs2->fields['pventa'];
    $totalvalorizadopc_st = $rs2->fields['cantidad_contada'] * $rs2->fields['pcosto'];

    if (trim($rs2->fields['cantidad_contada']) != '') {
        $diferencia = floatval($rs2->fields['diferencia']);
    } else {
        $diferencia = 0;
    }


    // acumulados por categoria
    $stock_sisacum_cat[$idgrupoinsu] += $stock_sis;
    $contabilizadoacum_cat[$idgrupoinsu] += $contabilizado;
    $diferenciaacum_cat[$idgrupoinsu] += $diferencia;
    $totalvalorizadopvcum_cat[$idgrupoinsu] += $totalvalorizadopv;
    $totalvalorizadopccum_cat[$idgrupoinsu] += $totalvalorizadopc;
    $totalvalorizadopvcum_st_cat[$idgrupoinsu] += $totalvalorizadopv_st;
    $totalvalorizadopccum_st_cat[$idgrupoinsu] += $totalvalorizadopc_st;





    $rs2->MoveNext();
}
$rs2->MoveFirst();



?><!DOCTYPE html>
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
                    <h2>Resultado del Conteo</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p>
<a href="conteo_stock.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
<a href="conteo_stock_csv.php?id=<?php echo $idconteo; ?>" class="btn btn-sm btn-default"><span class="fa fa-file-excel-o"></span> Descargar CSV</a>
</p>
<hr />

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
    <tr>
      <th># Conteo</th>
      <th>Deposito</th>
      <th>Iniciado Por</th>
      <th>Finalizado Por</th>
      <th>Estado</th>
      </tr>
      </thead>
      <tbody>
    <tr>
      <td align="center"><?php echo $rs->fields['idconteo']; ?></td>
      <td align="center"><?php echo $rs->fields['deposito']; ?></td>
      <td align="center"><?php echo $rs->fields['usuario']; ?></td>
      <td align="center"><?php echo $rs->fields['usuariofin']; ?></td>
      <td align="center"><?php echo $rs->fields['estadoconteo']; ?></td>
      </tr>
  </tbody>
</table>
</div>
<p align="center">&nbsp;</p>
<div id="resp"></div>

<p>* Stock Sistema al: <?php echo date("d/m/Y H:i:s", strtotime($fechastock)); ?></p>
<p>* Ventas entre:   <?php echo date("d/m/Y H:i:s", strtotime($fechastockini)); ?> y <?php echo date("d/m/Y H:i:s", strtotime($fechastock)); ?></p>
<br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
    <tr>
	  <th>Cod</th>
      <th>Producto</th>
      <th><strong>Medida</th>
      <th><strong>Stock Sistema</th>
      <!--<td width="100" align="center" bgcolor="#F8FFCC"><strong>Ventas</th>
      <th>Stock c/ Vent</th>-->
      <th>Contabilizado</th>
      <th>Diferencia</th>
      <th>Precio Costo</th>
      <th>Dif Total PC</th>
      <th>Total PC</th>
      <th>Precio Venta</th>
      <th>Dif Total PV</th>
      <th>Total PV</th>
    </tr>
    </thead>
    <tbody>
<?php
$i = 1;
while (!$rs2->EOF) {

    // grupo de insumos
    $grupo = $rs2->fields['grupo'];
    $idgrupoinsu = $rs2->fields['idgrupoinsu'];
    $idinsumo = $rs2->fields['idinsumo'];
    $stock_sis = $rs2->fields['stock'];
    $contabilizado = $rs2->fields['cantidad_contada'];
    $cantidad_venta = $rs2->fields['cantidad_venta'];
    ?>
<?php
if ($i > 1) {
    if ($grupo != $grupoant) { ?>
		<tr style="background-color:#FF9">

			<td align="left" colspan="3">Subtotal <?php echo antixss($grupoant); ?> [<?php echo $idgrupoinsuant ?>]</td>

    
        
            <td align="center"><?php echo formatomoneda($stock_sisacum_cat[$idgrupoinsuant], 4, 'N');?></td>
            <td align="center"><?php echo formatomoneda($contabilizadoacum_cat[$idgrupoinsuant], 4, 'N');?></td>
            <td align="center"><?php echo formatomoneda($diferenciaacum_cat[$idgrupoinsuant], 4, 'N');?></td>
            <td align="center"></td>
            <td align="center"><?php echo formatomoneda($totalvalorizadopvcum_cat[$idgrupoinsuant], 4, 'N');?></td>
            <td align="center"><?php echo formatomoneda($totalvalorizadopccum_cat[$idgrupoinsuant], 4, 'N');?></td>
            <td align="center"></td>
            <td align="center"><?php echo formatomoneda($totalvalorizadopvcum_st_cat[$idgrupoinsuant], 4, 'N');?></td>
            <td align="center"><?php echo formatomoneda($totalvalorizadopccum_st_cat[$idgrupoinsuant], 4, 'N');?></td>
        
		</tr>
<?php
    }
}
    ?> 
<?php if ($grupo != $grupoant) { ?>
    <tr>
      <td colspan="14" bgcolor="#BFD2FF"><?php echo $grupo;?></td>
      </tr>
<?php } ?>
    <tr>
      <td align="center"><?php echo $rs2->fields['idinsumo']; ?></td>
      <td><?php echo $rs2->fields['descripcion']; ?></td>
      <td width="100" align="center"><?php echo $rs2->fields['medida']; ?></td>

      <td width="100" align="center"><?php if ($stock_sis != '') {
          echo formatomoneda($stock_sis, 4, 'N');
      } ?></td>
      <!--<td width="100" align="center"><?php if ($stock_sis != '') {
          echo formatomoneda($cantidad_venta, 4, 'N');
      } ?>
        </td>
      <td align="center"><?php if ($stock_sis != '') {
          echo formatomoneda($cantidad_venta + $stock_sis, 4, 'N');
      } ?></td>-->
      <td align="center" bgcolor="#FFFF66"><?php if ($contabilizado != '') {
          echo formatomoneda($contabilizado, 4, 'N');
      } ?></td>
<?php
$diferencia = "";
    if (isset($_POST['cont_'.$idinsumo]) && trim($_POST['cont_'.$idinsumo]) != '') {
        $diferencia = floatval($_POST['cont_'.$idinsumo]) - floatval($rs2->fields['stock']);
    } else {
        if (trim($rs2->fields['cantidad_contada']) != '') {
            //$diferencia=floatval($rs2->fields['cantidad_contada'])-(floatval($rs2->fields['stock'])+$cantidad_venta);
            $diferencia = floatval($rs2->fields['diferencia']);
        }
    }
    if ($diferencia < 0) {
        $colord = "FF0000";
    } else {
        $colord = "000000";
    }
    //$totalvalorizado=$diferencia*$rs2->fields['pventa'];
    $totalvalorizadopv = $rs2->fields['diferencia_pv'];
    $totalvalorizadopc = $rs2->fields['diferencia_pc'];


    $totalvalorizadopv_st = $rs2->fields['cantidad_contada'] * $rs2->fields['pventa'];
    $totalvalorizadopc_st = $rs2->fields['cantidad_contada'] * $rs2->fields['pcosto'];


    ?>
      <td align="center" id="dif_<?php echo $i; ?>" style="color:#<?php echo $colord; ?>;"><?php if (trim($diferencia) != '') {
          echo formatomoneda($diferencia, 4, 'N');
      } elseif (intval($diferencia) == 0) {
          echo $diferencia;
      } ?></td>
      <td align="center"><?php echo  formatomoneda($rs2->fields['pcosto'], 4, 'N'); ?></td>
      <td align="center" style="color:#<?php echo $colord; ?>;"><?php echo formatomoneda($totalvalorizadopc, 4, 'N'); ?></td>
      <td align="center" ><?php echo formatomoneda($totalvalorizadopc_st, 4, 'N'); ?></td>
      <td align="center"><?php echo  formatomoneda($rs2->fields['pventa'], 4, 'N'); ?></td>
      <td align="center" style="color:#<?php echo $colord; ?>;"><?php echo formatomoneda($totalvalorizadopv, 4, 'N'); ?></td>
      <td align="center" ><?php echo formatomoneda($totalvalorizadopv_st, 4, 'N'); ?></td>
    </tr>
<?php
$grupoant = $grupo;
    $idgrupoinsuant = $idgrupoinsu;

    // sumatorias
    $stock_sisacum += $stock_sis;
    $contabilizadoacum += $contabilizado;
    $diferenciaacum += $diferencia;
    $totalvalorizadopvcum += $totalvalorizadopv;
    $totalvalorizadopccum += $totalvalorizadopc;
    $totalvalorizadopvcum_st += $totalvalorizadopv_st;
    $totalvalorizadopccum_st += $totalvalorizadopc_st;
    //echo $totalvalorizadopvcum_st.'|'.$totalvalorizadopv_st."<br />";

    $i++;
    $rs2->MoveNext();
} ?>
    <tr style="background-color:#FF9">

        <td align="left" colspan="3">Subtotal <?php echo antixss($grupoant); ?> [<?php echo $idgrupoinsuant ?>]</td>


    
        <td align="center"><?php echo formatomoneda($stock_sisacum_cat[$idgrupoinsuant], 4, 'N');?></td>
        <td align="center"><?php echo formatomoneda($contabilizadoacum_cat[$idgrupoinsuant], 4, 'N');?></td>
        <td align="center"><?php echo formatomoneda($diferenciaacum_cat[$idgrupoinsuant], 4, 'N');?></td>
        <td align="center"></td>
        <td align="center"><?php echo formatomoneda($totalvalorizadopvcum_cat[$idgrupoinsuant], 4, 'N');?></td>
        <td align="center"><?php echo formatomoneda($totalvalorizadopccum_cat[$idgrupoinsuant], 4, 'N');?></td>
        <td align="center"></td>
        <td align="center"><?php echo formatomoneda($totalvalorizadopvcum_st_cat[$idgrupoinsuant], 4, 'N');?></td>
        <td align="center"><?php echo formatomoneda($totalvalorizadopccum_st_cat[$idgrupoinsuant], 4, 'N');?></td>
    
    </tr>
  </tbody>
    <tfoot>
    <tr>
	  <td colspan="3" align="left" bgcolor="#E0E0E0">Totales</td>
      <td width="100" align="center" bgcolor="#E0E0E0"><?php echo formatomoneda($stock_sisacum, 4, 'N'); ?></td>
     <!-- <td width="100" align="center" bgcolor="#E0E0E0">&nbsp;</td>
      <td width="80" align="center" bgcolor="#E0E0E0">&nbsp;</td>-->
      <td width="80" align="center" bgcolor="#E0E0E0"><?php echo formatomoneda($contabilizadoacum, 4, 'N'); ?></td>
      <td width="80" align="center" bgcolor="#E0E0E0"><?php echo formatomoneda($diferenciaacum, 4, 'N'); ?></td>
      <td width="80" align="center" bgcolor="#E0E0E0">&nbsp;</td>
      <td width="80" align="center" bgcolor="#E0E0E0" <?php if ($totalvalorizadopccum < 0) {?>style="color:#FF0000;"<?php } ?>><?php echo formatomoneda($totalvalorizadopccum, 4, 'N'); ?></td>
      <td width="80" align="center" bgcolor="#E0E0E0" ><?php echo formatomoneda($totalvalorizadopccum_st, 4, 'N'); ?></td>
      <td width="80" align="center" bgcolor="#E0E0E0">&nbsp;</td>
      <td width="80" align="center" bgcolor="#E0E0E0" <?php if ($totalvalorizadopvcum < 0) {?>style="color:#FF0000;"<?php } ?>><?php echo formatomoneda($totalvalorizadopvcum, 4, 'N'); ?></td>
      <td width="80" align="center" bgcolor="#E0E0E0" ><?php echo formatomoneda($totalvalorizadopvcum_st, 4, 'N'); ?></td>
    </tr>
</tfoot>
</table>
</div>
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
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
