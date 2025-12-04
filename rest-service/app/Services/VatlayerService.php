<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class VatlayerService
{
    protected string $apiKey;
    protected string $endpoint;

    public function __construct()
    {
        $this->apiKey = config('services.vatlayer.key');
        $this->endpoint = config('services.vatlayer.endpoint');
    }

    public function validate(string $vatNumber)
    {
        $url = $this->endpoint . '?access_key=' . $this->apiKey . '&vat_number=' . urlencode($vatNumber);

        $response = Http::get($url);
        
        return $response->json();
    }
}