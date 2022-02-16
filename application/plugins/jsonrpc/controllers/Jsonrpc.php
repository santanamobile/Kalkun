<?php
/**
 * Kalkun
 * An open source web based SMS Management
 *
 * @package		Kalkun
 * @author		Kalkun Dev Team
 * @license		https://spdx.org/licenses/GPL-3.0-or-later.html
 * @link		http://kalkun.sourceforge.net
 */

// ------------------------------------------------------------------------

/**
 * Jsonrpc Class
 *
 * @package		Kalkun
 * @subpackage	Plugin
 * @category	Controllers
 */
include_once(APPPATH.'plugins/Plugin_controller.php');

class Jsonrpc extends Plugin_controller {

	function __construct()
	{
		parent::__construct(FALSE);
	}

	/**
	* JSONRPC server for sending sms
	*
	*/
	function send_sms()
	{
		// Json sample for input:
		// {"jsonrpc":"2.0","id":551,"method":"sms.send_sms","params":{"phoneNumber":"+1234","message":"Testing JSONRPC"}}

		$evaluator = new Evaluator();
		$server = new \Datto\JsonRpc\Http\Server($evaluator);
		$server->reply();
	}

	/**
	* Sample JSONRPC client example
	* that consume send sms function
	*/
	function send_sms_client()
	{
		$this->load->helper('url');
		$server_url = site_url('plugin/jsonrpc/send_sms');

		$client = new \Datto\JsonRpc\Http\Client($server_url);
		$request = array('phoneNumber' => '+1234', 'message' => 'Testing JSONRPC');
		$client->query('sms.send_sms', $request, $result);
		try
		{
			$client->send();
		}
		catch (ErrorException $exception)
		{
			echo "Exception sending jsonrpc query: ${exception}.message\n";
			exit(1);
		}

		echo '<pre>';
		print_r($result);
		echo '</pre>';
	}
}

class Evaluator implements Datto\JsonRpc\Evaluator {

	public function evaluate($method, $arguments)
	{
		if ($method === 'sms.send_sms')
		{
			return self::send_sms($arguments);
		}

		throw new MethodException();
	}

	private static function send_sms($arguments)
	{
		if (empty($arguments))
		{
			throw new ArgumentException();
		}

		$CI = &get_instance();
		$CI->load->model(array('Kalkun_model', 'Message_model'));

		$data['coding'] = 'default';
		$data['class'] = '1';
		$data['dest'] = $arguments['phoneNumber'];
		$data['date'] = date('Y-m-d H:i:s');
		$data['message'] = $arguments['message'];
		$data['delivery_report'] = 'default';
		$data['uid'] = 1;
		$sms = $CI->Message_model->send_messages($data);

		return implode(' ', $sms);
	}
}
