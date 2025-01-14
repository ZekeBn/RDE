<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$dirsup = "S";
$submodulo = "162";
require_once("../includes/rsusuario.php");

if (intval($idconteo) == 0) {
    $idconteo = intval($_POST['id']);
    if (intval($idconteo) == 0) {
        header("location: conteo_stock.php");
        exit;
    }
}

// filtros
$whereadd = "";
$codbar = antisqlinyeccion($_POST['codbar'], "text");
$producto = antisqlinyeccion($_POST['producto'], "text");
if (trim($_POST['codbar']) != '') {
    $whereadd .= " and (select productos.barcode from productos where productos.idprod_serial = insumos_lista.idproducto and productos.borrado = 'N') = $codbar ";
} elseif (trim($_POST['producto']) != '') {
    $whereadd .= " and insumos_lista.descripcion =  $producto ";
}




$consulta = "
select *,
(select descripcion from gest_depositos where iddeposito = conteo.iddeposito)  as deposito,
(select estadoconteo from estado_conteo where idestadoconteo = conteo.estado ) as estadoconteo,
(select usuario from usuarios where idusu = conteo.iniciado_por) as usuario
from conteo
where
estado <> 6
and (estado = 1 or estado = 2)
and idconteo = $idconteo
and afecta_stock = 'N'
and fecha_final is null
and idempresa = $idempresa
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito = intval($rs->fields['iddeposito']);
if (intval($rs->fields['idconteo']) == 0) {
    header("location: conteo_stock.php");
    exit;
}
//$fecha_inicio=date("Y-m-d");
$fecha_inicio = date("Y-m-d H:i:s", strtotime($rs->fields['inicio_registrado_el']));
$consulta = "
select *,
(SELECT nombre FROM grupo_insumos where idgrupoinsu = insumos_lista.idgrupoinsu) as grupo,
(select barcode from productos where idprod_serial = insumos_lista.idproducto) as codbar,
(SELECT nombre FROM medidas where id_medida = insumos_lista.idmedida) as medida,
(SELECT sum(disponible) FROM gest_depositos_stock_gral where idproducto = insumos_lista.idinsumo and iddeposito = $iddeposito and idempresa = $idempresa) as stock,
(
select sum(venta_receta.cantidad) as venta
from venta_receta 
inner join ventas on ventas.idventa = venta_receta.idventa
where 
venta_receta.idinsumo = insumos_lista.idinsumo  
and ventas.fecha >= '$fecha_inicio'
and (select iddeposito from gest_depositos where idsucursal = ventas.sucursal  and tiposala = 2) = $iddeposito
and ventas.estado <> 6
) as venta,
(select p1 from productos where idprod_serial = insumos_lista.idproducto and productos.borrado = 'N' and productos.idempresa = $idempresa) as pventa,
(select cantidad_contada from conteo_detalles where idconteo = $idconteo and idinsumo = insumos_lista.idinsumo) as cantidad_contada
from insumos_lista 
where 
insumos_lista.idgrupoinsu in (SELECT idgrupoinsu FROM conteo_grupos where idconteo = $idconteo)
and insumos_lista.idempresa = $idempresa
and insumos_lista.estado = 'A'
and insumos_lista.hab_invent = 1
$whereadd
order by (SELECT nombre FROM grupo_insumos where idgrupoinsu = insumos_lista.idgrupoinsu) asc, descripcion asc
";
$rs2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$totreg = $rs2->RecordCount();
if ($totreg == 1) {

}

?><div id="resp"></div>
<p align="center">
<?php if (trim($_POST['codbar']) != '') { ?>
Filtrando por Codigo de Barras: <?php echo antixss($_POST['codbar']); ?> | [Borra Filtro]
<?php } elseif (trim($_POST['producto']) != '') {?>
Filtrando por Producto: <?php echo antixss($_POST['producto']); ?> | [Borra Filtro]
<?php } ?>
</p>
<br /><hr /><br />

<p align="center">* Ventas desde: <?php echo date("d/m/Y H:i:s", strtotime($fecha_inicio)); ?></p>

<br />
<div class="table-responsive">
	<table width="900" border="1" class="table table-bordered jambo_table bulk_action">
	  <tbody>
		<tr>
		  <td align="center" bgcolor="#F8FFCC"><strong>Cod</strong></td>
		  <td align="center" bgcolor="#F8FFCC"><strong>Cod</strong></td>
		  <td align="center" bgcolor="#F8FFCC"><strong>Producto</strong></td>
		  <td width="80" align="center" bgcolor="#F8FFCC"><strong>Medida</strong></td>
		  <td width="80" align="center" bgcolor="#F8FFCC"><strong>Contabilizado</strong></td>
		  <td width="80" align="center" bgcolor="#F8FFCC"><strong>Lote</strong></td>
		  <td width="80" align="center" bgcolor="#F8FFCC"><strong>Vencimiento</strong></td>
		  <td width="80" align="center" bgcolor="#F8FFCC"><strong>Almacenamiento</strong></td>
		  <td width="80" align="center" bgcolor="#F8FFCC"><strong>Tipo Almacenamiento</strong></td>
		  <td width="80" align="center" bgcolor="#F8FFCC"><strong>Fila</strong></td>
		  <td width="80" align="center" bgcolor="#F8FFCC"><strong>Columna</strong></td>
		</tr>
	<?php
    $i = 1;
while (!$rs2->EOF) {

    // grupo de insumos
    $maneja_lote = $rs2->fields['maneja_lote'];
    $grupo = $rs2->fields['grupo'];
    $idinsumo = $rs2->fields['idinsumo'];

    if ($grupo != $grupoant) { ?>
		<tr>
		  <td colspan="8" bgcolor="#BFD2FF"><?php echo $grupo;?></td>
		  </tr>
	<?php } ?>
		<tr>
		  <td align="center"><?php echo $rs2->fields['idinsumo']; ?></td>
		  <td align="center"><?php echo $rs2->fields['codbar']; ?></td>
		  <td><?php echo $rs2->fields['descripcion']; ?></td>
		  <td width="100" align="center">
			<?php // echo $rs2->fields['medida'];?>
			<?php

                $medida = antixss($rs2->fields['medida']);
    $idmedida = intval($rs2->fields['idmedida']);
    $idmedida2 = intval($rs2->fields['idmedida2']);
    $cant_medida2 = intval($rs2->fields['cant_medida2']);
    $idmedida3 = intval($rs2->fields['idmedida3']);
    $cant_medida3 = intval($rs2->fields['cant_medida3']);
    $bandera_cant_medida2 = false;
    $bandera_cant_medida3 = false;
    if ($idmedida2 > 0 && $cant_medida2 > 0) {
        $bandera_cant_medida2 = true;
    }
    if ($idmedida3 > 0 && $cant_medida3 > 0) {
        $bandera_cant_medida3 = true;
    }
    // valor seleccionado
    if (isset($_POST['idmedida'])) {
        $value_selected = htmlentities($_POST['idmedida']);
    } else {
        $value_selected = $idmedida;
    }
    // opciones
    $opciones = [
        "$medida" => $idmedida
    ];
    if ($bandera_cant_medida2) {
        $consulta = "SELECT nombre from medidas where id_medida = $idmedida2";
        $respuesta_nombre = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $medida2 = $respuesta_nombre->fields['nombre'];
        $opciones["$medida2"] = $idmedida2;
    }
    if ($bandera_cant_medida3) {
        $consulta = "SELECT nombre from medidas where id_medida = $idmedida3";
        $respuesta_nombre = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $medida3 = $respuesta_nombre->fields['nombre'];
        $opciones["$medida3"] = $idmedida3;
    }


    ?>



				<select class="custom-select form-control" name="medida_select_<?php echo $i?>" id="medida_select_<?php echo $i?>">
					<option  data-hidden-orden="0" data-hidden-value="0" selected value="<?php echo $idmedida ?>"><?php echo $medida ?></option>
					<?php if ($bandera_cant_medida2) { ?>
						<option  data-hidden-orden="1" data-hidden-value="<?php echo $cant_medida2 ?>" value="<?php echo $idmedida2 ?>"><?php echo $medida2 ?></option>
					<?php } ?>
					<?php if ($bandera_cant_medida3) { ?>
						<option data-hidden-orden="2" data-hidden-value="<?php echo $cant_medida3 * $cant_medida2 ?>" value="<?php echo $idmedida3 ?>"><?php echo $medida3 ?></option>
					<?php } ?>					
				</select>




		  </td>
		  <td align="center"><input name="cont_<?php echo $idinsumo?>" type="number" id="cont_<?php echo $i; ?>" size="8" maxlength="8" style="height:30px; width:100%" onchange="calcular_dif(<?php echo $i; ?>);" onkeypress="return solonumerosypuntoycoma(event);" value="<?php

    if (isset($_POST['cont_'.$idinsumo]) && trim($_POST['cont_'.$idinsumo]) != '') {
        echo floatval($_POST['cont_'.$idinsumo]);
    } else {
        if (trim($rs2->fields['cantidad_contada']) != '') {
            echo str_replace(',', '.', formatomoneda($rs2->fields['cantidad_contada'], 4, 'N'));
        }
    }

    ?>" <?php if ($totreg == 1) { ?>autofocus<?php } ?>  /></td>
	<?php if ($maneja_lote == 1) { ?>
		
		<td align="center"  >
			<input type="text" name="lote_<?php echo $i; ?>" id="lote_<?php echo $i; ?>"  value="<?php echo htmlentities($_POST['lote']);?>" placeholder="lote"  />
		</td>
		<td align="center"  >
			<input type="date" name="vencimiento_<?php echo $i; ?>" id="vencimiento_<?php echo $i; ?>" value="<?php  if (isset($_POST['vencimiento'])) {
			    echo htmlentities($_POST['vencimiento']);
			} else {
			    echo htmlentities($rs->fields['vencimiento']);
			}?>" placeholder="vencimiento"  />
		</td>
	<?php } ?>
	
	<td align="center"  >
					<?php

                    // consulta

                    $consulta = "
					SELECT idtipo, descripcion
					FROM tipo_moneda
					where
					estado = 1
					order by descripcion asc
					";

    // valor seleccionado
    if (isset($_POST['idmoneda'])) {
        $value_selected = htmlentities($_POST['idmoneda']);
    } else {
        $value_selected = $id_moneda_nacional;
    }



    // parametros
    $parametros_array = [
        'nombre_campo' => 'idmoneda',
        'id_campo' => 'idmoneda',

        'nombre_campo_bd' => 'descripcion',
        'id_campo_bd' => 'idtipo',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" "'.$add,
        'autosel_1registro' => 'N'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?>
	</td>


	<?php
    $diferencia = "";
    if (isset($_POST['cont_'.$idinsumo]) && trim($_POST['cont_'.$idinsumo]) != '') {
        $diferencia = floatval($_POST['cont_'.$idinsumo]) - floatval($rs2->fields['stock']);
    } else {
        if (trim($rs2->fields['cantidad_contada']) != '') {
            $diferencia = floatval($rs2->fields['cantidad_contada']) - floatval($rs2->fields['stock']);
        }
    }
    if ($diferencia < 0) {
        $colord = "FF0000";
    } else {
        $colord = "000000";
    }


    ?>
	
	
	<!-- <td align="center" id="dif_<?php echo $i; ?>" ><?php if ($rs2->fields['cantidad_contada'] > 0) {
	    echo formatomoneda($rs2->fields['cantidad_contada'], 4, 'N');
	} ?></td> -->
		</tr>
	<?php
    $grupoant = $grupo;
    $i++;
    $rs2->MoveNext();
} ?>
	  </tbody>
	</table>
</div><br />
<form id="form1" name="form1" method="post" action="" enctype="application/json">
<input type="hidden" name="accion" id="accion" value="0" />

<br />
</form>