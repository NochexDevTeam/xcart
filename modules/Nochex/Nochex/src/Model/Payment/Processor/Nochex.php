<?php

namespace Nochex\Nochex\Model\Payment\Processor;

class Nochex extends \XLite\Model\Payment\Base\WebBased
{
     
	public function getOperationTypes()
    {
        return [
            self::OPERATION_SALE,
            self::OPERATION_AUTH,
        ];
    }

    public function getSettingsWidget()
    {
        return 'modules/Nochex/Nochex/config.twig';
    }

  protected function getFormURL()
    {
        return 'https://secure.nochex.com/default.aspx';
    }

    protected function getFormFields()
    {
        $currency = $this->transaction->getCurrency();

		$testTrans = "";
		if ($this->getSetting('testmode') == "TEST"){
			$testTrans = "100";
		}
        	
		foreach ($this->getOrder()->getItems() as $item) {
            $product = $item->getProduct();

            $i++;
            $suffix = $i == 0 ? '' : ('_' . $i);

            $desc .= substr($product->getName(), 0, 127) . ", " . $item->getAmount() . " GBP ";

           /* $fields['c_prod' . $suffix]        = $product->getProductId() . ',' . $item->getAmount();
            $fields['c_name' . $suffix]        = substr($product->getName(), 0, 127);
            $fields['c_price' . $suffix]       = $this->getFormattedPrice($item->getPrice());
            $fields['c_description' . $suffix] = strip_tags(substr(($description), 0, 254));*/
        }
	
        $fields = [
            'amount'        =>  round($this->transaction->getValue(), 2),
            'merchant_id'    => $this->getSetting('merchantid'),
            'order_id'       => $this->getTransactionId(),
            'billing_fullname'     => $this->getProfile()->getBillingAddress()->getFirstname().' '.$this->getProfile()->getBillingAddress()->getLastname(),
            'email_address'    => $this->getProfile()->getLogin(),
            'customer_phone_number'   => $this->getProfile()->getBillingAddress()->getPhone(),
            'description'     => $desc, 
            'billing_address'       => $this->getProfile()->getBillingAddress()->getStreet(),
            'billing_city'       => $this->getProfile()->getBillingAddress()->getCity(),
            'billing_postcode'       => $this->getProfile()->getBillingAddress()->getZipcode(),
            'billing_country'       => $this->getProfile()->getBillingAddress()->getCountry(),
            'delivery_fullname'     => $this->getProfile()->getShippingAddress()->getFirstname().' '.$this->getProfile()->getShippingAddress()->getLastname(),
            'delivery_address'       => $this->getProfile()->getShippingAddress()->getStreet(),
            'delivery_city'       => $this->getProfile()->getShippingAddress()->getCity(),
            'delivery_postcode'       => $this->getProfile()->getShippingAddress()->getZipcode(),
            'delivery_country'       => $this->getProfile()->getShippingAddress()->getCountry(),
            'success_url'     => $this->getReturnURL('order_id', $this->getTransactionId()),
            'test_transaction'     => $testTrans,
            'test_success_url'     => $this->getReturnURL('order_id', $this->getTransactionId()),
            'callback_url'     => $this->getCallbackURL('order_id', $this->getTransactionId())
        ];

        return $fields;
    }

	public function processCallback(\XLite\Model\Payment\Transaction $transaction)
    {
        $this->transaction = $transaction;
        $registerOriginalTransaction = true;
		
				// Get the POST information from Nochex server
		$postvars = http_build_query($_POST);
		ini_set("SMTP","mail.nochex.com" ); 
		$header = "From: apc@nochex.com";

		// Set parameters for the email
		$to = 'james.lugton@nochex.com';
		$url = "https://secure.nochex.com/apc/apc.aspx";

		// Curl code to post variables back
		$ch = curl_init(); // Initialise the curl tranfer
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars); // Set POST fields
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
		$output = curl_exec($ch); // Post back
		curl_close($ch);

		// Put the variables in a printable format for the email
		$debug = "IP -> " . $_SERVER['REMOTE_ADDR'] ."\r\n\r\nPOST DATA:\r\n"; 
		foreach($_POST as $Index => $Value) 
		$debug .= "$Index -> $Value\r\n"; 
		$debug .= "\r\nRESPONSE:\r\n$output";
		 
		//If statement
		if (!strstr($output, "AUTHORISED")) {  // searches response to see if AUTHORISED is present if it isn’t a failure message is displayed
			$msg = "APC was not AUTHORISED.\r\n\r\n$debug";  // displays debug message
			$status = $transaction::STATUS_FAILED;
		} 
		else { 
			$msg = "APC was AUTHORISED.\r\n\r\n$debug"; // if AUTHORISED was found in the response then it was successful
			$status = $transaction::STATUS_SUCCESS;
		}
		
		$transaction->setStatus($status);
        $transaction->registerTransactionInOrderHistory('callback' . $status);
		
		/*$transaction->setNote("Nochex Transaction Id: " . $_POST['transaction_id']);
		$transaction->setNote($msg);
			*/
		return true;
		
	}
			
    /**
     * Get return type
     *
     * @return string
     */
    public function getReturnType()
    {
        return self::RETURN_TYPE_HTML_REDIRECT;
    }
	
	public function getAvailableSettings()
    {
        return [
            'merchantid',
            'testmode'
        ];
    }

	protected function getSetting($name)
    {
        $result = parent::getSetting($name);

        if (is_null($result)) {
            $method = \XLite\Core\Database::getRepo('XLite\Model\Payment\Method')->findOneBy(['service_name' => 'Nochex']);
            $result = $method
                ? $method->getSetting($name)
                : null;
        }

        return $result;
    }


	protected function getNochexPaymentSettings()
    {
        $result = [];

        $fields = $this->getAvailableSettings();

        foreach ($fields as $field) {
            $result[$field] = $this->getSetting($field);
        }

        return $result;
    }

	public function getInputTemplate()
    {
        return 'modules/Nochex/Nochex/checkout/checkout.twig';
    }
}