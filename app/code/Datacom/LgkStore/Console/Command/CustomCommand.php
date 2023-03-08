<?php

namespace Datacom\LgkStore\Console\Command;

class CustomCommand extends \Symfony\Component\Console\Command\Command {
    
    public function __construct(
        \Magento\Framework\App\State $state
	) {
        parent::__construct();
        
        $this->_state = $state;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->_resourceConnection = $objectManager->create('Magento\Framework\App\ResourceConnection');
        $this->_productRepository = $objectManager->create('Magento\Catalog\Model\ProductRepository');
        $this->_tierPriceStorageInterface = $objectManager->create('Magento\Catalog\Api\TierPriceStorageInterface');
        $this->_categoryRepository = $objectManager->create('Magento\Catalog\Model\CategoryRepository');
    }
    
    protected function configure()
	{
        $this->setName('datacom:customcommand')->setDescription('Comando da console customizzato per esecuzione procedure di volta in volta');
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
	{
        $this->_state->setAreaCode('adminhtml');

        //$this->exportProductsWithResellerHigherPrice($output);
        $this->exportProductsWithResellerMinimumQuantity($output);
    }
    
    private function exportProductsWithResellerHigherPrice($output) {
        $conn = $this->_resourceConnection->getConnection();

        $productSkus = $conn->fetchCol('SELECT sku FROM catalog_product_entity WHERE sku <> \'\'');
        
        $output->writeln('sku,descrizione,base,rivenditore');

        foreach ($productSkus as $pSku)
        {
            $product = $this->_productRepository->get($pSku);

            $price = $product->getPrice();

            $tierPrices = $product->getTierPrices();

            $alreadySeenTierPrices = [];
            foreach ($tierPrices as $tp) {
                $tpData = $tp->getData();

                if (!in_array($tpData['customer_group_id'], [4, 5, 6])) continue;
                if ($tpData['value'] < $price) continue;
                if (in_array($tpData['value'], $alreadySeenTierPrices)) continue;

                $rowItems = [
                    '"'.str_replace([',', '"'], ['-', ''], $pSku).'"',
                    '"'.str_replace([',', '"'], ['-', ''], $product->getName()).'"',
                    number_format($price, 2, '.', ''),
                    number_format($tpData['value'], 2, '.', '')
                ];

                $output->writeln(implode(',', $rowItems));

                $alreadySeenTierPrices[] = $tpData['value'];
            }
        }
    }

    private function exportProductsWithResellerMinimumQuantity($output) {
        $conn = $this->_resourceConnection->getConnection();

        $productSkus = $conn->fetchAll('SELECT catalog_product_entity.sku, catalog_category_product.category_id FROM catalog_product_entity INNER JOIN catalog_category_product ON catalog_category_product.product_id=catalog_product_entity.entity_id WHERE sku <> \'\' AND catalog_category_product.category_id IN (613, 614, 615, 616, 617, 618, 619, 620, 621, 622)');
        
        $output->writeln('qta,codice,descrizione');

        foreach ($productSkus as $data)
        {
            $pSku = $data['sku'];
            $catId = $data['category_id'];

            $product = $this->_productRepository->get($pSku);
            $category = $this->_categoryRepository->get($catId);
            
            $rowItems = [
                $category->getName(),
                '"'.str_replace([',', '"'], ['-', ''], $pSku).'"',
                '"'.str_replace([',', '"'], ['-', ''], $product->getName()).'"',
            ];

            $output->writeln(implode(',', $rowItems));
        }
    }
}