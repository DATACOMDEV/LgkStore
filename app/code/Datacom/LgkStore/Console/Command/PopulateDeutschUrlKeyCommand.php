<?php

namespace Datacom\LgkStore\Console\Command;

class PopulateDeutschUrlKeyCommand extends \Symfony\Component\Console\Command\Command {
    
    const TARGET_STORE_ID = 21;

    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Catalog\Model\ProductRepository $productRepository
	) {
        parent::__construct();
        
        $this->_state = $state;
        $this->_conn = $resourceConnection;
        $this->_categoryRepository = $categoryRepository;
        $this->_productRepository = $productRepository;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->_storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
    }

    protected function configure() {
        $this->setName('datacom:populatedeutschurlkeycommand')->setDescription('Popola le url key di prodotti e categorie per lo store tedesco partendo dai rispettivi nomi');
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
        $this->_state->setAreaCode('adminhtml');

        $lockFile = dirname(__FILE__).'/PopulateDeutschUrlKeyCommand.lock';

        if (file_exists($lockFile)) return;

        touch($lockFile);

        $err = null;

        try {
            $this->_execute($input, $output);
        } catch (\Exception $ex) {
            $err = $ex;
        }

        if (file_exists($lockFile)) {
            unlink($lockFile);
        }

        if (!is_null($err)) throw $err;
    }

    protected function _execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
        $conn = $this->_conn->getConnection();
        
        /*$catsData = $this->__getCategoriesWithStoreName($conn);
        $this->__handleCats($catsData);*/

        $prodsData = $this->__getProductsWithStoreName($conn);
        $this->__handleProds($prodsData);
    }

    protected function __handleCats($catsData) {
        if (empty($catsData)) return;

        foreach ($catsData as $id => $name) {
            $origCat = $this->_categoryRepository->get($id, 0);
            $storeCat = $this->_categoryRepository->get($id, self::TARGET_STORE_ID);
            
            if ($origCat->getUrlKey() != $storeCat->getUrlKey()) continue;
            
            $newUrlKey = $this->__getSanitizedUrlKey($name);
            
            if ($storeCat->getUrlKey() == $newUrlKey) continue;

            $storeCat->setUrlKey($newUrlKey);
            $storeCat->setData('save_rewrites_history', false);
            
            try {
                $storeCat->save();
            } catch (\Exception $ex) {
                echo "ERRORE: ".$storeCat->getId()."\r\n";
                echo "URL: ".$newUrlKey."\r\n";
                continue;
            }

            echo "FATTO: ".$storeCat->getId()."\r\n";
        }

        //Poi sono da cancellare gli attributi superflui dello store TARGET_STORE_ID
        //Poi sono da canellare i record url_rewrite con: redirect_type 301 store_id TARGET_STORE_ID is_autogenerated 0 metadata IS NOT NULL
    }

    protected function __handleProds($prodsData) {
        if (empty($prodsData)) return;
        $this->_storeManager->setCurrentStore(self::TARGET_STORE_ID);
        /*$storeProd = $this->_productRepository->getById(1, false, self::TARGET_STORE_ID);
        $storeProd->setUrlKey('moteur-rotax-max-micro-evo20');
        $this->_productRepository->save($storeProd);*/
        foreach ($prodsData as $id => $name) {

            try {
                $storeProd = $this->_productRepository->getById($id, true, self::TARGET_STORE_ID);
                $this->_productRepository->save($storeProd);
                //$storeProd->save();
            } catch (\Exception $ex) {
                echo "ERRORE: ".$storeProd->getId()."\r\n";
            }

            continue;

            $done = false;
            $affix = '';
            
            $origProd = $this->_productRepository->getById($id, false, 0);
            $storeProd = $this->_productRepository->getById($id, true, self::TARGET_STORE_ID);
            
            if ($origProd->getUrlKey() != $storeProd->getUrlKey()) continue;

            $newUrlKey = $this->__getSanitizedUrlKey($name);
            $storeProdUrlKey = $storeProd->getUrlKey();

            while (!$done) {
                $newUrlKey .= $affix;
    
                if ($storeProdUrlKey == $newUrlKey) {
                    $done = true;
                    break;
                }
    
                $storeProd->setUrlKey($newUrlKey);
    
                try {
                    $this->_productRepository->save($storeProd);
                    //$storeProd->save();
                } catch (\Exception $ex) {
                    echo "ERRORE: ".$storeProd->getId()."\r\n";
                    echo "URL: ".$newUrlKey."\r\n";
                    $affix .= '-'.$storeProd->getId();
                    continue;
                }
    
                echo "FATTO: ".$storeProd->getId()."\r\n";
                $done = true;
            }
        }
    }

    protected function __getCategoriesWithStoreName($conn) {
        $query = sprintf('SELECT entity_id, value FROM catalog_category_entity_varchar 
        WHERE entity_id>2 AND attribute_id=42 AND store_id=%d
        ORDER BY entity_id ASC', self::TARGET_STORE_ID);
        $rows = $conn->query($query);
        $retval = [];
        foreach ($rows as $r) {
            $retval[$r['entity_id']] = $r['value'];
        }

        return $retval;
    }

    protected function __getProductsWithStoreName($conn) {
        $query = 'SELECT entity_id, value FROM catalog_product_entity_varchar WHERE attribute_id=70 AND store_id='.self::TARGET_STORE_ID;
        $rows = $conn->query($query);
        $retval = [];
        foreach ($rows as $r) {
            $retval[$r['entity_id']] = $r['value'];
        }

        return $retval;
    }

    private function __getSanitizedUrlKey($name) {
        $newUrlKey = strtolower($name);
        $newUrlKey = trim($newUrlKey);
        $newUrlKey = preg_replace('/[^a-z0-9]/i', '-', $newUrlKey);
        
        while (true) {
            if (strstr($newUrlKey, '--') === false) break;
            $newUrlKey = str_replace('--', '-', $newUrlKey);
        }

        return $newUrlKey;

        $newUrlKey = $this->remove_accents($name);
        $newUrlKey = strtolower($newUrlKey);
        $newUrlKey = trim($newUrlKey);
        $newUrlKey = str_replace([' ', '.'], '-', $newUrlKey);
        $newUrlKey = str_replace(['(', ')'], '', $newUrlKey);

        return $newUrlKey;
    }

    private function remove_accents($string) {
        if ( !preg_match('/[\x80-\xff]/', $string) ) return $string;

        $chars = array(
            // Decompositions for Latin-1 Supplement
            chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
            chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
            chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
            chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
            chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
            chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
            chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
            chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
            chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
            chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
            chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
            chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
            chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
            chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
            chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
            chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
            chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
            chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
            chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
            chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
            chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
            chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
            chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
            chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
            chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
            chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
            chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
            chr(195).chr(191) => 'y',
            // Decompositions for Latin Extended-A
            chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
            chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
            chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
            chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
            chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
            chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
            chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
            chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
            chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
            chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
            chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
            chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
            chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
            chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
            chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
            chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
            chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
            chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
            chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
            chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
            chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
            chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
            chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
            chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
            chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
            chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
            chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
            chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
            chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
            chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
            chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
            chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
            chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
            chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
            chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
            chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
            chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
            chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
            chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
            chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
            chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
            chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
            chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
            chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
            chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
            chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
            chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
            chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
            chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
            chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
            chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
            chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
            chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
            chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
            chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
            chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
            chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
            chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
            chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
            chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
            chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
            chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
            chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
            chr(197).chr(190) => 'z', chr(197).chr(191) => 's'
        );

        $string = strtr($string, $chars);

        return $string;
    }
}