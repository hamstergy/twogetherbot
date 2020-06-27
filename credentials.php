<?php

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// создаем переменную бота
$bot_api_key  = $_ENV["BOT_API_KEY"];
$bot_username = $_ENV["BOT_USERNAME"];
$hook_url     = $_ENV["HOOK_URL"];
$servername  = $_ENV["DB_SERVER"];
$dbuser = $_ENV["DB_USERNAME"];
$dbname = $_ENV["DB_NAME"];
$dbpassword = $_ENV["DB_PASSWORD"];
$conn = mysqli_connect($servername, $dbuser, $dbpassword, $dbname);
$conn->set_charset('utf8mb4');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
