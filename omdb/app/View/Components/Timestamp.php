<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Timestamp extends Component
{
  protected $time;

  /**
   * Create a new component instance.
   */
  public function __construct($time)
  {
    $this->time = $time;
  }

  /**
   * Get the view / contents that represent the component.
   */
  public function render(): View|Closure|string
  {
    return view("components.timestamp", ["time" => $this->time]);
  }
}
