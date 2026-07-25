<?php

$dbConfig  = require_once __DIR__ . '/../config/database.php';
$telConfig = require_once __DIR__ . '/../config/telegram.php';

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/TelegramClient.php';
require_once __DIR__ . '/../src/RedditScraper.php';
require_once __DIR__ . '/../src/Services/FreeGamesService.php';
require_once __DIR__ . '/../src/Models/FreeGamesModel.php';

use Services\FreeGamesService;
use Models\FreeGamesModel;

date_default_timezone_set('America/Sao_Paulo');

$redditScraper    = new RedditScraper();
$telegramClient   = new TelegramClient($telConfig);
$db               = (new Database($dbConfig))->getConnection();
$freeGamesModel   = new FreeGamesModel($db);
$freeGamesService = new FreeGamesService($freeGamesModel, $telegramClient);

echo "[!] Iniciando Daemon Rápido (Reddit)...\n";

$redditFails     = 0;
$failsLimit      = 3;
$redditCicles    = 0;
$redditMaxCicles = rand(6, 9);

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
        echo "[" . date('H:i:s') . "] Varrendo o r/FreeGameFindings...\n";
        try {
            $redditGames = $redditScraper->searchFreeGames();
            $redditFails = 0;

            $freeGamesService->insertRedditGame($redditGames);
        } catch (Exception $e) {
            $redditFails++;
            $redditError = "[FALHA REDDIT] {$redditFails}/{$failsLimit}: " . $e->getMessage() . "\n";
            echo $redditError;

            if ($redditFails >= $failsLimit) {
                $errorTelReturn = $telegramClient->sendMessage($redditError);
                if ($errorTelReturn['status'] === 'error') {
                    echo "[FALHA TELEGRAM] " . $errorTelReturn['return'] . "\n";
                }

                $redditFails = 0;
            }

            if (str_contains($redditError, '429')) {
                echo "[" . date('H:i:s') . "] Dormindo por 5 minutos...\n----------------------\n";
                $redditCicles = 0;
                sleep(300);
                continue;
            }
        }

        if ($redditCicles >= $redditMaxCicles) {
            $scanInterval    = rand(120, 150);
            echo "[" . date('H:i:s') . "] Após {$redditMaxCicles} ciclos, dormindo por {$scanInterval} segundos...\n";
            $redditCicles    = 0;
            $redditMaxCicles = rand(6, 9);
        } else {
            $scanInterval    = rand(70, 90);
            echo "[" . date('H:i:s') . "] Dormindo por {$scanInterval} segundos...\n";
            $redditCicles++;
        }

        echo "----------------------\n";
        sleep($scanInterval);
    }
}