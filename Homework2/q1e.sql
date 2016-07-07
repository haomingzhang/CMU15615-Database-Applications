CREATE VIEW breakfast AS SELECT DISTINCT b.bid, name, state FROM business AS b, business_category AS bc WHERE b.bid = bc.bid and b.state='PA' and b.city='Pittsburgh' and bc.category LIKE '%Breakfast & Brunch%';
CREATE VIEW bidreview AS SELECT b.bid, COUNT(review.bid) AS reviewnum FROM breakfast AS b, review WHERE b.bid=review.bid GROUP BY b.bid;
CREATE VIEW bidscore AS SELECT b.bid, AVG(review.stars) AS score FROM breakfast AS b, review WHERE b.bid=review.bid GROUP BY b.bid;
SELECT b.bid, b.name, s.score, r.reviewnum FROM breakfast AS b, bidreview AS r, bidscore AS s WHERE b.bid=r.bid and b.bid=s.bid ORDER BY s.score DESC, r.reviewnum DESC, b.bid ASC LIMIT 10;
DROP VIEW breakfast CASCADE;

