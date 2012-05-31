SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE SCHEMA IF NOT EXISTS `Featherweight` ;
USE `Featherweight` ;

-- -----------------------------------------------------
-- Table `Featherweight`.`groups`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `Featherweight`.`groups` ;

CREATE  TABLE IF NOT EXISTS `Featherweight`.`groups` (
  `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(20) NOT NULL ,
  `description` VARCHAR(100) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
AUTO_INCREMENT = 3
DEFAULT CHARACTER SET = latin1;

-- -----------------------------------------------------
-- Table `Featherweight`.`users_groups`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `Featherweight`.`users_groups` ;

CREATE TABLE IF NOT EXISTS `users_groups` (
  `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` MEDIUMINT(8) UNSIGNED NOT NULL,
  `group_id` MEDIUMINT(8) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
);
ENGINE = InnoDB
AUTO_INCREMENT = 3
DEFAULT CHARACTER SET = latin1;

-- -----------------------------------------------------
-- Table `Featherweight`.`login_attempts`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `login_attempts`;

CREATE TABLE `login_attempts` (
  `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` VARBINARY(16) NOT NULL,
  `login` VARCHAR(100) NOT NULL,
  `time` INT(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`)
);
ENGINE = InnoDB
AUTO_INCREMENT = 3
DEFAULT CHARACTER SET = latin1;

-- -----------------------------------------------------
-- Table `Featherweight`.`users`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `Featherweight`.`users` ;

CREATE  TABLE IF NOT EXISTS `Featherweight`.`users` (
  `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` VARBINARY(16) NOT NULL,
  `username` VARCHAR(100) NOT NULL,
  `password` VARCHAR(80) NOT NULL,
  `salt` VARCHAR(40) DEFAULT NULL,
  `email` VARCHAR(100) NOT NULL,
  `activation_code` VARCHAR(40) DEFAULT NULL,
  `forgotten_password_code` VARCHAR(40) DEFAULT NULL,
  `forgotten_password_time` INT(11) UNSIGNED DEFAULT NULL,
  `remember_code` VARCHAR(40) DEFAULT NULL,
  `created_on` INT(11) UNSIGNED NOT NULL,
  `last_login` INT(11) UNSIGNED DEFAULT NULL,
  `active` tinyINT(1) UNSIGNED DEFAULT NULL,
  `first_name` VARCHAR(50) DEFAULT NULL,
  `last_name` VARCHAR(50) DEFAULT NULL,
  `company` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `fk_users_groups`
    FOREIGN KEY (`group_id` )
    REFERENCES `Featherweight`.`groups` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 3
DEFAULT CHARACTER SET = latin1;

CREATE INDEX `fk_users_groups` ON `Featherweight`.`users` (`group_id` ASC) ;


-- -----------------------------------------------------
-- Table `Featherweight`.`meta`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `Featherweight`.`meta` ;

CREATE  TABLE IF NOT EXISTS `Featherweight`.`meta` (
  `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `user_id` MEDIUMINT(8) UNSIGNED NOT NULL ,
  `first_name` VARCHAR(50) NULL DEFAULT NULL ,
  `last_name` VARCHAR(50) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `fk_meta_users1`
    FOREIGN KEY (`user_id` )
    REFERENCES `Featherweight`.`users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 3
DEFAULT CHARACTER SET = latin1;

CREATE INDEX `fk_meta_users1` ON `Featherweight`.`meta` (`user_id` ASC) ;


-- -----------------------------------------------------
-- Table `Featherweight`.`addons`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `Featherweight`.`addons` ;

CREATE  TABLE IF NOT EXISTS `Featherweight`.`addons` (
  `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `addon_id` VARCHAR(45) NOT NULL ,
  `type` SMALLINT UNSIGNED NULL ,
  `download_count` INT UNSIGNED NULL DEFAULT 0 ,
  `created_on` INT(11) NOT NULL ,
  `changed_on` INT(11) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;

CREATE UNIQUE INDEX `addon_id_UNIQUE` ON `Featherweight`.`addons` (`addon_id` ASC) ;


-- -----------------------------------------------------
-- Table `Featherweight`.`tags`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `Featherweight`.`tags` ;

CREATE  TABLE IF NOT EXISTS `Featherweight`.`tags` (
  `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `tag` VARCHAR(100) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;

CREATE UNIQUE INDEX `tag_UNIQUE` ON `Featherweight`.`tags` (`tag` ASC) ;


-- -----------------------------------------------------
-- Table `Featherweight`.`addon_tags`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `Featherweight`.`addon_tags` ;

CREATE  TABLE IF NOT EXISTS `Featherweight`.`addon_tags` (
  `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `addon_id` MEDIUMINT(8) UNSIGNED NOT NULL ,
  `tag_id` MEDIUMINT(8) UNSIGNED NOT NULL ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `fk_addon_tags_addons1`
    FOREIGN KEY (`addon_id` )
    REFERENCES `Featherweight`.`addons` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_addon_tags_tags1`
    FOREIGN KEY (`tag_id` )
    REFERENCES `Featherweight`.`tags` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_addon_tags_addons1` ON `Featherweight`.`addon_tags` (`addon_id` ASC) ;

CREATE INDEX `fk_addon_tags_tags1` ON `Featherweight`.`addon_tags` (`tag_id` ASC) ;


-- -----------------------------------------------------
-- Table `Featherweight`.`addon_ownerships`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `Featherweight`.`addon_ownerships` ;

CREATE  TABLE IF NOT EXISTS `Featherweight`.`addon_ownerships` (
  `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `addon_id` MEDIUMINT(8) UNSIGNED NOT NULL ,
  `user_id` MEDIUMINT(8) UNSIGNED NOT NULL ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `fk_addon_ownerships_addons1`
    FOREIGN KEY (`addon_id` )
    REFERENCES `Featherweight`.`addons` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_addon_ownerships_users1`
    FOREIGN KEY (`user_id` )
    REFERENCES `Featherweight`.`users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_addon_ownerships_addons1` ON `Featherweight`.`addon_ownerships` (`addon_id` ASC) ;

CREATE INDEX `fk_addon_ownerships_users1` ON `Featherweight`.`addon_ownerships` (`user_id` ASC) ;


-- -----------------------------------------------------
-- Table `Featherweight`.`addon_files`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `Featherweight`.`addon_files` ;

CREATE  TABLE IF NOT EXISTS `Featherweight`.`addon_files` (
  `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `addon_id` MEDIUMINT(8) UNSIGNED NOT NULL ,
  `os` VARCHAR(10) NULL DEFAULT NULL ,
  `version` VARCHAR(15) NOT NULL ,
  `created_on` INT(11) NOT NULL ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `fk_addon_files_addons1`
    FOREIGN KEY (`addon_id` )
    REFERENCES `Featherweight`.`addons` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_addon_files_addons1` ON `Featherweight`.`addon_files` (`addon_id` ASC) ;

CREATE UNIQUE INDEX `version_UNIQUE` ON `Featherweight`.`addon_files` (`addon_id` ASC, `version` ASC, `os` ASC) ;


-- -----------------------------------------------------
-- Table `Featherweight`.`addon_changelog`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `Featherweight`.`addon_changelog` ;

CREATE  TABLE IF NOT EXISTS `Featherweight`.`addon_changelog` (
  `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `file_id` MEDIUMINT(8) UNSIGNED NOT NULL ,
  `locale` VARCHAR(5) NOT NULL ,
  `text` MEDIUMTEXT NULL ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `fk_addon_changelog_addon_files1`
    FOREIGN KEY (`file_id` )
    REFERENCES `Featherweight`.`addon_files` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_addon_changelog_addon_files1` ON `Featherweight`.`addon_changelog` (`file_id` ASC) ;


-- -----------------------------------------------------
-- Table `Featherweight`.`addon_compartibility`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `Featherweight`.`addon_compartibility` ;

CREATE  TABLE IF NOT EXISTS `Featherweight`.`addon_compartibility` (
  `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `file_id` MEDIUMINT(8) UNSIGNED NOT NULL ,
  `application_id` VARCHAR(254) NOT NULL ,
  `min_version` VARCHAR(15) NOT NULL ,
  `max_version` VARCHAR(15) NOT NULL ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `fk_addon_compartibility_addon_files1`
    FOREIGN KEY (`file_id` )
    REFERENCES `Featherweight`.`addon_files` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_addon_compartibility_addon_files1` ON `Featherweight`.`addon_compartibility` (`file_id` ASC) ;


-- -----------------------------------------------------
-- Table `Featherweight`.`addon_meta`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `Featherweight`.`addon_meta` ;

CREATE  TABLE IF NOT EXISTS `Featherweight`.`addon_meta` (
  `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `addon_id` MEDIUMINT(8) UNSIGNED NOT NULL ,
  `locale` VARCHAR(5) NOT NULL ,
  `name` VARCHAR(100) NOT NULL ,
  `description` MEDIUMTEXT NULL DEFAULT NULL ,
  `homepage` VARCHAR(254) NULL DEFAULT NULL ,
  `email` VARCHAR(254) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `fk_addon_meta_addons1`
    FOREIGN KEY (`addon_id` )
    REFERENCES `Featherweight`.`addons` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_addon_meta_addons1` ON `Featherweight`.`addon_meta` (`addon_id` ASC) ;


-- -----------------------------------------------------
-- Table `Featherweight`.`addon_credits`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `Featherweight`.`addon_credits` ;

CREATE  TABLE IF NOT EXISTS `Featherweight`.`addon_credits` (
  `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `addon_id` MEDIUMINT(8) UNSIGNED NOT NULL ,
  `type` SMALLINT NOT NULL ,
  `name` VARCHAR(100) NOT NULL ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `fk_addon_credits_addons1`
    FOREIGN KEY (`addon_id` )
    REFERENCES `Featherweight`.`addons` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_addon_credits_addons1` ON `Featherweight`.`addon_credits` (`addon_id` ASC) ;


-- -----------------------------------------------------
-- Table `Featherweight`.`addon_comments`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `Featherweight`.`addon_comments` ;

CREATE  TABLE IF NOT EXISTS `Featherweight`.`addon_comments` (
  `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `users_id` MEDIUMINT(8) UNSIGNED NOT NULL ,
  `addons_id` MEDIUMINT(8) UNSIGNED NOT NULL ,
  `created_on` INT(11) NOT NULL ,
  `text` MEDIUMTEXT NOT NULL ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `fk_addon_comments_users1`
    FOREIGN KEY (`users_id` )
    REFERENCES `Featherweight`.`users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_addon_comments_addons1`
    FOREIGN KEY (`addons_id` )
    REFERENCES `Featherweight`.`addons` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_addon_comments_users1` ON `Featherweight`.`addon_comments` (`users_id` ASC) ;

CREATE INDEX `fk_addon_comments_addons1` ON `Featherweight`.`addon_comments` (`addons_id` ASC) ;


-- -----------------------------------------------------
-- Table `Featherweight`.`addon_ratings`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `Featherweight`.`addon_ratings` ;

CREATE  TABLE IF NOT EXISTS `Featherweight`.`addon_ratings` (
  `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `users_id` MEDIUMINT(8) UNSIGNED NOT NULL ,
  `addons_id` MEDIUMINT(8) UNSIGNED NOT NULL ,
  `text` MEDIUMTEXT NOT NULL ,
  `rating` TINYINT UNSIGNED NOT NULL ,
  `created_on` INT(11) NOT NULL ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `fk_addon_ratings_users1`
    FOREIGN KEY (`users_id` )
    REFERENCES `Featherweight`.`users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_addon_ratings_addons1`
    FOREIGN KEY (`addons_id` )
    REFERENCES `Featherweight`.`addons` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_addon_ratings_users1` ON `Featherweight`.`addon_ratings` (`users_id` ASC) ;

CREATE INDEX `fk_addon_ratings_addons1` ON `Featherweight`.`addon_ratings` (`addons_id` ASC) ;


-- -----------------------------------------------------
-- Insert initial data needed to login as 'admin@admin.com' with 'password'
-- -----------------------------------------------------
INSERT INTO `users` (`id`, `ip_address`, `username`, `password`, `salt`, `email`, `activation_code`, `forgotten_password_code`, `forgotten_password_time`, `remember_code`, `created_on`, `last_login`, `active`, `first_name`, `last_name`, `company`, `phone`) VALUES
(1, '', 'Administrator', '$2a$10$5vjDMHEgWV5skr9ZcmG59eanRbsEDjyUOiULMHYku7bfVjhqRlpdK', '6f86b7f2168f0c5e7599cb5614270c', 'admin@admin.com', '28501cf96bab91b55bb0125104c158a096c0d695', NULL, NULL, NULL, 1338435150, 1338435150, 1, 'Admin', 'Istrator', 'Admin', '555-555-5555');
INSERT INTO `meta` (`id`, `user_id`, `first_name`, `last_name`) VALUES
(1, 1, 'Addons', 'Admin');
INSERT INTO `groups` (`id`, `name`, `description`) VALUES
(1, 'admin', 'Administrator'),
(2, 'members', 'General User');
INSERT INTO `users_groups` (`id`, `user_id`, `group_id`) VALUES
	(1,1,1),
	(1,1,2);

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
