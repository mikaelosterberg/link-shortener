<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_report');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Report $report): bool
    {
        // Check basic view permission first
        if (! $user->can('view_report')) {
            return false;
        }

        // Check if report is active
        if (! $report->is_active) {
            return false;
        }

        // Public reports are visible to everyone with view permission
        if ($report->visibility === 'public') {
            return true;
        }

        // Own reports
        if ($report->user_id === $user->id) {
            return true;
        }

        // Team reports - check if user has view_any permission or admin role
        if ($report->visibility === 'team') {
            return $user->can('view_any_report') || $user->hasAnyRole(['super_admin', 'admin']);
        }

        // Private reports - only owner or users with view_any permission
        return $user->can('view_any_report');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_report');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Report $report): bool
    {
        // Users can update their own reports or if they have permission to update any
        return ($report->user_id === $user->id && $user->can('update_report')) ||
               $user->can('update_any_report');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Report $report): bool
    {
        // Users can delete their own reports or if they have permission to delete any
        return ($report->user_id === $user->id && $user->can('delete_report')) ||
               $user->can('delete_any_report');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_report');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Report $report): bool
    {
        return $user->can('force_delete_report');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_report');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Report $report): bool
    {
        return $user->can('restore_report');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_report');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Report $report): bool
    {
        return $user->can('replicate_report');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_report');
    }
}
