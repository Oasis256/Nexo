<?php

namespace Modules\RenCommissions\Services;

use App\Services\Options;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\RenCommissions\Models\PosCommissionSession;

/**
 * POS Commission Cleanup Service
 * 
 * Handles cleanup of orphaned/expired session records.
 * Should be run via scheduled job or on demand.
 */
class PosCommissionCleanupService
{
    /**
     * Options service instance
     */
    protected Options $options;

    /**
     * Default expiration time in hours
     */
    protected const DEFAULT_EXPIRATION_HOURS = 24;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->options = app()->make(Options::class);
    }

    /**
     * Run full cleanup process
     *
     * @return array Cleanup statistics
     */
    public function runCleanup(): array
    {
        $stats = [
            'started_at' => Carbon::now()->toDateTimeString(),
            'expired_deleted' => 0,
            'orphaned_deleted' => 0,
            'total_deleted' => 0,
            'errors' => [],
        ];

        try {
            // Delete expired session records
            $expiredCount = $this->deleteExpiredSessions();
            $stats['expired_deleted'] = $expiredCount;

            // Delete orphaned records (sessions that no longer exist)
            $orphanedCount = $this->deleteOrphanedRecords();
            $stats['orphaned_deleted'] = $orphanedCount;

            $stats['total_deleted'] = $expiredCount + $orphanedCount;

        } catch (\Exception $e) {
            $stats['errors'][] = $e->getMessage();
            Log::error('RenCommissions cleanup error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }

        $stats['ended_at'] = Carbon::now()->toDateTimeString();

        // Log cleanup results
        Log::info('RenCommissions session cleanup completed', $stats);

        return $stats;
    }

    /**
     * Delete sessions older than the expiration threshold
     *
     * @param int|null $hoursOld Hours after which sessions are considered expired
     * @return int Number of deleted records
     */
    public function deleteExpiredSessions(?int $hoursOld = null): int
    {
        $hoursOld = $hoursOld ?? $this->getExpirationHours();
        $threshold = Carbon::now()->subHours($hoursOld);

        return PosCommissionSession::where('created_at', '<', $threshold)->delete();
    }

    /**
     * Delete orphaned records
     * Records that belong to sessions that are no longer active
     *
     * @return int Number of deleted records
     */
    public function deleteOrphanedRecords(): int
    {
        // Get unique session IDs from our records
        $sessionIds = PosCommissionSession::distinct()
            ->pluck('session_id')
            ->toArray();

        if (empty($sessionIds)) {
            return 0;
        }

        $deleted = 0;

        foreach ($sessionIds as $sessionId) {
            // Check if session still exists (this is approximate since we can't 
            // directly check PHP sessions from other users)
            // We rely on the time-based expiration as the primary cleanup method
            // This method is mainly for handling edge cases
            
            $count = PosCommissionSession::where('session_id', $sessionId)->count();
            
            // If session has been idle for more than a short threshold,
            // and no recent updates, consider it orphaned
            $lastUpdate = PosCommissionSession::where('session_id', $sessionId)
                ->max('updated_at');

            if ($lastUpdate) {
                $lastUpdateTime = Carbon::parse($lastUpdate);
                $shortThreshold = Carbon::now()->subHours(2);

                if ($lastUpdateTime->lt($shortThreshold)) {
                    $sessionDeleted = PosCommissionSession::clearSession($sessionId);
                    $deleted += $sessionDeleted;
                }
            }
        }

        return $deleted;
    }

    /**
     * Get configured expiration hours
     *
     * @return int
     */
    public function getExpirationHours(): int
    {
        return (int) $this->options->get(
            'rencommissions_session_expiration_hours',
            self::DEFAULT_EXPIRATION_HOURS
        );
    }

    /**
     * Get statistics about current session records
     *
     * @return array
     */
    public function getSessionStats(): array
    {
        $total = PosCommissionSession::count();
        $uniqueSessions = PosCommissionSession::distinct('session_id')->count('session_id');
        
        $expirationThreshold = Carbon::now()->subHours($this->getExpirationHours());
        $expiredCount = PosCommissionSession::where('created_at', '<', $expirationThreshold)->count();

        $oldest = PosCommissionSession::orderBy('created_at', 'asc')->first();
        $newest = PosCommissionSession::orderBy('created_at', 'desc')->first();

        return [
            'total_records' => $total,
            'unique_sessions' => $uniqueSessions,
            'expired_records' => $expiredCount,
            'expiration_hours' => $this->getExpirationHours(),
            'oldest_record' => $oldest ? $oldest->created_at->toDateTimeString() : null,
            'newest_record' => $newest ? $newest->created_at->toDateTimeString() : null,
        ];
    }

    /**
     * Clear all session records (use with caution)
     *
     * @return int Number of deleted records
     */
    public function clearAllSessions(): int
    {
        $count = PosCommissionSession::count();
        PosCommissionSession::truncate();
        
        Log::warning('RenCommissions: All session records cleared', [
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Delete records for a specific session
     *
     * @param string $sessionId
     * @return int Number of deleted records
     */
    public function clearSpecificSession(string $sessionId): int
    {
        return PosCommissionSession::clearSession($sessionId);
    }
}
