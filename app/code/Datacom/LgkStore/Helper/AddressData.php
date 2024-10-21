<?php

namespace Datacom\LgkStore\Helper;

class AddressData extends \Magento\Framework\App\Helper\AbstractHelper {
    
    protected $_store;
    protected $_encoder;
    protected $_resourceConnection;

    protected $ceeIds = null;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $store,
        \Magento\Framework\Url\EncoderInterface $encoder,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->_store = $store;
        $this->_encoder = $encoder;
        $this->_resourceConnection = $resourceConnection;

        parent::__construct($context);

        //$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }

    public function get($quoteId) {
        $conn = $this->_resourceConnection->getConnection();
        $query = sprintf(
            'SELECT shipping_c_fiscale, billing_c_fiscale, shipping_codice_sdi_pec, billing_codice_sdi_pec FROM %s WHERE quote_id=:quote_id',
            \Datacom\LgkStore\Model\Constants::TABLE_QUOTE_ATTRIBUTES
        );
        $bind = [
            'quote_id' => $quoteId
        ];
        $results = $conn->fetchAll($query, $bind);

        if (!$results) return null;

        foreach ($results as $r) {
            return $r;
        }

        return null;
    }

    public function save($quoteId, $shippingCFisc, $billingCFisc, $shippingSdiPec, $billincSdiPec) {
        $conn = $this->_resourceConnection->getConnection();

        try {
            $query = sprintf(
                'INSERT INTO %s (quote_id, shipping_c_fiscale, billing_c_fiscale, shipping_codice_sdi_pec, billing_codice_sdi_pec) 
                VALUES (:quote_id, :shipping_c_fiscale, :billing_c_fiscale, :shipping_codice_sdi_pec, :billing_codice_sdi_pec)
                ON DUPLICATE KEY UPDATE shipping_c_fiscale=:shipping_c_fiscale, billing_c_fiscale=:billing_c_fiscale, shipping_codice_sdi_pec=:shipping_codice_sdi_pec, billing_codice_sdi_pec=:billing_codice_sdi_pec',
                \Datacom\LgkStore\Model\Constants::TABLE_QUOTE_ATTRIBUTES
            );
            $bind = [
                ':quote_id' => $quoteId,
                ':shipping_c_fiscale' => $shippingCFisc,
                ':billing_c_fiscale' => $billingCFisc,
                ':shipping_codice_sdi_pec' => $shippingSdiPec,
                ':billing_codice_sdi_pec' => $billincSdiPec
            ];
            $conn->query($query, $bind);
        } catch (\Exception $ex) {
            throw $ex;
        } finally {
            $this->_resourceConnection->closeConnection();
        }
    }
}