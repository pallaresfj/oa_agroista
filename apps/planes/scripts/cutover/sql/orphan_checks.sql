-- Returns one row per referential check.
-- orphan_count must be 0 for every row.

SELECT 'plan_user.plan_id -> plans.id' AS check_name, COUNT(*) AS orphan_count
FROM plan_user pu
LEFT JOIN plans p ON p.id = pu.plan_id
WHERE p.id IS NULL

UNION ALL

SELECT 'plan_user.user_id -> users.id' AS check_name, COUNT(*) AS orphan_count
FROM plan_user pu
LEFT JOIN users u ON u.id = pu.user_id
WHERE u.id IS NULL

UNION ALL

SELECT 'subject_user.subject_id -> subjects.id' AS check_name, COUNT(*) AS orphan_count
FROM subject_user su
LEFT JOIN subjects s ON s.id = su.subject_id
WHERE s.id IS NULL

UNION ALL

SELECT 'subject_user.user_id -> users.id' AS check_name, COUNT(*) AS orphan_count
FROM subject_user su
LEFT JOIN users u ON u.id = su.user_id
WHERE u.id IS NULL

UNION ALL

SELECT 'subjects.plan_id -> plans.id' AS check_name, COUNT(*) AS orphan_count
FROM subjects s
LEFT JOIN plans p ON p.id = s.plan_id
WHERE p.id IS NULL

UNION ALL

SELECT 'plans.school_profile_id -> school_profiles.id' AS check_name, COUNT(*) AS orphan_count
FROM plans p
LEFT JOIN school_profiles sp ON sp.id = p.school_profile_id
WHERE sp.id IS NULL

UNION ALL

SELECT 'topics.subject_id -> subjects.id' AS check_name, COUNT(*) AS orphan_count
FROM topics t
LEFT JOIN subjects s ON s.id = t.subject_id
WHERE s.id IS NULL

UNION ALL

SELECT 'rubrics.subject_id -> subjects.id' AS check_name, COUNT(*) AS orphan_count
FROM rubrics r
LEFT JOIN subjects s ON s.id = r.subject_id
WHERE s.id IS NULL

UNION ALL

SELECT 'centers.user_id -> users.id' AS check_name, COUNT(*) AS orphan_count
FROM centers c
LEFT JOIN users u ON u.id = c.user_id
WHERE u.id IS NULL

UNION ALL

SELECT 'teachers.center_id -> centers.id' AS check_name, COUNT(*) AS orphan_count
FROM teachers t
LEFT JOIN centers c ON c.id = t.center_id
WHERE c.id IS NULL

UNION ALL

SELECT 'students.center_id -> centers.id' AS check_name, COUNT(*) AS orphan_count
FROM students s
LEFT JOIN centers c ON c.id = s.center_id
WHERE c.id IS NULL

UNION ALL

SELECT 'activities.center_id -> centers.id' AS check_name, COUNT(*) AS orphan_count
FROM activities a
LEFT JOIN centers c ON c.id = a.center_id
WHERE c.id IS NULL

UNION ALL

SELECT 'budgets.center_id -> centers.id' AS check_name, COUNT(*) AS orphan_count
FROM budgets b
LEFT JOIN centers c ON c.id = b.center_id
WHERE c.id IS NULL;
