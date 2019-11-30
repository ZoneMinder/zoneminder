ALTER TABLE Events MODIFY `Orientation` enum('0','90','180','270','hori','vert','ROTATE_0','ROTATE_90','ROTATE_180','ROTATE_270','FLIP_HORI','FLIP_VERT') NOT NULL default 'ROTATE_0';
UPDATE Events SET Orientation='ROTATE_0' WHERE Orientation='0';
UPDATE Events SET Orientation='ROTATE_90' WHERE Orientation='90';
UPDATE Events SET Orientation='ROTATE_180' WHERE Orientation='180';
UPDATE Events SET Orientation='ROTATE_270' WHERE Orientation='270';
UPDATE Events SET Orientation='FLIP_HORI' WHERE Orientation='hori';
UPDATE Events SET Orientation='FLIP_VERT' WHERE Orientation='vert';

ALTER TABLE Events MODIFY `Orientation` enum('ROTATE_0','ROTATE_90','ROTATE_180','ROTATE_270','FLIP_HORI','FLIP_VERT') NOT NULL default 'ROTATE_0';

ALTER TABLE Monitors MODIFY `Orientation` enum('0','90','180','270','hori','vert','ROTATE_0','ROTATE_90','ROTATE_180','ROTATE_270','FLIP_HORI','FLIP_VERT') NOT NULL default 'ROTATE_0';
UPDATE Monitors SET Orientation='ROTATE_0' WHERE Orientation='0';
UPDATE Monitors SET Orientation='ROTATE_90' WHERE Orientation='90';
UPDATE Monitors SET Orientation='ROTATE_180' WHERE Orientation='180';
UPDATE Monitors SET Orientation='ROTATE_270' WHERE Orientation='270';
UPDATE Monitors SET Orientation='FLIP_HORI' WHERE Orientation='hori';
UPDATE Monitors SET Orientation='FLIP_VERT' WHERE Orientation='vert';

ALTER TABLE Monitors MODIFY `Orientation` enum('ROTATE_0','ROTATE_90','ROTATE_180','ROTATE_270','FLIP_HORI','FLIP_VERT') NOT NULL default 'ROTATE_0';
