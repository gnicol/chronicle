<?

require_once 'instances.php';

if (!array_key_exists($_REQUEST['instance'], $instances)) {
    $body  = '<font color="red">Invalid instance specified.</font><br><br><a href="/">Return to list</a>';
    require_once 'template.php';
    exit();
}

$instance = $instances[$_REQUEST['instance']];
$output = '';

$myP4Cmd = 'p4 -p '. $instance['p4port'] . ' -u '. $instance['p4user'] .' ';

// stop the server
$cmd = $myP4Cmd .'admin stop 2>&1';
exec($cmd, $cmdOutput);
$output .= '['. $cmd ."]:\n". implode("\n", $cmdOutput) . "\n\n";
unset($cmdOutput);

// blow away the folder
exec('rm -rf '. $instance['p4root'].'/db.*');
exec('rm -rf '. $instance['p4root'].'/depot/*');
exec('rm -rf '. $instance['p4root'].'/journal');
exec('rm -rf '. $instance['p4root'].'/log');

// restart server
$cmd = '/usr/local/bin/p4d'.
    ' -d'.
    ' -p '. $instance['p4port'].
    ' -r '. $instance['p4root'].
    ' -L '. $instance['p4root'] .'/log'.
    ' > /dev/null 2>&1 < /dev/null';
exec($cmd, $cmdOutput);
$output .= '['. $cmd ."]:\n". implode("\n", $cmdOutput) . "\n\n";
unset($cmdOutput);

// create the p4cms user
$cmd = $myP4Cmd. 'user -o | sed -e "s,^\(FullName:\).*,\1 Chronicle Admin," | '. $myP4Cmd .'user -i 2>&1';
$cmdOutput = shell_exec($cmd);
$output .= '['. $cmd ."]:\n". $cmdOutput . "\n\n";

// remove all old files
exec('rm -rf '. $instance['root'] .'/data/*');

// inform them it worked
$body = 'Perforce Depot reset for instance: '.
    $instance['title'].
    '<br/><br/><a href="/">Return to list</a>'.
    '<br/><br/><pre>'.
    $output.
    '</pre>';

require_once 'template.php';
