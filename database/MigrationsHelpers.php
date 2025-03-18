<?php

use Illuminate\Support\Facades\DB;

class MigrationsHelpers
{
    /**
     * Insert the default permissions
     *
     * @param  string  $type  the type of resource to create the access permissions
     */
    public static function addDefaultPermissions(string $type)
    {
        $tableName = config('permission.table_names')['permissions'];
        $result = [];
        $permissions = [
            'view_'.$type,
            'view_self_'.$type,
            'publish_'.$type,
            'publish_self_'.$type,
            'create_'.$type,
            'edit_'.$type,
            'edit_self_'.$type,
            'delete_'.$type,
            'delete_self_'.$type,
        ];

        foreach ($permissions as $permission) {
            $exists = DB::table($tableName)
                ->where('name', '=', $permission)
                ->where('guard_name', '=', 'web')
                ->first();

            if (is_null($exists)) {
                DB::table($tableName)->insert([
                    'name' => $permission,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $result[$permission] = DB::table($tableName)
                    ->where('name', '=', $permission)
                    ->where('guard_name', '=', 'web')
                    ->first()->id;
            } else {
                $result[$permission] = $exists->id;
            }
        }

        self::addDefaultRoles($type, $result);
    }

    /**
     * Insert the default roles with the default permissions
     *
     * @param  array  $permissionsMap  the permissions mapping
     */
    private static function addDefaultRoles(string $type, array $permissionsMap)
    {
        $tableNames = config('permission.table_names');
        $rolesTableName = $tableNames['roles'];
        $roleHasPermissionTableName = $tableNames['role_has_permissions'];
        $roles = [
            'Admin' => '*',
            'Editor' => '*',
            'Contributor' => [
                'view_self_'.$type,
                'create_'.$type,
                'edit_self_'.$type,
            ],
            'Author' => [
                'view_'.$type,
                'view_self_'.$type,
                'publish_self_'.$type,
                'create_'.$type,
                'edit_self_'.$type,
                'delete_self_'.$type,
            ],
        ];
        $result = [];

        foreach ($roles as $role => $permissions) {
            $exists = DB::table($rolesTableName)
                ->where('name', '=', $role)
                ->where('guard_name', '=', 'web')
                ->first();
            if (is_null($exists)) {
                DB::table($rolesTableName)->insert([
                    'name' => $role,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $result[$role] = DB::table($rolesTableName)
                    ->where('name', '=', $role)
                    ->where('guard_name', '=', 'web')
                    ->first()->id;
            } else {
                $result[$role] = $exists->id;
            }

            if (is_string($permissions) && $permissions === '*') {
                $permissionsArray = array_keys($permissionsMap);
            } else {
                $permissionsArray = $permissions;
            }

            foreach ($permissionsArray as $permission) {
                $exists = DB::table($roleHasPermissionTableName)
                    ->where('permission_id', '=', $permissionsMap[$permission])
                    ->where('role_id', '=', $result[$role])
                    ->first();
                if (is_null($exists)) {
                    DB::table($roleHasPermissionTableName)->insert([
                        'permission_id' => $permissionsMap[$permission],
                        'role_id' => $result[$role],
                    ]);
                }
            }
        }
    }

    /**
     * Remove the default permissions of a resource
     *
     * @param  string  $type  the resource type
     */
    public static function removeDefaultPermissions(string $type)
    {
        $permissions = [
            'view_'.$type,
            'view_self_'.$type,
            'publish_'.$type,
            'publish_self_'.$type,
            'create_'.$type,
            'edit_'.$type,
            'edit_self_'.$type,
            'delete_'.$type,
            'delete_self_'.$type,
        ];

        $tableNames = config('permission.table_names');
        $permissionsTableName = $tableNames['permissions'];
        $roleHasPermissionTableName = $tableNames['role_has_permissions'];

        foreach ($permissions as $permission) {
            $permissionRow = DB::table($permissionsTableName)
                ->where('name', '=', $permission)
                ->first();
            if (! is_null($permissionRow)) {
                DB::table($roleHasPermissionTableName)
                    ->where('permission_id', '=', $permissionRow->id)
                    ->delete();

                DB::table($permissionsTableName)
                    ->where('name', '=', $permission)
                    ->delete();
            }
        }
    }
}
