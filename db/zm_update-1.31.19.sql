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

    call update_storage_stats(OLD.StorageId, diff);
  ELSE
    call update_storage_stats(NEW.StorageId, NEW.DiskSpace);
    call update_storage_stats(OLD.StorageId, -OLD.DiskSpace);
  END IF;

end;

//

delimiter ;

drop trigger if exists event_insert_trigger;

delimiter //

create trigger event_insert_trigger

after insert

on Events

for each row

begin

  call update_storage_stats(NEW.StorageId, NEW.DiskSpace);

end;

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

