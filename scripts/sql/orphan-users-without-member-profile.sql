-- =============================================================================
-- Orphan users: Joomla account without Balancirk member profile
-- =============================================================================
--
-- Finds users in #__users that have no matching row in
-- #__balancirk_members_additional. These accounts do not appear in the
-- #__balancirk_members view and cannot be linked as parents of students.
--
-- Replace #__ with your Joomla table prefix before running.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. Main list (contact / manual follow-up)
-- -----------------------------------------------------------------------------
SELECT
    u.id,
    u.name,
    u.username,
    u.email,
    u.block,
    u.activation,
    u.registerDate,
    u.lastvisitDate,
    CASE
        WHEN u.block = 1 AND u.activation <> '' THEN 'wacht op activatie'
        WHEN u.block = 1 THEN 'geblokkeerd'
        ELSE 'actief'
    END AS status
FROM `#__users` AS u
LEFT JOIN `#__balancirk_members_additional` AS m ON m.id = u.id
WHERE m.id IS NULL
  AND u.id > 42
ORDER BY u.registerDate DESC;


-- -----------------------------------------------------------------------------
-- 2. Quick count
-- -----------------------------------------------------------------------------
SELECT COUNT(*) AS orphan_users
FROM `#__users` AS u
LEFT JOIN `#__balancirk_members_additional` AS m ON m.id = u.id
WHERE m.id IS NULL
  AND u.id > 42;


-- -----------------------------------------------------------------------------
-- 3. Recent registrations only (last 90 days)
-- -----------------------------------------------------------------------------
SELECT
    u.id,
    u.name,
    u.username,
    u.email,
    u.registerDate,
    u.block,
    u.activation
FROM `#__users` AS u
LEFT JOIN `#__balancirk_members_additional` AS m ON m.id = u.id
WHERE m.id IS NULL
  AND u.id > 42
  AND u.registerDate >= DATE_SUB(NOW(), INTERVAL 90 DAY)
ORDER BY u.registerDate DESC;


-- -----------------------------------------------------------------------------
-- 4. Export-friendly (e-mail outreach)
-- -----------------------------------------------------------------------------
SELECT
    u.id,
    u.email,
    u.name,
    u.username,
    DATE_FORMAT(u.registerDate, '%Y-%m-%d') AS registered_on
FROM `#__users` AS u
LEFT JOIN `#__balancirk_members_additional` AS m ON m.id = u.id
WHERE m.id IS NULL
  AND u.id > 42
  AND u.email <> ''
ORDER BY u.registerDate DESC;


-- -----------------------------------------------------------------------------
-- 5. Orphan users already linked as parent of a student (data inconsistency)
-- -----------------------------------------------------------------------------
SELECT
    u.id,
    u.email,
    u.name,
    u.registerDate,
    COUNT(p.id) AS parent_links
FROM `#__users` AS u
LEFT JOIN `#__balancirk_members_additional` AS m ON m.id = u.id
INNER JOIN `#__balancirk_parents` AS p ON p.parent = u.id
WHERE m.id IS NULL
  AND u.id > 42
GROUP BY u.id, u.email, u.name, u.registerDate
ORDER BY parent_links DESC, u.registerDate DESC;
