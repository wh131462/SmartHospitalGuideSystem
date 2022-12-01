# SmartHospitalGuideSystem
 Smart hospital guide system is a 2d map guide.
 智慧医院电子导览系统，个人毕设项目存档。


My Sql Structure:
```
CREATE DATABASE `SmartHospital` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;
use SmartHospital;
CREATE TABLE `builder`(
  `bid` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `description` text,
  `level` int(3) NOT NULL,
  `maps` text,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`bid`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `room` (
  `rid` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `name`  varchar(30) NOT NULL,
  `description` text,
  `bid` int(12) NOT NULL,
  `builderName` varchar(20),
  `level` int(3) NOT NULL,
  `position` varchar(30) NOT NULL,
  `location` varchar(40),
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rid`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `line` (
  `lid` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `bid` int(12) NOT NULL,
  `level` int(3) NOT NULL,
  `startPoint` varchar(30) NOT NULL,
  `endPoint` varchar(30) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`lid`)
) DEFAULT CHARSET=utf8;
```
select * from (select * from room where `level`=1) where `bid` = '3';
