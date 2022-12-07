<?php
/*
 * @info Платёжный модуль Best2Pay для JoomShopping
 * @package JoomShopping for Joomla!
 * @subpackage payment
 * tested on Joomla v. 4.2.5 and JoomShopping v. 5.1.1
*/

defined('_JEXEC') or die('Restricted access');

class pm_best2pay extends PaymentRoot {

	function loadLanguageFile() {
		$lang = JFactory::getLanguage();
		$lang->load('com_best2pay');
	}

	function showPaymentForm($params, $pmconfigs) {
		include(dirname(__FILE__) . '/paymentform.php');
	}

	function showAdminFormParams($params) {
		$this->loadLanguageFile();
		
		$array_params = [
			'best2pay_sector_id',
			'best2pay_password',
			//'best2pay_testmode', // test mode is on by default in the template
			'best2pay_tax',
			'transaction_end_status',
			'transaction_pending_status',
			'transaction_failed_status'
		];

		foreach ($array_params as $key) {
			$params[$key] = $params[$key] ?? '';
		}

		$orders = JModelLegacy::getInstance('orders', 'JshoppingModel');
		
		$tax_list = [
			1 => \JText::_('JSHOP_CFG_BEST2PAY_TAX_1'),
			2 => \JText::_('JSHOP_CFG_BEST2PAY_TAX_2'),
			3 => \JText::_('JSHOP_CFG_BEST2PAY_TAX_3'),
			4 => \JText::_('JSHOP_CFG_BEST2PAY_TAX_4'),
			5 => \JText::_('JSHOP_CFG_BEST2PAY_TAX_5'),
			6 => \JText::_('JSHOP_CFG_BEST2PAY_TAX_6')
		];
		
		include(dirname(__FILE__) . '/adminparamsform.php');
	}

	function checkTransaction($pmconfigs, $order, $act) {

		$this->loadLanguageFile();
		$request = \JFactory::getApplication()->input->getArray();

		try {
			$sector_id = $pmconfigs['best2pay_sector_id'] ?? null;
			$password = $pmconfigs['best2pay_password'] ?? null;
			$id = $request['id'] ?? null;
			$operation = $request['operation'] ?? null;
			$signature = base64_encode(md5($sector_id . $id . $operation . $password));
			
			$best2pay_url = $this->getUrl($pmconfigs);

			$data = [
				'sector' => $sector_id,
				'id' => $id,
				'operation' => $operation,
				'signature' => $signature
			];

			$repeat = 3;
			while ($repeat) {
				$repeat--;
				// pause because of possible background processing in the Best2Pay
				sleep(2);
				$xml = $this->sendRequest($best2pay_url . '/webapi/Operation', $data);
				if (!$xml)
					throw new Exception("Empty data");
				$xml = simplexml_load_string($xml);
				if (!$xml)
					throw new Exception("Non valid XML was received");
				$response = json_decode(json_encode($xml));
				if (!$response)
					throw new Exception("Non valid XML was received");

				// check server signature
				$tmp_response = (array) $response;
				unset($tmp_response['signature'], $tmp_response['ofd_state']);
				$signature = base64_encode(md5(implode('', $tmp_response) . $password));
				if ($signature !== $response->signature)
					throw new Exception("Invalid signature");

				// check order state
				if (
					($response->type !== 'PURCHASE' && $response->type !== 'PURCHASE_BY_QR' && $response->type !== 'AUTHORIZE')
					|| $response->state !== 'APPROVED'
				)
					continue;

				return [1, ''];
			}
			
			return [0, \JText::_('JSHOP_CFG_BEST2PAY_ORDER_NOT_PAID')];

		} catch (Exception $ex) {
			return [0, $ex->getMessage()];
		}
	}

	function showEndForm($pmconfigs, $order) {
		
		$best2pay_url = $this->getUrl($pmconfigs);

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
		
		$order_price = round(floatval($order->order_total) * 100);
		$signature  = base64_encode(md5($pmconfigs['best2pay_sector_id'] . $order_price . $currency . $pmconfigs['best2pay_password']));

		$cart = JSFactory::getModel('cart', 'jshop');
		if (method_exists($cart, 'init')) {
			$cart->init('cart', 1);
		} else {
			$cart->load('cart');
		}
		
		$TAX = (isset($pmconfigs['best2pay_tax']) && $pmconfigs['best2pay_tax'] > 0 && $pmconfigs['best2pay_tax'] <= 6) ? $pmconfigs['best2pay_tax'] : 6;
		$fiscalPositions = '';
		$fiscalAmount = 0;
		
		foreach ($cart->products as $product) {
			$product_quantity = (int) $product['quantity'];
			$fiscalPositions .= $product_quantity . ';';
			$fiscalPositions .= intval(round($product['price'] * 100)) . ';';
			$fiscalPositions .= $TAX . ';';
			$fiscalPositions .= $product['product_name'] . '|';
			$fiscalAmount += intval(round($product_quantity * $product['price'] * 100));
		}
		
		if ($order->order_shipping > 0) {
			$fiscalPositions .= '1;';
			$shipping_price = intval(round($order->order_shipping * 100));
			$fiscalPositions .= $shipping_price . ';';
			$fiscalPositions .= $TAX . ';';
			$fiscalPositions .= \JText::_('JSHOP_SHIPPING') . '|';
			$fiscalAmount += $shipping_price;
		}
		
		$fiscalDiff = abs($fiscalAmount - $order_price);
		if ($fiscalDiff){
			$fiscalPositions .= '1' . ';';
			$fiscalPositions .= $fiscalDiff . ';';
			$fiscalPositions .= '6;';
			$fiscalPositions .= \JText::_('JSHOP_DISCOUNT') . ';';
			$fiscalPositions .= '14' . '|';
		}
		
		$fiscalPositions = substr($fiscalPositions, 0, -1);

		$data = [
			'sector' => $pmconfigs['best2pay_sector_id'],
			'reference' => $order->order_id,
			'fiscal_positions' => $fiscalPositions,
			'amount' => $order_price,
			'description' => sprintf(\JText::_('JSHOP_PAYMENT_NUMBER'), $order->order_number),
			'email' => $order->email,
			'currency' => $currency,
			'mode' => 1,
			'url' => JURI::root() . 'index.php?option=com_jshopping&controller=checkout&task=step7&act=return&js_paymentclass=pm_best2pay',
			'signature' => $signature
		];
		error_log(var_export($data, true));

		$best2pay_id = $this->sendRequest($best2pay_url . '/webapi/Register', $data);
		
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
					<p><a href="javascript:history.back();">«« <?php echo \JText::_('JSHOP_BACK_TO_SHOP')?></a></p>
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
			<?php echo \JText::_('JSHOP_REDIRECT_TO_PAYMENT_PAGE'); ?>
			<br/>
			<script type="text/javascript">document.getElementById('paymentform').submit();</script>
		</body>
		</html>
		<?php
		die();
	}

	function getUrlParams($pmconfigs) {
		$params = [];
		$params['order_id'] = \JFactory::getApplication()->input->getInt("reference");
		$params['hash'] = '';
		$params['checkHash'] = 0;
		$params['checkReturnParams'] = 1;
		return $params;
	}
	
	function getUrl($pmconfigs) {
		if (!empty($pmconfigs['best2pay_testmode']))
			return 'https://test.best2pay.net';
		else
			return 'https://pay.best2pay.net';
	}
	
	/**
	 * @param $url string
	 * @param $data array
	 * @param $method string
	 * @return false|string
	 */
	function sendRequest($url, $data, $method = 'POST') {
		$query = http_build_query($data);
		$context = stream_context_create([
			'http' => [
				'header'  => "Content-Type: application/x-www-form-urlencoded\r\n"
					. "Content-Length: " . strlen($query) . "\r\n",
				'method'  => $method,
				'content' => $query
			]
		]);
		
		if (!$context)
			return false;
		
		return file_get_contents($url, false, $context);
	}
	
}