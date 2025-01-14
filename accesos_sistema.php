 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "16";
$submodulo = "76";
require_once("includes/rsusuario.php");

$modulomaster = "N";
if (($idusu == 2 or $idusu == 3) && $idempresa == 1 && $superus == 'S') {
    $modulomaster = "S";
}
if ($modulomaster != "S") {
    $whereadd .= "    
    and usuarios_accesos.idusuario <> 2  
    and usuarios_accesos.idusuario <> 3
    and (SELECT soporte from usuarios where idusu = usuarios_accesos.idusuario) <> 1
    ";
}

$id = intval($_GET['id']);
if ($id > 0) {
    $whereadd .= " and usuarios_accesos.idusuario = $id ";
}

if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-").'01';
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}


$consulta = "
select *, (select usuario from usuarios where idusu = usuarios_accesos.idusuario)  as usuario
from usuarios_accesos 
where
idusuario is not null
$whereadd
and date(fechahora) >= '$desde'
and date(fechahora) <= '$hasta'
order by fechahora desc
limit 200
";
$rsac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


function getOS($user_agent)
{

    //global $user_agent;

    $os_platform = "Desconocido";

    $os_array = [
                          '/windows nt 10/i' => 'Windows 10',
                          '/windows nt 6.3/i' => 'Windows 8.1',
                          '/windows nt 6.2/i' => 'Windows 8',
                          '/windows nt 6.1/i' => 'Windows 7',
                          '/windows nt 6.0/i' => 'Windows Vista',
                          '/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
                          '/windows nt 5.1/i' => 'Windows XP',
                          '/windows xp/i' => 'Windows XP',
                          '/windows nt 5.0/i' => 'Windows 2000',
                          '/windows me/i' => 'Windows ME',
                          '/win98/i' => 'Windows 98',
                          '/win95/i' => 'Windows 95',
                          '/win16/i' => 'Windows 3.11',
                          '/macintosh|mac os x/i' => 'Mac OS X',
                          '/mac_powerpc/i' => 'Mac OS 9',
                          '/linux/i' => 'Linux',
                          '/ubuntu/i' => 'Ubuntu',
                          '/iphone/i' => 'iPhone',
                          '/ipod/i' => 'iPod',
                          '/ipad/i' => 'iPad',
                          '/android/i' => 'Android',
                          '/blackberry/i' => 'BlackBerry',
                          '/webos/i' => 'Mobile'
                    ];

    foreach ($os_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $os_platform = $value;
        }
    }

    return $os_platform;
}

function getBrowser($user_agent)
{



    $browser = "Desconocido";

    $browser_array = [
                            '/msie/i' => 'Internet Explorer',
                            '/firefox/i' => 'Firefox',
                            '/safari/i' => 'Safari',
                            '/chrome/i' => 'Chrome',
                            '/edge/i' => 'Edge',
                            '/opera/i' => 'Opera',
                            '/netscape/i' => 'Netscape',
                            '/maxthon/i' => 'Maxthon',
                            '/konqueror/i' => 'Konqueror',
                            '/mobile/i' => 'Dispositivo Movil'
                     ];

    foreach ($browser_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $browser = $value;
        }
    }

    return $browser;
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
                    <h2>Accesos al Sistema</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">




<p><a href="usuarios.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />


<form id="form1" name="form1" method="get" action="">

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Desde *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="desde" id="desde" value="<?php  echo $desde; ?>" placeholder="Desde" class="form-control" required />                    

    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Hasta *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="hasta" id="hasta" value="<?php echo $hasta; ?>" placeholder="Hasta" class="form-control" required />                    

    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12"> Usuario * </label>
    <div class="col-md-9 col-sm-9 col-xs-12" id="categoriabox">
<?php
// consulta
$consulta = "
SELECT idusu, usuario
FROM usuarios
where
estado = 1
order by usuario asc
 ";

// valor seleccionado
if (isset($_GET['id'])) {
    $value_selected = htmlentities($_GET['id']);
} else {
    //$value_selected=htmlentities($rs->fields['idusu']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'id',
    'id_campo' => 'id',

    'nombre_campo_bd' => 'usuario',
    'id_campo_bd' => 'idusu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
    </div>
</div>


<div class="clearfix"></div>
<br />


    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Filtrar</button>

        </div>
    </div>


<br />
</form>        
<div class="clearfix"></div>
  <hr /><br /> 

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
          <thead>
            <tr>
              <th>Usuario</th>
              <th>Fecha Hora</th>
              <th>OS</th>
              <th>Navegador</th>
              <th>IP</th>
              <th>Host</th>
              <th>Agente</th>
              <th>Coordenadas GPS</th>
            </tr>
            </thead>
            <tbody>
           <?php  while (!$rsac->EOF) {
               $user_agent = $rsac->fields['agente'];
               //$datos_agente=obtenerNavegadorWeb($user_agent);

               ?>
            <tr>
              <td align="center"><a href="accesos_sistema.php?id=<?php echo $rsac->fields['idusuario']?>"><?php echo $rsac->fields['usuario']?></a></td>
              <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rsac->fields['fechahora'])); ?></td>
              <td width="10" align="center"><?php echo getOS($user_agent);?></td>
              <td width="10" align="center"><?php echo getBrowser($user_agent);?></td>
              <td align="center"><?php echo $rsac->fields['ip_real']; ?></td>
              <td align="center"><?php echo $rsac->fields['hostname']; ?></td>
              <td width="10" align="center"><textarea name="textarea" id="textarea" cols="45" rows="3"><?php echo $rsac->fields['agente']; ?></textarea></td>
                    <td align="center">
              <?php if (floatval($rsac->fields['latitud']) != 0 or floatval($rsac->fields['longitud']) != 0) { ?>
              <a href='https://www.google.com/maps?q=<?php echo $rsac->fields['latitud']; ?>,<?php echo $rsac->fields['longitud']; ?>' target='_blank'>
              Latitud: <?php echo $rsac->fields['latitud']; ?><br />
              Longitud: <?php echo $rsac->fields['longitud']; ?>
              </a>
              <?php } ?>
              </td>
            </tr>
           <?php  $rsac->MoveNext();
           }  ?> 
          </tbody>
      </table>
</div>
      
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
