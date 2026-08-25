<?php

use App\Modules\FeatureToggle\Application\Services\FeatureService;

if (!function_exists('feature')) {
    /**
     * Atalho global para verificar se uma Feature está ativa.
     */
    function feature(string $featureName): bool
    {
        return app(FeatureService::class)->isActive($featureName);
    }
}