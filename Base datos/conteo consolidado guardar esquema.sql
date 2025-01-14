conteo consolidado guardar
pro
- es mas facil filtrar datos
contra
- no hay control de cual fue agregado por sistema
conteo consolidado no guardar
- al dar consolidar verifica si hay un conteo consolidado pendiente a guardar
si no entonces crea uno nuevo, si existe agrega los subs conteos a dicho conteo 
consolidado de esta manera se podra abrir los subs conteos que afectan a los errores 
sin abrir todos los demas
- select para ver los detalles de los conteos que tienen el id deposito tipo conteo insumo estado  id_ref del consolidado inner join detalle para el idalm
- select del deposito con ese idalm idinsumo 
- si los dos select no se machean no deja guardar hasta que reabra(se borra la entrada consolidado y se borra las referencias de cada conteo ) y agregue al conteo consolide y verifique  
-opcion de afectar o no stock

SELECT conteo_detalles.*,
(SELECT usuarios.nombres from usuarios where usuarios.idusu = conteo.iniciado_por ) as iniciado_por
from conteo_detalles
INNER JOIN conteo on conteo.idconteo = conteo_detalles.idconteo
where 
conteo.idconteo_ref = 21
and conteo.estado = 2
ORDER BY conteo_detalles.idconteo, conteo_detalles.idalm




////////////////////////////
para obtener el iddeposito el idalmacenamiento la fila la columna y el pasillo de 
el deposito grl el que esta en stock
/////////////////////////
SELECT 
gest_depositos_stock.idproducto, gest_depositos_stock_almacto.idalm,gest_depositos_stock_almacto.fila,
gest_depositos_stock.iddeposito,
gest_depositos_stock_almacto.columna,gest_depositos_stock_almacto.idpasillo
FROM gest_depositos_stock_almacto
INNER JOIN gest_deposito_almcto on gest_deposito_almcto.idalm = gest_depositos_stock_almacto.idalm
INNER JOIN gest_deposito_almcto_grl on gest_deposito_almcto_grl.idalmacto = gest_deposito_almcto.idalmacto
INNER JOIN gest_depositos_stock on gest_depositos_stock.idregseriedptostk = gest_depositos_stock_almacto.idregseriedptostk
WHERE gest_depositos_stock_almacto.disponible != 0

//// el que obtinee el detalle del conteo consolidado

SELECT conteo_detalles.*,
						(SELECT usuarios.nombres from usuarios where usuarios.idusu = conteo.iniciado_por ) as iniciado_por
						from conteo_detalles
						INNER JOIN conteo on conteo.idconteo = conteo_detalles.idconteo
						where 
						conteo.idconteo_ref = 21
						and conteo.estado = 2
						ORDER BY conteo_detalles.idconteo, conteo_detalles.idalm



para solucionar problema de no saber que productos estan en cada elementeo en 
el detalle de la consolidacion del conteo 
ser preocedera de la siguiente forma 
-cada vez que ser itera un sub conteo se verificara los almacenamientos que ese conteo 
realizo con la finaldiad de no olvidar ningun elementeo
-una vez realizado eso con todos lo sub conteos ser procedera a consultar todos los 
almacenamientos que tocaron y se los comprara con lo guardado en la db para poder verificar
que ningun almacen quedo si contar y sin revisar
- en caso que un almacenamiento no se reviso y no tiene ningun elemento se le permitira guardar
- en caso que no ser reviso y tenga algo producto no se le dejara guardar 