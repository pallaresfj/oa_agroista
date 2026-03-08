<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    /**
     * Determine whether the user can view any attendances.
     */
    public function viewAny(User $user): bool
    {
        return $user->role?->canViewAllAttendances() || $user->role === UserRole::DOCENTE;
    }

    /**
     * Determine whether the user can view the attendance.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        // Soporte and directivo can view all
        if ($user->role?->canViewAllAttendances()) {
            return true;
        }

        // Docente can only view their own
        return $user->id === $attendance->user_id;
    }

    /**
     * Determine whether the user can create attendances.
     */
    public function create(User $user): bool
    {
        // Only active docentes and directivos can register attendance
        return $user->is_active && in_array($user->role, [
            UserRole::DOCENTE,
            UserRole::DIRECTIVO,
        ]);
    }

    /**
     * Determine whether the user can update the attendance.
     */
    public function update(User $user, Attendance $attendance): bool
    {
        // Soporte and directivos can update attendances
        return in_array($user->role, [UserRole::SOPORTE, UserRole::DIRECTIVO]);
    }

    /**
     * Determine whether the user can delete the attendance.
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        // Only soporte can delete attendances
        return $user->role === UserRole::SOPORTE;
    }

    /**
     * Determine whether the user can restore the attendance.
     */
    public function restore(User $user, Attendance $attendance): bool
    {
        return $user->role === UserRole::SOPORTE;
    }

    /**
     * Determine whether the user can permanently delete the attendance.
     */
    public function forceDelete(User $user, Attendance $attendance): bool
    {
        return $user->role === UserRole::SOPORTE;
    }

    /**
     * Determine whether the user can export attendances.
     */
    public function export(User $user): bool
    {
        return $user->role?->canViewAllAttendances();
    }
}
