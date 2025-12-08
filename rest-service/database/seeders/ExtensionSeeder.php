<?php

namespace Database\Seeders;

use App\Models\Extension;
use Illuminate\Database\Seeder;

class ExtensionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $extension = new Extension;
        $extension->name = 'Opg to 3D';
        $extension->status = 'active';
        $extension->queue_name = '';
        $extension->start_date = null;
        $extension->unique_name = 'opg-to-3d';
        $extension->save();
    }
}
