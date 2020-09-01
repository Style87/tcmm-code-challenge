CREATE TABLE `todo`.`note_tag_lkp` (
  `note_id` INT NOT NULL,
  `tag_id` INT NOT NULL,
  PRIMARY KEY (`note_id`, `tag_id`),
  INDEX `note_tag_lkp_tag_id_idx` (`tag_id` ASC),
  CONSTRAINT `note_tag_lkp_note_id`
    FOREIGN KEY (`note_id`)
    REFERENCES `todo`.`notes` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `note_tag_lkp_tag_id`
    FOREIGN KEY (`tag_id`)
    REFERENCES `todo`.`tags` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION);
