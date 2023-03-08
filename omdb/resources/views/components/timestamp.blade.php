@if ($time)
  <span style="text-decoration-line: underline; text-decoration-style: dotted;"
    title="{{ $time->toIso8601String() }}">
    {{ $time->diffForHumans() }}
  </span>
@endif
