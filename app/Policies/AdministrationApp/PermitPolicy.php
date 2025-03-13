<?php

namespace App\Policies\AdministrationApp;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PermitPolicy
{
    use HandlesAuthorization;
    protected static string $prefixName = 'permit';

    /**
     * Check if the user has the required permission.
     *
     * @param User $user
     * @param string $action
     * @return bool
     */
    protected function checkPermission(User $user, string $action): bool
    {
        return $user->can("{$action}_" . self::$prefixName);
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_any');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user): bool
    {
        return $this->checkPermission($user, 'view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user): bool
    {
        return $this->checkPermission($user, 'update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): bool
    {
        return $this->checkPermission($user, 'delete');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $this->checkPermission($user, 'delete_any');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user): bool
    {
        return $this->checkPermission($user, 'replicate');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function export(User $user): bool
    {
        return $this->checkPermission($user, 'export');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function import(User $user): bool
    {
        return $this->checkPermission($user, 'import');
    }
}
