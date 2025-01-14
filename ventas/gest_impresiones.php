<?php
require_once("includes/conexion.php");
//require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo="1";
$submodulo="30";

require_once("includes/rsusuario.php"); 
require_once("includes/num2letra.php");


$buscar="Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal and tipocaja = 1 order by fecha desc limit 1";
$rscaja=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));



$idcaja=intval($rscaja->fields['idcaja']);
$estadocaja=intval($rscaja->fields['estado_caja']);

if ($idcaja==0){
	echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>" 	;
	exit;	
}
if ($estadocaja==3){
	echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>" 	;
	exit;	
}


// script de impresion factura
$consulta="
select script_factura from preferencias where idempresa = $idempresa limit 1
";
$rsscr=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

// script de impresion factura
$consulta="
select filtros_reimp, reimpresion_muestra_cant from preferencias_caja  limit 1
";
$rspc=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
$filtros_reimp=$rspc->fields['filtros_reimp'];
$reimpresion_muestra_cant=$rspc->fields['reimpresion_muestra_cant'];

//Vemos si tiene idventa
$venta=intval($_GET['vta']); // para factura
$vpedido=intval($_GET['v']); // para ticket


$tickete=$cabecera;




if($rsco->fields['ticket_fox'] == 'S'){
	
	/*$consulta="
	select idatc from mesas_atc where idmesa = $idmesa and estado = 1 order by idatc desc limit 1
	";
	$rsatc = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
	//$idatc=$rsatc->fields['idatc'];
	//$ticket_json=preticket_mesa_json($idatc);*/
	if($vpedido > 0){
		$ticket_json=ticket_venta_json($vpedido);
	}
	
	
}else{
		
	if($vpedido > 0){
	
		$idped=intval($vpedido);
		//echo $idmesa;
		if($idped == 0){
			echo "Pedido inexistente!";
			exit;
		}
		
		// trae la primera impresora
		$consulta="SELECT * FROM impresoratk where idempresa = $idempresa  and idsucursal = $idsucursal and borrado = 'N' order by idimpresoratk asc limit 1";
		$rsimp = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
		$pie_pagina=$rsimp->fields['pie_pagina'];
		$defaultprnt="http://localhost/impresorweb/ladocliente.php";
		$script_impresora=trim($rsimp->fields['script']);
		if(trim($script_impresora) == ''){
			$script_impresora=$defaultprnt;	
		}
		
		
		// busca si es una mesa o un pedido
		$idmesa=0;
		$consulta="
		SELECT * FROM ventas WHERE idventa = $vpedido
		";
		$rsdatoscab = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
		if(intval($rsdatoscab->fields['idmesa']) > 0){
			$idmesa=intval($rsdatoscab->fields['idmesa']);
			$idpedido=0;	
		}else{
			$idpedido=intval($rsdatoscab->fields['idpedido']);	
			$idped=$idpedido;
		}
	
		// si no es mesa
		if($idmesa == 0){
	
			// tipo de impresor
			$impresor_tip="REI";
			$redir_impr="gest_impresiones.php";
			
			// parametros
			$consolida='S';
			$leyenda_credito=$rsimp->fields['leyenda_credito'];
			$datos_fiscal=$rsimp->fields['datos_fiscal'];
			$muestra_nombre=$rsimp->fields['muestra_nombre'];
			$usa_chapa=$rsimp->fields['usa_chapa'];
			$usa_obs=$rsimp->fields['usa_obs'];
			$usa_precio=$rsimp->fields['usa_precio'];
			$usa_total=$rsimp->fields['usa_total'];
			$usa_nombreemp=$rsimp->fields['usa_nombreemp'];
			$usa_totaldiscreto=$rsimp->fields['usa_totaldiscreto'];
			$txt_codvta=$rsimp->fields['txt_codvta'];
			$cabecera_pagina=$rsimp->fields['cabecera_pagina'];
			$pie_pagina=$rsimp->fields['pie_pagina'];
				
		
		
		// si es mesa	
		}else{
			
			
					
			
			
			$consulta=" 
			select numero_mesa, nombre, idmesa
			from mesas
			inner join salon on mesas.idsalon = salon.idsalon
			where 
			idmesa = $idmesa
			and salon.idsucursal = $idsucursal
			";
			//echo $consulta;
			$rsmes= $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
			$numeromesa=$rsmes->fields['numero_mesa'];
			$salon=$rsmes->fields['nombre'];
			$idmesa=intval($rsmes->fields['idmesa']);
			if($idmesa == 0){
				echo "Mesa Inexistente.";
				exit;
			}
			
			// tipo de impresor
			$impresor_tip="MES";
			$redir_impr="gest_impresiones.php";
			
			// parametros
			$consolida='S';
			$leyenda_credito=$rsimp->fields['leyenda_credito'];
			$datos_fiscal=$rsimp->fields['datos_fiscal'];
			$muestra_nombre=$rsimp->fields['muestra_nombre'];
			$usa_chapa=$rsimp->fields['usa_chapa'];
			$usa_obs=$rsimp->fields['usa_obs'];
			$usa_precio=$rsimp->fields['usa_precio'];
			$usa_total=$rsimp->fields['usa_total'];
			$usa_nombreemp=$rsimp->fields['usa_nombreemp'];
			$usa_totaldiscreto=$rsimp->fields['usa_totaldiscreto'];
			$txt_codvta=$rsimp->fields['txt_codvta'];
			$cabecera_pagina=$rsimp->fields['cabecera_pagina'];
			$pie_pagina=$rsimp->fields['pie_pagina'];
			
			
			
		}
		
	
		require_once("impresor_motor.php");
	
	}
}


$consulta="
select ruc, razon_social from cliente where borrable = 'N' and estado<>6 order by idcliente asc limit 1
";
$rscli=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
$razon_social_pred=strtoupper(trim($rscli->fields['razon_social']));
$ruc_pred=$rscli->fields['ruc'];


?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
<script>
function mostrar(valor){
		if (valor==1){
			//particular
			document.getElementById('ocu').hidden="";
			
			document.getElementById('razontr').hidden="hidden";
			document.getElementById('nombrestr').hidden="";
			document.getElementById('apellidostr').hidden="";
			document.getElementById('documentotr').hidden="";
			document.getElementById('ructr').hidden="hidden";
			document.getElementById('nacimientotr').hidden="";
			document.getElementById('tipoclie').value=valor;
		} else {
			if (valor==2){
				document.getElementById('ocu').hidden="";
				
				document.getElementById('nombrestr').hidden="hidden";
				document.getElementById('apellidostr').hidden="hidden";
				document.getElementById('documentotr').hidden="hidden";
				document.getElementById('razontr').hidden="";
				document.getElementById('ructr').hidden="";
				document.getElementById('nacimientotr').hidden="hidden";
				document.getElementById('tipoclie').value=valor;
			} else {
				//seleccionar
				
			}
			 
		}
		
		
	}
	function verificar(){
		var errores='';
		var tipo=document.getElementById('octipoclie').value;
		if (tipo==0){
			errores=errores+'Debe Indicar tipo de cliente. \n';
		}
		
		if (tipo==1){
			var no=document.getElementById('nombres').value;
			var ap=document.getElementById('apellidos').value;
			var dc=document.getElementById('documento').value;
			var ruc=document.getElementById('ruc').value;
			if ((no=='') || (ap=='')){
				errores=errores+'Debe Indicar nombres y apellidos del cliente. \n';
			}
			if ((dc=='') && (ruc=='')){
				errores=errores+'Debe indiciar al menos un tipo de documento. \n';
			}
			var dir=document.getElementById('direccion').value;
			if (dir==''){
				errores=errores+'Debe indiciar direccion particular. \n';
			}
			
		}
		if (tipo==2){
			var raz=document.getElementById('razon').value;
			var ruc=document.getElementById('ruc').value;
			if ((raz=='')){
				errores=errores+'Debe Indicar Razon Social de la Empresa. \n';
			}
			if ((ruc=='')){
				errores=errores+'Debe indiciar RUC de la empresa. \n';
			}
			var dir=document.getElementById('direccion').value;
			if (dir==''){
				errores=errores+'Debe indiciar direccion de la empresa. \n';
			}
			var cel=document.getElementById('celular').value;
			var lbaja=document.getElementById('lbaja').value;
			if ((cel=='') && (lbaja=='')){
				errores=errores+'Debe indiciar al menos un numero telefonico. \n';
			}
		}
		
		if (errores!=''){
			alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');	
		} else {
			document.getElementById('formreg').submit();
			
		}
	}
	function seleccionar(cual){
		if (seleccionar!=''){
			document.getElementById('octipoclie').value=parseInt(cual);	
			
		}
		
	}
	function listo(){
		alertar('Listo.','Registro correcto','success','Continuar');	
		
	}
</script>
<script src="js/sweetalert.min.js"></script>
 <link rel="stylesheet" type="text/css" href="css/sweetalert.css">
 <script>
 function alertar(titulo,error,tipo,boton){
	swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
	}
	
	
 
 
 <?php if ($imprimir==1){?>
function imprimir(){
	
	var parametros='tk='+document.getElementById('tickete').value;
	OpenPage('http://localhost/impresorweb/ladoclientefac.php',parametros,'POST','impresion','pred');
	setTimeout(function(){ document.body.innerHTML='<meta http-equiv="refresh" content="0; url=gest_impresiones.php" />'; }, 500);
		
}
<?php }?>
<?php if($_GET['v'] > 0){ ?>
function imprime_cliente(){
		//alert('a');
		var texto = document.getElementById("texto").value;
		//alert(texto);
        var parametros = {
                "tk" : texto,
				"tk_json" : '<?php echo $ticket_json; ?> '
        };
       $.ajax({
                data:  parametros,
                url:   '<?php echo $script_impresora; ?>',
                type:  'post',
				dataType: 'html',
                beforeSend: function () {
                        $("#impresion_box").html("Enviando Impresion...");
                },
				crossDomain: true,
                success:  function (response) {
						//$("#impresion_box").html(response);	
						//si impresion es correcta marcar
						var str = response;
						var res = str.substr(0, 18);
						//alert(res);
						if(res == 'Impresion Correcta'){
							//marca_impreso('<?php echo $id; ?>');
							//document.body.innerHTML = "Impresion Enviada!";
							document.location.href='gest_impresiones.php';
							$('#reimprimebox',window.parent.document).html('');
						}else{
							$("#impresion_box").html(response);	
						}
						
						// si no es correcta avisar para entrar al modulo de reimpresiones donde se pone la ultima impresion correcta y desde ahi se marca como no impreso todas las que le siguen
						
                }
        });
	
}
<?php }?>
	  <?php /*if ($imprimir==2){?>
function imprimecliente(){
	
	var parametros='tk='+document.getElementById('tickete').value;
	OpenPage('http://localhost/impresorweb/ladocliente.php',parametros,'POST','impresion','pred');
	setTimeout(function(){ document.body.innerHTML='<meta http-equiv="refresh" content="0; url=gest_impresiones.php" />'; }, 500);
		
}
<?php }*/?>
function reimprimir(idventa,id){
        var parametros = {
                "id" : idventa
        };
       $.ajax({
                data:  parametros,
                url:   'reimprimir_vcaja.php',
                type:  'post',
                beforeSend: function () {
						$("#reimpcoc_"+id).remove();
                        $("#reicoc_"+id).html("<br /><br />Enviando Impresion...<br /><br />");
                },
                success:  function (response) {
						$("#reicoc_"+id).html(response);
                }
        });
		
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
            </div>
            <div class="clearfix"></div>
			<?php require_once("includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Reimpresiones</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">



<?php 
			
				$cvta=intval($_GET['cvta']);
				if($cvta > 0){
					$whereadd=" and idventa = $cvta ";	
				}
				$mostrar=intval($_GET['mostrar']);
				if ($mostrar > 0){
					$limite=" ";
				} else {
					$limite=" limit $reimpresion_muestra_cant "	;
					
				}
			
				$buscar="Select ventas.fecha,factura,ventas.idventa,recibo,ventas.razon_social,ruchacienda,dv,idpedido,totalcobrar,ventas.ruc,
				(
				select tipoimpreso from facturas where idtanda = ventas.idtandatimbrado
				) as tipoimpreso,
				(
				select documentos_electronicos_emitidos_estado_mail.estado_mail 
				from documentos_electronicos_emitidos
				inner join documentos_electronicos_emitidos_estado_mail on documentos_electronicos_emitidos_estado_mail.idestadomail = documentos_electronicos_emitidos.estado_enviocliente
				where
				documentos_electronicos_emitidos.idventa = ventas.idventa
				and documentos_electronicos_emitidos.estado <> 6
				) as estado_mail,
				(
				select documentos_electronicos_emitidos_estado_mail.idestadomail 
				from documentos_electronicos_emitidos
				inner join documentos_electronicos_emitidos_estado_mail on documentos_electronicos_emitidos_estado_mail.idestadomail = documentos_electronicos_emitidos.estado_enviocliente
				where
				documentos_electronicos_emitidos.idventa = ventas.idventa
				and documentos_electronicos_emitidos.estado <> 6
				) as idestadomail,
				cliente.email
				from ventas
				inner join cliente on cliente.idcliente=ventas.idcliente
				where 
				cliente.idempresa=$idempresa 
				and ventas.idempresa=$idempresa 
				and ventas.idcaja=$idcaja
				and ventas.estado <> 6
				and idcanal <> 8
				/*
				and ventas.factura <> ''
				and ventas.factura is not null
				*/
				$whereadd
				order by fecha desc
				$limite
				";
				$rsvv=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
				 
				 $tdata=$rsvv->RecordCount();
			?>
			<?php if($filtros_reimp == 'S'){ ?>
       		<form id="form1" name="form1" method="get" action="">
       		  CodVTA: <label for="textfield"></label>
       		    <input type="text" name="cvta" id="cvta" value="<?php if(intval($cvta) > 0){ echo $cvta; } ?>" />
   		      
              <input type="submit" name="button" id="button" value="Buscar" />
   		  </form><br /><br />
          <!--<form id="form1" name="form2" method="get" action="">
       		  Mostrar: <label for="textfield"></label>
       		    <input type="text" name="mostrar" id="mostrar" value="<?php if(intval($valor) > 0){ echo $valor; } else { echo $_REQUEST['mostrar']; } ?>" />
   		      
              <input type="submit" name="button" id="button" value="Mostrar Registros" />
   		  </form>-->
   		  <?php } ?>
   		  <br /><br />
   		  
<?php if (intval($cvta) > 0 && intval($rsvv->fields['idventa']) == 0){?>
<br />
El codigo de venta: <?php echo $cvta ?>, no existe o no pertenece a tu caja. <a href="gest_impresiones.php">[Ver ultimos <?php echo $reimpresion_muestra_cant?>]</a><br />
<br />
<?php } ?>
       		<?php if ($tdata > 0){?>
				<?php if(intval($cvta) > 0){ ?>Filtrando por: <?php echo $cvta; ?> | <a href="gest_impresiones.php">[Borrar Filtro]</a>
				<?php } ?><br /><br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
       				<tr>
                    	<th ><strong>Idventa</strong></th>
						<th ><strong>Cliente</strong></th>
						<th><strong>Total Venta</strong></th>
						<th>Fecha Venta</th>
						<?php if($facturador_electronico == 'S'){ ?>
						<th>Mail</th>
						<?php } ?>
                        <th><strong>Comandas de Cocina</strong></th>
						<th><strong>Ticket Pedido</strong></th>
						<th><strong>Factura</strong></th>
					</tr>
	</thead>
    <tbody>
					<?php 
					$i=0;
					while (!$rsvv->EOF){
						$i++;
						$vta=intval($rsvv->fields['idventa']);
						$factura=trim($rsvv->fields['factura']);
						$ruc=trim($rsvv->fields['ruc']);
						if ($ruc==''){
							$ruc=$rsvv->fields['ruchacienda'].'-'.$rsvv->fields['dv'];
						}
						$tipoimpreso=trim($rsvv->fields['tipoimpreso']);
						
						
					?>
					<tr>
                    	<td height="40" align="center" valign="middle"><?php echo $rsvv->fields['idventa'];?></td>
						<td height="40" align="center" valign="middle"><?php echo $rsvv->fields['razon_social'];?> | <?php echo $ruc;?><br /><?php 
// si es preimpreso
if($tipoimpreso != 'AUT'){?>
                        <a href="editafac.php?vta=<?php echo $vta ;?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
<?php } ?>
<?php 
// si es autoimpresor solo se permite cambiar si era ruc generico
if($tipoimpreso == 'AUT' && $ruc == $ruc_pred){?>
                        <a href="editafac.php?vta=<?php echo $vta ;?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
<?php } ?>
						</td>
						<td align="center" valign="middle"><?php echo formatomoneda($rsvv->fields['totalcobrar'],4,'N'); ?></td>
						<td align="center" valign="middle"><?php echo date("d/m/Y H:i:s",strtotime($rsvv->fields['fecha'])); ?></td>
						
						
<?php if($facturador_electronico == 'S'){ ?>
						<td align="center" valign="middle" ><?php echo antixss(strtolower($rsvv->fields['email'])); ?><br /><?php echo antixss($rsvv->fields['estado_mail']); ?><br />
						<?php if($ruc != $ruc_pred){ ?>
						<a href="edita_mail.php?vta=<?php echo $vta ;?>" class="btn btn-sm btn-default" title="Editar Mail y Reenviar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar Mail y Reenviar"><span class="fa fa-edit"></span></a>
						<?php } ?>
						</td>
<?php } ?>
						
                        <td align="center" valign="middle" id="reicoc_<?php echo $i;  ?>"><a href="javascript:reimprimir(<?php echo $vta ;?>,<?php echo $i; ?>);void(0);" id="reimpcoc_<?php echo $i; ?>"><img src="img/1495739941_printer.png" width="32" height="32" border="0" /><br /><?php echo $rsvv->fields['idventa'] ;?></a></td>
						<td align="center" valign="middle"><a href="<?php echo $rsscr->fields['script_factura']; ?>?vta=<?php echo $vta ;?>&tk=1"><img src="img/1495739941_printer.png" width="32" height="32" border="0" /><br /><?php echo $rsvv->fields['idventa'] ;?></a></td>
						<td align="center" valign="middle">
						<span class="resaltarojomini">
					  <?php if($factura != ''){ if($rsscr->fields['script_factura'] != ''){ ?><a href="<?php echo $rsscr->fields['script_factura']; ?>?vta=<?php echo $vta ;?>"><img src="img/1495739941_printer.png" width="32" height="32" border="0" /><br /><?php echo $factura ;?></a>
					  <?php }else{ ?>Formato de factura no definido.<?php } }else{ ?>
                      <a href="asignafac.php?vta=<?php echo $vta ;?>" class="btn btn-sm btn-default" title="Asignar" data-toggle="tooltip" data-placement="right"  data-original-title="Asignar"><span class="fa fa-search"></span> Asignar</a>
					  <?php } ?></span></td>
					</tr>
					<?php $rsvv->MoveNext();}?>
                    </tbody>
				</table>
		  </div>
       		<?php }?>
        </div>
        <br />
        <?php if (intval($imprimir)==1 || intval($imprimir)==2){ ?>
        <textarea id="tickete" style="display:"><?php echo $tickete; ?></textarea>
        <?php } ?>
<div id="impresion">
        
</div>   



<?php if($_GET['v'] > 0){ ?>
<div style="width:290px; border:1px solid; margin:0px auto; background-color:#FFFFFF; padding:5px; text-align:center; min-height:50px;" id="impresion_box">
<p align="center"><input type="button" value="imprimir" style="padding:10px;" onClick="imprime_cliente();"></p>
</div><br />
<div style="width:290px; border:1px solid; margin:0px auto; background-color:#FFFFFF; padding:5px;">
<textarea style="display:; width:310px; height:500px;" id="texto"><?php echo $texto; ?></textarea>
<pre>
<?php //echo $texto; ?>
</pre>
</div>
<?php } ?>
<br /><br /><br /><br /><br />


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
