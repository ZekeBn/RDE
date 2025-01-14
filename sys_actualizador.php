 <?php
// actualiza atraso en detalle, debe estar en el webservice
$consulta = "
update detalle set atraso = COALESCE(DATEDIFF(CURDATE(),vencimiento),0) where saldo_cuota > 0;
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// cera atraso en detalle cancelado
$consulta = "
update detalle set atraso = 0 where saldo_cuota = 0;
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// actualiza saldos y atraso en operacion
$consulta = "
update operacion    
set    
saldo =        COALESCE((
            select sum(saldo_cuota)
            from detalle 
            where 
            detalle.idoperacion = operacion.idoperacion 
            ),0),
atraso =    COALESCE((
            select max(atraso)
            from detalle 
            where 
            detalle.idoperacion = operacion.idoperacion 
            and saldo_cuota > 0
            ),0)            
where
operacion.estado <> 6
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$consulta = "
update operacion    
set    
atraso =    0            
where
operacion.estado = 6
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
