<?php

namespace Datacom\LgkStore\Console\Command;

class RestoreCategoriesMetaDataCommand extends \Symfony\Component\Console\Command\Command {
    
    protected $_categoryCollectionFactory;
    protected $_csv;
    protected $_store;

    protected $_objectManager;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Framework\File\Csv $csv
	) {
        parent::__construct();
        
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_csv = $csv;
        
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->_store = $this->_objectManager->create('Magento\Store\Model\StoreManagerInterface');
    }
    
    protected function configure()
	{
        $this->setName('datacom:restorecategoriesmetadatacommand')->setDescription('Reintegra dati meta sulle categorie');
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
	{
        //$this->_state->setAreaCode('adminhtml');

        $mapping = dirname(__FILE__).'/lgkstore.meta-categories.mapping.csv';
        $file = dirname(__FILE__).'/lgkstore.meta-categories.csv';

        $rows = $this->_csv->getData($file);
        $mapping = $this->_csv->getData($mapping);

        $activeCategoriesIds = $this->_categoryCollectionFactory->create()
                ->addIsActiveFilter()
                ->getColumnValues('entity_id');

        foreach ($rows as $index => $data) {
            //$this->_store->setCurrentStore($data[1]);
            $curCatName = null;

            foreach ($mapping as $index => $datam) {
                if ($datam[0] == $data[3]) {
                    $curCatName = $datam[1];
                    break;
                }
            }

            if (empty($curCatName)) {
                continue;
            }

            $categories = $this->_categoryCollectionFactory->create()
                ->addAttributeToFilter('name', $curCatName);
            
            if ($categories->getSize() == 0) {
                continue;
            }

            if ($categories->getSize() > 1) {
                $output->writeln('PROBLEMA: "'.$curCatName.'"');
                continue;
            }

            $valueToUse = $data[2];
            $valueToUse = trim($valueToUse);
            $valueToUse = str_replace(
                ['\''],
                ['\'\''],
                $valueToUse
            );
            foreach ($categories as $cat) {
                if (!in_array($cat->getId(), $activeCategoriesIds)) {
                    continue;
                }
                
                if ($data[0] == 46) {
                    $tablename = 'catalog_category_entity_varchar';
                } else {
                    $tablename = 'catalog_category_entity_text';
                }

                $output->writeln('UPDATE '.$tablename.' SET value=\''.$valueToUse.'\' WHERE attribute_id='.$data[0].' AND store_id='.$data[1].' AND entity_id='.$cat->getId().';');
            }
            //$data[0]  Attribute id
            //$data[1]  Id store prodotto
            //$data[2]  Value
            //$data[3]  Vecchia entity id
        }
	}
}