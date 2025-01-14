<?php
require_once("../../includes/conexion.php");
require_once("../../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "129";
$dirsup_sec = "S";

require_once("../../includes/rsusuario.php");

$consulta = "select usa_lista_zonas from preferencias";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$usarzonadelivery = trim($rspref->fields['usa_lista_zonas']);

// para evitar error de zonas
$consulta = "
Select idzona,descripcion,costoentrega, 
(select count(*) as total from gest_zonas where estado=1 and gest_zonas.idsucursal = $idsucursal) as total
from gest_zonas
where 
estado=1 
and gest_zonas.idsucursal = $idsucursal
order by idzona asc
limit 1
";
$rszold = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idzonaex = intval($rszold->fields['idzona']);
// si existe mas de 1 para la misma sucursal borra
if (intval($rszold->fields['total']) > 1) {
    $consulta = "
	update gest_zonas set estado = 6 where idzona > $idzonaex and idsucursal = $idsucursal
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}
// si no existe zona crea
if (intval($rszold->fields['idzona']) == 0) {
    $consulta = "
	INSERT INTO gest_zonas
	(descripcion, costoentrega, estado, latini, latfin, idciudad, observaciones, idempresa, idsucursal, idprod_serial) 
	VALUES
	('0',0,1,NULL,NULL,1,NULL,1,$idsucursal,NULL)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}


// campos busqueda
if (isset($_GET['telefono'])) {

    // recibe variables
    $telefono = antisqlinyeccion($_GET['telefono'], "int");

    $consulta = "
	select *,
	(select ruc from cliente where idcliente = cliente_delivery.idcliente) as ruc,
	(select razon_social from cliente where idcliente = cliente_delivery.idcliente) as razon_social,
	(
		select fechahora 
		from tmp_ventares_cab 
		where 
		tmp_ventares_cab.idclientedel = cliente_delivery.idclientedel
		and estado <> 6
		order by fechahora desc
		limit 1
	) as ultped
	from cliente_delivery
	where
	cliente_delivery.idclientedel is not null
	and cliente_delivery.telefono = $telefono
	and cliente_delivery.estado <> 6
	order by cliente_delivery.nomape asc
	limit 50
	";
    $rscab_old = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idclientedel = intval($rscab_old->fields['idclientedel']);
    // si envio telefono
    if (intval($_GET['telefono']) > 0) {
        // si no existe cliente con ese telefono
        if ($idclientedel == 0) {
            header("location: delivery_clie_agrega.php?tel=0".solonumeros($_GET['telefono']));
            exit;
        }
    }


}
// campos busqueda
if (trim($_GET['nombre']) != '' && intval($_GET['telefono']) == 0) {

    //	ALTER TABLE cliente_delivery ADD nomape VARCHAR(500) NULL AFTER apellidos;

    //
    $consulta = "update cliente_delivery set nomape = CONCAT(trim(nombres),' ',trim(apellidos)) where nomape is null";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // recibe variables
    $nombre = antisqlinyeccion($_GET['nombre'], "like");

    $consulta = "
	select *,
	(select ruc from cliente where idcliente = cliente_delivery.idcliente) as ruc,
	(select razon_social from cliente where idcliente = cliente_delivery.idcliente) as razon_social,
	(
		select fechahora 
		from tmp_ventares_cab 
		where 
		tmp_ventares_cab.idclientedel = cliente_delivery.idclientedel
		and estado <> 6
		order by fechahora desc
		limit 1
	) as ultped
	from cliente_delivery
	where
	cliente_delivery.idclientedel is not null 
	and cliente_delivery.estado <> 6
	and cliente_delivery.nomape like '%$nombre%'
	order by cliente_delivery.nomape asc
	limit 50
	";
    //echo $consulta;
    //exit;
    $rscab_old = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}
if (trim($_GET['ruc']) != '' && intval($_GET['telefono']) == 0) {

    $consulta = "update cliente_delivery set nomape = CONCAT(nombres, ' ', apellidos) where nomape is null";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // recibe variables
    $ruc = antisqlinyeccion($_GET['ruc'], "like");

    $consulta = "
	select cliente_delivery.*, cliente.ruc, cliente.razon_social,
	(
		select fechahora 
		from tmp_ventares_cab 
		where 
		tmp_ventares_cab.idclientedel = cliente_delivery.idclientedel
		and estado <> 6
		order by fechahora desc
		limit 1
	) as ultped
	from cliente_delivery
	inner join cliente on cliente.idcliente = cliente_delivery.idcliente
	where
	cliente_delivery.idclientedel is not null
	and cliente.ruc like '%$ruc%' 
	and cliente_delivery.estado <> 6
	order by cliente_delivery.nomape asc
	limit 50
	";
    $rscab_old = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}
?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../../includes/head_gen.php"); ?>
<script>
// manejar cookie
function setCookie(cname,cvalue,exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires=" + d.toGMTString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for(var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function checkCookie() {
    var user=getCookie("username");
    if (user != "") {
        alert("Welcome again " + user);
    } else {
       user = prompt("Please enter your name:","");
       if (user != "" && user != null) {
           setCookie("username", user, 30);
       }
    }
}
// manejar cookie
function asigna_domicilio(domi,idzona){
	setCookie("dom_deliv", domi,1);
	//var co = getCookie("dom_deliv");
	//alert(co);
	if(idzona > 0){
		agrega_carrito_zona(idzona);
		
	}else{
		document.location.href='gest_ventas_resto_caja.php';
	}
}
function borra_dir(){
	if (window.confirm("Esta seguro que desea eliminar esta direccion?")) { 
		document.location.href='delivery_pedidos_dir.php?id=1&ch=';
	}

}
function agrega_carrito_zona(idzona){
	var direccionurl='delivery_carrito_zona.php';	
	var parametros = {
	  "idzona" : idzona
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			//$("#busqueda_prod").html('Cargando...');				
		},
		success:  function (response, textStatus, xhr) {
			if(xhr.status === 200){
				if(IsJsonString(response)){
					var obj = jQuery.parseJSON(response);
					if(obj.valido == 'S'){
						document.location.href='gest_ventas_resto_caja.php';
					}else{
						alert(obj.errores);		
					}
				}else{
					alert(response);	
				}
				
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			alert('Pagina no encontrada [404]');
		
		} else if (jqXHR.status == 500) {
		
			alert('Internal Server Error [500].');
		
		} else if (textStatus === 'parsererror') {
		
			alert('Requested JSON parse failed.');
		
		} else if (textStatus === 'timeout') {
		
			alert('Tiempo de espera agotado, time out error.');
		
		} else if (textStatus === 'abort') {
		
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
		
		} else {
		
			alert('Uncaught Error: ' + jqXHR.responseText);
		
		}
		
	});	
	
}
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
function solouncampo(campo){
	if(campo == 'telefono'){
		$("#ruc").val('');	
		$("#nombre").val('');
	}
	if(campo == 'ruc'){
		$("#telefono").val('');	
		$("#nombre").val('');
	}
	if(campo == 'nombre'){
		$("#ruc").val('');	
		$("#telefono").val('');
	}
}
</script>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("../../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("../../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Delivery</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<a href="delivery_clie_agrega.php?tel=<?php echo date("Ymdhis").$idusu; ?>&app=s" class="btn btn-sm btn-default"><span class="fa fa-search"></span> App sin Datos del Cliente</a>
<hr />				  
<form id="form1" name="form1" method="get" action="">

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Telefono *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="number" name="telefono" id="telefono" value="<?php  if (isset($_GET['telefono'])) {
	    echo htmlentities($_GET['telefono']);
	}?>" placeholder="Telefono" class="form-control" onKeyPress="solouncampo('telefono');"   />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombre" id="nombre" value="<?php  if (isset($_GET['nombre'])) {
	    echo htmlentities($_GET['nombre']);
	}?>" placeholder="Nombre" class="form-control" onKeyPress="solouncampo('nombre');"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">RUC </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="ruc" id="ruc" value="<?php  if (isset($_GET['ruc'])) {
	    echo htmlentities($_GET['ruc']);
	}?>" placeholder="RUC" class="form-control" onKeyPress="solouncampo('ruc');"  />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Buscar</button>

        </div>
    </div>

  <input type="hidden" name="MM_search" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<hr /><br />
            
<?php if (isset($_GET['telefono'])) {
    if ($rscab_old->fields['idclientedel'] > 0) {
        ?>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
              <thead> 
                <tr>
                  <th></th>
                  <th>Direcciones</strong></th>
                  <th>Cliente</th>

                </tr>
               </thead>  
               <tbody> 
<?php

$totcli = 0;
        while (!$rscab_old->EOF) {
            $idclientedel = $rscab_old->fields['idclientedel'];

            ?>
                <tr>
			<td>
				
				<div class="btn-group">
                
					<a href="delivery_pedidos_dir.php?id=<?php echo $idclientedel; ?>" class="btn btn-sm btn-default" title="Agregar Direccion" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar Direccion"><span class="fa fa-map-marker"></span></a>
					<a href="delivery_clie_edita.php?id=<?php echo $idclientedel; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="delivery_clie_del.php?id=<?php echo $idclientedel; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
                  <td align="left">
<?php
$consulta = "
select *
,(select describezona from zonas_delivery where idzonadel=cliente_delivery_dom.idzonadel) as describezona
,(select obs from zonas_delivery where idzonadel=cliente_delivery_dom.idzonadel) as obs,
(select estado from zonas_delivery where idzonadel=cliente_delivery_dom.idzonadel) as estado_zona
 from cliente_delivery_dom 
where
idclientedel = $idclientedel
and estado = 1
";
            $rsdirec = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            if ($rsdirec->fields['idclientedel'] > 0) {
                ?>
<table class="table table-bordered jambo_table bulk_action">
<?php while (!$rsdirec->EOF) {

    if ($rsdirec->fields['estado_zona'] != 6) {
        $accion_bnt = 'asigna_domicilio('.$rsdirec->fields['iddomicilio'].','.intval($rsdirec->fields['idzonadel']).');';
        $zona_estado_obs = '';
    } else {
        $accion_bnt = "document.location.href='delivery_clie_edita_dir.php?id=".$rsdirec->fields['iddomicilio']."&msg=zona'";
        $zona_estado_obs = ' <strong style="color:#F00;">(Zona Eliminada)</strong>';
    }


    ?>
    <tr>
        <td align="left" style="width:20%;"><a href="javascript:void(0);" onMouseUp="<?php echo $accion_bnt; ?>" class="btn btn-app" title="Enviar Delivery" data-toggle="tooltip" data-placement="right"  data-original-title="Enviar Delivery"><i class="fa fa-motorcycle"></i> Delivery</a></td>
    	<td style="width:80%;"><strong>Lugar:</strong> <?php echo $rsdirec->fields['nombre_domicilio']; ?>
        <br /><strong>DIR:</strong><?php echo $rsdirec->fields['direccion']; ?>
        <br /><strong>REF:</strong><?php echo $rsdirec->fields['referencia']; ?><br />
        <?php if ($usarzonadelivery == 'S') { ?>
        <strong>Zona:</strong> <?php echo $rsdirec->fields['describezona'].' | '.$rsdirec->fields['obs'].$zona_estado_obs; ?><br />
        <?php }?>
        </td>
    </tr>
<?php $rsdirec->MoveNext();
} ?>
</table>
<?php } else { ?>
SIN DOMICILIO REGISTRADO<br />
 <a href="delivery_clie_agrega_dir.php?id=<?php echo $idclientedel; ?>" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar Direccion</a>
<?php }?>
                  </td>
                  <td align="left">
                  <strong>Cliente:</strong><?php echo $rscab_old->fields['nombres']; ?> <?php echo $rscab_old->fields['apellidos']; ?><br />
                  <strong>Tel:</strong> 0<?php echo $rscab_old->fields['telefono']; ?><br />
                  <strong>RUC:</strong> <?php echo $rscab_old->fields['ruc']; ?><br />
                  <strong>Razon Social:</strong> <?php echo $rscab_old->fields['razon_social']; ?><br />
                  <?php
$ult_ped_fec = $rscab_old->fields['ultped'];
            //$ult_ped_fec="2021-10-01 18:20";
            $minutos_transcurridos = totalminutos($ult_ped_fec, $ahora);
            /*$horas_transcurridos=intval($minutos_transcurridos/60);
            $dias_transcurridos=intval(fecha_dif_dias($ult_ped_fec,$ahora));
            if($dias_transcurridos > 0){
                $transcurre=$dias_transcurridos.' Dias';
            }else{
                echo $horas_transcurridos;
                if($horas_transcurridos > 0){
                    $transcurre=$horas_transcurridos.' Horas';
                }else{
                    $transcurre=$minutos_transcurridos.' Minutos';
                }
            }
            echo $transcurre;
            */


            ?>
                  <strong>Ultimo Pedido:</strong> <?php echo date("d/m/Y H:i:s", strtotime($ult_ped_fec)); ?><br />
                 <?php if ($minutos_transcurridos <= 180) {
                     echo '<strong style="color:#F00;">Hace: '.$minutos_transcurridos.' Minutos </strong>';
                 } ?>
                  
                  </td>

<?php
$totcli++;
            $rscab_old->MoveNext();
        } ?>
              </tbody>
            </table>
</div>

<?php
        //echo $totcli;
        if ($totcli == 1) {
            // trae el ultimo pedido
            $consulta = "
SELECT * 
FROM tmp_ventares_cab 
where 
idclientedel = $idclientedel 
and estado <> 6
order by idtmpventares_cab desc 
limit 1
";
            $rsultped = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idtmpventares_cab = $rsultped->fields['idtmpventares_cab'];
            if ($idtmpventares_cab > 0) {
                $consulta = "
select idproducto, productos.descripcion as producto, sum(cantidad) as cantidad
from tmp_ventares 
inner join productos on productos.idprod_serial = tmp_ventares.idproducto
where 
tmp_ventares.idtmpventares_cab = $idtmpventares_cab
group by tmp_ventares.idproducto, productos.descripcion
order by productos.descripcion asc
";

                $rsultpeddet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idproducto_det = $rsultpeddet->fields['idproducto'];
                if ($idproducto_det > 0) {
                    ?>
<strong>Ultimo Pedido:</strong><br />
<strong>Fecha:</strong> <?php echo date("d/m/Y H:i:s", strtotime($rsultped->fields['fechahora'])); ?><br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">Producto</th>
			<th align="center">Cantidad</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rsultpeddet->EOF) { ?>
		<tr>


			<td align="left"><?php echo antixss($rsultpeddet->fields['producto']); ?></td>
			<td align="right"><?php echo formatomoneda($rsultpeddet->fields['cantidad'], 4, 'N');  ?></td>
		</tr>
<?php $rsultpeddet->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />
<?php /*   BOTON REPETIR PEDIDO Y BOTON HISTORICO DE PEDIDOS
Repetir copia los productos al carrito pero con el precio actual no el precio del pedido pasado
tambien prever tablas de: combo, combinado, combinado extendido, agregados, eliminados y delivery nuevo

 ?>
<!--<a href="javascript:void(0);" onMouseUp="asigna_domicilio(<?php echo $rsdirec->fields['iddomicilio']; ?>,<?php echo intval($rsdirec->fields['idzonadel']); ?>);" class="btn btn-app" title="Enviar Delivery" data-toggle="tooltip" data-placement="right"  data-original-title="Enviar Delivery"><i class="fa fa-motorcycle"></i> Repetir Pedido</a>-->
<?php */ ?>
<?php } // if($idproducto_det > 0){?>
<?php } // if($idtmpventares_cab > 0){?>
<?php } // if($totcli == 1){?>
            <p align="center">&nbsp;</p>
<?php } else { ?> <br /> 

<h2 align="center">
No se encontraron registros con los datos indicados.
</h2>
<p align="center">
<br /><br />
<a href="delivery_clie_agrega.php?tel=<?php if (intval($_GET['telefono']) > 0) {
    echo '0'.intval($_GET['telefono']);
} ?>" class="btn btn-app" title="Agregar Cliente" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar Cliente"><i class="fa fa-plus"></i> Agregar Cliente</a>

</p>
 <br />
<?php } ?> 
        <?php } ?>   

                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("../../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../../includes/footer_gen.php"); ?>
<script>
$(document).ready(function(){
    $("#telefono").focus();
	$("#telefono").select();
});
</script>
  </body>
</html>
