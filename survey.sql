CREATE TABLE `_survey` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `customer` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` bit(1) NOT NULL DEFAULT b'1',
  `deadline` date default null,
  PRIMARY KEY (`id`)
);

CREATE TABLE `_survey_question` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `survey` bigint(20) NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `question_survay` (`survey`),
  CONSTRAINT `question_survay` FOREIGN KEY (`survey`) REFERENCES `_survey` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `_survey_answer` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `question` bigint(20) NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `answer_question` (`question`),
  CONSTRAINT `answer_question` FOREIGN KEY (`question`) REFERENCES `_survey_question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `_survey_answer_choosen` (
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `answer` bigint(20) NOT NULL,
  `date_choosen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `answer_choosen` FOREIGN KEY (`answer`) REFERENCES `_survey_answer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  PRIMARY KEY (`email`,`answer`)
);


