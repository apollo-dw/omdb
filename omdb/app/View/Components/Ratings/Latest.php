<?php

namespace App\View\Components\Ratings;

use App\Models\Rating;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Latest extends Component
{
    protected $beatmapset_id;

    /**
     * Create a new component instance.
     */
    public function __construct(string $beatmapsetId)
    {
        $this->beatmapset_id = intval($beatmapsetId);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $ratings = Rating::where('beatmapset_id', $this->beatmapset_id)
            ->with('beatmap')
            ->with('osu_user')
            ->get();

        return view('components.ratings.latest', [
            'beatmapset_id' => $this->beatmapset_id,
            'ratings' => $ratings,
        ]);
    }
}
