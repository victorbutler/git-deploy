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
		$hip_ip = $this->_config->get('hipchat_ip');
		$hipchat_try = 0;
		while ($hipchat_try < 5) { // try max 5 times
			if ($hipchat_try > 0) {
				$hip_ip = $this->find_ip();
			}
			$url = $hip_ip.'/v1/rooms/message?auth_token='.$this->_config->get('hipchat_auth_token');
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
				$protocol = 'https://';
				$c = curl_init();
				curl_setopt($c, CURLOPT_POST, count($fields));
				curl_setopt($c, CURLOPT_URL, $protocol.$url);
				curl_setopt($c, CURLOPT_TIMEOUT, 3);
				curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($fields));
				if ($proxy) {
					curl_setopt($c, CURLOPT_PROXY, 'http://'.$proxy);
				}
				//curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyauth);
				curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($c, CURLOPT_HEADER, 1);
				curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);

				curl_exec($c);
				$curl_errno = curl_errno($c);
				$curl_error = curl_error($c);
				curl_close($c);

				if ($curl_errno > 0) {
					$hipchat_try++;
					$failed_message .= ' '.$hipchat_try.': ('.$curl_error.')';
				} else {
					if ($hipchat_try > 0) { // if also the previous hip ips failed,
						// save new hip ip
						$update_result = $this->_config->set('hipchat_id', $hip_ip);
						if ($update_result === null) {
							throw new Exception('Could not save HipChat IP Address');
						}
					}
					return true;
				}
			} elseif (function_exists('stream_context_create')) {
				$protocol = 'http://';
				$data = http_build_query($fields);
				$context = array(
					'http' => array(
						'request_fulluri' => true,
						'method' => 'POST',
						'timeout' => 3,
						'header'=> "Content-type: application/x-www-form-urlencoded\r\n"
								 . "Content-Length: " . strlen($data) . "\r\n",
						'content' => $data
					)
				);
				if ($proxy) {
					$context['http']['proxy'] = 'tcp://'.$proxy;
				}
				$stream = stream_context_create($context);
				$result = @fopen($protocol.$url, 'r', false, $stream);
				if ($result) {
					fclose($result);
					if ($hipchat_try > 0) { // if also the previous hip ips failed,
						// save new hip ip
						$update_result = $this->_config->set('hipchat_id', $hip_ip);
						if ($update_result === null) {
							throw new Exception('Could not save HipChat IP Address');
						}
					}
					return true;
				} else {
					$hipchat_try++;
				}
			} else {
				break;
			}
		}
		throw new Exception($failed_message);
	}

	public function find_ip() {
		$proxy = ($this->_config->get('curl_proxy') && $this->_config->get('curl_proxy') == '' ? null : $this->_config->get('curl_proxy')); //
		if (function_exists('curl_init')) {
			$c = curl_init();
			curl_setopt($c, CURLOPT_URL, $this->_dns_over_http);
			curl_setopt($c, CURLOPT_TIMEOUT, 3);
			if ($proxy) {
				curl_setopt($c, CURLOPT_PROXY, 'http://'.$proxy);
			}
			
			curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
			
			// curl_setopt($c, CURLOPT_HEADER, 1);
			curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
			
			$result = curl_exec($c);
			$curl_errno = curl_errno($c);
			$curl_error = curl_error($c);
			curl_close($c);
			
			if ($result !== FALSE) {
				$response = json_decode($result);
				return array_pop($response->answer)->rdata;
			}
		}
	}

}
