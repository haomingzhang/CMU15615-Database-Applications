CREATE VIEW uidusefulness AS SELECT r.uid, SUM(r.votes_useful) AS usefulness FROM review AS r GROUP BY r.uid;
SELECT uu.uid, yu.name, uu.usefulness FROM uidusefulness AS uu, yelp_user AS yu WHERE uu.uid=yu.uid and uu.usefulness >= ALL(SELECT uidusefulness.usefulness FROM uidusefulness) ORDER BY uu.uid ASC;
DROP VIEW uidusefulness;
