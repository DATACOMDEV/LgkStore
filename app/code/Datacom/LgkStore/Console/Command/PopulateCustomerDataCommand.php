<?php

namespace Datacom\LgkStore\Console\Command;

class PopulateCustomerDataCommand extends \Symfony\Component\Console\Command\Command {
    
    protected $_csv;
    protected $_customerApi;

    protected $_objectManager;

    public function __construct(
        \Magento\Framework\File\Csv $csv,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerApi
	) {
        parent::__construct();

        $this->_csv = $csv;
        $this->_customerApi = $customerApi;
        
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }
    
    protected function configure()
	{
        $this->setName('datacom:populatecustomerdatacommand')->setDescription('Valorizza i dati restanti dei clienti');
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
	{
        //if (!$this->_state->getAreaCode()) {
            //$this->_state->setAreaCode('adminhtml');
        //}

        $rows = $this->_csv->getData(dirname(__FILE__).'/lgkstore.customers.csv');

        foreach ($rows as $index => $data) {
            if (count($data) < 3 || empty($data[0])) {
                continue;
            }

            try {
                $customer = $this->_customerApi->get($data[0]);
            } catch (\Throwable $th) {
                continue;
            }

            $mustSave = false;

            //fax: $data[1]
            $customerData = unserialize($data[2]);
            //piva: $customerData[1]
            //Nome azienda: $customerData[2], lo possiamo ignorare perché l'import se n'è già occupato
            //Codice fiscale: $customerData[3]
            //Codice univoco: $customerData[4]
            //Codice id (passaporto o id card): $customerData[5]

            $output->writeln($customer->getEmail());

            $customAttributesDataInfo = array(
                'fax' => empty($data[1]) ? null : $data[1],
                'taxvat' => array(
                    'get' => 'return $customer->getTaxvat();',
                    'set' => '$customer->setTaxvat(\'#VALUE#\');',
                    'newvalue' => empty($customerData[1]) ? null : $customerData[1]
                ),
                'c_fiscale' => empty($customerData[3]) ? null : $customerData[3],
                'codice_univoco' => empty($customerData[4]) ? null : $customerData[4],
                'codice_id' => empty($customerData[5]) ? null : $customerData[5]
            );

            foreach ($customAttributesDataInfo as $attrCode => $newItemData) {
                if (is_array($newItemData)) {
                    $curAttributeValue = eval($newItemData['get']);
                    $newValue = $newItemData['newvalue'];
                } else {
                    $curAttributeValue = $customer->getCustomAttribute($attrCode);

                    if ($curAttributeValue) {
                        $curAttributeValue = $curAttributeValue->getValue();
                    }

                    $newValue = $newItemData;
                }

                if (!empty($newValue) && (!$curAttributeValue ||
                $newValue != $curAttributeValue)) {
                    if ($curAttributeValue) {
                        $output->writeln($attrCode.' CLIENTE: '.$curAttributeValue);
                    }
                    $output->writeln($attrCode.' NUOVO: '.$newValue);
                    if (is_array($newItemData)) {
                        eval(\str_replace('#VALUE#', $newValue, $newItemData['set']));
                    } else {
                        $customer->setCustomAttribute($attrCode, $newValue);
                    }
                    $mustSave = true;
                }
            }

            if ($mustSave) {
                try {
                    $this->_customerApi->save($customer);
                } catch (\Throwable $th) {
                    $output->writeln('ERRORE: Salvataggio cliente '.$customer->getEmail().'. '.$th->getMessage());
                }                
            }

            $output->writeln('----------------');
        }
	}
}