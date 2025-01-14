<?php
/*---------------------------------
UR: 19/10/2021
Nuevo script para arqueo y resumenes
de arqueos generados

---------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "22";
require_once("includes/rsusuario.php");

if (intval($idcaja) == 0) {
    $idcaja = intval($_POST['idcaja']);
}
$tipo = intval($_POST['tipo']);
if ($tipo == 1) {
    //registro de billetes
    $billete = intval($_POST['billete']);//es el id , no la denominacion del billete
    $buscar = "Select * from gest_billetes where idbillete=$billete";
    $cantidad = floatval($_POST['cantidad']);
    $rsvalor = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $valor = intval($rsvalor->fields['valor']);
    $subtotal = $cantidad * $valor;

    if ($cantidad > 0) {

        $insertar = "Insert into caja_billetes
		(idcajero,idcaja,idbillete,cantidad,subtotal)
		values
		($idusu,$idcaja,$billete,$cantidad,$subtotal)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
    }
}
if ($tipo == 2) {

    $moneda = intval($_POST['moneda']);
    $cantidad = floatval($_POST['cantidad']);
    $coti = intval($_POST['cotizacion']);
    $subtotal = $cantidad * $coti;
    if ($subtotal > 0) {
        $insertar = "Insert into caja_moneda_extra
		(idcaja,cajero,cantidad,subtotal,moneda,cotiza)
		values
		($idcaja,$idusu,$cantidad,$subtotal,$moneda,$coti)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
    }

}
if ($tipo == 3) {
    $fpago = intval($_POST['formapago']);
    $monto = floatval($_POST['monto']);
    $idbanco = intval($_POST['idbanco']);
    $valoradicional = antisqlinyeccion($_POST['adicional'], 'text');

    $insertar = "Insert into  caja_arqueo_fpagos
	(idcaja,idformapago,monto,idbanco,valor_adicional,estado,registrado_por,registrado_el)
	values
	($idcaja,$fpago,$monto,$idbanco,$valoradicional,1,$idusu,current_timestamp)";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

}

if ($tipo == 6) {
    //Eliminar los valores
    $cual = intval($_POST['cual']);
    $idunico = intval($_POST['idserial']);
    $tipo = intval($_POST['tipo']);
    if ($cual == 1) {
        //billetes
        $update = "update caja_billetes set estado=6 where registrobill=$idunico"	;

    }
    if ($cual == 2) {
        //monedas
        $update = "update caja_moneda_extra set estado=6 where sermone=$idunico"	;
    }
    if ($cual == 3) {
        //formas pago
        $update = "update caja_arqueo_fpagos set estado=6,anulado_por=$idusu,anulado_el=current_timestamp where idserie=$idunico"	;

    }
    $conexion->Execute($update) or die(errorpg($conexion, $update));

}

//--------------CURSORES----------------//
$buscar = "Select valor,cantidad,subtotal,registrobill from caja_billetes
inner join gest_billetes
on gest_billetes.idbillete=caja_billetes.idbillete
where caja_billetes.idcajero=$idusu and idcaja=$idcaja and caja_billetes.estado=1
order by valor asc";
$rsbilletitos = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tbilletes = $rsbilletitos->RecordCount();


//Monedas extranjeras
$buscar = "Select descripcion,cantidad,subtotal,sermone from caja_moneda_extra 
inner join tipo_moneda on tipo_moneda.idtipo=caja_moneda_extra.moneda 
where idcaja=$idcaja and cajero=$idusu and caja_moneda_extra.estado=1";
$rsmmone = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tmone = $rsmmone->RecordCount();


//Formas de Pago
$buscar = "Select (select descripcion from formas_pago where idforma=caja_arqueo_fpagos.idformapago) as descripcion,
monto,(select descripcion from gest_bancos where banco=caja_arqueo_fpagos.idbanco) as banco,
valor_adicional,idserie 
from caja_arqueo_fpagos 
where idcaja=$idcaja and caja_arqueo_fpagos.estado=1 and idformapago > 1";
$rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tfp = $rsf->RecordCount();

?>
<div class="col-md-4 col-sm-12 col-xs-12">
	<div class="pricing">
		<div class="title" style="height: 50px;">
			<h2>Moneda Nacional</h2>
		</div>
		<div class="x_content" style="height: 150px;overflow-y:scroll">
		    
		    
            		   		    
            <div class="table-responsive">
                <table width="100%" class="table table-bordered jambo_table bulk_action">
            	  <tbody>
            <?php

            $cantidad_acum = 0;
$subtotal_acum = 0;
while (!$rsbilletitos->EOF) {
    $cantidad_acum += $rsbilletitos->fields['cantidad'];
    $subtotal_acum += $rsbilletitos->fields['subtotal'];

    ?>
            		<tr>
            			<td>
            				
            				<div class="btn-group">
            					
            					<a href="javascript:eliminar_valor(1,<?php echo $rsbilletitos->fields['registrobill']?>);" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
            				</div>
            
            			</td>
            			<td align="center"><?php echo formatomoneda($rsbilletitos->fields['valor']);?></td>
            			<td align="center"><?php echo formatomoneda($rsbilletitos->fields['cantidad']);?></td>
            			<td align="center"><?php echo formatomoneda($rsbilletitos->fields['subtotal']);?></td>
            		</tr>
            <?php

    $rsbilletitos->MoveNext();
} $rsbilletitos->MoveFirst(); ?>
            	  <tfoot>
            		<tr>
            			<td>Totales</td>
            			<td align="center"></td>
            			<td align="center"><?php echo formatomoneda($cantidad_acum);?></td>
            			<td align="center"><?php echo formatomoneda($subtotal_acum);?></td>
            		</tr>
            	  </tfoot>
            	  </tbody>
                </table>
            </div>
            <br />
		    





		</div>
	</div>
</div>

<div class="col-md-4 col-sm-12 col-xs-12">
	<div class="pricing">
		<div class="title" style="height: 50px;">
			<h2>Moneda Extranjera</h2>
		</div>
		<div class="x_content" style="height: 150px;overflow-y:scroll">
		    
		    
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	 <!-- <thead>
		<tr>
			<th></th>
			<th align="center">Forma Pago</th>
			<th align="center">Valor</th>
			<th align="center">Mas Info</th>
		</tr>
	  </thead>-->
	  <tbody>
<?php

$cantidad_acum = 0;
$subtotal_acum = 0;
while (!$rsmmone->EOF) {
    $cantidad_acum += $rsmmone->fields['cantidad'];
    $subtotal_acum += $rsmmone->fields['subtotal'];

    ?>
		<tr>
			<td>
				
				<div class="btn-group">
					
					<a href="javascript:eliminar_valor(2,<?php echo $rsmmone->fields['sermone']?>);" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo $rsmmone->fields['descripcion']?></td>
			<td align="center"><?php echo formatomoneda($rsmmone->fields['cantidad'], 2, 'S');?></td>
			<td align="center"><?php echo formatomoneda($rsmmone->fields['subtotal'], 2, 'S');?></td>
		</tr>
<?php

    $rsmmone->MoveNext();
} $rsmmone->MoveFirst(); ?>
	  <tfoot>
		<tr>
			<td>Totales</td>
			<td align="center"></td>
			<td align="center"><?php echo formatomoneda($cantidad_acum, 2, 'S');?></td>
			<td align="center"><?php echo formatomoneda($subtotal_acum, 2, 'S');?></td>
		</tr>
	  </tfoot>
	  </tbody>
    </table>
</div>
<br />

		    

		</div>
	</div>
</div>

<div class="col-md-4 col-sm-12 col-xs-12">
	<div class="pricing">
		<div class="title" style="height: 50px;">
			<h2>Formas de Pago</h2>
		</div>
		<div class="x_content" style="height: 150px;overflow-y:scroll">
		    
		    
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	 <!-- <thead>
		<tr>
			<th></th>
			<th align="center">Forma Pago</th>
			<th align="center">Valor</th>
			<th align="center">Mas Info</th>
		</tr>
	  </thead>-->
	  <tbody>
<?php while (!$rsf->EOF) {
    $monto_acum += $rsf->fields['monto'];


    ?>
		<tr>
			<td>
				
				<div class="btn-group">
					
					<a href="javascript:eliminar_valor(3,<?php echo $rsf->fields['idserie']?>);" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo $rsf->fields['descripcion']?></td>
			<td align="center"><?php echo formatomoneda($rsf->fields['monto']);?></td>
			<td align="center"><?php
                if ($rsf->fields['banco'] != '') {
                    echo "Banco: ".$rsf->fields['banco']." ";
                }
    if ($rsf->fields['valor_adicional'] != '') {
        echo "Mas info: ".$rsf->fields['valor_adicional'];
    }
    ?></td>
		</tr>
<?php

$rsf->MoveNext();
} //$rs->MoveFirst();?>
	  <tfoot>
		<tr>
			<td>Totales</td>
			<td align="center"></td>
			<td align="center"><?php echo formatomoneda($monto_acum);?></td>
			<td align="center"></td>
		</tr>
	  </tfoot>
	  </tbody>
    </table>
</div>
<br />



			
	</div>
</div>