<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");
//lista de bancos registrados

//print_r($_POST);

$buscar = "Select * from bancos where caja = 'N' and muestra_cliente = 'S' order by nombre asc";
$rsbanco = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tbanco = $rsbanco->RecordCount();

//Traemos las preferencias para la empresa
$buscar = "Select * from preferencias where idempresa=$idempresa ";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Para obligar voucher / numero o cualquier cosa
$obliga_adicional = trim($rspref->fields['obliga_adicional']);
$texto_adicional = trim($rspref->fields['txt_adicional']);

$usa_descuento = $rspref->fields['usa_descuento'];
$usa_chapa = $rspref->fields['usa_chapa'];
$usa_obscaja = $rspref->fields['usa_obscaja'];
$autoimpresor = $rspref->fields['autoimpresor'];
$usa_vendedor = $rspref->fields['usa_vendedor'];
$obliga_vendedor = trim($rspref->fields['obliga_vendedor']);
//Buscamos el valor que indica si se bloquea el descuento o no en venta final. Nota El campo autorizar, se utilizaba para permitir la entrega/recepcion de valores por medio de una clave autorizada. hora se va usar solo para descuentos, asi simplificamzos el proceso de descuento
$bloquear = intval($rspref->fields['autorizar']);
$controlfactura = intval($rspref->fields['controlafactura']);
$mostradel = intval($rspref->fields['deliveryproducto']);
$factura_obliga = trim($rspref->fields['factura_obliga']);
//Adherentes
$adherente = trim($rspref->fields['usa_adherente']);
$cajacompleta = trim($rspref->fields['usa_vta_completa']);
if ($bloquear == 0) {
    //No se establecio por la preferencia, y bloqueamos x seguridad
    $bloquear = 1;
}

// preferencias caja
$consulta = "
SELECT 
usa_motorista, obliga_motorista, usa_canalventa, vencimiento_credito,
permite_desc_factura, desc_redondeo_ceros, desc_redondeo_dir, 
usa_orden_compra, obliga_orden_compra												
FROM preferencias_caja 
WHERE  idempresa = $idempresa 
";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$usa_motorista = trim($rsprefcaj->fields['usa_motorista']);
$obliga_motorista = trim($rsprefcaj->fields['obliga_motorista']);
$usa_canalventa = trim($rsprefcaj->fields['usa_canalventa']);
$vencimiento_credito = trim($rsprefcaj->fields['vencimiento_credito']);
$permite_desc_factura = trim($rsprefcaj->fields['permite_desc_factura']);
$desc_redondeo_ceros = intval($rsprefcaj->fields['desc_redondeo_ceros']);
$desc_redondeo_dir = trim($rsprefcaj->fields['desc_redondeo_dir']);
$usar_oc = trim($rsprefcaj->fields['usa_orden_compra']);
$obligar_oc = trim($rsprefcaj->fields['obliga_orden_compra']);


//Traemos la numeracion de factura secuencia x sucursal y punto expedicion
if ($controlfactura == 1) {

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
}






$tipofactura = intval($rspref->fields['tipofactura']);
$script = trim($rspref->fields['script_factura']);
//echo $buscar;exit;

if ($tipofactura == 0) {
    //no se definio si es laser o matricial, usar matricial x defecto
    $warningfactura = "ATENCION: Si desea facturar,se intentar&aacute; imprimir en una matricial, debido a que el tipo de factura no ha sido establecido.</span>";
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
$totalzonas = $rszonas->RecordCount();



// clilente generico
$buscar = "
Select ruc, razon_social, idcliente
from cliente 
where 
borrable = 'N' 
and estado <> 6 
order by idcliente asc 
limit 1
";
//echo  $buscar;exit;
$rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$genericoruc = trim($rsf->fields['ruc']);
$genericodv = "";
$razon = trim($rsf->fields['razon_social']);
$razon_social_pred = $razon;
$idcliente_pred = $rsf->fields['idcliente'];

$idpedido = intval($_REQUEST['idpedido']);
if ($idpedido > 0) {
    $consulta = "
	select ruc
	from tmp_ventares_cab 
	where 
	idtmpventares_cab = $idpedido
	limit 1
	";
    $rscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $ruc_pedido = $rscab->fields['ruc'];
    if ($ruc_pedido != '') {
        $consulta = "
		select idcliente 
		from cliente 
		where 
		ruc = '$ruc_pedido' 
		order by idcliente asc 
		limit 1
		";
        $rscliped = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcliente = $rscliped->fields['idcliente'];
    }

}


//echo $montototal;
$domicilio = intval($_COOKIE['dom_deliv']);
if (intval($_POST['idcliente']) > 0) {
    $idcliente = intval($_POST['idcliente']);
}
if (intval($_POST['idsucursal_clie']) > 0) {
    $idsucursal_clie = intval($_POST['idsucursal_clie']);
}
$idcliente = 3;
if ($idcliente > 0) {
    $buscar = "Select * from cliente where idcliente=$idcliente and idempresa=$idempresa";
    $rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    //	$rr=explode("-",$rsf->fields['ruc']);
    $genericoruc = trim($rsf->fields['ruc']);
    $genericodv = "";
    $razon = trim($rsf->fields['razon_social']);
} else {
    //vemos x domicilio
    $domicilio = intval($_COOKIE['dom_deliv']);
    if ($domicilio > 0) {


        //vemos si hay uno seleccionado
        $buscar = "Select *,referencia from cliente_delivery inner join cliente_delivery_dom
		on cliente_delivery.idclientedel=cliente_delivery_dom.idclientedel
		where iddomicilio=$domicilio and cliente_delivery.idempresa=$idempresa limit 1
		";
        $rscasa = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        $direccion = trim($rscasa->fields['direccion']);
        $telefono = trim($rscasa->fields['telefono']);
        $referencia = trim($rscasa->fields['direccion'].' | '.$rscasa->fields['referencia'].' | '.$telefono);

        $razon = trim($rscasa->fields['nombres'].' '.$rscasa->fields['apellidos']);
        $idcliente = intval($rscasa->fields['idcliente']);
        $buscar = "Select * from cliente where idcliente=$idcliente and idempresa=$idempresa";
        $rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $rr = explode("-", $rsf->fields['ruc']);
        $genericoruc = trim($rr[0]);
        $genericodv = "";
        $razon = trim($rsf->fields['razon_social']);
    }


}


// si no se selecciono cliente asigna el cliente generico
if ($idcliente == 0) {
    $idcliente = trim($idcliente_pred);
}
// DIPLOMATICO

// busca si el cliente es diplomatico
$consulta = "
Select diplomatico, carnet_diplomatico 
from cliente 
where 
idcliente=$idcliente
limit 1
";
$rsdip = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// si es diplomatico cambia valores
if ($rsdip->fields['diplomatico'] == 'S') {
    // construye para enviar a la funcion
    $parametros_array = [
        'idusu' => $idusu,
        'diplo' => 'S',
        'idempresa' => $idempresa,
        'idsucursal' => $idsucursal

    ];
    // envia a la funcion
    $res = diplomatico_ventadir($parametros_array);

    // si no es diplomatico
} else {
    // busca si hay al menos un registro en tmp_ventres como diplomatico si
    $consulta = "
	select idventatmp
	from tmp_ventares
	WHERE
	tmp_ventares.registrado = 'N'
	and tmp_ventares.borrado = 'N'
	and tmp_ventares.finalizado = 'N'
	and tmp_ventares.usuario = $idusu
	and tmp_ventares.idsucursal = $idsucursal
	and diplo = 'S'
	limit 1
	";
    $rsdipcar = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // si hay desasigna
    if ($rsdipcar->fields['idventatmp'] > 0) {
        // si hay restaura valores
        $parametros_array = [
            'idusu' => $idusu,
            'diplo' => 'N',
            'idempresa' => $idempresa,
            'idsucursal' => $idsucursal

        ];
        // envia a la funcion
        $res = diplomatico_ventadir($parametros_array);
    }
}


// si envio pedido
$idpedido = intval($_POST['idpedido']);
if ($idpedido > 0) {
    $whereadd = " 
	and tmp_ventares.idtmpventares_cab = $idpedido 
	and tmp_ventares.finalizado = 'S'
	";
} else {
    $whereadd = " 
	and tmp_ventares.usuario = $idusu
	and tmp_ventares.finalizado = 'N'
	";
}

//Traemos la cabecera
// monto total de productos en carrito
$consulta = "
select sum(subtotal) as total_monto
from tmp_ventares 
inner join productos on tmp_ventares.idproducto = productos.idprod_serial
where 
tmp_ventares.registrado = 'N'
$whereadd
and tmp_ventares.borrado = 'N'
and tmp_ventares.idempresa = $idempresa
and tmp_ventares.idsucursal = $idsucursal
and productos.idempresa = $idempresa
";

$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$montototalprod = floatval($rs->fields['total_monto']);
// productos mas agregados
$montototal = $montototalprod + $montototalag;
//echo $montototal;exit;


//segun tipo de cobro se habilita o se sacan los tr
$tipocobro = intval($_POST['tipocobro']);
//echo $tipocobro;
if ($tipocobro == 1) {
    //EFECTIVO
    $img = "img/cc100x48efectivo.png";
    $valor = $montototal;
    $read = "  ";
}
if ($tipocobro == 4) {
    //TARJETA DEBITO
    $img = "img/cc100x48debito.png";

    $valor = $montototal;
    $read = " readonly='readonly' ";
}
if ($tipocobro == 3) {
    //MIX
    $img = "img/mixto01.png";
    $valor = 0;
    $read = "  ";
} else {
    // borrar carrito cobro del usuario si no es mixto
    $consulta = "
	DELETE 
	FROM carrito_cobros_ventas 
	WHERE 
	registrado_por = $idusu
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}
if ($tipocobro == 2) {
    //TARJETA CREDITO
    $img = "img/cc100x48credito.png";
    $valor = 0;
    $read = "  ";
}

if ($tipocobro == 5) {
    //CHEQUE
    $img = "img/cc100x48cheque.png";
    $valor = 0;
    $read = "  ";
}
if ($tipocobro == 6) {
    //transfer
    $img = "img/cc100x48transfer.png";
    $valor = 0;
    $read = "  ";
}
if ($tipocobro == 7) {
    //CRED
    $img = "img/cc100x48creditov2.png";
    $valor = 0;
    $read = " readonly='readonly' ";
}
/*
if ($tipocobro<=3){
    $tipochar="CONTADO";
} else {
    $tipochar="CREDITO";
}*/



$consulta = "
select descripcion,obliga_facturar,idformapago_set from formas_pago where idforma  = $tipocobro limit 1
";
$rsfsel = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idformapago_set = $rsfsel->fields['idformapago_set'];
if ($rsfsel->fields['obliga_facturar'] == 'S') {
    $factura_obliga = "S";
}


$consulta = "
select idforma, descripcion, idformapago_set
from formas_pago 
where
estado <> 6
and muestra_vcaja = 'S'
order by orden asc, descripcion asc
limit 5
";
$rsformas = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "
SELECT * 
FROM delivery_personal
where
estado = 1
order by nombre asc
";
$rsd = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
<div align="center" style="min-height: 440px;width: 100%;">
	
<div align="center">
<?php if ($genericoruc == '44444401') {
    //echo 'lll';exit;
    $genericoruc = '44444401'.'-'.'7';
}?>
	<span style="font-size: 20px;"><strong><?php  ?><?php if ($idpedido > 0) { ?>Pedido N&ordm; <?php echo $idpedido?>&nbsp;|&nbsp;<?php } ?>Total Venta <?php echo formatomoneda($montototal)?></strong></span><br />
	  <input type="hidden" name="totalventa" id="totalventa" value="<?php echo $montototal; ?>">
	<input type="hidden" name="totalventa_actu" id="totalventa_actu" value="<?php echo $montototal; ?>">
	<input type="hidden" name="totalventa_original" id="totalventa_original" value="<?php echo $montototal; ?>">
	  <input type="hidden" name="mediopagooc" id="mediopagooc" value="<?php echo $tipocobro?>" />
	<input type="hidden" name="delioc" id="delioc" value="0" />
    <input type="hidden" name="idpedido" id="idpedido" value="<?php echo intval($idpedido); ?>" />
	  <input type="hidden" name="idcliente" id="idcliente" value="<?php if ($idcliente == 0) {
	      echo $rsclipred->fields['idcliente'];
	  } else {
	      echo $idcliente;
	  }?>">
      <input type="hidden" name="idsucursal_clie" id="idsucursal_clie" value="<?php if ($idsucursal_clie == 0) {
          echo $rsclipred->fields['idsucursal_clie'];
      } else {
          echo $idsucursal_clie;
      }?>">
	<br />
	<div align="center" id="controles" style=" width: 100%;">
		<?php if ($cajacompleta == 'S') {?>
        <div class="btn-group btn-group-toggle" data-toggle="buttons">
        <?php while (!$rsformas->EOF) {

            $classforma = "primary";
            if ($tipocobro == $rsformas->fields['idforma']) {
                $classforma = "info";
            }


            ?>
		<a href="javascript:void(0);" onClick="cobranza(<?php echo $rsformas->fields['idforma']; ?>,<?php echo $idpedido ?>);">
			<span class="btn btn-<?php echo $classforma; ?>"><?php echo $rsformas->fields['descripcion']; ?></span>
		</a>
		<?php $rsformas->MoveNext();
        } ?>
		<?php
        $classforma = "default";
		    if ($tipocobro == 7) {
		        $classforma = "info";
		    }
		    ?>
		<a href="javascript:void(0);" onClick="cobranza(7,<?php echo $idpedido ?>);">
			<span class="btn btn-<?php echo $classforma ?>">Credito</span>
		</a>
		<?php
		    $classforma = "default";
		    if ($tipocobro == 3) {
		        $classforma = "info";
		    }
		    ?>
		<a href="javascript:void(0);" onClick="cobranza(3,<?php echo $idpedido ?>);">
			<span class="btn btn-<?php echo $classforma ?>">Pago Mixto</span>
		</a>
		<?php if ($adherente == 'S') {?>
		<?php
		    $classforma = "default";
		    if ($tipocobro == 8) {
		        $classforma = "info";
		    }
		    ?>
        	<a href="javascript:void(0);" onClick="cobranza(8,<?php echo $idpedido ?>);">
			<span class="btn btn-<?php echo $classforma ?>">Adherente</span>
            </a>
		<?php }?>
        </div>
		<?php } ?>
	</div>
	<br />
	<div id="oculto1" style="display:none;">
    <strong><?php echo $rsfsel->fields['descripcion']; ?></strong>
	
	<?php if ($mostradel == 0 && $domicilio > 0) {?>
    <?php
if ($usa_motorista == 'S') {


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



}

	    ?>
		<select id="tipozona" name="tipozona" onChange="esplitear(this.value)" style="height: 40px; width: 20%; display:none;">
			  <?php if ($totalzonas > 1 or $totalzonas == 0) { ?><option value="<?php echo '0 - 0';?>" >Enviar x Delivery</option><?php } ?>
			  <?php while (!$rszonas->EOF) {?>
			  <option value="<?php echo $rszonas->fields['idzona'].' - '.$rszonas->fields['costoentrega']; ?>" <?php if ($delivery_zona == $rszonas->fields['idzona']) { ?> selected<?php } ?>><?php echo $rszonas->fields['descripcion']?> -><?php echo formatomoneda($rszonas->fields['costoentrega'])?></option>
			  <?php $rszonas->MoveNext();
			  } ?>
		</select>
		&nbsp;<select id="llevapos" name="llevapos" style="height: 40px; width: 20%;" readonly>
            <option value="0" >Llevar POS?</option>
          		 <option value="1" <?php if ($domicilio > 0 && $tipocobro == 1) { ?>selected="selected"<?php } ?>>SIN POS</option>
			  	<option value="2" <?php if ($domicilio > 0 && ($tipocobro == 4 or $tipocobro == 2)) { ?>selected="selected"<?php } ?>>CON POS</option>
          </select>
          
		&nbsp;<input type="text" name="cambiopara" id="cambiopara" value="" placeholder="llevar cambio de" style="height: 40px;width: 20%" />&nbsp;<select id="iddelivery" name="iddelivery" style="height: 40px; width: 20%; display:none;">
            <option value="" >Seleccionar..</option>
            <?php while (!$rsd->EOF) { ?>
            <option value="<?php echo $rsd->fields['iddelivery']; ?>" ><?php echo $rsd->fields['nombre']; ?> <?php echo $rsd->fields['apellido']; ?></option>
            <?php $rsd->MoveNext();
            } ?>
          </select>
        <input type="hidden" name="totzonas" id="totzonas" value="<?php echo $totalzonas; ?>" />
		<br /></br>
	<?php }?>
		
		<div align="center">
			<table width="600">
				<tr>
					<td width="77" align="left" valign="middle">
					<?php if (trim($img) != '') { ?>
						<img src="<?php echo $img ?>" width="100" height="48" />
					<?php } ?>
					</td>
					<td width="311" align="left" valign="middle">
							<input type="text" name="montorecibido" id="montorecibido" title="Monto Recibido" value="<?php echo $montototal?>" class="texto1" style="width: 99%" onKeyUp="actualiza_saldos()" <?php if ($tipocobro == 7) {?> readonly <?php } ?>/> <br />
					</td >
						<td id="vueltotd" hidden="hidden" ><input type="text" name="vuelto" id="vuelto"  title="Vuelto" value="0" class="texto1" style="width: 99%" readonly />
					</td>
				</tr>
			</table>
			<table width="416" <?php if ($tipocobro == 3) {?> style="display:none;"<?php } ?>>
				<tr id="warpago" hidden="hidden">
					<td colspan="2" align="center" style="color: white"><img src="img/alerta_blue.jpg" width="20" height="20" alt=""/>  Debe indicar Banco y numero de cheque!</td>
				</tr>
				<tr>
					<td width="246">
						<select id="adicional2" name="banco"  hidden="hidden" style="height: 40px; width: 98%;">
							<option value="0" selected="selected" >Seleccione Banco</option>
							<?php
                            if ($tbanco > 0) {
                                while (!$rsbanco->EOF) {
                                    ?>
							<option value="<?php echo $rsbanco->fields['idbanco']?>" ><?php echo $rsbanco->fields['nombre']?></option>
							<?php $rsbanco->MoveNext();
                                }
                            }?>
						</select>
					</td>
<?php
if ($facturador_electronico == 'S' && ($idformapago_set == 3 or $idformapago_set == 4)) {

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
					<td width="246">
						<select id="iddenominaciontarjeta" name="iddenominaciontarjeta"  style="height: 40px; width: 98%;">
							<option value="0" selected="selected" >Tipo Tarjeta</option>
							<?php
                                while (!$rsdenotarj->EOF) {
                                    ?>
							<option value="<?php echo $rsdenotarj->fields['id']?>" ><?php echo $rsdenotarj->fields['descripcion']?></option>
							<?php $rsdenotarj->MoveNext();
                                } ?>
						</select>
					</td>
<?php } ?>
					<td width="259">	
						<input type="text" name="adicional1" id="adicional1" value="" class="texto2" hidden="hidden" placeholder="<?php echo $texto_adicional;?>" style="width: 98%;" /> 
					</td>
				</tr>
			</table>

			
			
		</div>
	</div>
	            <?php if ($tipocobro == 3) {?>
            <div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead class="thead-dark">
                <tr style="background-color:#CCC; font-weight:bold;">
                    <th>Forma Pago</th>
                    <th>Monto</th>
					<?php if ($facturador_electronico == 'S') { ?>
					<th>Datos</th>
					<?php } ?>
                    <th></th>
                    <th>Detalle</th>
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
	                    'acciones' => ' onchange="forma_pago_mixsel(this.value);" ',
	                    'autosel_1registro' => 'S'

	                ];
	                //print_r($parametros_array);
	                // construye campo
	                echo campo_select($consulta, $parametros_array);
	                ?></td>
                    <td><input name="idforma_mixto_monto" id="idforma_mixto_monto" type="text" value="<?php echo $montototal - $carrito_cobros; ?>" class="form-control" /></td>
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
                    <td id="carrito_pagos_box">            
					<?php require_once("carrito_cobros_venta.php"); ?>
                
                </td>
                </tr>
                </tbody>
            </table><br />
			<?php } ?>
	<div id="oculto2" hidden="hidden" >
		<table width="661" style="border-collapse: collapse">
			<tr>
				<td colspan="3" align="center">
				<?php if (intval($_SESSION['idclienteprevio']) == 0) { ?>
                <a href="#pop2"  onMouseUp="agrega_cliente(<?php echo $tipocobro?>,<?php echo $idpedido; ?>);"><img src="img/1485884191_user_add.png" width="25" height="25" alt=""/></a>&nbsp;&nbsp;
				<a href="javascript:void(0);" onMouseUp="busca_cliente(<?php echo $tipocobro?>,<?php echo $idpedido; ?>);"><img src="img/1485884224_user_manage.png" width="25" height="25" alt="" id="selclie"/></a>
                <?php } ?>
                &nbsp;&nbsp;
                <?php if ($permite_desc_factura == 'S') {?>
				<img src="img/desc_mini.png" width="25" height="25" id="descuento_icon" alt="Descuento" title="Descuento" onMouseUp="descuento_asigna();" />				
                <div id="descuento_box" style="display:none;"><br />
                <strong title="Redondeo a 3 ceros  hacia arriba">%</strong> <input type="text" name="descuento_porc" id="descuento_porc" placeholder="% Descuento" value=""  onKeyUp="calcula_desc(this.value,<?php echo $desc_redondeo_ceros; ?>,'<?php echo $desc_redondeo_dir; ?>')" onChange="calcula_desc(this.value,<?php echo $desc_redondeo_ceros; ?>,'<?php echo $desc_redondeo_dir; ?>');" >
                Monto: <input type="text" name="descuento" id="descuento" placeholder="Monto Descuento" value=""  onKeyUp="calcula_desc_mont(this.value);" onChange="calcula_desc_mont(this.value);" ><br />
                Motivo: <input type="text" name="motivodesc" id="motivodesc" placeholder="Motivo Descuento" value="" ><br /><br />
                </div>
                <?php } ?>
                <?php if ($usa_chapa == 'S') {?>
                <input type="text" name="chapa" id="chapa" placeholder="Nombre" value="" >
                <?php } ?>
                <?php if ($usa_obscaja == 'S') {?>
                <input type="text" name="observacion" id="observacion" placeholder="Observacion" value="" >
                <?php } ?>
				<?php
if ($usa_vendedor == 'S') {


    // consulta
    $consulta = "
	SELECT * , concat(nombres,' ',apellidos) as nomape
	FROM vendedor 
	where 
	estado = '1'
	order by nombres asc
	
	 ";


    // valor seleccionado
    if (isset($_POST['idvendedor'])) {
        $value_selected = htmlentities($_POST['idvendedor']);
    } else {
        $value_selected = htmlentities($rs->fields['idvendedor']);
    }

    // parametros
    $parametros_array = [
            'nombre_campo' => 'idvendedor',
            'id_campo' => 'idvendedor',

            'nombre_campo_bd' => 'nomape',
            'id_campo_bd' => 'idvendedor',

            'value_selected' => $value_selected,

            'pricampo_name' => 'Vendedor...',
            'pricampo_value' => '',
            'style_input' => 'class="form-control"',
            'acciones' => ' onchange="forma_pago(this.value);"; ',
            'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);



}

?>
<?php
// si se permite facturar desde depositos en preferencias
if ($rsco->fields['factura_deposito'] == 'S') {

    $consulta = "
	select * 
	from usuarios_depositos 
	where 
	idusuario = $idusu 
	and estado = 1
	limit 1;
	";
    $rsdepusu = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // si el usuario tiene permisos para facturar desde algun deposito
    if ($rsdepusu->fields['iddeposito'] > 0) {

        // consulta
        $consulta = "
		SELECT iddeposito, descripcion
		FROM gest_depositos
		where
		estado = 1
		and idsucursal = $idsucursal
		and tiposala <> 3
		and 
		(
		iddeposito in (select iddeposito from usuarios_depositos where idusuario = $idusu and estado = 1  )
		or tiposala = 2
		)
		order by descripcion asc
		 ";

        // valor seleccionado
        if (isset($_POST['iddeposito'])) {
            $value_selected = htmlentities($_POST['iddeposito']);
        } else {
            $value_selected = htmlentities($rs->fields['iddeposito']);
        }

        // parametros
        $parametros_array = [
            'nombre_campo' => 'iddeposito',
            'id_campo' => 'iddeposito',

            'nombre_campo_bd' => 'descripcion',
            'id_campo_bd' => 'iddeposito',

            'value_selected' => $value_selected,

            'pricampo_name' => 'SELECIONAR DEPOSITO...',
            'pricampo_value' => '',
            'style_input' => 'class="form-control"',
            'acciones' => ' required="required" ',
            'autosel_1registro' => 'N'

        ];

        // construye campo
        echo campo_select($consulta, $parametros_array);

    }

}
// si se permite facturar desde depositos en preferencias
if ($usa_canalventa == 'S') {

    $idcanalventa = intval($_SESSION['idcanalventa']);
    if ($idcanalventa == 0) {
        $consulta = "
		select idcanalventa, canal_venta 
		from canal_venta 
		where 
		estado = 1
		order by canal_venta asc
		";
        $rsdepusu = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // si hay canales cargados
        if ($rsdepusu->fields['idcanalventa'] > 0) {

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



        } // if($rsdepusu->fields['idcanalventa'] > 0){

    } else { // if($idcanalventa == 0){
        echo '<input type="hidden" id="idcanalventa" name="idcanalventa" value="'.$idcanalventa.'" />';

    } // if($idcanalventa == 0){

}



?><?php if ($venta_retroactiva == 'S') {  ?>
<br /><strong>Fecha Factura:</strong> <input name="fecha_venta" id="fecha_venta" type="date" value="" />
<?php } ?>
<br /><strong>Cod Pedido Externo:</strong> <input name="codpedido_externo" id="codpedido_externo" type="text" value="" />
<?php if ($usar_oc == 'S') { ?>
<br /><strong>Orden Compra Numero:</strong> <input name="ocnumero" id="ocnumero" type="text" value="" />
<?php } ?>
<br />
</td>
				<td colspan="4" align="left"><!--<span class="tooltip" content: attr(title); title="Tipo Venta y datos Factura"><img src="img/factura.png" width="40" height="40" alt=""/></span>--><?php
                if ($tipocobro == 1) {

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
                    if ($rsmoneda->fields['idtipo'] > 0) {
                        /*?><select name="moneda" id="moneda" onChange="moneda_extrangera(this.value);" style="width:40%;">
                        <?php while(!$rsmoneda->EOF){ ?>
                          <option value="<?php echo $rsmoneda->fields['idtipo']; ?>"><?php echo $rsmoneda->fields['descripcion']; if($rsmoneda->fields['borrable'] == 'S'){ ?> - <?php echo formatomoneda($rsmoneda->fields['cotizacion']); } ?></option>
                        <?php $rsmoneda->MoveNext(); } ?>
                        </select><?php */


                        ?><table width="98%" border="1" style="border-collapse:collapse; font-size:12px;">
  <tbody>
    <tr>
      <td align="center" bgcolor="#F8FFCC"><strong>Moneda</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Cotiza</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Monto</strong></td>
    </tr>
<?php while (!$rsmoneda->EOF) { ?>
    <tr>
      <td bgcolor="#FFFFFF"><?php echo $rsmoneda->fields['descripcion']; ?></td>
      <td align="right" bgcolor="#FFFFFF"><?php echo formatomoneda($rsmoneda->fields['cotizacion'], 2, 'N'); ?></td>
      <td align="right" bgcolor="#FFFFFF" style="font-size:18px;"><?php echo formatomoneda($montototal / $rsmoneda->fields['cotizacion'], 2, 'N'); ?></td>
    </tr>
<?php $rsmoneda->MoveNext();
} ?>
  </tbody>
</table>
              <input type="text" name="monto_extrangero" id="monto_extrangero"  style="width:50%; display:none;"><?php }
                    } ?></td>
			</tr>
<?php
if (intval($_SESSION['idclienteprevio']) > 0) {
    $idclienteprevio = intval($_SESSION['idclienteprevio']);
    $consulta = "
	select ruc, razon_social, idcliente
	from cliente
	where 
	idcliente = $idclienteprevio
	";
    $rscliprev = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcliente = $rscliprev->fields['idcliente'];
    $razon = $rscliprev->fields['razon_social'];
    $genericoruc = $rscliprev->fields['ruc'];
}

?>
			<tr>
				<td width="52"><input name="ruc" type="text" id="ruc" style="background-color:#DCDCDC; height: 40px;" value="<?php echo $genericoruc; ?>" size="8"  readonly onMouseUp="agrega_cliente(<?php echo $tipocobro?>,<?php echo $idpedido; ?>);" />
				</td>
				
				<td width="292">
					<input name="razon_social" type="text" id="razon_social" style="background-color:#DCDCDC; height: 40px; width: 98%;" value="<?php  if ($idcliente == 0) {
					    echo $razon_social_pred;
					} else {
					    echo $razon;
					}?>" readonly onMouseUp="agrega_cliente(<?php echo $tipocobro?>,<?php echo $idpedido; ?>);">
				</td>
				<td width="129" ><select name="tipoventa" id="tipoventa" style="height: 44px; width: 99%">
<?php if ($tipocobro == 7) { ?>
			 <option value="2" <?php echo $selecred; ?>>CREDITO</option>
<?php } else { ?>
			 <option value="1" <?php echo $selecont; ?>>CONTADO</option>
<?php } ?>
		   </select></td>
				<td width="18"><input name="pref1" type="text" id="pref1" value="<?php echo $parte1f?>" size="3" maxlength="3" style="height: 40px;"></td>
				<td width="18"> <input name="pref2" type="text" id="pref2" value="<?php echo $parte2f?>" size="3" maxlength="3" style="height: 40px;"></td>
				<td width="102"> <input name="fact" type="text" id="fact" value="<?php echo $maxnfac?>" size="7" maxlength="7" style="height: 40px; width: 50%" placeholder="N&uacute;mero de factura"></td>



			</tr>



		</table>
	</div>
	<div id="ocultoad" hidden="hidden">
		<br />
		<input type="tex" name="adherentebus" id="adherentebus" style="width: 550px; height: 40px;" placeholder="Buscar adherente" onKeyUp="carga_adherentes2(this.value)"   />
		
		<div id="cargaad" >
			
			
		</div>
	</div>
</div>
	<div id="cuerpo3" hidden="hidden">	
		
		<div class="clearfix"></div>
        <br />
		<?php if ($tipocobro == 7 && $vencimiento_credito == 'S') { ?>
		Vencimiento Factura:
		<div align="center"><input type="date" name="primervto" id="primervto" value="<?php echo date("Y-m-d"); ?>" /></div>
		<?php } ?><br />
		<div id="terminar">
        
        <a href="javascript:void(0);" onClick="registrar_venta(1);" class="btn btn-lg btn-success" role="button" aria-disabled="true"><span class="fa fa-print"></span> FACTURA</a>
         <?php if ($factura_obliga != 'S') {?>
        <a href="javascript:void(0);" onClick="registrar_venta(2);" class="btn btn-lg btn-warning" role="button" aria-disabled="true" id="ticket_btn"><span class="fa fa-print"></span> TICKET</a>
            <?php } ?>
		</div>
	</div>
</div>
