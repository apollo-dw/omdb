-- MySQL dump 10.13  Distrib 8.0.32, for Linux (x86_64)
--
-- Host: localhost    Database: omdb
-- ------------------------------------------------------
-- Server version	8.0.32-0ubuntu0.20.04.2

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

DROP TABLE IF EXISTS `apikeys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `apikeys` (
  `ApiID` int NOT NULL AUTO_INCREMENT,
  `Name` text,
  `ApiKey` text,
  `UserID` int DEFAULT NULL,
  PRIMARY KEY (`ApiID`),
  UNIQUE KEY `ApiKey` (`ApiKey`(255))
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `beatmaps`
--

DROP TABLE IF EXISTS `beatmaps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `beatmaps` (
  `BeatmapID` mediumint unsigned NOT NULL,
  `SetID` mediumint unsigned DEFAULT NULL,
  `CreatorID` int unsigned NOT NULL DEFAULT '0',
  `SetCreatorID` int DEFAULT NULL,
  `DifficultyName` varchar(80) COLLATE utf8mb3_bin NOT NULL DEFAULT '',
  `Mode` tinyint unsigned NOT NULL DEFAULT '0',
  `Status` tinyint NOT NULL DEFAULT '0',
  `SR` float NOT NULL DEFAULT '0',
  `Rating` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `ChartRank` int DEFAULT NULL,
  `ChartYearRank` int DEFAULT NULL,
  `Timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `RatingCount` int DEFAULT NULL,
  `WeightedAvg` float DEFAULT NULL,
  `Genre` int DEFAULT NULL,
  `Lang` int DEFAULT NULL,
  `Artist` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `Title` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `DateRanked` timestamp NULL DEFAULT NULL,
  `Blacklisted` tinyint(1) NOT NULL DEFAULT '0',
  `BlacklistReason` text COLLATE utf8mb3_bin,
  PRIMARY KEY (`BeatmapID`),
  KEY `beatmapset_id` (`SetID`),
  FULLTEXT KEY `Artist` (`Artist`,`Title`,`DifficultyName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blacklist`
--

DROP TABLE IF EXISTS `blacklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blacklist` (
  `UserID` int NOT NULL,
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `CommentID` int NOT NULL AUTO_INCREMENT,
  `UserID` int NOT NULL,
  `SetID` int NOT NULL,
  `Comment` text,
  `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CommentID`)
) ENGINE=InnoDB AUTO_INCREMENT=10489 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mappernames`
--

DROP TABLE IF EXISTS `mappernames`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mappernames` (
  `UserID` int DEFAULT NULL,
  `Username` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ratings`
--

DROP TABLE IF EXISTS `ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ratings` (
  `RatingID` int NOT NULL AUTO_INCREMENT,
  `BeatmapID` int NOT NULL,
  `UserID` int NOT NULL,
  `Score` decimal(2,1) DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`RatingID`),
  KEY `idx_beatmapID` (`BeatmapID`)
) ENGINE=InnoDB AUTO_INCREMENT=100307 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `setretrieveinfo`
--

DROP TABLE IF EXISTS `setretrieveinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `setretrieveinfo` (
  `LastRetrieval` datetime DEFAULT NULL,
  `LastDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
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
  PRIMARY KEY (`UserID`)
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

-- Dump completed on 2023-02-26 20:34:21
