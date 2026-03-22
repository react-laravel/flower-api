<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FlowerSeeder extends Seeder
{
    public function run(): void
    {
        (new FlowerDataSeeder())->run();
    }
}
