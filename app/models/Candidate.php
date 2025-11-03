<?php
// app/models/Candidate.php
require_once __DIR__ . '/Database.php';

class Candidate extends Database {
    private function ensureCvColumns() {
        static $columnsChecked = false;
        if ($columnsChecked) {
            return;
        }

        $columnsChecked = true;
        $dbName = null;
        $dbResult = $this->conn->query("SELECT DATABASE() AS db_name");
        if ($dbResult && ($row = $dbResult->fetch_assoc())) {
            $dbName = $row['db_name'] ?? null;
        }
        if (!$dbName) {
            return;
        }

        $dbEsc = $this->conn->real_escape_string($dbName);
        $sql = "SELECT column_name FROM information_schema.columns WHERE table_schema = '{$dbEsc}' AND table_name = 'candidates' AND column_name IN ('cv_path','updated_at')";
        $result = $this->conn->query($sql);
        $existing = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['column_name'])) {
                    $existing[$row['column_name']] = true;
                }
            }
            $result->free();
        }

        if (empty($existing['cv_path'])) {
            $this->conn->query("ALTER TABLE candidates ADD COLUMN cv_path VARCHAR(255) DEFAULT NULL");
        }
        if (empty($existing['updated_at'])) {
            $this->conn->query("ALTER TABLE candidates ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        }
    }

    public function getByUserId($user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM candidates WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getProfileByUserId($user_id) {
    $sql = "SELECT u.id AS user_id, u.email, u.name AS full_name, u.phone, u.avatar_path, u.created_at,
               c.id AS candidate_id, c.headline, c.summary, c.location, c.skills, c.experience,
               c.profile_picture, c.cv_path, c.updated_at
                FROM users u
                LEFT JOIN candidates c ON c.user_id = u.id
                WHERE u.id = ?
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return null;
        }
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    public function getProfileByCandidateId($candidate_id) {
    $sql = "SELECT u.id AS user_id, u.email, u.name AS full_name, u.phone, u.avatar_path, u.created_at,
               c.id AS candidate_id, c.headline, c.summary, c.location, c.skills, c.experience,
               c.profile_picture, c.cv_path, c.updated_at
                FROM candidates c
                INNER JOIN users u ON u.id = c.user_id
                WHERE c.id = ?
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return null;
        }
        $stmt->bind_param('i', $candidate_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    public function createOrUpdate($user_id, $headline = null, $summary = null, $location = null, $skills = null, $experience = null) {
        $existing = $this->getByUserId($user_id);
        $skills_json = $skills ? json_encode($skills, JSON_UNESCAPED_UNICODE) : null;
        $experience_json = $experience ? json_encode($experience, JSON_UNESCAPED_UNICODE) : null;
        if ($existing) {
            $stmt = $this->conn->prepare("UPDATE candidates SET headline=?, summary=?, location=?, skills=?, experience=? WHERE user_id=?");
            $stmt->bind_param("sssssi", $headline, $summary, $location, $skills_json, $experience_json, $user_id);
            return $stmt->execute();
        } else {
            $stmt = $this->conn->prepare("INSERT INTO candidates (user_id, headline, summary, location, skills, experience) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("isssss", $user_id, $headline, $summary, $location, $skills_json, $experience_json);
            if ($stmt->execute()) return $this->conn->insert_id;
            return false;
        }
    }

    // List candidates with simple filters: skills (comma separated), location, min_experience_items
    public function listCandidates($filters = []) {
        $where = [];
        $params = [];
        $types = '';
        if (!empty($filters['location'])) {
            $where[] = 'c.location LIKE ?';
            $params[] = '%' . $filters['location'] . '%';
            $types .= 's';
        }
        if (!empty($filters['skills'])) {
            $skills = array_map('trim', explode(',', $filters['skills']));
            $skillParts = [];
            foreach ($skills as $s) {
                // Use JSON_SEARCH to find the skill inside skills JSON
                $skillParts[] = "JSON_SEARCH(c.skills, 'one', '" . $this->conn->real_escape_string($s) . "') IS NOT NULL";
            }
            if (!empty($skillParts)) $where[] = '(' . implode(' OR ', $skillParts) . ')';
        }
        if (!empty($filters['min_experience_items'])) {
            $min = (int)$filters['min_experience_items'];
            $where[] = "JSON_LENGTH(c.experience) >= ?";
            $params[] = $min;
            $types .= 'i';
        }
        $sql = "SELECT c.*, u.email, u.name as user_name FROM candidates c JOIN users u ON u.id = c.user_id";
        if (!empty($where)) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY c.id DESC';
        if (empty($params)) {
            return $this->conn->query($sql);
        }
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return false;
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Update candidate profile fields. $data may contain keys:
     * full_name, headline, summary, location, phone, skills (json), experience (json)
     */
    public function updateProfile($user_id, $data) {
        // Update users.name and users.phone if provided
        if (!empty($data['full_name']) || !empty($data['phone'])) {
            $parts = [];
            $types = '';
            $params = [];
            if (!empty($data['full_name'])) {
                $parts[] = 'name = ?';
                $types .= 's';
                $params[] = $data['full_name'];
            }
            if (!empty($data['phone'])) {
                $parts[] = 'phone = ?';
                $types .= 's';
                $params[] = $data['phone'];
            }
            if (!empty($parts)) {
                $sql = "UPDATE users SET " . implode(', ', $parts) . " WHERE id = ?";
                $types .= 'i';
                $params[] = $user_id;
                $stmt = $this->conn->prepare($sql);
                if ($stmt === false) return false;
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
            }
        }

        // Update candidates fields
        $fields = [];
        $types = '';
        $params = [];
        if (isset($data['headline'])) {
            $fields[] = 'headline = ?'; $types .= 's'; $params[] = $data['headline'];
        }
        if (isset($data['summary'])) {
            $fields[] = 'summary = ?'; $types .= 's'; $params[] = $data['summary'];
        }
        if (isset($data['location'])) {
            $fields[] = 'location = ?'; $types .= 's'; $params[] = $data['location'];
        }
        if (isset($data['skills'])) {
            $fields[] = 'skills = ?'; $types .= 's'; $params[] = $data['skills'];
        }
        if (isset($data['experience'])) {
            $fields[] = 'experience = ?'; $types .= 's'; $params[] = $data['experience'];
        }

        if (!empty($fields)) {
            $sql = "UPDATE candidates SET " . implode(', ', $fields) . " WHERE user_id = ?";
            $types .= 'i';
            $params[] = $user_id;
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) return false;
            $stmt->bind_param($types, ...$params);
            return $stmt->execute();
        }
        return true;
    }

    public function updateCv($user_id, $cv_path) {
        $this->ensureCvColumns();
        $sql = "UPDATE candidates SET cv_path = ?, updated_at = NOW() WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            // Fallback for databases that chưa có cột updated_at
            $sql = "UPDATE candidates SET cv_path = ? WHERE user_id = ?";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                return false;
            }
        }

        $stmt->bind_param("si", $cv_path, $user_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected > 0) {
            return true;
        }

        // Nếu không có thay đổi, nhưng hồ sơ đã tồn tại thì vẫn tính là thành công
        $existing = $this->getByUserId($user_id);
        if ($existing) {
            return true;
        }

        // Hồ sơ chưa tồn tại -> tạo mới một bản ghi tối thiểu
        $insertSql = "INSERT INTO candidates (user_id, cv_path) VALUES (?, ?)";
        $insertStmt = $this->conn->prepare($insertSql);
        if ($insertStmt === false) {
            return false;
        }
        $insertStmt->bind_param("is", $user_id, $cv_path);
        $result = $insertStmt->execute();
        $insertStmt->close();
        return $result;
    }

    public function updateAvatar($user_id, $avatar_path) {
        // Update users.avatar_path
        $stmt = $this->conn->prepare("UPDATE users SET avatar_path = ? WHERE id = ?");
        $stmt->bind_param("si", $avatar_path, $user_id);
        return $stmt->execute();
    }
}
?>
