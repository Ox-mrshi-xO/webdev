<?php
// classes/Appointment.php
class Appointment {
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function getAll(string $filter = ''): array {
        $where = $filter ? "WHERE a.status = '" . $this->db->escape($filter) . "'" : '';
        return $this->db->fetchAll("SELECT a.*, p.full_name AS patient_name, p.student_id,
            u.full_name AS user_name
            FROM appointments a
            LEFT JOIN patients p ON a.patient_id = p.id
            LEFT JOIN users u ON a.user_id = u.id
            $where ORDER BY a.appointment_date DESC, a.appointment_time DESC");
    }

    public function getByUser(int $userId): array {
        $userId = (int)$userId;
        return $this->db->fetchAll("SELECT a.*, p.full_name AS patient_name
            FROM appointments a
            LEFT JOIN patients p ON a.patient_id = p.id
            WHERE a.user_id = $userId ORDER BY a.appointment_date DESC");
    }

    public function getById(int $id): ?array {
        return $this->db->fetchOne("SELECT a.*, p.full_name AS patient_name, p.student_id
            FROM appointments a LEFT JOIN patients p ON a.patient_id = p.id WHERE a.id = " . (int)$id);
    }

    public function getUpcoming(): array {
        return $this->db->fetchAll("SELECT a.*, p.full_name AS patient_name
            FROM appointments a LEFT JOIN patients p ON a.patient_id = p.id
            WHERE a.appointment_date >= CURDATE() AND a.status = 'Approved'
            ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 10");
    }

    public function getTodayCount(): int {
        return $this->db->fetchCount("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()");
    }

    public function create(array $data): array {
        $fields = ['patient_id','user_id','appointment_date','appointment_time','type','status','reason','notes','created_by'];
        $vals = [];
        foreach ($fields as $f) {
            $v = (isset($data[$f]) && $data[$f] !== '') ? "'" . $this->db->escape($data[$f]) . "'" : 'NULL';
            $vals[] = $v;
        }
        $this->db->query("INSERT INTO appointments (" . implode(',', $fields) . ") VALUES (" . implode(',', $vals) . ")");
        $id = $this->db->lastInsertId();
        return $id ? ['success' => true, 'id' => $id, 'message' => 'Appointment created.'] : ['success' => false, 'message' => 'Failed.'];
    }

    public function updateStatus(int $id, string $status, string $notes = ''): array {
        $id = (int)$id;
        $status = $this->db->escape($status);
        $notes  = $this->db->escape($notes);
        $this->db->query("UPDATE appointments SET status = '$status', notes = '$notes' WHERE id = $id");
        return ['success' => true, 'message' => 'Status updated.'];
    }

    public function delete(int $id): array {
        $this->db->query("DELETE FROM appointments WHERE id = " . (int)$id);
        return ['success' => true, 'message' => 'Appointment deleted.'];
    }
}

// classes/MedicalRecord.php
class MedicalRecord {
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function getByPatient(int $patientId): array {
        $patientId = (int)$patientId;
        return $this->db->fetchAll("SELECT mr.*, u.full_name AS created_by_name
            FROM medical_records mr LEFT JOIN users u ON mr.created_by = u.id
            WHERE mr.patient_id = $patientId ORDER BY mr.visit_date DESC");
    }

    public function getById(int $id): ?array {
        return $this->db->fetchOne("SELECT mr.*, p.full_name AS patient_name, p.student_id,
            u.full_name AS created_by_name
            FROM medical_records mr
            LEFT JOIN patients p ON mr.patient_id = p.id
            LEFT JOIN users u ON mr.created_by = u.id
            WHERE mr.id = " . (int)$id);
    }

    public function getByUserPatient(int $userId): array {
        $userId = (int)$userId;
        return $this->db->fetchAll("SELECT mr.*, p.full_name AS patient_name
            FROM medical_records mr
            JOIN patients p ON mr.patient_id = p.id
            WHERE p.user_id = $userId ORDER BY mr.visit_date DESC");
    }

    public function create(array $data): array {
        $fields = ['patient_id','visit_date','visit_time','chief_complaint','diagnosis','treatment',
                   'prescription','blood_pressure','temperature','pulse_rate','weight','height','doctor_notes','follow_up_date','created_by'];
        $vals = [];
        foreach ($fields as $f) {
            $v = (isset($data[$f]) && $data[$f] !== '') ? "'" . $this->db->escape($data[$f]) . "'" : 'NULL';
            $vals[] = $v;
        }
        $this->db->query("INSERT INTO medical_records (" . implode(',', $fields) . ") VALUES (" . implode(',', $vals) . ")");
        $id = $this->db->lastInsertId();
        return $id ? ['success' => true, 'id' => $id, 'message' => 'Medical record added.'] : ['success' => false, 'message' => 'Failed.'];
    }

    public function update(int $id, array $data): array {
        $id = (int)$id;
        $fields = ['visit_date','visit_time','chief_complaint','diagnosis','treatment',
                   'prescription','blood_pressure','temperature','pulse_rate','weight','height','doctor_notes','follow_up_date'];
        $sets = [];
        foreach ($fields as $f) {
            if (isset($data[$f])) $sets[] = "$f = '" . $this->db->escape($data[$f]) . "'";
        }
        if (empty($sets)) return ['success' => false, 'message' => 'No data.'];
        $this->db->query("UPDATE medical_records SET " . implode(',', $sets) . " WHERE id = $id");
        return ['success' => true, 'message' => 'Record updated.'];
    }

    public function delete(int $id): array {
        $this->db->query("DELETE FROM medical_records WHERE id = " . (int)$id);
        return ['success' => true, 'message' => 'Record deleted.'];
    }

    public function countToday(): int {
        return $this->db->fetchCount("SELECT COUNT(*) FROM medical_records WHERE visit_date = CURDATE()");
    }
}

// classes/UserManager.php
class UserManager {
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function getAll(string $search = ''): array {
        $where = '';
        if ($search) {
            $s = $this->db->escape($search);
            $where = "WHERE full_name LIKE '%$s%' OR email LIKE '%$s%' OR student_id LIKE '%$s%'";
        }
        return $this->db->fetchAll("SELECT id,student_id,full_name,email,role,phone,gender,is_active,created_at FROM users $where ORDER BY created_at DESC");
    }

    public function getById(int $id): ?array {
        return $this->db->fetchOne("SELECT * FROM users WHERE id = " . (int)$id);
    }

    public function create(array $data): array {
        $email = $this->db->escape($data['email'] ?? '');
        $exists = $this->db->fetchOne("SELECT id FROM users WHERE email = '$email'");
        if ($exists) return ['success' => false, 'message' => 'Email already registered.'];
        $hash = password_hash($data['password'] ?? 'Clinic@1234', PASSWORD_BCRYPT);
        $fields = ['student_id','full_name','email','password','role','phone','gender','date_of_birth','address','blood_type','emergency_contact','emergency_phone'];
        $vals = [];
        $data['password'] = $hash;
        foreach ($fields as $f) {
            $v = (isset($data[$f]) && $data[$f] !== '') ? "'" . $this->db->escape($data[$f]) . "'" : 'NULL';
            $vals[] = $v;
        }
        $this->db->query("INSERT INTO users (" . implode(',', $fields) . ") VALUES (" . implode(',', $vals) . ")");
        $id = $this->db->lastInsertId();
        // Auto-create patient record for user role
        if ($id && ($data['role'] ?? 'user') === 'user') {
            $data['created_by'] = $data['created_by'] ?? 1;
            $data['user_id'] = $id;
            $patientFields = ['student_id','full_name','email','phone','gender','date_of_birth','address','blood_type','emergency_contact','emergency_phone'];
            $pVals = [];
            foreach ($patientFields as $f) {
                $v = (isset($data[$f]) && $data[$f] !== '') ? "'" . $this->db->escape($data[$f]) . "'" : 'NULL';
                $pVals[] = $v;
            }
            $pVals[] = $id;
            $pVals[] = $data['created_by'];
            $this->db->query("INSERT INTO patients (" . implode(',', $patientFields) . ", user_id, created_by) VALUES (" . implode(',', $pVals) . ")");
        }
        return $id ? ['success' => true, 'id' => $id, 'message' => 'User created.'] : ['success' => false, 'message' => 'Failed.'];
    }

    public function update(int $id, array $data): array {
        $id = (int)$id;
        $fields = ['full_name','phone','gender','date_of_birth','address','blood_type','emergency_contact','emergency_phone','is_active','role'];
        $sets = [];
        foreach ($fields as $f) {
            if (isset($data[$f])) $sets[] = "$f = '" . $this->db->escape($data[$f]) . "'";
        }
        if (!empty($data['password'])) {
            $hash = password_hash($data['password'], PASSWORD_BCRYPT);
            $sets[] = "password = '$hash'";
        }
        if (empty($sets)) return ['success' => false, 'message' => 'No data.'];
        $this->db->query("UPDATE users SET " . implode(',', $sets) . " WHERE id = $id");
        return ['success' => true, 'message' => 'User updated.'];
    }

    public function toggleActive(int $id): array {
        $this->db->query("UPDATE users SET is_active = NOT is_active WHERE id = " . (int)$id);
        return ['success' => true, 'message' => 'Status toggled.'];
    }

    public function count(): int {
        return $this->db->fetchCount("SELECT COUNT(*) FROM users WHERE role = 'user'");
    }
}

// classes/Notification.php
class Notification {
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function getByUser(int $userId): array {
        $userId = (int)$userId;
        return $this->db->fetchAll("SELECT * FROM notifications WHERE user_id = $userId ORDER BY created_at DESC");
    }

    public function getUnreadCount(int $userId): int {
        return $this->db->fetchCount("SELECT COUNT(*) FROM notifications WHERE user_id = " . (int)$userId . " AND is_read = 0");
    }

    public function markRead(int $id, int $userId): void {
        $this->db->query("UPDATE notifications SET is_read = 1 WHERE id = " . (int)$id . " AND user_id = " . (int)$userId);
    }

    public function markAllRead(int $userId): void {
        $this->db->query("UPDATE notifications SET is_read = 1 WHERE user_id = " . (int)$userId);
    }

    public function send(int $userId, string $title, string $message, string $type = 'general'): void {
        $userId = (int)$userId;
        $title   = $this->db->escape($title);
        $message = $this->db->escape($message);
        $type    = $this->db->escape($type);
        $this->db->query("INSERT INTO notifications (user_id, title, message, type) VALUES ($userId, '$title', '$message', '$type')");
    }

    public function delete(int $id, int $userId): void {
        $this->db->query("DELETE FROM notifications WHERE id = " . (int)$id . " AND user_id = " . (int)$userId);
    }
}

// classes/ConsultationRequest.php
class ConsultationRequest {
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function getAll(): array {
        return $this->db->fetchAll("SELECT cr.*, u.full_name AS user_name, u.student_id, u.email
            FROM consultation_requests cr LEFT JOIN users u ON cr.user_id = u.id
            ORDER BY cr.created_at DESC");
    }

    public function getByUser(int $userId): array {
        return $this->db->fetchAll("SELECT * FROM consultation_requests WHERE user_id = " . (int)$userId . " ORDER BY created_at DESC");
    }

    public function create(array $data): array {
        $fields = ['user_id','preferred_date','preferred_time','type','reason'];
        $vals = [];
        foreach ($fields as $f) {
            $v = (isset($data[$f]) && $data[$f] !== '') ? "'" . $this->db->escape($data[$f]) . "'" : 'NULL';
            $vals[] = $v;
        }
        $this->db->query("INSERT INTO consultation_requests (" . implode(',', $fields) . ") VALUES (" . implode(',', $vals) . ")");
        $id = $this->db->lastInsertId();
        return $id ? ['success' => true, 'id' => $id, 'message' => 'Consultation request submitted.'] : ['success' => false, 'message' => 'Failed.'];
    }

    public function updateStatus(int $id, string $status, string $notes = ''): array {
        $id     = (int)$id;
        $status = $this->db->escape($status);
        $notes  = $this->db->escape($notes);
        $this->db->query("UPDATE consultation_requests SET status = '$status', admin_notes = '$notes' WHERE id = $id");
        return ['success' => true, 'message' => 'Status updated.'];
    }

    public function getPendingCount(): int {
        return $this->db->fetchCount("SELECT COUNT(*) FROM consultation_requests WHERE status = 'Pending'");
    }
}

// classes/MedicineRequest.php
class MedicineRequest {
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function getAll(): array {
        return $this->db->fetchAll("SELECT mr.*, u.full_name AS user_name, u.student_id,
            m.name AS medicine_name, m.unit
            FROM medicine_requests mr
            LEFT JOIN users u ON mr.user_id = u.id
            LEFT JOIN medicines m ON mr.medicine_id = m.id
            ORDER BY mr.created_at DESC");
    }

    public function getByUser(int $userId): array {
        return $this->db->fetchAll("SELECT mr.*, m.name AS medicine_name, m.unit, m.quantity AS stock
            FROM medicine_requests mr LEFT JOIN medicines m ON mr.medicine_id = m.id
            WHERE mr.user_id = " . (int)$userId . " ORDER BY mr.created_at DESC");
    }

    public function create(array $data): array {
        $fields = ['user_id','medicine_id','quantity_requested','reason'];
        $vals = [];
        foreach ($fields as $f) {
            $v = (isset($data[$f]) && $data[$f] !== '') ? "'" . $this->db->escape($data[$f]) . "'" : 'NULL';
            $vals[] = $v;
        }
        $this->db->query("INSERT INTO medicine_requests (" . implode(',', $fields) . ") VALUES (" . implode(',', $vals) . ")");
        $id = $this->db->lastInsertId();
        return $id ? ['success' => true, 'id' => $id, 'message' => 'Request submitted.'] : ['success' => false, 'message' => 'Failed.'];
    }

    public function updateStatus(int $id, string $status, string $notes = ''): array {
        $id     = (int)$id;
        $status = $this->db->escape($status);
        $notes  = $this->db->escape($notes);
        $this->db->query("UPDATE medicine_requests SET status = '$status', admin_notes = '$notes' WHERE id = $id");
        return ['success' => true, 'message' => 'Status updated.'];
    }

    public function getPendingCount(): int {
        return $this->db->fetchCount("SELECT COUNT(*) FROM medicine_requests WHERE status = 'Pending'");
    }
}
