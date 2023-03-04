<?php

namespace App\View\Components\Ratings;

use Closure;
use Illuminate\Contracts\View\View;
use App\Models\OmdbUser;
use App\Models\Rating;
use Illuminate\View\Component;

class Display extends Component
{
  protected $rating;

  /**
   * Create a new component instance.
   */
  public function __construct(Rating $rating)
  {
    $this->rating = $rating;
  }

  /**
   * Get the view / contents that represent the component.
   */
  public function render(): View|Closure|string
  {
    $hint = null;

    if ($this->rating->omdb_user) {
      $custom_ratings = json_decode($this->rating->omdb_user->custom_ratings, true);
      info('custom ratings' . json_encode($custom_ratings));
      $rs = number_format($this->rating->score, 1);
      $hint = $custom_ratings[$rs] ?? null;
    }

    return view("components.ratings.display", [
      'rating' => $this->rating,
      'hint' => $hint,
    ]);
  }
}
