<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectCost;
use App\Models\ProjectRevenue;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    /**
     * Generate next project code.
     */
    public static function generateProjectCode(int $businessUnitId): string
    {
        $year = now()->format('Y');
        $prefix = "PRJ-{$year}-";

        $last = Project::where('business_unit_id', $businessUnitId)
            ->where('project_code', 'like', "{$prefix}%")
            ->orderByDesc('project_code')
            ->value('project_code');

        $seq = 1;
        if ($last) {
            $seq = (int) substr($last, strlen($prefix)) + 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new project.
     */
    public static function createProject(array $data): Project
    {
        $buId = $data['business_unit_id'];
        $data['project_code'] = self::generateProjectCode($buId);

        return Project::create($data);
    }

    /**
     * Update an existing project.
     */
    public static function updateProject(Project $project, array $data): Project
    {
        $project->update($data);
        return $project->fresh();
    }

    /**
     * Add a cost item to a project.
     */
    public static function addCost(int $projectId, array $data): ProjectCost
    {
        $data['project_id'] = $projectId;
        $cost = ProjectCost::create($data);

        // Recalculate totals
        $project = Project::find($projectId);
        $project->recalculate();

        return $cost;
    }

    /**
     * Update a cost item.
     */
    public static function updateCost(ProjectCost $cost, array $data): ProjectCost
    {
        $cost->update($data);
        $cost->project->recalculate();
        return $cost->fresh();
    }

    /**
     * Delete a cost item.
     */
    public static function deleteCost(ProjectCost $cost): void
    {
        $project = $cost->project;
        $cost->delete();
        $project->recalculate();
    }

    /**
     * Add a revenue item to a project.
     */
    public static function addRevenue(int $projectId, array $data): ProjectRevenue
    {
        $data['project_id'] = $projectId;
        $revenue = ProjectRevenue::create($data);

        $project = Project::find($projectId);
        $project->recalculate();

        return $revenue;
    }

    /**
     * Update a revenue item.
     */
    public static function updateRevenue(ProjectRevenue $revenue, array $data): ProjectRevenue
    {
        $revenue->update($data);
        $revenue->project->recalculate();
        return $revenue->fresh();
    }

    /**
     * Delete a revenue item.
     */
    public static function deleteRevenue(ProjectRevenue $revenue): void
    {
        $project = $revenue->project;
        $revenue->delete();
        $project->recalculate();
    }

    /**
     * Get project summary / profitability analysis.
     */
    public static function getSummary(Project $project): array
    {
        $costByCategory = $project->costs()
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        return [
            'budget' => (float) $project->budget,
            'actual_cost' => (float) $project->actual_cost,
            'revenue' => (float) $project->revenue,
            'profit' => $project->profit,
            'profit_margin' => $project->profit_margin,
            'budget_usage' => $project->budget_usage,
            'cost_by_category' => $costByCategory,
            'cost_count' => $project->costs()->count(),
            'revenue_count' => $project->revenues()->count(),
        ];
    }

    /**
     * Change project status.
     */
    public static function changeStatus(Project $project, string $status): Project
    {
        if (!array_key_exists($status, Project::STATUSES)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        $project->update(['status' => $status]);
        return $project->fresh();
    }

    /**
     * Delete a project (only if in planning or cancelled).
     */
    public static function deleteProject(Project $project): void
    {
        if (!in_array($project->status, ['planning', 'cancelled'])) {
            throw new \Exception('Hanya proyek dengan status Perencanaan atau Dibatalkan yang bisa dihapus.');
        }

        $project->delete();
    }
}
