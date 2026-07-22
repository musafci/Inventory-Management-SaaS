<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ActivityLogService
{
    /**
     * @var array<string, class-string>
     */
    protected array $subjectTypeMap = [
        'sales_order' => SalesOrder::class,
        'purchase_order' => PurchaseOrder::class,
        'payment' => Payment::class,
        'stock_movement' => StockMovement::class,
        'role' => Role::class,
    ];

    /**
     * @return LengthAwarePaginator<int, Activity>
     */
    public function paginateForOrganization(Organization $organization): LengthAwarePaginator
    {
        return $this->applyFilters(
            Activity::query()->where('organization_id', $organization->id),
        )
            ->with(['causer', 'organization'])
            ->latest('created_at')
            ->paginate(request()->integer('per_page', 25));
    }

    /**
     * @return LengthAwarePaginator<int, Activity>
     */
    public function paginatePlatformWide(): LengthAwarePaginator
    {
        return $this->applyFilters(Activity::query())
            ->with(['causer', 'organization'])
            ->latest('created_at')
            ->paginate(request()->integer('per_page', 25));
    }

    /**
     * @return array{
     *     total: int,
     *     last_24_hours: int,
     *     last_7_days: int,
     *     by_event: array<int, array{event: string, count: int}>,
     *     by_subject_type: array<int, array{subject_type: string, count: int}>,
     *     top_organizations: array<int, array{organization_id: int, organization_name: string|null, count: int}>
     * }
     */
    public function summarize(?int $organizationId = null): array
    {
        $baseQuery = Activity::query();

        if ($organizationId !== null) {
            $baseQuery->where('organization_id', $organizationId);
        }

        $now = now();

        return [
            'total' => (clone $baseQuery)->count(),
            'last_24_hours' => (clone $baseQuery)->where('created_at', '>=', $now->copy()->subDay())->count(),
            'last_7_days' => (clone $baseQuery)->where('created_at', '>=', $now->copy()->subDays(7))->count(),
            'by_event' => (clone $baseQuery)
                ->selectRaw('event, COUNT(*) as count')
                ->whereNotNull('event')
                ->groupBy('event')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->map(fn ($row): array => [
                    'event' => (string) $row->event,
                    'count' => (int) $row->count,
                ])
                ->values()
                ->all(),
            'by_subject_type' => (clone $baseQuery)
                ->selectRaw('subject_type, COUNT(*) as count')
                ->whereNotNull('subject_type')
                ->groupBy('subject_type')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->map(fn ($row): array => [
                    'subject_type' => class_basename((string) $row->subject_type),
                    'subject_type_class' => (string) $row->subject_type,
                    'count' => (int) $row->count,
                ])
                ->values()
                ->all(),
            'top_organizations' => $organizationId === null
                ? (clone $baseQuery)
                    ->selectRaw('organization_id, COUNT(*) as count')
                    ->whereNotNull('organization_id')
                    ->groupBy('organization_id')
                    ->orderByDesc('count')
                    ->limit(10)
                    ->get()
                    ->map(function ($row): array {
                        $organization = Organization::query()->find($row->organization_id);

                        return [
                            'organization_id' => (int) $row->organization_id,
                            'organization_name' => $organization?->name,
                            'count' => (int) $row->count,
                        ];
                    })
                    ->values()
                    ->all()
                : [],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function subjectTypeOptions(): array
    {
        return collect($this->subjectTypeMap)
            ->mapWithKeys(fn (string $class, string $key): array => [$key => class_basename($class)])
            ->all();
    }

    public function resolveSubjectLabel(Activity $activity): ?string
    {
        $subject = $activity->subject;

        if ($subject === null) {
            return null;
        }

        return match (true) {
            $subject instanceof SalesOrder => $subject->order_number,
            $subject instanceof PurchaseOrder => $subject->po_number,
            $subject instanceof Payment => 'Payment #'.$subject->id,
            $subject instanceof StockMovement => 'Movement #'.$subject->id,
            $subject instanceof Role => $subject->name,
            isset($subject->name) => (string) $subject->name,
            isset($subject->sku) => (string) $subject->sku,
            default => class_basename($activity->subject_type).' #'.$activity->subject_id,
        };
    }

    /**
     * @param  Builder<Activity>  $query
     * @return Builder<Activity>
     */
    protected function applyFilters(Builder $query): Builder
    {
        $event = request()->query('event');
        if (is_string($event) && $event !== '') {
            $query->where('event', $event);
        }

        $subjectType = request()->query('subject_type');
        if (is_string($subjectType) && $subjectType !== '') {
            $resolved = $this->subjectTypeMap[$subjectType] ?? (
                class_exists($subjectType) ? $subjectType : null
            );

            if ($resolved !== null) {
                $query->where('subject_type', $resolved);
            }
        }

        $organizationId = request()->query('organization_id');
        if ($organizationId !== null && $organizationId !== '') {
            $query->where('organization_id', (int) $organizationId);
        }

        $userId = request()->query('user_id');
        if ($userId !== null && $userId !== '') {
            $query->where('causer_id', (int) $userId);
        }

        $search = request()->query('search');
        if (is_string($search) && $search !== '') {
            $term = '%'.$search.'%';
            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('description', 'like', $term)
                    ->orWhere('log_name', 'like', $term);
            });
        }

        $from = request()->query('from');
        if (is_string($from) && $from !== '') {
            $query->where('created_at', '>=', Carbon::parse($from)->startOfDay());
        }

        $to = request()->query('to');
        if (is_string($to) && $to !== '') {
            $query->where('created_at', '<=', Carbon::parse($to)->endOfDay());
        }

        return $query;
    }
}
