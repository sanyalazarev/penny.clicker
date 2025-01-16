<?php
	class jwt {
		private $secret_key;
		
		public function __construct($secret_key) {
			$this->secret_key = $secret_key;
		}
			
		public function generateJWT($data) {
			// Заголовок токена
			$header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

			// Полезная нагрузка (payload) с информацией о пользователе
			$data['iat'] = time();
			$payload = json_encode($data);

			// Кодируем header и payload в Base64Url
			$base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
			$base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

			// Создаем подпись токена
			$signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret_key, true);
			$base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

			// Собираем токен
			$jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

			return $jwt;
		}
	
		public function decodeJWT($jwt) {
			// Разделяем токен на три части
			$tokenParts = explode('.', $jwt);
			if (count($tokenParts) !== 3) {
				return false; // Неверный формат токена
			}
			
			// Получаем заголовок, полезную нагрузку и подпись
			$header = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0]));
			$payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
			$signature_provided = $tokenParts[2];

			// Проверяем подпись токена
			$base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
			$base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
			$signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret_key, true);
			$base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

			// Сравниваем предоставленную подпись и вычисленную подпись
			if ($base64UrlSignature !== $signature_provided) {
				return false; // Неверная подпись, токен недействителен
			}

			// Декодируем полезную нагрузку
			$payloadArray = json_decode($payload, true);
			
			return $payloadArray;
		}
	}
?>