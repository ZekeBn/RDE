<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "130";
require_once("includes/rsusuario.php");


// funciones para stock
require_once("includes/funciones_stock.php");

$idajuste = intval($_GET['id']);
if ($idajuste == 0) {
    header("location: ajustar_stock.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from gest_depositos_ajustes_stock 
where 
idajuste = $idajuste
and estado = 'A'
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idajuste = intval($rs->fields['idajuste']);
if ($idajuste == 0) {
    header("location: ajustar_stock.php");
    exit;
}


$consulta = "
select *,
(select usuario from usuarios where gest_depositos_ajustes_stock.registrado_por = usuarios.idusu) as registrado_por,
(select descripcion from gest_depositos where iddeposito = gest_depositos_ajustes_stock.iddeposito) as deposito,
(select motivo from motivos_ajuste where idmotivo = gest_depositos_ajustes_stock.idmotivo) as motivo_ajuste
from gest_depositos_ajustes_stock 
where 
 estado = 'A'
 and idajuste = $idajuste
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

//Post Final
if (isset($_POST['ter']) && ($_POST['ter']) != '') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots

    $tfin = intval($idajuste);

    // recorre los archivos a transferir en la tabla temporal
    $buscar = "
	select *, 
	(select insumos_lista.descripcion from insumos_lista where idinsumo = tmp_ajuste.idinsumo) as insumo,
	(select insumos_lista.costo from insumos_lista where idinsumo = tmp_ajuste.idinsumo) as ultcosto
	from tmp_ajuste
	inner join gest_depositos_ajustes_stock on gest_depositos_ajustes_stock.idajuste = tmp_ajuste.idajuste
	 where 
	 gest_depositos_ajustes_stock.idajuste=$idajuste
	 and gest_depositos_ajustes_stock.estado = 'A'
	 order by (select insumos_lista.descripcion from insumos_lista where idinsumo = tmp_ajuste.idinsumo) asc
	 ";
    $rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    if (intval($rsf->fields['idinsumo']) == 0) {
        //echo "Error! no existe la tanda de ajuste o no cargo ningun item.";
        //exit;
        $valido = "N";
        $errores .= " - No existe la tanda de ajuste o no cargo ningun item.<br />";
    }


    /// si se valida el stock disponible
    /*if($valida_stock == 'S'){

        while (!$rsf->EOF){

            $idinsumo_traslado=$rsf->fields['idproducto'];
            $cantidad_traslado=$rsf->fields['cantidad'];
            $iddeposito_origen=$rsf->fields['origen'];
            $nombreinsu=$rsf->fields['insumo'];

            // busca el disponible en stock general
            $buscar="Select sum(disponible)  as total_stock
            from gest_depositos_stock_gral
            where
            idproducto=$idinsumo_traslado
            and idempresa=$idempresa
            and estado=1
            and iddeposito = $iddeposito_origen";
            $rsst=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
            $total_stock=floatval($rsst->fields['total_stock']);
            if($cantidad_traslado > $total_stock){
                $errores.="-El disponible es menor a la cantidad que quiere transferir de $nombreinsu, quedan $total_stock y quiere transferir $cantidad_traslado.<br />";
                $valido="N";
            }
            if($total_stock <= 0){
                $errores.="-No queda disponible de $nombreinsu en stock.<br />";
                $valido="N";
            }

        $rsf->MoveNext(); }
        $rsf->MoveFirst(); // reinicia el recordset
    }*/

    // si todo es valido
    if ($valido == "S") {

        // trasladamos insumos
        while (!$rsf->EOF) {

            $idinsumo_ajuste = $rsf->fields['idinsumo'];
            $cantidad_ajuste = $rsf->fields['cantidad'];
            $iddeposito = $rsf->fields['iddeposito'];
            //$iddeposito_destino=$rsf->fields['destino'];
            $nombreinsu = str_replace("'", "", $rsf->fields['insumo']);
            $tipoajuste = $rsf->fields['tipoajuste'];
            $idajuste = $rsf->fields['idajuste'];
            $fechaajuste = $rsf->fields['fechaajuste'];
            $ultcosto = floatval($rsf->fields['ultcosto']);

            // inserta en tabla de ajustes
            $insertar = "
			INSERT INTO gest_depositos_ajustes_stock_det
			(idajuste, idempresa, idinsumo, fechaajust, registrado_por, registrado_el, cantidad_ajuste, tipoajuste, iddeposito) 
			VALUES 
			($idajuste, $idempresa, $idinsumo_ajuste, '$fechaajuste', $idusu, '$ahora', $cantidad_ajuste, '$tipoajuste', $iddeposito)
			";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

            $consulta = "
			select max(idajustedet) as idajustedet
			from gest_depositos_ajustes_stock_det
			where 
			registrado_por = $idusu 
			and idajuste = $idajuste
			";
            $rsajust = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idajustedet = $rsajust->fields['idajustedet'];

            // busca si existe en stock general cada insumo a trasladar
            $buscar = "Select * 
			from gest_depositos_stock_gral 
			where 
			idproducto=$idinsumo_ajuste 
			and idempresa=$idempresa 
			and estado=1 
			and iddeposito = $iddeposito";
            $rsst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            // si no existe inserta
            if (intval($rsst->fields['idproducto']) == 0) {
                $insertar = "INSERT INTO gest_depositos_stock_gral
				(iddeposito, idproducto, disponible, tipodeposito, last_transfer, estado, descripcion, idempresa) 
				VALUES 
				($iddeposito,$idinsumo_ajuste,0,1,'$ahora',1,'$nombreinsu',$idempresa
				)";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
                //movimientos_stock($idinsumo_ajuste,0,$iddeposito,9,'+',$idajuste,$fechaajuste);
            }

            // descontar insumo de stock general
            if ($tipoajuste == '-') {
                $consulta = "

				UPDATE gest_depositos_stock_gral 
				SET 
				disponible=(disponible-$cantidad_ajuste)
				WHERE 
				idempresa=$idempresa 
				and iddeposito=$iddeposito
				and idproducto=$idinsumo_ajuste
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                movimientos_stock($idinsumo_ajuste, $cantidad_ajuste, $iddeposito, 9, '-', $idajuste, $fechaajuste);
            } else {
                // aumentar insumo de stock general destino
                $consulta = "
				UPDATE gest_depositos_stock_gral 
				SET 
				disponible=(disponible+$cantidad_ajuste)
				WHERE 
				idempresa=$idempresa 
				and iddeposito=$iddeposito
				and idproducto=$idinsumo_ajuste
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                movimientos_stock($idinsumo_ajuste, $cantidad_ajuste, $iddeposito, 9, '+', $idajuste, $fechaajuste);
            }



            // descontamos con costos y depositos especificos
            if ($tipoajuste == '-') {
                $costo_acum = descontar_stock($idinsumo_ajuste, $cantidad_ajuste, $iddeposito);
                if ($costo_acum > 0) {
                    $costo = $costo_acum / $cantidad_ajuste;
                } else {
                    $costo = 0;
                }

            } else {
                aumentar_stock($idinsumo_ajuste, $cantidad_ajuste, $ultcosto, $iddeposito);
                $costo = $ultcosto;
            }

            // actualiza el costo
            $consulta = "
			update gest_depositos_ajustes_stock_det
			set
			precio_costo = $costo
			where
			idajustedet = $idajustedet
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $rsf->MoveNext();
        }

        //actualizamos el cierre de transferencia
        $update = "
		Update gest_depositos_ajustes_stock 
		set 
		estado='C' 
		where 
		idajuste=$tfin 
		and idempresa=$idempresa
		and estado='A'
		and iddeposito=$iddeposito 
		";
        $conexion->Execute($update) or die(errorpg($conexion, $update));

        header("Location: ajustar_stock_det_fin.php?id=$idajuste");
        exit;


    } //if($valido=="S"){

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
<script>
function busca_insumo(valor){
	var n = valor.length;
	//alert(valor);
	if(n > 2){
		$("#codigoprod").val('');
	   var parametros = {
              "codigop" : valor,
		   	  "idajuste" : '<?php echo $idajuste; ?>'
	   };
       $.ajax({
                data:  parametros,
                url:   'gest_ajustar_stock_cuadro.php',
                type:  'post',
                beforeSend: function () {
                        $("#insumo_box").html("Cargando...");
                },
                success:  function (response) {
						$("#insumo_box").html(response);
                }
        });	
	}
}
function busca_insumo_cod_enter(e){
	tecla = (document.all) ? e.keyCode : e.which;
	// tecla enter
  	if (tecla==13){
		busca_insumo_cod();
	}
}
function busca_insumo_cod(){
	   var valor = $("#codigoprod").val();
	  $("#codigop").val(''); 
	   var parametros = {
              "codigoprod" : valor,
		      "idajuste" : "<?php echo $idajuste; ?>"
	   };
       $.ajax({
                data:  parametros,
                url:   'gest_ajustar_stock_cuadro.php',
                type:  'post',
                beforeSend: function () {
                        $("#insumo_box").html("Cargando...");
                },
                success:  function (response) {
						$("#insumo_box").html(response);
                }
        });	
}
function busca_insumo_cbar(e){
	var codbar = $("#codigobarra").val();
	tecla = (document.all) ? e.keyCode : e.which;
	// tecla enter
  	if (tecla==13){
		var valor = $("#codigobarra").val();
		$("#codigop").val(''); 
		var parametros = {
		  "codigobarra" : valor,
		  "idajuste" : "<?php echo $idajuste; ?>"
		};
		$.ajax({
			data:  parametros,
			url:   'gest_ajustar_stock_cuadro.php',
			type:  'post',
			beforeSend: function () {
					$("#insumo_box").html("Cargando...");
			},
			success:  function (response) {
					$("#insumo_box").html(response);
					$("#codigobarra").val('');
					$(".insu_focus_1").focus();
			}
		});	
	}
}
function busca_insumo_grup(valor){
	  $("#codigoprod").val('');
	  $("#codigop").val(''); 
	   var parametros = {
              "grupo" : valor,
		   	  "idajuste" : "<?php echo $idajuste; ?>"
	   };
       $.ajax({
                data:  parametros,
                url:   'gest_ajustar_stock_cuadro.php',
                type:  'post',
                beforeSend: function () {
                        $("#insumo_box").html("Cargando...");
                },
                success:  function (response) {
						$("#insumo_box").html(response);
                }
        });	
}
	function addtmp_todo(){
		//alert($("#tras_insumo").serialize());
       $.ajax({
                data:  $("#tras_insumo").serialize(),
                url:   'add_tmp_ajuste.php',
                type:  'post',
				dataType: 'html',
                beforeSend: function () {
                        $("#insumo_box").html("");
						$("#tmprodusmov").html('Cargando...');
                },
                success:  function (response) {
						$("#tmprodusmov").html(response);
						
                }
        });
		
	}
	function terminar(tandafin){
		$("#cerrartrans").hide();
		var tfp=document.getElementById('ter').value;
		if (tfp !=''){
			document.getElementById('fin').submit();
		}
		
	}
	function chau(cual,tanda){
		if (cual !=''){	
		   var parametros = {
				  "cual" : cual,
			   	  "idta" : tanda,
			   	  "tp"   : "3",
			      "idajuste" : "<?php echo $idajuste; ?>"
		   };
		   $.ajax({
					data:  parametros,
					url:   'add_tmp_ajuste.php',
					type:  'post',
					beforeSend: function () {
						$("#tmprodusmov").html("Cargando...");
					},
					success:  function (response) {
						$("#tmprodusmov").html(response);
					}
			});	
			

		}
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
                    <h2>Ajustar Stock</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<a href="ajustar_stock_import.php?id=<?php echo $idajuste; ?>" class="btn btn-sm btn-default"><span class="fa fa-upload"></span> Carga Masiva</a>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">Idajuste</th>

			<th align="center">Deposito</th>

			<th align="center">Motivo</th>
			<th align="center">Registrado el</th>
			<th align="center">Registrado por</th>

		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>

			<td align="center"><?php echo intval($rs->fields['idajuste']); ?></td>

			<td align="center"><?php echo antixss($rs->fields['deposito']); ?> [<?php echo intval($rs->fields['iddeposito']); ?>]</td>

			<td align="center"><?php echo antixss($rs->fields['motivo_ajuste']); ?> [<?php echo antixss($rs->fields['idmotivo']); ?>]</td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>

		</tr>
<?php

$rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>

    </table>
</div>
	<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>				  
<div id="productos" align="center">
   		<span class="resaltaditomenor"><?php echo $orichar ?> Buscar Insumo <?php echo $deschar?></span>
    	<br />
        <form id="sc2" name="sc2" action="gest_adm_depositos_mover_tanda.php" method="post" >
        <table width="400">
        	 <tr>
        	   <td><input type="text" name="codigoprod" id="codigoprod" style="width:99%; height:40px;" value="<?php echo htmlentities($_POST['codigoprod']);?>" placeholder="codigo" onkeyup="busca_insumo_cod_enter(event)" /></td>
        		<td height="28" colspan="2"><input type="text" name="codigop" id="codigop" style="width:99%; height:40px;" value="<?php if ($_POST['codigoprod'] == '') {
        		    echo htmlentities($_POST['codigop']);
        		} ?>" placeholder="Ingrese producto a buscar" onkeyup="busca_insumo(this.value);"   />
                </td>
        		<td width="26"><select name="grupo" required="required" id="grupo" style="width:90%; height:40px;" onchange="busca_insumo_grup(this.value);">
                <option value="0" selected="selected">Seleccionar</option>
                <?php
                $buscar = "Select * from grupo_insumos where idempresa=$idempresa and estado=1 order by nombre asc";
$gr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

while (!$gr->EOF) {?>
                <option value="<?php echo $gr->fields['idgrupoinsu']?>" <?php if ($gr->fields['idgrupoinsu'] == $_GET['gr']) { ?>selected="selected"<?php } ?>><?php echo trim($gr->fields['nombre']) ?></option>
                <?php $gr->MoveNext();
}?>
              </select></td>	
                </td>
        		<td width="26"><a href="javascript:void(0);" onclick="busca_insumo_cod();"><img src="img/buscar.png" width="32" height="32" alt=""/></a>
                	
                </td>
        </tr>
         <tr>
                        <td colspan="5"><input type="text" name="codigobarra" id="codigobarra" style="width:99%; height:40px;" value="<?php echo htmlentities($_POST['codigobar']);?>" placeholder="codigo barras" onkeyup="busca_insumo_cbar(event);"   /></td>
                </td>
         </tr>
        
        
        </table>
    	</form>
    </div>
     <br />

     <div align="center"><?php echo $errorcantidaad;?></div>
    	<div align="center" id="insumo_box"></div>

    <div id="tmprodusmov" align="center">
    <?php require_once('add_tmp_ajuste.php');?>
    
    </div>

<form id="form1" name="form1" method="post" action="">	
<div class="clearfix"></div>
<br /><hr /><br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Finalizar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='ajustar_stock.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>
<input type="hidden" name="ter" id="ter" value="<?php echo intval($idajuste); ?>"  />
  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>				  
<br />
<br /><br />

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
