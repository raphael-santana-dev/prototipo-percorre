<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            UserSeeder::class,
            SystemBaseSeeder::class,
            PendingPermissionsAndFeaturesSeeder::class,
            PendingFeaturesSeeder::class,
            EtapaSeeder::class,
            StudentSeeder::class,
            StatusInscricaoBaseSeeder::class,
            UnidadeSeeder::class,
            CursoSeeder::class,
        ]);
    }
}
