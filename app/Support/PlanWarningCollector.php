<?php

namespace App\Support;

class PlanWarningCollector
{
    protected ?string $warning = null;

    public function record(?string $warning): void
    {
        if ($warning === null) {
            return;
        }

        if ($this->warning === null || $this->severity($warning) > $this->severity($this->warning)) {
            $this->warning = $warning;
        }
    }

    public function current(): ?string
    {
        return $this->warning;
    }

    public function clear(): void
    {
        $this->warning = null;
    }

    protected function severity(string $warning): int
    {
        return match ($warning) {
            'over_limit_grace' => 2,
            'approaching_limit' => 1,
            default => 0,
        };
    }
}
