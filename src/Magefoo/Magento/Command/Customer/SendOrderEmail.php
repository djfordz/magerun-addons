<?php

namespace Magefoo\Magento\Command\Customer;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SendOrderEmail extends AbstractMagentoCommand
{

    const ENTITY                            = 'order';
    const EMAIL_EVENT_NAME_NEW_ORDER        = 'new_order';
    const XML_PATH_EMAIL_TEMPLATE           = 'sales_email/order/template';
    const XML_PATH_EMAIL_IDENTITY           = 'sales_email/order/identity';


    protected function configure()
    {
        $this
            ->setName('customer:sendorderemail')
            ->setDescription('Test email functionality. Send a customer order email to specified email address. [magefoo]')
            ->addArgument('email', InputArgument::REQUIRED, 'email to send order confirmation to?')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {
            $storeId = \Mage::app()->getStore()->getStoreId();

            $collection = \Mage::getModel('sales/order')->getCollection()
                ->addFieldToFilter('status', 'complete')
                ->addFieldToFilter('status', 'pending');
            $collection->getSelect()->order(new \Zend_Db_Expr('RAND()'));
            $collection->getSelect()->limit(1);

            $order = null;

            foreach($collection as $value) {
                $order = $value;
            }

            if(empty($order)) {
                echo "No orders completed. exiting...\n";
                exit(1);
            }

            $orderId = $order->getRealOrderId();

            $templateId = \Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId);
                
            $emailInfo = \Mage::getModel('core/email_info');
            $emailInfo->addTo($input->getArgument('email'), 'TestEmail');
            
            $mailer = \Mage::getModel('core/email_template_mailer');

            $mailer->addEmailInfo($emailInfo);
            $mailer->setSender(
                \Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY), $storeId
            );
            
            $mailer->setStoreId($storeId);
            $mailer->setTemplateId($templateId);
            $mailer->setTemplateParams(
                array(
                    'order'         => $order,
                    'billing'       => $order->getBillingAddress(),
                    'payment_html'  => null
                )
            );

            $version = \Mage::getVersionInfo();

            if ($version['minor'] >= '9') {
                $emailQueue = \Mage::getModel('core/email_queue');
                $emailQueue->setEntityId($orderId)
                    ->setEntityType(self::ENTITY)
                    ->setEventType(self::EMAIL_EVENT_NAME_NEW_ORDER)
                    ->setIsForceCheck(true);

                try {
                    $mailer->setQueue($emailQueue)->send();
                    echo "Queue Detected, Email added to Queue\n";
                } catch (\Exception $e) {
                    echo "Unable to complete. Error: " . $e->getMessage();
                }
                
            } else {

                try {
                    $mailer->send();
                    echo "No Queue Detected, Sending Transactional Email.\n";
                } catch (\Exception $e) {
                    echo "Unable to complete. Error: " . $e->getMessage();
                }
            }
        }
    }
}
