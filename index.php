<?php
/**
 * Application entry point
 *
 * Example - run a particular store or website:
 * --------------------------------------------
 * require __DIR__ . '/app/bootstrap.php';
 * $params = $_SERVER;
 * $params[\Magento\Store\Model\StoreManager::PARAM_RUN_CODE] = 'website2';
 * $params[\Magento\Store\Model\StoreManager::PARAM_RUN_TYPE] = 'website';
 * $bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $params);
 * \/** @var \Magento\Framework\App\Http $app *\/
 * $app = $bootstrap->createApplication(\Magento\Framework\App\Http::class);
 * $bootstrap->run($app);
 * --------------------------------------------
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/*if ($_SERVER['REMOTE_ADDR'] != '79.8.112.38') {
	require 'maintenance.html';
	die();
}*/

$requestUri = $_SERVER['REQUEST_URI'];
$requestUriLength = strlen($requestUri);
$mustRedirect = false;
if (substr($requestUri, 0, 14) != '/admin_10kq5z/' &&
!in_array(substr($requestUri, 0, 4), ['/en/', '/it/'])) {
    $mustRedirect = true;
}

if ($mustRedirect) {
    header("location: https://www.kartandgo.store/it".$requestUri);
    die();
}

try {
    require __DIR__ . '/app/bootstrap.php';
} catch (\Exception $e) {
    echo <<<HTML
<div style="font:12px/1.35em arial, helvetica, sans-serif;">
    <div style="margin:0 0 25px 0; border-bottom:1px solid #ccc;">
        <h3 style="margin:0;font-size:1.7em;font-weight:normal;text-transform:none;text-align:left;color:#2f2f2f;">
        Autoload error</h3>
    </div>
    <p>{$e->getMessage()}</p>
</div>
HTML;
    exit(1);
}

/*$dbConfigParameters = require dirname(__FILE__).'/app/etc/env.php';
require(dirname(__FILE__) . '/ThrottleRequest.php' );
$tr = new ThrottleRequest([
	'db_user' => $dbConfigParameters['db']['connection']['default']['username'],
	'db_pass' => $dbConfigParameters['db']['connection']['default']['password'],
	'db_dbname' => $dbConfigParameters['db']['connection']['default']['dbname']
]);*/

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
/** @var \Magento\Framework\App\Http $app */
$app = $bootstrap->createApplication(\Magento\Framework\App\Http::class);
$bootstrap->run($app);
