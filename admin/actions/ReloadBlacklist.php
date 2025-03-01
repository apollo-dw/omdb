<?php
    require_once ('../../base.php');
    require ('../base.php');

    $stmt = $conn->prepare("UPDATE beatmaps SET Blacklisted = '0' WHERE BlacklistReason = 'mapper has requested blacklist';");
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE beatmaps AS bm
                                      SET ChartRank = NULL,
                                        ChartYearRank = NULL,
                                        Rating = NULL,
                                        Blacklisted = '1',
                                        BlacklistReason = 'mapper has requested blacklist'
                                      WHERE bm.BeatmapID IN (
                                        SELECT bc1.BeatmapID
                                        FROM beatmap_creators bc1
                                        WHERE bc1.CreatorID IN (
                                            SELECT UserID
                                            FROM blacklist
                                        )
                                        AND NOT EXISTS (
                                            SELECT 1
                                            FROM beatmap_creators bc2
                                            WHERE bc1.BeatmapID = bc2.BeatmapID
                                            AND bc2.CreatorID NOT IN (
                                                SELECT UserID
                                                FROM blacklist
                                            )
                                        )
                                      );");
    $stmt->execute();

    header("Location: ../blacklist.php");
    die();