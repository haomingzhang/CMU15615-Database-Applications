CREATE VIEW uidstate AS SELECT r.uid, COUNT(DISTINCT b.state) AS statenum FROM review As r, business AS b WHERE b.bid=r.bid GROUP BY r.uid;
SELECT yu.uid, yu.name, us.statenum FROM yelp_user AS yu, uidstate AS us WHERE yu.uid=us.uid and us.statenum>5 ORDER BY us.statenum DESC, us.uid ASC;
DROP VIEW uidstate;
