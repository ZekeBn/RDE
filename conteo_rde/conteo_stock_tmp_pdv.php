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
$guardar = antisqlinyeccion($_POST['guardar'], "int");


if ($guardar == 1) {

    $accion = antisqlinyeccion($_POST['accion'], "int");
    $cant = antisqlinyeccion($_POST['cant'], "int");
    $idprod = antisqlinyeccion($_POST['idprod'], "int");
    $lote = antisqlinyeccion($_POST['lote'], "text");
    $vencimiento = antisqlinyeccion($_POST['vencimiento'], "date");
    $id = antisqlinyeccion($_POST['id'], "int");




    ///////////////////////////////////////////////////////////////////////
    // pegando funcion para agregar al carrito

    $idconteo = intval($_POST['id']);
    if (intval($idconteo) == 0) {
        echo "No envio id";
        exit;
    }

    $consulta = "
	select *
	from conteo
	where
	estado <> 6
	and (estado = 1 or estado = 2)
	and idconteo = $idconteo
	and afecta_stock = 'N'
	and fecha_final is null
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $iddeposito = intval($rs->fields['iddeposito']);
    $idsucursal = intval($rs->fields['idsucursal']);
    if (intval($rs->fields['idconteo']) == 0) {
        echo "Conteo inexistente o finalizado";
        exit;
    }


    if (isset($_POST['accion']) && ($_POST['accion'] == 1)) {
        $accion = intval($_POST['accion']);
        $sumavent = strtoupper(substr(trim($_POST['sumavent']), 0, 1));
        //echo "accion:".$accion."<br />";

        // validaciones basicas
        $valido = "S";
        $errores = "";


        // recorrer y validar datos
        $totprodenv = 0;
        $totprodenv_ex = 0;
        //foreach($_POST as $key => $value){
        //$idproducto=intval(str_replace("cont_","",$key));
        //$cantidad=$value;
        $idproducto = intval($_POST['idprod']);
        $cantidad = floatval($_POST['cant']);
        $cantidad_contada = $cantidad;
        if (trim($cantidad) != '' && $idproducto > 0) {
            // busca que exista el insumo
            $idproducto = antisqlinyeccion($idproducto, 'int');
            $buscar = "Select idinsumo as idprod_serial, descripcion, estado, maneja_lote from insumos_lista where idinsumo=$idproducto";
            //echo $buscar;
            //exit;
            $rsin = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idproducto_ex = $rsin->fields['idprod_serial'];
            $maneja_lote = intval($rsin->fields['maneja_lote']);
            $descripcion = antisqlinyeccion($rsin->fields['descripcion'], "text");
            $estado_prod = $rsin->fields['estado'];
            // si el producto esta activo
            if ($estado_prod == 'A') {
                $totprodenv++;

                // si el producto fue borrado
            } else {
                // si la accion es diferente a continuar mas tarde
                if ($accion != 1) {
                    if ($idproducto_ex > 0) {
                        $errores .= "- El producto $descripcion con id: $idproducto, fue borrado.<br />";
                        $valido = "N";
                    } else {
                        $errores .= "- El producto $descripcion con id: $idproducto, no existe.<br />";
                        $valido = "N";
                    }
                }

            } // if($estado == 1){

        } // if(trim($cantidad) != '' && $idproducto > 0){

        //} // foreach($_POST as $key => $value){

        // si la accion es diferente a continuar mas tarde
        if ($accion != 1) {
            if (intval($totprodenv) == 0) {
                $errores .= "- No completaste ninguna cantidad.<br />";
                $valido = "N";
            }
        }


        if ($valido == 'S') {

            //foreach($_POST as $key => $value){
            /*$idproducto=intval(str_replace("cont_","",$key));
            $cantidad=$value;*/
            $idproducto = intval($_POST['idprod']);
            $cantidad = floatval($_POST['cant']);
            $lote = antisqlinyeccion($_POST['lote'], 'text');
            $vencimiento = antisqlinyeccion($_POST['vencimiento'], 'text');

            $cantidad_contada = $cantidad;

            //echo $accion;
            //exit;

            if (trim($_POST['cant']) != '' && $idproducto > 0) {

                // por si hay texto en el campo cantidad
                $cantidad = floatval($cantidad);
                $cantidad_contada = $cantidad;



                // stock disponible
                $consulta = "
				select sum(disponible) as total_stock,
				/*(
				select sum(venta_receta.cantidad) as venta
				from venta_receta 
				inner join ventas on ventas.idventa = venta_receta.idventa
				where 
				venta_receta.idproducto = gest_depositos_stock_gral.idproducto  
				and ventas.fecha >= '$fecha_inicio'
				and (select iddeposito from deposito where idsucursal = ventas.sucursal and tiposala = 2) = $iddeposito
				and ventas.estado <> 6
				) as venta,*/
				(
				select productos_sucursales.precio 
				from productos_sucursales
				where 
				productos_sucursales.idproducto = $idproducto
				and productos_sucursales.idsucursal = $idsucursal
				) as pventa
				from gest_depositos_stock_gral 
				where 
				gest_depositos_stock_gral.idproducto = $idproducto
				and gest_depositos_stock_gral.iddeposito = $iddeposito
				";
                $rsdisp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $disponible = floatval($rsdisp->fields['total_stock']);
                $pventa = floatval($rsdisp->fields['pventa']);
                $pcosto = 0;
                $cantidad_sistema = $disponible;

                /////////////////////////////////////////////////////////////////////////////////
                /////////////////////////////////////////////////////////////////////////////////
                /////////////////////////////////////////////////////////////////////////////////
                /////////////////////////AGREGAR LOTE Y VTO//////////////////////////////////////
                /////////////////////////////////////////////////////////////////////////////////
                /////////////////////////////////////////////////////////////////////////////////
                /////////////////////////////////////////////////////////////////////////////////

                $whereadd = "";
                if ($maneja_lote == 1) {
                    $whereadd = " and lote = $lote and (DATE_FORMAT(conteo_detalles.vto, '%Y-%m-%d') = DATE_FORMAT($vencimiento, '%Y-%m-%d'))";
                }


                // busca si existe ese producto en detalle para este conteo
                $consulta = "
				select * 
				from conteo_detalles 
				where 
				idconteo = $idconteo
				and idinsumo = $idproducto
				and idconteo in (select idconteo from conteo where idconteo = conteo_detalles.idconteo )
				$whereadd
				";
                //echo $consulta;
                $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                //calculos
                $venta = floatval($rsdisp->fields['venta']);
                $cantidad_contada = $cantidad;
                $cantidad_teorica = floatval($disponible);
                $cantidad_teorica_cv = $cantidad_teorica + $venta;
                $diferencia = $cantidad_contada - $cantidad_teorica;
                $diferencia_cv = $cantidad_contada - $cantidad_teorica_cv;
                $cantidad_venta = "0";
                if ($sumavent == 'S') {
                    $diferencia = $diferencia_cv;
                    $cantidad_venta = $venta;
                }
                $precio_venta = $pventa;
                $precio_costo = $pcosto;
                $diferencia_pv = $diferencia * $precio_venta;
                $diferencia_pc = $diferencia * $precio_costo;

                //echo $diferencia;
                //if($diferencia > 0){
                //	$cantidad_aumentar=$diferencia;
                //	echo "<br />Aum:".$cantidad_aumentar;
                //}
                //if($diferencia < 0){
                //	$cantidad_descontar=$diferencia*-1;
                //	echo "<br />Desc:".$cantidad_descontar;
                //}

                //exit;



                // si no existe inserta
                if (intval($rsex->fields['idinsumo']) == 0) {
                    $consulta = "
					insert into conteo_detalles
					(idconteo, idinsumo,  cantidad_contada,  cantidad_sistema, cantidad_venta, precio_venta, precio_costo, diferencia, diferencia_pv, diferencia_pc, descripcion, idusu, ubicacion,lote,vto)
					values
					($idconteo, $idproducto,  $cantidad_contada, $cantidad_sistema, $cantidad_venta, $precio_venta, $precio_costo, $diferencia, $diferencia_pv, $diferencia_pc, $descripcion, $idusu, $iddeposito,$lote,$vencimiento)
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                } else {
                    // si existe actualiza
                    $consulta = "
					update conteo_detalles
					set
						cantidad_contada=$cantidad_contada,
						cantidad_sistema=$cantidad_sistema,
						cantidad_venta=$cantidad_venta,
						precio_venta=$precio_venta, 
						precio_costo=$precio_costo,
						diferencia=$diferencia, 
						diferencia_pv=$diferencia_pv,
						diferencia_pc=$diferencia_pc,
						idusu=$idusu,
						ubicacion=$iddeposito
					where
						idinsumo=$idproducto
						and idconteo=$idconteo
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                }

                // Guardar
                if ($accion == 1) {

                    // estado guardado
                    $consulta = "
					update conteo 
					set 
					estado = 2,
					ult_modif = '$ahora',
					sumoventa = '$sumavent'
					where
					idconteo = $idconteo
					
					";
                    //$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

                } // if($accion == 1){



            } // if(trim($cantidad) != '' && $idproducto > 0){

            if (trim($_POST['cant']) == '' && $idproducto > 0) {

                $consulta = "
				delete from conteo_detalles					
				where
				idinsumo=$idproducto
				and idconteo=$idconteo
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $diferencia = "";
            }

            //} // foreach($_POST as $key => $value){



            // redireccionar
            //header("location: conteo.php");
            //echo $diferencia;


            //////////////////////////////////////////////////////////////////////////////////////

            // comentando exit ya que no actuara como endpoint  si no como un refresh de la
            // pantalla

            /////////////////////////////////////////////////


            // echo "Guardado: ".floatval($_POST['cant']);
            // exit;

        } // if($valido == 'S'){
    }



    ///////////////////fim de pegado



}








if (trim($_POST['codbar']) != '') {
    $whereadd .= " and (select productos.barcode from productos where productos.idprod_serial = insumos_lista.idproducto and productos.borrado = 'N') = $codbar ";
} elseif (trim($_POST['producto']) != '') {
    $whereadd .= " and insumos_lista.descripcion =  $producto ";
}




$consulta = "
select conteo.*,
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
SELECT 
  conteo_detalles.*, 
  (
    SELECT 
      nombre 
    FROM 
      grupo_insumos 
    where 
      idgrupoinsu = insumos_lista.idgrupoinsu
  ) as grupo, 
  (
    SELECT 
      nombre 
    FROM 
      medidas 
    where 
      idmedida = conteo_detalles.idmedida_ref
  ) as medida, 
  (
    select 
      barcode 
    from 
      productos 
    where 
      idprod_serial = insumos_lista.idproducto
  ) as codbar 
FROM 
  conteo_detalles 
  INNER JOIN conteo on conteo.idconteo = conteo_detalles.idconteo 
  inner join insumos_lista on insumos_lista.idinsumo = conteo_detalles.idinsumo 
WHERE 
  conteo_detalles.idconteo = $idconteo 
  and conteo.estado = 1

";
$rs2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$totreg = $rs2->RecordCount();
if ($totreg == 1) {

}


$consulta_total = "SELECT SUM(cantidad_contada) as totalpantalla FROM conteo_detalles 
INNER JOIN conteo on conteo.idconteo = conteo_detalles.idconteo
WHERE conteo.idconteo= $idconteo and conteo.estado=1";
$rs_conteo_total = $conexion->Execute($consulta_total) or die(errorpg($conexion, $consulta_total));


?><div id="resp"></div>
<p align="center">
<?php if (trim($_POST['codbar']) != '') { ?>
Filtrando por Codigo de Barras: <?php echo antixss($_POST['codbar']); ?> | [Borra Filtro]
<?php } elseif (trim($_POST['producto']) != '') {?>
Filtrando por Producto: <?php echo antixss($_POST['producto']); ?> | [Borra Filtro]
<?php } ?>
</p>
<br /><hr /><br />


<br />
<div class="table-responsive">
	<table width="900" border="1" class="table table-bordered jambo_table bulk_action">
	  <tbody>
		<tr>
		  <td align="center" bgcolor="#F8FFCC"><strong>Cod</strong></td>
		  <td align="center" bgcolor="#F8FFCC"><strong>Cod</strong></td>
		  <td align="center" bgcolor="#F8FFCC"><strong>Producto</strong></td>
		  <td width="80" align="center" bgcolor="#F8FFCC"><strong>Medida</strong></td>
		  <td width="80" align="center" bgcolor="#F8FFCC"><strong>lote</strong></td>
		  <td width="80" align="center" bgcolor="#F8FFCC"><strong>vencimiento</strong></td>
		  <td width="80" align="center" bgcolor="#F8FFCC"><strong>cantidad</strong></td>
		</tr>
	<?php
    $i = 1;
while (!$rs2->EOF) {

    // grupo de insumos
    $grupo = $rs2->fields['grupo'];
    $idinsumo = $rs2->fields['idinsumo'];

    if ($grupo != $grupoant) { ?>
		<tr>
		  <td colspan="7" bgcolor="#BFD2FF"><?php echo $grupo;?></td>
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



				<?php echo $rs2->fields['medida'] ?>




		  </td>
		
	<?php
    $diferencia = "";
    if (isset($_POST['cont_tmp_'.$idinsumo]) && trim($_POST['cont_tmp_'.$idinsumo]) != '') {
        $diferencia = floatval($_POST['cont_tmp_'.$idinsumo]) - floatval($rs2->fields['stock']);
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
	<td align="center" id="lote_tmp_<?php echo $i; ?>" ><?php if ($rs2->fields['lote'] > 0) {
	    echo $rs2->fields['lote'];
	} ?></td>
	<td align="center" id="vencimiento_tmp_<?php echo $i; ?>" ><?php if ($rs2->fields['vto'] > 0) {
	    echo $rs2->fields['vto'];
	} ?></td>
	<td align="center" id="dif_tmp_<?php echo $i; ?>" ><?php if ($rs2->fields['cantidad_contada'] > 0) {
	    echo formatomoneda($rs2->fields['cantidad_contada'], 4, 'N');
	} ?></td>
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




<div class="col-md-6 col-sm-12 col-xs-12 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Total Contabilizado Papel:</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="totalpapel" id="totalpapel" value="<?php  if (isset($_POST['totalpapel'])) {
	    echo htmlentities($_POST['totalpapel']);
	} else {
	    echo htmlentities($rs->fields['totalpapel']);
	}?>" placeholder="totalpapel" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-12 col-xs-12 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Total Contabilizado Pantalla:</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="totalpantalla" id="totalpantalla" value="<?php  if (isset($_POST['totalpantalla'])) {
	    echo htmlentities($_POST['totalpantalla']);
	} else {
	    echo intval(htmlentities($rs_conteo_total->fields['totalpantalla']));
	}?>" value="0" placeholder="0" class="form-control" readonly="readonly" />                    
	</div>
</div>

