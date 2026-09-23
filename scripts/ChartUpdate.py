import time
import mysql.connector
import os

DB_CONFIG = {
    "host": os.getenv('DATABASE_HOST'),
    "user": os.getenv('DATABASE_USER'),
    "password": os.getenv('DATABASE_PASSWORD'),
    "database": "omdb",
}

# deweighted users have abused ratings somehow, most commonly by spamming 0s/5s to specific mappers
# this should be databased instead
deweighted_users = [4960893, 10286675, 11212698, 8523723, 21489103]

def main():
    start_time = time.time()

    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor(buffered=True)

    try:
        # calculate user weights
        print(f"{time.time() - start_time:.4f}: calculating user weighting")

        deweighted_csv = ",".join(map(str, deweighted_users))

        sql_update_user_weights = f"""
            UPDATE `users` u
            LEFT JOIN (
                SELECT
                    user_scores.UserID,
                    SUM(user_scores.cnt) AS total_ratings,
                    -SUM((user_scores.cnt / total.total_cnt) * LOG2(user_scores.cnt / total.total_cnt)) AS entropy
                FROM (
                    SELECT UserID, Score, COUNT(*) as cnt
                    FROM ratings
                    GROUP BY UserID, Score
                ) user_scores
                JOIN (
                    SELECT UserID, COUNT(*) as total_cnt
                    FROM ratings
                    GROUP BY UserID
                ) total ON user_scores.UserID = total.UserID
                GROUP BY user_scores.UserID
            ) stats ON u.UserID = stats.UserID
            LEFT JOIN blacklist b ON u.UserID = b.UserID
            SET u.Weight = CASE
                WHEN u.UserID IN ({deweighted_csv}) THEN 0
                ELSE GREATEST(
                    0.1,
                    POW(LEAST(1.0, COALESCE(stats.entropy, 0) / 2.3), 3)
                    * LEAST(1.0, COALESCE(stats.total_ratings, 0) / 50.0)
                    * CASE WHEN u.LastAccessedSite < NOW() - INTERVAL 90 DAY THEN 0.7 ELSE 1.0 END
                ) * CASE WHEN b.UserID IS NOT NULL THEN 0.01 ELSE 1.0 END
            END
        """
        cursor.execute(sql_update_user_weights)
        conn.commit()

        # calculate ratings for each mode
        for mode in range(4):
            print(
                f"{time.time() - start_time:.4f}: calculating ratings for gamemode {mode}"
            )

            cursor.execute("SELECT AVG(Score) FROM ratings")
            row = cursor.fetchone()
            m = float(row[0]) if row and row[0] is not None else 0.0

            min_rating_count = 5 if mode == 0 else 2
            confidence = 24 if mode == 0 else 5

            # rating count and bayesian average
            sql_update_mode_ratings = f"""
                UPDATE beatmaps b
                JOIN (
                    SELECT
                        r.BeatmapID,
                        SUM(r.Score * u.Weight) / NULLIF(SUM(u.Weight), 0) AS weighted_avg,
                        SUM(u.Weight) AS weight_sum,
                        COUNT(*) AS rating_count
                    FROM ratings r
                    JOIN users u ON r.UserID = u.UserID
                    GROUP BY r.BeatmapID
                ) stats ON b.BeatmapID = stats.BeatmapID
                SET
                    b.WeightedAvg = stats.weighted_avg,
                    b.RatingCount = stats.rating_count,
                    b.Rating = CASE
                        WHEN stats.weight_sum IS NULL OR stats.weight_sum < 1.5 THEN NULL
                        WHEN {mode} = 0 AND stats.weight_sum < 20 THEN
                            2.9 + POW((LEAST(20.0, GREATEST(1.0, stats.weight_sum)) - 1.0) / 19.0, 3) *
                            ((((stats.weight_sum * stats.weighted_avg) + ({m} * {confidence})) / (stats.weight_sum + {confidence})) - 2.9)
                        ELSE
                            ((stats.weight_sum * stats.weighted_avg) + ({m} * {confidence})) / (stats.weight_sum + {confidence})
                    END
                WHERE b.Blacklisted = 0 AND b.Mode = {mode};
            """
            cursor.execute(sql_update_mode_ratings)
            conn.commit()

            # cache chart ranks
            print(f"{time.time() - start_time:.4f}: caching chart information for mode {mode}")

            sql_update_chart_ranks = f"""
                UPDATE beatmaps b
                JOIN (
                    SELECT
                        BeatmapID,
                        IF(global_rank < 10000, global_rank, NULL) AS rank_val,
                        IF(year_rank < 10000, year_rank, NULL) AS year_rank_val
                    FROM (
                        SELECT
                            b.BeatmapID,
                            ROW_NUMBER() OVER (ORDER BY b.Rating DESC) AS global_rank,
                            ROW_NUMBER() OVER (PARTITION BY YEAR(s.DateRanked) ORDER BY b.Rating DESC) AS year_rank
                        FROM beatmaps b
                        JOIN beatmapsets s ON s.SetID = b.SetID
                        WHERE b.Rating IS NOT NULL
                          AND b.Blacklisted = 0
                          AND b.RatingCount >= {min_rating_count}
                          AND b.Mode = {mode}
                    ) ranked_stats
                ) stats ON b.BeatmapID = stats.BeatmapID
                SET
                    b.ChartRank = stats.rank_val,
                    b.ChartYearRank = stats.year_rank_val;
            """
            cursor.execute(sql_update_chart_ranks)
            conn.commit()

            print(
                f"{time.time() - start_time:.4f}: removing unrated beatmaps from charts"
            )
            cursor.execute(
                f"""
                UPDATE beatmaps
                SET ChartRank = NULL, ChartYearRank = NULL
                WHERE Mode = {mode} AND RatingCount <= {min_rating_count}
            """
            )
            conn.commit()

            # calculate controversy
            print(f"{time.time() - start_time:.4f}: calculating controversy")
            sql_update_controversy = f"""
                UPDATE beatmaps b
                JOIN (
                    SELECT
                        r.BeatmapID,
                        -SUM((r.cnt / t.total_cnt) * LOG2(r.cnt / t.total_cnt)) AS entropy
                    FROM (
                        SELECT BeatmapID, Score, COUNT(*) AS cnt
                        FROM ratings
                        GROUP BY BeatmapID, Score
                    ) r
                    JOIN (
                        SELECT BeatmapID, COUNT(*) AS total_cnt
                        FROM ratings
                        GROUP BY BeatmapID
                        HAVING total_cnt > {min_rating_count}
                    ) t ON r.BeatmapID = t.BeatmapID
                    GROUP BY r.BeatmapID
                ) stats ON b.BeatmapID = stats.BeatmapID
                SET b.controversy = stats.entropy
                WHERE b.Mode = {mode};
            """
            cursor.execute(sql_update_controversy)
            conn.commit()

        # cache best maps and max ratings
        print(f"{time.time() - start_time:.4f}: caching best maps for each mode")

        cursor.execute("TRUNCATE TABLE cache_home_best_map;")
        sql_insert_home_best = """
            INSERT INTO cache_home_best_map (BeatmapID, Mode)
            SELECT BeatmapID, Mode FROM (
                SELECT b.BeatmapID, b.Mode,
                       ROW_NUMBER() OVER (PARTITION BY b.Mode ORDER BY b.Rating DESC) as rn
                FROM beatmaps b
                JOIN beatmapsets s ON s.SetID = b.SetID
                WHERE s.DateRanked >= DATE_SUB(NOW(), INTERVAL WEEKDAY(NOW()) + 7 DAY)
                  AND s.DateRanked < DATE_SUB(NOW(), INTERVAL WEEKDAY(NOW()) DAY)
                  AND b.Rating IS NOT NULL
            ) ranked
            WHERE rn = 1;
        """
        cursor.execute(sql_insert_home_best)
        conn.commit()

        print(
            f"{time.time() - start_time:.4f}: updating MaxRating for beatmaps"
        )
        sql_update_max_rating = """
            UPDATE beatmapsets s
            LEFT JOIN (
                SELECT SetID, MAX(RatingCount) AS MaxRating
                FROM beatmaps
                GROUP BY SetID
            ) b ON b.SetID = s.SetID
            SET s.MaxRating = COALESCE(b.MaxRating, 0);
        """
        cursor.execute(sql_update_max_rating)
        conn.commit()

        print(
            f"Total execution time in seconds: {time.time() - start_time:.4f}"
        )

    except mysql.connector.Error as err:
        print(f"Database Error: {err}")
    finally:
        cursor.close()
        conn.close()


if __name__ == "__main__":
    main()
