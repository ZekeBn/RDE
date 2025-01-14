<?php
require_once("../../includes/conexion.php");
require_once("../../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
$dirsup_sec = "S";

require_once("../../includes/rsusuario.php");

$idproducto = intval($_POST['idproducto']);


if (!function_exists("isNatural")) {
    function isNatural($var)
    {
        return preg_match('/^[0-9]+$/', (string )$var) ? true : false;
    }
}
// si edita varios registros de un mismo producto
if ($idproducto > 0) {
    $consulta = "
	select productos.descripcion as producto,  sum(tmp_ventares.cantidad) as cantidad, max(tmp_ventares.precio) as precio
	from tmp_ventares 
	inner join productos on tmp_ventares.idproducto = productos.idprod_serial
	where 
	registrado = 'N'
	and tmp_ventares.usuario = $idusu
	and tmp_ventares.borrado = 'N'
	and tmp_ventares.finalizado = 'N'
	and tmp_ventares.idsucursal = $idsucursal
	and tmp_ventares.idtipoproducto not in (2,3,4)
	and (
		select tmp_ventares_agregado.idventatmp 
		from tmp_ventares_agregado 
		WHERE 
		tmp_ventares_agregado.idventatmp = tmp_ventares.idventatmp
		limit 1
	) is null
	and (
		select tmp_ventares_sacado.idventatmp 
		from tmp_ventares_sacado 
		WHERE 
		tmp_ventares_sacado.idventatmp = tmp_ventares.idventatmp
		limit 1
	) is null
	and productos.idprod_serial = $idproducto
	group by productos.descripcion
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cantidad_original = floatval($rs->fields['cantidad']);
    $consulta = "
	select productos.idprod_serial, productos.descripcion, productos.precio_abierto,
	productos.precio_min, productos.precio_max
	from productos
	where 
	productos.idprod_serial = $idproducto
	limit 1
	";
    $rsp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $precio_unitario_old = floatval($rs->fields['precio']);
}
// registrar
if ($_POST['reg'] == 'S') {

    $valido = "S";
    $errores = "";

    $cantidad_nueva = floatval($_POST['cantidad']);
    $precio_unitario_new = floatval($_POST['precio_unitario']);

    if (floatval($_POST['cantidad']) <= 0) {
        $valido = "N";
        $errores .= "- No indico la cantidad.".$saltolinea;
    }
    // igual a la consulta de arriba pero sin agrupar
    $consulta = "
	select tmp_ventares.idventatmp, productos.idmedida, tmp_ventares.precio
	from tmp_ventares 
	inner join productos on tmp_ventares.idproducto = productos.idprod_serial
	where 
	registrado = 'N'
	and tmp_ventares.usuario = $idusu
	and tmp_ventares.borrado = 'N'
	and tmp_ventares.finalizado = 'N'
	and tmp_ventares.idsucursal = $idsucursal
	and tmp_ventares.idtipoproducto not in (2,3,4)
	and (
		select tmp_ventares_agregado.idventatmp 
		from tmp_ventares_agregado 
		WHERE 
		tmp_ventares_agregado.idventatmp = tmp_ventares.idventatmp
		limit 1
	) is null
	and (
		select tmp_ventares_sacado.idventatmp 
		from tmp_ventares_sacado 
		WHERE 
		tmp_ventares_sacado.idventatmp = tmp_ventares.idventatmp
		limit 1
	) is null
	and productos.idprod_serial = $idproducto
	order by tmp_ventares.idventatmp desc
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idmedida = $rs->fields['idmedida'];
    // si es unitario
    if ($idmedida == 4) {
        // si es unitaria valida que no tenga decimales
        if (!isNatural($cantidad_nueva)) {
            $valido = "N";
            $errores .= "- No puede fraccionar un producto unitario.".$saltolinea;
        }
    }

    // producto<br />




    // si todo es valido
    if ($valido == 'S') {




        // si la medida es unitaria
        if ($idmedida == 4) {

            // si la cantidad nueva es menor a la actual
            if ($cantidad_nueva < $cantidad_original) {

                $idventatmp = $rs->fields['idventatmp'];
                //agrega 1 registro con ese producto y la cantidad nueva
                $consulta = "
				INSERT INTO tmp_ventares
				(
				idproducto, idtipoproducto, cantidad, precio, fechahora, usuario, registrado, idsucursal, idempresa, 
				receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,subtotal,idlistaprecio
				) 
				select 
				idproducto, idtipoproducto, $cantidad_nueva, precio, fechahora, usuario, registrado, idsucursal, idempresa, 
				receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,(precio*$cantidad_nueva),idlistaprecio
				from tmp_ventares
				where
				tmp_ventares.idventatmp = $idventatmp
				;
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                //luego borra todo menos ese registro
                while (!$rs->EOF) {
                    $idventatmp = $rs->fields['idventatmp'];
                    // borra los detalles que contienen ese producto
                    $consulta = "
					update tmp_ventares
					set borrado = 'S'
					where
					idventatmp = $idventatmp
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    $rs->MoveNext();
                }




            } // if($cantidad_nueva > $cantidad_original){

            // si la cantidad nueva es mayor a la actual, agrega la diferencia
            if ($cantidad_nueva > $cantidad_original) {
                $idventatmp = $rs->fields['idventatmp'];
                $diferencia = $cantidad_nueva - $cantidad_original;
                //echo "a";
                //echo $cantidad_nueva;
                //echo $cantidad_original;
                //echo $diferencia;exit;
                // si es menor o igual a 10 inserta 1 registro por cada cantidad
                if ($diferencia <= 10) {
                    for ($i = 1;$i <= $diferencia;$i++) {
                        $consulta = "
						INSERT INTO tmp_ventares
						(
						idproducto, idtipoproducto, cantidad, precio, fechahora, usuario, registrado, idsucursal, idempresa, 
						receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,subtotal,idlistaprecio
						) 
						select 
						idproducto, idtipoproducto, 1, precio, fechahora, usuario, registrado, idsucursal, idempresa, 
						receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,precio,idlistaprecio
						from tmp_ventares
						where
						tmp_ventares.idventatmp = $idventatmp
						;
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    }
                } else {
                    $consulta = "
						INSERT INTO tmp_ventares
						(
						idproducto, idtipoproducto, cantidad, precio, fechahora, usuario, registrado, idsucursal, idempresa, 
						receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,subtotal,idlistaprecio
						) 
						select 
						idproducto, idtipoproducto, $diferencia, precio, fechahora, usuario, registrado, idsucursal, idempresa, 
						receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,subtotal,idlistaprecio
						from tmp_ventares
						where
						tmp_ventares.idventatmp = $idventatmp
						;
						";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                    $consulta = "
						select max(idventatmp) as idventatmp from tmp_ventares where usuario = $idusu
						";
                    $rsult = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    $idventatmp_new = $rsult->fields['idventatmp'];

                    $consulta = "
						update tmp_ventares
						set 
						subtotal = cantidad*precio
						where
						idventatmp = $idventatmp_new
						";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                }
                $consulta = "
				update tmp_ventares
				set 
				subtotal = cantidad*precio
				where
				idventatmp = $idventatmp
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            }

            // si la medida no es unitaria
        } else {
            // edita el primer registro
            $i = 1;
            while (!$rs->EOF) {
                $idventatmp = $rs->fields['idventatmp'];
                if ($i == 1) {
                    $consulta = "
					update tmp_ventares
					set 
					cantidad = $cantidad_nueva,
					subtotal = $cantidad_nueva*precio
					where
					idventatmp = $idventatmp
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    /*if($precio_unitario_new != $precio_unitario_old){
                        $consulta="
                        update tmp_ventares
                        set
                        precio = $precio_unitario_new,
                        subtotal = cantidad*$precio_unitario_new
                        where
                        idventatmp = $idventatmp
                        ";
                        $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
                    }*/
                } else {
                    // borra los siguientes registros
                    $consulta = "
					update tmp_ventares
					set 
					borrado = 'S'
					where
					idventatmp = $idventatmp
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                }
                $i++;
                $rs->MoveNext();
            }

        }

        // si la cantidad es igual a la actual, no edita cantidad solo precio si aplica
        if ($cantidad_nueva == $cantidad_original) {
            //echo $precio_unitario_new.'-';
            //echo $precio_unitario_old;
            if ($rsp->fields['precio_abierto'] == 'S') {
                if ($precio_unitario_new != $precio_unitario_old) {
                    $rs->MoveFirst();

                    while (!$rs->EOF) {
                        $idventatmp = $rs->fields['idventatmp'];
                        $consulta = "
						update tmp_ventares
						set 
						precio = $precio_unitario_new,
						subtotal = cantidad*$precio_unitario_new
						where 
						idventatmp = $idventatmp
						";
                        //echo $consulta;exit;
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                        $rs->MoveNext();
                    }
                }
            }
        }


    }

    $arr = [
    'valido' => $valido,
    'errores' => $errores
    ];

    // convierte a formato json
    $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

    // devuelve la respuesta formateada
    echo $respuesta;
    exit;

}
?>
<div class="alert alert-danger alert-dismissible fade in" role="alert" id="error_box" style="display:none;">
<strong>Errores:</strong><br />
<span id="error_box_msg"></span>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Producto *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="producto" id="producto" value="<?php  if (isset($_POST['producto'])) {
	    echo htmlentities($_POST['producto']);
	} else {
	    echo htmlentities($rs->fields['producto']);
	}?>" placeholder="Producto" class="form-control" disabled />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Cantidad *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="cantidad_edit" id="cantidad_edit" value="<?php  if (isset($_POST['cantidad_edit'])) {
	    echo floatval($_POST['cantidad_edit']);
	} else {
	    echo floatval($rs->fields['cantidad']);
	}?>" placeholder="Cantidad" class="form-control" required="required" autofocus="autofocus" onkeyup="edita_cant_reg_enter(<?php echo $idproducto ?>,event);" />                    
	</div>
</div>

<?php if ($rsp->fields['precio_abierto'] == 'S') { ?>
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Precio unitario  *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="precio_unitario_edit" id="precio_unitario_edit" value="<?php  if (floatval($_POST['precio_unitario_edit']) > 0) {
	    echo floatval($_POST['precio_unitario_edit']);
	} else {
	    echo floatval($rs->fields['precio']);
	}?>"  min="<?php echo floatval($rsp->fields['precio_min']); ?>" max="<?php echo floatval($rsp->fields['precio_max']); ?>" class="form-control" required="required" placeholder="(<?php echo formatomoneda($rsp->fields['precio_min']); ?> - <?php echo formatomoneda($rsp->fields['precio_max']); ?>)"   />                    
	</div>
</div>
<?php } ?>



<br />




<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
	   <button type="button" class="btn btn-success" onMouseUp="edita_cant_reg(<?php echo $idproducto ?>);"><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="$('#modal_ventana').modal('hide');"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

<br />

<div class="clearfix"></div>
<br /><br />