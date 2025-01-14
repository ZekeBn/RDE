<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
$pag = "index";
require_once("includes/rsusuario.php");

// crea imagen
$img = "gfx/empresas/emp_".$id_empresa.".png";
if (!file_exists($img)) {
    $img = "gfx/empresas/emp_0.png";
}

// fondo y linea por defecto
if ($fondo == '') {
    $fondo = "#FFFFFF";
}
if ($linea == '') {
    $linea = "#000000";
}


// si la pc esta asignada a una sucursal actualiza el usuario
/*if(isset($_COOKIE['csucursal']) && trim($_COOKIE['csucursal']) != ''){
    $csucursal=antisqlinyeccion($_COOKIE['csucursal'],'int');
    //echo $csucursal;
    $buscar="select * from sucursales where idempresa=$idempresa and idsucu = $csucursal order by nombre asc";
    $rspc=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    $sucursal_activa=strtoupper(trim($rspc->fields['nombre']));
    $idsucuact=$rspc->fields['idsucu'];

    // si la sucursal actual es diferente de la asignada a la pc
    if($idsucursal != $idsucuact){

        if($idsucuact > 0){

            // asignar a usuario la sucursal
            $consulta="
            UPDATE usuarios
            SET
                sucursal=$idsucuact
            WHERE
                idempresa=$idempresa
                and idusu=$idusu
                and estado=1
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

            // tambien en la variable de sesion
            $_SESSION['idsucursal'] = $idsucuact;

            // registra cambio
            $consulta="
            INSERT INTO asignasucu_auto
            (idusu, idsucursal_ant, idsucursal_asig, fechahora,idempresa)
            VALUES
            ($idusu,$idsucursal,$idsucuact,'$ahora',$idempresa)
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

            header("location: index.php?s=ok");
            exit;

        }

    }

}*/
// busca los parametros locales y actualiza usuario si son diferentes
$csucursal = antisqlinyeccion($_COOKIE['csucursal'], 'int');
$cfactura_suc = antisqlinyeccion($_COOKIE['cfactura_suc'], 'int');
$cfactura_pexp = antisqlinyeccion($_COOKIE['cfactura_pexp'], 'int');

if ($csucursal > 0) {

    $diferente = "N";

    // conversioens
    if (intval($cfactura_suc) == 0) {
        $cfactura_suc = 1;
    }
    if (intval($cfactura_pexp) == 0) {
        $cfactura_pexp = 1;
    }

    // busca si son diferentes al del usuario
    if ($idsucursal != $csucursal) {
        $diferente = "S";
    }
    if ($factura_suc != $cfactura_suc) {
        $diferente = "S";
    }
    if ($factura_pexp != $cfactura_pexp) {
        $diferente = "S";
    }

    if ($diferente == 'S') {
        // asignar a usuario la sucursal
        $consulta = "
		UPDATE usuarios 
		SET
			sucursal=$csucursal,
			factura_suc = $cfactura_suc,
			factura_pexp = $cfactura_pexp
		WHERE
			idempresa=$idempresa
			and idusu=$idusu
			and estado=1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // tambien en la variable de sesion
        $_SESSION['idsucursal'] = $csucursal;


        // para el log
        $idsucursal_ant = $idsucursal;
        $idsucursal_asig = $csucursal;
        $factura_suc_ant = $factura_suc;
        $factura_suc_asig = $cfactura_suc;
        $factura_pexp_ant = $factura_pexp;
        $factura_pexp_asig = $cfactura_pexp;


        // registra cambio
        $consulta = "
		INSERT INTO asignasucu_auto
		(idusu, idsucursal_ant, idsucursal_asig, fechahora,idempresa, factura_suc_ant, factura_suc_asig, factura_pexp_ant, factura_pexp_asig) 
		VALUES 
		($idusu,$idsucursal_ant,$idsucursal_asig,'$ahora',$idempresa, $factura_suc_ant, $factura_suc_asig, $factura_pexp_ant, $factura_pexp_asig)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // asigna cookie
        setcookie("csucursal", intval($idsucursal_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años
        setcookie("cfactura_suc", intval($factura_suc_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años
        setcookie("cfactura_pexp", intval($factura_pexp_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años

        header("location: index.php?s=ok");
        exit;

    }
}


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
			<?php require_once("includes/lic_gen.php");?>
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2><?php if (date('d/m') == '25/08') { ?><a href="25_agosto.php"><span style="color:#F00;" title="25 de Agosto Día del Idioma Guaraní">Ta peguah&ecirc; porait&ecirc;</span> <?php echo htmlentities($rsco->fields['nombre_sys']); ?></a><?php } else { ?>Bienvenido a <?php echo htmlentities($rsco->fields['nombre_sys']); ?><?php } ?></h2>
       
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<?php if (date('d/m') == '25/08') { ?>
<strong>¡¡¡ FELIZ DIA DEL IDIOMA GUARANI  !!!</strong>  <a href="
25_agosto.php" class="btn btn-sm btn-default" title="Dia del Idioma Guarani" data-toggle="tooltip" data-placement="right"  data-original-title="Dia del Idioma Guarani"><span class="fa fa-search"></span></a>
<?php } ?>
<?php
/*
$consulta="
select *,
(select usuario from usuarios where log_regimen.registrado_por = usuarios.idusu) as registrado_por
from log_regimen
order by idlog asc
limit 1
";
$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
$idlog=intval($rs->fields['idlog']);
if($idlog == 0){
?>
<div class="alert alert-info alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
IMPORTANTE! si tu empresa esta registrada en la SET con alguno de los codigos de actividad que figuran en el DECRETO Nro 3881.
puedes contactar con el Soporte para solicitar ayuda en la actualizacion los parametros de tu sistema al nuevo regimen especial que beneficia a:
 Hoteles, Restaurantes, Abastecimiento de Eventos, Paquetes Turisticos, Arrendamiento de Inmuebles etc.

<button class="btn btn-sm btn-default" type="button"
onmouseup="document.location.href='decreto_3881.php'"><span class="fa fa-search"></span> Mas info</button>
</div>
<?php }*/ ?>
<p align="center"><img src="<?php echo $img ?>" height="200" style="margin:5px;"  alt="<?php echo $nombreempresa ?>" title="<?php echo $nombreempresa ?>" /></p><br /><br /><br />
<p align="center" style="font-weight:bold; font-size:16px;"><?php echo $nombreempresa ?></p>

<p align="center" style="font-weight:bold; font-size:16px;">Sucursal Activa:</p>
<p align="center" style="font-weight:bold; font-size:24px; color:#FF0000;"><?php echo strtoupper($nombresucursal)?></p>

<hr />
<p align="center"><a href="<?php echo $rsco->fields['web_sys']; ?>" target="_blank"><img src="<?php echo $rsco->fields['logo_sys_indnew']; ?>" class="img-thumbnail" alt="<?php echo $rsco->fields['nombre_sys'] ?>"/></a></p>
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
