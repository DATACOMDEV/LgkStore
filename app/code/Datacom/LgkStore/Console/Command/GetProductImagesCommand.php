<?php

namespace Datacom\LgkStore\Console\Command;

class GetProductImagesCommand extends \Symfony\Component\Console\Command\Command {
    
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
        $this->setName('datacom:getproductimagescommand')->setDescription('Carica dal vecchio sito le immagini per prodotti che non hanno immagini');
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
        
        $endPoint = 'http://www.lgkstore.it/datacomapi.php?rand=123&name=';
        $products = $this->_productCollectionFactory->create()
            //->addAttributeToFilter('sku', 'RKD 165 E')
            ->setOrder('entity_id', 'ASC');

        foreach ($products as $curProd) {
            $curProduct = $this->_productRepository->get($curProd->getSku());
            $curEndpoint = $endPoint.urlencode($curProduct->getName());

            if (strpos($curProduct->getName(), '&') !== false) {
                $curEndpoint .= '&fix=1';
            }

            $images = $curProduct->getMediaGalleryImages();
            $imgsCount = 0;

            $toWarn = 0;
            if (empty($images)) {
                $toWarn = 2;
            } else {
                $imgsCount = count($images);
                if ($imgsCount < 2) {
                    if ($imgsCount > 0) {
                        foreach ($images as $img) {
                            $this->_curl->get($img->getUrl());
                            $resp = trim($this->_curl->getBody());
                            if (strpos($resp, 'Error 404') !== false) {
                                $toWarn = 1;
                            }
                        }
                    } else {
                        $toWarn = 2;
                    }
                }
            }

            if ($toWarn > 0) {
                $output->writeln("SKU: ".$curProduct->getSku());

                if ($toWarn == 1) {
                    if ($imgsCount == 0) {
                        $output->writeln('PROBLEMA: come mai passa di qua?');
                    } else if ($imgsCount == 1) {
                        foreach ($images as $img) {
                            $output->writeln('PROBLEMA: "'.$img->getUrl().'"');

                            $output->writeln('URL: "'.$curEndpoint.'"');

                            $curRemoteImages = [];
                    
                            try {
                                $curRemoteImages = $this->getRemoteProductImage($curEndpoint);
                            } catch (\Throwable $th) {
                                $output->writeln('RESP: '.$th->getMessage());
                            }

                            foreach ($curRemoteImages as $remoteUrl) {
                                $savePath = str_replace('http://www.kartandgo.store', BP, $img->getUrl());
                                $saveFolder = dirname($savePath);
                                if (!is_dir($saveFolder)) {
                                    mkdir($saveFolder, 0777, true);
                                }
                                $remoteUrl = str_replace([' '], ['%20'], $remoteUrl);
                                $output->writeln('REMOTE IMG: '.$remoteUrl);
                                $output->writeln('LOCAL PATH: '.$savePath);
                                $result = $this->_file->read($remoteUrl, $savePath);

                                if ($result) {
                                    $output->writeln('ESITO: ok');
                                } else {
                                    $output->writeln('ESITO: no');
                                }
                            }
                        }
                    } else {
                        $output->writeln('PROBLEMA: il prodotto ha più immagini"');
                    }
                } else {
                    $output->writeln('URL: "'.$curEndpoint.'"');
                    $curRemoteImages = [];
                    
                    try {
                        $curRemoteImages = $this->getRemoteProductImage($curEndpoint);
                    } catch (\Throwable $th) {
                        $output->writeln('RESP: '.$th->getMessage());
                        $output->writeln('-----');
                        continue;
                    }
                    
                    if (empty($curRemoteImages)) {
                        $output->writeln('ESITO: nessuna immagine');
                    } else {
                        $started = false;
                        foreach ($curRemoteImages as $item) {
                            if (!$started) {
                                $started = true;
                                //metto l'immagine small, thumb, gallery
                            } else {
                                //metto l'immagine e basta
                            }
                        }
    
                        if ($started) {
                            $output->writeln('ESITO: DA SALVARE');
                        } else {
                            $output->writeln('ESITO: nessuna immagine');
                        }
                    }
                }

                $output->writeln('-----');
            }
        }
	}
}