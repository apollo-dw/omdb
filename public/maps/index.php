<?php
    $PageTitle = "Maps";
    require '../header.php';
?>

<style>
  .map-search-bar {
    border: 0;
    outline: 0;
    font-family: var(--main-theme-text-font-family), Verdana, sans-serif;
    width: stretch;
    min-width: 0;
    z-index: 2;
    height: 4rem;
    font-size: 2em;
    box-sizing: border-box;
    display: inline-block;
    margin: 0px;
    padding: 0 1em;
    width: 100%;
    min-width: 0;
    flex-shrink: 1;
  }
</style>

<h1 style="margin: 0;">Map search</h1>
<hr>

<input class="map-search-bar" type="text" placeholder="Type in to search" ></form>