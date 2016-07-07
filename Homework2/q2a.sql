CREATE VIEW scores AS SELECT b.bid, COUNT(r.stars) AS reviewnum, MIN(r.stars) AS stars FROM business AS b, review AS r WHERE b.bid = r.bid and b.state='PA' GROUP BY b.bid;
SELECT s.bid, b.name, s.reviewnum FROM business AS b, scores AS s WHERE b.bid=s.bid and s.reviewnum>10 and s.stars=5 ORDER BY reviewnum DESC, bid ASC;
DROP VIEW scores;
