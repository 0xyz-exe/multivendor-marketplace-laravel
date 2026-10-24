<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Books', 'Home', 'Electronics'] as $name) Category::firstOrCreate(['name' => $name]);
        if (!DB::table('marketplace_settings')->exists()) DB::table('marketplace_settings')->insert(['commission_percent' => 10, 'created_at' => now(), 'updated_at' => now()]);
    }
}
