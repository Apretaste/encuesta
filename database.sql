--
-- Table structure for table `_survey`
--

CREATE TABLE IF NOT EXISTS `_survey` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `customer` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `details` varchar(1000) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `deadline` date DEFAULT NULL,
  `value` float NOT NULL COMMENT 'Amount added to the credit when completed',
  `answers` int(11) NOT NULL DEFAULT '0' COMMENT 'Times fully answered',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Table structure for table `_survey_answer`
--

CREATE TABLE IF NOT EXISTS `_survey_answer` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `question` bigint(20) NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `answer_question` (`question`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=16 ;

--
-- Table structure for table `_survey_answer_choosen`
--

CREATE TABLE IF NOT EXISTS `_survey_answer_choosen` (
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `survey` int(11) NOT NULL,
  `question` int(11) NOT NULL,
  `answer` bigint(20) NOT NULL,
  `date_choosen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`email`,`answer`),
  KEY `answer_choosen` (`answer`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `_survey_question`
--

CREATE TABLE IF NOT EXISTS `_survey_question` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `survey` bigint(20) NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `question_survay` (`survey`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

--
-- Constraints for table `_survey_answer`
--
ALTER TABLE `_survey_answer`
  ADD CONSTRAINT `answer_question` FOREIGN KEY (`question`) REFERENCES `_survey_question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `_survey_answer_choosen`
--
ALTER TABLE `_survey_answer_choosen`
  ADD CONSTRAINT `answer_choosen` FOREIGN KEY (`answer`) REFERENCES `_survey_answer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `_survey_question`
--
ALTER TABLE `_survey_question`
  ADD CONSTRAINT `question_survay` FOREIGN KEY (`survey`) REFERENCES `_survey` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
