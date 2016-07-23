<?

require_once 'instances.php';

// temp hack; hides the main instance so no-one messes with it
array_pop($instances);

// touch up instances with the last change number/date
foreach ($instances as $id => $instance) {
    $lastChange = explode(' ', exec(
                'p4 -p '. $sync['p4port'].
                  ' -u '. $sync['p4user'].
                  ' -d '. $instance['root'].
                  ' -c '. $instance['syncClient'].
                  ' changes -m1 @'.$instance['syncClient']
            )
    );

    if (count($lastChange) < 4) {
        $instances[$id]['changeDate'] = 'never';
        continue;
    }

    $instances[$id]['changeNo']   = $lastChange[1];
    $instances[$id]['changeDate'] = date('F j, Y', strtotime($lastChange[3]));
}

// render the home page body (populate 'body' value)
ob_start();
require_once 'template.body.p4cms.php';
$body = ob_get_clean();

ob_start();
require_once 'template.body.other.php';
$body .= ob_get_clean();

// render out the main page wrapper
require_once 'template.php';
