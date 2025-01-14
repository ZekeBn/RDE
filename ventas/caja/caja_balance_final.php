<?php
/*-------------------------------
19/10/2021

----------------------------*/
require_once("../../includes/conexion.php");
require_once("../../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "22";
$dirsup_sec = "S";

require_once("../../includes/rsusuario.php");
//Para resumen final, NO VISIBLE

$buscar = "Select * from preferencias_caja limit 1";
$rscajab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$cargar_boleta_encaja = trim($rscajab->fields['carga_boleta_deposito']);

$idcaja = intval($idcaja);
if ($idcaja == 0) {
    $idcaja = intval($_POST['idcaja']);
}

if ($idcaja == 0) {
    echo "error al obtener idcaja";
    exit;
}

$buscar = "Select SUM(subtotal) as total from caja_billetes
where caja_billetes.idcajero=$idusu and idcaja=$idcaja and caja_billetes.estado=1";
$rstb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$buscar = "select monto_apertura from caja_super where idcaja=$idcaja";
$rstc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$montoapertura = floatval($rstc->fields['monto_apertura']);
//exit;
//Monedas extranjeras
$buscar = "Select descripcion,cantidad,subtotal,sermone 
from caja_moneda_extra 
inner join tipo_moneda on tipo_moneda.idtipo=caja_moneda_extra.moneda 
where idcaja=$idcaja and cajero=$idusu and caja_moneda_extra.estado=1";
$rsmmone = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tmex = $rsmmone->RecordCount();

//Monedas extranjeras
$buscar = "Select sum(subtotal) as total
from caja_moneda_extra 
inner join tipo_moneda on tipo_moneda.idtipo=caja_moneda_extra.moneda 
where idcaja=$idcaja and cajero=$idusu and caja_moneda_extra.estado=1";
$rsmonextratot = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


//Formas de Pago
$buscar = "Select (select descripcion from formas_pago where idforma=caja_arqueo_fpagos.idformapago) as descripcion,
monto,(select descripcion from gest_bancos where banco=caja_arqueo_fpagos.idbanco) as banco,
valor_adicional,idserie 
from caja_arqueo_fpagos 
where idcaja=$idcaja and caja_arqueo_fpagos.estado=1";
$rsfpn = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tfp = $rsfpn->RecordCount();

//Pagos x la caja
$buscar = "Select sum(monto_abonado) as total from pagos_extra 
where idcaja=$idcaja and estado <> 6";
$rspagoxcaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tpagos = $rspagoxcaja->RecordCount();
$sub2 =



//deliverys NO rendidos en caja
$consulta = "
	Select *, total_cobrado as totalpend  
	from gest_pagos 
		where 
		cajero=$idusu  
		and estado=1 
		and idcaja=$idcaja 
		and rendido ='N'
		and idempresa = $idempresa
		order by fecha desc
	";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tdelivery = $rs->RecordCount();

$consulta = "
select tipocaja from usuarios where idusu = $idusu
";
$rstipocaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if ($rstipocaj->fields['tipocaja'] == 'V') {
    $cajavisible = 'S';
} else {
    $cajavisible = 'N';
}


$consulta = "SELECT * FROM preferencias_caja WHERE  idempresa = $idempresa ";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$caja_compartida = trim($rsprefcaj->fields['caja_compartida']);
$usar_turnos_caja = trim($rsprefcaj->fields['usa_turnos']);
$turno_automatico_caja = trim($rsprefcaj->fields['turno_automatico_caja']);

$arrastre_saldo_anterior = trim($rsprefcaj->fields['arrastre_saldo_anterior']);
$tipo_arrastre = trim($rsprefcaj->fields['tipo_arrastre']);
$cierre_caja_email = trim($rsprefcaj->fields['cierre_caja_mail']);

$consulta = "
select arrastre_caja_suc from sucursales where idsucu = $idsucursal limit 1
";
$rssucar = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if ($rssucar->fields['arrastre_caja_suc'] != "DEF") {
    if ($rssucar->fields['arrastre_caja_suc'] == "ACT") {
        $arrastre_saldo_anterior = 'S';
    }
    if ($rssucar->fields['arrastre_caja_suc'] == "INA") {
        $arrastre_saldo_anterior = 'N';
    }
}


$buscar = "
Select monto_apertura 
from caja_super
where  
idcaja=$idcaja";
$rscajbal = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


?>
<strong>Monto Apertura:</strong> <?php echo formatomoneda($rscajbal->fields['monto_apertura']); ?>
<?php if ($arrastre_saldo_anterior != 'S') { ?>
 <a href="caja_cajero_aper_edit.php" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
<?php } ?>
<?php if ($cargar_boleta_encaja == 'S') { ?>
<hr />
Cargar Boleta Deposito: &nbsp;<a href="javascript:void(0);" onclick="cargar_boletas_abrir();" class="btn btn-sm btn-default" title="Boleta Deposito" data-toggle="tooltip" data-placement="right"  data-original-title="Boleta Deposito"><span class="fa fa-gear"></span></a>
<?php } ?>
<hr />
<?php if ($cajavisible == 'S') { ?>
  <div class="row">
      <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Resumen de caja visible</h2>
                
                <div class="clearfix"></div>
            </div>
          <div class="x_content" >

<strong>Balance de Caja:</strong>
<div class="table-responsive">
  <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="left">Totales Sistema</th>

			<th align="center">Monto</th>

		</tr>
	  </thead>
	  <tbody>
		<tr>

			<td align="left">Monto Apertura</td>

			<td align="right"><?php echo formatomoneda($rscajbal->fields['monto_apertura']); ?></td>
		</tr>
		<tr>
<?php
$consulta = "
SELECT sum(gest_pagos.total_cobrado) as total
FROM gest_pagos
where
gest_pagos.estado <> 6
and gest_pagos.idcaja = $idcaja
and gest_pagos.tipomovdinero = 'E'
";
    $rsent = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
SELECT sum(gest_pagos.total_cobrado)*-1 as total
FROM gest_pagos
where
gest_pagos.estado <> 6
and gest_pagos.idcaja = $idcaja
and gest_pagos.tipomovdinero = 'S'
";
    $rssal = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cantidad_acum = 0;
    $total_acum = 0;
    $total_sistema = $rsent->fields['total'] + $rssal->fields['total'] + $rscajbal->fields['monto_apertura'];
    ?>
			<td align="left">(+) Total Ingresos Sistema</td>

			<td align="right"><?php echo formatomoneda($rsent->fields['total']); ?></td>
		</tr>
		<tr>

			<td align="left">(-) Total Egresos Sistema</td>

			<td align="right"><?php echo formatomoneda($rssal->fields['total']); ?></td>
		</tr>
		<tr>

			<td align="left">(=) Total Sistema</td>

			<td align="right"><?php echo formatomoneda($total_sistema); ?></td>
		</tr>


	  </tbody>
<?php
    $consulta = "
select formas_pago.descripcion as formapago, sum(monto) as total
from caja_arqueo_fpagos
inner join formas_pago on formas_pago.idforma = caja_arqueo_fpagos.idformapago
where
caja_arqueo_fpagos.idcaja = $idcaja
and caja_arqueo_fpagos.estado <> 6
and idformapago > 1
group by formas_pago.descripcion
order by formas_pago.descripcion asc
";
    $rsarq = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    $total_efectivo_nacyextra = $rstb->fields['total'] + $rsmonextratot->fields['total'];
    $total_dec_acum += $total_efectivo_nacyextra;
    ?>
	  <thead>
		<tr>
			<th align="left">Totales Declarados</th>

			<th align="center">Monto</th>

		</tr>
        </thead>
        <tbody>
		<tr>

			<td align="left">(+) EFECTIVO</td>

			<td align="right"><?php echo formatomoneda($total_efectivo_nacyextra);  ?></td>
		</tr>
<?php while (!$rsarq->EOF) { ?>
		<tr>

			<td align="left">(+) <?php echo antixss($rsarq->fields['formapago']); ?></td>

			<td align="right"><?php echo formatomoneda($rsarq->fields['total']); ?></td>
		</tr>
<?php
    $total_dec_acum += $rsarq->fields['total'];
    $rsarq->MoveNext();
}


    ?>

		<tr>

			<td align="left">(=) Totales Declarados</td>
			<td align="right"><?php echo formatomoneda($total_dec_acum); ?></td>
		</tr>
        </tbody>
	  <thead>
		<tr>
			<th align="left">Total Declarado - Total Sistema</th>

			<th align="center">Monto</th>

		</tr>
        </thead>
        <tbody>
		<tr>

			<td align="left">(+) Totales Declarado</td>
			<td align="right"><?php echo formatomoneda($total_dec_acum); ?></td>
		</tr>
		<tr>

			<td align="left">(-) Totales Sistema</td>
			<td align="right"><?php echo formatomoneda($total_sistema * -1); ?></td>
		</tr>

        </tbody>
        <tfoot>
		<tr>
<?php

    $diferencia = $total_dec_acum - $total_sistema;
    if ($diferencia < 0) {
        $resultado = "FALTANTE";
        $color = "#F00";
    }
    if ($diferencia > 0) {
        $resultado = "SOBRANTE";
        $color = "#00F";
    }
    if ($diferencia == 0) {
        $resultado = "SIN DIFERENCIAS";
        $color = "#090";
    }
    ?>
			<td align="left"  style="color:<?php echo $color ?>;">(=) Diferencia (<?php echo $resultado; ?>) </td>
			<td align="right" style="color:<?php echo $color ?>;"><?php echo formatomoneda($diferencia); ?></td>
		</tr>
	  </tfoot>

    </table>
</div>
<br />


<strong>Informaciones Relevantes:</strong>
<div class="table-responsive">
  <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="left">Informacion</th>
			<th align="center">Cantidad</th>
			<th align="center">Total</th>

		</tr>
	  </thead>
	  <tbody>

		<tr>
<?php

    //descuento x productos
    $consulta = "
Select count(ventas.idventa) as cantidad,  sum(ventas_detalles.descuento) as total
from ventas_detalles 
inner join ventas on ventas.idventa=ventas_detalles.idventa 
inner join productos on productos.idprod_serial=ventas_detalles.idprod
where 
ventas.estado <> 6
and ventas_detalles.descuento > 0
and ventas.idcaja=$idcaja
";
    $rsdescp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?>
			<td align="left">Descuentos sobre Productos</td>
			<td align="center"><?php echo formatomoneda($rsdescp->fields['cantidad']); ?></td>
			<td align="right"><?php echo formatomoneda($rsdescp->fields['total']); ?></td>
		</tr>
		<tr>
<?php

    // descuentos x factura
    $consulta = "
select count(ventas.idventa) as cantidad, sum(descneto) as total
from ventas 
where
descneto > 0
and idcaja = $idcaja
and estado <> 6
";
    //echo $consulta;
    $rsdesctot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?>
			<td align="left">Descuentos sobre Facturas</td>
			<td align="center"><?php echo formatomoneda($rsdesctot->fields['cantidad']); ?></td>
			<td align="right"><?php echo formatomoneda($rsdesctot->fields['total']); ?></td>
		</tr>
		<tr>
<?php
    $consulta = "
select sum(totalcobrar) as total, count(idventa) as cantidad
from ventas 
where
ventas.idcaja = $idcaja
and ventas.estado <> 6
";
    $rsvvig = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?>
			<td align="left">Ventas Vigentes</td>
			<td align="center"><?php echo formatomoneda($rsvvig->fields['cantidad']); ?></td>
			<td align="right"><?php echo formatomoneda($rsvvig->fields['total']); ?></td>
		</tr>
		<tr>
<?php
    $consulta = "
select sum(totalcobrar) as total, count(idventa) as cantidad
from ventas 
inner join usuarios on ventas.anulado_por = usuarios.idusu
where
ventas.idcaja = $idcaja
and ventas.estado = 6
";
    $rsanul = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?>
			<td align="left">Ventas Anuladas</td>
			<td align="center"><?php echo formatomoneda($rsanul->fields['cantidad']); ?></td>
			<td align="right"><?php echo formatomoneda($rsanul->fields['total']); ?></td>
		</tr>

<?php
    $consulta = "
select sum(totalcobrar) as total, count(idventa) as cantidad
from ventas 
where
ventas.idcaja = $idcaja
and ventas.estado <> 6
and tipo_venta = 2
";
    $rsvcred = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?>
		<tr>
		  <td align="left">Ventas a Credito</td>
			<td align="center"><?php echo formatomoneda($rsvcred->fields['cantidad']); ?></td>
			<td align="right"><?php echo formatomoneda($rsvcred->fields['total']); ?></td>
		  </tr>
		<tr>
<?php


    $consulta = "
select count(idtmpventares_cab) as cantidad, sum(monto) as total
from tmp_ventares_cab
inner join usuarios on tmp_ventares_cab.anulado_por = usuarios.idusu
where
tmp_ventares_cab.estado = 6
and tmp_ventares_cab.anulado_idcaja = $idcaja
and tmp_ventares_cab.monto > 0
";
    //echo $consulta;
    $rspedborra = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?>

			<td align="left">Pedidos Borrados</td>
			<td align="center"><?php echo formatomoneda($rspedborra->fields['cantidad']); ?></td>
			<td align="right"><?php echo formatomoneda($rspedborra->fields['total']); ?></td>
		</tr>
		<tr>
<?php
    $consulta = "
select sum(cantidad) as cantidad, sum(subtotal) as total
from 
(
select sum(cantidad) as cantidad, sum(subtotal) as subtotal
from tmp_ventares
inner join usuarios on tmp_ventares.borrado_mozo_por = usuarios.idusu
inner join productos on productos.idprod=tmp_ventares.idproducto
where
tmp_ventares.borrado = 'S'
and tmp_ventares.borrado_mozo = 'S'
and tmp_ventares.borrado_mozo_idcaja = $idcaja

UNION

select  sum(cantidad) as cantidad, sum(subtotal) as subtotal
from tmp_ventares_bak
inner join usuarios on tmp_ventares_bak.borrado_mozo_por = usuarios.idusu
inner join productos on productos.idprod=tmp_ventares_bak.idproducto
where
tmp_ventares_bak.borrado = 'S'
and tmp_ventares_bak.borrado_mozo = 'S'
and tmp_ventares_bak.borrado_mozo_idcaja = $idcaja
) pedbor
";
    //echo $consulta;
    $rspedborraprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?>
			<td align="left">Productos Borrados de Pedidos Activos</td>
			<td align="center"><?php echo formatomoneda($rspedborraprod->fields['cantidad']); ?></td>
			<td align="right"><?php echo formatomoneda($rspedborraprod->fields['total']); ?></td>
		</tr>

	  </tbody>

    </table>
</div>
<br />




				  </div>
					<!-------------------X CONTENT---------->
				</div>
				<!-------------------X PANEL---------->
			</div>
          </div>
<?php }  ?>

<div class="clearfix"></div>
  <div class="row">
      <div class="col-md-12 col-sm-12 col-xs-12 text-center">
        <div class="x_panel">

          <div class="x_content" >
           <button type="cierreform" class="btn btn-success" onclick="mostrarcuadro(2);"><span class="fa fa-sign-out"></span> Cerrar Caja</button>
 
				<form id="cierreform" action="" method="post">
					<input type="hidden" value="<?php echo $idcaja; ?>" name="occierrecaja" id="occierrecaja" />
                    <input type="hidden" name="MM_cierre" value="form_cierre" />
				</form>
           </div>
        </div>
      </div>
    </div>


	<div class="clearfix"></div>
</div>