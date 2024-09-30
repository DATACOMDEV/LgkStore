<?php

require '000.deepl_data.php';

$cwd = dirname(__FILE__);

$magentoRoot = dirname(dirname($cwd));

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

$conn = get_connection($dbData);
$conn->set_charset('utf8');

$idAttributVarchar = [
    46  //meta_title    
];
$idAttributiText = [
    48, //meta_description
    47  //meta_keywords
];
//Recupero gli articoli che hanno già un valore sullo store francese per l'attributo interessato
$idProdottiValorizzati = [];
$results = $conn->query(sprintf('SELECT entity_id, value FROM catalog_category_entity_varchar
WHERE attribute_id=42 AND store_id=%d', $idTargetStore));
if (!$results) {
    throw new \Exception('Query error: '.$conn->error);
}
while ($row = $results->fetch_assoc()) {
    $idProdottiValorizzati[$row['entity_id']] = $row['value'];
}
if (empty($idProdottiValorizzati)) die();
foreach ($idAttributVarchar as $idAttributo) {
    foreach ($idProdottiValorizzati as $entityId => $newValue) {
        $stmnt = $conn->prepare('INSERT INTO catalog_category_entity_varchar (attribute_id, store_id, entity_id, value) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE value=?');
        $stmnt->bind_param('iiiss', $idAttributo, $idTargetStore, $entityId, $newValue, $newValue);
        $stmnt->execute();

        echo $entityId." aggiornato\r\n";
    }
}
foreach ($idAttributiText as $idAttributo) {
    foreach ($idProdottiValorizzati as $entityId => $newValue) {
        $stmnt = $conn->prepare('INSERT INTO catalog_category_entity_text (attribute_id, store_id, entity_id, value) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE value=?');
        $stmnt->bind_param('iiiss', $idAttributo, $idTargetStore, $entityId, $newValue, $newValue);
        $stmnt->execute();

        echo $entityId." aggiornato\r\n";
    }
}
