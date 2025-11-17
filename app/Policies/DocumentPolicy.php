<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Document;

class DocumentPolicy
{
    /** ✅ Allow viewing if HR, Supervisor, or document owner */
    public function view(User $user, Document $doc): bool
    {
        if ($user->employee && $user->employee->id === $doc->employee_id) {
            return true;
        }

        if ($user->hasRole('hr') || $user->hasRole('supervisor')) {
            return true;
        }

        return false;
    }

    /** ✅ Allow updating if HR or document owner */
    public function update(User $user, Document $doc): bool
    {
        if ($user->employee && $user->employee->id === $doc->employee_id) {
            return true;
        }

        if ($user->hasRole('hr')) {
            return true;
        }

        return false;
    }

    /** ✅ Delete same as update */
    public function delete(User $user, Document $doc): bool
    {
        return $this->update($user, $doc);
    }

    /** ✅ Allow creating for employees and HR only */
    public function create(User $user): bool
    {
        return $user->hasRole('hr') || $user->hasRole('employee');
    }
}
