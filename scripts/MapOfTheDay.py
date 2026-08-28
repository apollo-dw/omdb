import mysql.connector
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

sql_beatmap_search = """SELECT b.BeatmapID FROM beatmaps b JOIN beatmapsets s ON b.SetID = s.SetID WHERE b.blacklisted = 0 AND b.Mode = %s AND (b.SR >= 4 OR NOT EXISTS (SELECT 1 FROM beatmaps b2 WHERE b2.SetID = b.SetID AND b2.Mode = b.Mode AND b2.SR > b.SR)) AND s.DateRanked < NOW() - INTERVAL 3 MONTH ORDER BY RAND() LIMIT 1"""
sql_motd = "REPLACE INTO cache (Attribute, Value) VALUES (%s, %s)";

for mode in range(4):
    cursor.execute(sql_beatmap_search, (mode,))
    result = cursor.fetchone()

    if result:
        beatmap_id = result[0]
        attribute = f"motd_{mode}"

        cursor.execute(sql_motd, (attribute, beatmap_id,))

cnx.commit()
cursor.close()
cnx.close()
