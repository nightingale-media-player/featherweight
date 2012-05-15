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
-- Table `Featherweight`.`users`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `Featherweight`.`users` ;

CREATE  TABLE IF NOT EXISTS `Featherweight`.`users` (
  `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `group_id` MEDIUMINT(8) UNSIGNED NOT NULL ,
  `ip_address` CHAR(16) NOT NULL ,
  `username` VARCHAR(15) NOT NULL ,
  `password` VARCHAR(40) NOT NULL ,
  `salt` VARCHAR(40) NULL DEFAULT NULL ,
  `email` VARCHAR(254) NOT NULL ,
  `activation_code` VARCHAR(40) NULL DEFAULT NULL ,
  `forgotten_password_code` VARCHAR(40) NULL DEFAULT NULL ,
  `remember_code` VARCHAR(40) NULL DEFAULT NULL ,
  `created_on` INT(11) UNSIGNED NOT NULL ,
  `last_login` INT(11) UNSIGNED NULL DEFAULT NULL ,
  `active` TINYINT(1) UNSIGNED NULL DEFAULT NULL ,
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

INSERT INTO `users` (`id`, `group_id`, `ip_address`, `username`, `password`, `salt`, `email`, `activation_code`, `forgotten_password_code`, `remember_code`, `created_on`, `last_login`, `active`) VALUES
(1, 1, '127.0.0.1', 'addons admin', '39e35cb02c5e170678176fea8c2a4c3e3efe03a8', '4f13f7d4f216878247609d2acc2ce2', 'admin@admin.com', NULL, NULL, NULL, 1324280040, 1324280114, 1);
INSERT INTO `meta` (`id`, `user_id`, `first_name`, `last_name`) VALUES
(1, 1, 'Addons', 'Admin');
INSERT INTO `groups` (`id`, `name`, `description`) VALUES
(1, 'admin', 'Administrator'),
(2, 'members', 'General User');


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
