import mysql.connector
from ossapi import *
import os

DatabaseUser = os.getenv('DATABASE_USER')
DatabasePassword = os.getenv('DATABASE_PASSWORD')
DatabaseHost=os.getenv('DATABASE_HOST')
DatabaseTable='omdb'

cnx = mysql.connector.connect(user=DatabaseUser,
                              password=DatabasePassword,
                              host=DatabaseHost,
                              database=DatabaseTable)
cursor = cnx.cursor()

api = Ossapi(os.getenv('OSU_CLIENT_ID'), os.getenv('OSU_CLIENT_SECRET'))

sql_descriptors = """
INSERT INTO descriptor_votes (BeatmapID, UserID, Vote, DescriptorID)
VALUES (%s, 0, 1, %s)
ON DUPLICATE KEY UPDATE Vote = 1;
"""

if len(sys.argv) != 2:
    print(f"Usage: python {sys.argv[0]} <featured_artist_id>")
    sys.exit(1)

featured_artist_id = int(sys.argv[1])

page_cursor = None
while True:
    result = api.search_beatmapsets(
        query=f"featured_artist={featured_artist_id}",
        sort="ranked_asc",
        explicit_content="show",
        cursor=page_cursor
    )

    beatmapsets = result.beatmapsets
    print("Found " + str(len(beatmapsets)) + " beatmapsets")

    if len(beatmapsets) == 0:
        print("we are done sir")
        break

    for set in beatmapsets:
        is_featured_artist = hasattr(set, 'track_id') and set.track_id is not None

        for map in set.beatmaps or []:
            votes_to_insert = []

            if is_featured_artist:
                votes_to_insert.append(78)

            for descriptor_id in votes_to_insert:
                try:
                    cursor.execute(sql_descriptors, (map.id, descriptor_id))
                except Exception as desc_error:
                    print(
                        f"Failed descriptor {descriptor_id} for map {map.id}:",
                        desc_error
                    )

    cnx.commit()
    page_cursor = result.cursor

    if page_cursor is None:
        print("we are done sir")
        break

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
