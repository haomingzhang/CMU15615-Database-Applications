CREATE VIEW coffeepa AS SELECT DISTINCT b.bid from business AS b, business_category AS bc where b.bid=bc.bid AND b.state='PA' AND b.name LIKE '%Coffee%' AND bc.category LIKE '%Coffee%';
SELECT DISTINCT b.bid, b.name from business AS b, business_category AS bc where b.bid=bc.bid AND b.state='PA' AND b.name LIKE '%Coffee%' AND NOT b.bid IN(SELECT bid FROM coffeepa) ORDER BY b.bid ASC;
DROP VIEW coffeepa;
