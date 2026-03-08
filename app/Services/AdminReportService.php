<?php

namespace App\Services;

use App\Models\Competition;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AdminReportService
{
    /**
     * Summary statistics for the admin dashboard.
     *
     * @return array{total: int, active: int, ended: int, setup: int, total_contestants: int}
     */
    public function summary(): array
    {
        $counts = Competition::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalContestants = DB::table('contestants')->count();

        return [
            'total'             => array_sum($counts),
            'setup'             => (int) ($counts['setup']  ?? 0),
            'active'            => (int) ($counts['active'] ?? 0),
            'ended'             => (int) ($counts['ended']  ?? 0),
            'total_contestants' => (int) $totalContestants,
        ];
    }

    /**
     * Paginated, filtered list of competitions for the admin index.
     *
     * @param  array{search?: string, status?: string, from?: string, to?: string}  $filters
     */
    public function filteredCompetitions(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Competition::query()
            ->withCount('contestants')
            ->withCount('rounds')
            ->orderByDesc('created_at');

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (! empty($filters['from'])) {
            $query->fromDate($filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->toDate($filters['to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Full competition detail for the show page including related data.
     */
    public function competitionDetail(int $id): Competition
    {
        return Competition::with([
            'contestants' => fn($q) => $q->orderByDesc('score'),
            'rounds.firstBuzzContestant',
            'rounds.buzzAttempts.contestant',
        ])->findOrFail($id);
    }
}
