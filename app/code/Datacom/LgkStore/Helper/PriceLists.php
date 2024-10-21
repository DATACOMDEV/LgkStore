<?php

namespace Datacom\LgkStore\Helper;

class PriceLists extends \Magento\Framework\App\Helper\AbstractHelper {
    
    protected $_resourceConnection;
    protected $_productCollectionFactory;
    protected $_tietPrices;
    protected $_productTierPriceManagement;
    protected $_productAction;
    //protected $_basePriceStorage;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Api\TierPriceStorageInterface $tietPrices,
        \Magento\Catalog\Api\ProductTierPriceManagementInterface $productTierPriceManagement,
        \Magento\Catalog\Model\Product\Action $productAction,
        //\Magento\Catalog\Api\BasePriceStorageInterface $basePriceStorage,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->_resourceConnection = $resourceConnection;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_tietPrices = $tietPrices;
        $this->_productTierPriceManagement = $productTierPriceManagement;
        $this->_productAction = $productAction;
        //$this->_basePriceStorage = $basePriceStorage;

        parent::__construct($context);

        //$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }

    public function getCsvHeader() {
        return [
            "sku",
            "codice fornitore",
            "prezzo di acquisto",
            "prezzo",
            "special price",
            "qta azienda italiana",
            "prezzo azienda italiana",
            "qta azienda estera",
            "prezzo azienda estera",
            "qta privato",
            "prezzo privato",
            "qta privato cee",
            "prezzo privato cee",
            "qta privato extra cee",
            "prezzo privato extra cee",
            "qta rivenditori esteri",
            "prezzo rivenditori esteri",
            "qta rivenditori italiani",
            "prezzo rivenditori italiani"
        ];
    }

    public function updateProductData($product, $prezzoDiAcquisto, $price, $specialPrice) {
        $this->_productAction->updateAttributes(
            [$product->getId()], 
            [
                'prezzo_di_acquisto' => $prezzoDiAcquisto,
                'price' => $price,
                'special_price' => $specialPrice
            ],
            0
        );
        
        /*
        Funzionerebbe. Così rimarrebbe scoperta la gestione dello special price.
        $existingPrices = $this->_basePriceStorage->get([$product->getSku()]);

        foreach ($existingPrices as $ep) {
            if ($ep->getPrice() == $price) continue;
            $ep->setPrice($price);
            $this->_basePriceStorage->update([$ep]);
        }*/
    }

    public function updateProductPrices($productSku, 
        $qtaAziendaItaliana, $prezzoAziendaItaliana, 
        $qtaAziendaEstera, $prezzoAziendaEstera, 
        $qtaPrivato, $prezzoPrivato, 
        $qtaPrivatoCee, $prezzoPrivatoCee, 
        $qtaPrivatoExtraCee, $prezzoPrivatoExtraCee, 
        $qtaRivenditoriEsteri, $prezzoRivenditoriEsteri, 
        $qtaRivenditoriItaliani, $prezzoRivenditoriItaliani) {
            //Azienda italiana
            $this->updateProductTierPrice($productSku, 9, $qtaAziendaItaliana, $prezzoAziendaItaliana);

            //Azienda estera
            $this->updateProductTierPrice($productSku, 10, $qtaAziendaEstera, $prezzoAziendaEstera);

            //Privato
            $this->updateProductTierPrice($productSku, 8, $qtaPrivato, $prezzoPrivato);

            //Privato CEE
            $this->updateProductTierPrice($productSku, 7, $qtaPrivatoCee, $prezzoPrivatoCee);

            //Privato extra CEE
            $this->updateProductTierPrice($productSku, 12, $qtaPrivatoExtraCee, $prezzoPrivatoExtraCee);

            //Rivenditori
            //$this->updateProductTierPrice($productSku, 6, $qtaRivenditoriEsteri, $prezzoRivenditoriEsteri);

            //Rivenditori esteri
            $this->updateProductTierPrice($productSku, 5, $qtaRivenditoriEsteri, $prezzoRivenditoriEsteri);

            //Rivenditori italiani
            $this->updateProductTierPrice($productSku, 4, $qtaRivenditoriItaliani, $prezzoRivenditoriItaliani);
    }

    private function updateProductTierPrice($productSku, $customerGroupId, $qty, $price) {
        //a regola hanno 1 tier price per gruppo cliente, di commessa è stato detto che altri casi non sono gestiti

        $existingTier = $this->_productTierPriceManagement->getList($productSku, $customerGroupId);
        $existingTierQty = [];

        foreach ($existingTier as $t) {
            if ($t->getValue() == $price && $t->getQty() == $qty) return;
            $existingTierQty[] = $t->getQty();
        }
        
        if ($qty == 0 && $price == 0) {
            if (empty($existingTierQty)) return;
            foreach ($existingTierQty as $tq) {
                $this->_productTierPriceManagement->remove($productSku, $customerGroupId, floatval($tq));
            }
            return;
        }

        foreach ($existingTierQty as $tq) {
            if ($tq == $qty) continue;
            $this->_productTierPriceManagement->remove($productSku, $customerGroupId, floatval($tq));
        }

        $this->_productTierPriceManagement->add($productSku, $customerGroupId, number_format($price, 2, ',', ''), $qty);
    }

    public function getProducts($manufacturerId = 0) {
        $retval = $this->_productCollectionFactory->create();

        if ($manufacturerId > 0) {
            $retval->addAttributeToFilter('manufacturer', $manufacturerId);
        }

        $retval->addAttributeToSelect([
            'sku',
            'codice_fornitore',
            'prezzo_di_acquisto',
            'price',
            'special_price'
        ]);

        return $retval;
    }

    public function getProductTierPrices($sku) {
        $retval = [
            'azienda italiana' => [
                'qty' => 0,
                'price' => 0.0
            ],
            'azienda estera' => [
                'qty' => 0,
                'price' => 0.0
            ],
            'rivenditori italiani' => [
                'qty' => 0,
                'price' => 0.0
            ],
            'rivenditori esteri' => [
                'qty' => 0,
                'price' => 0.0
            ],
            'rivenditori' => [
                'qty' => 0,
                'price' => 0.0
            ],
            'privato' => [
                'qty' => 0,
                'price' => 0.0
            ],
            'privato cee' => [
                'qty' => 0,
                'price' => 0.0
            ],
            'privato extra cee' => [
                'qty' => 0,
                'price' => 0.0
            ]
        ];

        $tier = $this->_tietPrices->get([$sku]);
        if (count($tier) == 0) return $retval;
        
        foreach ($tier as $t) {
            if ($t->getPriceType() != 'fixed') throw new \Exception(sprintf('Price type: %s', $t->getPriceType()));
            if ($t->getWebsiteId() != 0) throw new \Exception(sprintf('Website id: %d', $t->getWebsiteId()));
            $customerGroupText = strtolower($t->getCustomerGroup());
            if (!array_key_exists($customerGroupText, $retval)) throw new \Exception(sprintf('Missing customer group: %s', $customerGroupText));
            $retval[$customerGroupText]['qty'] = intval($t->getQuantity());
            $retval[$customerGroupText]['price'] = round($t->getPrice(), 2);
        }
        return $retval;
    }

    public function getProductBackupData($product) {
        $retval = [
            'sku' => $product->getSku(),
            'codice_fornitore' => $product->getData('codice_fornitore'),
            'prezzo_di_acquisto' => round($product->getData('prezzo_di_acquisto'), 2),
            'price' => round($product->getPrice(), 2),
            'special_price' => round($product->getSpecialPrice(), 2)
        ];

        $tierData = $this->getProductTierPrices($product->getSku());
        foreach ($tierData as $group => $groupData) {
            switch ($group) {
                case 'azienda italiana':
                    $retval['qta_azienda_italiana'] = intval($groupData['qty']);
                    $retval['prezzo_azienda_italiana'] = round($groupData['price'], 2);
                    break;
                case 'azienda estera':
                    $retval['qta_azienda_estera'] = intval($groupData['qty']);
                    $retval['prezzo_azienda_estera'] = round($groupData['price'], 2);
                    break;
                case 'rivenditori italiani':
                    $retval['qta_rivenditori_italiani'] = intval($groupData['qty']);
                    $retval['prezzo_rivenditori_italiani'] = round($groupData['price'], 2);
                    break;
                //case 'rivenditori':
                case 'rivenditori esteri':
                    $retval['qta_rivenditori_esteri'] = intval($groupData['qty']);
                    $retval['prezzo_rivenditori_esteri'] = round($groupData['price'], 2);
                    break;
                case 'privato':
                    $retval['qta_privato'] = intval($groupData['qty']);
                    $retval['prezzo_privato'] = round($groupData['price'], 2);
                    break;
                case 'privato cee':
                    $retval['qta_privato_cee'] = intval($groupData['qty']);
                    $retval['prezzo_privato_cee'] = round($groupData['price'], 2);
                    break;
                case 'privato extra cee':
                    $retval['qta_privato_extra_cee'] = intval($groupData['qty']);
                    $retval['prezzo_privato_extra_cee'] = round($groupData['price'], 2);
                    break;
                default:
                    break;
            }
        }

        return $retval;
    }

    private function areValuesDifferent($float1, $float2) {
        $value1 = $float1 * 100;
        $value1 = intval($value1);
        $value2 = $float2 * 100;
        $value2 = intval($value2);
        $result = $value1 - $value2;
        $result = intval($result);
        return $result != 0;
    }

    public function mustChangeProduct($product, /*$codiceFornitore,*/ $prezzoAcquisto, $price, $specialPrice, 
    $qtaAziendaItaliana, $prezzoAziendaItaliana, $qtaAziendaEstera, $prezzoAziendaEstera, $qtaPrivato, 
    $prezzoPrivato, $qtaPrivatoCee, $prezzoPrivatoCee, $qtaPrivatoExtraCee, $prezzoPrivatoExtraCee, $qtaRivenditoriEsteri,
    $prezzoRivenditoriEsteri, $qtaRivenditoriItaliani, $prezzoRivenditoriItaliani) {
        //if ($product->getData('codice_fornitore') != $codiceFornitore) return true;

        if ($this->areValuesDifferent($product->getData('prezzo_di_acquisto') ? round($product->getData('prezzo_di_acquisto'), 2) : 0, $prezzoAcquisto)) return true;

        if ($this->areValuesDifferent(round($product->getPrice(), 2), $price)) return true;

        if ($this->areValuesDifferent(round($product->getSpecialPrice(), 2), $specialPrice)) return true;

        $tierData = $this->getProductTierPrices($product->getSku());
        foreach ($tierData as $group => $groupData) {
            switch ($group) {
                case 'azienda italiana':
                    if ($this->mustChangeProductGroupData($tierData, $group, $qtaAziendaItaliana, $prezzoAziendaItaliana)) return true;
                    break;
                case 'azienda estera':
                    if ($this->mustChangeProductGroupData($tierData, $group, $qtaAziendaEstera, $prezzoAziendaEstera)) return true;
                    break;
                case 'rivenditori italiani':
                    if ($this->mustChangeProductGroupData($tierData, $group, $qtaRivenditoriItaliani, $prezzoRivenditoriItaliani)) return true;
                    break;
                //case 'rivenditori':
                case 'rivenditori esteri':
                    if ($this->mustChangeProductGroupData($tierData, $group, $qtaRivenditoriEsteri, $prezzoRivenditoriEsteri)) return true;
                    break;
                case 'privato':
                    if ($this->mustChangeProductGroupData($tierData, $group, $qtaPrivato, $prezzoPrivato)) return true;
                    break;
                case 'privato cee':
                    if ($this->mustChangeProductGroupData($tierData, $group, $qtaPrivatoCee, $prezzoPrivatoCee)) return true;
                    break;
                case 'privato extra cee':
                    if ($this->mustChangeProductGroupData($tierData, $group, $qtaPrivatoExtraCee, $prezzoPrivatoExtraCee)) return true;
                    break;
                default:
                    break;
            }
        }

        return false;
    }

    private function mustChangeProductGroupData($tierData, $group, $qtyCompare, $priceCompare) {
        if (!array_key_exists($group, $tierData)) throw new \Exception(sprintf('Missing customer group: %s', $group));
        if ($tierData[$group]['qty'] != $qtyCompare) return true;
        if ($this->areValuesDifferent($tierData[$group]['price'], $priceCompare)) return true;
        return false;
    }
}