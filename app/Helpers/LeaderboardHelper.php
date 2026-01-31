<?php

declare(strict_types=1);

namespace App\Helpers;

use PDO;
use Redis;
use RedisException;
use RuntimeException;
use Logger;

/**
 * Helper class for leaderboard operations with Redis and MySQL synchronization.
 *
 * @package App\Helpers
 */
final class LeaderboardHelper
{
    // Redis key prefixes for different leaderboard types
    private const string REDIS_LEADERBOARD_PREFIX = 'leaderboard';
    private const string REDIS_WEEKLY_PREFIX = 'leaderboard:week';
    private const string REDIS_MONTHLY_PREFIX = 'leaderboard:month';
    private const string REDIS_ALLTIME_PREFIX = 'leaderboard:alltime';

    // Target platforms (from database ENUM)
    public const string TARGET_TG = 'tg';
    public const string TARGET_VK = 'vk';

    /**
     * Update user's score in all leaderboard types (Redis + immediate sync to MySQL).
     *
     * @param int    $userId          User ID
     * @param int    $scoreIncrement  Score increment (can be negative)
     * @param string $target          Platform target ('tg' or 'vk')
     * @param bool   $syncImmediately Whether to sync immediately to MySQL
     *
     * @return array Updated scores for all leaderboard types
     * @throws RedisException
     */
    public static function updateLeaderboard(
        int $userId,
        int $scoreIncrement,
        string $target = self::TARGET_TG,
        bool $syncImmediately = false
    ): array {
        // Validate target
        if (!in_array($target, [self::TARGET_TG, self::TARGET_VK], true)) {
            throw new RuntimeException("Invalid target: {$target}. Must be 'tg' or 'vk'");
        }

        $redis = RedisHelper::getInstance();
        
        // Get current period identifiers
        $currentWeek = date('Y-\WW'); // Format: 2026-W05
        $currentMonth = date('Y-m');   // Format: 2026-02
        
        // Create user key with target suffix
        $userKey = "user_id:{$userId}:{$target}";
        
        // Update Redis sorted sets for all leaderboard types
        $redis->zIncrBy(self::REDIS_WEEKLY_PREFIX . ":{$currentWeek}", $scoreIncrement, $userKey);
        $redis->zIncrBy(self::REDIS_MONTHLY_PREFIX . ":{$currentMonth}", $scoreIncrement, $userKey);
        $redis->zIncrBy(self::REDIS_ALLTIME_PREFIX, $scoreIncrement, $userKey);
        
        // Get updated scores
        $weeklyScore = (int)$redis->zScore(self::REDIS_WEEKLY_PREFIX . ":{$currentWeek}", $userKey) ?: 0;
        $monthlyScore = (int)$redis->zScore(self::REDIS_MONTHLY_PREFIX . ":{$currentMonth}", $userKey) ?: 0;
        $alltimeScore = (int)$redis->zScore(self::REDIS_ALLTIME_PREFIX, $userKey) ?: 0;
        
        // Sync to MySQL if requested
        if ($syncImmediately) {
            self::syncLeaderboardToMySQL($currentWeek, $currentMonth, $userId, $target, $scoreIncrement);
        }
        
        return [
            'weekly' => $weeklyScore,
            'monthly' => $monthlyScore,
            'alltime' => $alltimeScore,
        ];
    }

    /**
     * Synchronize leaderboard data from Redis to MySQL.
     *
     * @param string $currentWeek   Current week identifier (YYYY-W##)
     * @param string $currentMonth Current month identifier (YYYY-MM)
     * @param int    $userId       Specific user ID to sync (optional)
     * @param string $target       Platform target ('tg' or 'vk')
     * @param int    $scoreIncrement Score increment for the user
     *
     * @return void
     */
    public static function syncLeaderboardToMySQL(
        string $currentWeek,
        string $currentMonth,
        ?int $userId = null,
        string $target = self::TARGET_TG,
        int $scoreIncrement = 0
    ): void {
        $pdo = Database::getInstance();
        $redis = RedisHelper::getInstance();

        try {
            $pdo->beginTransaction();

            // Sync weekly leaderboard
            if ($userId !== null) {
                // Sync specific user for weekly
                $userKey = "user_id:{$userId}:{$target}";
                $weeklyScore = (int)$redis->zScore(self::REDIS_WEEKLY_PREFIX . ":{$currentWeek}", $userKey) ?: 0;
                
                $stmt = $pdo->prepare(
                    "INSERT INTO leaderboard_weekly (week, user_id, target, score) 
                     VALUES (?, ?, ?, ?) 
                     ON DUPLICATE KEY UPDATE score = VALUES(score)"
                );
                $stmt->execute([$currentWeek, $userId, $target, $weeklyScore]);
            } else {
                // Sync all users for weekly (batch operation)
                $weeklyScores = $redis->zRange(self::REDIS_WEEKLY_PREFIX . ":{$currentWeek}", 0, -1, ['withscores' => true]);
                foreach ($weeklyScores as $key => $score) {
                    // Extract user_id and target from key format "user_id:{id}:{target}"
                    $parts = explode(':', $key);
                    if (count($parts) === 3 && $parts[0] === 'user_id') {
                        $uid = (int)$parts[1];
                        $tgt = $parts[2];
                        
                        $stmt = $pdo->prepare(
                            "INSERT INTO leaderboard_weekly (week, user_id, target, score) 
                             VALUES (?, ?, ?, ?) 
                             ON DUPLICATE KEY UPDATE score = VALUES(score)"
                        );
                        $stmt->execute([$currentWeek, $uid, $tgt, (int)$score]);
                    }
                }
            }

            // Sync monthly leaderboard
            if ($userId !== null) {
                // Sync specific user for monthly
                $userKey = "user_id:{$userId}:{$target}";
                $monthlyScore = (int)$redis->zScore(self::REDIS_MONTHLY_PREFIX . ":{$currentMonth}", $userKey) ?: 0;
                
                $stmt = $pdo->prepare(
                    "INSERT INTO leaderboard_monthly (month, user_id, target, score) 
                     VALUES (?, ?, ?, ?) 
                     ON DUPLICATE KEY UPDATE score = VALUES(score)"
                );
                $stmt->execute([$currentMonth, $userId, $target, $monthlyScore]);
            } else {
                // Sync all users for monthly (batch operation)
                $monthlyScores = $redis->zRange(self::REDIS_MONTHLY_PREFIX . ":{$currentMonth}", 0, -1, ['withscores' => true]);
                foreach ($monthlyScores as $key => $score) {
                    $parts = explode(':', $key);
                    if (count($parts) === 3 && $parts[0] === 'user_id') {
                        $uid = (int)$parts[1];
                        $tgt = $parts[2];
                        
                        $stmt = $pdo->prepare(
                            "INSERT INTO leaderboard_monthly (month, user_id, target, score) 
                             VALUES (?, ?, ?, ?) 
                             ON DUPLICATE KEY UPDATE score = VALUES(score)"
                        );
                        $stmt->execute([$currentMonth, $uid, $tgt, (int)$score]);
                    }
                }
            }

            // Sync all-time leaderboard
            if ($userId !== null) {
                // Sync specific user for all-time
                $userKey = "user_id:{$userId}:{$target}";
                $alltimeScore = (int)$redis->zScore(self::REDIS_ALLTIME_PREFIX, $userKey) ?: 0;
                
                $stmt = $pdo->prepare(
                    "INSERT INTO leaderboard (user_id, target, score) 
                     VALUES (?, ?, ?) 
                     ON DUPLICATE KEY UPDATE score = VALUES(score)"
                );
                $stmt->execute([$userId, $target, $alltimeScore]);
            } else {
                // Sync all users for all-time (batch operation)
                $alltimeScores = $redis->zRange(self::REDIS_ALLTIME_PREFIX, 0, -1, ['withscores' => true]);
                foreach ($alltimeScores as $key => $score) {
                    $parts = explode(':', $key);
                    if (count($parts) === 3 && $parts[0] === 'user_id') {
                        $uid = (int)$parts[1];
                        $tgt = $parts[2];
                        
                        $stmt = $pdo->prepare(
                            "INSERT INTO leaderboard (user_id, target, score) 
                             VALUES (?, ?, ?) 
                             ON DUPLICATE KEY UPDATE score = VALUES(score)"
                        );
                        $stmt->execute([$uid, $tgt, (int)$score]);
                    }
                }
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            Logger::error("Leaderboard sync to MySQL failed: {$e->getMessage()}", ['exception' => $e]);
            throw new RuntimeException("Failed to sync leaderboard to MySQL: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get leaderboard from Redis (current data, fast).
     *
     * @param string $type          Leaderboard type: 'weekly', 'monthly', or 'alltime'
     * @param int    $limit         Number of entries to return
     * @param string $target        Platform target ('tg' or 'vk')
     * @param string $period        Period identifier (YYYY-W## for weekly, YYYY-MM for monthly)
     *
     * @return array Array of ['user_id' => int, 'target' => string, 'score' => int, 'rank' => int]
     */
    public static function getLeaderboardFromRedis(
        string $type = 'alltime',
        int $limit = 10,
        string $target = self::TARGET_TG,
        ?string $period = null
    ): array {
        $redis = RedisHelper::getInstance();
        $results = [];

        try {
            switch ($type) {
                case 'weekly':
                    $period = $period ?? date('Y-\WW');
                    $key = self::REDIS_WEEKLY_PREFIX . ":{$period}";
                    break;
                case 'monthly':
                    $period = $period ?? date('Y-m');
                    $key = self::REDIS_MONTHLY_PREFIX . ":{$period}";
                    break;
                case 'alltime':
                default:
                    $key = self::REDIS_ALLTIME_PREFIX;
                    $type = 'alltime';
                    break;
            }

            // Get top users from Redis sorted set
            $redisData = $redis->zRevRange($key, 0, $limit - 1, ['withscores' => true]);
            
            $rank = 1;
            foreach ($redisData as $userKey => $score) {
                // Parse user key format "user_id:{id}:{target}"
                $parts = explode(':', $userKey);
                if (count($parts) === 3 && $parts[0] === 'user_id') {
                    $userId = (int)$parts[1];
                    $userTarget = $parts[2];
                    
                    // Filter by target if specified
                    if ($target === $userTarget) {
                        $results[] = [
                            'user_id' => $userId,
                            'target' => $userTarget,
                            'score' => (int)$score,
                            'rank' => $rank++,
                        ];
                    }
                }
            }

            return $results;
        } catch (RedisException $e) {
            Logger::error("Failed to get leaderboard from Redis: {$e->getMessage()}", ['exception' => $e]);
            throw new RuntimeException("Failed to get leaderboard from Redis: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get leaderboard from MySQL (cached/synchronized data, reliable).
     *
     * @param string $type   Leaderboard type: 'weekly', 'monthly', or 'alltime'
     * @param int    $limit Number of entries to return
     * @param string $target Platform target ('tg' or 'vk')
     * @param string $period Period identifier (YYYY-W## for weekly, YYYY-MM for monthly)
     *
     * @return array Array of ['user_id' => int, 'target' => string, 'score' => int, 'rank' => int]
     */
    public static function getLeaderboardFromMySQL(
        string $type = 'alltime',
        int $limit = 10,
        string $target = self::TARGET_TG,
        ?string $period = null
    ): array {
        $pdo = Database::getInstance();
        $results = [];

        try {
            switch ($type) {
                case 'weekly':
                    $period = $period ?? date('Y-\WW');
                    $sql = "SELECT user_id, target, score, 
                                   ROW_NUMBER() OVER (ORDER BY score DESC) as rank
                            FROM leaderboard_weekly 
                            WHERE week = ? AND target = ?
                            ORDER BY score DESC 
                            LIMIT ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$period, $target, $limit]);
                    break;
                    
                case 'monthly':
                    $period = $period ?? date('Y-m');
                    $sql = "SELECT user_id, target, score,
                                   ROW_NUMBER() OVER (ORDER BY score DESC) as rank
                            FROM leaderboard_monthly 
                            WHERE month = ? AND target = ?
                            ORDER BY score DESC 
                            LIMIT ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$period, $target, $limit]);
                    break;
                    
                case 'alltime':
                default:
                    $sql = "SELECT user_id, target, score,
                                   ROW_NUMBER() OVER (ORDER BY score DESC) as rank
                            FROM leaderboard 
                            WHERE target = ?
                            ORDER BY score DESC 
                            LIMIT ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$target, $limit]);
                    $type = 'alltime';
                    break;
            }

            while ($row = $stmt->fetch()) {
                $results[] = [
                    'user_id' => (int)$row['user_id'],
                    'target' => $row['target'],
                    'score' => (int)$row['score'],
                    'rank' => (int)$row['rank'],
                ];
            }

            return $results;
        } catch (\PDOException $e) {
            Logger::error("Failed to get leaderboard from MySQL: {$e->getMessage()}", ['exception' => $e]);
            throw new RuntimeException("Failed to get leaderboard from MySQL: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get user's rank and score in leaderboard.
     *
     * @param int    $userId User ID
     * @param string $type   Leaderboard type: 'weekly', 'monthly', or 'alltime'
     * @param string $target Platform target ('tg' or 'vk')
     * @param string $period Period identifier
     * @param bool   $useRedis Whether to use Redis (true) or MySQL (false)
     *
     * @return array|null ['rank' => int, 'score' => int] or null if user not found
     */
    public static function getUserRank(
        int $userId,
        string $type = 'alltime',
        string $target = self::TARGET_TG,
        ?string $period = null,
        bool $useRedis = true
    ): ?array {
        if ($useRedis) {
            return self::getUserRankFromRedis($userId, $type, $target, $period);
        } else {
            return self::getUserRankFromMySQL($userId, $type, $target, $period);
        }
    }

    /**
     * Get user's rank from Redis.
     */
    private static function getUserRankFromRedis(
        int $userId,
        string $type,
        string $target,
        ?string $period = null
    ): ?array {
        $redis = RedisHelper::getInstance();
        $userKey = "user_id:{$userId}:{$target}";

        try {
            $score = null;
            $rank = null;

            switch ($type) {
                case 'weekly':
                    $period = $period ?? date('Y-\WW');
                    $key = self::REDIS_WEEKLY_PREFIX . ":{$period}";
                    $score = (int)$redis->zScore($key, $userKey);
                    if ($score > 0) {
                        $rank = (int)$redis->zRevRank($key, $userKey) + 1; // 1-based rank
                    }
                    break;
                    
                case 'monthly':
                    $period = $period ?? date('Y-m');
                    $key = self::REDIS_MONTHLY_PREFIX . ":{$period}";
                    $score = (int)$redis->zScore($key, $userKey);
                    if ($score > 0) {
                        $rank = (int)$redis->zRevRank($key, $userKey) + 1;
                    }
                    break;
                    
                case 'alltime':
                default:
                    $key = self::REDIS_ALLTIME_PREFIX;
                    $score = (int)$redis->zScore($key, $userKey);
                    if ($score > 0) {
                        $rank = (int)$redis->zRevRank($key, $userKey) + 1;
                    }
                    break;
            }

            return ($score !== null && $score > 0) ? ['rank' => $rank, 'score' => $score] : null;
        } catch (RedisException $e) {
            Logger::error("Failed to get user rank from Redis: {$e->getMessage()}", ['exception' => $e]);
            return null;
        }
    }

    /**
     * Get user's rank from MySQL.
     */
    private static function getUserRankFromMySQL(
        int $userId,
        string $type,
        string $target,
        ?string $period = null
    ): ?array {
        $pdo = Database::getInstance();

        try {
            switch ($type) {
                case 'weekly':
                    $period = $period ?? date('Y-\WW');
                    $sql = "SELECT score, 
                                   (SELECT COUNT(*) + 1 
                                    FROM leaderboard_weekly l2 
                                    WHERE l2.week = ? AND l2.target = ? AND l2.score > l1.score) as rank
                            FROM leaderboard_weekly l1
                            WHERE l1.week = ? AND l1.user_id = ? AND l1.target = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$period, $target, $period, $userId, $target]);
                    break;
                    
                case 'monthly':
                    $period = $period ?? date('Y-m');
                    $sql = "SELECT score,
                                   (SELECT COUNT(*) + 1 
                                    FROM leaderboard_monthly l2 
                                    WHERE l2.month = ? AND l2.target = ? AND l2.score > l1.score) as rank
                            FROM leaderboard_monthly l1
                            WHERE l1.month = ? AND l1.user_id = ? AND l1.target = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$period, $target, $period, $userId, $target]);
                    break;
                    
                case 'alltime':
                default:
                    $sql = "SELECT score,
                                   (SELECT COUNT(*) + 1 
                                    FROM leaderboard l2 
                                    WHERE l2.target = ? AND l2.score > l1.score) as rank
                            FROM leaderboard l1
                            WHERE l1.user_id = ? AND l1.target = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$target, $userId, $target]);
                    break;
            }

            $row = $stmt->fetch();
            return $row ? ['rank' => (int)$row['rank'], 'score' => (int)$row['score']] : null;
        } catch (\PDOException $e) {
            Logger::error("Failed to get user rank from MySQL: {$e->getMessage()}", ['exception' => $e]);
            return null;
        }
    }

    /**
     * Clean old leaderboard data from Redis and MySQL.
     *
     * @param string $type   Leaderboard type: 'weekly', 'monthly', or 'alltime'
     * @param int    $keepPeriods Number of periods to keep (weeks for weekly, months for monthly)
     *
     * @return int Number of cleaned entries
     */
    public static function cleanOldLeaderboardData(string $type = 'weekly', int $keepPeriods = 4): int
    {
        $redis = RedisHelper::getInstance();
        $pdo = Database::getInstance();
        $cleanedCount = 0;

        try {
            switch ($type) {
                case 'weekly':
                    // Clean old weekly data
                    for ($i = $keepPeriods; $i < 52; $i++) {
                        $week = date('Y-\WW', strtotime("-{$i} weeks"));
                        $key = self::REDIS_WEEKLY_PREFIX . ":{$week}";
                        
                        // Remove from Redis
                        $redis->del($key);
                        
                        // Remove from MySQL
                        $stmt = $pdo->prepare("DELETE FROM leaderboard_weekly WHERE week = ?");
                        $stmt->execute([$week]);
                        $cleanedCount += $stmt->rowCount();
                    }
                    break;
                    
                case 'monthly':
                    // Clean old monthly data
                    for ($i = $keepPeriods; $i < 24; $i++) {
                        $month = date('Y-m', strtotime("-{$i} months"));
                        $key = self::REDIS_MONTHLY_PREFIX . ":{$month}";
                        
                        // Remove from Redis
                        $redis->del($key);
                        
                        // Remove from MySQL
                        $stmt = $pdo->prepare("DELETE FROM leaderboard_monthly WHERE month = ?");
                        $stmt->execute([$month]);
                        $cleanedCount += $stmt->rowCount();
                    }
                    break;
                    
                case 'alltime':
                    // For all-time, we typically don't clean data
                    break;
            }

            Logger::info("Cleaned {$cleanedCount} old {$type} leaderboard entries");
            return $cleanedCount;
        } catch (\Exception $e) {
            Logger::error("Failed to clean old leaderboard data: {$e->getMessage()}", ['exception' => $e]);
            throw new RuntimeException("Failed to clean old leaderboard data: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get current period identifiers.
     *
     * @return array ['week' => string, 'month' => string]
     */
    public static function getCurrentPeriods(): array
    {
        return [
            'week' => date('Y-\WW'),
            'month' => date('Y-m'),
        ];
    }
}