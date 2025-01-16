<?php
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=UTF-8');
	header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
	header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

	require_once "config.php";
	
	require_once "db.class.php";
	require_once "jwt.class.php";
	
	$db = new db($dbSettings);
	$jwt = new jwt($jwtSecretKey);

	function runTelegramBotApi($api, $data) {
		global $appUrl;
		global $apiToken;
		
		$url = $appUrl . 'telegramBot.php';

		$data['token'] = $apiToken;
		
		$data['api'] = $api;
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

	function filteringTaskdata($input) {
		global $availableCurrency;
		
		$input['url'] = addslashes($input['url'] ?? '');
		if (!preg_match('/\b((https?|ftp):\/\/[-\w]+(\.[-\w]+)+(:\d+)?(\/([\w\/_-]*)*(\?\S+)?)?)\b/', $input['url'])) {
			echo json_encode(['success' => false, 'error' => 'Invalid URL.']);
			exit;
		}

		$input['title'] = addslashes($input['title'] ?? '');
		$input['description'] = addslashes($input['description'] ?? '');

		$input['numberExecutions'] = (int)($input['numberExecutions'] ?? 0);
		if ($input['numberExecutions'] <= 0) {
			echo json_encode(['success' => false, 'error' => 'Invalid number of executions.']);
			exit;
		}

		$input['price'] = (float)($input['price'] ?? 0);
		if ($input['price'] <= 0) {
			echo json_encode(['success' => false, 'error' => 'Invalid price.']);
			exit;
		}

		if (!in_array($input['currency'], $availableCurrency)) {
			echo json_encode(['success' => false, 'error' => 'Invalid currency.']);
			exit;
		}

		$input['mode'] = (int) $input['mode'];
		if ($input['mode'] != 0 && $input['mode'] != 1) {
			echo json_encode(['success' => false, 'error' => 'Invalid check mode.']);
			exit;
		}
		$input['keyword'] = addslashes($input['keyword'] ?? '');

		$input['deadline'] = addslashes($input['deadline'] ?? '');
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['deadline'])) {
			echo json_encode(['success' => false, 'error' => 'Invalid deadline format.']);
			exit;
		}
		
		return $input;
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/error/add') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['error'])) {
			file_put_contents("errors.log", date('Y-m-d H:i:s') . " - ERROR: " . PHP_EOL . $input['error'] . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
			echo json_encode(['success' => true]);
		} else {
			// http_response_code(400);
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}

	// USERS
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/users/newReferral') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token']) && isset($input['chat_id']) && isset($input['chat_id2']) && $input['token'] === $apiToken) {
			$db->insert("users_referrals", array('chat_id'=>$input['chat_id'], 'chat_id2'=>$input['chat_id2']));
			echo json_encode(['success' => true, 'error' => '', 'user_chat_id' => $task->chat_id, 'user_msg' => $msg]);
		} else {
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/user/checkAuth') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['initData'])) {
			$decoded_data = urldecode($input['initData']);
			parse_str($decoded_data, $data);

			$received_hash = $data['hash'];
			unset($data['hash']);

			// Generate a secret key based on the token and the 'WebAppData' string
			$secret_key = hash_hmac('sha256', $telegramApiToken, 'WebAppData', true);

			// We sort the data alphabetically and form the data-check-string string
			ksort($data);
			$data_check_string = '';
			$userData = [];
			foreach ($data as $key => $value) {
				$data_check_string .= "$key=$value\n";
				if($key == "user")
					$userData = json_decode($value, true);
			}
			$data_check_string = rtrim($data_check_string, "\n");

			// Generate a hash for the data-check-string using the secret key
			$calculated_hash = hash_hmac('sha256', $data_check_string, $secret_key);
			
			if (hash_equals($received_hash, $calculated_hash)) {
				$userData['chat_id'] = $userData['id'];
				
				$userData = array_filter($userData, function($value, $key) {
					return in_array($key, array("chat_id", "first_name", "last_name", "username", "language_code"));
				}, ARRAY_FILTER_USE_BOTH);
				
				$userData['nickname'] = isset($userData['username']) ? $userData['username'] : $userData['first_name'] . ' ' . $userData['last_name'];
				
				$user = $db->select("users", "`chat_id` = " . $userData['chat_id'])->row();
				
				if($user) {
					$user->new = false;
				} else {
					$userData['initData'] = $input['initData'];
					$db->insert("users", $userData);
					$user = $db->select("users", "`chat_id` = " . $userData['chat_id'])->row();
					$user->new = true;
				}
				
				$token = $jwt->generateJWT(['chat_id' => $userData['chat_id']]);
				
				echo json_encode(['success' => true, 'error' => '', 'token' => $token, 'user' => $user]);
			} else {
				echo json_encode(['success' => false, 'error' => 'Authentication error.']);
			}
		} else {
			echo json_encode(['success' => false, 'error' => 'Authentication error.']);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/user/setLanguage') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token']) && isset($input['language'])) {
			$jwtData = $jwt->decodeJWT($input['token']);
			$chat_id = (int) $jwtData['chat_id'];
			
			if ($chat_id <= 0) {
				echo json_encode(['success' => false, 'error' => 'Invalid chat_id.']);
				exit;
			}
			
			if (!in_array($input['language'], $availableLanguages)) {
				echo json_encode(['success' => false, 'error' => 'Invalid language.']);
				exit;
			}
			
			$db->update("users", ['language_code' => $input['language']], "`chat_id` = " . $chat_id);
			
			echo json_encode(['success' => true, 'error' => '']);
		} else {
			// http_response_code(400);
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/user/getInfo') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token'])) {
			$jwtData = $jwt->decodeJWT($input['token']);
			$chat_id = (int) $jwtData['chat_id'];
			
			$user = $db->query("SELECT `photo`, `nickname`, `about`, `receive_notifications` FROM `users` WHERE `chat_id` = " . $chat_id)->row();
			
			echo json_encode(['success' => true, 'error' => '', 'user' => $user]);
		} else {
			// http_response_code(400);
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/user/editInfo') !== false) {
		if (isset($_POST['token']) && isset($_POST['nickname']) && isset($_POST['about']) && isset($_POST['receive_notifications'])) {
			$jwtData = $jwt->decodeJWT($_POST['token']);
			$chat_id = (int) $jwtData['chat_id'];
			
			$nickname = htmlspecialchars($_POST['nickname']);
			$about = htmlspecialchars($_POST['about']);
			$receive_notifications = htmlspecialchars($_POST['receive_notifications']);

			if(isset($_FILES['photo'])) {
				if ($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
					$uploadDir = '/uploads/profile_photos/';
					$fileName = uniqid() . '_' . basename($_FILES['photo']['name']);
					$uploadFilePath = $uploadDir . $fileName;
					
					// Get the image dimensions
					list($originalWidth, $originalHeight) = getimagesize($_FILES['photo']['tmp_name']);
					
					// Determine if compression is needed
					if ($originalWidth > $photoMaxDimensions || $originalHeight > $photoMaxDimensions) {
						$scale = min($photoMaxDimensions / $originalWidth, $photoMaxDimensions / $originalHeight);
						$newWidth = round($originalWidth * $scale);
						$newHeight = round($originalHeight * $scale);

						// Create an image based on a file type
						$imageType = $_FILES['photo']['type'];
						switch ($imageType) {
							case 'image/jpeg':
								$sourceImage = imagecreatefromjpeg($_FILES['photo']['tmp_name']);
								break;
							case 'image/png':
								$sourceImage = imagecreatefrompng($_FILES['photo']['tmp_name']);
								break;
							case 'image/gif':
								$sourceImage = imagecreatefromgif($_FILES['photo']['tmp_name']);
								break;
							default:
								header('Content-Type: application/json');
								echo json_encode(['success' => false, 'error' => 'Unsupported image type.']);
								exit;
						}

						// Create a new image with reduced dimensions
						$resizedImage = imagecreatetruecolor($newWidth, $newHeight);
						imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

						// Save the compressed image
						switch ($imageType) {
							case 'image/jpeg':
								imagejpeg($resizedImage, '.' . $uploadFilePath);
								break;
							case 'image/png':
								imagepng($resizedImage, '.' . $uploadFilePath);
								break;
							case 'image/gif':
								imagegif($resizedImage, '.' . $uploadFilePath);
								break;
						}

						// Freeing up memory
						imagedestroy($sourceImage);
						imagedestroy($resizedImage);
					} else {
						move_uploaded_file($_FILES['photo']['tmp_name'], '.' . $uploadFilePath);
					}
					
					$db->update("users", ['photo' => $uploadFilePath, 'nickname' => $nickname, 'about' => $about, 'receive_notifications' => $receive_notifications], "`chat_id` = " . $chat_id);
					
					// $stmt = $pdo->prepare("INSERT INTO users (nickname) VALUES (:nickname)");
					// $stmt->bindParam(':nickname', $_POST['nickname']);
					// $stmt->execute();
							
					echo json_encode([
						'success' => true,
						'error' => '',
						'upload_file_path' => $uploadFilePath
					]);
				} else {
					echo json_encode(['success' => false, 'error' => 'File upload error. ']);
				}
			} else {
				$db->update("users", ['nickname' => $nickname, 'about' => $about, 'receive_notifications' => $receive_notifications], "`chat_id` = " . $chat_id);
						
				echo json_encode([
					'success' => true,
					'error' => ''
				]);
			}
		} else {
			// http_response_code(400);
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/user/getProfileInfo') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token'])) {
			$jwtData = $jwt->decodeJWT($input['token']);
			$chat_id = (int) $jwtData['chat_id'];
			
			$user = $db->query("SELECT * FROM `users` WHERE `chat_id` = " . $chat_id)->row();
			$my = $db->query("SELECT COUNT(`id`) AS c FROM `tasks` WHERE `chat_id` = " . $chat_id)->row();
			$active = $db->query("SELECT COUNT(`id`) AS c FROM `tasks_completed` WHERE `status` = 0 AND `chat_id` = " . $chat_id)->row();
			$completed = $db->query("SELECT COUNT(`id`) AS c FROM `tasks_completed` WHERE `status` = 1 AND `chat_id` = " . $chat_id)->row();
			$declined = $db->query("SELECT COUNT(`id`) AS c FROM `tasks_completed` WHERE `status` = 2 AND `chat_id` = " . $chat_id)->row();
			
			echo json_encode(['success' => true, 'error' => '', 'user' => $user, 'tasks' => ['my' => $my->c, 'active' => $active->c, 'completed' => $completed->c, 'declined' => $declined->c]]);
		} else {
			// http_response_code(400);
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/user/getBalanceInfo') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token'])) {
			$jwtData = $jwt->decodeJWT($input['token']);
			$chat_id = (int) $jwtData['chat_id'];
			
			$rates = $db->query("SELECT * FROM `cryptocurrency_rates` ORDER BY `date` DESC LIMIT 1")->row();
			$balance = $db->query("SELECT `ton_coin`, `not_coin`, `dogs_coin`, `hmstr_coin`, `x_coin` FROM `users` WHERE `chat_id` = " . $chat_id)->row();
			$transactions = $db->query("SELECT t.*, t2.`type` FROM `transactions` AS t LEFT JOIN `transactions_types` AS t2 ON t2.`id` = t.`type_id` WHERE t.`chat_id` = " . $chat_id . " ORDER BY t.`id` DESC LIMIT 0, 100;")->rows();
			
			echo json_encode(['success' => true, 'error' => '', 'min_withdrawal' => $minWithdrawal, 'rates' =>$rates, 'balance' => $balance, 'transactions' => $transactions]);
		} else {
			// http_response_code(400);
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}

	// TASKS
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/tasks/getAvailable') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token'])) {
			$jwtData = $jwt->decodeJWT($input['token']);
			$chat_id = (int) $jwtData['chat_id'];
			
			$tasks = $db->query("SELECT t.*
				FROM `tasks` AS t 
				LEFT JOIN `tasks_completed` AS t2 ON t2.`task_id` = t.`id` AND t2.`chat_id` = " . $chat_id . "
				WHERE t.`status` = 1 AND t2.task_id IS NULL
				ORDER BY t.`top` DESC, t.`date` DESC")->rows();
		}
		
		echo json_encode(['success' => true, 'error' => '', 'tasks' => $tasks]);
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/tasks/getById') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token']) && isset($input['task_id'])) {
			$jwtData = $jwt->decodeJWT($input['token']);
			$chat_id = (int) $jwtData['chat_id'];
			$task_id = (int) $input['task_id'];
			
			$task = $db->query("SELECT t.*, t2.task_id AS completed 
				FROM `tasks` AS t 
				LEFT JOIN `tasks_completed` AS t2 ON t2.`task_id` = t.`id` AND t2.`chat_id` = " . $chat_id . " 
				WHERE t.`id` = " . $task_id)->row();
			
			echo json_encode(['success' => true, 'error' => '', 'task' => $task]);
		} else {
			// http_response_code(400);
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/tasks/getMy') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token'])) {
			$jwtData = $jwt->decodeJWT($input['token']);
			$chat_id = (int) $jwtData['chat_id'];
			
			$tasks = (object) ['moderation'=> [], 'active'=> [], 'declined'=> [], 'archive'=> []];
			
			$rows = $db->query("SELECT t.*, 
				(SELECT COUNT(`id`) FROM `tasks_completed` WHERE `task_id` = t.`id` AND `status` = 0) AS numberModeration, 
				(SELECT COUNT(`id`) FROM `tasks_completed` WHERE `task_id` = t.`id` AND `status` = 1) AS numberCompleted
				FROM `tasks` AS t 
				WHERE t.`chat_id` = " . $chat_id . "
				ORDER BY t.`date` DESC")->rows();
			
			if(!$rows)
				$rows = [];
			
			$tasks->moderation = array_values(array_filter($rows, function($r) {
				return $r->status === 0;
			}));
			$tasks->active = array_values(array_filter($rows, function($r) {
				return $r->status === 1;
			}));
			$tasks->declined = array_values(array_filter($rows, function($r) {
				return $r->status === 2;
			}));
			$tasks->archive = array_values(array_filter($rows, function($r) {
				return $r->status === 3;
			}));
			
			echo json_encode(['success' => true, 'error' => '', 'tasks' => $tasks]);
		} else {
			// http_response_code(400);
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/tasks/getCompleted') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token'])) {
			$jwtData = $jwt->decodeJWT($input['token']);
			$chat_id = (int) $jwtData['chat_id'];
			
			$tasks = (object) ['active'=> [], 'completed'=> [], 'declined'=> []];
			
			$rows = $db->query("SELECT t2.*, t.`status`, t.`date`
				FROM `tasks_completed` AS t
				LEFT JOIN `tasks` AS t2 ON t2.`id` = t.`task_id`
				WHERE t.`chat_id` = " . $chat_id . "
				ORDER BY t.`date` DESC")->rows();
			
			if(!$rows)
				$rows = [];
			
			$tasks->active = array_values(array_filter($rows, function($r) {
				return $r->status === 0;
			}));
			$tasks->completed = array_values(array_filter($rows, function($r) {
				return $r->status === 1;
			}));
			$tasks->declined = array_values(array_filter($rows, function($r) {
				return $r->status === 2;
			}));
			
			echo json_encode(['success' => true, 'error' => '', 'tasks' => $tasks]);
		} else {
			// http_response_code(400);
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/tasks/getRequests') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token']) && isset($input['task_id'])) {
			$jwtData = $jwt->decodeJWT($input['token']);
			$chat_id = (int) $jwtData['chat_id'];
			$task_id = (int) $input['task_id'];
			
			$task = $db->select("tasks", "`id` = " . $task_id)->row();
			
			$requests = new stdClass();
			
			$rows = $db->query("SELECT t.*, t3.chat_id, t3.nickname 
				FROM `tasks_completed` AS t 
				LEFT JOIN `tasks` AS t2 ON t2.`id` = t.`task_id` 
				LEFT JOIN `users` AS t3 ON t3.`chat_id` = t.`chat_id`
				WHERE t.`task_id` = " . $task_id . " AND t2.`chat_id` = " . $chat_id . "
				ORDER BY t.`date` DESC")->rows();
			
			if(!$rows)
				$rows = [];
			
			$requests->awaiting = array_values(array_filter($rows, function($r) {
				return $r->status === 0;
			}));
			$requests->accepted = array_values(array_filter($rows, function($r) {
				return $r->status === 1;
			}));
			$requests->declined = array_values(array_filter($rows, function($r) {
				return $r->status === 2;
			}));
			
			echo json_encode(['success' => true, 'error' => '', 'task' => $task, 'requests' => $requests]);
		} else {
			// http_response_code(400);
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/tasks/checkRequest') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token']) && isset($input['request_id']) && isset($input['action'])) {
			$jwtData = $jwt->decodeJWT($input['token']);
			$chat_id = (int) $jwtData['chat_id'];
			$request_id = (int) $input['request_id'];
			$action = $input['action'];
			
			$requests = $db->query("SELECT t.`chat_id` AS executor_chat_id, t2.`id`, t2.`chat_id` AS customer_chat_id, t2.`price`, t2.`currency`, t2.`balance` 
				FROM `tasks_completed` AS t 
				LEFT JOIN `tasks` AS t2 ON t2.`id` = t.`task_id` 
				WHERE t.`id` = " . $request_id . " AND t2.`chat_id` = " . $chat_id)->rows();
			
			if(count($requests) !== 1) {
				// http_response_code(400);
				echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
				exit;
			}
			
			if($action === 'accept') {
				$task = $requests[0];
				
				if($task->balance < $task->price) {
					echo json_encode(['success' => false, 'error' => 'You cannot pay the task for this user because the task balance is less than required. Refill task balance.']);
					exit;
				}
				
				$db->update('tasks_completed', array('status' => 1), "`id` = " . $request_id);
				
				$db->query("UPDATE `tasks` SET `balance` = (`balance` - `price`) WHERE `id` = " . $task->id);
				
				$coin = strtolower($task->currency) . '_coin';
				$db->query("UPDATE `users` SET `" . $coin . "` = (`" . $coin . "` + " . $task->price . ") WHERE `chat_id` = " . $task->executor_chat_id);
			
				$input['transaction_id'] = $db->insert("transactions", 
					array(
						'chat_id' => $task->executor_chat_id, 
						'type_id' => 4, 
						'object_id' => $task->id, 
						'sum' => $task->price, 
						'currency' => $task->currency
					)
				);
				
				/* $res = runTelegramBotApi("sendMessage", [
					'chat_id' => $requests->customer_chat_id, 
					'text' => "You have a new request to check the completion of task #`" . $task->id . "`"
				]); */
				
				echo json_encode(['success' => true, 'error' => '']);
			}
			else if($action === 'decline') {
				$db->update('tasks_completed', array('status' => 2), "`id` = " . $request_id);
				
				/* $res = runTelegramBotApi("sendMessage", [
					'chat_id' => $requests->customer_chat_id, 
					'text' => "You have a new request to check the completion of task #`" . $task->id . "`"
				]); */
				
				echo json_encode(['success' => true, 'error' => '']);
			}
			else {
				// http_response_code(400);
				echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
			}
		} else {
			// http_response_code(400);
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/tasks/add') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token'])) {
			$jwtData = $jwt->decodeJWT($input['token']);
			$input['chat_id'] = (int) $jwtData['chat_id'];
			
			// Filtering input data
			if ($input['chat_id'] <= 0) {
				echo json_encode(['success' => false, 'error' => 'Invalid chat_id.']);
				exit;
			}

			$input = filteringTaskdata($input);
			// END Filtering input data

			// We determine the amount of the task
			$sum = $input['numberExecutions'] * $input['price'];
			
			// Checking the amount of money in the client's account
			$user = $db->select("users", "`chat_id` = " . $input['chat_id'])->row();
			$coin = strtolower($input['currency']) . '_coin';
			if($user->$coin < $sum) {
				echo json_encode(['success' => false, 'error' => 'Not enough funds to pay for tasks. Top up your balance.']);
				exit;
			}
			
			$input['status'] = 0;
			$input['balance'] = $sum;
			
			// We take only the necessary data
			$input = array_filter($input, function($value, $key) {
				return in_array($key, array("chat_id", "url", "title", "description", "numberExecutions", "price", "currency", "mode", "keyword", "deadline", "status", "balance"));
			}, ARRAY_FILTER_USE_BOTH);
			
			// Adding a task
			$input['id'] = $db->insert("tasks", $input);
			
			// We write off funds from the client to the task account
			$db->query("UPDATE `users` SET `" . $coin . "` = (`" . $coin . "` - " . $sum . ") WHERE `chat_id` = " . $input['chat_id']);
			
			// Adding a transaction to the user
			$input['transaction_id'] = $db->insert("transactions", 
				array(
					'chat_id' => $input['chat_id'], 
					'type_id' => 3, 
					'object_id' => $input['id'], 
					'sum' => -1 * $sum, 
					'currency' => $input['currency']
				)
			);
			
			$res = runTelegramBotApi("moderationTask", $input);
			
			echo json_encode(['success' => true, 'error' => '', 'task' => $input]);
		} else {
			// http_response_code(400);
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/tasks/edit') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && $input['token'] && isset($input['task_id'])) {
			$jwtData = $jwt->decodeJWT($input['token']);
			$chat_id = (int) $jwtData['chat_id'];
			$task_id = (int) $input['task_id'];
			
			if ($chat_id <= 0 || $task_id <= 0) {
				echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
				exit;
			}
			
			$task = $db->select("tasks", "`id` = " . $task_id . " AND `chat_id` = " . $chat_id)->row();
			
			if(!$task) {
				echo json_encode(['success' => false, 'error' => 'Access denied']);
				exit;
			}
			
			// Filtering input data
			$input = filteringTaskdata($input);
			
			$sum = $input['numberExecutions'] * $input['price'];
			
			$user = $db->select("users", "`chat_id` = " . $chat_id)->row();
			$coin = strtolower($input['currency']) . '_coin';
			if(
				($input['currency'] == $task->currency && $user->$coin < ($sum - $task->balance)) ||
				($input['currency'] != $task->currency && $user->$coin < $sum)
			) {
				echo json_encode(['success' => false, 'error' => 'Not enough funds to pay for tasks. Top up your balance.']);
				exit;
			}
			
			if($input['numberExecutions'] != $task->numberExecutions || $input['price'] != $task->price || $input['currency'] != $task->currency) {
				if($task->balance > 0) {
					$coin = strtolower($task->currency) . '_coin';
					$db->query("UPDATE `users` SET `" . $coin . "` = (`" . $coin . "` + " . $task->balance . ") WHERE `chat_id` = " . $task->chat_id);
					$db->insert("transactions", 
						array(
							'chat_id' => $task->chat_id, 
							'type_id' => 5, 
							'object_id' => $input['task_id'], 
							'sum' => $task->balance, 
							'currency' => $task->currency
						)
					);
				}
				
				if($sum > 0) {
					$coin = strtolower($input['currency']) . '_coin';
					$db->query("UPDATE `users` SET `" . $coin . "` = (`" . $coin . "` - " . $sum . ") WHERE `chat_id` = " . $task->chat_id);
					$db->insert("transactions", 
						array(
							'chat_id' => $task->chat_id, 
							'type_id' => 3, 
							'object_id' => $input['id'], 
							'sum' => -1 * $sum, 
							'currency' => $input['currency']
						)
					);
				}
			}
			
			unset($input['token'], $input['task_id'], $input['completed']);
			
			$input['balance'] = $sum;
			$input['status'] = 0;
			
			$db->update("tasks", $input, "`id` = " . $task_id);
			
			$input['id'] = $task_id;
			
			$res = runTelegramBotApi("moderationTask", $input);
			
			echo json_encode(['success' => true, 'error' => '', 'task' => $input]);
		} else {
			// http_response_code(400);
			echo json_encode(['success' => false, 'error' => 'Invalid task ID']);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/tasks/stop') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && $input['token'] && isset($input['task_id'])) {
			$jwtData = $jwt->decodeJWT($input['token']);
			$chat_id = (int) $jwtData['chat_id'];
			$task_id = (int) $input['task_id'];
			
			if ($chat_id <= 0 || $task_id <= 0) {
				echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
				exit;
			}
			
			$task = $db->select("tasks", "`id` = " . $task_id . " AND `chat_id` = " . $chat_id)->row();
			
			if(!$task) {
				echo json_encode(['success' => false, 'error' => 'Access denied']);
				exit;
			}
			
			$res = $db->update("tasks", ['status' => 3], "`id` = " . $task_id);
			
			echo json_encode(['success' => true, 'error' => '']);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/tasks/moderation') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token']) && $input['token'] === $apiToken) {
			$input['status'] = (int) $input['status'];
			$task = $db->select("tasks", "`id` = " . $input['task_id'])->row();
			
			if(!$task) {
				echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
				exit;
			}
			
			if($input['status'] == 2) {
				$coin = strtolower($task->currency) . '_coin';
				$db->query("UPDATE `users` SET `" . $coin . "` = (`" . $coin . "` + " . $task->balance . ") WHERE `chat_id` = " . $task->chat_id);
				$db->query("UPDATE `tasks` SET `balance` = 0 WHERE `id` = " . $input['task_id']);
				
				$db->insert("transactions", 
					array(
						'chat_id' => $task->chat_id, 
						'type_id' => 5, 
						'object_id' => $input['task_id'], 
						'sum' => $task->balance, 
						'currency' => $task->currency
					)
				);
			}
			
			$res = $db->update("tasks", ['status' => $input['status']], "`id` = " . $input['task_id']);
			
			$msg = $input['status'] == 1 ? 
				"*Thank you! Task #" . $input['task_id'] . "* has been verified and has received the *ACTIVE* status." :
				"*Sorry. Task #*" . $input['task_id'] . " failed the verified and was assigned the status *DECLINE*. Coins have been returned to your balance. To clarify the reason, please contact the administration.";
				
			echo json_encode(['success' => true, 'error' => '', 'user_chat_id' => $task->chat_id, 'user_msg' => $msg]);
		} else {
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/tasks/startDoing') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token']) && isset($input['task_id'])) {
			$jwtData = $jwt->decodeJWT($input['token']);
			$chat_id = (int) $jwtData['chat_id'];
			
			$task_id = (int) $input['task_id'];
			
			$task = $db->select("tasks", "`id` = " . $task_id)->row();
			if(!$task) {
				echo json_encode(['success' => false, 'error' => 'Sorry. Task not found. Our specialists are already sorting out the error.']);
				exit;
			}
			
			$task_completed = $db->select("tasks_completed", "`task_id` = " . $task_id . " AND `chat_id` = " . $chat_id)->row();
			
			if($task_completed) {
				echo json_encode(['success' => false, 'error' => 'You have already completed this task.']);
				exit;
			}
			
			if($task->mode == 0) {
				unset($input['token']);
				$input['chat_id'] = $chat_id;
				
				$db->insert("tasks_completed", $input);
				
				$res = runTelegramBotApi("sendMessage", [
					'chat_id' => $task->chat_id, 
					'text' => "*You have a new request to check the completion of task:*\n_" . $task->title . "_"
				]);
				
				echo json_encode(['success' => true, 'error' => '']);
			}
			else if($task->mode == 1) {
				if($task->balance < $task->price) {
					echo json_encode(['success' => false, 'error' => 'Sorry, someone has already completed this task before you. Due to this, the balance of the task is exhausted and we cannot credit you with the reward.']);
					exit;
				}
				
				if(strtolower($input['keyword']) != strtolower($task->keyword)) {
					echo json_encode(['success' => false, 'error' => 'Invalid verification code.']);
					exit;
				}
				
				unset($input['token']);
				$input['chat_id'] = $chat_id;
				$input['status'] = 1;
				
				$db->insert("tasks_completed", $input);
			
				$db->query("UPDATE `tasks` SET `balance` = (`balance` - `price`) WHERE `id` = " . $task->id);
			
				$coin = strtolower($task->currency) . '_coin';
				$db->query("UPDATE `users` SET `" . $coin . "` = (`" . $coin . "` + " . $task->price . ") WHERE `chat_id` = " . $chat_id);
			
				$input['transaction_id'] = $db->insert("transactions", 
					array(
						'chat_id' => $chat_id, 
						'type_id' => 4, 
						'object_id' => $task->id, 
						'sum' => $task->price, 
						'currency' => $task->currency
					)
				);
				
				echo json_encode(['success' => true, 'error' => '']);
			}
		} else {
			// http_response_code(400);
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}

	// BALANCE
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/balance/refill') !== false) {
		// Получаем данные запроса
		$data = json_decode(file_get_contents('php://input'), true);

		$amount = $data['amount'] ?? null;
		$currency = $data['currency'] ?? null;
		$walletId = $data['walletId'] ?? null;

		// Проверяем, что все необходимые данные присутствуют
		if (!$amount || !$currency || !$walletId) {
			// http_response_code(400); // Ошибка: некорректный запрос
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
			exit;
		}

		// Функция для вызова API Telegram Wallet и получения платежной ссылки
		function createTelegramPaymentLink($amount, $currency, $walletId) {
			// Данные, которые отправляются в API для создания платежа
			$postData = [
				'amount' => $amount,   // Сумма пополнения
				'currency' => $currency, // Валюта
				'wallet_id' => $walletId // Идентификатор кошелька
			];

			// Настройка cURL для отправки POST-запроса
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $telegramApiUrl . '/createInvoiceLink');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

			// Выполняем запрос и получаем результат
			$response = curl_exec($ch);
			curl_close($ch);

			// Обработка результата (предполагаем, что API возвращает JSON с payment_url)
			$result = json_decode($response, true);

			if (isset($result['payment_url'])) {
				return $result['payment_url'];
			} else {
				throw new Exception('Failed to create payment link');
			}
		}

		try {
			// Вызов функции для создания ссылки на оплату через Telegram Wallet
			$paymentUrl = createTelegramPaymentLink($amount, $currency, $walletId);

			// Возвращаем URL для оплаты в ответе
			echo json_encode(['success' => true, 'paymentUrl' => $paymentUrl]);
		} catch (Exception $e) {
			http_response_code(500); // Ошибка сервера
			echo json_encode(['success' => false, 'error' => $e->getMessage()]);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/balance/addRefillRequest') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token'])) {
			$jwtData = $jwt->decodeJWT($input['token']);
			$input['chat_id'] = (int) $jwtData['chat_id'];
			
			// Filtering input data
			if ($input['chat_id'] <= 0) {
				echo json_encode(['success' => false, 'error' => 'Invalid chat_id.']);
				exit;
			}
			
			$input['amount'] = (float)($input['amount'] ?? 0);
			if ($input['amount'] <= 0) {
				echo json_encode(['success' => false, 'error' => 'Invalid amount.']);
				exit;
			}
			
			if (!in_array($input['currency'], $availableCurrency)) {
				echo json_encode(['success' => false, 'error' => 'Invalid currency.']);
				exit;
			}
			
			$input['address'] = addslashes($input['address'] ?? '');
			
			// We take only the necessary data
			$input = array_filter($input, function($value, $key) {
				return in_array($key, array("chat_id", "address", "amount", "currency"));
			}, ARRAY_FILTER_USE_BOTH);
			
			$input['request_id'] = $db->insert("refill_requests", $input);
			
			$res = runTelegramBotApi("addRefillRequest", $input);
			
			echo json_encode(['success' => true, 'error' => '']);
		} else {
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/balance/addWithdrawalRequest') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token'])) {
			$jwtData = $jwt->decodeJWT($input['token']);
			$input['chat_id'] = (int) $jwtData['chat_id'];
			
			// Filtering input data
			if ($input['chat_id'] <= 0) {
				echo json_encode(['success' => false, 'error' => 'Invalid chat_id.']);
				exit;
			}
			
			$input['amount'] = (float)($input['amount'] ?? 0);
			if ($input['amount'] <= 0) {
				echo json_encode(['success' => false, 'error' => 'Invalid amount.']);
				exit;
			}
			
			if (!in_array($input['currency'], $availableCurrency)) {
				echo json_encode(['success' => false, 'error' => 'Invalid currency.']);
				exit;
			}
			
			$user = $db->select("users", "`chat_id` = " . $input['chat_id'])->row();
			$coin = strtolower($input['currency']) . '_coin';
			if($user->$coin < $input['amount']) {
				echo json_encode(['success' => false, 'error' => 'The amount on your balance is less than the amount specified in the withdrawal form.']);
				exit;
			}
			
			$input['address'] = addslashes($input['address'] ?? '');
			
			// We take only the necessary data
			$input = array_filter($input, function($value, $key) {
				return in_array($key, array("chat_id", "address", "amount", "currency"));
			}, ARRAY_FILTER_USE_BOTH);
			
			$input['request_id'] = $db->insert("withdrawal_requests", $input);
			
			$res = runTelegramBotApi("addWithdrawalRequest", $input);
			
			echo json_encode(['success' => true, 'error' => '']);
		} else {
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/balance/confirmRefillRequest') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token']) && $input['token'] === $apiToken) {
			$request = $db->select("refill_requests", "`id` = " . $input['request_id'])->row();
			
			if($request->status == 0) {
				$coin = strtolower($request->currency) . '_coin';
				$db->query("UPDATE `users` SET `" . $coin . "` = (`" . $coin . "` + " . $request->amount . ") WHERE `chat_id` = " . $request->chat_id);
			
				$db->insert("transactions", 
					array(
						'chat_id' => $request->chat_id, 
						'type_id' => 1, 
						'object_id' => $input['request_id'], 
						'sum' => $request->amount, 
						'currency' => $request->currency
					)
				);
				
				$res = $db->update("refill_requests", ['status' => 1], "`id` = " . $input['request_id']);
				
				echo json_encode(['success' => true, 'error' => '', 'user_chat_id' => $request->chat_id, 'user_msg' => "*Thank you!* Your account has been refill with *+" . $request->amount . " " . $request->currency . "*."]);
			} else {
				echo json_encode(['success' => false, 'error' => '*Request #*`' . $input['request_id'] . '` already checked.']);
			}
		} else {
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/balance/declineRefillRequest') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token']) && $input['token'] === $apiToken) {
			$request = $db->select("refill_requests", "`id` = " . $input['request_id'])->row();
			
			if($request->status == 0) {
				$res = $db->update("refill_requests", ['status' => 2], "`id` = " . $input['request_id']);
				
				echo json_encode(['success' => true, 'error' => '', 'user_chat_id' => $request->chat_id, 'user_msg' => "*Request #*`" . $input['request_id'] . "`\nAttempt refill was rejected.\nWe did not find a transfer coins from the address specified in the deposit form."]);
			} else {
				echo json_encode(['success' => false, 'error' => '*Request #*`' . $input['request_id'] . '` already checked.']);
			}
		} else {
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/balance/confirmWithdrawalRequest') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token']) && $input['token'] === $apiToken) {
			$request = $db->select("withdrawal_requests", "`id` = " . $input['request_id'])->row();
			
			if($request->status == 0) {
				$coin = strtolower($request->currency) . '_coin';
				$db->query("UPDATE `users` SET `" . $coin . "` = (`" . $coin . "` - " . $request->amount . ") WHERE `chat_id` = " . $request->chat_id);
			
				$db->insert("transactions", 
					array(
						'chat_id' => $request->chat_id, 
						'type_id' => 2, 
						'object_id' => $input['request_id'], 
						'sum' => -1 * $request->amount, 
						'currency' => $request->currency
					)
				);
				
				$res = $db->update("withdrawal_requests", ['status' => 1], "`id` = " . $input['request_id']);
				
				echo json_encode(['success' => true, 'error' => '', 'user_chat_id' => $request->chat_id, 'user_msg' => "*Thank you!* Request #`" . $input['request_id'] . "` for withdrawal of " . $request->amount . " " . $request->currency . " coins has been completed. Expect the transfer to be confirmed soon."]);
			} else {
				echo json_encode(['success' => false, 'error' => '*Request #*`' . $input['request_id'] . '` already checked.']);
			}
		} else {
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/balance/declineWithdrawalRequest') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token']) && $input['token'] === $apiToken) {
			$request = $db->select("withdrawal_requests", "`id` = " . $input['request_id'])->row();
			
			if($request->status == 0) {
				$res = $db->update("withdrawal_requests", ['status' => 2], "`id` = " . $input['request_id']);
				
				echo json_encode(['success' => true, 'error' => '', 'user_chat_id' => $request->chat_id, 'user_msg' => "*Request #*`" . $input['request_id'] . "`\nYour withdrawal attempt was rejected.\nTo clarify the reason, please contact the administration."]);
			} else {
				echo json_encode(['success' => false, 'error' => '*Request #*`' . $input['request_id'] . '` already checked.']);
			}
		} else {
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/updateCurrency') !== false) {
		$input = json_decode(file_get_contents('php://input'), true);
		
		if ($input && isset($input['token']) && $input['token'] === $apiToken) {
			$rates = [];
			foreach($input['prices'] as $key => $value) {
				$key = strtolower(str_replace('USDT', '', $key));
				$rates[$key] = $value;
			}
			$db->insert("cryptocurrency_rates", $rates);
			
			echo json_encode(['success' => true, 'error' => '']);
		} else {
			echo json_encode(['success' => false, 'error' => 'Invalid input data.']);
		}
	}
?>