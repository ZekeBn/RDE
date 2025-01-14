<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");
//Verificamos si hay una caja abierta por este usuario
$buscar = "
Select * 
from caja_super 
where 
estado_caja=1 
and cajero=$idusu  
and tipocaja=1
order by fecha desc 
limit 1
";
$rscaj = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaj->fields['idcaja']);

$idcaja_compartida = intval($rscaj->fields['idcaja_compartida']);
$idnumeradorcab = intval($rscaj->fields['idnumeradorcab']);
//listado de bancos en caso que se requiera
$buscar = "Select * from gest_bancos where estado <> 6 order by descripcion asc";
$rf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$eliminarunico = intval($_POST['chau']);
$registrar = intval($_POST['registrar']);
if ($registrar > 0) {
    //print_r($_POST);
    $numboleta = intval($_POST['numboleta']);
    $montoglobal = floatval($_POST['monto']);
    $formapago = intval($_POST['fpago']);
    $montoforma = floatval($_POST['montofpago']);
    $obsfp = antisqlinyeccion($_POST['obs'], 'text');

    $buscar = "Select * from teso_boletas_deposito 
		where  numeroboleta=$numboleta and idcaja=$idcaja";
    //echo $buscar;
    $rsbolecab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idboleta = intval($rsbolecab->fields['idboleta']);
    if ($idboleta == 0) {
        if ($numboleta > 0 && $montoglobal > 0) {
            //generamos la cabcera y despues actualizamos
            $insertar = "
			insert into teso_boletas_deposito
			(numeroboleta,idbanco,total_boleta,registrado_por,generado_el,estado,idcaja)
			values
			($numboleta,0,$montoglobal,$idusu,'$ahora',1,$idcaja)
			";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

            $buscar = "Select * from teso_boletas_deposito 
				where idcaja=$idcaja and estado =1 order by idboleta desc limit 1";
            $rsbolecab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idboleta = intval($rsbolecab->fields['idboleta']);

        }
    } else {

        //echo "La boleta ya se encuentra registrada. No se permite duplicar";




    }

    //salvada la cabeccera , el cuerpo
    $buscar = "Select * from teso_boletas_deposito_detalles where idboleta=$idboleta and estado=1 and idformapago=$formapago";
    $rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    //echo $buscar;
    if ($rsb->fields['idformapago'] == '' && $formapago > 0 && $montoforma > 0) {
        $insertar = "Insert into  teso_boletas_deposito_detalles
		(idboleta,idformapago,monto_declarado,monto_recibido,registrado_el,registrado_por)
		values
		($idboleta,$formapago,$montoforma,0,'$ahora',$idusu)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

        $update = "update teso_boletas_deposito set total_boleta=(select sum(monto_declarado) from teso_boletas_deposito_detalles where idboleta=$idboleta) where idboleta=$idboleta";
        $conexion->Execute($update) or die(errorpg($conexion, $update));




    }

}
if ($eliminarunico > 0) {
    $delete = "delete from teso_boletas_deposito_detalles where idunicoserial=$eliminarunico ";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));


}
//Formas de Pago
$buscar = "Select *
from formas_pago 
where estado=1 order by descripcion asc";
$rsfpn = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tfp = $rsfpn->RecordCount();

//Verificamos si los registros existen
$buscar = "Select descripcion,monto_declarado,idunicoserial,(select numeroboleta from teso_boletas_deposito
	where idboleta=teso_boletas_deposito_detalles.idboleta limit 1) as numeroboleta
from teso_boletas_deposito_detalles
inner join formas_pago on formas_pago.idforma=teso_boletas_deposito_detalles.idformapago
where teso_boletas_deposito_detalles.estado=1 and idboleta in
(Select idboleta from teso_boletas_deposito where idcaja=$idcaja )

order by descripcion asc";
$rsl = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$treg = $rsl->RecordCount();


?>
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Boleta Numero *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nboleta" id="nboleta" value="<?php  if (isset($_POST['nboleta'])) {
	    echo htmlentities($_POST['nboleta']);
	} else {
	    echo htmlentities($rs->fields['nboleta']);
	}?>" placeholder="" class="form-control" required  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12"> Monto Global Boleta</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="mglobal" id="mglobal" value="<?php  if (isset($_POST['mlobal'])) {
	    echo htmlentities($_POST['mglobal']);
	} else {
	    echo htmlentities($rs->fields['mglobal']);
	}?>" placeholder="" class="form-control"  />                    
	</div>
</div>
<hr />
<div class="clearfix"></div>
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Forma Pago *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
		<select name="formapagoli"  id="formapagoli"  style="width: 100%;height:40px;" >
			<option value="" selected="selected">Seleccionar</option>
			<?php while (!$rsfpn->EOF) { ?>
			<option value="<?php echo $rsfpn->fields['idforma'] ?>" selected="selected"><?php echo $rsfpn->fields['descripcion'] ?></option>
	
			<?php $rsfpn->MoveNext();
			} ?>
		</select>
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Monto Gs </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="mfpago" id="mfpago" value="<?php  if (isset($_POST['mfpago'])) {
	    echo htmlentities($_POST['mfpago']);
	} else {
	    echo htmlentities($rs->fields['mfpago']);
	}?>" placeholder="" class="form-control"  />                    
	</div>
</div>
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Obs </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="obs_local" id="obs_local" value="<?php  if (isset($_POST['obs_local'])) {
	    echo htmlentities($_POST['obs_local']);
	} else {
	    echo htmlentities($rs->fields['obs_local']);
	}?>" placeholder="" class="form-control"  />                    
	</div>
</div>
<div class="col-md-6 col-sm-6 form-group">
	<a href="javascript:void(0);" onclick="agregarfpagoboleta();" class="btn btn-primary"><span class="fa fa-plus-square"></span>&nbsp;Agregar</a>
</div>
<hr />
<div id="recargajk">
	<?php if ($treg > 0) {
	    $buscar = "Select * from teso_boletas_deposito where idcaja=$idcaja and estado=1";
	    $rs1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
	    $idboletan = intval($rs1->fields['numeroboleta']);
	    $montobn = floatval($rs1->fields['total_boleta']);
	    ?>
	<table class="table table-bordered jambo_table bulk_action">
    <thead>
		<tr>
			<!--<th colspan ="3">Boleta Numero: <?php echo formatomoneda($idboletan) ?> &nbsp; Global GS:  <?php echo formatomoneda($montobn) ?></th>-->
		</tr>
		<tr>
			<th>Forma Pago</th>
			<th>Boleta Num</th>
			<th>Monto Gs</th>
			<th></th>
		</tr>
	</thead>
		<tbody>
		<?php
	            $subt = 0;
	    while (!$rsl->EOF) {
	        $subt = $subt + intval($rsl->fields['monto_declarado']);

	        ?>
		<tr>
			<td><?php echo $rsl->fields['descripcion']; ?></td>
			<td><?php echo formatomoneda($rsl->fields['numeroboleta']); ?></td>
			<td><?php echo formatomoneda($rsl->fields['monto_declarado']); ?></td>
			<td><a href="javascript:void(0);" onclick="eliminarunicop(<?php echo $rsl->fields['idunicoserial']; ?>);"><span class="fa fa-trash"></span>&nbsp;Eliminar</a></td>
		</tr>
	
		<?php $rsl->MoveNext();
	    } ?>
		<tr>
			<td colspan="4" align="center">Total en Boletas Gs: &nbsp;<?php echo formatomoneda($subt); ?></td>
			
		</tr>
		</tbody>
	</table>
	<?php } ?>
</div>