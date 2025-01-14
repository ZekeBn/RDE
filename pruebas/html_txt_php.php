<!DOCTYPE html>
<html>
<head>
  <title>PHP Refresh</title>
  <script>
    function refreshPHP() {
      var xhr = new XMLHttpRequest();
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          document.getElementById("phpResult").innerHTML = xhr.responseText;
        }
      };
      xhr.open("GET", "log.php", true);
      xhr.send();
    }

    setInterval(refreshPHP, 1000);
  </script>
</head>
<body>
  <h1>PHP Refresh Example</h1>
  <div id="phpResult"></div>
</body>
</html>
