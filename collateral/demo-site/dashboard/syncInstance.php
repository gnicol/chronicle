<?

require_once 'instances.php';

if (!array_key_exists($_REQUEST['instance'], $instances)) {
    $body = '<font color="red">Invalid instance specified.</font><br><br><a href="/">Return to list</a>';
    require_once 'template.php';
    exit();
}

$instance = $instances[$_REQUEST['instance']];
$output = '';

#$cmd = $p4Cmd . 'info';
#exec($cmd, $cmdOutput);
#$output .= '['. $cmd ."]:\n". implode("\n", $cmdOutput) ."\n";
#unset($cmdOutput);

// sync the instance
$cmd = $p4Cmd.
    ' -d '. $instance['root'].
    ' -c '. $instance['syncClient'].
    ' sync '. $instance['syncPath'].
    ' 2>&1';
exec($cmd, $cmdOutput);
$output .= '['. $cmd ."]:\n". implode("\n", $cmdOutput) ."\n\n";
unset($cmdOutput);

$cwd = getcwd();
chdir( $instance['root'] );

$cmd = 'env P4USER=cmsuser P4CLIENT=' . $instance['syncClient']
	. ' ant -Dversion.special=DEMO info make-version-file docbook-clean get-version-properties docbook 2>&1';
exec($cmd, $cmdOutput);
$output .= '['. $cmd ."]:\n". implode("\n", $cmdOutput) ."\n\n";
unset($cmdOutput);

// inform them it worked
$body = 'Source code updated for instance: '.
    $instance['title'].
    '<br><br><a href="/">Return to list</a>'.
    '<br><br><pre>'.
    $output.
    '</pre>';

chdir( $cwd );

require_once 'template.php';
