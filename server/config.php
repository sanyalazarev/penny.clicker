<?php
	$dbSettings = array(
		'hostname' => "",
		'database' => "",
		'username' => "",
		'password' => "",
		'dbprefix' => "",
		'char_set' => "utf8mb4"
	);
	
	$jwtSecretKey = "098f6bcd4621d373cade4e832627b4f6";

	$appUrl = 'https://clicker.halivan.com/';
	
	$apiUrl = 'https://clicker.halivan.com/api.php';
	$apiToken = "1553798bac7ba1b3773077c59a449b81";

	$telegramApiToken = ''; // [Bot ID]:[Secret authentication key]
	$telegramApiUrl = 'https://api.telegram.org/bot' . $telegramApiToken;
	
	$telegramAdminChatId = 476833501;

	$photoMaxDimensions = 500;
	
	$minWithdrawal = 3;

	$availableCurrency = ['TON', 'NOT', 'DOGS', 'HMSTR', 'X'];
	$availableLanguages = ['en', 'uk', 'es', 'ru'];