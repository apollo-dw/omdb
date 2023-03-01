<?php

namespace App\Console\Commands;

use App\Models\Beatmap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RetrieveBeatmaps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'omdb:retrieve_beatmaps';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the list of beatmaps from OSU';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Get an API key
        $response = Http::asForm()->post('https://osu.ppy.sh/oauth/token', [
            'client_id' => config('auth.osu_client_id'),
            'client_secret' => config('auth.osu_client_secret'),
            'grant_type' => 'client_credentials',
            'scope' => 'public',
        ]);
        $result = $response->json();
        $access_token = $result["access_token"];

        info('Got access token.', ['access_token' => $access_token]);

        $cursor = null;

        while (true) {
            // Retrieve beatmaps
            $response = Http::withToken($access_token)->get('https://osu.ppy.sh/api/v2/beatmapsets/search', [
                'query' => 'ranked>2023/02/01',
                'sort' => 'ranked_asc',
                'explicit_content' => 'show',
                'cursor_string' => $cursor,
            ]);

            $result = $response->json();
            $beatmapsets = $result['beatmapsets'];

            if (count($beatmapsets) == 0) {
                $this->info('Done.');
                break;
            }

            $db_beatmapsets = array();

            foreach ($beatmapsets as $beatmapset) {
                // TODO: This is slow when running sequentially, need to
                // refactor this to use Guzzle async requests.
                $response = Http::withToken($access_token)
                    ->withUrlParameters(['id' => $beatmapset['id']])
                    ->get('https://osu.ppy.sh/api/v2/beatmapsets/{id}');

                $full_beatmapset = $response->json();

                // TODO: Blacklisting
                foreach ($beatmapset['beatmaps'] as $beatmap) {
                    array_push($db_beatmapsets, [
                        'beatmap_id' => $beatmap['id'],
                        'beatmapset_id' => $beatmapset['id'],
                        'beatmapset_creator_id' => $beatmapset['user_id'],
                        'difficulty_name' => $beatmap['version'],
                        'artist' => $beatmapset['artist'],
                        'title' => $beatmapset['title'],
                        'mode' => $beatmap['mode_int'],
                        'status' => $beatmap['status'],
                        'genre' => $full_beatmapset['genre']['id'],
                        'language' => $full_beatmapset['language']['id'],
                        'star_rating' => $beatmap['difficulty_rating'],
                        'date_ranked' => $beatmapset['ranked_date'],
                    ]);
                }
            }

            Beatmap::insert($db_beatmapsets);

            $this->info('Found and inserted ' . count($beatmapsets) . ' sets.');

            $cursor = $result['cursor_string'];
            $this->info('Cursor: ' . json_encode($result['cursor']));
            if ($cursor === null) break;
        }
    }
}
