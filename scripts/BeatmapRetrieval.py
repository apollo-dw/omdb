import mysql.connector
from ossapi import *
import os

rulesets = {
    "osu": 0,
    "taiko": 1,
    "fruits": 2,
    "mania": 3,
}

DatabaseUser = os.getenv('DATABASE_USER')
DatabasePassword = os.getenv('DATABASE_PASSWORD')
DatabaseHost=os.getenv('DATABASE_HOST')
DatabaseTable='omdb'

# Set up the connection to the database
cnx = mysql.connector.connect(user=DatabaseUser,
                              password=DatabasePassword,
                              host=DatabaseHost,
                              database=DatabaseTable)
cursor = cnx.cursor()


api = Ossapi(os.getenv('OSU_CLIENT_ID'), os.getenv('OSU_CLIENT_SECRET'))

cursor.execute("SELECT Timestamp FROM beatmaps ORDER BY Timestamp DESC LIMIT 0, 1;")
LatestDbMap = cursor.fetchall()[0]
LatestAddedDate = LatestDbMap[0]

sql_beatmap_creators = "INSERT INTO beatmap_creators (BeatmapID, CreatorID) VALUES (%s, %s);"
sql_beatmaps = """
REPLACE INTO beatmaps
    (BeatmapID, SetID, SR, DifficultyName, Mode, Status, Blacklisted, BlacklistReason, ApproachRate, CircleSize, Drain, OverallDifficulty, CircleCount, SpinnerCount, SliderCount, PlayTime, Bpm)
VALUES
    (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);"""

sql_beatmapsets = """
REPLACE INTO beatmapsets
    (DateRanked, Artist, SetID, CreatorID, Genre, Lang, Title, Status, HasStoryboard, HasVideo, CreatorName, IsNSFW)
VALUES
    (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);"""

sql_beatmapset_nominators = "INSERT INTO beatmapset_nominators (SetID, NominatorID, Mode) VALUES (%s, %s, %s);"

sql_descriptors = """
INSERT INTO descriptor_votes (BeatmapID, UserID, Vote, DescriptorID)
VALUES (%s, 0, 1, %s)
ON DUPLICATE KEY UPDATE Vote = 1;
"""

sql_update_search = """
UPDATE beatmapsets s
LEFT JOIN (
    SELECT
        b.SetID,
        BIT_OR(1 << b.Mode) AS ModeMask,
        LEFT(CONCAT_WS(' ',
            s2.Artist,
            s2.Title,
            GROUP_CONCAT(DISTINCT b.DifficultyName SEPARATOR ' '),
            GROUP_CONCAT(DISTINCT COALESCE(mn.Username, u.Username) SEPARATOR ' ')
        ), 2048) AS SearchText,
        LEFT(CONCAT_WS(',',
            b.SetID,
            GROUP_CONCAT(DISTINCT b.BeatmapID),
            GROUP_CONCAT(DISTINCT COALESCE(bc.CreatorID, s2.CreatorID))
        ), 2048) AS SearchIDs
    FROM beatmaps b
    LEFT JOIN beatmapsets s2 ON b.SetID = s2.SetID
    LEFT JOIN beatmap_creators bc ON b.BeatmapID = bc.BeatmapID
    LEFT JOIN users u ON u.UserID = COALESCE(bc.CreatorID, s2.CreatorID)
    LEFT JOIN mappernames mn ON mn.UserID = COALESCE(bc.CreatorID, s2.CreatorID)
    WHERE b.SetID = %s
    GROUP BY b.SetID
) t ON t.SetID = s.SetID
SET
    s.SearchText = t.SearchText,
    s.SearchIDs = t.SearchIDs,
    s.ModeMask = COALESCE(t.ModeMask, 0)
WHERE s.SetID = %s;
"""

while True:
    beatmapsets = api.search_beatmapsets(query=f"ranked>\"{LatestAddedDate}\"", sort="ranked_asc", explicit_content="show").beatmapsets
    print("Found " + str(len(beatmapsets)) + " beatmapsets")
    if len(beatmapsets) == 0:
        print("we are done sir")
        break

    for set in beatmapsets:
        display_date = set.ranked_date
        if str(set.ranked) == "RankStatus.LOVED":
            display_date = set.submitted_date

        fullSet = api.beatmapset(set)
        is_featured_artist = hasattr(set, 'track_id') and set.track_id is not None

        for nomination in fullSet.current_nominations:
            userID = nomination.user_id
            for ruleset in nomination.rulesets:
                try:
                    vals = (fullSet.id, userID, rulesets[ruleset])
                    cursor.execute(sql_beatmapset_nominators, vals)
                    cnx.commit()
                except Exception as e:
                    print(f"Error occurred with inserting set {fullSet.id}: {e}")

        #DateRanked, Artist, SetID, CreatorID, Genre, Lang, Title, Status, HasStoryboard, HasVideo
        val = (display_date.strftime('%Y-%m-%d %H:%M:%S'), # DateRanked,
               set.artist, # Artist
               set.id, # SetID
               set.user_id, # SetCreatorID
               fullSet.genre["id"], # Genre
               fullSet.language["id"], # Lang
               set.title, # Title
               set.status.value, # Status
               fullSet.storyboard, # HasStoryboard
               fullSet.video, # HasVideo
               set.creator, # CreatorName
               set.nsfw # IsNSFW
               )

        try:
            cursor.execute(sql_beatmapsets, val)
            cnx.commit()
            print(f"Inserted set {set.id}")
        except Exception as error:
            print(f"Error occurred with set {set.id}", error)

        for map in fullSet.beatmaps or []:
            owners = getattr(map, "owners", None)

            cursor.execute("SELECT * FROM blacklist WHERE UserID = '" + str(map.user_id) + "';")
            result = cursor.fetchone()

            if result:
                blacklisted = 1
                blacklist_reason = "mapper has requested blacklist"
            else:
                blacklisted = 0
                blacklist_reason = None

            if owners:
                creator_ids = [o.id for o in owners]
            else:
                creator_ids = [map.user_id]
            creator_ids = list(dict.fromkeys(creator_ids))

            for creator_id in creator_ids:
                creator_val = (map.id, creator_id)
                try:
                    print(f"Inserting set creators set {set.id} map {map.id}. Creator: {creator_id}")
                    cursor.execute(sql_beatmap_creators, creator_val)
                    cnx.commit()
                except Exception as e:
                    print(
                        f"Error occurred with inserting set creators set {set.id} map {map.id}. Creators: {creator_ids}"
                        f"Error: {e}"
                    )

            #BeatmapID, SetID, SR, DifficultyName, Mode, Status, Blacklisted, BlacklistReason
            val = (map.id, # BeatmapID
                   set.id, # SetID
                   map.difficulty_rating, # SR
                   map.version, # Difficulty Name
                   map.mode_int, # Mode
                   map.status.value, # Status
                   blacklisted, # Blacklisted
                   blacklist_reason, # BlacklistReason
                   map.ar, # AR
                   map.cs, # CS
                   map.drain, # HP
                   map.accuracy, # OD
                   map.count_circles, # circles
                   map.count_spinners, # spinners
                   map.count_sliders, #sliders
                   map.total_length, # play time
                   map.bpm # bpm
                   )

            try:
                cursor.execute(sql_beatmaps, val)
                cnx.commit()
                print(f"Inserted set {set.id} => map {map.id}")
            except Exception as error:
                print(f"Error occurred with set {set.id} => map {map.id}", error)

            votes_to_insert = []

            owners = getattr(map, "owners", None)
            mapper_count = len(owners)

            is_collab = False
            is_megacollab = False

            if mapper_count >= 8:
                is_megacollab = True
            elif mapper_count >= 2:
                is_collab = True

            # precision: if CS >= 6
            if getattr(map, 'cs', 0) >= 6 and getattr(map, 'mode_int', 0) == 0:
                votes_to_insert.append(35)

            # large circles: if CS <= 3 and SR >= 4.0
            if getattr(map, 'cs', 10) < 3 and getattr(map, 'difficulty_rating', 0) >= 4.0 and getattr(map, 'mode_int', 0) == 0:
                votes_to_insert.append(82)

            # slider only: if circle count == 0
            if getattr(map, 'count_circles', -1) == 0 and getattr(map, 'mode_int', 0) == 0:
                votes_to_insert.append(69)

            # circle only: if slider count == 0
            if getattr(map, 'count_sliders', -1) == 0 and getattr(map, 'mode_int', 0) == 0:
                votes_to_insert.append(70)

            total_length = getattr(map, 'total_length', 0)
            if total_length > 600:
                votes_to_insert.append(40) # gungathon only
            elif total_length > 300:
                votes_to_insert.append(39) # marathon only

            # Featured Artist check
            if hasattr(fullSet, 'track_id') and fullSet.track_id is not None:
                votes_to_insert.append(78)

            # Apply Collab Tiers
            if is_megacollab:
                votes_to_insert.append(68)
            elif is_collab:
                votes_to_insert.append(38)

            for descriptor_id in votes_to_insert:
                try:
                    cursor.execute(sql_descriptors, (map.id, descriptor_id))
                except Exception as desc_error:
                    print(f"Failed descriptor {descriptor_id} for map {map.id}:", desc_error)
            cnx.commit()

        LatestAddedDate = set.ranked_date.strftime('%Y-%m-%d %H:%M:%S')
        cursor.execute("SET SESSION group_concat_max_len = 65535")
        cursor.execute(sql_update_search, (set.id, set.id))
        cnx.commit()



clear_cache_query = "DELETE FROM cache_home_recent_maps;"
cursor.execute(clear_cache_query)
cnx.commit()

for mode in range(4):
    used_sets = []
    cursor.execute("SELECT s.SetID, Artist, Title, CreatorID, DateRanked, b.Timestamp FROM beatmaps b JOIN beatmapsets s ON b.SetID = s.SetID WHERE Mode = %s ORDER BY b.Timestamp DESC LIMIT 200;", (mode,))

    for row in cursor.fetchall():
        if row[0] in used_sets:
            continue
        if len(used_sets) >= 8:
            break

        metadata = f"{row[1]} - {row[2]}"
        cache_insert_query = "INSERT INTO cache_home_recent_maps (SetID, Timestamp, Metadata, CreatorID, Mode) VALUES (%s, %s, %s, %s, %s);"
        cache_data = (row[0], row[5], metadata, row[3], mode)
        cursor.execute(cache_insert_query, cache_data)
        used_sets.append(row[0])

cnx.commit()

print("done")

print("Starting full descriptor rebuild...")
cursor.execute("TRUNCATE TABLE beatmap_descriptors")
cursor.execute("""
            INSERT INTO beatmap_descriptors (BeatmapID, DescriptorID, Weight)
            SELECT
                BeatmapID,
                DescriptorID,
                SUM(CASE WHEN Vote = 1 THEN 1 ELSE -1 END) AS net
            FROM descriptor_votes
            GROUP BY BeatmapID, DescriptorID
            HAVING net > 0
""")
cnx.commit()

cursor.close()
cnx.close()
