<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Paginator extends Component
{
  protected int $page;
  protected int $num_pages;

  /**
   * Create a new component instance.
   */
  public function __construct(int $page, int $numPages)
  {
    $this->page = $page;
    $this->num_pages = $numPages;
  }

  /**
   * Get the view / contents that represent the component.
   */
  public function render(): View|Closure|string
  {
    return view("components.paginator", [
      "page" => $this->page,
      "num_pages" => $this->num_pages,
    ]);
  }
}
