<?php

namespace Datacom\LgkStore\Console\Command;

class GetProductOptionsWithPriceCommand extends \Symfony\Component\Console\Command\Command {
    
    protected $_productCollectionFactory;
    protected $_productRepository;
    protected $_state;
    protected $_curl;
    protected $_file;
    protected $_customOptions;

    protected $_objectManager;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\App\State $state,
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository
	) {
        parent::__construct();
        
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_productRepository = $productRepository;
        $this->_state = $state;
        
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_curl = $this->_objectManager->create('Magento\Framework\HTTP\Client\Curl');
        $this->_file = $this->_objectManager->create('Magento\Framework\Filesystem\Io\File');
        $this->_customOptions = $this->_objectManager->get('Magento\Catalog\Model\Product\Option');
    }
    
    protected function configure()
	{
        $this->setName('datacom:getproductoptionswithpricecommand')->setDescription('Carica dal vecchio sito le opzioni con prezzi per prodotti');
    }

    protected function getRemoteProductImage($curEndpoint) {
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
        $this->_state->setAreaCode('adminhtml');
        /*FUNZIONE PER RECUPERO PREZZI OPZIONI*/
        $endPoint = 'http://www.lgkstore.it/datacomapi2.php?rand=123&name=';
        $products = $this->_productCollectionFactory->create()
            //->addAttributeToFilter('sku', 'RKD JFASILV')
            ->setOrder('entity_id', 'ASC');
        
        foreach ($products as $curProd) {
            $curProduct = $this->_productRepository->get($curProd->getSku());

            $customOptions = $this->_customOptions->getProductOptionCollection($curProduct);

            if (empty($customOptions) ||  count($customOptions) == 0) {
                continue;
            }

            $curEndpoint = $endPoint.urlencode($curProduct->getName());

            if (strpos($curProduct->getName(), '&') !== false) {
                $curEndpoint .= '&fix=1';
            }

            try {
                $remoteOptions = $this->getRemoteProductOptions($curEndpoint);
            } catch (\Throwable $th) {
                /*$output->writeln("SKU: ".$curProduct->getSku());
                $output->writeln("URL: ".$curEndpoint);
                $output->writeln("ERRORE: ".$th->getMessage());*/
                continue;
            }

            if (!empty($remoteOptions)) {
                //$output->writeln("SKU: ".$curProduct->getSku());

                $toSaveCustomOptions = array();
                foreach ($customOptions as $opt) {
                    //$output->writeln('OPZIONE: '.$opt->getTitle());

                    foreach ($remoteOptions as $k => $d) {
                        if ($opt->getTitle() != trim($d['name'])) {
                            continue;
                        }

                        $found = true;
                        $values = $opt->getValues();
                        foreach ($values as $val) {
                            foreach ($d['data'] as $optData) {
                                if ($val->getTitle() != trim($optData['value'])) {
                                    continue;
                                }
                                
                                if (number_format($val->getPrice(), 4, '.', '') != number_format($optData['price'], 4, '.', '')) {
                                    $output->writeln('UPDATE `catalog_product_option_type_price` SET price='.number_format($optData['price'], 4, '.', '').' WHERE option_type_id='.$val->getOptionTypeId().';');
                                }

                                $toCheckSku = trim($optData['sku']);
                                if (!empty($toCheckSku) && $val->getSku() != $toCheckSku) {
                                    $toCheckSku = str_replace('\'', '\'\'', $toCheckSku);
                                    //$output->writeln('UPDATE `catalog_product_option_type_value` SET sku=\''.$toCheckSku.'\' WHERE option_type_id='.$val->getOptionTypeId().';');
                                }

                                break;
                            }
                        }

                        break;
                    }
                }
            }
        }
	}
}