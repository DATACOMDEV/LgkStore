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

if (file_exists('categories_ids.txt')) {
    unlink('categories_ids.txt');
}

$conn = get_connection($dbData);

$foundCats = [];
$results = $conn->query('SELECT c.entity_id, c.path, vc.value FROM catalog_category_entity c
INNER JOIN catalog_category_entity_varchar vc ON vc.entity_id=c.entity_id
WHERE c.entity_id>2 AND vc.attribute_id=42 AND vc.store_id=0
ORDER BY path ASC');
if (!$results) {
    throw new \Exception('Query error: '.$conn->error);
  }
while ($row = $results->fetch_assoc()) {
    $foundCats[$row['entity_id']] = $row['value'];

    $curPathIds = explode('/', $row['path']);
    array_shift($curPathIds);
    array_shift($curPathIds);

    $pathStr = [];
    foreach ($curPathIds as $id) {
        $pathStr[] = $foundCats[$id];
    }
    $pathStr = implode('\\', $pathStr);

    file_put_contents(dirname(__FILE__).'/categories_ids.txt', sprintf("%s    id: %d\r\n", $pathStr, $row['entity_id']), FILE_APPEND);
}