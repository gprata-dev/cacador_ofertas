<?php

$dbConfig  = require_once __DIR__ . '/../config/database.php';
$telConfig = require_once __DIR__ . '/../config/telegram.php';

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/TelegramClient.php';
require_once __DIR__ . '/../src/SteamScraper.php';
require_once __DIR__ . '/../src/Services/FreeGamesService.php';
require_once __DIR__ . '/../src/Models/FreeGamesModel.php';

use Services\FreeGamesService;
use Models\FreeGamesModel;

date_default_timezone_set('America/Sao_Paulo');

$steamScraper     = new SteamScraper();
$telegramClient   = new TelegramClient($telConfig);
$db               = (new Database($dbConfig))->getConnection();
$freeGamesModel   = new FreeGamesModel($db);
$freeGamesService = new FreeGamesService($freeGamesModel, $telegramClient);

echo "[!] Iniciando Daemon Super Rápido (Steam)...\n";

$steamFails     = 0;
$failsLimit     = 5;
$steamCicles    = 0;
$steamMaxCicles = rand(12, 15);

while (true) {
    try {
        $db->query("SELECT 1");
    } catch (PDOException $e) {
        echo "[" . date('H:i:s') . "] Conexão com o banco perdida. Reconectando...\n";
        $db = (new Database($dbConfig))->getConnection();
        $freeGamesModel   = new FreeGamesModel($db);
        $freeGamesService = new FreeGamesService($freeGamesModel, $telegramClient);
    }
    
    $blockActualTime = (int)date('G');
    $isSilenced      = ($blockActualTime >= 0 && $blockActualTime < 7);

    if($isSilenced) {
        echo "[" . date('H:i:s') . "] Horário de silêncio (0h às 7h). Em espera...\n";
        sleep(600);

    } else {
        echo "[" . date('H:i:s') . "] Varrendo a Steam...\n";
        try {
            $steamGames = $steamScraper->searchFreeGames();
            $steamFails = 0;

            $freeGamesService->insertSteamGame($steamGames);
        } catch (Exception $e) {
            $steamFails++;
            $steamError = "[FALHA STEAM] {$steamFails}/{$failsLimit}: " . $e->getMessage() . "\n";
            echo $steamError;

            if ($steamFails >= $failsLimit) {
                $errorTelReturn = $telegramClient->sendMessage($steamError);
                if ($errorTelReturn['status'] === 'error') {
                    echo "[FALHA TELEGRAM] " . $errorTelReturn['return'] . "\n";
                }
                
                $steamFails = 0;
            }

            if (str_contains($steamError, '429')) {
                echo "[" . date('H:i:s') . "] Dormindo por 5 minutos...\n----------------------\n";
                $steamCicles = 0;
                sleep(300);
                continue;
            }
        }

        if ($steamCicles >= $steamMaxCicles) {
            $scanInterval    = rand(45, 60);
            echo "[" . date('H:i:s') . "] Após {$steamMaxCicles} ciclos, dormindo por {$scanInterval} segundos...\n";
            $steamCicles    = 0;
            $steamMaxCicles = rand(12, 15);
        } else {
            $scanInterval   = rand(20, 30);
            echo "[" . date('H:i:s') . "] Dormindo por {$scanInterval} segundos...\n";
            $steamCicles++;
        }

        echo "----------------------\n";
        sleep($scanInterval);
    }
}