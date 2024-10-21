<?php

$cwd = dirname(__FILE__);

$magentoRoot = dirname($cwd);

$dbData = require($magentoRoot.'/app/etc/env.php');
$dbData = $dbData['db']['connection']['default'];

if (!function_exists('get_connection')) {
	function get_connection($dbData) {
		static $conn = null;
		
		if (is_null($conn)) {
			$conn = new \mysqli($dbData['host'], $dbData['username'], $dbData['password'], $dbData['dbname']);
		}
		
		if ($conn->connect_error) {
			throw new \Exception('Connection failed: ' . $conn->connect_error);
		}
		
		return $conn;
	}
}

$targetFile = dirname(__FILE__).'/align_url_path.lock';

if (file_exists($targetFile)) return;

touch($targetFile);

$err = null;

try {
    $conn = get_connection($dbData);

    $results = $conn->query('DELETE FROM catalog_product_entity_varchar WHERE store_id<>0 AND attribute_id IN (84, 85, 86)');
    
    if (!$results) throw new \Exception('Query error: '.$conn->error);
} catch (Exception $ex) {
    $err = $ex;
}

unlink($targetFile);

if (!is_null($err)) throw $err;
