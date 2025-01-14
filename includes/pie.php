<!--<div class="pie"><?php// echo $nombre_sys ?> - <?php //echo $telefono_sys?><br /><?php //if($empresa!=''){echo "<span style='font-weight:bold;color:#F00;'>Empresa: ".$empresa."</span>"; }?></div> -->
<?php //if ($idempresa != ''){?>
<!--
<div class="logobot"> <div class="piecent"></div>
<div class="pieder"></div> <div class="pieizq"></div> <div class="pieder"></div>
<img src="<?php // echo $logo;?>"  />
</div> -->
<?php //}?>
<div class="divpiecito" align="center">
    <div class="piecentv2">
        <div align="center">
        <a href="soporte.php">[Soporte Tecnico]</a> - <strong>Copyright &reg; <?php echo date("Y") ?></strong>
        </div><br />
    </div>
</div>
<script src="js/mantienesession.js?20201017190100"></script>
<script>
$(document).ready(function(){
	setInterval(function(){ mantiene_session(); }, 1200000); // 20min
	//setInterval(function(){ mantiene_session(); }, 10000); // 20min
});
</script>
<?php
// cierra las conexiones
$conexion->Close();
?>