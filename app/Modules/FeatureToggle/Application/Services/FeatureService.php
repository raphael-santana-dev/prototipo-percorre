<?php

namespace App\Modules\FeatureToggle\Application\Services;

use App\Modules\FeatureToggle\Domain\Models\Feature;
use Illuminate\Support\Facades\Cache;

class FeatureService
{
    /**
     * Verifica se uma feature está ativa utilizando Cache
     */
    public function isActive(string $name): bool
    {
        return Cache::rememberForever("feature_status_{$name}", function () use ($name) {
            $feature = Feature::where('name', $name)->first();
            return $feature ? $feature->is_active : false;
        });
    }

    /**
     * Ativa ou desativa uma feature e limpa o cache
     */
    public function toggle(string $name, bool $status): void
    {
        Feature::updateOrCreate(
            ['name' => $name],
            ['is_active' => $status]
        );

        Cache::forget("feature_status_{$name}");
    }

    /**
     * Cria uma nova feature (se não existir)
     */
    public function create(string $name, string $description): Feature
    {
        $feature = Feature::firstOrCreate(
            ['name' => strtolower($name)],
            ['description' => $description, 'is_active' => false]
        );

        Cache::forget("feature_status_{$feature->name}");

        return $feature;
    }
}