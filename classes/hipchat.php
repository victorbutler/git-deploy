<?php

class Hipchat {

	protected $_dns_over_http = "http://api.statdns.com/hipchat.com/a";

	protected $_config;

	protected static $_instance;

	public static function instance() {
		if (!self::$_instance) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		$this->_config = Config::instance();
	}

	public function send($message, $failed_message = 'Deploy was successful but HipChat message failed') {
		$this->_config = Config::instance();
		$url = 'https://api.hipchat.com/v1/rooms/message?auth_token='.$this->_config->get('hipchat_auth_token');
		$destination = 'http://'.$_SERVER['HTTP_HOST'].option('base_path').'/'.$project_obj_or_id->destination;
		$proxy = ($this->_config->get('curl_proxy') && $this->_config->get('curl_proxy') == '' ? null : $this->_config->get('curl_proxy')); // null disables proxy (if config item is undefined or empty string in DB)
		$fields = array(
			'room_id' => $this->_config->get('hipchat_room_id'),
			'from' => $this->_config->get('hipchat_from'),
			'message_format' => 'html',
			'notify' => $this->_config->get('hipchat_notify'),
			'color' => $this->_config->get('hipchat_color'),
			'message' => $message
		);
		if (function_exists('curl_init')) {
			$c = curl_init();
			curl_setopt($c, CURLOPT_URL, $url);
			curl_setopt($c, CURLOPT_POST, count($fields));
			curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($fields));
			if ($proxy) {
				curl_setopt($c, CURLOPT_PROXY, 'http://'.$proxy);
			}
			//curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyauth);
			curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($c, CURLOPT_HEADER, 1);

			$result = curl_exec($c);
			curl_close($c);

			return ($result !== FALSE);
		} elseif (function_exists('stream_context_create')) {
			$data = http_build_query($fields);
			$context = array(
				'http' => array(
					'request_fulluri' => true,
					'method' => 'POST',
					'header'=> "Content-type: application/x-www-form-urlencoded\r\n"
							 . "Content-Length: " . strlen($data) . "\r\n",
					'content' => $data
				)
			);
			if ($proxy) {
				$context['http']['proxy'] = 'tcp://'.$proxy;
			}
			$stream = stream_context_create($context);
			$result = @fopen($url, 'r', false, $stream);
			return ($result);
		}
		throw new Exception($failed_message);
	}

}
