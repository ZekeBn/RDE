<!DOCTYPE html>
<html>
<head>
  <title>Logging Page</title>
  <script>
    var interval;
    var logFileContent = "";

    function startLogging() {
      interval = setInterval(function() {
        var logMessage = "Logging message at: " + new Date();
        console.log(logMessage);
        logFileContent += logMessage + "\n";
      }, 1000);
    }

    function stopLogging() {
      clearInterval(interval);
      downloadLogFile();
    }

    function downloadLogFile() {
      var element = document.createElement('a');
      element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(logFileContent));
      element.setAttribute('download', 'log.txt');
      element.style.display = 'none';
      document.body.appendChild(element);
      element.click();
      document.body.removeChild(element);
    }

    window.addEventListener("beforeunload", function() {
      stopLogging();
    });
  </script>
</head>
<body>
  <h1>Logging Page</h1>
  <button onclick="startLogging()">Start Logging</button>
  <button onclick="stopLogging()">Stop Logging</button>
</body>
</html>
