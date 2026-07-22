<?php

namespace App\GraphQL\Queries;

use App\Models\Asset;
use Illuminate\Support\Collection;

class Assets
{
    /**
     * @param  array{company_id:int|string, status?:string|null, ppe_class_id?:int|string|null, search?:string|null}  $args
     * @return Collection<int, Asset>
     */
    public function __invoke(mixed $root, array $args): Collection
    {
        $status = $args['status'] ?? 'active';

        return Asset::query()
            ->where('company_id', (int) $args['company_id'])
            ->when($status === 'active', fn ($q) => $q->active())
            ->when($status === 'disposed', fn ($q) => $q->disposed())
            ->when(! empty($args['ppe_class_id']), fn ($q) => $q->where('ppe_class_id', (int) $args['ppe_class_id']))
            ->when(! empty($args['search']), function ($q) use ($args) {
                $s = $args['search'];
                $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('asset_tag', 'like', "%{$s}%"));
            })
            ->orderByDesc('acquisition_date')
            ->get();
    }
}
