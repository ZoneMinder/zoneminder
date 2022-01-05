/* Change Cause from varchar(32) to TEXT.  We now include alarmed zone name */
ALTER TABLE `Events` MODIFY `Cause` TEXT;
