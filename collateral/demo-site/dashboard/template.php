<html>
    <head>
        <title>Perforce Chronicle Instances</title>
        <link type="text/css" rel="stylesheet" href="style.css" />
    </head>
    <body>
        <div id="container">
            <h1>
                <a id="logo" href="/"></a>
            </h1>
            <h1 class="white" align="center">
                Chronicle Instances (on <? print gethostname(); ?>)
            </h1>
            <h1 class="white" align="center">
                <a href="http://computer.perforce.com/newwiki/index.php?title=P4CMS/Demo_Instances/Known_Issues">-- Known Chronicle Issues --</a> 
            </h1>

            <? echo $body; ?>

        </div>

        <div id="footer">
            <a href="http://computer.perforce.com/newwiki/index.php?title=P4CMS">Chronicle Product Page</a> contains additional system details and resources.
            <hr />
            If you experience difficulty with this site please contact <a href="mailto:mwensauer@perforce.com">Marc Wensauer</a>.
            <hr />
            Machine info: <? print php_uname(); ?>
        </div>
    </body>
    <!-- ee -->
</html>
