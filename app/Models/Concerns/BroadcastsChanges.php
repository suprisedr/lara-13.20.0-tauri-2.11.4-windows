<?php

namespace App\Models\Concerns;

use App\Events\RecordChanged;

trait BroadcastsChanges
{
    public static function bootBroadcastsChanges(): void
    {
        foreach (['created', 'updated', 'deleted'] as $event) {
            static::$event(function ($model) use ($event) {
                $companyId = $model->company_id
                    ?? $model->getAttribute('company_id');

                if (! $companyId) {
                    return;
                }

                $shortName = class_basename($model);

                RecordChanged::dispatch($companyId, $shortName, $model->getKey(), $event);
            });
        }
    }
}
