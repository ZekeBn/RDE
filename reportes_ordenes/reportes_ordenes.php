<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "602";
// $modulo="1";
// $submodulo="2";
$dirsup = "S";
require_once("../includes/rsusuario.php");

require_once("../compras_ordenes/preferencias_compras_ordenes.php");
require_once("../proveedores/preferencias_proveedores.php");
$whereadd = "";
$consult = "";
$ocnum = intval($_GET['ocnum']);
if ($ocnum > 0) {


    // consulta a la tabla
    $consulta = "
select * ,compras_ordenes.idproveedor, prov.nombre as proveedor, tipo_o.tipo as tipo_origen,
cotizaciones.cotizacion as cot_ref
from compras_ordenes 
left join proveedores as prov on prov.idproveedor = compras_ordenes.idproveedor 
left join tipo_origen as tipo_o on tipo_o.idtipo_origen = compras_ordenes.idtipo_origen
left join cotizaciones on cotizaciones.idcot = compras_ordenes.idcot  
where 
compras_ordenes.ocnum = $ocnum
and compras_ordenes.estado = 2 and compras_ordenes.ocnum_ref is NULL
limit 1
";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $ocnum = intval($rs->fields['ocnum']);
    $idproveedor_res = intval($rs->fields['idproveedor']);
    $proveedor_res = antixss($rs->fields['proveedor']);




    $buscar = "SELECT * FROM `tipo_origen` WHERE UPPER(tipo) = UPPER('importacion')";
    $rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idtipo_origen_importacion = intval($rsd->fields['idtipo_origen']);



    $buscar = "SELECT idproveedor, idtipo_origen,nombre ,idmoneda, ruc,tipocompra
FROM proveedores
";

    $resultados_proveedores = null;
    $rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    while (!$rsd->EOF) {
        $idproveedor = trim(antixss($rsd->fields['idproveedor']));
        $idmoneda = trim(antixss($rsd->fields['idmoneda']));
        $idtipo_origen = trim(antixss($rsd->fields['idtipo_origen']));
        $nombre = trim(antixss($rsd->fields['nombre']));
        $tipocompra = trim(antixss($rsd->fields['tipocompra']));
        $ruc = trim(antixss($rsd->fields['ruc']));
        $resultados_proveedores .= "
	<a class='a_link_proveedores'  href='javascript:void(0);' data-hidden-value='$ruc' onclick=\"cambia_proveedor($idtipo_origen, $idmoneda, $idproveedor, '$nombre',$tipocompra);\">[$idproveedor]-$nombre</a>
	";

        $rsd->MoveNext();
    }


} else {
    // echo "Error";exit;
}



?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <script>
<?php if ($ocnum > 0) { ?>
		window.onload = function(){
			$("#crear_suborden").css("display", "none");
		}
	<?php } ?>   
      function  habilitar_edit(ocnum){
      var parametros = {
            "ocnum"   : ocnum
            };
        $.ajax({
                    data:  parametros,
                    url:   'habilitar_edit_compras_orden.php',
                    type:  'post',
                    beforeSend: function () {
                          // $("#selecompra").html('Cargando...');  
                    },
                    success:  function (response) {
                // $("#selecompra").html(response);
                // $("#cantidad").focus();
                if(JSON.parse(response)['success'] == true){
                }
              }
            });	
            // await sleep(500);
            document.location.href='compras_ordenes_det.php?id='+ocnum;
    }
	function buscar_por_orden_compra(event){
		event.preventDefault();
		var parametros = {
				"idtransaccion"   : "idtransaccion",
				"idunico"		  : "idunico"
		};

		$("#titulov").html("Buscar Orden de Compra");
		$.ajax({		  
			data:  parametros,
			url:   'buscar_orden_compra_modal.php',
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {	
				
			},
			success:  function (response) {
				$("#ventanamodal").modal("show");
				$("#cuerpov").html(response);	
				
			}
		});
	}
  </script>
<style type="text/css">
        #lista_proveedores {
            width: 100%;
        }
       
        .a_link_proveedores{
            display: block;
            padding: 0.8rem;
        }	
        .a_link_proveedores:hover{
            color:white;
            background: #73879C;
        }
        .dropdown_proveedores{
            position: absolute;
            top: 70px;
            left: 0;
            z-index: 99999;
            width: 100% !important;
            overflow: auto;
            white-space: nowrap;
            background: #fff !important;
            border: #c2c2c2 solid 1px;
        }
        .dropdown_proveedores_input{ 
            position: absolute;
            top: 37px;
            left: 0;
            z-index: 99999;
            display:none;
            width: 100% !important;
            padding: 5px !important;
        }
        .btn_proveedor_select{
            border: #c2c2c2 solid 1px;
            color: #73879C;
            width: 100%;
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
                    <h2>Ordenes de Compra </h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                    </li>
                  </ul>
                  <div class="clearfix"></div>
                </div>
                <div class="x_content">
                  
                <form id="form1" name="form1" method="get" action="">
                    <label class="control-label col-md-3 col-sm-3 col-xs-12">OCNUM *</label>
					<div class="col-md-9 col-sm-9 col-xs-12">
						<input type="text" name="ocnum" id="ocnum" value="<?php  if (isset($_POST['ocnum'])) {
						    echo htmlentities($_POST['ocnum']);
						} else {
						    echo htmlentities($ocnum);
						}?>" placeholder="Ej: ocnum" class="form-control"   />                    
					</div>


					<a href="javascript:void(0);" onclick="buscar_por_orden_compra(event);" class="btn btn-sm btn-default">
							<span class="fa fa-list"></span> Elegir por Orden de Compra
					</a>
					
					<div class="clearfix"></div>
					<br />

					<div class="form-group">
						<div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
							<button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Buscar</button>
						</div>
					</div>
                    
     			</form>
                  


   
                <?php if (trim($errores) != "") { ?>
						<div class="alert alert-danger alert-dismissible fade in" role="alert">
							<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
							</button>
							<strong>Errores:</strong><br /><?php echo $errores; ?>
						</div>
						<?php } ?>

            <?php if ($ocnum > 0) {?>
			<form id="form1" name="form1" method="post" action="">
				<br>
				<br>
				<br>
				<div class=" form-group">
					<label class="control-label col-md-3 col-sm-3 col-xs-12">Proveedor *</label>
					<div class="col-md-9 col-sm-9 col-xs-12">
						<div class="" style="display:flex;">
							<div class="dropdown " id="lista_proveedores">
								<select disabled class="form-control" id="idproveedor" name="idproveedor">
								<option value="" disabled ></option>
								<?php if (intval($idproveedor_res) > 0) { ?>
									<option value="<?php echo $idproveedor_res; ?>"   selected><?php echo $proveedor_res; ?></option>
								<?php } ?>
							</select>
							</div>
								<!-- <a  href="javascript:void(0);" onclick="agregar_proveedor(event);" class="btn btn-sm btn-default">
									<span  class="fa fa-plus"></span> Agregar
								</a> -->
						</div>
					</div>
				</div>
				<div class="clearfix"></div>
				<div class=" form-group">
					<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Origen *</label>
					<div class="col-md-9 col-sm-9 col-xs-12">
					<?php

                        // consulta

                        $consulta = "
						SELECT *
						FROM tipo_origen
						order by tipo asc
						";

                // valor seleccionado
                if (isset($_POST['idtipo_origen'])) {
                    $value_selected = htmlentities($_POST['idtipo_origen']);
                } else {
                    $value_selected = $rs->fields['idtipo_origen'];
                }



                // parametros
                $parametros_array = [
                    'nombre_campo' => 'idtipo_origen',
                    'id_campo' => 'idtipo_origen',

                    'nombre_campo_bd' => 'tipo',
                    'id_campo_bd' => 'idtipo_origen',

                    'value_selected' => $value_selected,

                    'pricampo_name' => 'Seleccionar...',
                    'pricampo_value' => '',
                    'style_input' => 'class="form-control"',
                    'acciones' => ' required="required" disabled "'.$add,
                    'autosel_1registro' => 'N'

                ];

                // construye campo
                echo campo_select($consulta, $parametros_array);

                ?>
					</div>
				</div>

				<div class="form-group">
					<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Orden *</label>
					<div class="col-md-9 col-sm-9 col-xs-12">
					<input disabled type="date" name="fecha" id="fecha" value="<?php  if (isset($_POST['fecha'])) {
					    echo htmlentities($_POST['fecha']);
					} else {
					    echo date("Y-m-d", strtotime(htmlentities($rs->fields['fecha'])));
					}?>" placeholder="Fecha" class="form-control" required="required" />                    
					</div>
				</div>

				<div class="form-group">
					<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipocompra *</label>
					<div class="col-md-9 col-sm-9 col-xs-12">
						<?php

                        // consulta

                        $consulta = "
						SELECT *
						FROM tipocompra
						order by tipocompra asc
						";

                // valor seleccionado
                if (isset($_POST['idtipocompra'])) {
                    $value_selected = htmlentities($_POST['idtipocompra']);
                } else {
                    $value_selected = $rs->fields['tipocompra'];
                }



                // parametros
                $parametros_array = [
                    'nombre_campo' => 'idtipocompra',
                    'id_campo' => 'idtipocompra',

                    'nombre_campo_bd' => 'tipocompra',
                    'id_campo_bd' => 'idtipocompra',

                    'value_selected' => $value_selected,

                    'pricampo_name' => 'Seleccionar...',
                    'pricampo_value' => '',
                    'style_input' => 'class="form-control"',
                    'acciones' => ' required="required" disabled "'.$add,
                    'autosel_1registro' => 'N'

                ];

                // construye campo
                echo campo_select($consulta, $parametros_array);

                ?>
					</div>
				</div>
				<div class="form-group">
					<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha entrega </label>
					<div class="col-md-9 col-sm-9 col-xs-12">
					<input disabled type="date" name="fecha_entrega" id="fecha_entrega" value="<?php  if (isset($_POST['fecha_entrega'])) {
					    echo htmlentities($_POST['fecha_entrega']);
					} else {
					    echo htmlentities($rs->fields['fecha_entrega']);
					}?>" placeholder="Fecha entrega" class="form-control"  />                    
					</div>
				</div>
				<div id="monedas" class="form-group">
					<div class="form-group">
						<label class="control-label col-md-3 col-sm-3 col-xs-12">Moneda *</label>
						<div class="col-md-9 col-sm-9 col-xs-12">
						<?php

                        // consulta


                        $consulta = "
						Select * from tipo_moneda where estado=1 and nacional!='S' and cotiza=1
						";

                // valor seleccionado
                if (isset($_POST['idtipo_moneda'])) {
                    $value_selected = htmlentities($_POST['idtipo_moneda']);
                } else {
                    $value_selected = $rs->fields['idtipo_moneda'];
                }



                // parametros
                $parametros_array = [
                    'nombre_campo' => 'idtipo_moneda',
                    'id_campo' => 'idtipo_moneda',

                    'nombre_campo_bd' => 'descripcion',
                    'id_campo_bd' => 'idtipo',

                    'value_selected' => $value_selected,

                    'pricampo_name' => 'Seleccionar...',
                    'pricampo_value' => '',
                    'style_input' => 'class="form-control"',
                    'acciones' => ' disabled  "'.$add,
                    'autosel_1registro' => 'N'

                ];

                // construye campo
                echo campo_select($consulta, $parametros_array);

                ?>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-md-3 col-sm-3 col-xs-12">Cambio del dia</label>
						<div class="col-md-9 col-sm-9 col-xs-12">
							<input  disabled type="text" name="cot_ref" id="cot_ref" aria-describedby="cotRefHelp" placeholder="Cambio del dia" value="<?php  if (isset($_POST['cot_ref'])) {
							    echo htmlentities($_POST['cot_ref']);
							} else {
							    echo htmlentities($rs->fields['cot_ref']);
							}?>" class="form-control"/>
							<small id="cotRefHelp"><small>    
						</div>
					</div>

					<div id="grilla_box"><?php require("../compras_ordenes/compras_ordenes_grillaprod_det.php"); ?></div>
					<div id="grilla_box_ordenes_asociadas"><?php require("../compras_ordenes/sub_ordenes_asociadas_detalle.php"); ?></div>
					<!-- <div id="grilla_box_ordenes_asociadas"><?php // require("sub_ordenes_asociadas.php");?></div> -->
					<div class="clearfix"></div>


				</div><div class="form-group">
					<div class="col-md-12 col-sm-12 col-xs-12" style="display:flex;justify-content:center;">
						<button type="button" class="btn btn-primary" onMouseUp="document.location.href='compras_ordenes.php'"><span class="fa fa-ban"></span> volver</button>
					</div>
				</div>

				<input type="hidden" name="MM_update" value="form1" />
				<input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
				<br />
			</form>
				<?php } ?>




                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            



            



                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 

			<!-- POPUP DE MODAL OCULTO -->

			<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="ventanamodal">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
		<div class="modal-header">
			<h4 class="modal-title" id="titulov"></h4>
		</div>
		<div class="modal-body" id="cuerpov" >
			
		</div>
		<div class="modal-footer"  id="piev">
			
			<button type="button" id="cerrarpop" style="display:none;" class="btn btn-default" data-dismiss="modal">Cerrar</button>&nbsp;
			
		</div>

		</div>
	</div>
</div>
        <!-- POPUP DE MODAL OCULTO -->



        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
