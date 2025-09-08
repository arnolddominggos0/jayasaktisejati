<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\{User, Branch};
use Illuminate\Support\Facades\Hash;

class RolesAndUsersSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['super_admin','office_admin','field_coordinator','customer'] as $r) {
            Role::findOrCreate($r, 'web');
        }

        $jkt = Branch::firstOrCreate(['code'=>'JKT'], ['name'=>'Jakarta']);
        $mdo = Branch::firstOrCreate(['code'=>'MDO'], ['name'=>'Manado']);

        $admin = User::firstOrCreate(
            ['email'=>'admin@jss.local'],
            ['name'=>'Super Admin','password'=>Hash::make('Admin#12345'),'branch_id'=>$jkt->id]
        ); $admin->syncRoles(['super_admin']);

        $oa = User::firstOrCreate(
            ['email'=>'office.jkt@jss.local'],
            ['name'=>'Office Admin JKT','password'=>Hash::make('Admin#12345'),'branch_id'=>$jkt->id]
        ); $oa->syncRoles(['office_admin']);

        $fc = User::firstOrCreate(
            ['email'=>'koor.mdo@jss.local'],
            ['name'=>'Koordinator MDO','password'=>Hash::make('Admin#12345'),'branch_id'=>$mdo->id]
        ); $fc->syncRoles(['field_coordinator']);

        $cust = User::firstOrCreate(
            ['email'=>'customer@jss.local'],
            ['name'=>'Customer Demo','password'=>Hash::make('Admin#12345'),'branch_id'=>$mdo->id]
        ); $cust->syncRoles(['customer']);
    }
}
