<?php declare(strict_types=1);

namespace NetzreichExport\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;

class ExportCommand extends Command
{
    protected static $defaultName = 'netzreich:csv';#

    protected $day = "";

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;
 

    public function __construct(EntityRepositoryInterface $orderRepository, UrlGeneratorInterface $urlGenerator)
    {
        // best practices recommend to call the parent constructor first and
        // then set your own properties. That wouldn't work in this case
        // because configure() needs the properties set in this constructor
       
        $this->orderRepository = $orderRepository;
        $this->urlGenerator = $urlGenerator;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addArgument('begin', InputArgument::OPTIONAL, 'Ordernumber Start')
            ->addArgument('end', InputArgument::OPTIONAL, 'Ordernumber End')
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $begin = $input->getArgument('begin');
        $end = $input->getArgument('end');
        $key = $begin . "-" . $end;

        $output->writeln('Export Bestellungen von-bis: ' . $key);


        $filter =  new RangeFilter('orderNumber', [
            RangeFilter::GTE => $begin,
            RangeFilter::LTE => $end
        ]);

        /** @var EntityCollection $entities */
        $entities = $this->orderRepository->search(
            (new Criteria())->addFilter($filter)->addAssociation('documents.documentType')->addAssociation('documents.documentMediaFile')->addAssociation('transactions.paymentMethod'),
            \Shopware\Core\Framework\Context::createDefaultContext()
        );
        $path = 'exporte/'.$key;
        try {
            mkdir( $path, 0777, true );
            mkdir( $path. '/pdfs', 0777, true);
        }catch (\Exception $e) {
            echo "Verzeichnis konnte nicht angelegt werden"; 
        }
        $fp = fopen($path.'/orders.csv', 'w'); 
        $header= ["Bruttobetrag", "SOLL/HABEN", "Konto (Erlöskonto)", "Gegenkonto (Debitor)", "Belegdatum", "Belegnummer", "Buchungstext", "Zahlungsart", "Dokument"];
        $delimiter=";";
        fputcsv($fp, $header, $delimiter);
        foreach($entities as $entity) {
            $invoices  = [];
            if ($entity->getDocuments() !== null) {
                $invoices =  $entity->getDocuments()->filter( function ($doc) use ($output) {
                    if ($doc->getDocumentType() !== null) {
                        $type = $doc->getDocumentType()->getTechnicalName();
                        return $type == "invoice" || $type == 'storno';
                    }
                    return false;
                });
            }
            $transaction  = "";
            if ($entity->getTransactions() !== null) {
                $transaction = $entity->getTransactions()->first()->getPaymentMethod()->getName();
            }

            if (count($invoices) > 0 ) {
                foreach($invoices as $invoice) {
                    $output->writeln('Entity: ' . $entity->getOrderCustomer()->getCustomerNumber() . "--" . $entity->getOrdernumber() . ' : '. $entity->getAmountTotal());
                    if ( $invoice->getDocumentMediaFile() !== null) {
						$documentDate = $invoice->getDocumentMediaFile()->getCreatedAt();
                        $oldpath = $this->urlGenerator->getRelativeMediaUrl($invoice->getDocumentMediaFile());
                        $output->writeln($oldpath);
                        $pwd = getenv('PWD').'/files/';
                        $type = ($invoice->getDocumentType()->getTechnicalName() == 'storno') ? $invoice->getConfig()['custom']['stornoNumber'] : $invoice->getConfig()['custom']['invoiceNumber'];
                        copy($pwd.$oldpath,  getenv('PWD').'/'.$path. '/pdfs/'. $invoice->getDocumentType()->getTechnicalName().'_'.$type.'_'.$entity->getOrdernumber().'_'.$entity->getOrderCustomer()->getCustomerNumber().'_'.$transaction.'.pdf');
                    }
                    $amount = $entity->getAmountTotal()*1;
//                    $amount = str_replace('.',',',$amount);
					// date_default_timezone_set("Europe/Berlin");
					// $documentDate = $invoice->getDocumentMediaFile()->getCreatedAt();
                    $debit = "0";
                    $output->writeln('Type: ' .$entity->getTransactions()->first()->getPaymentMethod()->getName());
                    switch($entity->getTransactions()->first()->getPaymentMethod()->getName()) {
                        case "SOFORT Banking":
                            $debit = "111113";
                            break;
                        case "SOFORT Überweisung":
                            $debit = "111113";
                            break;
                        case "Kredit- oder Debitkarte":
                            $debit = "111113";
                            break;
                        case "SEPA Lastschrift":
                            $debit = "111112";
                            break;
                        case "Rechnungskauf":
                            $debit = "111112";
                            break;
                        case "Kreditkarte":
                            $debit = "111113";
                            break;
                        case "Credit card":
                            $debit = "111113";
                            break;
                        case "PayPal":
                            $debit = "111112";
                            break;
                        case "Mollie PayPal":
                            $debit = "111112";
                            break;
                        case "Kauf auf Rechnung":
                            $debit = "111112";
                            break;
//                        case ("SOFORT Banking" || "Kreditkarte" || "Credit card"):
//                            $debit = "111113";
//                            break;
//                        case ("PayPal" || "Mollie PayPal" || "Kauf auf Rechnung"):
//                            $debit = "111112";
//                            break;
			case "Vorkasse":
                            $debit = "111114";
                            break;
                        case "Lastschrift":
                            $debit = "111115";
                            break;
                        case "Rechnung":
                            $debit = "111116";
                            break;
                        case "Amazon Pay":
                            $debit = "111117";
                            break;
                        case "Google Pay":
                            $debit = "111118";
                            break;
                        case "Apple Pay":
                            $debit = "111119";
                            break;
			case "CHECK24":
			    $debit = "111120";
			    break;
                        default:
                            break;
                    };
		    $output->writeln('Konto: ' .$debit . " " . 'Zahlungsart: ' .$transaction);
		    fputcsv($fp, [
                        ($invoice->getDocumentType()->getTechnicalName() == 'storno') ? '-'.$amount :''.$amount,
                        "H",
                        "44020",
                        $debit,
                        $invoice->getDocumentMediaFile() == null ? "" : $documentDate->format('dmy'), //$documentDate->format('dmy, H:i'),
                        ($invoice->getDocumentType()->getTechnicalName() == 'storno') ? $invoice->getConfig()['custom']['stornoNumber'] : $invoice->getConfig()['custom']['invoiceNumber'],
			"A: ". $entity->getOrdernumber()." | K: ". $entity->getOrderCustomer()->getCustomerNumber()." | N: ".$entity->getOrderCustomer()->getFirstName()." ".$entity->getOrderCustomer()->getLastName(),
			$transaction,
			// $entity->getOrderCustomer()->getCustomerName(),
			$invoice->getDocumentMediaFile() !== null ? "PDF" : "no PDF"
                    ], $delimiter);
            	}
            }
        }
        fclose($fp);
        $output->writeln('Anzahl Bestellungen: ' . count($entities));
        
        // Fehlermeldung in der Console verhindern
        return 1;
    }
}
