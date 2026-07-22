<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Spatie\Permission\Models\Role as SpatieRole;

#[Fillable(['name', 'guard_name', 'organization_id', 'description', 'is_protected', 'is_system'])]
class Role extends SpatieRole
{
    use LogsModelActivity;

    protected function casts(): array
    {
        return [
            'is_protected' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function isProtected(): bool
    {
        return (bool) $this->is_protected;
    }
}
