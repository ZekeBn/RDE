<!-- JAVASCRIPTS -->
<script src="js/arandu.js" type="text/javascript"></script> 
<script type="text/javascript" src="js/jquery.min.js"></script>
<script src="js/jquery-1.9.1.js"></script>
<script src="js/jquery-ui.js"></script>
<script type="text/javascript" src="js/modernizr.js"></script>
<!-- JAVASCRIPTS -->


<!-- CSS -->
<link rel="stylesheet" href="css/arandu.css" type="text/css" media="screen" /> 
<link rel="stylesheet" href="css/jquery-ui.css">
<!-- CSS -->


<!-- Favicon -->
<link rel="shortcut icon" href="favicon.ico" >
<!-- Favicon -->



<!-- calendario html5 -->


<script>
if (!Modernizr.inputtypes.date) {
	$(function() {
		$('input[type=date]').datepicker({dateFormat: 'yy-mm-dd'});
	});
}

$(function() {
    
 //Array para dar formato en español
  $.datepicker.regional['es'] = 
  {
  closeText: 'Cerrar', 
  prevText: 'Previo', 
  nextText: 'Próximo',
  
  monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
  'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
  monthNamesShort: ['Ene','Feb','Mar','Abr','May','Jun',
  'Jul','Ago','Sep','Oct','Nov','Dic'],
  monthStatus: 'Ver otro mes', yearStatus: 'Ver otro año',
  dayNames: ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'],
  dayNamesShort: ['Dom','Lun','Mar','Mie','Jue','Vie','Sáb'],
  dayNamesMin: ['Do','Lu','Ma','Mi','Ju','Vi','Sa'],
  //dateFormat: 'dd/mm/yy', firstDay: 0, 
  changeYear: true,
  initStatus: 'Selecciona la fecha', isRTL: false};
 $.datepicker.setDefaults($.datepicker.regional['es']);
 
  }); 
</script>
<!-- calendario html5 -->
<!-- calendario -->
<script src="js/calend.js" type="text/javascript"></script>
<link rel="stylesheet" href="css/calend.css" type="text/css" media="all" />
<!-- calendario -->
<!-- RELOJ -->
<script language="JavaScript" type="text/javascript"> 
<!--
var horaserver = '<?php echo intval(date("H")); ?>'; 
var minutoserver = '<?php echo intval(date("i")); ?>'; 
var segundoserver = '<?php echo intval(date("s")); ?>';  
var hora = 'a';
var minuto = 'a';
var segundo = 'a';
-->
</script>
<!-- RELOJ -->
<!-- Toltip -->
<script>
$(function() {
	var tooltips = $( "[title]" ).tooltip({
		position: {
			my: "left top",
			at: "right+5 top-5"
		}
	});
});

function solonumeros(e)
        {
       		 var keynum = window.event ? window.event.keyCode : e.which;
        	 if ((keynum == 8) || (keynum == 46))
        	 return true;
         
        	return /\d/.test(String.fromCharCode(keynum));
        }
function sololetras(e)
	
        {
       		 var keynum = window.event ? window.event.keyCode : e.which;
			
        	 if ((keynum == 8) || (keynum == 46))
        	 return true;
        // CON ESPACIOS
        	return /\D/.test(String.fromCharCode(keynum));
        }
function solonumerosypuntoycoma(e)
        {
       		 var keynum = window.event ? window.event.keyCode : e.which;
        	 if ((keynum == 8) || (keynum == 46) || (keynum == 190) || (keynum == 110) || (keynum == 188))
        	 return true;
         
        	return /\d/.test(String.fromCharCode(keynum));
        }

function coma_por_punto(id) {
	var txt = $("#"+id).val();
	var res = txt.replace(',','.');
	$("#"+id).val(res);
}
</script>

<!-- Toltip -->