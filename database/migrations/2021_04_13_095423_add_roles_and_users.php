<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddRolesAndUsers extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        $permissions = $this->addDefaultPermissions();
        $roles = $this->addDefaultRoles($permissions);

        $this->addDefaultUsers($roles);
    }

    /**
     * Insert the default permissions
     *
     * @return array
     */
    public function addDefaultPermissions(): array {
        $tableName = config('permission.table_names')['permissions'];
        $permissions = [
            'view_user',
            'create_user',
            'edit_user',
            'delete_user',
            'view_permission',
            'create_permission',
            'edit_permission',
            'delete_permission',
            'view_role',
            'view_self_role',
            'create_role',
            'edit_role',
            'delete_role',
            'view_self_user',
            //            'edit_self_user', // Unuseful since anyone should be able to edit themselves
            'edit_self_role',
        ];
        $result = [];

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
                    'updated_at' => now()
                ]);

                $result[$permission] = DB::table($tableName)
                    ->where('name', '=', $permission)
                    ->where('guard_name', '=', 'web')
                    ->first()->id;
            } else
                $result[$permission] = $exists->id;
        }

        return $result;
    }

    /**
     * Insert the default roles with the default permissions
     *
     * @param array $permissionsMap the permissions mapping
     *
     * @return array
     */
    public function addDefaultRoles(array $permissionsMap): array {
        $tableNames = config('permission.table_names');
        $rolesTableName = $tableNames['roles'];
        $roleHasPermissionTableName = $tableNames['role_has_permissions'];
        $roles = [
            'Admin' => '*',
            'Editor' => ['view_self_user'],
            'Contributor' => ['view_self_user'],
            'Author' => ['view_self_user']
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
                    'updated_at' => now()
                ]);
                $result[$role] = DB::table($rolesTableName)
                    ->where('name', '=', $role)
                    ->where('guard_name', '=', 'web')
                    ->first()->id;
            } else
                $result[$role] = $exists->id;

            if (is_string($permissions) && $permissions === '*')
                $permissionsArray = array_keys($permissionsMap);
            else $permissionsArray = $permissions;

            foreach ($permissionsArray as $permission) {
                $exists = DB::table($roleHasPermissionTableName)
                    ->where('permission_id', '=', $permissionsMap[$permission])
                    ->where('role_id', '=', $result[$role])
                    ->first();
                if (is_null($exists)) {
                    DB::table($roleHasPermissionTableName)->insert([
                        'permission_id' => $permissionsMap[$permission],
                        'role_id' => $result[$role]
                    ]);
                }
            }
        }

        return $result;
    }

    /**
     * Insert the default users with the default roles
     *
     * @param array $rolesMap
     */
    public function addDefaultUsers(array $rolesMap) {
        $users = [
            [
                'name' => 'Webmapp Team',
                'email' => 'team@webmapp.it',
                'password' => bcrypt('webmapp'),
                'role' => 'Admin'
            ],
            [
                'name' => 'Alessio Piccioli',
                'email' => 'alessiopiccioli@webmapp.it',
                'password' => bcrypt('webmapp'),
                'role' => 'Admin'
            ],
            [
                'name' => 'Andrea Del Sarto',
                'email' => 'andreadel84@gmail.com',
                'password' => bcrypt('webmapp'),
                'role' => 'Admin'
            ],
            [
                'name' => 'Antonella Puglia',
                'email' => 'antonellapuglia@webmapp.it',
                'password' => bcrypt('webmapp'),
                'role' => 'Admin'
            ],
            [
                'name' => 'Davide Pizzato',
                'email' => 'davidepizzato@webmapp.it',
                'password' => bcrypt('webmapp'),
                'role' => 'Admin'
            ],
            [
                'name' => 'Marco Barbieri',
                'email' => 'marcobarbieri@webmapp.it',
                'password' => bcrypt('webmapp'),
                'role' => 'Admin'
            ],
            [
                'name' => 'Pedram Katanchi',
                'email' => 'pedramkatanchi@webmapp.it',
                'password' => bcrypt('webmapp'),
                'role' => 'Admin'
            ],
            [
                'name' => 'Laura Roth',
                'email' => 'lauraroth72@gmail.com',
                'password' => bcrypt('geohub'),
                'role' => 'Editor'
            ]
        ];
        $tableNames = config('permission.table_names');
        $modelHasRolesTableName = $tableNames['model_has_roles'];

        foreach ($users as $user) {
            $exists = DB::table('users')
                ->where('email', '=', $user['email'])
                ->first();
            $userId = null;

            if (is_null($exists)) {
                DB::table('users')->insert(
                    [
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'password' => $user['password'],
                        'email_verified_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $userId = DB::table('users')
                    ->where('email', '=', $user['email'])
                    ->first()->id;
            } else $userId = $exists->id;

            $exists = DB::table($modelHasRolesTableName)
                ->where('role_id', '=', $rolesMap[$user['role']])
                ->where('model_id', '=', $userId)
                ->where('model_type', '=', User::class)
                ->first();

            if (is_null($exists)) {
                DB::table($modelHasRolesTableName)->insert([
                    'role_id' => $rolesMap[$user['role']],
                    'model_id' => $userId,
                    'model_type' => User::class
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
    }
}
