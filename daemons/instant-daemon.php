<?php

$dbConfig  = require_once __DIR__ . '/../config/database.php';
$telConfig = require_once __DIR__ . '/../config/telegram.php';

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/TelegramClient.php';
require_once __DIR__ . '/../src/Services/TelegramService.php';
require_once __DIR__ . '/../src/Models/TrackedProductsModel.php';

use Services\TelegramService;
use Models\TrackedProductsModel;

date_default_timezone_set('America/Sao_Paulo');

$telegramClient   = new TelegramClient($telConfig);
$db               = (new Database($dbConfig))->getConnection();
$trackProdsModel  = new TrackedProductsModel($db);
$telegramService  = new TelegramService($trackProdsModel, $telegramClient, $telConfig);

echo "[!] Iniciando Caçador de Ofertas...\n";
$inicialTelReturn = $telegramClient->sendMessage("🚨 Caçador de Ofertas iniciado!");
if ($inicialTelReturn['status'] === 'error') {
    echo "[FALHA TELEGRAM] " . $inicialTelReturn['return'] . "\n";
    exit;
}

while (true) {
    try {
        $db->query("SELECT 1");
    } catch (PDOException $e) {
        echo "[" . date('H:i:s') . "] Conexão com o banco perdida. Reconectando...\n";
        $db = (new Database($dbConfig))->getConnection();
        $trackProdsModel = new TrackedProductsModel($db);
        $telegramService = new TelegramService($trackProdsModel, $telegramClient, $telConfig);
    }
    
    $telegramService->handleUserToBotMessages();

    sleep(1);
}