<?php

namespace PinnacleHunt\Services;

use PinnacleHunt\Config\Database;
use PDO;
use Exception;

class MatrixEngine {
    /**
     * Place user into the company-wide BFS 3-Matrix for a given package tier.
     * Dimensions: 3-matrix, max depth = 7.
     * Slot searching query:
     * SELECT id, depth, children_count
     * FROM matrix_nodes
     * WHERE package_id = ? AND children_count < 3 AND depth < 7
     * ORDER BY depth ASC, id ASC
     * LIMIT 1 FOR UPDATE;
     */
    public static function placeUserInMatrix(int $userId, int $packageId): array {
        $pdo = Database::getConnection();

        // Check if user is already placed in this package matrix
        $checkStmt = $pdo->prepare("SELECT id, depth, position_index, parent_id FROM matrix_nodes WHERE package_id = ? AND user_id = ?");
        $checkStmt->execute([$packageId, $userId]);
        $existing = $checkStmt->fetch();
        if ($existing) {
            return $existing;
        }

        // Find earliest available parent node via company-wide BFS
        $searchSql = "
            SELECT id, depth, children_count
            FROM matrix_nodes
            WHERE package_id = ? AND children_count < 3 AND depth < 7
            ORDER BY depth ASC, id ASC
            LIMIT 1
            FOR UPDATE
        ";
        $stmt = $pdo->prepare($searchSql);
        $stmt->execute([$packageId]);
        $parent = $stmt->fetch();

        if (!$parent) {
            // Check if there are any nodes in this package matrix
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM matrix_nodes WHERE package_id = ?");
            $countStmt->execute([$packageId]);
            $totalInTree = (int)$countStmt->fetchColumn();

            if ($totalInTree === 0) {
                // Root node for this package matrix
                $insertStmt = $pdo->prepare("
                    INSERT INTO matrix_nodes (package_id, user_id, parent_id, depth, position_index, children_count)
                    VALUES (?, ?, NULL, 1, 1, 0)
                ");
                $insertStmt->execute([$packageId, $userId]);
                $nodeId = (int)$pdo->lastInsertId();

                return [
                    'id' => $nodeId,
                    'package_id' => $packageId,
                    'user_id' => $userId,
                    'parent_id' => null,
                    'depth' => 1,
                    'position_index' => 1,
                    'children_count' => 0
                ];
            } else {
                throw new Exception("Matrix capacity reached for package tier {$packageId} (Depth 7 fully saturated).");
            }
        }

        // Increment parent's children_count
        $newChildrenCount = (int)$parent['children_count'] + 1;
        $updateParent = $pdo->prepare("UPDATE matrix_nodes SET children_count = ? WHERE id = ?");
        $updateParent->execute([$newChildrenCount, $parent['id']]);

        // Insert new child node
        $newDepth = (int)$parent['depth'] + 1;
        $insertStmt = $pdo->prepare("
            INSERT INTO matrix_nodes (package_id, user_id, parent_id, depth, position_index, children_count)
            VALUES (?, ?, ?, ?, ?, 0)
        ");
        $insertStmt->execute([$packageId, $userId, $parent['id'], $newDepth, $newChildrenCount]);
        $nodeId = (int)$pdo->lastInsertId();

        return [
            'id' => $nodeId,
            'package_id' => $packageId,
            'user_id' => $userId,
            'parent_id' => (int)$parent['id'],
            'depth' => $newDepth,
            'position_index' => $newChildrenCount,
            'children_count' => 0
        ];
    }

    /**
     * Get up to 7 upline ancestors for a matrix node in the same package tier
     */
    public static function getUplineAncestors(int $packageId, int $userId, int $maxLevels = 7): array {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT id, parent_id FROM matrix_nodes WHERE package_id = ? AND user_id = ?");
        $stmt->execute([$packageId, $userId]);
        $currentNode = $stmt->fetch();

        if (!$currentNode || !$currentNode['parent_id']) {
            return [];
        }

        $ancestors = [];
        $currentParentId = $currentNode['parent_id'];
        $level = 1;

        while ($currentParentId && $level <= $maxLevels) {
            $parentStmt = $pdo->prepare("
                SELECT m.id, m.user_id, m.parent_id, m.depth, m.position_index, u.username, u.full_name
                FROM matrix_nodes m
                JOIN users u ON m.user_id = u.id
                WHERE m.id = ?
            ");
            $parentStmt->execute([$currentParentId]);
            $parent = $parentStmt->fetch();

            if (!$parent) {
                break;
            }

            $parent['matrix_level'] = $level;
            $ancestors[] = $parent;
            $currentParentId = $parent['parent_id'];
            $level++;
        }

        return $ancestors;
    }

    /**
     * Get downline tree for a given user and package tier
     */
    public static function getDownlineTree(int $packageId, int $userId, int $maxDepth = 7): ?array {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT m.*, u.username, u.full_name, u.email
            FROM matrix_nodes m
            JOIN users u ON m.user_id = u.id
            WHERE m.package_id = ? AND m.user_id = ?
        ");
        $stmt->execute([$packageId, $userId]);
        $root = $stmt->fetch();

        if (!$root) {
            return null;
        }

        return self::buildSubtree($root, $maxDepth, 0);
    }

    private static function buildSubtree(array $node, int $maxDepth, int $currentDepth): array {
        if ($currentDepth >= $maxDepth) {
            $node['children'] = [];
            return $node;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT m.*, u.username, u.full_name, u.email
            FROM matrix_nodes m
            JOIN users u ON m.user_id = u.id
            WHERE m.parent_id = ?
            ORDER BY m.position_index ASC
        ");
        $stmt->execute([$node['id']]);
        $children = $stmt->fetchAll();

        $node['children'] = [];
        foreach ($children as $child) {
            $node['children'][] = self::buildSubtree($child, $maxDepth, $currentDepth + 1);
        }

        return $node;
    }

    /**
     * Get entire company tree for a package tier (Admin view)
     */
    public static function getCompanyTree(int $packageId, int $maxDepth = 7): ?array {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT m.*, u.username, u.full_name, u.email
            FROM matrix_nodes m
            JOIN users u ON m.user_id = u.id
            WHERE m.package_id = ? AND m.parent_id IS NULL
            LIMIT 1
        ");
        $stmt->execute([$packageId]);
        $root = $stmt->fetch();

        if (!$root) {
            return null;
        }

        return self::buildSubtree($root, $maxDepth, 0);
    }
}
