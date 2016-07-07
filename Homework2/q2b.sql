CREATE VIEW uidhit AS SELECT r.uid, COUNT(r.bid) AS hit FROM review AS r GROUP BY r.uid;
CREATE VIEW bidonehit AS SELECT b.bid, COUNT(r.uid) AS onehitnum FROM business AS b, review AS r WHERE b.bid=r.bid and r.stars=5 and r.uid IN (SELECT uid from uidhit WHERE hit=1)  GROUP BY b.bid;
SELECT bo.bid, b.name, bo.onehitnum FROM business AS b, bidonehit AS bo WHERE b.bid=bo.bid and bo.onehitnum>150 ORDER BY onehitnum DESC, b.name ASC, bo.bid ASC;
DROP VIEW uidhit CASCADE;
