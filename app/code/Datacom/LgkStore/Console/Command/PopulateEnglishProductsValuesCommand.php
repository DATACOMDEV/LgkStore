<?php

namespace Datacom\LgkStore\Console\Command;

class PopulateEnglishProductsValuesCommand extends \Symfony\Component\Console\Command\Command {
    
    protected $_productRepository;
    protected $_state;

    protected $_objectManager;

    public function __construct(
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\App\State $state
	) {
        parent::__construct();
        
        $this->_productRepository = $productRepository;
        $this->_state = $state;
        
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_productCollection = $this->_objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
        $this->_curl = $this->_objectManager->create('Magento\Framework\HTTP\Client\Curl');
    }
    
    protected function configure()
	{
		$this->setName('datacom:populateenglishproductsvalues')->setDescription('Popola i valori in inglese dei prodotti partendo dai csv');
    }

    protected function getProduct($sku, $name) {
        $retval = null;
        $nameToCheck = trim($name);
        $items = $this->_productCollection->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('sku', ['like' => $sku.'%']);

        foreach ($items as $item) {
            if (in_array($item->getSku(), [$sku, $sku.'-'.$item->getId()])) {
                if ($item->getName() == $nameToCheck) {
                    $retval = $this->_productRepository->get($item->getSku());
                    break;
                }
            }
        }

        return $retval;
    }

    protected function getRemoteProductOptions($curEndpoint) {
        $retval = [];
        $this->_curl->get($curEndpoint);
        $resp = trim($this->_curl->getBody());

        try {
            $jsonResp = json_decode($resp, true);
        } catch (\Throwable $th) {
            $jsonResp = null;
        }

        if ($jsonResp == null) {
            if ($resp == '[]') {
                return $retval;
            } else {
                throw new \Exception($resp);
            }
        } else {
            foreach ($jsonResp as $item) {
                $retval[] = $item;
            }
        }

        return $retval;
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
	{
        //if (!$this->_state->getAreaCode()) {
            $this->_state->setAreaCode('adminhtml');
        //}

        $endPoint = 'http://www.lgkstore.it/datacomapi3.php?rand=123&name=';

        $items = $this->_productCollection->create()
            ->addAttributeToSelect('*');

        foreach ($items as $prod) {
            //$endsWith = '-'.$prod->getId();
            //if (substr($prod->getSku(), -strlen($endsWith)) == $endsWith) {
                
                $itaProd = $this->_productRepository->get($prod->getSku(), false, 20);
                $curProduct = $this->_productRepository->get($prod->getSku(), false, 1);
                $curEndpoint = $endPoint.urlencode($itaProd->getName());

                if (strpos($itaProd->getName(), '&') !== false) {
                    $curEndpoint .= '&fix=1';
                }
                
                try {
                    $oldWebsiteData = $this->getRemoteProductOptions($curEndpoint);
                } catch (\Throwable $th) {
                    $output->writeln('<error>ERROR: '.$prod->getId().'</error>');
                    $output->writeln('<error>ERROR: '.$th->getMessage().'</error>');
                    $output->writeln('--------------');
                    continue;
                }
                
                $toUpdate = [];

                /*if ($curProduct->getName() != $oldWebsiteData[0]) {
                    $toUpdate['name'] = $oldWebsiteData[0];
                }

                $newDescr = html_entity_decode($oldWebsiteData[1]);
                if ($curProduct->getDescription() != $newDescr) {
                    $toUpdate['description'] = $newDescr;
                }

                if ($curProduct->getUrlKey() != $oldWebsiteData[2]) {
                    $toUpdate['url_key'] = $oldWebsiteData[2];
                }

                $newMetaTitle = $oldWebsiteData[3];
                if ($newMetaTitle != 'LGK Store' && $curProduct->getMetaTitle() != $newMetaTitle) {
                    $toUpdate['meta_title'] = $newMetaTitle;
                }

                if ($curProduct->getMetaDescription() != $oldWebsiteData[4]) {
                    $toUpdate['meta_description'] = $oldWebsiteData[4];
                }*/

                if ($curProduct->getMetaKeyword() != $oldWebsiteData[5]) {
                    $toUpdate['meta_keyword'] = $oldWebsiteData[5];
                }

                if (count($toUpdate) > 0) {
                    try {
                        $productActionObject = $this->_objectManager->create('Magento\Catalog\Model\Product\Action');
                        $productActionObject->updateAttributes([$prod->getId()], $toUpdate, 1);
                        $output->writeln('<info>OK: '.$prod->getId().'</info>');
                        $output->writeln(print_r($toUpdate, true));
                    } catch (\Throwable $th) {
                        $output->writeln('<error>ERROR: '.$prod->getId().'</error>');
                        $output->writeln('<error>ERROR: '.$th->getMessage().'</error>');
                    }
                }

                $output->writeln('--------------');
            //}
        }

        /*$rows = file(dirname(__FILE__).'/lgkstore.products.csv', FILE_IGNORE_NEW_LINES);

        foreach ($rows as $row) {
            $data = explode('","', $row);
            $data = array_map(function($item) {
                $retval = trim($item, '"');
                $retval = trim($retval);

                return $retval;
            }, $data);

            if (empty($data[0]) || count($data) < 3) {
                continue;
            }

            try {
                //$curProduct = $this->_productRepository->get($data[0], false, 1);
                $curProduct = $this->getProduct($data[0], $data[4]);
            } catch (\Throwable $th) {
                $output->writeln($th->getMessage());
                continue;
            }

            if ($curProduct->getStatus() != 1) {
                continue;
            }


            $datatoSave = array();

            $output->writeln("PRODOTTO: ".$curProduct->getName()." [".$curProduct->getSku()."] (".$curProduct->getId().")");
            $output->writeln("NOME VECCHIO: ".$curProduct->getName());
            $output->writeln("URL KEY VECCHIO: ".$curProduct->getUrlKey());

            if ($curProduct->getName() != $data[1]) {
                $datatoSave['name'] = $data[1];
                $output->writeln("NOME NUOVO: ".$data[1]);
            }

            if ($curProduct->getUrlKey() != $data[2]) {
                $datatoSave['url_key'] = $data[2];
                $output->writeln("URL KEY NUOVO: ".$data[2]);
            }

            try {
                if (count($datatoSave) > 0) {
                    //$this->_productRepository->save($curProduct);
                    foreach ($datatoSave as $k => $v) {
                        $curProduct->addAttributeUpdate($k, $v, 1);
                    }
                }
                $output->writeln('-----------------');
            } catch (\Throwable $th) {
                $output->writeln($th);
                $output->writeln('-----------------');
                continue;
            }
        }*/
	}
}