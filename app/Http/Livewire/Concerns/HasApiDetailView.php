<?php

namespace App\Http\Livewire\Concerns;

use App\Services\Web\ApiClient;

trait HasApiDetailView
{
    public ?array $detail = null;

    public function isDetailView(): bool
    {
        return $this->detail !== null;
    }

    protected function loadDetailFromApi(string $endpoint, string $fallbackUrl): void
    {
        $api = new ApiClient();
        $response = $api->get($endpoint);

        if (isset($response['error'])) {
            session()->flash('error', $response['error']);
            $this->redirect($fallbackUrl, navigate: true);

            return;
        }

        $this->detail = $response['data'] ?? null;
    }

    protected function mountDetailRoute(string $showRouteName, string $endpointPrefix, string $fallbackUrl, string $param = 'id'): void
    {
        if (! request()->routeIs($showRouteName)) {
            return;
        }

        $id = request()->route($param);
        if ($id) {
            $this->loadDetailFromApi("{$endpointPrefix}/{$id}", $fallbackUrl);
        }
    }
}
