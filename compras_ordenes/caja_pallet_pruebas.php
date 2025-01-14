<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caja Pallet</title>
</head>
<body>
    <script>
	function cargarMedida2Edit(value,limpiar){
		var medida2_input = document.querySelector('#form1Edit #bulto');
		var cant_medida = <?php echo $cant_medida2 ? $cant_medida2 : 1;  ?>;
        console_log(cant_medida);
		$("#cantidad").val(value*cant_medida)
		if(limpiar == true){
			$('#form1Edit #bulto_edi').val(0);
		}

		var medida3_input = $("#form1Edit #pallet");
		var cant_medida3 = <?php echo $cant_medida3 ? $cant_medida3 : 1; ?>;

	
		if(  cant_medida3 != undefined && value%cant_medida3 > 0 ){
			var result = (value / cant_medida3 * 100).toFixed(0) / 100;
			medida3_input.val(result);
		}
	}

    </script>
<?php $cant_medida2 = 1;
		$cant_medida3 = 1;
		?>
    <form id="form1Edit" name="form1Edit" method="post" action="">
<!-- MEDIDAS 2  -->
<div class="form-group col-md-6 col-xs-12">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">
        <a id="caja_plus" href="javascript:void(0);" style="display:none;" class="btn btn-sm btn-default">
            <span class="fa fa-plus"></span>
        </a>
        <?php if ($medida2) { ?>
            <div id="medida2"><?php echo $medida2; ?>:</div>
        <?php } else { ?>
            <div id="medida2">Medida2:</div>
        <?php } ?>
    </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <?php if ($cant_medida2 > 0) { ?>
        <input class="form-control" onchange="cargarMedida2Edit(this.value,true)" aria-describedby="cajaHelp" type="text" name="bulto" id="bulto" value="<?php echo $cantidad_medida2_inicial;?>" size="10" />    
    <?php } else { ?>
        <input disabled class="form-control" onchange="cargarMedida2Edit(this.value,true)" aria-describedby="cajaHelp" type="text" name="bulto" id="bulto" value="0" size="10" />    
    <?php } ?>
        <small id="cajaHelp" style="display:none;" class="form-text text-muted">Sin <strong class="medida2_nombre">Medida2</strong> asignadas, favor agregar en insumos.</small>
    </div>
</div>

<!-- MEDIDAS INICIO 3 -->
<div class="form-group col-md-6 col-xs-12">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">
        <a id="pallet_plus" href="javascript:void(0);" style="display:none;" class="btn btn-sm btn-default">
            <span class="fa fa-plus"></span>
        </a>
        <?php if ($medida3) { ?>
            <div id="medida3"><?php echo $medida3; ?>:</div>
        <?php } else { ?>
            <div id="medida3">Medida3:</div>
        <?php } ?>
    </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <?php if ($cant_medida3 > 0) { ?>
        <input aria-describedby="palletHelp" onchange="cargarMedida3Edit(this.value)" type="text" class="form-control" name="pallet" id="pallet" value="<?php echo $cantidad_medida3_inicial;?>" size="10" disabled />
    <?php } else { ?>
        <input disabled aria-describedby="palletHelp" onchange="cargarMedida3Edit(this.value)" type="text" class="form-control" name="pallet" id="pallet" value="0" size="10" />
    <?php } ?>
        <span style="color: red; font-weight: bold; font-family: 'Arial', sans-serif;">Valor Referencial</span>
        <small id="palletHelp" style="display:none;" class="form-text text-muted">Sin <strong class="medida2_nombre">Medida3</strong> asignadas, favor agregar en insumos.</small>
    </div>
</div>

    </form>
</body>
</html>