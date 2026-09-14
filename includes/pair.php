<?php
/**
 * pair.php - An ND pair shares one project.
 *
 * Two ND students register together, pay once, and submit one project between
 * them. They keep separate logins — each is accountable for their own account
 * — but everything about the project must look identical to either of them.
 * Whichever partner uploads, the other sees it on their dashboard, can open
 * the approval slip, and does not have to upload it again.
 *
 * Nothing here changes for an HND student: profile_student_ids() simply
 * returns their own id, and every query behaves exactly as before.
 */

/**
 * Every student id whose work is shared with this one: the student, plus
 * their ND partner when they have one. Always contains at least the student,
 * so callers can use it unconditionally.
 */
function profile_student_ids(PDO $pdo, int $student_id): array {
    static $cache = [];
    if (isset($cache[$student_id])) {
        return $cache[$student_id];
    }

    $stmt = $pdo->prepare(
        "SELECT p.student1_id, p.student2_id
         FROM students s
         JOIN nd_pairs p ON p.id = s.pair_id
         WHERE s.id = ?"
    );
    $stmt->execute([$student_id]);
    $row = $stmt->fetch();

    $ids = [$student_id];
    if ($row) {
        foreach ([$row['student1_id'], $row['student2_id']] as $id) {
            $id = (int) $id;
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
    }

    return $cache[$student_id] = $ids;
}

/** "?,?" placeholders matching the ids above, for an IN (...) clause. */
function profile_id_placeholders(array $ids): string {
    return implode(',', array_fill(0, count($ids), '?'));
}

/** True when this student registered as half of an ND pair. */
function is_paired(PDO $pdo, int $student_id): bool {
    return count(profile_student_ids($pdo, $student_id)) > 1;
}

/**
 * Every student on this profile, as id/name/matric_no rows. One row for a solo
 * student; two for an ND pair, in registration order (student 1 first).
 */
function profile_members(PDO $pdo, int $student_id): array {
    $ids = profile_student_ids($pdo, $student_id);
    $ph  = profile_id_placeholders($ids);
    $stmt = $pdo->prepare(
        "SELECT id, name, matric_no FROM students WHERE id IN ($ph) ORDER BY id"
    );
    $stmt->execute($ids);
    return $stmt->fetchAll();
}

/** The other half of the pair, or null for a student who registered alone. */
function pair_partner(PDO $pdo, int $student_id): ?array {
    $others = array_values(array_diff(profile_student_ids($pdo, $student_id), [$student_id]));
    if (!$others) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT id, name, matric_no FROM students WHERE id = ?");
    $stmt->execute([$others[0]]);
    return $stmt->fetch() ?: null;
}

/**
 * The pair's live submission, if there is one — pending or approved, whoever
 * uploaded it. A rejected project deliberately does NOT count, so the pair can
 * submit a corrected version after a department admin sends one back.
 */
function pair_live_project(PDO $pdo, int $student_id): ?array {
    $ids = profile_student_ids($pdo, $student_id);
    $ph  = profile_id_placeholders($ids);

    $stmt = $pdo->prepare(
        "SELECT p.*, s.id AS uploader_id, s.name AS uploaded_by, s.matric_no AS uploaded_by_matric
         FROM projects p
         JOIN students s ON s.id = p.student_id
         WHERE p.student_id IN ($ph) AND p.status IN ('pending','approved')
         ORDER BY p.id DESC
         LIMIT 1"
    );
    $stmt->execute($ids);
    return $stmt->fetch() ?: null;
}
