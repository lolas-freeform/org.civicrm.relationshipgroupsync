DROP TABLE IF EXISTS `relationshipgroupsync_config`;

-- /*******************************************************
-- *
-- * relationshipgroupsync_config
-- *
-- * A group sync config entry.
-- *******************************************************/
CREATE TABLE `relationshipgroupsync_config` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Group Sync Config ID',
  `contact_type` varchar(255) NOT NULL COMMENT 'Contact Type.',
  `contact_sub_type` varchar(255) NOT NULL COMMENT 'Contact Sub Type.',
  `relationship_type_id` INT NOT NULL COMMENT 'Relationship Type ID',
  `relationship_direction` varchar(4) NOT NULL 'Relationship Direction',
  `group_type` varchar(64) NOT NULL COMMENT 'Group Type string with value separator',
  `description` varchar(255) DEFAULT '' COMMENT 'Group description',
  `domain_id` INT NOT NULL COMMENT 'Domain ID',
  PRIMARY KEY (`id`),
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;