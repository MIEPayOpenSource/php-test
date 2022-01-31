ALTER TABLE `appointment`.`todos`
ADD COLUMN `completed` TINYINT(1) NULL DEFAULT 0 AFTER `description`;