/*
Add the EndTime IS NOT NULL term to the Update Disk Space Filter.
This will only work if they havn't modified the stock filter 
 */
UPDATE Filters SET Query_json='{"terms":[{"attr":"DiskSpace","op":"IS","val":"NULL"},{"cnj":"and","obr":"0","attr":"EndDateTime","op":"IS NOT","val":"NULL","cbr":"0"}]}' WHERE Query_json='{"terms":[{"attr":"DiskSpace","op":"IS","val":"NULL"}]}';

/*
Add the EndTime IS NOT NULL term to the Purge When Full Filter. 
This will only work if they havn't modified the stock filter .
This is important to prevent SQL Errors inserting into Frames table if PurgeWhenFull deletes in-progress events.
 */
UPDATE Filters SET Query_json='{"sort_field":"Id","terms":[{"val":0,"attr":"Archived","op":"="},{"cnj":"and","val":95,"attr":"DiskPercent","op":">="},{"cnj":"and","obr":"0","attr":"EndDateTime","op":"IS NOT","val":"NULL","cbr":"0"}],"limit":100,"sort_asc":1}' WHERE Query_json='{"sort_field":"Id","terms":[{"val":0,"attr":"Archived","op":"="},{"cnj":"and","val":95,"attr":"DiskPercent","op":">="}],"limit":100,"sort_asc":1}';
