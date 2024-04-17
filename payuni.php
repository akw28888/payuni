	<?php

	if (!defined("WHMCS")) {
		die("This file cannot be accessed directly");
	}

	function payuni_MetaData()
	{
		return array(
			'DisplayName' => 'payuni',
			'APIVersion' => '1.0', // Use API Version
			'DisableLocalCreditCardInput' => true,
			'TokenisedStorage' => false,
		);
	}

	function payuni_config()
	{
		return array(
			// the friendly display name for a payment gateway should be
			// defined here for backwards compatibility
			'FriendlyName' => array(
				'Type' => 'System',
				'Value' => 'Payuni',
			),
			// a text field type allows for single line text input
			'MerID' => array(
				'FriendlyName' => 'MerID (商店代號)',
				'Type' => 'text',
				'Size' => '25',
				'Default' => '',
				'Description' => '在此處輸入您的商店代號',
			),
			// a password field type allows for masked text input
			'hashKey' => array(
				'FriendlyName' => 'Hash Key',
				'Type' => 'password',
				'Size' => '25',
				'Default' => '',
				'Description' => '在此輸入您生成的 Hash Key',
			),
			'hashIV' => array(
				'FriendlyName' => 'Hash IV',
				'Type' => 'password',
				'Size' => '25',
				'Default' => '',
				'Description' => '在此輸入您生成的 Hash IV',
			),
			// the yesno field type displays a single checkbox option
			'testMode' => array(
				'FriendlyName' => '測試模式',
				'Type' => 'yesno',
				'Description' => '勾選以啟用測試模式。',
			),
		);
	}

	function payuni_link($params)
	{
		// 網關參數
		$MerID = $params['MerID'];
		$hashKey = $params['hashKey'];
		$hashIV = $params['hashIV'];
		$testMode = $params['testMode'];

		// 帳單參數
		$invoiceId = $params['invoiceid'];
		$description = $params["description"];
		//由於支付接口不支援小數金額，因此取整數金額。
		$amount = explode('.',$params['amount']);

		// 客戶參數
		$firstname = $params['clientdetails']['firstname'];
		$lastname = $params['clientdetails']['lastname'];
		$email = $params['clientdetails']['email'];
		$address1 = $params['clientdetails']['address1'];
		$address2 = $params['clientdetails']['address2'];
		$city = $params['clientdetails']['city'];
		$state = $params['clientdetails']['state'];
		$postcode = $params['clientdetails']['postcode'];
		$country = $params['clientdetails']['country'];
		$phone = $params['clientdetails']['phonenumber'];

		// 系統參數
		$companyName = $params['companyname'];
		$systemUrl = $params['systemurl'];
		$returnUrl = $params['returnurl'];
		$langPayNow = $params['langpaynow'];
		$moduleDisplayName = $params['name'];
		$moduleName = $params['paymentmethod'];
		$whmcsVersion = $params['whmcsVersion'];

		//是否為測試模式
		if($testMode == true) {
			$url = 'https://sandbox-api.payuni.com.tw/api/upp';
		} else {
			$url = 'https://api.payuni.com.tw/api/upp';
		}

		//交易參數
		$postfields = array();
		$postfields = array(
			'MerID' => $MerID,
			'MerTradeNo' => $invoiceId,
			'TradeAmt' => $amount['0'],
			'Timestamp' => time(),
			'ReturnURL' => $returnUrl,
			'NotifyURL' => $systemUrl .'modules/gateways/callback/payuni.php',
			'UsrMail' => $email,
			'UsrMailFix' => 1,
			'ProdDesc' => $params["description"],
		);
		$aesData = getAES($postfields, $hashKey, $hashIV);
		$sha256Data = aes_sha256_str($aesData, $hashKey, $hashIV);

		//需要傳送的參數
		$transData = array(
			'MerID' => $MerID,
			'Version' => '1.0',
			'EncryptInfo' => $aesData,
			'HashInfo' => $sha256Data,
		);

		//生成需要提交的表單
		$htmlOutput = '<form method="post" action="' . $url . '">';
		foreach ($transData as $key => $value) {
			$htmlOutput .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
		}
		$htmlOutput .= '<input class="btn btn-success" type="submit" value="' . $langPayNow . '" />';
		$htmlOutput .= '</form>';

		return $htmlOutput;
	}

	//AES 加密
	function getAES($postData, $hashKey, $hashIV) 
	{
		$tag = ""; //預設為空
		$encrypted = openssl_encrypt(http_build_query($postData), "aes-256-gcm", trim($hashKey), 0, trim($hashIV), $tag);
		return trim(bin2hex($encrypted . ":::" . base64_encode($tag)));
	}
	function addpadding($string, $blocksize = 32) 
	{
		$len = strlen($string);
		$pad = $blocksize - ($len % $blocksize); 
		$string .= str_repeat(chr($pad), $pad); 
		return $string;
	}
	//SHA256 加密
	function aes_sha256_str($aesData, $hashKey, $hashIV)
	{
		return strtoupper(hash("sha256", "$hashKey$aesData$hashIV"));
	}

