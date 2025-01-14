<?php
require_once("../../includes/conexion.php");
require_once("../../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "129";
$dirsup_sec = "S";

require_once("../../includes/rsusuario.php");

$idclientedel = intval($_GET['id']);
if ($idclientedel == 0) {
    header("location: delivery_pedidos.php");
    exit;
}
//si hay para eliminar
$chau = intval($_REQUEST['ch']);

if ($chau > 0) {
    //$update="update cliente_delivery_dom set estado=6,anulado_por=$idusu,anulado_el=current_timestamp where iddomicilio=$chau";
    //$conexion->Execute($update) or die(errorpg($conexion,$update));


}

// busca clientes
$consulta = "
select * 
from cliente_delivery
where
idclientedel = $idclientedel and estado <>6
limit 1
";
$rscab_old = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idclientedel = intval($rscab_old->fields['idclientedel']);
if ($idclientedel == 0) {
    header("location: delivery_pedidos.php");
    exit;
}

// busca domicilios
$consulta = "
select *,(select describezona from zonas_delivery where idzonadel=cliente_delivery_dom.idzonadel) as describezona
,(select obs from zonas_delivery where idzonadel=cliente_delivery_dom.idzonadel) as obs
from cliente_delivery_dom
where
idclientedel = $idclientedel and estado <> 6
";
$rsdom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


$consulta = "select usa_lista_zonas from preferencias";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$usarzonadelivery = trim($rspref->fields['usa_lista_zonas']);

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
		document.location.href='delivery_pedidos_dir.php?id=<?php echo intval($_REQUEST['id']); ?>&ch=<?php echo $iddomicilio; ?>';
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

</script>
  </head>

  <body class="nav-md" <?php if ($_GET['iddomicilio'] > 0) { ?> onload="asigna_domicilio(<?php echo intval($_GET['iddomicilio']); ?>,<?php echo intval($rsdom->fields['idzonadel']); ?>);"<?php } ?>>
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

            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <?php require_once("../../includes/lic_gen.php");?>
                    <h2>Domicilios del Cliente</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p>
<a href="delivery_pedidos.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Buscar Cliente</a>
<a href="delivery_clie_agrega_dir.php?id=<?php echo $idclientedel ?>" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar Direccion</a>
</p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
              <thead>
                <tr>
                	<th ></th>
                  <th >Nombre y Apellido</th>
                  <th >Telefono</th>
                </tr>
              </thead>
              <tbody>
                <?php while (!$rscab_old->EOF) {
                    $idclientedel = $rscab_old->fields['idclientedel'];

                    ?>
                <tr>
                	<td >
				<div class="btn-group">
					<a href="delivery_clie_edita.php?id=<?php echo $rscab_old->fields['idclientedel']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                    <a href="delivery_clie_del.php?id=<?php echo $rscab_old->fields['idclientedel']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>
                    
                    </td>
                  <td align="left"><?php echo $rscab_old->fields['nombres']; ?> <?php echo $rscab_old->fields['apellidos']; ?></td>
                   <td align="center">0<?php echo $rscab_old->fields['telefono']; ?></td>
                </tr>
                <?php $rscab_old->MoveNext();
                } ?>
              </tbody>
            </table>
</div>

<p align="center">&nbsp;</p>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
              <thead> 
                <tr>
                	<th ></th>
                  <th ><strong>Lugar</strong></th>
                  <th ><strong>Direccion</strong></th>
                  <?php if ($usarzonadelivery == 'S') {?>
                  <th >Zona - Obs</th>
                  <?php } ?>
                  <th >Referencia</th>

                  
                </tr>
               </thead>  
               <tbody> 
<?php while (!$rsdom->EOF) {

    $iddomicilio = $rsdom->fields['iddomicilio'];

    ?>
                <tr>
                <td>
                    
                    <div class="btn-group">
                        <a href="javascript:void(0);" onMouseUp="asigna_domicilio(<?php echo $rsdom->fields['iddomicilio']; ?>,<?php echo intval($rsdom->fields['idzonadel']); ?>);" class="btn btn-sm btn-default" title="Delivery" data-toggle="tooltip" data-placement="right"  data-original-title="Delivery"><span class="fa fa-motorcycle"></span></a>
                        <a href="delivery_clie_edita_dir.php?id=<?php echo $rsdom->fields['iddomicilio']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                        <a href="delivery_clie_borra_dir.php?id=<?php echo $rsdom->fields['iddomicilio']; ?>"  class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                    </div>
    
                </td>
                  <td align="center"><?php echo $rsdom->fields['nombre_domicilio']; ?></td>
                  <td align="center"><?php echo $rsdom->fields['direccion']; ?></td>
                  <?php if ($usarzonadelivery == 'S') {?>
                  <td align="center"><?php echo $rsdom->fields['describezona'].' | '.$rsdom->fields['obs']; ?></td>
                  <?php } ?>
                  <td align="center"><?php echo $rsdom->fields['referencia']; ?></td>


                 
                 </tr>
                
<?php $rsdom->MoveNext();
} ?>
              </tbody>
            </table>

</div>


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
  </body>
</html>
