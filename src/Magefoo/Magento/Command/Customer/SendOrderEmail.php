<?php

namespace Magefoo\Magento\Command\Customer;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendOrderEmail extends AbstractMagentoCommand
{

    const ENTITY                            = 'order';
    const EMAIL_EVENT_NAME_NEW_ORDER        = 'new_order';
    const XML_PATH_EMAIL_TEMPLATE           = 'sales_email/order/template';
    const XML_PATH_EMAIL_IDENTITY           = 'sales_email/order/identity';


    protected function configure()
    {
        $this
            ->setName('customer:sendtransemail')
            ->setDescription('Test email functionality. Send a customer order email to specified email address. [magefoo]')
            ->addOption('email', 'e', InputOption::VALUE_REQUIRED, 'email to send order confirmation to?', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $storeId = \Mage::app()->getStore()->getStoreId();

        $collection = \Mage::getModel('sales/order_collection')->getCollection()
            ->addFieldToFilter('status', 'complete');
        $collection->getSelect()->order(new Zend_Db_Expr('RAND()'));
        $order = $collection->getSelect()->limit(1);

        $orderId = $order->getRealOrderId();

        $templateId = \Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId);

        $mailer = \Mage::getModel('core/email_template_mailer');
        $emailInfo = \Mage::getModel('core/email_info');
        $emailInfo->addTo($input->getOption('email'), 'TestEmail');
        
        $mailer->addEmailInfo($emailInfo);
        $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY), $storeId);
        $mailer->setStoreId($storeId);
        $mailer->setTemplateId($templateId);
        $mailer->setTemplateParams(
            array(
                'order'         => $collection,
                'billing'       => $collection->getBillingAddress(),
                'payment_html'  => null
            )
        );

        $emailQueue = \Mage::getModel('core/email_queue');
        $emailQueue->setEntityId($orderId)
            ->setEntityType(self::ENTITY)
            ->setEventType(self::EMAIL_EVENT_NAME_NEW_ORDER)
            ->setIsForceCheck(true);

        $mailer->setQueue($emailQueue)->send();

        echo "Email sent to" . $input->getOption('email') . "\n";
    }
}
