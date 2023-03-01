<?php

namespace Database\Seeders;

use App\Models\Beatmap;
use App\Models\Username;
use App\Models\BeatmapSet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BeatmapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $results = BeatmapSet::factory(10)->create();

        foreach ($results as $beatmapset) {
            Beatmap::factory(10)->withId($beatmapset->id)->create();

            Username::factory()->withId($beatmapset->creator_id)->create();
        }
    }
}
