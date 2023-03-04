<?php

namespace App\View\Components\Ratings;

use App\Models\Rating;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Latest extends Component
{
  protected $user_id = null;
  protected $beatmapset_id = null;

  /**
   * Create a new component instance.
   */
  public function __construct(
    string $beatmapsetId = '',
    string $userId = '',
  )
  {
    if ($beatmapsetId !== '')
      $this->beatmapset_id = intval($beatmapsetId);
    if ($userId !== '')
      $this->user_id = intval($userId);
  }

  /**
   * Get the view / contents that represent the component.
   */
  public function render(): View|Closure|string
  {
    $query = Rating::with("beatmap")
      ->with("osu_user");

    if ($this->user_id !== null)
      $query = $query->where("user_id", $this->user_id);
    if ($this->beatmapset_id !== null)
      $query = $query->where("beatmapset_id", $this->beatmapset_id);

    $ratings = $query
      ->orderByDesc('created_at')
      ->get();

    return view("components.ratings.latest", [
      "beatmapset_id" => $this->beatmapset_id,
      "ratings" => $ratings,
    ]);
  }
}
