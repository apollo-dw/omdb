<div class='starRatingDisplay'
  @if ($hint) title="{{ $hint }}" style='border-bottom:1px dotted white;' @endif>
  <div class='starBackground'>
    <i class='icon-star'></i>
    <i class='icon-star'></i>
    <i class='icon-star'></i>
    <i class='icon-star'></i>
    <i class='icon-star'></i>

    <div class='starForeground'>
      @for ($i = 0; $i < 5; $i++)
        @if ($i < $rating->score)
          @if ($rating->score - 0.5 == $i)
            <i class='star icon-star-half'></i>
          @else
            <i class='star icon-star'></i>
          @endif
        @endif
      @endfor
    </div>
  </div>
</div>
