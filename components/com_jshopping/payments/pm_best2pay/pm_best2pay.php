<?php
/*
* @info Платёжный модуль Best2Pay для JoomShopping
* @package JoomShopping for Joomla!
* @subpackage payment
* @author wolfsoft@mail.ru
*/

defined('_JEXEC') or die('Restricted access');

class pm_best2pay extends PaymentRoot {

	function loadLanguageFile() {
		$lang = JFactory::getLanguage();
		$langtag = $lang->getTag();
		if (file_exists(JPATH_ROOT . '/components/com_jshopping/payments/pm_best2pay/lang/' . $langtag . '.php'))
			require_once(JPATH_ROOT . '/components/com_jshopping/payments/pm_best2pay/lang/' . $langtag . '.php');
		else
			require_once(JPATH_ROOT . '/components/com_jshopping/payments/pm_best2pay/lang/en-GB.php');
	}

	function showPaymentForm($params, $pmconfigs) {
		include(dirname(__FILE__) . '/paymentform.php');
	}

	function showAdminFormParams($params) {
		$array_params = array(
			'best2pay_sector_id',
			'best2pay_password',
			'best2pay_mode',
			'best2pay_kkt',
			'best2pay_tax',
			'transaction_end_status',
			'transaction_pending_status',
			'transaction_failed_status'
		);

		foreach ($array_params as $key) {
			if (!isset($params[$key]))
				$params[$key] = '';
		}

		$orders = JModelLegacy::getInstance('orders', 'JshoppingModel');
		$this->loadLanguageFile();
		include(dirname(__FILE__) . '/adminparamsform.php');
	}

	function checkTransaction($pmconfigs, $order, $act) {

		$this->loadLanguageFile();

		try {
			$order_id = $order->order_id;
			$signature = base64_encode(md5($pmconfigs['best2pay_sector_id'] . JRequest::getVar('id') . JRequest::getVar('operation') . $pmconfigs['best2pay_password']));

			if ($pmconfigs['best2pay_mode'] == 'test')
				$best2pay_url = 'https://test.best2pay.net';
			else
				$best2pay_url = 'https://pay.best2pay.net';

			$context  = stream_context_create(array(
				'http' => array(
					'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
					'method'  => 'POST',
					'content' => http_build_query(array(
						'sector' => $pmconfigs['best2pay_sector_id'],
						'id' => JRequest::getVar('id'),
						'operation' => JRequest::getVar('operation'),
						'signature' => $signature
					)),
				)
			));
	
			$repeat = 3;

			while ($repeat) {
	
				$repeat--;
	
				// pause because of possible background processing in the Best2Pay
				sleep(2);
	
				$xml = file_get_contents($best2pay_url . '/webapi/Operation', false, $context);
				if (!$xml)
					throw new Exception("Empty data");
				$xml = simplexml_load_string($xml);
				if (!$xml)
					throw new Exception("Non valid XML was received");
				$response = json_decode(json_encode($xml));
				if (!$response)
					throw new Exception("Non valid XML was received");

				// check server signature
				$tmp_response = (array)$response;
				unset($tmp_response["signature"]);
				$signature = base64_encode(md5(implode('', $tmp_response) . $pmconfigs['best2pay_password']));
				if ($signature !== $response->signature)
					throw new Exception("Invalid signature");

				// check order state
				if (($response->type != 'PURCHASE' && $response->type != 'EPAYMENT') || $response->state != 'APPROVED')
					continue;

				return array(1, '');
			}
	
			return array(0, _JSHOP_CFG_BEST2PAY_ORDER_NOT_PAID);

		} catch (Exception $ex) {
			return array(0, $ex->getMessage());
		}
	}

	function showEndForm($pmconfigs, $order) {

		if ($pmconfigs['best2pay_mode'] == 'test')
			$best2pay_url = 'https://test.best2pay.net';
		else
			$best2pay_url = 'https://pay.best2pay.net';

		switch (strtolower($order->currency_code_iso)) {
			case 'usd':
				$currency = 840;
				break;
			case 'eur':
				$currency = 978;
				break;
			default:
				$currency = 643;
				break;
		}

		$amount = round(floatval($order->order_total) * 100);
		$signature  = base64_encode(md5($pmconfigs['best2pay_sector_id'] . $amount . $currency . $pmconfigs['best2pay_password']));

        $fiscalPositions='';
        $cart = JSFactory::getModel('cart', 'jshop');
        if (method_exists($cart, 'init')) {
            $cart->init('cart', 1);
        } else {
            $cart->load('cart');
        }
        $KKT = $pmconfigs['best2pay_kkt'];

        if ($KKT == 'test'){
            $TAX = (strlen($pmconfigs['best2pay_tax']) > 0) ?
                intval($pmconfigs['best2pay_tax']) : 7;
            if ($TAX > 0 && $TAX < 7){
                foreach ($cart->products as $product) {
                    $fiscalPositions.=$product['quantity'].';';
                    $elementPrice = $product['price'];
                    $elementPrice = $elementPrice * 100;
                    $fiscalPositions.=$elementPrice.';';
                    $fiscalPositions.=$TAX.';';
                    $fiscalPositions.=$product['product_name'].'|';
                }
                $fiscalPositions = substr($fiscalPositions, 0, -1);
            }
        }

		$data = array(
			'sector' => $pmconfigs['best2pay_sector_id'],
			'reference' => $order->order_id,
            'fiscal_positions' => $fiscalPositions,
            'amount' => $amount,
			'description' => sprintf(_JSHOP_PAYMENT_NUMBER, $order->order_number),
			'email' => $order->email,
			'currency' => $currency,
			'mode' => 1,
			'url' => JURI::root() . 'index.php?option=com_jshopping&controller=checkout&task=step7&act=return&js_paymentclass=pm_best2pay',
			'signature' => $signature
		);
		error_log(var_export($data, true));
		$options = array(
		    'http' => array(
		        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
		        'method'  => 'POST',
		        'content' => http_build_query($data),
		    ),
		);
		$context  = stream_context_create($options);

		$best2pay_id = file_get_contents($best2pay_url . '/webapi/Register', false, $context);
		if (intval($best2pay_id) == 0) {
			$xml = simplexml_load_string($best2pay_id);
			$response = json_decode(json_encode($xml));
			?>
			<html>
				<head>
					<meta http-equiv="content-type" content="text/html; charset=utf-8" />
				</head>
				<body>
					<p><?php echo $response['description']; ?></p>
					<p><a href="javascript:history.back();">«« Go Back</a></p>
				</body>
			</html>
			<?php
		}

		$signature  = base64_encode(md5($pmconfigs['best2pay_sector_id'] . $best2pay_id . $pmconfigs['best2pay_password']));

		?>
		<html>
		<head>
			<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		</head>
		<body>
			<form id="paymentform" accept-charset="utf8" action="<?php echo $best2pay_url . '/webapi/Purchase'; ?>" method="post">
				<input type="hidden" name="sector" value="<?php echo $pmconfigs['best2pay_sector_id']; ?>">
				<input type="hidden" name="id" value="<?php echo $best2pay_id; ?>">
				<input type="hidden" name="signature" value="<?php echo $signature; ?>">
			</form>
			<?php echo _JSHOP_REDIRECT_TO_PAYMENT_PAGE; ?>
			<br/>
			<script type="text/javascript">document.getElementById('paymentform').submit();</script>
		</body>
		</html>
		<?php
		die();
	}

	function getUrlParams($pmconfigs) {
		$params = array();
		$params['order_id'] = JRequest::getInt('reference');
		$params['hash'] = '';
		$params['checkHash'] = 0;
		$params['checkReturnParams'] = 1;
		return $params;
	}
}
