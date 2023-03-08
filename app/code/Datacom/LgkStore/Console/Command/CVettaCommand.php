<?php

namespace Datacom\LgkStore\Console\Command;

class CVettaCommand extends \Symfony\Component\Console\Command\Command {
    
    private $_cVettaExporter;

    public function __construct(
        \Ittweb\Cvetta\Cron\Exporter $cVettaExporter,
        \Magento\Framework\App\State $state
	) {
        parent::__construct();
        
        $this->_cVettaExporter = $cVettaExporter;
        $this->_state = $state;
    }
    
    protected function configure()
	{
        $options = array(
			new \Symfony\Component\Console\Input\InputOption(
				'store',
				null,
				\Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED,
				'Store id'
			)
        );

        $this->setName('datacom:cvettacommand')->setDescription('Genera il feed di cVetta')->setDefinition($options);
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
	{
        $this->_state->setAreaCode('adminhtml');

        $storeId = $input->getOption('store');

        if (empty($storeId)) {
            $output->writeln('<error>Missing store id</error>');
        }

        if ($storeId == 'all') {
            $this->_cVettaExporter->export();
        } else {
            $this->_cVettaExporter->exportSingleStore($storeId);
        }
	}
}