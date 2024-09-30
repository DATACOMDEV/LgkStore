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

    $storeIds = [1, 2, 20];

    foreach ($storeIds as $sId) {
        $results = $conn->query('SELECT entity_id, value FROM catalog_product_entity_varchar WHERE attribute_id=122 AND store_id='.$sId);

        if (!$results) throw new \Exception('Query error: '.$conn->error);

        $vals = [];
        foreach ($results as $row) {
            $vals[$row['entity_id']] = $row['value'];
        }

        foreach ($vals as $id => $val) {
            $stmnt = $conn->prepare('UPDATE catalog_product_entity_varchar SET value=? WHERE attribute_id=123 AND store_id=? AND entity_id=?');
            $stmnt->bind_param('sii', $val, $sId, $id);
            $stmnt->execute();
        }
    }
} catch (Exception $ex) {
    $err = $ex;
}

unlink($targetFile);

if (!is_null($err)) throw $err;

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