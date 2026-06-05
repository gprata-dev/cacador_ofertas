<?php 

namespace Models;

use PDO;
use PDOException;

class FreeGamesModel
{
    private string $table = "free_games";
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Insert a new game
     * 
     * @param string $appId App ID
     * @param string $title Game title
     * @param string $link  Game link
     * @return array        Array with status and return
     */
    public function insertNewGame(string $appId, string $title, string $link): array
    {
        $resCheck = $this->checkForRecentlyAddedGames($link);
        if ($resCheck['status'] === 'error') {
            return $resCheck;
        }
        if ($resCheck['return'] === true) {
            return ['status' => 'success', 'return' => 0];
        }

        $query = "INSERT INTO {$this->table} (app_id, title, link)
                    VALUES (:app_id, :title, :link) 
                    ON DUPLICATE KEY UPDATE created_at = NOW()";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':app_id', $appId, PDO::PARAM_STR);
            $stmt->bindValue(':title', $title, PDO::PARAM_STR);
            $stmt->bindValue(':link', $link, PDO::PARAM_STR);
            $stmt->execute();

            return ['status' => 'success', 'return' => $stmt->rowCount()];
        } catch (PDOException $e) {
            return ['status' => 'error', 'return' => 'insertNewGame ' . $e->getMessage()];
        }
    }

    private function checkForRecentlyAddedGames(string $link): array
    {
        $checkQuery = "SELECT id FROM {$this->table} 
                        WHERE link = :link 
                        AND created_at >= NOW() - INTERVAL 1 DAY 
                        LIMIT 1";
        try {
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindValue(':link', $link, PDO::PARAM_STR);
            $checkStmt->execute();

            if($checkStmt->fetch()) {
                $updateQuery = "UPDATE {$this->table} 
                                SET created_at = NOW() 
                                WHERE link = :link";
                $updateStmt = $this->db->prepare($updateQuery);
                $updateStmt->bindValue(':link', $link, PDO::PARAM_STR);
                $updateStmt->execute();

                return ['status' => 'success', 'return' => true];
            }

            return ['status' => 'success', 'return' => false];
        } catch (PDOException $e) {
            return ['status' => 'error', 'return' => 'checkRecentlyAddedGames ' . $e->getMessage()];
        }
    }
}