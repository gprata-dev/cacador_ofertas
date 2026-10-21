<?php 

namespace Models;

use PDO;
use PDOException;

class FreeGamesModel
{
    private string $table = "free_games";
    private PDO    $db;

    /**
     * Initializes the model with a database connection.
     * 
     * @param PDO $db Database connection.
     */
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
        $resCheck = $this->checkForRecentlyAddedGames($appId, $link);
        if ($resCheck['status'] === 'error') return $resCheck;
        if ($resCheck['return'] === true)    return ['status' => 'success', 'return' => 0];

        $query = "INSERT INTO {$this->table} (app_id, title, link)
                    VALUES (:app_id, :title, :link) 
                    ON DUPLICATE KEY UPDATE created_at = NOW(), link = :link";
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
     * Checks if a game was seen within the last 24 hours by ID or shared link.
     * If found, silently updates its created_at timestamp (Heartbeat) to prevent notifying the same giveaway multiple times.
     * 
     * @param string $appId Steam app ID or Reddit post ID
     * @param string $link Game URL
     * @return array       Associative array ['status' => 'success'|'error', 'return' => bool|string].
     *                     If success, 'return' is TRUE if found and updated, FALSE if not found.
     */
    private function checkForRecentlyAddedGames(string $appId, string $link): array
    {
        $checkQuery = "SELECT id FROM {$this->table} 
                        WHERE (app_id = :app_id OR link = :link)
                        AND created_at >= NOW() - INTERVAL 1 DAY 
                        LIMIT 1";
        try {
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindValue(':app_id', $appId, PDO::PARAM_STR);
            $checkStmt->bindValue(':link', $link, PDO::PARAM_STR);
            $checkStmt->execute();

            if($checkStmt->fetch()) {
                $updateQuery = "UPDATE {$this->table} 
                                SET created_at = NOW() 
                                WHERE (app_id = :app_id OR link = :link)";
                $updateStmt = $this->db->prepare($updateQuery);
                $updateStmt->bindValue(':app_id', $appId, PDO::PARAM_STR);
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