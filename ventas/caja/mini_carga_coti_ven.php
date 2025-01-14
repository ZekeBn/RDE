<?php
/*---------------------------------
UR:19/10/2021
----------------------------------*/
require_once("../../includes/conexion.php");
require_once("../../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
$dirsup_sec = "S";

require_once("../../includes/rsusuario.php");

$idmoneda = intval($_POST['idmoneda']);
if ($idmoneda > 0) {


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
	and borrable = 'S' and idtipo=$idmoneda 
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
    $cotizacion = intval($rsmoneda->fields['cotizacion']);
}
?>
<input type="text" class="form-control has-feedback-left" name="cotiza" id="cotiza" placeholder="Cotizacion" required="required" value="<?php echo $cotizacion; ?>">
<span class="fa fa-asterisk form-control-feedback left" aria-hidden="true"></span>
