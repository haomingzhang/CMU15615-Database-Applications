CREATE VIEW burger AS SELECT DISTINCT b.bid, name  FROM business AS b, business_category AS bc WHERE b.bid = bc.bid and b.state='PA' and b.city='Pittsburgh' and bc.category LIKE '%Burgers%';
CREATE VIEW uidstate AS SELECT r.uid, COUNT(DISTINCT b.state) AS statenum FROM review As r, business AS b WHERE b.bid=r.bid GROUP BY r.uid;

CREATE VIEW bidreview AS SELECT b.bid, COUNT(review.bid) AS reviewnum FROM burger AS b, review, uidstate WHERE b.bid=review.bid and review.uid IN (SELECT uid FROM uidstate WHERE statenum>2) GROUP BY b.bid;
CREATE VIEW bidscore AS SELECT b.bid, AVG(review.stars) AS score FROM burger AS b, review WHERE b.bid=review.bid and review.uid IN (SELECT uid FROM uidstate WHERE statenum>2) GROUP BY b.bid;
SELECT b.bid, b.name,s.score, r.reviewnum FROM burger AS b, bidreview AS r, bidscore AS s WHERE b.bid=r.bid and b.bid=s.bid ORDER BY s.score DESC, r.reviewnum DESC, b.bid ASC LIMIT 5;

DROP VIEW burger CASCADE;
DROP VIEW uidstate;
