<?php
	/* ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL); */
	
	require_once "config.php";

	$content = file_get_contents("php://input");
	$input = json_decode($content, true);
	
	// file_put_contents("telegramBotRequests.log", $content . "\n", FILE_APPEND | LOCK_EX);
	
	// ==================================================================
	// BOT
	if (isset($input['message'])) {
		$chatId = $input['message']['chat']['id'];
		$message = $input['message']['text'];
		
		if (strpos($message, '/start') === 0) {
			$startParam = str_replace('/start ', '', $message);

			// Check if the parameter exists
			if ($startParam !== '/start') {
				$reply = $startParam;
				$res = runApi('/users/newReferral', ['token' => $apiToken, 'chat_id' => $startParam, 'chat_id2' => $chatId]);
			}
			
			$data = [
				'chat_id' => $chatId,
				'text' => 'Penny Clicker is a platform that allows you to earn money from web activities and advertise any source using TON, USDT, NOT, DOGS, X, HMSTR cryptocurrencies.',
				'reply_markup' => json_encode([
					'inline_keyboard' => [
						[
							[
								'text' => 'Start app',
								'web_app' => [
									'url' => $appUrl
								]
							]
						]
					]
				])
			];
			
			file_get_contents($telegramApiUrl . "/sendMessage?" . http_build_query($data));
		}
	}
	else if (isset($input['callback_query'])) {
		file_get_contents($telegramApiUrl . "/answerCallbackQuery?" . http_build_query([
			'callback_query_id' => $input['callback_query']['id'],
			'text' => "Request accepted.",
			'show_alert' => false  // Set to true if you want to show the message as a popup
		]));
		
		$answer = $input['callback_query']['data'];
		
		if (strpos($answer, '/task_request_confirm/') !== false) {
			$task_id = (int) str_replace('/task_request_confirm/', '', $answer);
			
			$res = runApi('/tasks/moderation', ['token' => $apiToken, 'task_id' => $task_id, 'status' => 1]);
			$res = json_decode($res);
			
			// send admin
			file_get_contents($telegramApiUrl . "/sendMessage?" . http_build_query([
				'chat_id' => $telegramAdminChatId,
				'text' => $res->success ? 'Task #`' . $task_id . '` was *approved*.' : $res->error, 
				'parse_mode' => "Markdown"
			]));
			
			// send user
			if($res->success) {
				file_get_contents($telegramApiUrl . "/sendMessage?" . http_build_query([
					'chat_id' => $res->user_chat_id,
					'text' => $res->user_msg, 
					'parse_mode' => "Markdown"
				]));
			}
		}
		else if (strpos($answer, '/task_request_decline/') !== false) {
			$task_id = (int) str_replace('/task_request_decline/', '', $answer);
			
			$res = runApi('/tasks/moderation', ['token' => $apiToken, 'task_id' => $task_id, 'status' => 2]);
			$res = json_decode($res);
			
			// send admin
			file_get_contents($telegramApiUrl . "/sendMessage?" . http_build_query([
				'chat_id' => $telegramAdminChatId,
				'text' => $res->success ? 'Task #`' . $task_id . '` was *declined*.' : $res->error, 
				'parse_mode' => "Markdown"
			]));
			
			// send user
			if($res->success) {
				file_get_contents($telegramApiUrl . "/sendMessage?" . http_build_query([
					'chat_id' => $res->user_chat_id,
					'text' => $res->user_msg, 
					'parse_mode' => "Markdown"
				]));
			}
		}
		else if (strpos($answer, '/refill_request_confirm/') !== false) {
			$request_id = (int) str_replace('/refill_request_confirm/', '', $answer);
			
			$res = runApi('/balance/confirmRefillRequest', ['token' => $apiToken, 'request_id' => $request_id]);
			$res = json_decode($res);
			
			// send admin
			file_get_contents($telegramApiUrl . "/sendMessage?" . http_build_query([
				'chat_id' => $telegramAdminChatId,
				'text' => $res->success ? 'Refill #`' . $request_id . '` was *approved*.' : $res->error, 
				'parse_mode' => "Markdown"
			]));
			
			// send user
			file_get_contents($telegramApiUrl . "/sendMessage?" . http_build_query([
				'chat_id' => $res->user_chat_id,
				'text' => $res->user_msg, 
				'parse_mode' => "Markdown"
			]));
		}
		else if (strpos($answer, '/refill_request_decline/') !== false) {
			$request_id = (int) str_replace('/refill_request_decline/', '', $answer);
			
			$res = runApi('/balance/declineRefillRequest', ['token' => $apiToken, 'request_id' => $request_id]);
			$res = json_decode($res);
			
			// send admin
			file_get_contents($telegramApiUrl . "/sendMessage?" . http_build_query([
				'chat_id' => $telegramAdminChatId,
				'text' => $res->success ? 'Refill #`' . $request_id . '` was *declined*.' : $res->error, 
				'parse_mode' => "Markdown"
			]));
			
			// send user
			file_get_contents($telegramApiUrl . "/sendMessage?" . http_build_query([
				'chat_id' => $res->user_chat_id,
				'text' => $res->user_msg, 
				'parse_mode' => "Markdown"
			]));
		}
		else if (strpos($answer, '/withdrawal_request_confirm/') !== false) {
			$request_id = (int) str_replace('/withdrawal_request_confirm/', '', $answer);
			
			$res = runApi('/balance/confirmWithdrawalRequest', ['token' => $apiToken, 'request_id' => $request_id]);
			$res = json_decode($res);
			
			// send admin
			file_get_contents($telegramApiUrl . "/sendMessage?" . http_build_query([
				'chat_id' => $telegramAdminChatId,
				'text' => $res->success ? 'Withdrawal #`' . $request_id . '` was *approved*.' : $res->error, 
				'parse_mode' => "Markdown"
			]));
			
			// send user
			file_get_contents($telegramApiUrl . "/sendMessage?" . http_build_query([
				'chat_id' => $res->user_chat_id,
				'text' => $res->user_msg, 
				'parse_mode' => "Markdown"
			]));
		}
		else if (strpos($answer, '/withdrawal_request_decline/') !== false) {
			$request_id = (int) str_replace('/withdrawal_request_decline/', '', $answer);
			
			$res = runApi('/balance/declineWithdrawalRequest', ['token' => $apiToken, 'request_id' => $request_id]);
			$res = json_decode($res);
			
			// send admin
			file_get_contents($telegramApiUrl . "/sendMessage?" . http_build_query([
				'chat_id' => $telegramAdminChatId,
				'text' => $res->success ? 'Withdrawal #`' . $request_id . '` was *declined*.' : $res->error, 
				'parse_mode' => "Markdown"
			]));
			
			// send user
			file_get_contents($telegramApiUrl . "/sendMessage?" . http_build_query([
				'chat_id' => $res->user_chat_id,
				'text' => $res->user_msg, 
				'parse_mode' => "Markdown"
			]));
		}
	}

	// ==================================================================
	// API
	if (isset($input['api']) && isset($input['token']) && $input['token'] == $apiToken) {
		if($input['api'] == "moderationTask") {
			$data = [
				'chat_id' => $telegramAdminChatId,
				'parse_mode' => "Markdown",
				'text' => "*Moderation Task #*`" . $input['id'] . "`\n" .
							"Title: `" . $input['title'] . "`\n" .
							"Sum: `" . $input['balance'] . " " . $input['currency'] . "`",
				'reply_markup' => json_encode([
					'inline_keyboard' => [
						[
							[ 'text' => "Confirm", 'callback_data' => '/task_request_confirm/' . $input['id'] ], 
							[ 'text'  => "Decline", 'callback_data' => '/task_request_decline/' . $input['id'] ]
						]
					]
				])
			];
			
			$res = file_get_contents($telegramApiUrl . "/sendMessage?" . http_build_query($data));
			
			echo json_encode(['success' => true, 'error' => '']);
		}
		else if($input['api'] == "addRefillRequest") {
			$data = [
				'chat_id' => $telegramAdminChatId,
				'parse_mode' => "Markdown",
				'text' => "*Refill #*`" . $input['request_id'] . "`\n" .
							"ChatID: `" . $input['chat_id'] . "`\n" .
							"Address: `" . $input['address'] . "`\n" .
							"Sum: `" . $input['amount'] . " " . $input['currency'] . "`",
				'reply_markup' => json_encode([
					'inline_keyboard' => [
						[
							[ 'text' => "Confirm", 'callback_data' => '/refill_request_confirm/' . $input['request_id'] ], 
							[ 'text'  => "Decline", 'callback_data' => '/refill_request_decline/' . $input['request_id'] ]
						]
					]
				])
			];
			
			$res = file_get_contents($telegramApiUrl . "/sendMessage?" . http_build_query($data));
			
			echo json_encode(['success' => true, 'error' => '']);
		}
		else if($input['api'] == "addWithdrawalRequest") {
			$data = [
				'chat_id' => $telegramAdminChatId,
				'parse_mode' => "Markdown",
				'text' => "*Withdrawal #*`" . $input['request_id'] . "`\n" .
							"ChatID: `" . $input['chat_id'] . "`\n" .
							"Address: `" . $input['address'] . "`\n" .
							"Sum: `" . $input['amount'] . " " . $input['currency'] . "`",
				'reply_markup' => json_encode([
					'inline_keyboard' => [
						[
							[ 'text' => "Confirm", 'callback_data' => '/withdrawal_request_confirm/' . $input['request_id'] ], 
							[ 'text'  => "Decline", 'callback_data' => '/withdrawal_request_decline/' . $input['request_id'] ]
						]
					]
				])
			];
			
			$res = file_get_contents($telegramApiUrl . "/sendMessage?" . http_build_query($data));
			
			echo json_encode(['success' => true, 'error' => '']);
		}
		else if($input['api'] == "sendMessage") {
			$data = [
				'chat_id' => $input['chat_id'],
				'parse_mode' => "Markdown",
				'text' => $input['text']
			];
			
			$res = file_get_contents($telegramApiUrl . "/sendMessage?" . http_build_query($data));
			
			echo json_encode(['success' => true, 'error' => '']);
		}
		else
			echo json_encode(['success' => false, 'error' => 'API method not found.']);
	}
	
	function runApi($api, $data) {
		global $apiUrl;
		
		$url = $apiUrl . $api;

		$jsonData = json_encode($data);

		$options = [
			'http' => [
				'header'  => "Content-Type: application/json\r\n" .
							 "Content-Length: " . strlen($jsonData) . "\r\n",
				'method'  => 'POST',
				'content' => $jsonData,
			]
		];

		$context = stream_context_create($options);

		$response = file_get_contents($url, false, $context);
		
		return $response;
	}
?>