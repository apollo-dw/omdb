<?php
    require '../../base.php';
	header('Content-Type: application/json; charset=utf-8');

    $beatmapId = $_GET["id"];

    if (!is_numeric($beatmapId))
        return;

    $beatmap = GetBeatmapDataOsuApi($token, $beatmapId);
    $owners = !empty($beatmap["owners"]) ? $beatmap["owners"] : [["id" => $beatmap["user_id"]]];

    $response = json_encode($owners);
	echo $response;