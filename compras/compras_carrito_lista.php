<?php
/*------------------------------
12/02/2023
-------------------------------*/
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");


$chau = intval($_POST['chau']);
if ($chau > 0) {
    $update = "update compras_pagos_formas set estado=6,anulado_el='$ahora',anulado_por=$idusu where idunicoserial=$chau";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
}

$idformapago = intval($_POST['idformapago']);
$idcuentainterna = intval($_POST['idcuentainterna']);
$monto_seleccionado = floatval($_POST['monto_seleccionado']);
$otros_valores = antisqlinyeccion($_POST['otros_valores'], 'text');
$idtransaccion = intval($idtransaccion);
$registrar = intval($_POST['registrar']);

if ($idtransaccion == 0) {
    $idtransaccion = intval($_POST['idtransaccion']);
}
if ($idtransaccion > 0) {

    if (($monto_seleccionado > 0) && ($registrar == 1)) {

        $insertar = "Insert into 	compras_pagos_formas	
		(idtransaccion,idcuenta_interna,monto_abonado,idforma_pago,otros_valores,observaciones,estado,registrado_por,registrado_el,anulado_por,anulado_el)
		values
		($idtransaccion,$idcuentainterna,$monto_seleccionado,$idformapago,$otros_valores,NULL,1,$idusu,'$ahora',NULL,NULL)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
    }
    $buscar = "Select 
					monto_abonado,
					(select CONCAT(denominacion,' CTA:',cuentanum) from cuentas where idcuenta=compras_pagos_formas.idcuenta_interna) as numerocuenta,
					idunicoserial,
					(select descripcion from formas_pago where idforma=compras_pagos_formas.idforma_pago) 
					as formapagodes,
					otros_valores
			from 
				compras_pagos_formas
				where estado=1 
				and idtransaccion=$idtransaccion
				order by idunicoserial 
				desc
	";
    $rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tfreg = $rsf->RecordCount();


    $buscar = "Select * from cuentas  order by denominacion asc";
    //$rslista1=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));

    if ($tfreg > 0) {
        ?>
	<table class="tablaconborde2" width="650px;">
		<tr>
			<td>Monto</td>
			<td>Forma Pago</td>
			<td>Otros</td>
			<td>Movimiento en cta</td>
			<td></td>
		</tr>
		<?php while (!$rsf->EOF) { ?>
		<tr>
			<td><?php echo formatomoneda($rsf->fields['monto_abonado']) ?></td>
			<td><?php echo $rsf->fields['formapagodes'] ?></td>
			<td><?php echo $rsf->fields['otros_valores'] ?></td>
			<td><?php echo $rsf->fields['numerocuenta'] ?></td>
			<td><a href="javascript:void(0);" onclick="eliminar_pago(<?php echo $rsf->fields['idunicoserial'] ?>)">[ELIMINAR]</a></td>
		</tr>
		
		<?php $rsf->MoveNext();
		} ?>
	</table>
<?php
    }
}
?>
