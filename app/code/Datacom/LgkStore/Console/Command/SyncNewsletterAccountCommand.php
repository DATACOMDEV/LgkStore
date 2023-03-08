<?php

namespace Datacom\LgkStore\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncNewsletterAccountCommand extends \Symfony\Component\Console\Command\Command
{
    protected $_conn;
    protected $_customerRepositoryInterface;

    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
	) {
        $this->_state = $state;
        $this->_conn = $resourceConnection;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;

		parent::__construct();
	}

	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this
			->setName('datacom:syncnewsletteraccountcommand')
			->setDescription('Sincronizza gli account newsletter, esportandoli su Mailup');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
	{
        $this->_state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

        $conn = $this->_conn->getConnection();

        //$rows = $conn->fetchAll('SELECT newsletter_subscriber.subscriber_email, newsletter_subscriber.customer_id, newsletter_subscriber.subscriber_id, customer_entity.group_id FROM newsletter_subscriber INNER JOIN customer_entity ON customer_entity.entity_id=newsletter_subscriber.customer_id WHERE subscriber_status=1 AND subscriber_id NOT IN (SELECT subscriber_id FROM dtm_newsletter_sync)');

        $rows = $conn->fetchAll('SELECT customer_entity.entity_id, customer_entity.email, customer_entity.group_id FROM customer_entity WHERE entity_id NOT IN (SELECT subscriber_id FROM dtm_newsletter_sync)');

        foreach ($rows as $r) {
            try {
                $this->syncAccountData($r['email'], intval($r['group_id']));

                $output->writeln("E-MAIL: ".$r['email']);
                
                $query = 'INSERT INTO dtm_newsletter_sync (subscriber_id) VALUES ('.$r['entity_id'].')';

                $conn->query($query);
                //break;
            } catch (\Exception $ex) {
                //throw $ex;
            }
        }
    }

    protected function syncAccountData($email, $idGruppoMagento) {
        //https://devdocs.magento.com/guides/v2.4/get-started/gs-curl.html
        //https://aureatelabs.com/magento-2/how-to-use-curl-in-magento-2/
        /*if (!empty($customerId)) {
            $customer = $this->_customerRepositoryInterface->getById($customerId);
            echo $customer->getFirstname()."\r\n";
        } else {
            echo $email."\r\n";
        }*/
        $this->postMailUpRequest($email, $idGruppoMagento);
    }

    protected function postMailUpRequest($mail, $idGruppoMagento) {
        switch ($idGruppoMagento) {
            case 4: //Rivenditori italiani
                $idGruppoMailup = 161;
                break;
            case 5: //Rivenditori esteri
                $idGruppoMailup = 160;
                break;
            case 6: //Rivenditori
                $idGruppoMailup = 159;
                break;
            case 7: //Privato CEE
                $idGruppoMailup = 155;
                break;
            case 8: //Privato
                $idGruppoMailup = 154;
                break;
            case 9: //Azienda italiana
                $idGruppoMailup = 158;
                break;
            case 10: //Azienda estera
                $idGruppoMailup = 157;
                break;
            case 12: //Privato extra cee
                $idGruppoMailup = 156;
                break;
            default:
                $idGruppoMailup = 162;
                break;
        }

        //list -> lista mail up in cui inserire il messaggio
        //group -> gruppo specifico dentro una lista di mailup (id numerici gruppi, eventualmente separati da virgola)
        //email -> email address
		$pars = array(
			'email' => $mail,
			'list' => 3,
			'group' => $idGruppoMailup,
		);

		$curlSES=curl_init(); 

		curl_setopt($curlSES,CURLOPT_URL,"a0e4x.emailsp.net/frontend/subscribe.aspx");
		curl_setopt($curlSES,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curlSES,CURLOPT_HEADER, false); 
		curl_setopt($curlSES, CURLOPT_POST, true);
		curl_setopt($curlSES, CURLOPT_POSTFIELDS,$pars);
		curl_setopt($curlSES, CURLOPT_CONNECTTIMEOUT,10);
		curl_setopt($curlSES, CURLOPT_TIMEOUT,30);

		$result=curl_exec($curlSES);

        curl_close($curlSES);

        //echo "RISPOSTA 1: ".$result."\r\n";
        
        $parsedResponse = explode('href', $result)[1];
        $parsedResponse = explode('"', $parsedResponse)[1];

        $curlSES=curl_init(); 

		curl_setopt($curlSES,CURLOPT_URL,"a0e4x.emailsp.net" . $parsedResponse);
		curl_setopt($curlSES,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curlSES,CURLOPT_HEADER, false);
		curl_setopt($curlSES, CURLOPT_CONNECTTIMEOUT,10);
		curl_setopt($curlSES, CURLOPT_TIMEOUT,30);

		$result=curl_exec($curlSES);

        curl_close($curlSES);

        //echo "RISPOSTA 2: ".$result."\r\n";
	}
}