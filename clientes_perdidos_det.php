<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "11";
$submodulo = "607";
require_once("includes/rsusuario.php");

$idcliente = intval($_POST['idcliente']);

$consulta = "
SELECT c.idcliente, c.ruc, c.razon_social, c.fantasia, v.idventa, v.totalcobrar, v.factura, v.ruc, v.razon_social, s.nombre as sucursal, v.fecha,
ca.canal
FROM cliente c
INNER JOIN ventas v ON c.idcliente = v.idcliente
inner join sucursales s on s.idsucu = v.sucursal
inner join canal ca on ca.idcanal = v.idcanal
WHERE 
c.estado <> 6
and v.estado <> 6
and v.finalizo_correcto = 'S'
and v.idcliente = $idcliente
order by  v.fecha desc
limit 10
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>
<div class="table-responsive">
<table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
        <tr>
            <th>ID Cliente</th>
            <th>RUC</th>
            <th>Razón Social</th>
            <th>Fantasía</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td align="center"><?php echo intval($rs->fields['idcliente']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['fantasia']); ?></td>
        </tr>
    </tbody>
</table>
</div>
<hr />
<strong>ULTIMAS 10 VENTAS REALIZADAS AL CLIENTE:</strong><br />
<div class="table-responsive">
<table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
        <tr>
            <th></th>
            <th>ID Venta</th>
            <th>Fecha</th>
            <th>Monto</th>
            <th>Factura</th>
            <th>Canal</th>
            <th>Sucursal</th>
        </tr>
    </thead>
    <tbody>
        <?php while (!$rs->EOF) {
            $idventa = intval($rs->fields['idventa']);
            $consulta = "
            select *,
            (select barcode from productos where idprod_serial = ventas_detalles.idprod) as barcode,
                (
                select idinsumo 
                from productos 
                inner join insumos_lista on insumos_lista.idproducto = productos.idprod_serial
                where 
                idprod_serial = ventas_detalles.idprod
                ) as idinsumo,
            (select descripcion from productos where idprod_serial = ventas_detalles.idprod) as producto
            
            from ventas_detalles 
            where 
             idventa = $idventa
            order by pventa asc
            ";
            $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            ?>
        <tr>
            <td align="center">
				
             <div class="btn-group">
					<a href="javascript:detallar(<?php echo $rs->fields['idventa']; ?>);void(0);" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span id='sp_<?php echo $rs->fields['idventa']; ?>' class="fa fa-chevron-down"></span></a>
					
				</div>


            </td>
            <td align="center"><?php echo intval($rs->fields['idventa']); ?></td>
            <td align="center"><?php if ($rs->fields['fecha'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha']));
            }  ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['totalcobrar']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['factura']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['canal']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
        </tr>
        <tr>
            <td align="center" colspan='7' style='display:none;' id='idventa_<?php echo intval($rs->fields['idventa']); ?>'>

                <div class="table-responsive">
                    <table width="100%" class="table table-bordered jambo_table bulk_action">
                    <thead>
                        <tr>
                            <th align="center">Codigo</th>
                            <th align="center">Codigo Barra</th>
                            <th align="center">Producto</th>
                            <th align="center">Cantidad</th>
                            <th align="center">Precio Unitario</th>
                            <th align="center">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while (!$rsdet->EOF) {
                        $idventadet = $rsdet->fields['idventadet'];

                        ?>
                        <tr>
                            <td align="center"><?php echo antixss($rsdet->fields['idinsumo']); ?></td>
                            <td align="center"><?php echo antixss($rsdet->fields['barcode']); ?></td>
                            <td align="center"><?php echo antixss($rsdet->fields['producto']); ?></td>
                            <td align="right"><?php echo formatomoneda($rsdet->fields['cantidad'], 4, 'N');  ?></td>
                            <td align="right"><?php echo formatomoneda($rsdet->fields['pventa']);  ?></td>
                            <td align="right"><?php echo formatomoneda($rsdet->fields['subtotal']);  ?></td>
                        </tr>
                    <?php $rsdet->MoveNext();
                    } //$rs->MoveFirst();?>
                    </tbody>
                    </table>
                </div>
		
            </td>
        </tr>
        <?php $rs->MoveNext();
        } ?>
    </tbody>
</table>
</div>
<br />
