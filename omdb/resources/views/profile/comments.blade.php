@extends('layouts.master')

@section('title', 'Comments')

@section('content')

  <div style="text-align:center;">
    <x-paginator :page="$page" :num-pages="$num_pages" />
  </div>

  <div class="flex-container commentContainer" style="width:100%;">
    @foreach ($comments as $comment)
      <div class="flex-container flex-child commentHeader">
        <div class="flex-child" style="height:24px;width:24px;">
          <a href="/profile/{{ $comment->user_id }}"><img
              src="https://s.ppy.sh/a/{{ $comment->user_id }}"
              style="height:24px;width:24px;"
              title="{{ $comment->osu_user->username }}" /></a>
        </div>
        <div classz="flex-child">
          <a
            href="/profile/{{ $comment->user_id }}">{{ $comment->osu_user->username }}</a>
          on <a href="../../mapset/{{ $comment->user_id }}">
            {{ $comment->beatmapset->artist }} -
            {{ $comment->beatmapset->title }}
          </a>
        </div>
        <div class="flex-child" style="margin-left:auto;">
          @if ($comment->user_id == -1)
            <i class="icon-remove removeComment" style="color:#f94141;"
              value="{{ $comment->id }}"></i>
          @endif

          {{ $comment->created_at->diffForHumans() }}
        </div>
      </div>
      <div class="flex-child comment" style="min-width:0;overflow: hidden;">
        <div>
          <a href="../../mapset/{{ $comment->beatmapset_id }}"><img
              src="https://b.ppy.sh/thumb/{{ $comment->beatmapset_id }}l.jpg"
              class="diffThumb"
              onerror="this.onerror=null; this.src='/charts/INF.png';"
              style="height:64px;width:64px;float:left;margin:0.5rem;"></a>
        </div>
        <p>
          {{-- TODO: ParseOsuLinks --}}
          {{ nl2br($comment->comment) }}
        </p>
      </div>
    @endforeach
  </div>

  <div style="text-align:center;">
    <x-paginator :page="$page" :num-pages="$num_pages" />
  </div>

@endsection
