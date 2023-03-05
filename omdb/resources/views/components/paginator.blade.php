<div class="pagination">
  @if ($page > 1)
    <b>
      <span> <a href='?page=1'>&laquo;</a> </span>
      <span> <a href='?page={{ $page - 1 }}'>&lsaquo;</a> </span>
    </b>
  @endif

  <span id="page">{{ $page }}</span>

  @if ($page < $num_pages)
    <b>
      <span> <a href='?page={{ $page + 1 }}'>&rsaquo;</a> </span>
      <span> <a href='?page={{ $num_pages }}'>&raquo;</a> </span>
    </b>
  @endif
</div>
