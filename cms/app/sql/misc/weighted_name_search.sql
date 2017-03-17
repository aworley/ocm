ALTER TABLE aliases ADD COLUMN keywords TEXT;
ALTER TABLE aliases ADD FULLTEXT(keywords);
