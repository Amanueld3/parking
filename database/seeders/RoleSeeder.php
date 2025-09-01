<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionPrefixes = [
            'view_',
            'view_any_',
            'create_',
            'update_',
            'delete_',
            'delete_any_',
            'restore_',
            'restore_any_',
            'replicate_',
            'reorder_',
            'force_delete_',
            'force_delete_any_',
        ];

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->permissions()->detach();
        $superAdminPermissions = [];
        foreach ($permissionPrefixes as $prefix) {
            if (Permission::where('name', "{$prefix}role")->exists()) {
                $superAdminPermissions[] = "{$prefix}role";
            }
            $superAdminPermissions[] = "{$prefix}user";
        }
        $superAdmin->givePermissionTo($superAdminPermissions);


        $Admin = Role::firstOrCreate(['name' => 'admin']);
        $Admin->permissions()->detach();
        $AdminPermissions = [];
        foreach ($permissionPrefixes as $prefix) {
            if (Permission::where('name', "{$prefix}role")->exists()) {
                $AdminPermissions[] = "{$prefix}role";
            }
            $AdminPermissions[] = "{$prefix}owner";
            $AdminPermissions[] = "{$prefix}place";
            $AdminPermissions[] = "{$prefix}slot";
            $AdminPermissions[] = "{$prefix}agent";
            $AdminPermissions[] = "{$prefix}user";
            $AdminPermissions[] = "{$prefix}vehicle";
        }
        $AdminPermissions[] = 'view_payment';
        $AdminPermissions[] = 'view_any_payment';
        $Admin->givePermissionTo($AdminPermissions);


        $owner = Role::firstOrCreate(['name' => 'owner']);
        $owner->permissions()->detach();
        $ownerPermissions = [];
        foreach ($permissionPrefixes as $prefix) {
            $ownerPermissions[] = "{$prefix}place";
            $ownerPermissions[] = "{$prefix}slot";
        }
        $owner->givePermissionTo($ownerPermissions);

        $agent = Role::firstOrCreate(['name' => 'agent']);
        $agent->permissions()->detach();
        $agentPermissions = [];
        $agentPermissions[] = 'page_CheckoutParking';
        $agentPermissions[] = 'page_ParkingDesk';
        $agentPermissions[] = 'widget_AgentShortcutNavigation';
        $agentPermissions[] = 'create_vehicle';
        $agentPermissions[] = 'view_any_vehicle';

        $agent->givePermissionTo($agentPermissions);
    }
}
