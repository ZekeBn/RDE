<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modal con Input</title>
</head>
<body>

<div class="container mt-5">
    <h2>Motivo No Compra</h2>
    <!-- Botón para abrir el modal -->
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#myModal">
        Motivo No Compra
    </button>

    <!-- Modal -->
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myModalLabel">Motivo:</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="textForm">
                        <div class="form-group">
                            <label for="inputText">Ingrese el motivo:</label>
                            <input type="text" class="form-control" id="inputText" placeholder="Escribe algo aquí...">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="submitForm()">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function submitForm() {
        const text = document.getElementById('inputText').value;
        alert('Texto enviado: ' + text);
        $('#myModal').modal('hide');
    }
</script>
</body>
</html>
