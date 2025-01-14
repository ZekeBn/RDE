<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "256";
require_once("includes/rsusuario.php");

require_once("includes/funciones_stock.php");



// si la caja no esta abierta direcciona
$parametros_array = [
    'idcajero' => $idusu,
    'idsucursal' => $idsucursal,
    'idtipocaja' => 1
];
$res = caja_abierta($parametros_array);
$idcaja = $res['idcaja'];


//print_r($res);
///exit;
if ($res['valido'] != 'S') {
    header("location: gest_administrar_caja.php");
    exit;
}

if (intval($_REQUEST['ocidreg']) > 0) {
    //print_r($_REQUEST);
    //anulamos esa nc
    $idnc = intval($_REQUEST['ocidreg']);
    //cabecera
    $buscar = "Select * from nota_credito_cabeza_proveedor where idnotacred=$idnc";
    $rsnca = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    //TOmamos el id del proveedor
    $idproveedor = intval($rsnca->fields['idproveedor']);


    if ($rsnca->fields['anulado_por'] == 0 && $rsnca->fields['estado'] <> 6) {
        //Generamos el cuerpo de la nc original;p
        $buscar = "Select * from nota_credito_cuerpo_proveedor where idnotacred=$idnc";
        $rsncu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $numeronota = $rsncu->fields['numero'];
        $solofechanota = date("y-m-d", strtotime($rsncu->fields['fecha_nota']));
        $errores = "";
        //validar
        while (!$rsncu->EOF) {
            $parametros_array['idnotacredito'] = $idnc;
            $parametros_array['anulado_por'] = $idusu;
            $res = pago_proveedor_valida_anulacion($parametros_array);
            if ($res['valido'] != 'S') {
                $errores .= $res['errores'];
                $valido = "N";
            }



            $rsncu->MoveNext();

        }
        $rsncu->MoveFirst();

        if ($errores == '') {

            while (!$rsncu->EOF) {
                //Primero vemos si tienen deposito , puede que sea prod pero solo si cantidad  > 0 devolvemos al deposito del cual salio
                $iddeposito = intval($rsncu->fields['iddeposito']);
                $facturanum = trim($rsncu->fields['factura']);
                $idfactura = intval($rsncu->fields['id_factura']);
                $idinsumo = intval($rsncu->fields['codproducto']);
                $cantidad = floatval($rsncu->fields['cantidad']);
                $precio = floatval($rsncu->fields['precio']);
                $subtotal = floatval($rsncu->fields['subtotal']);





                //regresamos al stock
                if ($iddeposito > 0 && $idinsumo > 0 && $facturanum == '') {

                    //echo 'cc'.$idinsumo.$idinsumo.$facturanum;exit;
                    aumentar_stock_general($idinsumo, $cantidad, $iddeposito);
                    // aumenta stock costo
                    aumentar_stock($idinsumo, $cantidad, $precio, $iddeposito);
                    // registra el aumento // codrefer es idnotacredito y fechacomprobante es fecha nota de credito
                    movimientos_stock($idinsumo, $cantidad, $iddeposito, 16, '+', $numeronota, $solofechanota); // 16 ANULA NC PROV

                }

                $parametros_array['idnotacredito'] = $idnc;
                $parametros_array['anulado_por'] = $idusu;
                pago_proveedor_anula($parametros_array);



                $rsncu->MoveNext();
            }
        }
        //Por ultimo marcamos como anulado
        $update = "Update nota_credito_cabeza_proveedor set estado=6,anulado_por=$idusu,anulado_el=current_timestamp where idnotacred=$idnc";
        $conexion->Execute($update) or die(errorpg($conexion, $update));

        header("Location: nota_credito_prov_anula.php");
        exit;
    } else {
        echo $errores;
        exit;
        //errores

    }

}

//Ultimas anuladas
//echo $metodo;
$buscar = "Select timbrado,(select nombre from proveedores where idproveedor=nota_credito_cabeza_proveedor.idproveedor) as proveedor,
  numero,fecha_nota,idnotacred,usuario,nota_credito_cabeza_proveedor.registrado_el,nota_credito_cabeza_proveedor.estado,
  (select usuario from usuarios where idusu=anulado_por) as anulador,anulado_el,
  (select sum(subtotal) as t from nota_credito_cuerpo_proveedor
  where idnotacred=nota_credito_cabeza_proveedor.idnotacred) as totalnc
  from nota_credito_cabeza_proveedor
  inner join usuarios on usuarios.idusu=nota_credito_cabeza_proveedor.registrado_por
  where nota_credito_cabeza_proveedor.estado=6
  order by numero desc limit 20";
$rnotasanu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tanuladas = $rnotasanu->RecordCount();

?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
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
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Anulacion NC: Proveedores</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                      <div class="col-md12">
                            <div class="col-md-6">
                                <input type="text" name="numeronc" id="numeronc" placeholder="Número Nota" style="height:40px;width:90%;" onKeyUp="filtrar(1);" />
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="idnumeronc" id="idnumeronc" placeholder="Id Nota credito" style="height:40px;width:90%;" onKeyUp="filtrar(2);" />
                            </div>
                      </div>
							<div id="listanc">
                            	 <?php require_once("nota_cred_anula_sele.php");?>
                            
                            </div>




                  </div>
                </div>
              </div>
            </div>
            <?php if ($tanuladas > 0) {?>
            
            <!-- SECCION --> 
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Ultimas anulaciones efectuadas</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    		<table class="table table-striped jambo_table bulk_action">
			<thead>
				<tr class="headings">
					
					 <th class="column-title">Proveedor</th>
					<th class="column-title">Fecha Nota </th>
					<th class="column-title">Numero</th>
					 <th class="column-title">Monto NC</th>
					
					 <th class="column-title">Anulado por</th>
					 <th class="column-title">Anulado el</th>
					
				</tr>
			</thead>
			
			<tbody>
			 <?php while (!$rnotasanu->EOF) {?>
				<tr class="even pointer">
				
					<td height="35" class=" "><?php echo $rnotasanu->fields['proveedor']?></td>
					<td align="center" class=" "><?php echo date("d/m/Y", strtotime($rnotasanu->fields['fecha_nota']));?></td>
					<td align="center" class=" "><?php echo $rnotasanu->fields['numero']?></td>
					<td align="center" class=" "><?php echo formatomoneda($rnotasanu->fields['totalnc']); ?></td>
				  
				 
					<td class=" "><?php echo $rnotasanu->fields['anulador']?></td>
			 <td align="center" class=" "><?php echo date("d/m/Y H:i:s", strtotime($rnotasanu->fields['anulado_el']));?></td>
				   
				</tr>
				<?php $rnotasanu->MoveNext();
			 }?>
			</tbody>
			
			
			</table>


                  </div>
                </div>
              </div>
            </div>
            
            
            <?php }?>
          </div>
        </div>
         <div class="modal fade" id="modpop"  role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document">
			  <div class="modal-content">

				<!-- Modal Header -->
				<div class="modal-header">
					
					
					<span  id="modal_titulo" style="font-weight:bold;">Atencion</span>
				  <button type="button" class="close" data-dismiss="modal">&times;</button>
				</div>

				<!-- Modal body -->
				<div class="modal-body" id="modal_cuerpo" >
				  Est&aacute; por anular el registro de nota de credito proveedor. Esta seguro?
				</div>

				<!-- Modal footer -->
				<div class="modal-footer">
                <button type="button" data-dismiss="modal" class="btn" onClick="enviar(1)">Cancelar</button>
				<button type="button"  class="btn btn-primary" id="delete" onClick="enviar(2)">Eliminar</button>
    			
				</div>

			  </div>
			</div>
		  </div>
        <!-- /page content -->
<script>
function enviar(cual){
	if(cual==2){
		var idn=$("#ocidreg").val();
		if (idn!=''){
				$("#hgj1").submit();
		} else {
			alert('VACIO')	;
		}
		
	}
	if(cual==1){
		$("#modpop").modal('hide');
		
	}
}
function confirmar(idnota){
	$("#ocidreg").val(idnota);
	$("#modpop").modal('show');
	
}
function pedir_permiso(){
		}
	function filtrar(cual){
		
			var metodo=cual;
			var filtro='';
			if (cual==1){
					filtro=$("#numeronc").val();
				
			}
			if (cual==2){
					filtro=$("#idnumeronc").val();
				
			}
			if (filtro==''){
				//alert('aca');
				filtro='';
				metodo=3;
			}
			var parametros = {
					"metodo" : metodo,
					"valor"		:filtro
				};
				$.ajax({
						data:  parametros,
						url:   'nota_cred_anula_sele.php',
						type:  'post',
						beforeSend: function () {

						},
						success:  function (response) {
							
							$("#listanc").html(response);
						}
				});
	}

</script>
        <!-- footer content -->
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
