<html>
  <head>
    <title>Title</title>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <script type="text/javascript">
      function insertText(elemID, text)
      {
        var elem = document.getElementById(elemID);
        elem.innerHTML += text;
      }
    </script>
  </head>
  <body>
    <form>
      <textarea id="txt1"></textarea>
      <input type="button" value="Insert some text" onclick="insertText('txt1', 'Hello');">
    </form>
  </body>
</html>
