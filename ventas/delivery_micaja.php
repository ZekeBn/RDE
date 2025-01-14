<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "212";
require_once("includes/rsusuario.php");

$consulta = "
select monto_endeliverycaja from preferencias_caja limit 1
";
$rsprefcaja = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$monto_endeliverycaja = $rsprefcaja->fields['monto_endeliverycaja'];

//Comprobar apertura de caja en fecha establecida
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$idcaja = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);

if ($idcaja == 0) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>" 	;
    exit;
}
if ($estadocaja == 3) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>" 	;
    exit;
}


if ($_GET['estado'] > 0) {
    $idestadodelivery = intval($_GET['estado']);
    $whereadd .= " and (select idestadodelivery from tmp_ventares_cab where tmp_ventares_cab.idventa = ventas.idventa) = $idestadodelivery ";
}
if ($_GET['idmotorista'] > 0) {
    $idmotorista = intval($_GET['idmotorista']);
    $whereadd .= " and ventas.idmotorista = $idmotorista ";
}
if (trim($_GET['ruc']) != '') {
    $ruc = antisqlinyeccion($_GET['ruc'], 'like');
    $whereadd .= " and ventas.ruc like '%$ruc%' ";
}
if (trim($_GET['razon_social']) != '') {
    $razon_social = antisqlinyeccion($_GET['razon_social'], 'like');
    $whereadd .= " and ventas.razon_social like '%$razon_social%' ";
}
if (trim($_GET['idventa']) != '') {
    $idventa = antisqlinyeccion($_GET['idventa'], 'text');
    $whereadd .= " and ventas.idventa = $idventa ";
}
if (trim($_GET['idpedido']) != '') {
    $idpedido = antisqlinyeccion($_GET['idpedido'], 'text');
    $whereadd .= " and ventas.idpedido = $idpedido ";
}
if (trim($_GET['nombre']) != '') {
    $nombre = antisqlinyeccion($_GET['nombre'], 'like');
    $whereadd .= " 
	and (
		select concat(nombres,' ',apellidos) as nomape
		from cliente_delivery 
		where 
		idclientedel = ventas.idclientedel
	) like '%$nombre%' ";
}
if (trim($_GET['telefono']) != '') {
    $telefono = antisqlinyeccion($_GET['telefono'], 'int');
    $whereadd .= " 
	and (
		select telefono
		from cliente_delivery 
		where 
		idclientedel = ventas.idclientedel
	) = $telefono ";
}


//&idmotorista=3&ruc=&razon_social=&nombre=&telefono=&idventa=&idpedido=

$consulta = "
select *, 
(select nombres from cliente_delivery where idclientedel = ventas.idclientedel) as nombres,
(select apellidos from cliente_delivery where idclientedel = ventas.idclientedel) as apellidos,
(select telefono from cliente_delivery where idclientedel = ventas.idclientedel) as telefono,
(select direccion from cliente_delivery_dom where iddomicilio = ventas.iddomicilio) as direccion,
(select estado_delivery from delivery_estado inner join tmp_ventares_cab on delivery_estado.idestadodelivery = tmp_ventares_cab.idestadodelivery where tmp_ventares_cab.idventa = ventas.idventa) as estado_delivery,
(select color from delivery_estado inner join tmp_ventares_cab on delivery_estado.idestadodelivery = tmp_ventares_cab.idestadodelivery where tmp_ventares_cab.idventa = ventas.idventa) as color,
(select motorista from motoristas where idmotorista = ventas.idmotorista) as motorista,
(select chapa from tmp_ventares_cab where tmp_ventares_cab.idventa = ventas.idventa) as chapa,
(
select nombre_motorista 
from app_rider_motorista 
inner join ventas_datosextra on ventas_datosextra.id_app_rider_motorista = app_rider_motorista.id_app_rider_motorista
where 
ventas_datosextra.idventa = ventas.idventa
) as nombre_motorista_app
from ventas 
where 
idcanal = 3 
and ventas.estado <> 6
and idcaja = $idcaja
$whereadd
order by fecha desc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
<script>
function filtra_deliv(idestadodelivery){
	document.location.href='delivery_micaja.php?estado='+idestadodelivery;

}
</script>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
              <!--<div class="title_left">
                <h3>Plain Page</h3>
              </div>-->

              <!--<div class="title_right">
                <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                  <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search for...">
                    <span class="input-group-btn">
                      <button class="btn btn-default" type="button">Go!</button>
                    </span>
                  </div>
                </div>
              </div>-->
            </div>

            <div class="clearfix"></div>
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Deliverys Enviados</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                      <!--<li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                        <ul class="dropdown-menu" role="menu">
                          <li><a href="#">Settings 1</a>
                          </li>
                          <li><a href="#">Settings 2</a>
                          </li>
                        </ul>
                      </li>
                      <li><a class="close-link"><i class="fa fa-close"></i></a>
                      </li>-->
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
  
					  
<form id="form1" name="form1" method="get" action="">


	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Estado del Delivery </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
// consulta
$consulta = "
SELECT idestadodelivery, estado_delivery, orden
FROM delivery_estado
where
estado = 1
order by orden asc
 ";

// valor seleccionado
if (isset($_GET['estado'])) {
    $value_selected = htmlentities($_GET['estado']);
} else {
    $value_selected = htmlentities($rs->fields['idestadodelivery']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'estado',
    'id_campo' => 'estado',

    'nombre_campo_bd' => 'estado_delivery',
    'id_campo_bd' => 'idestadodelivery',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' onchange="filtra_deliv(this.value);" ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Motorista *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idmotorista, motorista
FROM motoristas
where
estado = 1
order by motorista asc
 ";

// valor seleccionado
if (isset($_GET['idmotorista'])) {
    $value_selected = htmlentities($_GET['idmotorista']);
} else {
    $value_selected = '';
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idmotorista',
    'id_campo' => 'idmotorista',

    'nombre_campo_bd' => 'motorista',
    'id_campo_bd' => 'idmotorista',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>    
	</div>
</div>
	
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">RUC *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="ruc" id="ruc" value="<?php  if (trim($_GET['ruc']) != '') {
	    echo antixss($_GET['ruc']);
	} ?>" placeholder="RUC" class="form-control"  />                    
	</div>
</div>	

	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Razon Social *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="razon_social" id="razon_social" value="<?php  if (trim($_GET['razon_social']) != '') {
	    echo antixss($_GET['razon_social']);
	} ?>" placeholder="Razon Social" class="form-control" />                    
	</div>
</div>	

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombre" id="nombre" value="<?php  if (trim($_GET['nombre']) != '') {
	    echo antixss($_GET['nombre']);
	} ?>" placeholder="Nombre" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Telefono *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="telefono" id="telefono" value="<?php  if (trim($_GET['telefono']) != '') {
	    echo antixss($_GET['telefono']);
	} ?>" placeholder="Telefono" class="form-control"  />                    
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idventa *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idventa" id="idventa" value="<?php  if (trim($_GET['idventa']) != '') {
	    echo intval($_GET['idventa']);
	} ?>" placeholder="Idventa" class="form-control"  />                    
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idpedido *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idpedido" id="idpedido" value="<?php  if (trim($_GET['idpedido']) != '') {
	    echo intval($_GET['idpedido']);
	} ?>" placeholder="Idpedido" class="form-control"  />                    
	</div>
</div>
					  
<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Filtrar</button>
        </div>
    </div>

<br />
</form>
<div class="clearfix"></div>
<br /><br />
					  
					  
<div class="clearfix"></div>
<Hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Venta #</th>
            <th align="center">Fecha/Hora</th>
			<th align="center">Nombre y Apellido</th>
            <th align="center">Razon Social</th>
            <th align="center">RUC</th>
            <th align="center">Direccion</th>
            <th align="center">Telefono</th>
<?php if ($monto_endeliverycaja == 'S') { ?>
            <th align="center">Monto</th>
<?php } ?>
            <th align="center">Motorista</th>
            <th align="center">Estado</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="delivery_micaja_det.php?id=<?php echo $rs->fields['idventa']; ?>&estado=<?php echo intval($_GET['estado']); ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
                    <a href="delivery_micaja_est.php?id=<?php echo $rs->fields['idventa']; ?>&estado=<?php echo intval($_GET['estado']); ?>" class="btn btn-sm btn-default" title="Cambiar Estado" data-toggle="tooltip" data-placement="right"  data-original-title="Cambiar Estado"><span class="fa fa-edit"></span></a>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idventa']); ?></td>
			<td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha'])); ?></td>
			<td align="center"><?php echo antixss($rs->fields['nombres']." ".$rs->fields['apellidos']); ?><?php if (trim($rs->fields['chapa']) != '') {
			    echo " | ".antixss($rs->fields['chapa']);
			} ?></td>
            <td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['direccion']); ?></td>
            <td align="center">0<?php echo antixss($rs->fields['telefono']); ?></td>
<?php if ($monto_endeliverycaja == 'S') { ?>
            <td align="center"><?php echo formatomoneda($rs->fields['totalcobrar']); ?></td>
<?php } ?>
            <td align="center"><?php echo antixss($rs->fields['motorista']); ?><?php if (trim($rs->fields['nombre_motorista_app']) != '') {?><br />[<?php echo antixss($rs->fields['nombre_motorista_app']); ?>]<?php } ?></td>
            <td align="center" style="font-weight:bold;color:#FFF; background-color:<?php echo antixss($rs->fields['color']); ?>;"><?php echo antixss($rs->fields['estado_delivery']); ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />





                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
