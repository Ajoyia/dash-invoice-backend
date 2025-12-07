<?php

namespace App\Services\Company;

use App\Services\VatlayerService;

class VatValidationService implements VatValidationServiceInterface
{
    public function __construct(
        private VatlayerService $vatlayerService
    ) {}

    public function validate(string $vatNumber): array
    {
        $result = $this->vatlayerService->validate($vatNumber);

        return [
            'data' => $result,
            'valid' => $result['valid'] ?? false,
        ];
    }
}
