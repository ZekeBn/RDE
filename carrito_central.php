 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");
//cargamos los valores basicos del pedido
//$buscar="Select regid from pedidos_eventos  ";
//$rga=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
if (intval($idreg) == 0) {
    //ver para buscar

}
$consulta = "
    SELECT *, (SELECT fechahora FROM usuarios_accesos where idusuario = usuarios.idusu order by fechahora desc limit 1) as ultacceso,
    (select nombre from sucursales where idsucu=usuarios.sucursal and idempresa=$idempresa) as sucuchar
    FROM usuarios
    where
    estado = 1
    and idempresa = $idempresa
    and idusu in (select idusu from modulo_usuario where idempresa = $idempresa and estado = 1 and submodulo = 22)
    and idusu=$idusu
    order by usuario asc
    ";

$rsccajacon = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rsccajacon->fields['idusu']) > 0) {
    $usar_cod_mozo = 'N';
    $escajero == 'S';
}
if ($escajero == 'S') {
    $add = " and tmp_ventares.idtmpventares_cab in (select idtmpventares_cab from tmp_ventares_cab where idatc=$idatc)  ";

} else {
    $add = " and tmp_ventares.usuario = $idusu ";
}

//Traemos las preferencias para la empresa
$buscar = "Select carry_out from preferencias where idempresa=$idempresa ";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$carry_out = trim($rspref->fields['carry_out']);


//Cliente x defecto
$buscar = "Select * from cliente where borrable='N' and idempresa=$idempresa";
$rsoclci = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$domicilio = intval($_COOKIE['dom_deliv']);
if ($domicilio > 0) {
    $buscar = "Select * from cliente_delivery inner join cliente_delivery_dom
        on cliente_delivery.idclientedel=cliente_delivery_dom.idclientedel
        where iddomicilio=$domicilio and cliente_delivery.idempresa=$idempresa limit 1
        ";
    $rscasa = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $direccion = trim($rscasa->fields['direccion']);
    $telefono = trim($rscasa->fields['telefono']);
    $nombreclidel = trim($rscasa->fields['nombres']);


}


$consulta = "
    select tmp_ventares.*, productos.descripcion, sum(cantidad) as total, sum(precio) as totalprecio, sum(subtotal) as subtotal,
    (select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
    (select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado
    from tmp_ventares 
    inner join productos on tmp_ventares.idproducto = productos.idprod_serial
    where 
    registrado = 'N'
    and tmp_ventares.usuario = $idusu
    and tmp_ventares.borrado = 'N'
    and tmp_ventares.finalizado = 'N'
    and tmp_ventares.idsucursal = $idsucursal
    and tmp_ventares.idempresa = $idempresa

    group by descripcion, receta_cambiada
    ";
//echo $consulta;exit;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$tr = $rs->RecordCount();
$buscar = "
    Select gest_zonas.idzona,descripcion,costoentrega
    from gest_zonas
    where 
    estado=1 
    and gest_zonas.idempresa = $idempresa 
    and gest_zonas.idsucursal = $idsucursal
    order by descripcion asc
    ";
$rszonas = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$buscar = "
    select * from cliente where borrable = 'N' and idempresa = $idempresa limit 1
    ";
$rsclipred = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

//echo $tr;

?>
<?php if ($tr > 0) {?>
    <table class="table table-hover">
      <thead>
        <tr>
          <th colspan="5" align="center" valign="middle" bgcolor="#E7E7E7"><?php echo $idreg;?></th>
        </tr>
        <tr>
          <th align="center" valign="middle" bgcolor="#E7E7E7">Producto</th>
          <th align="center" valign="middle" bgcolor="#E7E7E7">Cantidad</th>
            <th align="center" valign="middle" bgcolor="#E7E7E7">Sub Total</th>
          <th align="center" valign="middle" bgcolor="#E7E7E7">Obs/Com</th>
            <th align="center" valign="middle" bgcolor="#E7E7E7"><?php if ($tr > 0) {?>
                <button type="button" class="btn btn-danger btn-xs" onClick="borrar_todo();">Vaciar Carrito &nbsp;<span class="fa fa-delete fa-2x"></span></button>
                <?php }?></th>
        </tr>
      </thead>

      <tbody>
    <?php while (!$rs->EOF) {
        $com = trim($rs->fields['observacion']);
        $c++;
        $total = $rs->fields['subtotal'];
        $totalacum += $total;
        $des = str_replace("'", "", $rs->fields['descripcion']);
        $idventatmp = $rs->fields['idventatmp'];
        $pp = intval($rs->fields['idproducto']);
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
          <td align="center"><?php echo formatomoneda($rs->fields['subtotal'], 0, 'N'); ?></td>
            <td>
                <input type="text" name="obs_<?php echo $idventatmp ?>" id="obs_<?php echo $idventatmp ?>" value="<?php echo $com?>" onKeyUp="updatecomentario(<?php echo $idventatmp ?>,<?php echo $pp; ?>,this.value);" />
            </td>
          <td align="center"><div class="buttons">
             
            <button type="button" title="Eliminar producto" data-toggle="tooltip" data-placement="right"  data-original-title="Eliminar Producto" class="btn btn-danger btn-xs" onClick="borrar('<?php echo $rs->fields['idproducto']; ?>','<?php echo Capitalizar($des); ?>');"><span class="fa fa-times-circle fa-2x"></span></button>

              </div>
            </td>
        </tr>
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
    
    and tmp_ventares.borrado = 'N'
    and tmp_ventares.finalizado = 'N'
    and tmp_ventares.idsucursal = $idsucursal
    and tmp_ventares.idempresa = $idempresa
    
    )
    ";
    //echo $consulta;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $montototalagregado = $rs->fields['montototalagregados'];
    $totalagregado = $rs->fields['totalagregados'];
    $totalacum += $montototalagregado;

    if ($totalagregado > 0) {
        ?>
        <tr>
          <td height="30">Agregados</td>
          <td align="center"><?php echo formatomoneda($totalagregado, 0); ?></td>
          <td align="center"><?php echo formatomoneda($montototalagregado, 0); ?></td>
          <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
        </tr>
    <?php } ?>
        <tr>
            <td height="39" colspan="4" align="center"><strong><span style="font-size: 16px;color: #DB171A">Total Venta: <?php echo formatomoneda($totalacum, 0); ?><input type="hidden" name="totalventa" id="totalventa" value="<?php echo $totalacum; ?>">
        <input type="hidden" name="totalventa_real" id="totalventa_real" value="<?php echo $totalacum; ?>"></span></strong></td>
            <td align="center">&nbsp;</td>
        </tr>
          <tr>
              <td colspan="5" align="center">
              <form action="cat_pedidos.php" method="post" id="cp1">
                    <button type="button" id="pedidocarrito" name="pedidocarrito" class="btn btn-success btn-xs" onClick="env();" >Registrar Pedido &nbsp;<span class="fa fa-check"></span></button>
                    <input type="hidden" name="mesaoc" id="mesaoc" value="<?php echo $idmesa?>" />
                      <input type="hidden" name="ocmozopedido" id="ocmozopedido"  value="<?php echo $idmozo ?>" />
                      <input type="hidden" name="ocregev" id="ocregev"  value="<?php echo $idreg  ?>" />
                </form>

              </td>
        </tr>
      </tbody>
    </table>

    <br />
      </tbody>
    </table>
<div id="updcoment" style="display: "></div>
<?php } else { ?>
<div align="center">
    <span class="fa fa-warning"></span>&nbsp;&nbsp; No se agregaron productos al carrito.
</div>
<?php }?>
