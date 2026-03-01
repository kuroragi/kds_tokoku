<?php

namespace App\Services;

use App\Models\COA;
use App\Models\CoaTemplate;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Kuroragi\GeneralHelper\ActivityLog\ActivityLogger;
use Throwable;

class COAService
{
    protected ActivityLogger $logger;

    public function __construct()
    {
        $this->logger = new ActivityLogger(storage_path('logs/activity'));
    }

    // ── CRUD ────────────────────────────────────────────────────

    public function create(array $data): COA
    {
        try {
            return DB::transaction(function () use ($data) {
                $level = $this->calculateLevel($data['parent_code']);
                $businessUnitId = $data['business_unit_id'] ?? null;

                $this->reorderSiblings($data['parent_code'] ?? null, $data['order'], null, $businessUnitId);

                $coa = COA::create([
                    'business_unit_id' => $businessUnitId,
                    'template_id' => $data['template_id'] ?? null,
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'parent_code' => $data['parent_code'] ?? null,
                    'level' => $level,
                    'order' => $data['order'],
                    'description' => $data['description'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                    'is_leaf_account' => $data['is_leaf_account'] ?? true,
                    'is_locked' => $data['is_locked'] ?? false,
                ]);

                $this->log('info', 'coa_created', 'COA berhasil dibuat', $coa);

                return $coa;
            });

        } catch (QueryException $e) {
            $this->log('error', 'coa_create_failed', 'Gagal membuat COA: database error', null, $e);

            throw ValidationException::withMessages([
                'code' => 'Gagal menyimpan data. Silakan coba beberapa saat lagi.',
            ]);

        } catch (Throwable $e) {
            $this->log('critical', 'coa_create_failed', 'Gagal membuat COA: unexpected error', null, $e);

            throw ValidationException::withMessages([
                'code' => 'Terjadi kesalahan sistem. Silakan hubungi administrator.',
            ]);
        }
    }

    public function update(COA $coa, array $data): COA
    {
        try {
            if ($coa->is_locked) {
                throw ValidationException::withMessages([
                    'code' => 'Akun terkunci (dari template) dan tidak dapat diubah.',
                ]);
            }

            return DB::transaction(function () use ($coa, $data) {
                $level = $this->calculateLevel($data['parent_code'] ?? null);
                $businessUnitId = $coa->business_unit_id;

                $this->reorderSiblings($data['parent_code'] ?? null, $data['order'], $coa->id, $businessUnitId);

                $coa->update([
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'parent_code' => $data['parent_code'] ?? null,
                    'level' => $level,
                    'order' => $data['order'],
                    'description' => $data['description'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                    'is_leaf_account' => $data['is_leaf_account'] ?? true,
                ]);

                $coa = $coa->fresh();

                $this->log('info', 'coa_updated', 'COA berhasil diperbarui', $coa);

                return $coa;
            });

        } catch (ValidationException $e) {
            throw $e;

        } catch (QueryException $e) {
            $this->log('error', 'coa_update_failed', 'Gagal memperbarui COA: database error', $coa, $e);

            throw ValidationException::withMessages([
                'code' => 'Gagal memperbarui data. Silakan coba beberapa saat lagi.',
            ]);

        } catch (Throwable $e) {
            $this->log('critical', 'coa_update_failed', 'Gagal memperbarui COA: unexpected error', $coa, $e);

            throw ValidationException::withMessages([
                'code' => 'Terjadi kesalahan sistem. Silakan hubungi administrator.',
            ]);
        }
    }

    public function delete(COA $coa): void
    {
        try {
            if ($coa->is_locked) {
                throw ValidationException::withMessages([
                    'code' => 'Akun terkunci (dari template) dan tidak dapat dihapus.',
                ]);
            }

            if ($coa->children()->exists()) {
                throw ValidationException::withMessages([
                    'code' => 'Tidak dapat menghapus akun yang memiliki sub-akun.',
                ]);
            }

            if ($coa->journals()->exists()) {
                throw ValidationException::withMessages([
                    'code' => 'Tidak dapat menghapus akun yang sudah memiliki transaksi jurnal.',
                ]);
            }

            $coaData = $coa->toArray();

            DB::transaction(function () use ($coa) {
                $parentCode = $coa->parent_code;
                $businessUnitId = $coa->business_unit_id;
                $coa->delete();
                $this->normalizeOrder($parentCode, $businessUnitId);
            });

            $this->logger->log([
                'level' => 'info',
                'category' => 'coa_management',
                'message' => 'COA berhasil dihapus',
                'meta' => [
                    'action' => 'coa_deleted',
                    'coa_id' => $coaData['id'],
                    'coa_code' => $coaData['code'],
                    'coa_name' => $coaData['name'],
                    'business_unit_id' => $coaData['business_unit_id'] ?? null,
                    'user_id' => Auth::id(),
                    'user_name' => Auth::user()?->name,
                ]
            ]);

        } catch (ValidationException $e) {
            throw $e;

        } catch (QueryException $e) {
            $this->log('error', 'coa_delete_failed', 'Gagal menghapus COA: database error', $coa, $e);

            throw ValidationException::withMessages([
                'code' => 'Gagal menghapus data. Silakan coba beberapa saat lagi.',
            ]);

        } catch (Throwable $e) {
            $this->log('critical', 'coa_delete_failed', 'Gagal menghapus COA: unexpected error', $coa, $e);

            throw ValidationException::withMessages([
                'code' => 'Terjadi kesalahan sistem. Silakan hubungi administrator.',
            ]);
        }
    }

    public function toggleActive(COA $coa): COA
    {
        try {
            $coa->update(['is_active' => !$coa->is_active]);
            $coa = $coa->fresh();

            $action = $coa->is_active ? 'coa_activated' : 'coa_deactivated';
            $message = $coa->is_active ? 'COA berhasil diaktifkan' : 'COA berhasil dinonaktifkan';

            $this->log('info', $action, $message, $coa);

            return $coa;

        } catch (Throwable $e) {
            $this->log('error', 'coa_toggle_failed', 'Gagal mengubah status COA', $coa, $e);

            throw ValidationException::withMessages([
                'code' => 'Gagal mengubah status. Silakan coba beberapa saat lagi.',
            ]);
        }
    }

    public function getParentOptions(?int $excludeId = null, ?int $businessUnitId = null)
    {
        return COA::where('is_leaf_account', false)
            ->when($businessUnitId, fn($q) => $q->where('business_unit_id', $businessUnitId))
            ->when(!$businessUnitId, fn($q) => $q->whereNull('business_unit_id'))
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->orderBy('code')
            ->get();
    }

    // ── Template Cloning ────────────────────────────────────────

    /**
     * Clone all active templates into a business unit's COA.
     * Idempotent — skips if the unit already has COA rows.
     */
    public function cloneTemplatesForBusinessUnit(int $businessUnitId): int
    {
        $existingCount = COA::where('business_unit_id', $businessUnitId)->count();

        if ($existingCount > 0) {
            return 0; // already initialised, avoid duplication
        }

        return CoaTemplate::cloneToBusinessUnit($businessUnitId);
    }

    // ── Reorder Logic ───────────────────────────────────────────
    //
    // Strategy (based on two-pass approach):
    //  1. Siblings with order <= targetOrder → renumber sequentially from 1
    //  2. Siblings with order >= targetOrder → renumber from targetOrder+1
    //
    // This pushes existing items aside and reserves `targetOrder` for the
    // new / moved item.

    protected function reorderSiblings(?int $parentCode, int $targetOrder, ?int $excludeId = null, ?int $businessUnitId = null): void
    {
        $baseQuery = fn() => COA::when(
                $parentCode !== null,
                fn($q) => $q->where('parent_code', $parentCode),
                fn($q) => $q->whereNull('parent_code')
            )
            ->when($businessUnitId, fn($q) => $q->where('business_unit_id', $businessUnitId))
            ->when(!$businessUnitId, fn($q) => $q->whereNull('business_unit_id'))
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId));

        // Pass 1: siblings at or before targetOrder → renumber 1..n
        $before = $baseQuery()->where('order', '<=', $targetOrder)->orderBy('order')->get();
        $no = 0;
        foreach ($before as $sibling) {
            $no++;
            $sibling->updateQuietly(['order' => $no]);
        }

        // Pass 2: siblings at or after targetOrder → renumber targetOrder+1..n
        $after = $baseQuery()->where('order', '>=', $targetOrder)->orderBy('order')->get();
        $no = $targetOrder;
        foreach ($after as $sibling) {
            $no++;
            $sibling->updateQuietly(['order' => $no]);
        }
    }

    protected function normalizeOrder(?int $parentCode, ?int $businessUnitId = null): void
    {
        $siblings = COA::when(
                $parentCode !== null,
                fn($q) => $q->where('parent_code', $parentCode),
                fn($q) => $q->whereNull('parent_code')
            )
            ->when($businessUnitId, fn($q) => $q->where('business_unit_id', $businessUnitId))
            ->when(!$businessUnitId, fn($q) => $q->whereNull('business_unit_id'))
            ->orderBy('order')
            ->get();

        foreach ($siblings as $index => $sibling) {
            $sibling->updateQuietly(['order' => $index + 1]);
        }
    }

    // ── Helpers ─────────────────────────────────────────────────

    protected function calculateLevel(?int $parentCode): int
    {
        if (!$parentCode) {
            return 0;
        }

        $parent = COA::find($parentCode);
        return $parent ? ($parent->level + 1) : 0;
    }

    public static function calculateOrder(?int $parentCode, ?int $businessUnitId = null): int
    {
        $query = COA::when($businessUnitId, fn($q) => $q->where('business_unit_id', $businessUnitId))
            ->when(!$businessUnitId, fn($q) => $q->whereNull('business_unit_id'));

        if (!$parentCode) {
            return $query->whereNull('parent_code')->count() + 1;
        }

        return $query->where('parent_code', $parentCode)->count() + 1;
    }

    protected function log(string $level, string $action, string $message, ?COA $coa = null, ?Throwable $e = null): void
    {
        $meta = [
            'action' => $action,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
        ];

        if ($coa) {
            $meta['coa_id'] = $coa->id;
            $meta['coa_code'] = $coa->code;
            $meta['coa_name'] = $coa->name;
            $meta['coa_type'] = $coa->type;
            $meta['business_unit_id'] = $coa->business_unit_id;
        }

        if ($e) {
            $meta['exception'] = $e->getMessage();
            $meta['trace'] = $e->getTraceAsString();
        }

        $this->logger->log([
            'level' => $level,
            'category' => 'coa_management',
            'message' => $message,
            'meta' => $meta,
        ]);
    }
}
