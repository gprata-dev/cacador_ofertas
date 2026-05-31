<?php

$dbConfig  = require_once 'config/database.php';
$telConfig = require_once 'config/telegram.php';

require_once 'src/Database.php';
require_once 'src/TelegramClient.php';
require_once 'src/SteamScraper.php';
require_once 'src/RedditScraper.php';
require_once 'src/Services/FreeGamesService.php';
require_once 'src/Services/TelegramService.php';
require_once 'src/Models/FreeGamesModel.php';
require_once 'src/Models/TrackedProductsModel.php';

use Services\FreeGamesService;
use Services\TelegramService;
use Models\FreeGamesModel;
use Models\TrackedProductsModel;

date_default_timezone_set('America/Sao_Paulo');

$steamScraper     = new SteamScraper();
$redditScraper    = new RedditScraper();
$telegramClient   = new TelegramClient($telConfig);
$db               = (new Database($dbConfig))->getConnection();
$freeGamesModel   = new FreeGamesModel($db);
$trackProdsModel  = new TrackedProductsModel($db);
$freeGamesService = new FreeGamesService($freeGamesModel, $telegramClient);
$telegramService  = new TelegramService($trackProdsModel, $telegramClient, $telConfig);

echo "[!] Iniciando Caçador de Ofertas...\n";
$inicialTelReturn = $telegramClient->sendMessage("🚨 Caçador de Ofertas iniciado!");
if ($inicialTelReturn['status'] === 'error') {
    echo "[FALHA TELEGRAM] " . $inicialTelReturn['return'] . "\n";
    exit;
}

$steamFails   = 0;
$redditFails  = 0;
$failsLimit   = 5;
$lastScan     = 0;
$scanInterval = rand(10, 15);

while (true) {
    try {
        $db->query("SELECT 1");
    } catch (PDOException $e) {
        echo "[" . date('H:i:s') . "] Conexão com o banco perdida. Reconectando...\n";
        $db = (new Database($dbConfig))->getConnection();
    }
    
    $telegramService->handleUserToBotMessages();
    
    $blockActualTime = (int)date('G');
    $isSilenced      = ($blockActualTime >= 0 && $blockActualTime < 7);

    $actualTime = time();
    if(($actualTime - $lastScan >= $scanInterval) && !$isSilenced) {
        echo "[" . date('H:i:s') . "] Varrendo a Steam...\n";
        try {
            $steamGames = $steamScraper->searchFreeGames();
            $steamFails = 0;

            $freeGamesService->insertSteamGame($steamGames);
        } catch (Exception $e) {
            $steamFails++;
            $steamError = "[FALHA STEAM] {$steamFails}/{$failsLimit}: " . $e->getMessage() . "\n";
            echo $steamError;

            if($steamFails >= $failsLimit) {
                $errorTelReturn = $telegramClient->sendMessage($steamError);
                if ($errorTelReturn['status'] === 'error') {
                    echo "[FALHA TELEGRAM] " . $errorTelReturn['return'] . "\n";
                }
                
                $steamFails = 0;
            }
        }
        
        echo "[" . date('H:i:s') . "] Varrendo o r/FreeGameFindings...\n";
        try {
            $redditGames = $redditScraper->searchFreeGames();
            $redditFails = 0;

            $freeGamesService->insertRedditGame($redditGames);
        } catch (Exception $e) {
            $redditFails++;
            $redditError = "[FALHA REDDIT] {$redditFails}/{$failsLimit}: " . $e->getMessage() . "\n";
            echo $redditError;

            if($redditFails >= $failsLimit) {
                $errorTelReturn = $telegramClient->sendMessage($redditError);
                if ($errorTelReturn['status'] === 'error') {
                    echo "[FALHA TELEGRAM] " . $errorTelReturn['return'] . "\n";
                }

                $redditFails = 0;
            }
        }

        $lastScan     = time();
        $scanInterval = rand(10, 15);

        echo "[" . date('H:i:s') . "] Dormindo por {$scanInterval} segundos...\n";
        echo "----------------------\n";
    }

    if($isSilenced && (time() - $lastScan >= 600)) {
        echo "[" . date('H:i:s') . "] Dentro do horário de silêncio (0h às 7h). Daemon em espera...\n";
        $lastScan = time();
    }

    sleep(1);
}