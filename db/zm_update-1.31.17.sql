alter table Events modify Id int(10) unsigned;
alter table Events DROP Primary key;
alter table Events Add Primary key(Id);
alter table Events modify Id int(10) unsigned auto_incremement;

