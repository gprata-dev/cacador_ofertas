<?php 

namespace Services;

use Models\FreeGamesModel;
use TelegramClient;

class FreeGamesService
{
    private FreeGamesModel $freeGamesModel;
    private TelegramClient $telegram;

    public function __construct(FreeGamesModel $freeGamesModel, TelegramClient $telegram)
    {
        $this->freeGamesModel = $freeGamesModel;
        $this->telegram       = $telegram;
    }

    /**
     * Insert new free games from Steam in the database and alert in Telegram
     * 
     * @param array $games Array of free Steam games
     * @return void
     */
    public function insertSteamGame(array $games): void
    {
        if(empty($games)) {
            return;
        }

        foreach ($games as $game) {
            $appId  = $game['app_id'];
            $title  = $game['title'];
            $link   = $game['link'];

            $res = $this->freeGamesModel->insertNewGame($appId, $title, $link);
            if($res['status'] === 'error') {
                $dbError  = "[ERRO BANCO] " . $res['return'] . "\n";
                echo $dbError;                
                $errorTelReturn = $this->telegram->sendMessage($dbError);
                if ($errorTelReturn['status'] === 'error') {
                    echo "[FALHA TELEGRAM] " . $errorTelReturn['return'] . "\n";
                }
            }

            if($res['return'] > 0) {
                echo "🚨 STEAM: {$title}!\n";
                $telText   = "🚨 <b>NOVO JOGO GRÁTIS NA STEAM!</b>\n\n";
                $telText  .= "🎮 <b>{$title}</b>\n";
                $telText  .= "👉<a href='{$link}'>{$link}</a>";

                $telReturn = $this->telegram->sendMessage($telText);
                if ($telReturn['status'] === 'error') {
                    echo "[FALHA TELEGRAM] " . $telReturn['return'] . "\n";
                }
            }
        }
    }

    /**
     * Insert new free games from Reddit in the database and alert in Telegram
     * 
     * @param array $games Array of free Reddit games
     * @return void
     */
    public function insertRedditGame(array $games): void
    {
        if(empty($games)) {
            return;
        }
        
        foreach ($games as $game) {
            $appId  = $game['app_id'];
            $title  = $game['title'];
            $link   = $game['link'];

            $res = $this->freeGamesModel->insertNewGame($appId, $title, $link);
            if($res['status'] === 'error') {
                $dbError  = "[ERRO BANCO] " . $res['return'] . "\n";
                echo $dbError;                
                $errorTelReturn = $this->telegram->sendMessage($dbError);
                if ($errorTelReturn['status'] === 'error') {
                    echo "[FALHA TELEGRAM] " . $errorTelReturn['return'] . "\n";
                }
            }

            if ($res['return'] > 0) {
                echo "🚨 REDDIT: {$game['title']}!\n";
                $telText   = "🚨 <b>NOVO POST NO REDDIT!</b>\n\n";
                $telText  .= "🎮 <b>{$game['title']}</b>\n";
                $telText  .= "👉<a href='{$game['link']}'>{$game['link']}</a>\n\n";
                $telText  .= "<a href='https://www.reddit.com/r/FreeGameFindings/comments/{$game['app_id']}/'>https://www.reddit.com/r/FreeGameFindings/comments/{$game['app_id']}/</a>";

                $telReturn = $this->telegram->sendMessage($telText);
                if ($telReturn['status'] === 'error') {
                    echo "[FALHA TELEGRAM] " . $telReturn['return'] . "\n";
                }
            }
        }
    }
}