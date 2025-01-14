<?php
if ($dirsup == "S") {
    $dirini = '../';
} elseif ($dirsup_sec == "S") {
    $dirini = '../../';
} else {
    $dirini = '';
}
?>
	<!-- jQuery -->
    <script src="<?php echo $dirini; ?>vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="<?php echo $dirini; ?>vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- FastClick -->
    <script src="<?php echo $dirini; ?>vendors/fastclick/lib/fastclick.js"></script>
    <!-- NProgress -->
    <script src="<?php echo $dirini; ?>vendors/nprogress/nprogress.js"></script>
    <!-- iCheck -->
    <script src="<?php echo $dirini; ?>vendors/iCheck/icheck.min.js"></script>
    <!-- bootstrap-daterangepicker -->
    <script src="<?php echo $dirini; ?>vendors/moment/min/moment.min.js"></script>
    <script src="<?php echo $dirini; ?>vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
    <!-- Ion.RangeSlider -->
    <script src="<?php echo $dirini; ?>vendors/ion.rangeSlider/js/ion.rangeSlider.min.js"></script>
    <!-- Bootstrap Colorpicker -->
    <script src="<?php echo $dirini; ?>vendors/mjolnic-bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min.js"></script>
    <!-- jquery.inputmask -->
    <script src="<?php echo $dirini; ?>vendors/jquery.inputmask/dist/min/jquery.inputmask.bundle.min.js"></script>
    <!-- jQuery Knob -->
    <script src="<?php echo $dirini; ?>vendors/jquery-knob/dist/jquery.knob.min.js"></script>
    <!-- Cropper -->
    <script src="<?php echo $dirini; ?>vendors/cropper/dist/cropper.min.js"></script>
    <!-- jQuery doubleScroll -->
    <script src="<?php echo $dirini; ?>vendors/jquery.doubleScroll/jquery.doubleScroll.js"></script>
    <!-- PNotify -->
    <script src="<?php echo $dirini; ?>vendors/pnotify/dist/pnotify.js"></script>
    <script src="<?php echo $dirini; ?>vendors/pnotify/dist/pnotify.buttons.js"></script>
    <script src="<?php echo $dirini; ?>vendors/pnotify/dist/pnotify.nonblock.js"></script>

    <!-- Custom Theme Scripts -->
    <script src="<?php echo $dirini; ?>build/js/custom.js"></script>
    <!-- Nuestros JS -->
    <script src="<?php echo $dirini; ?>js/nuestrosys.js?20201017190100"></script>
<?php if ($doubleScroll != 'N') { ?>
<script>
$(document).ready(function(){
	setInterval(function(){ mantiene_session(); }, 1200000); // 20min
	
	$('.table-responsive').doubleScroll();
});
</script>
<?php } ?>
    <!-- Chart.js -->
    <script src="<?php echo $dirini; ?>vendors/Chart.js/dist/Chart.min.js"></script>
<!-- notificaciones de escritorio -->
<script>
/*$( document ).ready(function() {
  pedir_permiso('<?php echo $tienenoti; ?>');
});*/
function cierra_sesion(){
	if(!(typeof ApiChannel === 'undefined')){
		ApiChannel.postMessage('<?php
        //parametros para la funcion
        $parametros_array_tk = [
            'metodo' => 'logout'
        ];
echo texto_para_app($parametros_array_tk);

?>');
	}else{
		document.location.href='logout.php';	
	}
}
</script>
<!-- notificaciones de escritorio -->
    
    <a class="btn btn-primary" id="download" href="javascript:void(0);" style="display:none;" >Download</a>
