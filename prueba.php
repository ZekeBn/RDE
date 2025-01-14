<!DOCTYPE html>
<html>
<head>
    <title>Carrusel de Páginas</title>
    <style>
        .page {
            display: none;
        }
    </style>
</head>
<body>
    <?php
    // Array de páginas
    $pages = ["Página 1", "Página 2", "Página 3", "Página 4", "Página 5"];

    // Obtener la página actual desde la URL
    $currentPage = isset($_GET['page']) ? $_GET['page'] : 1;

    // Validar la página actual
    if ($currentPage < 1 || $currentPage > count($pages)) {
        $currentPage = 1;
    }

    // Mostrar la página actual
    echo '<div class="page" id="page' . $currentPage . '">' . $pages[$currentPage - 1] . '</div>';
    ?>

    <label for="pageSelector">Selecciona una página:</label>
    <select id="pageSelector" onchange="selectPage(this.value)">
        <?php
        // Generar las opciones del selector
        for ($i = 1; $i <= count($pages); $i++) {
            echo '<option value="' . $i . '"' . ($i == $currentPage ? ' selected' : '') . '>Página ' . $i . '</option>';
        }
    ?>
    </select>

    <script>
        function showPage(pageNumber) {
            // Ocultar todas las páginas
            var pages = document.getElementsByClassName("page");
            for (var i = 0; i < pages.length; i++) {
                pages[i].style.display = "none";
            }

            // Mostrar la página deseada
            var page = document.getElementById("page" + pageNumber);
            if (page) {
                page.style.display = "block";
            }
        }

        function selectPage(pageNumber) {
            showPage(pageNumber);
        }

        // Mostrar la página actual al cargar la página
        showPage(<?php echo $currentPage; ?>);
    </script>
</body>
</html>
