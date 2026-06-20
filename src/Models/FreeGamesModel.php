<?php 

namespace Models;

use PDO;
use PDOException;

class FreeGamesModel
{
    private string $table = "free_games";
    private PDO    $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Inserts a new game or updates an old one if it becomes free again.
     * Integrates a 24-hour spam shield to block redundant inserts from different scrapers.
     * 
     * @param string $appId App ID (Steam ID or Reddit Post ID)
     * @param string $title Game title
     * @param string $link  Game URL
     * @return array Associative array ['status' => 'success'|'error', 'return' => int|string].
     * If success, 'return' holds the affected rows:
     * 0 = Blocked by the 24h spam shield.
     * 1 = Brand new game inserted.
     * 2 = Old game updated (giveaway recurrence).
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

    /**
     * Checks if a game was added within the last 24 hours. 
     * If found, silently updates its created_at timestamp (Heartbeat) to prevent notifying the same giveaway multiple times.
     * 
     * @param string $link Game URL
     * @return array Associative array ['status' => 'success'|'error', 'return' => bool|string].
     * If success, 'return' is TRUE if found and updated, FALSE if not found.
     */
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