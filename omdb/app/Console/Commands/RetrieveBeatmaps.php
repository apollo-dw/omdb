<?php

namespace App\Console\Commands;

use App\Models\Beatmap;
use App\Models\BeatmapSet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Promise;

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

        $client = new GuzzleClient([
            'base_uri' => 'https://osu.ppy.sh/api/v2',
            'request.options' => [
                'headers' => "Bearer {$access_token}",
            ],
        ]);

        while (true) {
            // Retrieve beatmaps
            $response = Http::withToken($access_token)->get('https://osu.ppy.sh/api/v2/beatmapsets/search', [
                'query' => 'ranked>2023/02/20',
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
            $db_beatmaps = array();

            /* $requests = array();
            foreach ($beatmapsets as $beatmapset) {
                array_push($requests, $client->getAsync("/beatmapsets/{$beatmapset['id']}"));
            }
            $responses = Promise\Utils::unwrap($requests);
            info('WTF ' . json_encode($responses)); */

            foreach ($beatmapsets as $beatmapset) {
                // TODO: This is slow when running sequentially, need to
                // refactor this to use Guzzle async requests.
                $response = Http::withToken($access_token)
                    ->withUrlParameters(['id' => $beatmapset['id']])
                    ->get('https://osu.ppy.sh/api/v2/beatmapsets/{id}');

                $full_beatmapset = $response->json();

                array_push($db_beatmapsets, [
                    'id' => $beatmapset['id'],
                    'creator_id' => $beatmapset['user_id'],
                    'artist' => $beatmapset['artist'],
                    'title' => $beatmapset['title'],
                    'genre' => $full_beatmapset['genre']['id'],
                    'language' => $full_beatmapset['language']['id'],
                    'date_ranked' => $beatmapset['ranked_date'],
                ]);

                // TODO: Blacklisting
                foreach ($beatmapset['beatmaps'] as $beatmap) {
                    array_push($db_beatmaps, [
                        'id' => $beatmap['id'],
                        'beatmapset_id' => $beatmapset['id'],
                        'difficulty_name' => $beatmap['version'],
                        'mode' => $beatmap['mode_int'],
                        'status' => $beatmap['status'],
                        'star_rating' => $beatmap['difficulty_rating'],
                    ]);
                }
            }

            BeatmapSet::insert($db_beatmapsets);
            Beatmap::insert($db_beatmaps);

            $this->info('Found and inserted ' . count($beatmapsets) . ' sets.');

            $cursor = $result['cursor_string'];
            $this->info('Cursor: ' . json_encode($result['cursor']));
            if ($cursor === null) break;
        }
    }
}
