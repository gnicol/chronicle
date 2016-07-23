<?

$demoBaseHost		= 'cms.perforce.com';
$demoBaseDir		= '/home/p4admin/demo';
$demoDocRootBaseDir	= $demoBaseDir . '/p4cms-sites';
$demoServerBaseDir	= $demoBaseDir . '/p4cms-servers';
$demoClientBaseName	= 'cmsuser-cms-site-';

$sync['p4port']      	= 'perforce.perforce.com:1666';
$sync['p4user']      	= 'cmsuser';
$sync['defaultPath'] 	= '//...@p4cms-main-cc-1-smoke.build-passed';

$p4Cmd = 'p4 -p '. $sync['p4port'] .' -u '. $sync['p4user'] .' ';

$defaultInstanceUser = 'p4cms';
$instances = array();

$instances[] = array(
    'title'      => 'MAIN - General Demo',
    'url'        => 'http://demo.'. $demoBaseHost,
    'root'       => $demoDocRootBaseDir . '/demo',
    'p4root'     => $demoServerBaseDir . '/demo',
    'p4port'     => '1111',
    'p4user'     => $defaultInstanceUser,
    'syncClient' => $demoClientBaseName . 'demo',
    'syncPath'   => $sync['defaultPath'],
    'allowReset' => true,
);

$instances[] = array(
    'title'      => 'MAIN - UI Demo',
    'url'        => 'http://ui.'. $demoBaseHost,
    'root'       => $demoDocRootBaseDir . '/ui',
    'p4root'     => $demoServerBaseDir . '/ui',
    'p4port'     => '2222',
    'p4user'     => $defaultInstanceUser,
    'syncClient' => $demoClientBaseName . 'ui',
    'syncPath'   => $sync['defaultPath'],
    'allowReset' => true,
);

$instances[] = array(
    'title'      => 'MAIN - Docs Demo',
    'url'        => 'http://docs.'. $demoBaseHost,
    'root'       => $demoDocRootBaseDir . '/docs',
    'p4root'     => $demoServerBaseDir . '/docs',
    'p4port'     => '3333',
    'p4user'     => $defaultInstanceUser,
    'syncClient' => $demoClientBaseName . 'docs',
    'syncPath'   => $sync['defaultPath'],
    'allowReset' => true,
);

$instances[] = array(
    'title'      => 'MAIN - QA Demo',
    'url'        => 'http://qa.'. $demoBaseHost,
    'root'       => $demoDocRootBaseDir . '/qa',
    'p4root'     => $demoServerBaseDir . '/qa',
    'p4port'     => '4444',
    'p4user'     => $defaultInstanceUser,
    'syncClient' => $demoClientBaseName . 'qa',
    'syncPath'   => $sync['defaultPath'],
    'allowReset' => true,
);

$instances[] = array(
    'title'      => 'MAIN - Web Demo',
    'url'        => 'http://web.'. $demoBaseHost,
    'root'       => $demoDocRootBaseDir . '/web',
    'p4root'     => $demoServerBaseDir . '/web',
    'p4port'     => '5555',
    'p4user'     => $defaultInstanceUser,
    'syncClient' => $demoClientBaseName . 'web',
    'syncPath'   => $sync['defaultPath'],
    'allowReset' => true,
);

$instances[] = array(
    'title'      => 'MAIN - Eval-Demo for Don',
    'url'        => 'http://dmarti-demo.'. $demoBaseHost,
    'root'       => $demoDocRootBaseDir . '/dmarti',
    'p4root'     => $demoServerBaseDir . '/dmarti',
    'p4port'     => '6666',
    'p4user'     => $defaultInstanceUser,
    'syncClient' => $demoClientBaseName . 'dmarti',
    'syncPath'   => $sync['defaultPath'],
    'allowReset' => true,
);

$instances[] = array(
    'title'      => 'MAIN - Eval-Demo for Terry',
    'url'        => 'http://twilliams-demo.'. $demoBaseHost,
    'root'       => $demoDocRootBaseDir . '/twilliams',
    'p4root'     => $demoServerBaseDir . '/twilliams',
    'p4port'     => '7777',
    'p4user'     => $defaultInstanceUser,
    'syncClient' => $demoClientBaseName . 'twilliams',
    'syncPath'   => $sync['defaultPath'],
    'allowReset' => true,
);

$instances[] = array(
    'title'      => 'MAIN - Eval-Demo for Jackie',
    'url'        => 'http://jgarcia-demo.'. $demoBaseHost,
    'root'       => $demoDocRootBaseDir . '/jgarcia',
    'p4root'     => $demoServerBaseDir . '/jgarcia',
    'p4port'     => '8888',
    'p4user'     => $defaultInstanceUser,
    'syncClient' => $demoClientBaseName . 'jgarcia',
    'syncPath'   => $sync['defaultPath'],
    'allowReset' => true,
);

$instances[] = array(
    'title'      => 'MAIN - Debug',
    'url'        => 'http://maindebug.'. $demoBaseHost,
    'root'       => $demoDocRootBaseDir . '/maindebug',
    'p4root'     => $demoServerBaseDir . '/maindebug',
    'p4port'     => '9999',
    'p4user'     => $defaultInstanceUser,
    'syncClient' => $demoClientBaseName . 'maindebug',
    'syncPath'   => '//...',
    'allowReset' => true,
);

$instances[] = array(
    'title'      => '2011.1 - Demo',
    'url'        => 'http://20111.'. $demoBaseHost,
    'root'       => $demoDocRootBaseDir . '/2011.1',
    'p4root'     => $demoServerBaseDir . '/2011.1',
    'p4port'     => '20111',
    'p4user'     => $defaultInstanceUser,
    'syncClient' => $demoClientBaseName . '2011.1',
    'syncPath'   => '//...',
    'allowReset' => true,
);

$instances[] = array(
    'title'      => '2011.2 - Demo',
    'url'        => 'http://20112.'. $demoBaseHost,
    'root'       => $demoDocRootBaseDir . '/2011.2',
    'p4root'     => $demoServerBaseDir . '/2011.2',
    'p4port'     => '20112',
    'p4user'     => $defaultInstanceUser,
    'syncClient' => $demoClientBaseName . '2011.2',
    'syncPath'   => '//...',
    'allowReset' => true,
);

$instances[] = array(
    'title'      => '2012.1 - Demo',
    'url'        => 'http://20121.'. $demoBaseHost,
    'root'       => $demoDocRootBaseDir . '/2012.1',
    'p4root'     => $demoServerBaseDir . '/2012.1',
    'p4port'     => '20121',
    'p4user'     => $defaultInstanceUser,
    'syncClient' => $demoClientBaseName . '2012.1',
    'syncPath'   => '//...',
    'allowReset' => true,
);

$instances[] = array(
    'title'      => '2012.2 - Demo',
    'url'        => 'http://20122.'. $demoBaseHost,
    'root'       => $demoDocRootBaseDir . '/2012.2',
    'p4root'     => $demoServerBaseDir . '/2012.2',
    'p4port'     => '20122',
    'p4user'     => $defaultInstanceUser,
    'syncClient' => $demoClientBaseName . '2012.2',
    'syncPath'   => '//...',
    'allowReset' => true,
);

$instances[] = array(
    'title'      => '2012.3 - Demo',
    'url'        => 'http://20123.'. $demoBaseHost,
    'root'       => $demoDocRootBaseDir . '/2012.3',
    'p4root'     => $demoServerBaseDir . '/2012.3',
    'p4port'     => '20123',
    'p4user'     => $defaultInstanceUser,
    'syncClient' => $demoClientBaseName . '2012.3',
    'syncPath'   => '//...',
    'allowReset' => true,
);

$instances[] = array(
    'title'      => 'LIVEDEV - Demo',
    'url'        => 'http://livedev.'. $demoBaseHost,
    'root'       => $demoDocRootBaseDir . '/livedev',
    'p4root'     => $demoServerBaseDir . '/livedev',
    'p4port'     => '1174',
    'p4user'     => $defaultInstanceUser,
    'syncClient' => $demoClientBaseName . 'livedev',
    'syncPath'   => '//...',
    'allowReset' => true,
);

$instances[] = array(
    'title'      => 'LIVE - Demo',
    'url'        => 'http://live.'. $demoBaseHost,
    'root'       => $demoDocRootBaseDir . '/live',
    'p4root'     => $demoServerBaseDir . '/live',
    'p4port'     => '1173',
    'p4user'     => $defaultInstanceUser,
    'syncClient' => $demoClientBaseName . 'live',
    'syncPath'   => '//...',
    'allowReset' => true,
);

$instances[] = array(
    'title'      => 'LIVE - PerforceChronicle.com Demo',
    'url'        => 'http://perforcechronicle.'. $demoBaseHost,
    'root'       => $demoDocRootBaseDir . '/p4chron.com',
    'p4root'     => $demoServerBaseDir . '/p4chron.com',
    'p4port'     => '(rsh)',
    'p4user'     => $defaultInstanceUser,
    'syncClient' => $demoClientBaseName . 'p4chron.com',
    'syncPath'   => '//...',
    'allowReset' => false,
);

$instances[] = array(
    'title'      => 'Dev Team Demo Instance',
    'url'        => 'http://main.'. $demoBaseHost,
    'root'       => $demoDocRootBaseDir . '/main',
    'p4root'     => $demoServerBaseDir . '/main',
    'p4port'     => '12345',
    'p4user'     => $defaultInstanceUser,
    'syncClient' => $demoClientBaseName . 'main',
    'syncPath'   => $sync['defaultPath'],
    'allowReset' => true,
);

