<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "223";
require_once("includes/rsusuario.php");

// funciones para stock
require_once("includes/funciones_stock.php");

$idtanda = intval($_GET['id']);
if ($idtanda == 0) {
    header("location: gest_transferencias_rec.php");
    exit;
}

$consulta = "
select editar_traslado , editar_traslado_recep
from usuarios 
where 
idusu = $idusu
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$editar_traslado = $rs->fields['editar_traslado'];
$editar_traslado_recep = $rs->fields['editar_traslado_recep'];

$consulta = "
select *,
(select descripcion from gest_depositos where iddeposito = gest_transferencias.origen) as deposito_origen,
(select descripcion from gest_depositos where iddeposito = gest_transferencias.destino) as deposito_destino,
(select usuario from usuarios where gest_transferencias.generado_por = usuarios.idusu) as generado_por
from gest_transferencias 
where 
 estado = 2 
  and idtanda = $idtanda
order by idtanda asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtanda = intval($rs->fields['idtanda']);
$fecha_transferencia = $rs->fields['fecha_transferencia'];
$iddeposito_destino = intval($rs->fields['destino']);
$iddeposito_origen = intval($rs->fields['origen']);
$idpedidorepo = intval($rs->fields['idpedidorepo']);
if ($idtanda == 0) {
    header("location: gest_transferencias_rec.php");
    exit;
}


$consulta = "
select * from gest_depositos where tiposala = 3 order by iddeposito asc limit 1
";
$rstran = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito_transito = intval($rstran->fields['iddeposito']);
if ($iddeposito_transito == 0) {
    echo "Deposito de transito inexistente.";
    exit;
}


// trae la primera impresora
$consulta = "SELECT * FROM impresoratk where idempresa = $idempresa  and idsucursal = $idsucursal and borrado = 'N' order by idimpresoratk asc limit 1";
$rsimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$pie_pagina = $rsimp->fields['pie_pagina'];
$defaultprnt = "http://localhost/impresorweb/ladocliente.php";
$script_impresora = trim($rsimp->fields['script']);
if (trim($script_impresora) == '') {
    $script_impresora = $defaultprnt;
}
function agregaespacio_new($numero, $caracteresmax = 7, $dir = 'izq')
{
    $len = strlen($numero);
    if ($len > 0) {
        if ($len > $caracteresmax) {
            $numero = substr($numero, 0, $caracteresmax);
        }
        $faltan = $caracteresmax - $len;
        $ceros = "";
        for ($i = 1;$i <= $faltan;$i++) {
            $ceros = $ceros." ";
        }
        // se refiere a direccion del texto no del relleno de espacios
        if ($dir == 'izq') {
            $numero = $numero.$ceros;
        } else {
            $numero = $ceros.$numero;
        }
    }
    return $numero;
}
function traslado_stock_tk($idtanda)
{
    global $saltolinea;
    global $conexion;
    global $cajero;


    $consulta = "
	select * from empresas limit 1
	";
    $rsemp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $razon_social_empresa = trim($rsemp->fields['razon_social']);
    $ruc_empresa = trim($rsemp->fields['ruc']).'-'.trim($rsemp->fields['dv']);
    $direccion_empresa = trim($rsemp->fields['direccion']);
    $nombre_fantasia_empresa = trim($rsemp->fields['empresa']);
    $actividad_economica = trim($rsemp->fields['actividad_economica']);


    $consulta = "
	select *,
	(select descripcion from gest_depositos where iddeposito = gest_transferencias.origen) as deposito_origen,
	(select descripcion from gest_depositos where iddeposito = gest_transferencias.destino) as deposito_destino,
	(select usuario from usuarios where gest_transferencias.generado_por = usuarios.idusu) as generado_por,
	(select usuario from usuarios where gest_transferencias.recibido_por = usuarios.idusu) as recibido_por
	from gest_transferencias 
	where 
	 estado = 2 
	  and idtanda = $idtanda
	order by idtanda asc
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idtanda = intval($rs->fields['idtanda']);
    $fecha_transferencia = date("d/m/Y", strtotime($rs->fields['fecha_transferencia']));
    $iddeposito_destino = intval($rs->fields['destino']);
    $iddeposito_origen = intval($rs->fields['origen']);
    $deposito_destino = antixss($rs->fields['deposito_destino']);
    $deposito_origen = antixss($rs->fields['deposito_origen']);
    $fecha_recepcion = date("d/m/Y", strtotime($rs->fields['fecha_recepcion']));
    $idpedidorepo = intval($rs->fields['idpedidorepo']);
    $generado_por = antixss($rs->fields['generado_por']);
    $recibido_por = antixss($rs->fields['recibido_por']);




    $texto = "
****************************************
".texto_tk(trim($nombre_fantasia_empresa), 40, 'S')."
".texto_tk("TRASLADO DE STOCK Nro. $idtanda", 40, 'S')."
****************************************
Fecha Traslado: $fecha_transferencia
Enviado Por : $generado_por
Fecha Recepcion: $fecha_recepcion
Recibido Por: $recibido_por
Deposito Origen: $deposito_origen
Deposito Destino: $deposito_destino
****************************************".$saltolinea;

    //cuerpo
    $texto .= "> ARTICULOS SIN DIFERENCIA:
----------------------------------------
CANT     | [COD] ARTICULO
----------------------------------------
";

    $consulta = "
select *, 
(select descripcion from insumos_lista where idinsumo = gest_depositos_mov.idproducto) as producto,
(select motivo from  motivos_transfer_norecibe where idmotivo = gest_depositos_mov.idmotivo_dif) as motivo
from gest_depositos_mov 
where 
 idtanda = $idtanda 
 and estado  = 1
 and cantidad_recibe = cantidad
";
    $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    while (!$rsdet->EOF) {
        $texto .= agregaespacio_new(formatomoneda($rsdet->fields['cantidad'], 4, 'N'), 8, 'der').' | '.agregaespacio_new('['.$rsdet->fields['idproducto'].'] '.$rsdet->fields['producto'], 29, 'izq').$saltolinea;
        $rsdet->MoveNext();
    }


    $texto .= $saltolinea;

    $texto .= "
----------------------------------------
> ARTICULOS CON DIFERENCIA:
----------------------------------------
[COD] PRODUCTO 
ENVIADO           | RECIBIDO
----------------------------------------
";

    $consulta = "
select *, 
(select descripcion from insumos_lista where idinsumo = gest_depositos_mov.idproducto) as producto,
(select motivo from  motivos_transfer_norecibe where idmotivo = gest_depositos_mov.idmotivo_dif) as motivo
from gest_depositos_mov 
where 
 idtanda = $idtanda 
 and estado  = 1
 and cantidad_recibe <> cantidad
";
    $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    while (!$rsdet->EOF) {
        $texto .= agregaespacio_new('['.$rsdet->fields['idproducto'].'] '.$rsdet->fields['producto'].'', 40, 'izq').$saltolinea;
        $texto .= agregaespacio_new(formatomoneda($rsdet->fields['cantidad'], 4, 'N'), 17, 'der').' | '.agregaespacio_new(formatomoneda($rsdet->fields['cantidad_recibe'], 4, 'N'), 18, 'der').$saltolinea;
        $texto .= ' >MOTIVO: '.$rsdet->fields['motivo'].$saltolinea;
        //$texto.="_  _  _  _  _  _  _  _  _  _  _  _  _  _".$saltolinea;
        $rsdet->MoveNext();
    }


    $texto .= "----------------------------------------
IMPRESO EL : ".date("d/m/Y H:i:s")."
IMPRESO POR: ".strtoupper($cajero)."


        
		----------------------
		   FIRMA RECIBIDO

ACLARACION: _ _ _ _ _ _ _ _ _ _ _ _ _ _

NUMERO CI:  _ _ _ _ _ _ _ _ _ _ _ _ _ _

****************************************
";

    return $texto;
}

$texto = traslado_stock_tk($idtanda);


?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
<script>
function imprime_cliente(){
		//alert('a');
		var texto = document.getElementById("texto").value;
		//alert(texto);
        var parametros = {
                "tk"      : texto,
				'tk_json' : ''
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
							$("#impresion_box").html("Impresion Enviada!<hr />");
						}else{
							$("#impresion_box").html(response);	
						}
						
						
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
                    <h2>Traslados entrantes sin confirmar</h2>
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

<p>
<a href="gest_transferencias_rec.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
<a href="javascript:imprime_cliente();void(0);" class="btn btn-sm btn-default"><span class="fa fa-print"></span> Imprimir Ticket</a>
<a href="gest_transferencias_imp_rec.php?id=<?php echo $idtanda; ?>" class="btn btn-sm btn-default"><span class="fa fa-print"></span> Imprimir PDF</a>
</p>
<hr />

<div id="impresion_box"></div>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Idtanda</th>
			<th align="center">Origen</th>
			<th align="center">Destino</th>
			<th align="center">Fecha transferencia</th>
			<th align="center">Fecha Registrado</th>
			<th align="center">Generado por</th>
			</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td align="center"><?php echo intval($rs->fields['idtanda']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['deposito_origen']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['deposito_destino']); ?></td>
			<td align="center"><?php if ($rs->fields['fecha_transferencia'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_transferencia']));
			} ?></td>
			<td align="center"><?php if ($rs->fields['fecha_real'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_real']));
			}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['generado_por']); ?></td>
			</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />
<?php
$consulta = "
select *, 
(select descripcion from insumos_lista where idinsumo = gest_depositos_mov.idproducto) as producto,
(select motivo from  motivos_transfer_norecibe where idmotivo = gest_depositos_mov.idmotivo_dif) as motivo
from gest_depositos_mov 
where 
 idtanda = $idtanda 
 and estado  = 1
order by fechahora asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
///echo $consulta;
?>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
            <th align="center">Idproducto</th>
			<th align="center">Producto</th>
			<th align="center">Cantidad Envio</th>
			<th align="center">Cantidad Recibida</th>
			<th align="center">Diferencia</th>
			<th align="center">Motivo Diferencia</th>
		</tr>
	  </thead>
	  <tbody>
<?php
$i = 1;
while (!$rs->EOF) {
    $idserpks = $rs->fields['idserpks'];

    $cantidad_traslado = floatval($rs->fields['cantidad']);
    $cantidad_recibe = floatval($rs->fields['cantidad_recibe']);
    $diferencia = $cantidad_recibe - $cantidad_traslado;
    if ($diferencia <> 0) {
        $style = 'style="background-color:#F99; font-weight:bold;"';
    } else {
        $style = 'style="background-color:#FFF;"';
    }

    ?>
		<tr  id="fila_<?php echo $idserpks; ?>" <?php echo $style; ?>  >
			
            <td align="center"><?php echo antixss($rs->fields['idproducto']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['producto']); ?></td>
            <td align="right" id="cantenv_<?php echo $idserpks ?>"><?php echo formatomoneda($rs->fields['cantidad'], 4, 'N');  ?></td>
            <td align="right">
              <?php echo formatomoneda($rs->fields['cantidad_recibe']); ?></td>
            <td align="right" id="dif_<?php echo $idserpks ?>"><?php echo $diferencia; ?>

            </td>
            <td align="right">
            <?php echo antixss($rs->fields['motivo']); ?>
              </td>
 
		</tr>
<?php
$i++;
    $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />


<div class="clearfix"></div>
<br />

	<textarea name="texto" id="texto" style="display:none ; width:400px;" rows="30" ><?php echo $texto; ?></textarea>



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
