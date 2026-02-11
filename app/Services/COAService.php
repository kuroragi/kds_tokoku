<?php

namespace App\Services;

use App\Models\COA;
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

    public function create(array $data): COA
    {
        try {
            return DB::transaction(function () use ($data) {
                $level = $this->calculateLevel($data['parent_code']);
                $this->reorderSiblings($data['parent_code'] ?? null, $data['order']);

                $coa = COA::create([
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
            return DB::transaction(function () use ($coa, $data) {
                $level = $this->calculateLevel($data['parent_code'] ?? null);
                $this->reorderSiblings($data['parent_code'] ?? null, $data['order'], $coa->id);

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
                $coa->delete();
                $this->normalizeOrder($parentCode);
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

    public function getParentOptions(?int $excludeId = null)
    {
        return COA::where('is_leaf_account', false)
            ->when($excludeId, fn($query) => $query->where('id', '!=', $excludeId))
            ->orderBy('code')
            ->get();
    }

    protected function calculateLevel(?int $parentCode): int
    {
        if (!$parentCode) {
            return 0;
        }

        $parent = COA::find($parentCode);
        return $parent ? ($parent->level + 1) : 0;
    }

    public static function calculateOrder(?int $parentCode): int{
        if(!$parentCode){
            return COA::whereNull('parent_code')->count() + 1;
        }

        return COA::where('parent_code', $parentCode)->count();
    }

    protected function reorderSiblings(?int $parentCode, int $order, ?int $excludeId = null): void
    {
        COA::where('parent_code', $parentCode)
            ->where('order', '>=', $order)
            ->when($excludeId, fn($query) => $query->where('id', '!=', $excludeId))
            ->increment('order');
    }

    protected function normalizeOrder(?int $parentCode): void
    {
        $siblings = COA::where('parent_code', $parentCode)->orderBy('order')->get();

        foreach ($siblings as $index => $sibling) {
            $sibling->update(['order' => $index + 1]);
        }
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
