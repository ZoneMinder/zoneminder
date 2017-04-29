--
-- This updates a 1.30.2 database to 1.30.3
--
-- Make changes to Config table
--
ALTER TABLE Config ADD COLUMN `Advanced` BOOL NOT NULL DEFAULT 0 AFTER `Requires`;
UPDATE Config SET Advanced = '1' WHERE ID IN ( \
19, 20, 32, 39, 40, 41, 89, \
11, 12, 13,  87, 93, 94, 131, 132, 133, 134, 135, \
136, 146, 147, 155 \
);
