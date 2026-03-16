<?php
// classes/Medicine.php
class Medicine {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll(string $search = ''): array {
        $where = '';
        if ($search) {
            $s = $this->db->escape($search);
            $where = "WHERE name LIKE '%$s%' OR generic_name LIKE '%$s%' OR category LIKE '%$s%'";
        }
        return $this->db->fetchAll("SELECT * FROM medicines $where ORDER BY name ASC");
    }

    public function getById(int $id): ?array {
        return $this->db->fetchOne("SELECT * FROM medicines WHERE id = " . (int)$id);
    }

    public function getLowStock(): array {
        return $this->db->fetchAll("SELECT * FROM medicines WHERE quantity <= min_stock ORDER BY quantity ASC");
    }

    public function getExpiringSoon(int $days = 30): array {
        return $this->db->fetchAll("SELECT * FROM medicines WHERE expiry_date IS NOT NULL
            AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $days DAY)
            ORDER BY expiry_date ASC");
    }

    public function countLowStock(): int {
        return $this->db->fetchCount("SELECT COUNT(*) FROM medicines WHERE quantity <= min_stock");
    }

    public function create(array $data): array {
        $fields = ['name','generic_name','category','description','quantity','unit','min_stock','dosage','expiry_date','supplier','created_by'];
        $vals = [];
        foreach ($fields as $f) {
            $v = (isset($data[$f]) && $data[$f] !== '') ? "'" . $this->db->escape($data[$f]) . "'" : 'NULL';
            $vals[] = $v;
        }
        $sql = "INSERT INTO medicines (" . implode(',', $fields) . ") VALUES (" . implode(',', $vals) . ")";
        $this->db->query($sql);
        $id = $this->db->lastInsertId();
        if ($id) {
            $this->logAction($id, 'Added', (int)($data['quantity'] ?? 0), 0, (int)($data['quantity'] ?? 0), null, 'Initial stock added', (int)($data['created_by'] ?? 0));
            return ['success' => true, 'id' => $id, 'message' => 'Medicine added successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to add medicine.'];
    }

    public function update(int $id, array $data): array {
        $id = (int)$id;
        $current = $this->getById($id);
        $fields = ['name','generic_name','category','description','quantity','unit','min_stock','dosage','expiry_date','supplier'];
        $sets = [];
        foreach ($fields as $f) {
            if (isset($data[$f])) {
                $sets[] = "$f = '" . $this->db->escape($data[$f]) . "'";
            }
        }
        if (empty($sets)) return ['success' => false, 'message' => 'No data to update.'];
        $this->db->query("UPDATE medicines SET " . implode(',', $sets) . " WHERE id = $id");
        // Log quantity change
        if (isset($data['quantity']) && $current && (int)$data['quantity'] !== (int)$current['quantity']) {
            $diff = (int)$data['quantity'] - (int)$current['quantity'];
            $action = $diff > 0 ? 'Added' : 'Adjusted';
            $this->logAction($id, $action, abs($diff), (int)$current['quantity'], (int)$data['quantity'], null, 'Stock updated', (int)($data['updated_by'] ?? 0));
        }
        return ['success' => true, 'message' => 'Medicine updated.'];
    }

    public function delete(int $id): array {
        $id = (int)$id;
        $this->db->query("DELETE FROM medicines WHERE id = $id");
        return $this->db->affectedRows() > 0
            ? ['success' => true, 'message' => 'Medicine removed.']
            : ['success' => false, 'message' => 'Medicine not found.'];
    }

    public function dispense(int $id, int $qty, int $refId, int $doneBy): array {
        $med = $this->getById($id);
        if (!$med) return ['success' => false, 'message' => 'Medicine not found.'];
        if ($med['quantity'] < $qty) return ['success' => false, 'message' => 'Insufficient stock.'];
        $newQty = (int)$med['quantity'] - $qty;
        $this->db->query("UPDATE medicines SET quantity = $newQty WHERE id = $id");
        $this->logAction($id, 'Dispensed', $qty, (int)$med['quantity'], $newQty, $refId, 'Dispensed via request', $doneBy);
        return ['success' => true, 'message' => 'Medicine dispensed.', 'new_quantity' => $newQty];
    }

    private function logAction(int $medId, string $action, int $qty, int $before, int $after, ?int $refId, string $notes, int $doneBy): void {
        $notes = $this->db->escape($notes);
        $ref = $refId ? $refId : 'NULL';
        $this->db->query("INSERT INTO medicine_logs (medicine_id, action, quantity, quantity_before, quantity_after, reference_id, notes, done_by)
            VALUES ($medId, '$action', $qty, $before, $after, $ref, '$notes', $doneBy)");
    }

    public function count(): int {
        return $this->db->fetchCount("SELECT COUNT(*) FROM medicines");
    }
}
