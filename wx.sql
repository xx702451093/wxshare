/*
Navicat MySQL Data Transfer

Source Server         : mysql
Source Server Version : 50709
Source Host           : 127.0.0.1:3306
Source Database       : wx

Target Server Type    : MYSQL
Target Server Version : 50709
File Encoding         : 65001

Date: 2017-11-16 18:25:49
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for iteminfos
-- ----------------------------
DROP TABLE IF EXISTS `iteminfos`;
CREATE TABLE `iteminfos` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `author` varchar(50) DEFAULT NULL,
  `digest` varchar(100) DEFAULT NULL,
  `content` text,
  `url` varchar(225) NOT NULL,
  `addTime` int(11) NOT NULL,
  `media_id` varchar(100) NOT NULL,
  `content_source_url` varchar(150) NOT NULL DEFAULT '查看原文 链接',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  `thumb_media_id` varchar(100) NOT NULL,
  `show_cover_pic` tinyint(1) NOT NULL,
  `thumb_url` varchar(225) NOT NULL,
  `need_open_comment` tinyint(1) DEFAULT NULL,
  `only_fans_can_comment` tinyint(1) DEFAULT NULL,
  `ticket` varchar(200) DEFAULT NULL,
  `qrcodeExpire` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for param
-- ----------------------------
DROP TABLE IF EXISTS `param`;
CREATE TABLE `param` (
  `update_time` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
