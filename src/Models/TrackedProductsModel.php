<?php 

namespace Models;

use PDO;
use PDOException;

class TrackedProductsModel
{
    private string $table = 'tracked_products';
    private PDO    $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Update the target price of a product
     * 
     * @param float $newPrice       New target price
     * @param int   $underEditingId Product ID
     * @return array                Associative array ['status' => 'success'|'error', 'return' => int|string].
     */
    public function updateProduct(float $newPrice, int $underEditingId): array
    {
        $query = "UPDATE {$this->table} 
                    SET target_price = :price 
                    WHERE id = :id";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':price', $newPrice);
            $stmt->bindValue(':id', $underEditingId);
            $stmt->execute();

            return ['status' => 'success', 'return' => $stmt->rowCount()];
        } catch (PDOException $e) {
            return ['status' => 'error', 'return' => 'updateProduct ' . $e->getMessage()];
        }
    }

    /**
     * Insert a new product
     * 
     * @param string $url         Product URL
     * @param float  $targetPrice Target price
     * @return array              Associative array ['status' => 'success'|'error', 'return' => int|string].
     */
    public function insertProduct(string $url, float $targetPrice): array
    {
        $query = "INSERT INTO {$this->table} (url, target_price) 
                    VALUES (:url, :price) 
                    ON DUPLICATE KEY UPDATE target_price = :price";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':url', $url);
            $stmt->bindValue(':price', $targetPrice);
            $stmt->execute();

            return ['status' => 'success', 'return' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ['status' => 'error', 'return' => 'insertProduct ' . $e->getMessage()];
        }
    }

    /**
     * Get all tracked products
     * 
     * @return array Associative array ['status' => 'success'|'error', 'return' => array|string].
     */
    public function getAllProducts(): array
    {
        $query = "SELECT id, product_name, target_price, last_price 
                    FROM {$this->table} 
                    ORDER BY id DESC";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return ['status' => 'success', 'return' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (PDOException $e) {
            return ['status' => 'error', 'return' => 'getAllProducts ' . $e->getMessage()];
        }
    }

    /**
     * Delete a product
     * 
     * @param int    $id Product ID
     * @return array     Associative array ['status' => 'success'|'error', 'return' => int|string].
     */
    public function deleteProduct(int $id): array
    {
        $query = "DELETE FROM {$this->table} 
                    WHERE id = :id";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id', $id);
            $stmt->execute();

            return ['status' => 'success', 'return' => $stmt->rowCount()];
        } catch (PDOException $e) {
            return ['status' => 'error', 'return' => 'deleteProduct ' . $e->getMessage()];
        }
    }
}