<?php

$dbConfig  = require_once 'config/database.php';
$telConfig = require_once 'config/telegram.php';

require_once 'src/Database.php';
require_once 'src/TelegramBot.php';
require_once 'src/SteamScraper.php';
require_once 'src/RedditScraper.php';

date_default_timezone_set('America/Sao_Paulo');

echo "[!] Iniciando Caçador de Ofertas...\n";

$steamScraper  = new SteamScraper();
$redditScraper = new RedditScraper();
$telegram      = new TelegramBot($telConfig);
$db            = (new Database($dbConfig))->getConnection();

$inicialTelReturn = $telegram->sendMessage("🚨 Caçador de Ofertas iniciado!");
if ($inicialTelReturn['status'] === 'error') {
    echo "[FALHA TELEGRAM] " . $inicialTelReturn['message'] . "\n";
    exit;
}

$query = "INSERT INTO free_games (app_id, title, link)
            VALUES (:app_id, :title, :link) 
            ON DUPLICATE KEY UPDATE id=id";
$stmt  = $db->prepare($query);

while (true) {
    $actualTime = (int)date('G');
    if ($actualTime >= 0 && $actualTime <= 7) {
        echo "[" . date('H:i:s') . "] Dentro do horário de silêncio (0h às 7h). Daemon em espera...\n";
        sleep(600);
        continue;
    }

    try {
        $db->query("SELECT 1");
    } catch (\PDOException $e) {
        echo "[" . date('H:i:s') . "] Conexão com o banco perdida. Reconectando...\n";
        $db = (new Database($dbConfig))->getConnection();
    }

    echo "[" . date('H:i:s') . "] Varrendo a Steam...\n";
    $steamGames = $steamScraper->searchFreeGames();

    if(empty($steamGames)) {
        echo "Nenhum jogo gratuito encontrado na Steam\n";
    } else {
        foreach ($steamGames as $game) {
            try {
                $stmt->bindValue(':app_id', $game['app_id'], PDO::PARAM_STR);
                $stmt->bindValue(':title', $game['title'], PDO::PARAM_STR);
                $stmt->bindValue(':link', $game['link'], PDO::PARAM_STR);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    echo "🚨 STEAM: {$game['title']}!\n";
                    $telText   = "🚨 <b>NOVO JOGO GRÁTIS NA STEAM!</b>\n\n";
                    $telText  .= "🎮 <b>{$game['title']}</b>\n";
                    $telText  .= "👉<a href='{$game['link']}'>{$game['link']}</a>";

                    $telReturn = $telegram->sendMessage($telText);
                    if ($telReturn['status'] === 'error') {
                        echo "[FALHA TELEGRAM] " . $telReturn['message'] . "\n";
                    }
                }
            } catch (\PDOException $e) {
                $dbError  = "[ERRO BANCO] " . $e->getMessage() . "\n";
                echo $dbError;                
                $errorTelReturn = $telegram->sendMessage($dbError);
                if ($errorTelReturn['status'] === 'error') {
                    echo "[FALHA TELEGRAM] " . $errorTelReturn['message'] . "\n";
                }
            }
        }
    }

    
    echo "[" . date('H:i:s') . "] Varrendo o r/FreeGameFindings...\n";
    $redditGames = $redditScraper->searchFreeGames();

    if(empty($redditGames)) {
        echo "[FALHA] Nenhum jogo gratuito encontrado no Reddit\n";
        $errorTelReturn = $telegram->sendMessage("🚨 [FALHA] Nenhum jogo gratuito encontrado no Reddit");
    } else {
        foreach ($redditGames as $game) {
            try {
                $stmt->bindValue(':app_id', $game['app_id'], PDO::PARAM_STR);
                $stmt->bindValue(':title', $game['title'], PDO::PARAM_STR);
                $stmt->bindValue(':link', $game['link'], PDO::PARAM_STR);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    echo "🚨 REDDIT: {$game['title']}!\n";
                    $telText   = "🚨 <b>NOVO POST NO REDDIT!</b>\n\n";
                    $telText  .= "🎮 <b>{$game['title']}</b>\n";
                    $telText  .= "👉<a href='{$game['link']}'>{$game['link']}</a>\n\n";
                    $telText  .= "<a href='https://www.reddit.com/r/FreeGameFindings/comments/{$game['app_id']}/'>https://www.reddit.com/r/FreeGameFindings/comments/{$game['app_id']}/</a>";

                    $telReturn = $telegram->sendMessage($telText);
                    if ($telReturn['status'] === 'error') {
                        echo "[FALHA TELEGRAM] " . $telReturn['message'] . "\n";
                    }
                }
            } catch (\PDOException $e) {
                $dbError  = "[ERRO BANCO] " . $e->getMessage() . "\n";
                echo $dbError;                
                $errorTelReturn = $telegram->sendMessage($dbError);
                if ($errorTelReturn['status'] === 'error') {
                    echo "[FALHA TELEGRAM] " . $errorTelReturn['message'] . "\n";
                }
            }
        }
    }

    $waitingTime = rand(10, 15);
    echo "Dormindo por {$waitingTime} segundos...\n----------------------\n";
    sleep($waitingTime);
}