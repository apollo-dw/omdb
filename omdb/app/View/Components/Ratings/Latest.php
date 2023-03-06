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
  protected $show_user = true;
  protected $show_map_meta = true;
  protected $paginated = true;

  /**
   * Create a new component instance.
   */
  public function __construct(
    string $beatmapsetId = "",
    string $userId = "",
    bool $showUser = true,
    bool $showMapMeta = true,
    bool $paginated = false
  ) {
    if ($beatmapsetId !== "") {
      $this->beatmapset_id = intval($beatmapsetId);
    }
    if ($userId !== "") {
      $this->user_id = intval($userId);
    }

    $this->show_user = $showUser;
    $this->show_map_meta = $showMapMeta;
  }

  /**
   * Get the view / contents that represent the component.
   */
  public function render(): View|Closure|string
  {
    $query = Rating::with("beatmapset")
      ->with("beatmap")
      ->with("osu_user");

    if ($this->user_id !== null) {
      $query = $query->where("user_id", $this->user_id);
    }
    if ($this->beatmapset_id !== null) {
      $query = $query->where("beatmapset_id", $this->beatmapset_id);
    }

    $query = $query->orderByDesc("updated_at");

    if ($this->paginated) {
      $ratings = $query->paginate(10);
    } else {
      $ratings = $query->get();
    }

    return view("components.ratings.latest", [
      "beatmapset_id" => $this->beatmapset_id,
      "ratings" => $ratings,
      "show_user" => $this->show_user,
      "show_map_meta" => $this->show_map_meta,
      "paginated" => $this->paginated,
    ]);
  }
}
