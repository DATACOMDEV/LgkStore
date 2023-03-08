<?php
/**
 * Ittweb Cvetta Search Engine for Magento 2
 *
 * @category    Site Search & Navigation
 * @package     Ittweb_Cvetta
 * @author      Ittweb Team <mfantetti@ittweb> <sanedda@ittweb.net>
 * @copyright   Copyright (c) 2020 - Ittweb (https://ittweb.net)
 */

namespace Ittweb\Cvetta\Cron;

use Ittweb\Cvetta\Constants;
use Ittweb\Cvetta\Helper\Data;
use Ittweb\Cvetta\Logger\Logger;
use Ittweb\Cvetta\Model\Connection;
use Ittweb\Cvetta\Model\FeedFile;
use Ittweb\Cvetta\Model\FeedProducts;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Image\Factory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Calculation;

/**
 * Class Exporter
 * @package Ittweb\Cvetta\Cron
 */
class Exporter
{
    const FEED_DIRECTORY = '/feed';

    public $media_url;
    public $placeholder;
    public $media_url_cat_prod;
    public $media_url_server;

    private $_notificationsList = array();

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Calculation
     */
    protected $taxCalculation;

    /**
     * @var Factory
     */
    protected $imageFactory;

    /**
     * @var DirectoryList
     */
    protected $dir;

    /**
     * @var DeploymentConfig
     */
    protected $deploymentConfig;

    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var FeedProducts
     */
    private $_feedProducts;

    /**
     * @var FeedFile
     */
    private $_feedFile;

    /**
     * @var Connection
     */
    private $_connection;

    /**
     * @var Data
     */
    private $_helper;

    /**
     * @var Logger
     */
    private $_ittLogger;

    /**
     * Exporter constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Calculation $taxCalculation
     * @param Factory $imageFactory
     * @param DirectoryList $dir
     * @param FeedProducts $feedProducts
     * @param FeedFile $feedFile
     * @param Connection $connection
     * @param Data $helper
     * @param Logger $ittLogger
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ScopeConfigInterface $scopeConfig,
        Calculation $taxCalculation,
        Factory $imageFactory,
        DirectoryList $dir,
        StoreManagerInterface $storeManager,
        FeedProducts $feedProducts,
        FeedFile $feedFile,
        Connection $connection,
        Data $helper,
        Logger $ittLogger,
        DeploymentConfig $deploymentConfig
    ) {
        $this->imageFactory = $imageFactory;
        $this->resourceConnection = $resourceConnection;
        $this->scopeConfig = $scopeConfig;
        $this->taxCalculation = $taxCalculation;
        $this->dir = $dir;
        $this->deploymentConfig = $deploymentConfig;

        $this->_storeManager = $storeManager;
        $this->_feedProducts = $feedProducts;
        $this->_feedFile = $feedFile;
        $this->_connection = $connection;
        $this->_helper = $helper;
        $this->_ittLogger = $ittLogger;
    }

    private function exportStore($store) {
        // admin store
        if ($store->getId() == 0) {
            return;
        }
        $storeDebug = $store->getCode() . ' - ' . $store->getName();

        $moduleStatus =
            $this->_helper->isSetConfig(
                Constants::PATH_EXTENSION_STATUS,
                ScopeInterface::SCOPE_STORE,
                $store->getCode()
            );
        if (!$moduleStatus) {
            $msg = "Extension disabled for Store View: $storeDebug";
            $this->_ittLogger->warning($msg);
            $this->_notificationsList[] = $msg;
            return;
        }

        $msg = "Export :: Start for Store View: $storeDebug";
        $this->_ittLogger->debug($msg);
        $this->_notificationsList[] = $msg;

        /* get feed products */
        $ret = $this->_feedProducts->getFeedProducts($store);
        if (!$ret["success"]) {
            // deleting lock file
            $this->_helper->fileLockDelete();
            $this->_ittLogger->error($ret["message"]);
            $this->_notificationsList[] = $ret["message"];
            return;
        }
        // feed products
        $_feedProducts = $ret["feedProducts"];

        /* write feed file */
        $ret = $this->_feedFile->writeFeedFile(self::FEED_DIRECTORY, $store, $_feedProducts);
        if (!$ret["success"]) {
            // deleting lock file
            $this->_helper->fileLockDelete();
            $this->_ittLogger->error($ret["message"]);
            $this->_notificationsList[] = $ret["message"];
            return;
        }
        $cvettaRemoteFile = $ret["cvettaRemoteFile"];
        $cvettaLocalFile = $ret["cvettaLocalFile"];

        /* export feed file */
        $ret = $this->_connection->transferFtpFeedFile($cvettaRemoteFile, $cvettaLocalFile, $store);
        if (!$ret["success"]) {
            // deleting lock file
            $this->_helper->fileLockDelete();
            $this->_ittLogger->error($ret["message"]);
            $this->_notificationsList[] = $ret["message"];
            return;
        }

        $msg = "Export :: End for Store View: $storeDebug";
        $this->_ittLogger->debug($msg);
        $this->_notificationsList[] = $msg;
        /*
            // notifications
            if (!empty($this->_notificationsList))
                $ret = $this->_notifications->sendNotifications($this->_notificationsList, $store);
            if (!$ret["success"]) {
                // deleting lock file
                $this->_helper->fileLockDelete();
                $this->_itt_ittLogger->error($ret["message"]);
                return false;
            }
        */
    }

    public function export()
    {
        // check extension script execution
        $result = $this->_helper->fileLockCheck();
        if (!$result["success"]) {
            $this->_ittLogger->error($result["message"]);
            $this->_notificationsList[] = $result["message"];
            return false;
        }

        // getting all store view
        $stores = $this->_storeManager->getStores();

        foreach ($stores as $store) {
            $this->exportStore($store);
        }

        // deleting lock file
        $this->_helper->fileLockDelete();

        return true;
    }

    public function exportSingleStore($storeId)
    {
        // check extension script execution
        $result = $this->_helper->fileLockCheck();
        if (!$result["success"]) {
            $this->_ittLogger->error($result["message"]);
            $this->_notificationsList[] = $result["message"];
            return false;
        }

        // getting all store view
        $stores = $this->_storeManager->getStores();

        foreach ($stores as $store) {
            if ($store->getId() != $storeId) {
                continue;
            }
            $this->exportStore($store);
        }

        // deleting lock file
        $this->_helper->fileLockDelete();

        return true;
    }
}
