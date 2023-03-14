<?php

function parse_osu_links(string $string)
{
  $pattern = "/(\d+):(\d{2}):(\d{3})\s*(\(((\d,?)+)\))?/";
  $replacement = '<a class="osuTimestamp" href="osu://edit/$0">$0</a>';
  return preg_replace($pattern, $replacement, $string);
}
