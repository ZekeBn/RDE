<?php
if ($_SESSION['licencia'] == 2) {

    //echo 'DEMO' ;

}
require_once("conexion.php");
require_once("funciones.php");
require_once('rsusuario.php');
//Cantidad de modulos ppales
$buscar = "
Select idmodulo,descripcion,modulo as nombre from modulo 
where
idmodulo
		 in 
		 (
		 select distinct idmodulo from modulo_empresa 
		 where 
		 idempresa=$idempresa 
		 and estado=1 
		 and idmodulo in (select distinct idmodulo from modulo_usuario where idusu=$idusu and modulo_usuario.submodulo <> 2)
		 ) 
order by nombre asc";
//echo $buscar;
$rsm = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


?>

<!-- <link rel="stylesheet" href="./css/resetms.css">--> <!-- CSS reset -->
<link rel="stylesheet" href="../css/stylems.css"> <!-- Resource style -->
	<header>
		<div align="center" style="border: 0px solid #000000; height: 80px;">
			<div style="width: 980px; border: 0px solid #000000;">
				<div class="cablg1">
			    <img src="<?php echo $img; ?>" height="76" alt=""/>
			    </div>
				<div  class="cablg2"><br />
					<strong><span style="font-weight:50px;"><?php echo strtoupper($nombresucursal)?></span><br /><?php echo strtoupper($cajero); ?></strong><?php
if (trim($mensajelic_rs) != '') {
    echo "<br /><span style=\"color:#FF0000;\">$mensajelic_rs<span> <a href='lic.php'>[Mas]</a>";
}
?>
				</div><!--<div style="position:absolute; top:50px; float:inherit; border:1px solid #000; width:500px; height:100px;">test</div>-->
				<div class="cablg3"><img src="<?php echo $rsco->fields['logo_sys']; ?>" height="80" alt=""/></div>
			</div>
		</div>
		<nav id="cd-top-nav">
		<div align="center">
		
			</div>
		</nav>
		<a id="cd-menu-trigger" href="#0"><span class="cd-menu-text">MENU</span><span class="cd-menu-icon"></span></a>
	</header>
	<nav id="cd-lateral-nav">
        <ul class="cd-navigation">
        	<li>
				<a href="index.php">INICIO</a>
			</li> 
		</ul> 
<?php
while (!$rsm->EOF) {
    $elemento = capitalizar((utf8_decode($rsm->fields['nombre'])));

    $elementodes = utf8_decode($rsm->fields['descripcion']);
    $idmodppal = intval($rsm->fields['idmodulo']);

    //Traemos los scripts de las paginas pertenecientes a los sub modulos de acuerdo al mod ppal
    $buscar = "Select nombresub,descripcion,pagina,idsubmod from modulo_detalle
	where idsubmod in(select idsubmod from modulo_usuario where estado=1 
	and idusu=$idusu and idempresa=$idempresa) and estado=1 and mostrar > 0 
	and idmodulo=$idmodppal order by nombresub asc";

    $buscar = "Select nombresub,descripcion,pagina,idsubmod,
	registrado_el from modulo_detalle inner join modulo_usuario
	on modulo_usuario.submodulo=modulo_detalle.idsubmod
	where modulo_usuario.idempresa=$idempresa 
	and modulo_usuario.idmodulo=$idmodppal
	and modulo_usuario.submodulo <> 2
	and modulo_usuario.estado=1 and  modulo_detalle.mostrar > 0 and modulo_usuario.idusu=$idusu
	order by nombresub asc";

    $rss = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $paso = 0;



    ?>
	
		<ul class="cd-navigation">
			<li class="item-has-children">
				<a href="#0"><?php echo($elemento); ?></a>
				<ul class="sub-menu">
				 <?php
                        while (!$rss->EOF) {
                            $pagina = str_replace("'", "", $rss->fields['pagina']);
                            $subelemento = (trim($rss->fields['nombresub']));
                            $bb = $rss->fields['idsubmod'];
                            ?>
					<li><a href="<?php echo $pagina ?>"><?php echo  ($subelemento) ?></a></li>
					<?php $rss->MoveNext();
                        } ?>
				</ul>
			</li> <!-- item-has-children -->

			
		</ul> <!-- cd-navigation -->
<?php $rsm->MoveNext();
} ?>
		

		<div class="cd-navigation socials">
			<a href="ayuda.php?md=<?php echo $modulo?>&sm=<?php echo $submodulo?>"><img src="../img/ayudante_mini.gif" width="32" height="32" title="Qu&eacute; hago&#63;" /></a>
			<a href="preferencias.php"><img src="../img/set01.png" width="32" height="32" title="Accesos" /></a>
			
			<a class="cd-facebook cd-img-replace" href="https://www.facebook.com/ekarusoft/" target="_blank" title="Vis&iacute;tenos">Facebook</a>
			<a href="logout.php"><img src="../img/salir.png" width="32" height="32" title="Salir del Sistema" /></a>
		</div> <!-- socials -->
	</nav>
	

<script src="../js/mainms.js"></script> <!-- Resource jQuery -->






