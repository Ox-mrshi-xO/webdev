<?php
// classes/Patient.php
class Patient {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll(string $search = ''): array {
        $where = '';
        if ($search) {
            $s = $this->db->escape($search);
            $where = "WHERE p.full_name LIKE '%$s%' OR p.student_id LIKE '%$s%' OR p.email LIKE '%$s%'";
        }
        return $this->db->fetchAll("SELECT p.*, u.full_name AS created_by_name
            FROM patients p
            LEFT JOIN users u ON p.created_by = u.id
            $where ORDER BY p.created_at DESC");
    }

    public function getById(int $id): ?array {
        $id = (int)$id;
        return $this->db->fetchOne("SELECT * FROM patients WHERE id = $id");
    }

    public function getByUserId(int $userId): ?array {
        $userId = (int)$userId;
        return $this->db->fetchOne("SELECT * FROM patients WHERE user_id = $userId");
    }

    public function create(array $data): array {
        $fields = ['student_id','full_name','email','phone','address','date_of_birth',
                   'gender','blood_type','emergency_contact','emergency_phone','allergies','notes','created_by'];
        $vals = [];
        foreach ($fields as $f) {
            $v = isset($data[$f]) ? "'" . $this->db->escape($data[$f]) . "'" : 'NULL';
            $vals[] = $v;
        }
        $sql = "INSERT INTO patients (" . implode(',', $fields) . ") VALUES (" . implode(',', $vals) . ")";
        $this->db->query($sql);
        $id = $this->db->lastInsertId();
        if ($id) {
            // Link to user account if student_id matches
            if (!empty($data['student_id'])) {
                $sid = $this->db->escape($data['student_id']);
                $user = $this->db->fetchOne("SELECT id FROM users WHERE student_id = '$sid'");
                if ($user) {
                    $this->db->query("UPDATE patients SET user_id = {$user['id']} WHERE id = $id");
                }
            }
            return ['success' => true, 'id' => $id, 'message' => 'Patient record created.'];
        }
        return ['success' => false, 'message' => 'Failed to create patient.'];
    }

    public function update(int $id, array $data): array {
        $id = (int)$id;
        $fields = ['student_id','full_name','email','phone','address','date_of_birth',
                   'gender','blood_type','emergency_contact','emergency_phone','allergies','notes'];
        $sets = [];
        foreach ($fields as $f) {
            if (isset($data[$f])) {
                $sets[] = "$f = '" . $this->db->escape($data[$f]) . "'";
            }
        }
        if (empty($sets)) return ['success' => false, 'message' => 'No data to update.'];
        $sql = "UPDATE patients SET " . implode(',', $sets) . " WHERE id = $id";
        $this->db->query($sql);
        return ['success' => true, 'message' => 'Patient record updated.'];
    }

    public function delete(int $id): array {
        $id = (int)$id;
        $this->db->query("DELETE FROM patients WHERE id = $id");
        return $this->db->affectedRows() > 0
            ? ['success' => true, 'message' => 'Patient deleted.']
            : ['success' => false, 'message' => 'Patient not found.'];
    }

    public function count(): int {
        return $this->db->fetchCount("SELECT COUNT(*) FROM patients");
    }
}
