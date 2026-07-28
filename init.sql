-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: omdb
-- ------------------------------------------------------
-- Server version	8.0.46-0ubuntu0.22.04.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `apikeys`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `apikeys` (
  `ApiID` int NOT NULL AUTO_INCREMENT,
  `Name` text,
  `ApiKey` text,
  `UserID` int DEFAULT NULL,
  PRIMARY KEY (`ApiID`),
  UNIQUE KEY `ApiKey` (`ApiKey`(255))
) ENGINE=InnoDB AUTO_INCREMENT=997 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `beatmap_creators`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `beatmap_creators` (
  `BeatmapID` int NOT NULL,
  `CreatorID` int NOT NULL,
  PRIMARY KEY (`BeatmapID`,`CreatorID`),
  KEY `idx_creatorid` (`CreatorID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `beatmap_descriptors`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `beatmap_descriptors` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `BeatmapID` mediumint unsigned NOT NULL,
  `DescriptorID` int NOT NULL,
  `Weight` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_beatmap_descriptor` (`BeatmapID`,`DescriptorID`),
  KEY `idx_bd_beatmap_weight` (`BeatmapID`,`Weight` DESC),
  KEY `idx_bd_descriptor_beatmap` (`DescriptorID`,`BeatmapID`)
) ENGINE=InnoDB AUTO_INCREMENT=131074 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `beatmap_edit_requests`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `beatmap_edit_requests` (
  `EditID` int NOT NULL AUTO_INCREMENT,
  `BeatmapID` int DEFAULT NULL,
  `SetID` int DEFAULT NULL,
  `UserID` int NOT NULL,
  `EditData` json NOT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Status` enum('Pending','Denied','Approved') DEFAULT 'Pending',
  `EditorID` int DEFAULT NULL,
  PRIMARY KEY (`EditID`)
) ENGINE=InnoDB AUTO_INCREMENT=28443 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `beatmap_recommendations`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `beatmap_recommendations` (
  `RecommendationID` int unsigned NOT NULL AUTO_INCREMENT,
  `MapID` int NOT NULL,
  `RecMapID` int NOT NULL,
  `RecScore` float DEFAULT NULL,
  `ProcessDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`RecommendationID`),
  KEY `idx_mapid_processdate` (`MapID`,`ProcessDate`)
) ENGINE=InnoDB AUTO_INCREMENT=218689 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `beatmap_roles`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `beatmap_roles` (
  `RoleID` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(50) NOT NULL,
  `ShortDescription` text,
  PRIMARY KEY (`RoleID`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `beatmaps`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `beatmaps` (
  `BeatmapID` mediumint unsigned NOT NULL,
  `SetID` mediumint unsigned DEFAULT NULL,
  `DifficultyName` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `Mode` tinyint unsigned NOT NULL DEFAULT '0',
  `Status` tinyint NOT NULL DEFAULT '0',
  `SR` float NOT NULL DEFAULT '0',
  `Rating` float DEFAULT NULL,
  `ChartRank` smallint unsigned DEFAULT NULL,
  `ChartYearRank` smallint unsigned DEFAULT NULL,
  `Timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `RatingCount` smallint unsigned DEFAULT NULL,
  `WeightedAvg` float DEFAULT NULL,
  `Blacklisted` tinyint(1) NOT NULL DEFAULT '0',
  `BlacklistReason` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin,
  `controversy` float DEFAULT NULL,
  `ApproachRate` decimal(4,2) DEFAULT NULL,
  `CircleSize` decimal(4,2) DEFAULT NULL,
  `Drain` decimal(4,2) DEFAULT NULL,
  `OverallDifficulty` decimal(4,2) DEFAULT NULL,
  `CircleCount` mediumint unsigned DEFAULT NULL,
  `SpinnerCount` smallint unsigned DEFAULT NULL,
  `SliderCount` mediumint unsigned DEFAULT NULL,
  `PlayTime` smallint unsigned DEFAULT NULL,
  `LazerOnly` tinyint(1) DEFAULT NULL,
  `Bpm` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`BeatmapID`),
  KEY `beatmapset_id` (`SetID`),
  KEY `idx_Mode` (`Mode`),
  KEY `blacklisted_index` (`Blacklisted`),
  KEY `idx_beatmaps_set_mode` (`SetID`,`Mode`),
  KEY `idx_mode_blacklisted` (`Mode`,`Blacklisted`,`BeatmapID`),
  KEY `idx_mode_rating` (`Mode`,`Rating` DESC,`BeatmapID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `beatmapset_credits`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `beatmapset_credits` (
  `AssignmentID` int NOT NULL AUTO_INCREMENT,
  `SetID` int DEFAULT NULL,
  `MapID` int DEFAULT NULL,
  `RoleID` int NOT NULL,
  `UserID` int NOT NULL,
  PRIMARY KEY (`AssignmentID`),
  KEY `idx_beatmapsetid` (`MapID`)
) ENGINE=InnoDB AUTO_INCREMENT=2312 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `beatmapset_nominators`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `beatmapset_nominators` (
  `SetID` mediumint unsigned DEFAULT NULL,
  `NominatorID` int DEFAULT NULL,
  `Mode` tinyint unsigned DEFAULT NULL,
  UNIQUE KEY `beatmapset_nominators_pk` (`SetID`,`NominatorID`,`Mode`),
  KEY `beatmapset_nominators_SetID_index` (`SetID`),
  KEY `idx_nominatorid` (`NominatorID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `beatmapsets`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `beatmapsets` (
  `SetID` mediumint unsigned NOT NULL,
  `CreatorID` int unsigned DEFAULT NULL,
  `Status` tinyint NOT NULL DEFAULT '0',
  `Timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `Genre` tinyint unsigned DEFAULT NULL,
  `Lang` tinyint unsigned DEFAULT NULL,
  `Artist` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `Title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `DateRanked` timestamp NULL DEFAULT NULL,
  `HasStoryboard` tinyint(1) DEFAULT '0',
  `HasVideo` tinyint(1) DEFAULT '0',
  `CreatorName` varchar(50) DEFAULT NULL,
  `IsNSFW` tinyint(1) DEFAULT NULL,
  `SearchText` varchar(2048) DEFAULT NULL,
  `SearchIDs` varchar(2048) DEFAULT NULL,
  `MaxRating` smallint unsigned NOT NULL DEFAULT '0',
  `ModeMask` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`SetID`),
  KEY `idx_status` (`Status`),
  KEY `idx_creatorID` (`CreatorID`),
  KEY `idx_maxrating` (`MaxRating` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blacklist`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blacklist` (
  `UserID` int NOT NULL,
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `Attribute` varchar(64) NOT NULL,
  `Value` varchar(255) NOT NULL,
  PRIMARY KEY (`Attribute`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache_home_best_map`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_home_best_map` (
  `BeatmapID` int NOT NULL,
  `Mode` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache_home_recent_maps`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_home_recent_maps` (
  `SetID` int NOT NULL,
  `Timestamp` timestamp NOT NULL,
  `Metadata` varchar(255) NOT NULL,
  `CreatorID` int NOT NULL,
  `Mode` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comments`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `CommentID` int NOT NULL AUTO_INCREMENT,
  `UserID` int NOT NULL,
  `SetID` int NOT NULL,
  `Comment` text,
  `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CommentID`),
  KEY `idx_comments_set_date` (`SetID`,`date`),
  KEY `idx_comments_user` (`UserID`),
  KEY `idx_date_user_set` (`date`,`UserID`,`SetID`)
) ENGINE=InnoDB AUTO_INCREMENT=69428 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `descriptor_proposal_comments`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `descriptor_proposal_comments` (
  `CommentID` int NOT NULL AUTO_INCREMENT,
  `UserID` int NOT NULL,
  `ProposalID` int NOT NULL,
  `Comment` text NOT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`CommentID`),
  KEY `idx_time_user_proposal` (`Timestamp`,`UserID`,`ProposalID`)
) ENGINE=InnoDB AUTO_INCREMENT=802 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `descriptor_proposal_votes`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `descriptor_proposal_votes` (
  `VoteID` int NOT NULL AUTO_INCREMENT,
  `UserID` int NOT NULL,
  `Vote` enum('yes','no','hold') NOT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ProposalID` int DEFAULT NULL,
  PRIMARY KEY (`VoteID`)
) ENGINE=InnoDB AUTO_INCREMENT=1147 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `descriptor_proposals`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `descriptor_proposals` (
  `ProposalID` int NOT NULL AUTO_INCREMENT,
  `ProposerID` int NOT NULL,
  `DescriptorID` int DEFAULT NULL,
  `Name` varchar(40) NOT NULL,
  `ShortDescription` text NOT NULL,
  `ParentID` int DEFAULT NULL,
  `Usable` tinyint NOT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Type` enum('new','delete','modify') DEFAULT NULL,
  `EditorID` int DEFAULT NULL,
  `Status` enum('pending','approved','denied') NOT NULL DEFAULT 'pending',
  `UpdatedTimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `LongDescription` text,
  PRIMARY KEY (`ProposalID`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `descriptor_votes`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `descriptor_votes` (
  `VoteID` int NOT NULL AUTO_INCREMENT,
  `BeatmapID` int NOT NULL,
  `UserID` int NOT NULL,
  `Vote` tinyint(1) NOT NULL,
  `DescriptorID` int NOT NULL,
  PRIMARY KEY (`VoteID`),
  UNIQUE KEY `descriptor_votes_pk2` (`BeatmapID`,`UserID`,`DescriptorID`),
  KEY `descriptor_votes_BeatmapID_index` (`BeatmapID`)
) ENGINE=InnoDB AUTO_INCREMENT=91448 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `descriptors`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `descriptors` (
  `DescriptorID` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(40) NOT NULL,
  `ShortDescription` text,
  `ParentID` int DEFAULT NULL,
  `Usable` tinyint(1) NOT NULL DEFAULT '1',
  `LongDescription` text,
  PRIMARY KEY (`DescriptorID`),
  UNIQUE KEY `descriptors_pk2` (`Name`)
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forum_posts`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `forum_posts` (
  `PostID` int NOT NULL AUTO_INCREMENT,
  `ThreadID` int NOT NULL,
  `UserID` int NOT NULL,
  `Content` text,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`PostID`)
) ENGINE=InnoDB AUTO_INCREMENT=662 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forum_threads`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `forum_threads` (
  `ThreadID` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) NOT NULL,
  `TopicID` int NOT NULL,
  `UserID` int NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ThreadID`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forum_topics`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `forum_topics` (
  `TopicID` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) DEFAULT NULL,
  `Description` text,
  `ParentID` int DEFAULT NULL,
  PRIMARY KEY (`TopicID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `list_hearts`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `list_hearts` (
  `HeartID` int NOT NULL AUTO_INCREMENT,
  `ListID` int NOT NULL,
  `UserID` int NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`HeartID`),
  UNIQUE KEY `list_hearts_pk2` (`ListID`,`UserID`)
) ENGINE=InnoDB AUTO_INCREMENT=455 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `list_items`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `list_items` (
  `ItemID` int NOT NULL AUTO_INCREMENT,
  `ListID` int NOT NULL,
  `Type` enum('person','beatmap','beatmapset') NOT NULL,
  `SubjectID` int NOT NULL,
  `Description` text,
  `order` int NOT NULL,
  PRIMARY KEY (`ItemID`)
) ENGINE=InnoDB AUTO_INCREMENT=729664 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lists`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lists` (
  `ListID` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) NOT NULL,
  `Description` text,
  `UserID` int NOT NULL,
  `Private` tinyint(1) NOT NULL DEFAULT '0',
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ListID`),
  FULLTEXT KEY `Title` (`Title`)
) ENGINE=InnoDB AUTO_INCREMENT=216 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `logs`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs` (
  `LogID` int NOT NULL AUTO_INCREMENT,
  `UserID` int NOT NULL,
  `LogData` json DEFAULT NULL,
  PRIMARY KEY (`LogID`)
) ENGINE=InnoDB AUTO_INCREMENT=7221 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mappernames`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mappernames` (
  `UserID` int NOT NULL,
  `Username` varchar(255) DEFAULT NULL,
  `Country` char(2) DEFAULT NULL,
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `news_comments`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_comments` (
  `CommentID` int NOT NULL AUTO_INCREMENT,
  `UserID` int NOT NULL,
  `NewsID` int NOT NULL,
  `Comment` text NOT NULL,
  `Timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CommentID`),
  KEY `idx_news_comments_newsid` (`NewsID`),
  KEY `idx_time_user_news` (`Timestamp`,`UserID`,`NewsID`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `news_hearts`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_hearts` (
  `HeartID` int NOT NULL AUTO_INCREMENT,
  `NewsID` int NOT NULL,
  `UserID` int NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`HeartID`),
  UNIQUE KEY `news_hearts_pk2` (`NewsID`,`UserID`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `news_posts`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_posts` (
  `NewsID` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) NOT NULL,
  `Content` text NOT NULL,
  `AuthorID` int NOT NULL,
  `DateCreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `DateEdited` datetime DEFAULT NULL,
  PRIMARY KEY (`NewsID`),
  KEY `idx_news_date_created` (`DateCreated`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rating_tags`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rating_tags` (
  `UserID` int DEFAULT NULL,
  `BeatmapID` int DEFAULT NULL,
  `Tag` varchar(150) DEFAULT NULL,
  `TagID` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`TagID`),
  UNIQUE KEY `rating_tags_pk` (`BeatmapID`,`UserID`,`Tag`)
) ENGINE=InnoDB AUTO_INCREMENT=7807 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ratings`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ratings` (
  `RatingID` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `BeatmapID` mediumint unsigned NOT NULL,
  `UserID` int unsigned NOT NULL,
  `Score` decimal(2,1) DEFAULT NULL,
  `date` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`RatingID`),
  UNIQUE KEY `idx_user_beatmap` (`UserID`,`BeatmapID`),
  KEY `idx_beatmapID` (`BeatmapID`),
  KEY `idx_date_beatmap` (`date`,`BeatmapID`)
) ENGINE=InnoDB AUTO_INCREMENT=458813 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `review_hearts`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_hearts` (
  `HeartID` int NOT NULL AUTO_INCREMENT,
  `ReviewID` int NOT NULL,
  `UserID` int NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`HeartID`),
  UNIQUE KEY `review_hearts_pk2` (`ReviewID`,`UserID`)
) ENGINE=InnoDB AUTO_INCREMENT=161 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reviews`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `ReviewID` int NOT NULL AUTO_INCREMENT,
  `UserID` int NOT NULL,
  `SetID` mediumint unsigned NOT NULL,
  `Comment` text,
  `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ReviewID`),
  UNIQUE KEY `unique_review` (`UserID`,`SetID`),
  KEY `idx_date_user_set` (`date`,`UserID`,`SetID`)
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `SessionToken` varchar(64) NOT NULL,
  `UserID` int NOT NULL,
  `ExpiresAt` datetime NOT NULL,
  `LastAccessedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `IpAddress` varchar(45) DEFAULT NULL,
  `DeviceInfo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`SessionToken`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `setretrieveinfo`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `setretrieveinfo` (
  `LastRetrieval` datetime DEFAULT NULL,
  `LastDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stripe_payments`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stripe_payments` (
  `StripePaymentID` int unsigned NOT NULL AUTO_INCREMENT,
  `StripeEventID` varchar(255) NOT NULL,
  `EventType` varchar(100) NOT NULL,
  `StripeSessionID` varchar(255) DEFAULT NULL,
  `StripePaymentIntentID` varchar(255) DEFAULT NULL,
  `StripeCustomerID` varchar(255) DEFAULT NULL,
  `UserID` int unsigned DEFAULT NULL,
  `AmountTotal` int unsigned DEFAULT NULL,
  `Currency` varchar(10) DEFAULT NULL,
  `PaymentStatus` varchar(50) DEFAULT NULL,
  `Payload` json NOT NULL,
  `ProcessedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`StripePaymentID`),
  UNIQUE KEY `UK_StripeEventID` (`StripeEventID`),
  KEY `IX_StripeSessionID` (`StripeSessionID`),
  KEY `IX_StripePaymentIntentID` (`StripePaymentIntentID`),
  KEY `IX_StripeCustomerID` (`StripeCustomerID`),
  KEY `IX_UserID` (`UserID`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_correlations`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_correlations` (
  `user1_id` int DEFAULT NULL,
  `user2_id` int DEFAULT NULL,
  `correlation` float DEFAULT NULL,
  `count` int DEFAULT '0',
  UNIQUE KEY `user_correlations_pk` (`user1_id`,`user2_id`),
  UNIQUE KEY `idx_user_corr_pair` (`user1_id`,`user2_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_relations`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_relations` (
  `UserIDFrom` int DEFAULT NULL,
  `UserIDTo` int DEFAULT NULL,
  `type` int DEFAULT NULL,
  UNIQUE KEY `user_relations_pk` (`UserIDTo`,`UserIDFrom`),
  KEY `idx_userrelations_from_type_to` (`UserIDFrom`,`type`,`UserIDTo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `UserID` int NOT NULL,
  `Username` varchar(255) DEFAULT NULL,
  `AccessToken` varchar(2000) DEFAULT NULL,
  `RefreshToken` varchar(2000) DEFAULT NULL,
  `banned` tinyint(1) DEFAULT '0',
  `Weight` decimal(6,4) DEFAULT NULL,
  `DoTrueRandom` tinyint(1) NOT NULL DEFAULT '0',
  `Custom00Rating` varchar(60) NOT NULL DEFAULT '',
  `Custom05Rating` varchar(60) NOT NULL DEFAULT '',
  `Custom10Rating` varchar(60) NOT NULL DEFAULT '',
  `Custom15Rating` varchar(60) NOT NULL DEFAULT '',
  `Custom20Rating` varchar(60) NOT NULL DEFAULT '',
  `Custom25Rating` varchar(60) NOT NULL DEFAULT '',
  `Custom30Rating` varchar(60) NOT NULL DEFAULT '',
  `Custom35Rating` varchar(60) NOT NULL DEFAULT '',
  `Custom40Rating` varchar(60) NOT NULL DEFAULT '',
  `Custom45Rating` varchar(60) NOT NULL DEFAULT '',
  `Custom50Rating` varchar(60) NOT NULL DEFAULT '',
  `LastAccessedSite` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `HideRatings` tinyint(1) DEFAULT '0',
  `CustomDescription` text,
  `UserTitle` varchar(50) DEFAULT NULL,
  `IpAddress` varchar(50) DEFAULT NULL,
  `OnlyFriendsOnFrontPage` tinyint(1) DEFAULT '0',
  `moderator` tinyint(1) NOT NULL DEFAULT '0',
  `TokenExpiresAt` datetime DEFAULT NULL,
  `IsPatron` tinyint(1) NOT NULL DEFAULT '0',
  `PatronFromDate` datetime DEFAULT NULL,
  `PatronToDate` datetime DEFAULT NULL,
  `TotalPatronMonths` int NOT NULL DEFAULT '0',
  `ProfileTheme` json DEFAULT NULL,
  PRIMARY KEY (`UserID`),
  KEY `idx_hideratings` (`HideRatings`),
  KEY `users_HideRatings_index` (`HideRatings`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-28 10:03:07
