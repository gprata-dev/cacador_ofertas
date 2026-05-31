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
        $query = "INSERT INTO {$this->table} (app_id, title, link)
                    VALUES (:app_id, :title, :link) 
                    ON DUPLICATE KEY UPDATE id=id";
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
}