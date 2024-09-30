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

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' || $_SERVER['SERVER_PORT'] != 443) {
    $location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $location);
    exit();
}

 $allowedIps = [
    '94.138.175.51',    //nostro
    '46.114.214.113',    //tedesco
    '185.89.39.21'      //tedesco 2
];

if (in_array($_SERVER['HTTP_HOST'], ['www.kartandgo.de', 'kartandgo.de']) &&
!in_array($_SERVER['REMOTE_ADDR'], $allowedIps)) {
    header('Location: https://www.kartandgo.store');
    exit();
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

if (in_array($_SERVER['REMOTE_ADDR'], $allowedIps)) {
    $params = $_SERVER;

    if (array_key_exists('HTTP_HOST', $params)) {
        $params[\Magento\Store\Model\StoreManager::PARAM_RUN_TYPE] = 'website';
        
        switch($_SERVER['HTTP_HOST']) {
            case 'kartandgo.de':
            case 'www.kartandgo.de':
                $params[\Magento\Store\Model\StoreManager::PARAM_RUN_CODE] = 'base_de';
                break;
            default:
                $params[\Magento\Store\Model\StoreManager::PARAM_RUN_CODE] = 'base';
                break;
        }
    }

    $bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $params);
    /** @var \Magento\Framework\App\Http $app */
    $app = $bootstrap->createApplication(\Magento\Framework\App\Http::class);
    $bootstrap->run($app);
} else {
    $bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
    /** @var \Magento\Framework\App\Http $app */
    $app = $bootstrap->createApplication(\Magento\Framework\App\Http::class);
    $bootstrap->run($app);
}
