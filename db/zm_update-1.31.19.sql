drop procedure if exists update_storage_stats;

delimiter //

create procedure update_storage_stats(IN StorageId smallint(5), IN space BIGINT)

sql security invoker

deterministic

begin

  update Storage set DiskSpace = DiskSpace + space where Id = StorageId;

end;

//

delimiter ;

drop trigger if exists event_update_trigger;

delimiter //

create trigger event_update_trigger

after update

on Events

for each row

begin
    declare diff BIGINT default 0;

    set diff = NEW.DiskSpace - OLD.DiskSpace;
  IF ( NEW.StorageId = OLD.StorageID ) THEN

    IF ( diff ) THEN
    call update_storage_stats(OLD.StorageId, diff);
    END IF;
  ELSE
    IF ( NEW.DiskSpace ) THEN
    call update_storage_stats(NEW.StorageId, NEW.DiskSpace);
    END IF;
    IF ( OLD.DiskSpace ) THEN
    call update_storage_stats(OLD.StorageId, -OLD.DiskSpace);
    END IF;
  END IF;

end;

//

delimiter ;

drop trigger if exists event_insert_trigger;

delimiter //
/*
create trigger event_insert_trigger

after insert

on Events

for each row

begin

  call update_storage_stats(NEW.StorageId, NEW.DiskSpace);

end;
*/
//

delimiter ;


drop trigger if exists event_delete_trigger;

delimiter //

create trigger event_delete_trigger

before delete

on Events

for each row

begin

  call update_storage_stats(OLD.StorageId, -OLD.DiskSpace);

end;

//

delimiter ;

set @exist := (select count(*) from information_schema.statistics where table_name = 'Events' and index_name = 'Archived' and table_schema = database());
set @sqlstmt := if( @exist > 0, 'DROP INDEX Archived ON Events', "SELECT 'Archived INDEX is already removed.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_name = 'Events' and index_name = 'Frames' and table_schema = database());
set @sqlstmt := if( @exist > 0, 'DROP INDEX Frames ON Events', "SELECT 'Frames INDEX is already removed.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_name = 'Events' and index_name = 'Events_StorageId_idx' and table_schema = database());
set @sqlstmt := if( @exist > 0, "SELECT 'Index Events_StorageId_idx already exists.'", 'CREATE INDEX Events_StorageId_idx on Events (StorageId)');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_name = 'Events' and index_name = 'Events_EndTime_DiskSpace_idx' and table_schema = database());
set @sqlstmt := if( @exist > 0, "SELECT 'Index Events_EndTime_DiskSpace_idx already exists.'", 'CREATE INDEX Events_EndTime_DiskSpace_idx on Events (EndTime, DiskSpace)');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

UPDATE Storage SET DiskSpace=(SELECT SUM(DiskSpace) FROM Events WHERE StorageId=Storage.Id);

