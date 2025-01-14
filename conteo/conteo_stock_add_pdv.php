<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "162";
$dirsup = "S";

require_once("../includes/rsusuario.php");


//print_r($_POST);


if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

    // recibe parametros
    $fecha_inicio = antisqlinyeccion($ahora, "text");
    $iniciado_por = antisqlinyeccion($idusu, "int");
    $finalizado_por = antisqlinyeccion('', "int");
    $inicio_registrado_el = antisqlinyeccion($ahora, "text");
    $final_registrado_el = antisqlinyeccion('', "text");
    $estado = antisqlinyeccion(1, "int");
    $afecta_stock = antisqlinyeccion('N', "text");
    $fecha_final = antisqlinyeccion('', "text");
    $observaciones = antisqlinyeccion(' ', "text");
    $iddeposito = antisqlinyeccion($_POST['iddeposito'], "int");
    $totinsu = intval($_POST['totinsu']);

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // para evitar hack que colapse el servidor
    if (intval($_POST['totinsu']) > 1000) {
        $valido = "N";
        $errores .= " - La cantidad de grupos marcados supera el maximo permitido.<br />--> Intento de Hack Registrado ;)<br />";
        $totinsu = 1000;
    }
    if (intval($_POST['iddeposito']) == 0) {
        $valido = "N";
        $errores .= " - Debes seleccionar el deposito.<br />";
    }
    // buscamos que exista el deposito y su sucursal
    $consulta = "
	select * from gest_depositos
	where 
	idempresa = $idempresa
	and estado = 1
	and iddeposito = $iddeposito
	";
    $rsdep = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $iddeposito = intval($rsdep->fields['iddeposito']);
    $idsucu = intval($rsdep->fields['idsucursal']);
    if ($iddeposito == 0) {
        $valido = "N";
        $errores .= " - Deposito inexistente.<br />";
    }




    //$valido="N";
    // validaciones especificas

    // no se puede iniciar un conteo por que este deposito ya tiene activo otro con el mismo grupo de insumos



    // validar grupo de insumos que al menos 1 este marcado
    $totinsu_valor = 0;
    for ($i = 0;$i <= $totinsu;$i++) {
        $idgrupoinsu = intval($_POST['grupo_'.$i]);
        if ($idgrupoinsu > 0) {
            // busca si existe en la bd y si le pertenece
            $consulta = " SELECT * FROM grupo_insumos where idempresa = $idempresa and idgrupoinsu = $idgrupoinsu and estado = 1 ";
            $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // si existe cuenta
            if ($rsex->fields['idgrupoinsu'] > 0) {
                $totinsu_valor++;
                $grupo_enc[$totinsu_valor] = $idgrupoinsu;
            }
        }

    }
    // si no selecciono ningun insumo
    if ($totinsu_valor == 0) {
        $valido = "N";
        $errores .= " - Debes marcar al menos 1 grupo de insumos.<br />";
    }

    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		insert into conteo
		(fecha_inicio, iniciado_por, finalizado_por, estado, afecta_stock, fecha_final, observaciones,  idsucursal, idempresa, iddeposito, inicio_registrado_el, final_registrado_el)
		values
		($fecha_inicio, $iniciado_por, $finalizado_por, $estado, $afecta_stock, $fecha_final, $observaciones,  $idsucu, $idempresa, $iddeposito, $inicio_registrado_el, $final_registrado_el)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		select idconteo from conteo where idempresa = $idempresa and iniciado_por = $iniciado_por order by idconteo desc
		";
        $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idconteo = intval($rsmax->fields['idconteo']);


        // inserta en conteo grupo
        foreach ($grupo_enc as $idgrupo) {
            $idgrupoinsu = $idgrupo;
            $consulta = "
			insert into conteo_grupos
			(idgrupoinsu, idconteo, idempresa)
			values
			($idgrupoinsu, $idconteo, $idempresa)
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }

        header("location: conteo_stock_contar_pdv.php?id=".$idconteo);
        exit;

    }

}

$consulta = "
select * from grupo_insumos
where
idempresa = $idempresa
and estado = 1
order by nombre desc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>


<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("../includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<link rel="stylesheet" type="text/css" href="css/magnific-popup.css" />
<?php require("../includes/head.php"); ?>
<script>
function envia_form(){
	$("#button").hide();
	$("#form1").submit();
}
</script>
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
                    <h2>Conteo Stock PDV</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
					
				  



	<div class="clear"></div>
		<div class="cuerpo">
			<div class="colcompleto" id="contenedor">

           <div align="center">
    		<table width="70" border="0">
          <tbody>
            <tr>
              <td width="62"><a href="conteo_stock_pdv.php"><img src="img/homeblue.png" width="64" height="64" title="Regresar"/></a></td>
            </tr>
          </tbody>
        </table>
    </div>
 				<div class="divstd">
					<span class="resaltaditomenor">Conteo de Stock</span>
				</div>

<p align="center">&nbsp;</p>
<p align="center">&nbsp;</p>
<form id="form1" name="form1" method="post" action="">
<?php if (trim($errores) != "") { ?>
	<div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;"><strong>Errores:</strong> <br /><?php echo $errores; ?></div><br />
<?php } ?>
<p align="center">Seleccione Grupo de Insumos:</p>
<p align="center">&nbsp;</p>
<table width="400" border="1">
  <tr class="tablaconborde">
    <td align="center" bgcolor="#F8FFCC"><input type="checkbox" name="checkbox" id="checkbox" onclick="marcar(this);" /></td>
    <td align="center" bgcolor="#F8FFCC" class="tablalinda2"><strong>Grupo</strong></td>
  </tr>
<?php
$i = 1;
while (!$rs->EOF) { ?>
  <tr class="tablaconborde">
    <td align="center"><input type="checkbox" name="grupo_<?php echo $i; ?>" <?php if (intval($_POST['grupo_'.$i]) > 0) { ?>checked="checked"<?php } ?> value="<?php echo $rs->fields['idgrupoinsu']; ?>" /></td>
    <td><?php echo $rs->fields['nombre']; ?></td>
  </tr>
<?php $i++;
    $rs->MoveNext();
} ?>
  <tbody>
  </tbody>
</table>
<br /><br />
<p align="center">Deposito:</p>
<p align="center">              <?php

$buscar = "Select iddeposito,descripcion,tiposala,color,direccion,usuario
from gest_depositos 
inner join usuarios on usuarios.idusu=gest_depositos.idencargado 
where 
usuarios.idempresa=$idempresa 
and gest_depositos.idempresa=$idempresa 
and gest_depositos.idsucursal = $idsucursal
order by tiposala desc, descripcion ASC ";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
?><select name="iddeposito" id="iddeposito">
          			<?php while (!$rsd->EOF) {?>
                		<option value="<?php echo $rsd->fields['iddeposito']?>" <?php if ($rsd->fields['iddeposito'] == $_POST['iddeposito']) { ?>selected="selected"<?php } ?>><?php echo $rsd->fields['descripcion']?></option>
         			 <?php $rsd->MoveNext();
          			} ?>
                    </select></p>
<p align="center">&nbsp;</p>
<br />
<p align="center">
  <input type="submit" name="button" id="button" value="Iniciar Conteo" onmouseup="envia_form();" />
  <input type="hidden" name="MM_insert" value="form1" /><input type="hidden" name="totinsu" id="totinsu" value="<?php echo $i; ?>" />
</p>
<br />

</form>
<p align="center">&nbsp;</p>
          </div> 
			<!-- contenedor -->
   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->

	


					</div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            

            
            
            
          </div>
        </div>
        <!-- /page content -->
		  
        <!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        
            <div class="modal-header">
            	<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
           		<h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
            	Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
            	<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
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
