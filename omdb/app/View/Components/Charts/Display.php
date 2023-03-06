<?php

namespace App\View\Components\Charts;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Display extends Component
{
  protected $beatmaps;

  /**
   * Create a new component instance.
   */
  public function __construct($beatmaps)
  {
    $this->beatmaps = $beatmaps;
  }

  /**
   * Get the view / contents that represent the component.
   */
  public function render(): View|Closure|string
  {
    return view("components.charts.display", [
      "beatmaps" => $this->beatmaps,
    ]);
  }
}
