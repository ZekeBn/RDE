 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "196";
require_once("includes/rsusuario.php");


$idmesa = intval($_POST['idmesa']);

$img = "images/media_rest.jpg";

$consulta = "
update tmp_ventares_cab 
set 
estado = 6 
where 
tmp_ventares_cab.idtmpventares_cab not in (
select idtmpventares_cab 
from tmp_ventares
where 
idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
and idtmpventares_cab is not null
)
and tmp_ventares_cab.idtmpventares_cab not in (
select idtmpventares_cab 
from tmp_ventares_bak
where 
idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
and idtmpventares_cab is not null
)
";
//$conexion->Execute($consulta) or die (errorpg($conexion,$consulta));

$consulta = "
update tmp_ventares_cab 
set 
monto = (
            COALESCE
            (
                (
                    select sum(subtotal) as total_monto
                    from tmp_ventares
                    where
                    tmp_ventares.idempresa = tmp_ventares_cab.idempresa
                    and tmp_ventares.idsucursal = tmp_ventares_cab.idsucursal
                    and tmp_ventares.borrado = 'N'
                    and tmp_ventares.borrado_mozo = 'N'
                    and tmp_ventares.idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
                )
            ,0)
            
        )
WHERE
idmesa = $idmesa
and idventa is null
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "
select * 
from mesas 
inner join salon on salon.idsalon = mesas.idsalon
inner join mesas_atc on mesas_atc.idmesa = mesas.idmesa
where 
mesas.estadoex = 1
and salon.estado_salon = 1
and mesas_atc.idmesa = $idmesa
and mesas_atc.estado = 1
order by numero_mesa asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idmesa = intval($rs->fields['idmesa']);
$idatc = intval($rs->fields['idatc']);
if ($idmesa == 0) {
    echo "<br /><br />Mesa inexistente o cerrada.<br /><br />";
    exit;
}


$consulta = "
select idatc, pin from mesas_atc where estado = 1 and idmesa = $idmesa order by idatc desc limit 1
";
$rsatc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$pin = $rsatc->fields['pin'];
?>
                        
                        
                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="myModalLabel">Detalle de la Mesa <?php echo $rs->fields['numero_mesa']; ?></h4>
                        </div>
                        <div class="modal-body" id="modal_cuerpo">
<?php
if ($idmesa > 0) {
    $consulta = "
    select * 
    from tmp_ventares_cab 
    inner join mesas on mesas.idmesa = tmp_ventares_cab.idmesa
    where 
    tmp_ventares_cab.registrado = 'N' 
    and tmp_ventares_cab.estado <> 6
    and tmp_ventares_cab.idmesa = $idmesa
    order by tmp_ventares_cab.idtmpventares_cab asc
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idmesa = intval($rs->fields['idmesa']);
    $idatc = intval($rs->fields['idatc']);

}
if ($idmesa > 0) {
    $consulta = "
        select *, (select numero_mesa from mesas where mesas.idmesa = tmp_ventares_cab.idmesa) as numero_mesa,
        (select usuario from usuarios where idusu = tmp_ventares_cab.idusu) as operador
        from tmp_ventares_cab
        where
        tmp_ventares_cab.idsucursal = $idsucursal
        and tmp_ventares_cab.finalizado = 'S'
        and tmp_ventares_cab.registrado = 'N'
        and tmp_ventares_cab.estado = 1
        and tmp_ventares_cab.idmesa=$idmesa
        order by tmp_ventares_cab.fechahora asc
        ";
    //echo $consulta;
    //exit;
    $rsmesa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tcuerpo = $rsmesa->RecordCount();
    $numero_mesa = $rsmesa->fields['numero_mesa'];


    $consulta = "
        select sum(monto) as total_cuenta 
        from tmp_ventares_cab 
        inner join mesas on mesas.idmesa = tmp_ventares_cab.idmesa
        where 
        tmp_ventares_cab.registrado = 'N' 
        and tmp_ventares_cab.estado <> 6
        and tmp_ventares_cab.idmesa = $idmesa
        order by tmp_ventares_cab.idtmpventares_cab asc
        ";
    //echo $consulta;
    $rstot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $total_cuenta = $rstot->fields['total_cuenta'];
}

if ($idmesa > 0) {
    ?>                        
                        
 <strong>Total Cuenta:</strong>  <?php echo formatomoneda($total_cuenta, 0, "N"); ?>                     
<br /><br />
<button type="button" class="btn btn-default" id="preticket_imp" onclick="reimprimir_mesa(<?php echo $idmesa ?>);" ><span class="fa fa-print"></span> Imprimir Preticket</button>
 <a href="#" class="btn btn-sm btn-primary" onclick="detallar(<?php echo $idmesa ?>);"><span class="fa fa-search"></span> Vista Consolidada</a>
 <a href="#" class="btn btn-sm btn-default" onclick="detallar_det(<?php echo $idmesa ?>);"><span class="fa fa-search"></span> Vista Detallada</a>
 <hr />
  <a href="cuenta_mesas_qr.php?idmesa=<?php echo $idmesa; ?>" class="btn btn-sm btn-success"><span class="fa fa-qrcode"></span> Cobrar Mesa con QR</a>
  <button type="button" class="btn-btn-round btn-default btn-sm" onclick="document.location.href='mesas_qr_imprime.php?idatc=<?php echo $idatc ?>&mod=196'"><span class="fa fa-print">
            </span>&nbsp;&nbsp;Pin Mesa Smart: <?php echo formatomoneda($pin); ?></button>

<hr />
<?php

    $add = " and tmp_ventares.idtmpventares_cab in (select idtmpventares_cab from tmp_ventares_cab where idatc=$idatc)  ";
    $consulta = "
select productos.idprod_serial, productos.descripcion, sum(cantidad) as total, sum(precio) as totalprecio, sum(subtotal) as subtotal,
(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado
from tmp_ventares 
inner join productos on tmp_ventares.idproducto = productos.idprod_serial
where 
tmp_ventares.idtmpventares_cab in ( 
                                    select idtmpventares_cab 
                                    from tmp_ventares_cab 
                                    where 
                                    /*idsucursal = $idsucursal 
                                    and finalizado = 'S' 
                                    and registrado = 'N' 
                                    and*/ idatc=$idatc 
                                    and estado = 1 
                                    )
and tmp_ventares.borrado = 'N' 
and tmp_ventares.idsucursal = $idsucursal 

group by idprod_serial,descripcion, receta_cambiada
order by  descripcion asc
";
    //echo $consulta;exit;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
  <thead>
    <tr>
      <th height="29" valign="middle" >Producto</th>
      <th valign="middle" >Cantidad</th>
      <th valign="middle" >Sub Total</th>
  

    </tr>
  </thead>
  <tbody>
    <?php while (!$rs->EOF) {

        $c++;
        $total = $rs->fields['subtotal'];
        $totalacum += $total;
        $des = str_replace("'", "", $rs->fields['descripcion']);
        $idcabecera = intval($rs->fields['idtmpventares_cab']);

        $buscar = "select (CASE WHEN idmozo > 0 then idmozo else idusu END) as idunico,(select usuario from usuarios where idusu=idunico) as charusu from tmp_ventares_cab where idtmpventares_cab=$idcabecera limit 1";
        $rsbuscaquien = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        //echo $buscar;
        $quien = $rsbuscaquien->fields['charusu'];
        //Vemos si el producto es combinado, buscamos para mostra las mitades
        $idtipoproducto = intval($rs->fields['idtipoproducto']);
        $idproducto = intval($rs->fields['idproducto']);
        $idventatmp = intval($rs->fields['idventatmp']);
        if ($idtipoproducto == 4) {
            //buscamos las porciones seleccionadas
            $buscar = "select *,descripcion as pcharn 
            from tmp_combinado_componente 
            inner join productos on productos.idprod_serial=tmp_combinado_componente.idprodcombi
            where idatc=$idatc and idmesa=$idmesa and idprodppal=$idproducto and idtmpventares=$idventatmp";
            $rslistacomb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            //echo $buscar.'<br />';
            $tcombi = $rslistacomb->RecordCount();

        }
        $estilo = "";
        $tipoplato = trim($rs->fields['tipo_plato']);
        if ($tipoplato == 'E') {
            $estilo = "background-color:#2E9AFE;font-weight:bold;color:#FFFFF";
        }
        if ($tipoplato == 'F') {
            $estilo = "background-color:green; color:#FFFFF";
            $estilo = "background-color:#81F7D8;font-weight:bold;color:#FFFFF";
        }
        ?>
    <tr style="font-size: 1.2em;">
      <td style="<?php echo $estilo;?>" ><?php echo Capitalizar($rs->fields['descripcion']); ?></td>
      <td align="center"><?php echo formatomoneda($rs->fields['total'], 3, 'N'); ?></td>
      <td align="right"><?php if ($rs->fields['cortesia'] != 'S') {
          echo formatomoneda($rs->fields['subtotal'], 0, 'N');
      } else {
          echo "Cortesia";
      } ?></td>

    </tr>
      <?php if ($tcombi > 0) {?>
      <tr>
         
          <td colspan="5">
            <?php while (!$rslistacomb->EOF) {
                echo "<span class='fa fa-arrow-right'></span>&nbsp;&nbsp;&nbsp;&nbsp;".$rslistacomb->fields['pcharn'].", ";
                $rslistacomb->MoveNext();
            }
          ?>
          </td>
      </tr>
      <?php }?>
      
    <?php $rs->MoveNext();
    } ?>
    <?php

    // buscar si hay agregados y mostrar el total global
    $consulta = "
    SELECT sum(precio_adicional) as montototalagregados , count(idventatmp) as totalagregados
    FROM 
    tmp_ventares_agregado
    where
    idventatmp in (
    select tmp_ventares.idventatmp
    from tmp_ventares 
    where 
    registrado = 'N'
    and tmp_ventares.usuario = $idusu
    and tmp_ventares.borrado = 'N'
    and tmp_ventares.finalizado = 'N'
    and tmp_ventares.idsucursal = $idsucursal
    and tmp_ventares.idempresa = $idempresa
    and tmp_ventares.idmesa = $idmesa
    )
    ";
    //echo $consulta;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $montototalagregado = $rs->fields['montototalagregados'];
    $totalagregado = $rs->fields['totalagregados'];
    $totalacum += $montototalagregado
    ?>

    <tr>
      <td height="39" colspan="5" align="center">
          <h2>Total consumo mesa: <span id="totconsumo"><?php echo formatomoneda($totalacum);?></span> Gs</h2>
     
      
      </td>
    </tr>

  </tbody>
</table>
</div>
<?php
} else {
    echo "<br /><br /><strong>Mesa sin productos cargados.</strong><br /><br />";
}
?>


</div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                          
                        </div>
