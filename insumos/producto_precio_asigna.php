<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";
$dirsup = "S";
require_once("../includes/rsusuario.php");



$idproducto = intval($_GET['id']);
if (intval($idproducto) == 0) {
    header("location: gest_listado_productos.php");
    exit;
}

// insertamos en sucursales
$consulta = "
INSERT IGNORE INTO productos_sucursales (idproducto, idsucursal, idempresa, precio, activo_suc) 
select idprod_serial as idproducto, sucursales.idsucu as idsucursal, empresas.idempresa as idempresa, p1 as precio, 1 as activo_suc
from 
productos, sucursales, empresas 
where 
productos.idprod_serial 
not in 
(select productos_sucursales.idproducto from productos_sucursales where idempresa = empresas.idempresa and idsucursal = sucursales.idsucu )
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// insertamos en lista precios
$consulta = "
INSERT IGNORE INTO productos_listaprecios
( idlistaprecio, idproducto, idsucursal, estado, 
precio, reg_por, reg_el) 
select 
lista_precios_venta.idlistaprecio, productos_sucursales.idproducto, idsucursal, 1, 
(precio*(lista_precios_venta.recargo_porc/100))+precio, NULL, NULL 
from productos_sucursales, lista_precios_venta
where
productos_sucursales.idproducto = $idproducto
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// actualizar lista precios con recargo automatico // ((precio*(lista_precios_venta.recargo_porc/100))+precio)
$consulta = "
update productos_listaprecios
set
precio = 
	COALESCE(
		(
		select 
		CASE redondeo_direccion
		WHEN 'A' THEN CEIL(((precio*(lista_precios_venta.recargo_porc/100))+precio)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
		WHEN 'B' THEN FLOOR(((precio*(lista_precios_venta.recargo_porc/100))+precio)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
		ELSE
			ROUND(((precio*(lista_precios_venta.recargo_porc/100))+precio)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
		END as redondeado
		from productos_sucursales, lista_precios_venta
		where
		productos_sucursales.idproducto = productos_listaprecios.idproducto
		and productos_sucursales.idsucursal = productos_listaprecios.idsucursal
		and lista_precios_venta.idlistaprecio = productos_listaprecios.idlistaprecio
		)
	,0),
reg_por = $idusu,
reg_el = '$ahora'
where
idlistaprecio in (select idlistaprecio from lista_precios_venta where recargo_porc <> 0)
and idlistaprecio > 1
and idproducto = $idproducto
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// busca si el producto es solo conversion
$consulta = "
select * from insumos_lista where idproducto = $idproducto
";
$rsins = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// si es solo conversion
if ($rsins->fields['solo_conversion'] == 1) {
    // desactiva para la venta
    $consulta = "
	update productos_sucursales set activo_suc = 0 where idproducto = $idproducto
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}



$buscar = "
select * 
from productos_sucursales
inner join productos on productos.idprod_serial = productos_sucursales.idproducto
inner join sucursales on sucursales.idsucu = productos_sucursales.idsucursal
where
productos.idprod_serial = $idproducto
and productos.borrado = 'N'
and productos.idempresa = $idempresa
and productos_sucursales.idempresa = $idempresa
and sucursales.estado = 1
order by sucursales.nombre asc
"	;
$rsprodsuc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idproducto = $rsprodsuc->fields['idprod_serial'];
if (intval($idproducto) == 0) {
    header("location: gest_listado_productos.php");
    exit;
}

// crea imagen
$img = "gfx/productos/prod_".$idproducto.".jpg";
if (!file_exists($img)) {
    $img = "gfx/productos/prod_0.jpg";
}



if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    //print_r($_POST);
    //exit;
    if ($rsins->fields['solo_conversion'] != 1) {

        $consulta = "
		select * from sucursales where sucursales.estado = 1
		";
        $rssucus = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        while (!$rssucus->EOF) {
            // recibe parametros
            $idsucursal = $rssucus->fields['idsucu'];
            $precio = floatval($_POST['precio_'.$idsucursal]);
            $activo_suc = substr(trim($_POST['activo_suc_'.$idsucursal]), 0, 2);
            // conversiones
            if ($activo_suc != 'on') {
                $activo_suc = 0;
            } else {
                $activo_suc = 1;
            }

            $nuevoac = $activo_suc;

            $buscar = "Select * from productos_sucursales where idproducto=$idproducto and  idsucursal = $idsucursal" ;
            $rslp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $porig = floatval($rslp->fields['precio']);
            $anteac = intval($rslp->fields['activo_suc']);

            $consulta = "
			update productos_sucursales 
			set
			precio = $precio,
			activo_suc = $activo_suc
			where
			idsucursal = $idsucursal
			and idproducto = $idproducto
			";
            //echo $consulta."<br />";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            $consulta = "
			update productos_listaprecios
			set
			estado = $activo_suc
			where
			idsucursal = $idsucursal
			and idproducto = $idproducto
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            if ($anteac != $nuevoac) {
                $cambioactivo = 1;
            } else {
                $cambioactivo = 0;
            }
            //logueamos x cambio de precio
            if ($precio != $porig) {

                $insertar = "Insert into cambios_precios (fecha,cambiado_por,valor_orig,nuevo_precio,idsucursal,activo_ant,activo_nuevo,idproducto,cambio_activo) 
				values
				('$ahora',$idusu,$porig,$precio,$idsucursal,$anteac,$nuevoac,$idproducto,$cambioactivo)";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

            } else {
                //logueamos x cambio de estado
                if ($anteac != $nuevoac) {

                    $insertar = "Insert into cambios_precios (fecha,cambiado_por,valor_orig,nuevo_precio,idsucursal,activo_ant,activo_nuevo,idproducto,cambio_activo) 
					values
					('$ahora',$idusu,$porig,$precio,$idsucursal,$anteac,$nuevoac,$idproducto,$cambioactivo)";
                    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
                }
            }


            $rssucus->MoveNext();
        }

        // actualizar lista precios 1
        $consulta = "
			update productos_listaprecios
			set
			precio = COALESCE((
			select precio 
			from productos_sucursales 
			where
			productos_sucursales.idproducto = productos_listaprecios.idproducto
			and productos_sucursales.idsucursal = productos_listaprecios.idsucursal
			),0),
			reg_por = $idusu,
			reg_el = '$ahora'
			where
			idlistaprecio = 1
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // actualizar lista precios con recargo automatico
        /*$consulta="
        update productos_listaprecios
        set
        precio = COALESCE((select (precio*(lista_precios_venta.recargo_porc/100))+precio
        from productos_sucursales, lista_precios_venta
        where
        productos_sucursales.idproducto = productos_listaprecios.idproducto
        and productos_sucursales.idsucursal = productos_listaprecios.idsucursal
        and lista_precios_venta.idlistaprecio = productos_listaprecios.idlistaprecio),0),
        reg_por = $idusu,
        reg_el = '$ahora'
        where
        idlistaprecio in (select idlistaprecio from lista_precios_venta where recargo_porc <> 0)
        and idlistaprecio > 1
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));*/
        $consulta = "
			update productos_listaprecios
			set
			precio = 
				COALESCE(
					(
					select 
					CASE redondeo_direccion
					WHEN 'A' THEN CEIL(((precio*(lista_precios_venta.recargo_porc/100))+precio)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
					WHEN 'B' THEN FLOOR(((precio*(lista_precios_venta.recargo_porc/100))+precio)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
					ELSE
						ROUND(((precio*(lista_precios_venta.recargo_porc/100))+precio)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
					END as redondeado
					from productos_sucursales, lista_precios_venta
					where
					productos_sucursales.idproducto = productos_listaprecios.idproducto
					and productos_sucursales.idsucursal = productos_listaprecios.idsucursal
					and lista_precios_venta.idlistaprecio = productos_listaprecios.idlistaprecio
					)
				,0),
			reg_por = $idusu,
			reg_el = '$ahora'
			where
			idlistaprecio in (select idlistaprecio from lista_precios_venta where recargo_porc <> 0)
			and idlistaprecio > 1
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



        header("location: producto_precio_asigna.php?id=".$idproducto.'&ok=s');
        exit;

    } else {

        header("location: producto_precio_asigna.php?id=".$idproducto.'');
        exit;
    }



}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("../includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="../ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="../ani/css/style2.css" />
<link rel="stylesheet" type="text/css" href="../css/magnific-popup.css" />
<?php require("../includes/head.php"); ?>
<script src="../js/sweetalert.min.js"></script>
<script>
function alertar(titulo,error,tipo,boton){
	swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
	}
</script>
<link rel="stylesheet" type="text/css" href="css/sweetalert.css">
<style>
#idmarca{
	width:90%; 
	height:40px;
}
</style>
</head>
<body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Asignar precio a productos por sucursal</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">









	<div class="clear"></div>
		<div class="cuerpo">
            <div align="center">
                <?php require_once("../includes/menuarriba.php");?>
            </div>
			<div class="clear"></div><!-- clear1 -->
			<div class="colcompleto" id="contenedor">
			<a href="../productos.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
             <div align="center">
    		
    </div>
 				<div class="divstd">
					<span class="resaltaditomenor"></span>
				</div>

<br />
<div style="border:1px solid #000; text-align:center; width:500px; margin:0px auto; padding:5px;">
<strong>Editando:</strong><br /><br />
<h1 align="center" style="font-weight:bold; margin:0px; padding:0px; color:#0A9600;"><?php echo trim($rsprodsuc->fields['descripcion']) ?></h1>

<input class="btn btn-sm btn-default" type="button" name="button" id="button" value="Cambiar" onmouseup="document.location.href='../gest_editar_productos.php'" />

</div>
<br />
<form id="form1" name="form1" method="post" action="">

<table class="table table-bordered jambo_table bulk_action" >
  <tbody>
    <tr>
      <td align="center" bgcolor="#F8FFCC" height="40" width="300"><strong>Sucursal</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Muestra en Sucursal</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Precio</strong></td>
      </tr>
<?php while (!$rsprodsuc->EOF) {
    $idsucursal = $rsprodsuc->fields['idsucursal'];

    ?>
    <tr>
      <td align="center" height="40"><?php echo $rsprodsuc->fields['nombre']; ?></td>
      <td align="center"><input type="checkbox" name="activo_suc_<?php echo $idsucursal; ?>" id="activo_suc_<?php echo $idsucursal; ?>" <?php if ($rsprodsuc->fields['activo_suc'] == 1) { ?>checked="checked"<?php } ?> /></td>
      <td align="center">
        <input type="text" name="precio_<?php echo $idsucursal; ?>" id="precio_<?php echo $idsucursal; ?>" value="<?php echo intval($rsprodsuc->fields['precio']); ?>" style="text-align:right;" />
		<input name="ocpsucu_<?php echo $idsucursal; ?>" id="ocpsucu_<?php echo $idsucursal; ?>" value="<?php echo $idsucursal; ?>" type="hidden" />
		</td>
      </tr>
<?php $rsprodsuc->MoveNext();
} ?>
  </tbody>
</table>
<p align="center">&nbsp;</p>
<p align="center">
  <input type="submit" class="btn btn-sm btn-success" name="submit" id="submit" value="Guardar" />
  <input type="hidden" name="MM_update" id="MM_update" value="form1" />
</p>
</form>

  			</div> <!-- contenedor -->
  		
 

                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>