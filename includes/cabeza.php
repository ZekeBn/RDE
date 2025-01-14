<div class="cabeza" style="height:80px; ">
	<div style="float:left; width:10%;">
	<?php
        // crea imagen
        $img = "gfx/empresas/emp_".$idempresa.".png";
	if (!file_exists($img)) {
	    $img = "gfx/empresas/emp_0.png";
	}
	?>
	<!--<img src="<?php echo $img; ?>" height="100" style="float:left;margin-left:20px;" />
-->
	</div>
	<div style="float:left; width:70%; margin-left:20px;">
	  <?php if ($pag != 'login') {
	      if (file_exists("includes/menuarriba_cab.php")) {
	          require_once("includes/menuarriba_cab.php");
	      } else {
	          require_once("../includes/menuarriba_cab.php");
	      }

	  } ?>
	</div>
	<div style="float:left; width:10%; margin-left:10px;">
		<!--<img src="img/logoemp.png" height="100" style="float:left;" />-->

	</div>
    <!-- Si no hay login -->
</div>
 
