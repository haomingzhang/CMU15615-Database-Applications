CREATE VIEW bars AS SELECT DISTINCT b.bid, name, state FROM business AS b, business_category AS bc WHERE b.bid = bc.bid and bc.category LIKE '%Bars%';
CREATE VIEW bidreview AS SELECT bars.bid, COUNT(review.bid) AS reviewnum FROM bars, review WHERE bars.bid=review.bid GROUP BY bars.bid;
CREATE VIEW maxreviewperstate AS SELECT state, MAX(reviewnum) AS maxreview FROM bidreview, bars WHERE bars.bid=bidreview.bid GROUP BY state;
SELECT bars.bid, bars.name, br.reviewnum, bars.state FROM bars, bidreview AS br, maxreviewperstate AS mrps WHERE bars.bid=br.bid and bars.state=mrps.state and br.reviewnum=mrps.maxreview ORDER BY bars.state ASC, bars.bid ASC;
DROP VIEW bars CASCADE;
