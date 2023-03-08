<?php

/*
Query per estrapolare i dati necessari dal sito vecchio:

SELECT oc_lgk3_product.model, oc_lgk3_product_discount.customer_group_id, oc_lgk3_product_discount.quantity, oc_lgk3_product_discount.price, oc_lgk3_product_description.name
FROM oc_lgk3_product
INNER JOIN oc_lgk3_product_discount ON oc_lgk3_product_discount.product_id=oc_lgk3_product.product_id
INNER JOIN oc_lgk3_product_description ON oc_lgk3_product_description.product_id=oc_lgk3_product.product_id AND oc_lgk3_product_description.language_id=3
ORDER BY oc_lgk3_product.model, oc_lgk3_product_description.name

*/

namespace Datacom\LgkStore\Console\Command;

class FixGroupPricesCommand extends \Symfony\Component\Console\Command\Command {
    
    protected $_productRepository;
    protected $_tierPriceApi;
    protected $_state;
    protected $_productCollection;

    protected $_objectManager;

    protected $_lastErrorMessage;

    public function __construct(
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Catalog\Api\ProductTierPriceManagementInterface $tierPriceApi,
        \Magento\Framework\App\State $state
	) {
        parent::__construct();
        
        $this->_productRepository = $productRepository;
        $this->_tierPriceApi = $tierPriceApi;
        $this->_state = $state;
        
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->_productCollection = $this->_objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
    }
    
    protected function configure()
	{
        $options = array(
			new \Symfony\Component\Console\Input\InputOption(
				'continue',
				null,
				\Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED,
				'Continue'
			)
        );

		$this->setName('datacom:fixgrouppricescommand')->setDescription('Fix prezzi gruppi')->setDefinition($options);
    }

    protected function setProductTierPrices($productId, $tierPricesData) {
        $curProduct = $this->_productRepository->getById($productId);
        $mustSave = false;
        if (count($curProduct->getTierPrice()) > 0) {
            $curProduct->setTierPrice(array());
            $mustSave = true;
        }

        if ($curProduct->getPrice() == 0) {
            foreach ($tierPricesData as $tpd) {
                if ($tpd['cust_group'] == 8) {
                    $mustSave = true;
                    $curProduct->setPrice($tpd['price']);
                    break;
                }
            }
        }

        if ($mustSave) {
            try {
                $this->_productRepository->save($curProduct);
            } catch (\Exception $ex) {
                $this->_lastErrorMessage = $ex;
                return;
            }
        }

        /*$toDeleteItems = [];
        foreach ($curProduct->getTierPrices() as $tp) {
            $toDeleteItems[] = [
                'customer_group_id' => $tp->getCustomerGroupId(),
                'qty' => $tp->getQty()
            ];
        }

        foreach ($toDeleteItems as $toDelData) {
            $this->_tierPriceApi->remove($curProduct->getSku(), $toDelData['customer_group_id'], $toDelData['qty']);
        }*/

        foreach ($tierPricesData as $tierData) {

            if ($tierData['price'] == 0) {
                continue;
            }

            if ($tierData['price_qty'] == 0) {
                $tierData['price_qty'] = 1;
            }

            $this->_tierPriceApi->add($curProduct->getSku(), $tierData['cust_group'], $tierData['price'], $tierData['price_qty']);
        }
    }

    protected function getNewTierPrice($customerGroupId, $qty, $price) {
        return array(
            'website_id'  => 0,
            'cust_group'  => $customerGroupId,
            'price_qty'   => $qty,
            'price'   => $price
        );
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

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
	{
        $continue = $input->getOption('continue');
        //if (!$this->_state->getAreaCode()) {
            $this->_state->setAreaCode('adminhtml');
        //}
        
        $groupIdMapping = array(
            3 => 11, //Associazione sportiva
            7 => 10, //Azienda estera
            6 => 9, //Azienda italiana
            1 => 8, //Privato
            9 => 7, //Privato (Resto del mondo)
            2 => 6, //Rivenditori
            11 => 5,    //Rivenditori esteri
            10 => 4,    //Rivenditori italiani
        );

        $saveFile = dirname(__FILE__).'/grouppricefix.csv';

        $alreadyDoneIds = array();
        if (!$continue) {
            if (file_exists($saveFile)) {
                unlink($saveFile);
            }
        } else {
            if (file_exists($saveFile)) {
                $alreadyDoneIds = file($saveFile, FILE_IGNORE_NEW_LINES);
            }
        }

        $rows = file(dirname(__FILE__).'/lgkstore.group-prices.csv', FILE_IGNORE_NEW_LINES);

        $lastProductId = -1;
        $tierPricesData = array();
        foreach ($rows as $row) {
            $data = explode('","', $row);
            $data = array_map(function($item) {
                $retval = trim($item, '"');
                $retval = trim($retval);

                return $retval;
            }, $data);

            if (empty($data[0]) || count($data) < 5) {
                continue;
            }

            try {
                $curProduct = $this->getProduct($data[0], $data[4]);
            } catch (\Throwable $th) {
                $output->write('"'.$data[0].'": ');
                $output->writeln($th->getMessage());
                continue;
            }

            if (empty($curProduct) || !$curProduct->getId()) {
                continue;
            }

            if (in_array($curProduct->getId(), $alreadyDoneIds)) {
                continue;
            }

            if ($curProduct->getStatus() != 1) {
                continue;
            }

            if ($lastProductId != $curProduct->getId()) {
                if ($lastProductId != -1 && !empty($tierPricesData)) {
                    $this->setProductTierPrices($lastProductId, $tierPricesData);

                    if (!empty($this->_lastErrorMessage)) {
                        $output->writeln("PROBLEMA: + PRODOTTO '".$data[0]."' + ERRORE: '".$this->_lastErrorMessage."'");
                        $this->_lastErrorMessage = null;
                    }

                    file_put_contents($saveFile, $lastProductId."\r\n", FILE_APPEND);
                    $lastProductId = -1;
                    $tierPricesData = array();
                }

                $output->writeln("PRODOTTO: ".$curProduct->getName()." [".$curProduct->getSku()."] (".$curProduct->getId().")");
                $tierPricesData = array(
                    $this->getNewTierPrice($groupIdMapping[intval($data[1])], intval($data[2]), $data[3])
                );
            } else {
                $found = false;
                foreach ($tierPricesData as $tierData) {
                    if ($tierData['cust_group'] == $groupIdMapping[intval($data[1])] &&
                    $tierData['price_qty'] == intval($data[2])/* &&
                    $tierData['price'] == $data[3]*/) {
                        $found = true;

                        if ($tierData['price'] != $data[3]) {
                            $output->writeln("PROBLEMA: + PRODOTTO '".$data[0]."' + CUSTOMER GROUP '".$tierData['cust_group']."' + QTA '".$tierData['price_qty']."' + PREZZI '".$tierData['price']."' E '".$data[3]."'");
                        }

                        break;
                    }
                }

                if (!$found) {
                    $tierPricesData[] = $this->getNewTierPrice($groupIdMapping[intval($data[1])], intval($data[2]), $data[3]);
                }
            }


            $lastProductId = $curProduct->getId();
        }

        if ($lastProductId != -1 && !empty($tierPricesData)) {
            $this->setProductTierPrices($lastProductId, $tierPricesData);

            if (!empty($this->_lastErrorMessage)) {
                $output->writeln("PROBLEMA: + PRODOTTO '".$data[0]."' + ERRORE: '".$this->_lastErrorMessage."'");
                $this->_lastErrorMessage = null;
            }
            
            file_put_contents($saveFile, $lastProductId."\r\n", FILE_APPEND);
            $lastProductId = -1;
            $tierPricesData = array();
        }
	}
}