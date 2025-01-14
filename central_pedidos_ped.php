 <?php
/*-----------------------------------
02/10/2021
Se agrega cotizacion

-------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "333";
require_once("includes/rsusuario.php");

$usa_descuento = $rsco->fields['usa_descuento'];

$idpedido = intval($_REQUEST['idpedido']);




// preferencias caja
$consulta = "SELECT usa_motorista, obliga_motorista, usa_canalventa,
usa_orden_compra, obliga_orden_compra
 FROM preferencias_caja WHERE  idempresa = $idempresa ";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$usa_motorista = trim($rsprefcaj->fields['usa_motorista']);
$obliga_motorista = trim($rsprefcaj->fields['obliga_motorista']);
$usa_canalventa = trim($rsprefcaj->fields['usa_canalventa']);
$usar_oc = trim($rsprefcaj->fields['usa_orden_compra']);
$obligar_oc = trim($rsprefcaj->fields['obliga_orden_compra']);

$valido = "S";
$errores = "";

$consulta = "
select tmp_ventares_cab.*, canal.canal,
(select nombre from sucursales where idsucu = tmp_ventares_cab.idsucursal) as sucursal
from tmp_ventares_cab
inner join canal on canal.idcanal =  tmp_ventares_cab.idcanal
where 
tmp_ventares_cab.estado <> 6
and tmp_ventares_cab.finalizado = 'S'
and tmp_ventares_cab.idtmpventares_cab = $idpedido
order by tmp_ventares_cab.fechahora desc
";
//echo $consulta;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$ruc = $rs->fields['ruc'];
$idclienteped = intval($rs->fields['idclienteped']);
$ideventoped = intval($rs->fields['ideventoped']);
//$idsucursal_clie=intval($rs->fields['idsucursal_clie']);

$consulta = "
SELECT idformapagoped, idtmpventares_cab, idformapago, estado, pago_confirmado
FROM tmp_ventares_cab_fpag
WHERE 
idtmpventares_cab = $idpedido
and estado = 1
";
$rsfpagped = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$total_reg = $rsfpagped->RecordCount();
$pago_confirmado = $rsfpagped->fields['pago_confirmado'];
if ($total_reg > 1) {
    $pago_mixto = 'S';
} else {
    $pago_mixto = 'N';
}

// marcar como notificado
$consulta = "
update tmp_ventares_cab set notificado = 'S' where idtmpventares_cab = $idpedido
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


if ($idclienteped == 0) {
    $consulta_cli = "
    select * from cliente where ruc = '$ruc' and estado = 1 order by idcliente asc limit 1
    ";
    $rscli = $conexion->Execute($consulta_cli) or die(errorpg($conexion, $consulta_cli));
    $idcliente = intval($rscli->fields['idcliente']);
    // si no existe el cliente
    if ($idcliente == 0) {
        // si el ruc es generico
        if ($ruc == '44444401-7') {
            // restaura si se borro
            $consulta = "update cliente set estado = 1 where borrable = 'N' and estado = 6 and ruc = '$ruc'";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        } else {
            // busca si existe pero se borro
            $consulta = "
            select * from cliente where ruc = '$ruc' and estado = 6 order by idcliente desc limit 1
            ";
            $rscli_bor = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idcliente_bor = intval($rscli_bor->fields['idcliente']);
            // si existe pero esta borrado
            if ($idcliente_bor > 0) {
                // restaura
                $consulta = "update cliente set estado = 1 where ruc = '$ruc' and idcliente = $idcliente_bor";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            } else {
                /*$ruc_txt=htmlentities($ruc);
                echo "CLIENTE INEXISTENTE! RUC: $ruc_txt";
                exit;*/


                //busca el proximo id
                $buscar = "select max(idcliente) as mayor from cliente";
                $rsmay = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $mayor = intval($rsmay->fields['mayor']) + 1;

                // datos necesarios para crear cliente
                $nombres = antisqlinyeccion($rs->fields['nombre_deliv'], "text");
                $apellidos = antisqlinyeccion($rs->fields['apellido_deliv'], "text");
                $direclie = antisqlinyeccion($rs->fields['direccion'], "text");
                $telfo = antisqlinyeccion($rs->fields['telefono'], "text");
                $razon = antisqlinyeccion($rs->fields['razon_social'], "text");
                $ruc = antisqlinyeccion($rs->fields['ruc'], "text");

                $parametros_array = [
                    'idclientetipo' => 1,
                    'ruc' => $rs->fields['ruc'],
                    'razon_social' => $rs->fields['razon_social'],
                    'documento' => $_POST['dc'],
                    'fantasia' => $_POST['fantasia'],
                    'nombre' => $rs->fields['nombre_deliv'],
                    'apellido' => $rs->fields['apellido_deliv'],
                    'idvendedor' => '',
                    'sexo' => '',
                    'nombre_corto' => $_POST['nombre_corto'],
                    'idtipdoc' => $_POST['idtipdoc'],


                    'telefono' => $rs->fields['telefono'],
                    'celular' => $_POST['cel'],
                    'email' => $_POST['ema'],
                    'direccion' => $rs->fields['direccion'],
                    'comentario' => $_POST['comentario'],
                    'fechanac' => $_POST['fechanac'],

                    'ruc_especial' => $_POST['ruc_especial'],
                    'idsucursal' => $idsucursal,
                    'idusu' => $idusu,


                ];

                $res = validar_cliente($parametros_array);
                //echo $res['valido'];
                if ($res['valido'] != 'S') {
                    $valido = $res['valido'];
                    $errores .= nl2br($res['errores']);
                    echo $errores;
                    exit;
                }
                //echo $valido;exit;
                if ($valido == 'S') {
                    // inserta el cliente
                    $insertar = "
                    Insert into cliente 
                    (idcliente,idempresa,nombre,apellido,ruc,documento,direccion,celular,razon_social,diplomatico,carnet_diplomatico)
                    values
                    ($mayor,1,$nombres,$apellidos,$ruc,0,$direclie,$telfo,$razon,'N',NULL)
                    ";
                    //$conexion->Execute($insertar) or die(errorpg($conexion,$insertar));
                    $res = registrar_cliente($parametros_array);
                    //print_r($res);exit;
                    $idcliente = $res['idcliente'];
                    //$idclienteped=$idcliente;
                }
            }
        }
        // vuelve a consultar
        $rscli = $conexion->Execute($consulta_cli) or die(errorpg($conexion, $consulta_cli));
    }
} else {
    $consulta_cli = "
    select * from cliente where idcliente = $idclienteped limit 1
    ";
    $rscli = $conexion->Execute($consulta_cli) or die(errorpg($conexion, $consulta_cli));
    $idcliente = intval($rscli->fields['idcliente']);
}
if (intval($idcliente) == 0) {
    $ruc_txt = htmlentities($ruc);
    echo "CLIENTE INEXISTENTE! RUC: $ruc_txt, ID: $idcliente";
    exit;
} else {
    $consulta_cli = "
    select * from cliente where idcliente = $idcliente limit 1
    ";
    $rscli = $conexion->Execute($consulta_cli) or die(errorpg($conexion, $consulta_cli));
    $idcliente = intval($rscli->fields['idcliente']);
    $dias_credito = intval($rscli->fields['dias_credito']);
    //echo "eeeee".$dias_credito;exit;
    if ($dias_credito > 0) {
        $fecha = date('Y-m-d');
        $fechavence = date("Y-m-d", strtotime("+$dias_credito day", strtotime($fecha)));
        $read = " readonly ";
    } else {
        $read = "";
        $fechavence = date("Y-m-d");
    }
}

$ano = date("Y");
// busca si existe algun registro
$buscar = "
Select idsuc, numfac as mayor 
from lastcomprobantes 
where 
idsuc=$factura_suc 
and pe=$factura_pexp 
and idempresa=$idempresa 
order by ano desc 
limit 1";
$rsfactura = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//$maxnfac=intval(($rsfactura->fields['mayor'])+1);
// si no existe inserta
if (intval($rsfactura->fields['idsuc']) == 0) {
    $consulta = "
    INSERT INTO lastcomprobantes
    (idsuc, factura, numfac, recibo, numrec, tickete, numtk, ano, pe, numcheque, secuencia_cancela, 
    numhoja, hojalevante, idempresa) 
    VALUES
    ($factura_suc, 0, 0, NULL, 0, NULL, 0, $ano, $factura_pexp, NULL, 
    NULL, 0, '', $idempresa)
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}
$ultfac = intval($rsfactura->fields['mayor']);
if ($ultfac == 0) {
    $maxnfac = 1;
} else {
    $maxnfac = intval(($rsfactura->fields['mayor']) + 1);
}
$parte1 = intval($factura_suc);
$parte2 = intval($factura_pexp);
if ($parte1 == 0 or $parte2 == 0) {
    $parte1f = '001';
    $parte2f = '001';
} else {
    $parte1f = agregacero($parte1, 3);
    $parte2f = agregacero($parte2, 3);
}

?><div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="center">Pedido N#</th>
            <th align="center">Canal</th>

            <th align="center">Monto</th>
            <th align="center">Fecha/hora</th>
            <th align="center">Sucursal</th>


        </tr>
      </thead>
      <tbody>

        <tr>

            <td align="center"><?php echo intval($rs->fields['idtmpventares_cab']); ?></td>

            <td align="center"><?php echo antixss($rs->fields['canal']); ?></td>


            <td align="center"><?php echo formatomoneda($rs->fields['monto']);
$mt = floatval($rs->fields['monto']);?></td>
            <td align="center"><?php if ($rs->fields['fechahora'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['fechahora']));
            }  ?></td>
            <td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>

        </tr>

      </tbody>
    </table>
</div>
<br />

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">RUC</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input name='ruc' id='ruc' type='text' value='<?php echo antixss($rscli->fields['ruc']); ?>' readonly class="form-control" onClick="busca_cliente(<?php echo $idpedido; ?>);" style="cursor: pointer;" />
    <input name='idcliente' id='idcliente' type='hidden' value='<?php echo $idcliente ?>' />
    <input name='idsucursal_clie' id='idsucursal_clie' type='hidden' value='<?php echo $idsucursal_clie ?>' />
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Razon Social</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input name='cliente' id='cliente' type='text' value='<?php echo antixss($rscli->fields['razon_social']); ?>' readonly class="form-control" onClick="busca_cliente(<?php echo $idpedido; ?>);"  style="cursor: pointer;" />
    </div>
</div>


<div class="clearfix"></div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Forma de Pago </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php


// opciones
$opciones_extra = [
    'Pago Mixto' => '3',
];

// consulta
$consulta = "
SELECT idforma, descripcion
FROM formas_pago
where
estado = 1
order by descripcion asc
 ";

// valor seleccionado
if (isset($_POST['mediopago'])) {
    $value_selected = htmlentities($_POST['mediopago']);
} else {
    if (intval($rsfpagped->fields['idformapago']) > 0) {
        $value_selected = $rsfpagped->fields['idformapago'];
    } else {
        $value_selected = 1;
    }
    if ($pago_mixto == 'S') {
        $value_selected = 3;
    }

}

if ($pago_confirmado == 'S') {
    $campo_pago_dis = ' readonly ';
    $campo_pago_sty = ' style="display:none;"  ';
    $consulta_sel = "
    SELECT idforma, descripcion
    FROM formas_pago
    where
    estado = 1
    and idforma = ".intval($rsfpagped->fields['idformapago'])."
    limit 1
     ";
    $rsfpconf = $conexion->Execute($consulta_sel) or die(errorpg($conexion, $consulta_sel));
    $descripcion_fp = $rsfpconf->fields['descripcion'];
    echo '<input type="text" name="fp_conf" id="fp_conf" value="'.$descripcion_fp.'" placeholder="Forma Pago" class="form-control" disabled />';
} else {
    $campo_pago_dis = '';
    $campo_pago_sty = '';
}



// parametros
$parametros_array = [
    'nombre_campo' => 'mediopago',
    'id_campo' => 'mediopago',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idforma',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control" '.$campo_pago_sty,
    'acciones' => ' required="required" onchange="forma_pago(this.value);" '.$campo_pago_dis,
    'autosel_1registro' => 'S',
    'opciones_extra' => $opciones_extra

];

// construye campo
echo campo_select($consulta, $parametros_array);


if ($value_selected == 3) {
    $consulta = "
        delete from carrito_cobros_ventas
        where 
        registrado_por = $idusu
        ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $monto_fpag = floatval($rs->fields['monto']);
    $consulta = "
        insert into carrito_cobros_ventas
        (idformapago, monto_forma, registrado_por, registrado_el)
        select idformapago, monto_forma, $idusu, '$ahora'
        from tmp_ventares_cab_fpag
        where 
        idtmpventares_cab = $idpedido
        ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    //echo $consulta;exit;
    echo "<script> forma_pago(3); </script>";
}

?>
    </div>
</div>


<div  id="pago_mixto" style="display:none;">
<div class="clearfix"></div>
<br />
<div class="table-responsive">


    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead class="thead-dark">
                <tr>
                    <th>Forma Pago</th>
                    <th>Monto</th>
                    <th>Datos</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><?php // consulta
$consulta = "
SELECT idforma, descripcion
FROM formas_pago
where
estado = 1
and idforma <> 7
and idforma <> 8
order by descripcion asc
 ";

// valor seleccionado
if (isset($_POST['idforma_mixto'])) {
    $value_selected = htmlentities($_POST['idforma_mixto']);
} else {
    $value_selected = "1";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idforma_mixto',
    'id_campo' => 'idforma_mixto',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idforma',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  onchange="forma_pago_mixsel(this.value);" ',
    'autosel_1registro' => 'S'

];
//print_r($parametros_array);
// construye campo
echo campo_select($consulta, $parametros_array);
?></td>
                    <td><input name="idforma_mixto_monto" id="idforma_mixto_monto" type="text" value="<?php if ($montototal - $carrito_cobros > 0) {
                        echo $montototal - $carrito_cobros;
                    } ?>" class="form-control" style="text-align:right;" /></td>
<?php if ($facturador_electronico == 'S') { ?>
 <td>
<div class="clearfix"></div>
<?php
$consulta = "
select id, descripcion
from 
denominacion_tarjeta 
where 
id <> 99
order by id asc
";
    $rsdenotarj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?>
<div class="col-md-12 col-sm-12 form-group" id="iddenominaciontarjeta_mixsel_box" style="display: none;">

<select id="iddenominaciontarjeta_mixsel" name="iddenominaciontarjeta" class="form-control">
    <option value="0" selected="selected" >Tipo Tarjeta</option>
    <?php
        while (!$rsdenotarj->EOF) {
            ?>
    <option value="<?php echo $rsdenotarj->fields['id']?>" ><?php echo $rsdenotarj->fields['descripcion']?></option>
    <?php $rsdenotarj->MoveNext();
        } ?>
</select>

</div>

<?php
$buscar = "Select * from bancos where caja = 'N' and muestra_cliente = 'S' order by nombre asc";
    $rsbanco = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    ?>
<div class="col-md-12 col-sm-12 form-group" id="banco_mixsel_box" style="display: none;">

<select id="banco_mixsel" name="banco" class="form-control">
    <option value="0" selected="selected" >Seleccione Banco</option>
    <?php
        while (!$rsbanco->EOF) {
            ?>
    <option value="<?php echo $rsbanco->fields['idbanco']?>" ><?php echo $rsbanco->fields['nombre']?></option>
    <?php $rsbanco->MoveNext();
        } ?>
</select>

</div>

<div class="col-md-12 col-sm-12 form-group" id="cheque_numero_mixsel_box" style="display: none;">

    <input type="text" name="cheque_numero" id="cheque_numero_mixsel" value="" placeholder="cheque numero" class="form-control"  /> 

</div>
</td>
<?php } // if($facturador_electronico == 'S'){?>
                    
                    
                    <td><?php //echo intval($idpedido);?><a href="javascript:void(0);" class="btn btn-sm btn-success" onmouseup="agrega_carrito_pag(<?php echo intval($idpedido); ?>);"><span class="fa fa-plus"></span></a></td>

                </tr>
                <tr>
                    <td id="carrito_pagos_box" colspan="3">            
                    <?php require_once("carrito_cobros_venta_cent.php"); ?>
                
                    </td>
                </tr>
                </tbody>
            </table><br />
</div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="monto_box">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Monto</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="monto_recibido" id="monto_recibido" value="<?php  if (isset($_POST['monto_recibido'])) {
        echo intval($_POST['monto_recibido']);
    } else {
        echo floatval($rs->fields['monto']);
    }?>" placeholder="Monto" class="form-control" required="required" style="min-width:150px;" />
    </div>
</div>
<?php if ($facturador_electronico == 'S') { ?>
<div class="clearfix"></div>
<?php
$consulta = "
select id, descripcion
from 
denominacion_tarjeta 
where 
id <> 99
order by id asc
";
    $rsdenotarj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?>
<div class="col-md-6 col-sm-6 form-group" id="iddenominaciontarjeta_box" style="display: none;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Tarjeta</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<select id="iddenominaciontarjeta" name="iddenominaciontarjeta" class="form-control">
    <option value="0" selected="selected" >Tipo Tarjeta</option>
    <?php
        while (!$rsdenotarj->EOF) {
            ?>
    <option value="<?php echo $rsdenotarj->fields['id']?>" ><?php echo $rsdenotarj->fields['descripcion']?></option>
    <?php $rsdenotarj->MoveNext();
        } ?>
</select>
    </div>
</div>

<?php
$buscar = "Select * from bancos where caja = 'N' and muestra_cliente = 'S' order by nombre asc";
    $rsbanco = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    ?>
<div class="col-md-6 col-sm-6 form-group" id="banco_box" style="display: none;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Banco</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<select id="banco" name="banco" class="form-control">
    <option value="0" selected="selected" >Seleccione Banco</option>
    <?php
        while (!$rsbanco->EOF) {
            ?>
    <option value="<?php echo $rsbanco->fields['idbanco']?>" ><?php echo $rsbanco->fields['nombre']?></option>
    <?php $rsbanco->MoveNext();
        } ?>
</select>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="cheque_numero_box" style="display: none;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cheque Nro</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="cheque_numero" id="cheque_numero" value="" placeholder="cheque numero" class="form-control"  /> 
    </div>
</div>

<?php } // if($facturador_electronico == 'S'){?>
<?php if ($usa_descuento == 'S') {?>
<div class="clearfix"></div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Monto Descuento </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="descuento" id="descuento" value="<?php  if (isset($_POST['descuento'])) {
        echo intval($_POST['descuento']);
    }?>" placeholder="Monto Descuento" class="form-control"  />
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Motivo Descuento</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="motivo_descuento" id="motivo_descuento" value="<?php  if (isset($_POST['motivo_descuento'])) {
        echo antixss($_POST['motivo_descuento']);
    }?>" placeholder="Motivo Descuento" class="form-control" />
    </div>
</div>
<?php }  ?>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Condicion</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['condventa'])) {
    $value_selected = htmlentities($_POST['condventa']);
} else {
    $value_selected = 1;
}
// opciones
$opciones = [
    'CONTADO' => '1',
    'CREDITO' => '2'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'condventa',
    'id_campo' => 'condventa',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" onchange="condicion_factura(this.value);" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="venfact_box" style="display: none;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Vencimiento factura</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="date" name="vencimiento_factura" id="vencimiento_factura" value="<?php echo $fechavence; ?>" class="form-control" <?php echo $read; ?> />
    </div>
</div>

<?php if ($dias_credito > 0) { ?>
<div class="col-md-6 col-sm-6 form-group" id="venfact_vto_box" style="display: none;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">D&iacute;as Vencimiento</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="number" name="vencimiento_dias" id="vencimiento_dias" value="<?php echo $dias_credito; ?>" class="form-control" readonly />
    </div>
</div>
<?php } ?>
<?php if ($usar_oc == 'S') {
    if ($obligar_oc == 'S') {
        $req = " required  ";






    } else {
        $req = " ";
    }





















    ?>
<div class="col-md-6 col-sm-6 form-group"   >
    <label class="control-label col-md-3 col-sm-3 col-xs-12">OC N&deg;</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input <?php echo $req; ?> type="number" name="orden_compra_num" id="orden_compra_num" placeholder="Orden compra vinculada" value="" class="form-control" />
    </div>
</div>
<?php } ?>

<?php if ($usa_canalventa == 'S') { ?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Canal Venta</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
    // consulta
    $consulta = "
select idcanalventa, canal_venta 
from canal_venta 
where 
estado = 1
order by canal_venta asc
 ";

    // valor seleccionado
    if (isset($_POST['idcanalventa'])) {
        $value_selected = htmlentities($_POST['idcanalventa']);
    } else {
        $value_selected = htmlentities($rs->fields['idcanalventa']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'idcanalventa',
        'id_campo' => 'idcanalventa',

        'nombre_campo_bd' => 'canal_venta',
        'id_campo_bd' => 'idcanalventa',

        'value_selected' => $value_selected,

        'pricampo_name' => 'SELECIONAR CANAL...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'N'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);
    ?>
    </div>
</div>
<?php } ?>

<?php if ($usa_motorista == 'S') {?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Motorista</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php



        // consulta
        $consulta = "
    SELECT idmotorista, motorista
    FROM motoristas 
    where 
    estado = 1
    order by motorista asc
    
     ";


    // valor seleccionado
    if (isset($_POST['idmotorista'])) {
        $value_selected = htmlentities($_POST['idmotorista']);
    } else {
        $value_selected = htmlentities($rs->fields['idmotorista']);
    }

    // parametros
    $parametros_array = [
            'nombre_campo' => 'idmotorista',
            'id_campo' => 'idmotorista',

            'nombre_campo_bd' => 'motorista',
            'id_campo_bd' => 'idmotorista',

            'value_selected' => $value_selected,

            'pricampo_name' => 'Motorista...',
            'pricampo_value' => '',
            'style_input' => 'class="form-control" style="height: 40px;" ',
            'acciones' => '  ',
            'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);




    ?>
    </div>
</div>
<?php } ?>
 

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Factura</label>
    <div class="col-md-9 col-sm-9 col-xs-12"><table width="100%" border="0">
  <tr>
    <td><input name='fac_suc' id='fac_suc' type='text' value='<?php echo agregacero($factura_suc, 3); ?>' class="form-control" disabled="disabled" /></td>
    <td><input name='fac_pexp' id='fac_pexp' type='text' value='<?php echo agregacero($factura_pexp, 3); ?>' class="form-control" disabled="disabled" /></td>
    <td><input name='fac_nro' id='fac_nro' type='text' value='<?php echo agregacero($maxnfac, 7); ?>' class="form-control" /></td>
  </tr>
</table>
    </div>
</div>


<input name='idadherente' id='idadherente' type='hidden' value='' />
<input name='idservcom' id='idservcom' type='hidden' value='' />
<?php /*?><input name='banco' id='banco' type='hidden' value='' />
<input name='adicional' id='adicional' type='hidden' value='' /><?php */ ?>
<input name='montocheque' id='montocheque' type='hidden' value='' />


<input name='pedido' id='pedido' type='hidden' value='<?php echo $idpedido; ?>' />
<input name='idzona' id='idzona' type='hidden' value='<?php echo $rs->fields['delivery_zona']; ?>-' />
<input name='domicilio' id='domicilio' type='hidden' value='<?php echo $rs->fields['iddomicilio']; ?>' />
<input name='llevapos' id='llevapos' type='hidden' value='<?php echo $rs->fields['llevapos']; ?>' />
<input name='cambiode' id='cambiode' type='hidden' value='<?php echo $rs->fields['cambio']; ?>' />
<input name='observadelivery' id='observadelivery' type='hidden' value='<?php echo $rs->fields['observacion_delivery']; ?>' />
<input name='observacion' id='observacion' type='hidden' value='<?php echo $rs->fields['observacion']; ?>' />
<input name='chapa' id='chapa' type='hidden' value='<?php echo $rs->fields['chapa']; ?>' />
<input name='idvendedor' id='idvendedor' type='hidden' value='<?php echo $rs->fields['idvendedor']; ?>' />
<input name='iddeposito' id='iddeposito' type='hidden' value='' />
<input name='canal' id='canal' type='hidden' value='<?php echo $rs->fields['idcanal']; ?>' />
<?php
    // muestra solo las monedas con cotizacion cargada en el dia
        $ahorad = date("Y-m-d");
$consulta = "
    select *,
            (
            select cotizaciones.cotizacion
            from cotizaciones
            where 
            cotizaciones.estado = 1 
            and date(cotizaciones.fecha) = '$ahorad'
            and tipo_moneda.idtipo = cotizaciones.tipo_moneda
            order by cotizaciones.fecha desc
            limit 1
            ) as cotizacion
    from tipo_moneda 
    where
    estado = 1
    and borrable = 'S'
    and 
    (
        (
            borrable = 'N'
        ) 
        or  
        (
            tipo_moneda.idtipo in 
            (
            select cotizaciones.tipo_moneda 
            from cotizaciones
            where 
            cotizaciones.estado = 1 
            and date(cotizaciones.fecha) = '$ahorad'
            )
        )
    )
    order by borrable ASC, descripcion asc
    ";
$rsmoneda = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tmone = $rsmoneda->RecordCount();
?>
<div class="clearfix"></div>
<?php if ($tmone > 0) {?>
    <div class="form-group">
        <div class="col-md-12 col-sm-6 col-xs-12">
            <table class="table table-striped">
             
                <tbody>
            <?php while (!$rsmoneda->EOF) { ?>
            
                <tr>
                    
                  <td ><span class="fa fa-pencil"></span>&nbsp;<?php echo $rsmoneda->fields['descripcion']; ?></td>
                  <td align="right" ><?php echo formatomoneda($rsmoneda->fields['cotizacion'], 2, 'N'); ?></td>
                  <td align="right"><?php echo formatomoneda($mt / $rsmoneda->fields['cotizacion'], 2, 'N'); ?></td>
                </tr>
            <?php $rsmoneda->MoveNext();
            } ?>
              </tbody>
            </table>
       

        </div>
    </div>
<?php } ?>
<br />

    <div class="form-group" id="botones_venta">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
        
       <button type="button" class="btn btn-default" onmouseup="registrar_venta(<?php echo $idpedido; ?>,1);" ><span class="fa fa-check-square-o"></span> Factura</button>
        
        <?php

$consulta = "
SELECT factura_obliga
FROM preferencias 
WHERE  idempresa = $idempresa ";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$factura_obliga = trim($rspref->fields['factura_obliga']);


if (trim($factura_obliga) != 'S') {  ?>
        
       <button type="button" class="btn btn-default" onmouseup="registrar_venta(<?php echo $idpedido; ?>,2);" ><span class="fa fa-check-square-o"></span> Ticket</button>
       
        <?php } ?>
        </div>
    </div>
<div id="botones_venta_msg"></div>
<div class="clearfix"></div>
<br />
