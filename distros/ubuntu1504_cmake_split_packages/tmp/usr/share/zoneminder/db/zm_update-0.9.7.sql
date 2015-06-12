--
-- This updates a 0.9.7 database to 0.9.8
--
alter table Filters modify column AutoDelete tinyint unsigned not null default 0;
alter table Filters modify column AutoUpload tinyint unsigned not null default 0;
alter table Filters add column AutoEmail tinyint unsigned not null default 0;
alter table Filters add column AutoMessage tinyint unsigned not null default 0;
alter table Events add column Emailed tinyint unsigned not null default 0 after Uploaded;
alter table Events add column Messaged tinyint unsigned not null default 0 after Emailed;
