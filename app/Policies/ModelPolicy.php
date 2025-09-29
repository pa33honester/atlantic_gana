<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class ModelPolicy
{
    use HandlesAuthorization;

    /**
     * A fallback policy class used as a stub for IDE discovery and old policy mappings.
     * It intentionally allows nothing by default; Gate will fallback to other policies if present.
     */
    public function before($user, $ability)
    {
        // Return null to continue normal checks; override if you want to grant/deny globally.
        return null;
    }
}
