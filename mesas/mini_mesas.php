 <?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
require_once("../includes/rsusuario.php");
if ($usar_cod_mozo == 'S') {
    $usar_codigo = 1;

} else {
    $usar_codigo = 0;
}
$buscar = "Select mesas.*, mesas_estados_lista.*,
 mesas_estados_lista.color_indicador, mesas_estados_lista.color_texto, mesas_estados_lista.descripcion as mesa_estado_desc,
     (
    SELECT 
      mesas_pedidos_tipo.tipopedido as tipo_pedido_descripcion
    FROM 
      mesas_pedidos 
      INNER JOIN mesas_atc on mesas_atc.idatc = mesas_pedidos.idatc 
      inner join mesas_pedidos_tipo on mesas_pedidos_tipo.idtipopedido = mesas_pedidos.tipo_pedido
    WHERE 
      mesas_atc.estado = 1
      and mesas_pedidos.estado = 1
      and mesas_atc.idmesa = mesas.idmesa
     order by mesas_pedidos.idpedido desc
     limit 1
     ) as tipo_pedido_descripcion,
     (
    SELECT 
      mesas_pedidos.tipo_pedido
    FROM 
      mesas_pedidos 
      INNER JOIN mesas_atc on mesas_atc.idatc = mesas_pedidos.idatc 
      inner join mesas_pedidos_tipo on mesas_pedidos_tipo.idtipopedido = mesas_pedidos.tipo_pedido
    WHERE 
      mesas_atc.estado = 1
      and mesas_pedidos.estado = 1
      and mesas_atc.idmesa = mesas.idmesa
     order by mesas_pedidos.idpedido desc
     limit 1
     ) as tipo_pedido
 from mesas 
 inner join mesas_estados_lista on mesas_estados_lista.idestadomesa=mesas.estado_mesa
 inner join salon on salon.idsalon = mesas.idsalon
 where 
 mesas.idsucursal=$idsucursal 
 $addsalonmesas 
 and mesas.estadoex = 1
 and salon.estado_salon = 1
 order by mesas.numero_mesa asc
 ";
//echo $buscar;
$rsmesas = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tmesas = $rsmesas->RecordCount();
if ($tmesas > 0) {
    while (!$rsmesas->EOF) {
        $idmesita = intval($rsmesas->fields['idmesa']);
        $idmesa = $idmesita;
        $estadomesa = intval($rsmesas->fields['estado_mesa']);
        $mesa_estado_desc = htmlentities($rsmesas->fields['mesa_estado_desc']);
        if ($usar_iconos == 'S') {
            if ($estadomesa == 1) {
                $img = "gfx/mesa_libre_01_80x80.png";
            }
            if ($estadomesa == 2) {
                $img = "gfx/mesa_ocupada_01_80x80.png";
                $buscar = "Select nombre_mesa from mesas_atc where idmesa=$idmesita and estado=1";
                $rcon = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $mesanombre = trim($rcon->fields['nombre_mesa']);
            }
            if ($estadomesa == 3) {
                $img = "gfx/mesa_liberando_01_80x80.png";
            }
            if ($estadomesa == 4) {
                $img = "gfx/mesa_reservada_01_80x80.png";
            }
            if ($estadomesa == 5) {
                $img = "gfx/mesa_nodisponible_01_80x80.png";
            }
            if ($estadomesa == 6) {
                $img = "gfx/mesa_nodisponible_01_80x80.png";
            }
        } else {
            $color = trim($rsmesas->fields['color_indicador']);
            if ($estadomesa == 2) {
                $img = "gfx/mesa_ocupada_01_80x80.png";
            } else {
                $img = "";
            }


            $buscar = "Select nombre_mesa from mesas_atc where idmesa=$idmesita and estado=1";
            $rcon = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $mesanombre = htmlentities(trim($rcon->fields['nombre_mesa']));



        }
        if ($estadomesa == 6) {
            //Si o si tiene un atc
            $idagrupado = intval($rsmesas->fields['agrupado_con']);
            $buscar = "Select numero_mesa from mesas where idmesa= $idagrupado";
            $rsagg = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            //$buscar="select numero_mesa from mesas_atc where idmesa=$idmesita and estado=1";


        }
        ?><?php if ($usar_iconos == 'S') {?>
<a id="mesa_n_<?php echo $rsmesas->fields['idmesa'] ?>" href="javascript:void(0);" <?php if ($rsmesas->fields['estado_mesa'] != 6) {?> onClick="controlacod(<?php echo $rsmesas->fields['idmesa'] ?>,<?php echo $usar_codigo ?>,<?php echo $rsmesas->fields['numero_mesa'] ?>)" <?php }?>>
    <div style="width: 100px; height: 100px; margin-left: 5px; float: left" align="center"  ><img src="<?php echo $img; ?>"/><br /> <?php echo $rsmesas->fields['numero_mesa'] ?> 
    <?php if ($rsmesas->fields['estado_mesa'] == 6) {
        //Vemos con quien esta agrupada para mostrar
        echo 'AGR C/:'. $rsagg->fields['numero_mesa'];
    }
            ?>
    </div>
</a>
<?php } else {

    // armar iconos mesa sin espacios para optimizar ancho de banda y memoria

    // limpiar variable
    $mesa_icono = '';

    $color_texto = $rsmesas->fields['color_texto'];

    // acciones
    if ($rsmesas->fields['estado_mesa'] != 6) {
        $mesa_accion = 'onClick="controlacod('.$rsmesas->fields['idmesa'].','.$usar_codigo.','.$rsmesas->fields['numero_mesa'].')"';
    }
    // pendiente rendicion
    if ($rsmesas->fields['estado_mesa'] == 8) {
        /*$consulta="
        SELECT
            ventas.idventa,
            ventas.idcaja,
            mesas.numero_mesa,
            mesas.idmesa,
            mesas_atc.idatc,
            ventas.factura,
            usuarios_ven.usuario AS usuario_venta,
            usuarios_caj.usuario AS usuario_caja
        FROM ventas
        INNER JOIN mesas_atc ON mesas_atc.idatc = ventas.idatc
        INNER JOIN mesas ON mesas.idmesa = mesas_atc.idmesa
        INNER JOIN caja_super ON caja_super.idcaja = ventas.idcaja
        INNER JOIN usuarios AS usuarios_ven ON usuarios_ven.idusu = caja_super.cajero
        INNER JOIN usuarios AS usuarios_caj ON usuarios_caj.idusu = ventas.registrado_por
        LEFT JOIN ventas_rendido ON ventas_rendido.idventa = ventas.idventa AND ventas_rendido.estado = 1
        WHERE ventas.estado <> 6
            AND mesas_atc.estado = 3
            AND ventas_rendido.idventa IS NULL
            AND mesas.estado_mesa = 8
            AND mesas.idmesa = $idmesa
        ORDER BY ventas.idventa DESC
        LIMIT 1;
        ";
        $rspendrend=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

        $mensaje_modal="'";
        $mensaje_modal.='MESA #'.$rsmesas->fields['numero_mesa'].' BLOQUEADA\',\'LA <STRONG>MESA #'.$rsmesas->fields['numero_mesa'].'</STRONG> SE ENCUENTRA EN ESTADO <STRONG>PENDIENTE DE RENDICION</STRONG><hr />';
        $mensaje_modal.='<STRONG>Mesa Numero:</STRONG> '.$rsmesas->fields['numero_mesa'].'<br />';
        $mensaje_modal.='<STRONG>Estado:</STRONG> PENDIENTE DE RENDICION<hr />';

        $mensaje_modal.='<STRONG>Idmesa:</STRONG> '.$rsmesas->fields['idmesa'].'<br />';
        $mensaje_modal.='<STRONG>Idatc:</STRONG> '.$rsmesas->fields['idatc'].'<br />';
        $mensaje_modal.='<STRONG>Idventa (pendiente rendicion):</STRONG> '.$rspendrend->fields['idventa'].'<br />';
        if(trim($rspendrend->fields['factura']) != ''){
            $mensaje_modal.='<STRONG>Factura (pendiente rendicion):</STRONG> '.$rspendrend->fields['factura'].'<br />';
        }
        $mensaje_modal.='<STRONG>Idcaja:</STRONG> '.$rspendrend->fields['idcaja'].'<br />';
        $mensaje_modal.='<STRONG>Cajero:</STRONG> '.$rspendrend->fields['usuario_caja'].'<br />';
        $mensaje_modal.='<STRONG>Usuario Venta:</STRONG> '.$rspendrend->fields['usuario_venta'].'<br />';
        $mensaje_modal.="'";*/

        /*$mensaje_modal="'";
        $mensaje_modal.='MESA #'.$rsmesas->fields['numero_mesa'].' BLOQUEADA\',\'LA <STRONG>MESA #'.$rsmesas->fields['numero_mesa'].'</STRONG> SE ENCUENTRA EN ESTADO <STRONG>PENDIENTE DE RENDICION</STRONG><hr />';
        $mensaje_modal.='<STRONG>Mesa Numero:</STRONG> '.$rsmesas->fields['numero_mesa'].'<br />';
        $mensaje_modal.='<STRONG>Estado:</STRONG> PENDIENTE DE RENDICION<hr />';
        //$mensaje_modal.='<a href="javascript:mas_info_mesa('.$idmesa.');void(0);" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Mas Informacion</a><br /><div id="masinfo_bloq"></div>';
        $mensaje_modal.="'";
        $mesa_accion='onClick="alerta_modal('.$mensaje_modal.');"';*/
        $mesa_accion = 'onClick="info_mesa_bloq('.$idmesa.');"';
    }
    // abre cuadrito
    $mesa_icono .= '<a id="mesa_n_'.$rsmesas->fields['idmesa'].'" href="javascript:void(0);" '.$mesa_accion.' >';
    $mesa_icono .= '<div style="background-color:'.trim($color).';color:'.$color_texto.';" title="'.$mesa_estado_desc.'" data-toggle="tooltip" data-placement="right"  data-original-title="'.$mesa_estado_desc.'" class="cuadritomesanum">';
    if (intval($rsmesas->fields['tipo_pedido']) > 0) {
        $mesa_icono .= '<span style="font-size:11px;" class="titilar">'.$rsmesas->fields['tipo_pedido_descripcion'].'</span>';
    }
    $mesa_icono .= '<br />';
    $mesa_icono .= $rsmesas->fields['numero_mesa'];
    if ($mesanombre != '') {
        $mesa_icono .= '<span style="font-size:14px;color:'.$color_texto.';"><br />'.$mesanombre.'</span>';
    }
    if ($rsmesas->fields['estado_mesa'] == 6) {
        $mesa_icono .= '<a href="javascript:void(0);" onmouseup="desagrupar('.$rsmesas->fields['idmesa'].');">AGR M:'.$rsagg->fields['numero_mesa'].'</a>';
    }
    // cierra cuadrito
    $mesa_icono .= '</div></a>';

    // muestra cuadrito
    echo $mesa_icono.$saltolinea;



    ?>
<?php } ?>
<?php $rsmesas->MoveNext();
    } ?>
<input type="hidden" id="mapamesa" value="mapa"  />
<?php }?>
