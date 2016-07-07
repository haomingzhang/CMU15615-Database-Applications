CREATE VIEW avgmay AS SELECT r.bid, AVG(r.stars) AS scoremay FROM review AS r WHERE EXTRACT(MONTH FROM r.date)=5 AND EXTRACT(YEAR FROM r.date)=2011 GROUP BY r.bid;
CREATE VIEW avgjune AS SELECT r.bid, AVG(r.stars) AS scorejune FROM review AS r WHERE EXTRACT(MONTH FROM r.date)=6 AND EXTRACT(YEAR FROM r.date)=2011 GROUP BY r.bid;
CREATE VIEW jmp AS SELECT b.bid, b.name, scorejune-scoremay AS jump FROM business AS b, avgmay AS m, avgjune AS j WHERE b.bid=m.bid and b.bid=j.bid;
SELECT * FROM jmp WHERE jump>1 ORDER BY jump DESC, name ASC, bid ASC LIMIT 10;
DROP VIEW avgmay CASCADE;
DROP VIEW avgjune;
