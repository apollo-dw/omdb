-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 06, 2026 at 04:28 PM
-- Server version: 8.4.3
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `omdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `apikeys`
--

CREATE TABLE `apikeys` (
  `ApiID` int NOT NULL,
  `Name` text,
  `ApiKey` text,
  `UserID` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `beatmaps`
--

CREATE TABLE `beatmaps` (
  `BeatmapID` mediumint UNSIGNED NOT NULL,
  `SetID` mediumint UNSIGNED DEFAULT NULL,
  `DifficultyName` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `Mode` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `Status` tinyint NOT NULL DEFAULT '0',
  `SR` float NOT NULL DEFAULT '0',
  `Rating` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `ChartRank` int DEFAULT NULL,
  `ChartYearRank` int DEFAULT NULL,
  `Timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `RatingCount` int DEFAULT NULL,
  `WeightedAvg` float DEFAULT NULL,
  `Blacklisted` tinyint(1) NOT NULL DEFAULT '0',
  `BlacklistReason` text CHARACTER SET utf8mb3 COLLATE utf8mb3_bin,
  `controversy` decimal(10,8) DEFAULT NULL,
  `ApproachRate` decimal(4,2) DEFAULT NULL,
  `CircleSize` decimal(4,2) DEFAULT NULL,
  `Drain` decimal(4,2) DEFAULT NULL,
  `OverallDifficulty` decimal(4,2) DEFAULT NULL,
  `CircleCount` int DEFAULT NULL,
  `SpinnerCount` int DEFAULT NULL,
  `SliderCount` int DEFAULT NULL,
  `PlayTime` int DEFAULT NULL,
  `LazerOnly` tinyint(1) DEFAULT NULL,
  `Bpm` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `beatmapsets`
--

CREATE TABLE `beatmapsets` (
  `SetID` mediumint UNSIGNED NOT NULL,
  `CreatorID` int DEFAULT NULL,
  `Status` tinyint NOT NULL DEFAULT '0',
  `Timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `Genre` int DEFAULT NULL,
  `Lang` int DEFAULT NULL,
  `Artist` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `Title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `DateRanked` timestamp NULL DEFAULT NULL,
  `HasStoryboard` tinyint(1) DEFAULT '0',
  `HasVideo` tinyint(1) DEFAULT '0',
  `CreatorName` varchar(50) DEFAULT NULL,
  `IsNSFW` tinyint(1) DEFAULT NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `beatmapset_credits`
--

CREATE TABLE `beatmapset_credits` (
  `AssignmentID` int NOT NULL,
  `SetID` int DEFAULT NULL,
  `MapID` int DEFAULT NULL,
  `RoleID` int NOT NULL,
  `UserID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `beatmapset_nominators`
--

CREATE TABLE `beatmapset_nominators` (
  `SetID` int DEFAULT NULL,
  `NominatorID` int DEFAULT NULL,
  `Mode` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `beatmap_creators`
--

CREATE TABLE `beatmap_creators` (
  `BeatmapID` int NOT NULL,
  `CreatorID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `beatmap_descriptors`
--

CREATE TABLE `beatmap_descriptors` (
  `id` int UNSIGNED NOT NULL,
  `BeatmapID` varchar(255) NOT NULL,
  `DescriptorID` varchar(255) NOT NULL,
  `Weight` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `beatmap_edit_requests`
--

CREATE TABLE `beatmap_edit_requests` (
  `EditID` int NOT NULL,
  `BeatmapID` int DEFAULT NULL,
  `SetID` int DEFAULT NULL,
  `UserID` int NOT NULL,
  `EditData` json NOT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Status` enum('Pending','Denied','Approved') DEFAULT 'Pending',
  `EditorID` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `beatmap_roles`
--

CREATE TABLE `beatmap_roles` (
  `RoleID` int NOT NULL,
  `Name` varchar(50) NOT NULL,
  `ShortDescription` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blacklist`
--

CREATE TABLE `blacklist` (
  `UserID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_home_best_map`
--

CREATE TABLE `cache_home_best_map` (
  `BeatmapID` int NOT NULL,
  `Mode` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_home_recent_maps`
--

CREATE TABLE `cache_home_recent_maps` (
  `SetID` int NOT NULL,
  `Timestamp` timestamp NOT NULL,
  `Metadata` varchar(255) NOT NULL,
  `CreatorID` int NOT NULL,
  `Mode` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `CommentID` int NOT NULL,
  `UserID` int NOT NULL,
  `SetID` int NOT NULL,
  `Comment` text,
  `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `descriptors`
--

CREATE TABLE `descriptors` (
  `DescriptorID` int NOT NULL,
  `Name` varchar(40) NOT NULL,
  `ShortDescription` text,
  `ParentID` int DEFAULT NULL,
  `Usable` tinyint(1) NOT NULL DEFAULT '1',
  `LongDescription` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `descriptor_proposals`
--

CREATE TABLE `descriptor_proposals` (
  `ProposalID` int NOT NULL,
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
  `LongDescription` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `descriptor_proposal_comments`
--

CREATE TABLE `descriptor_proposal_comments` (
  `CommentID` int NOT NULL,
  `UserID` int NOT NULL,
  `ProposalID` int NOT NULL,
  `Comment` text NOT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `descriptor_proposal_votes`
--

CREATE TABLE `descriptor_proposal_votes` (
  `VoteID` int NOT NULL,
  `UserID` int NOT NULL,
  `Vote` enum('yes','no','hold') NOT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ProposalID` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `descriptor_votes`
--

CREATE TABLE `descriptor_votes` (
  `VoteID` int NOT NULL,
  `BeatmapID` int NOT NULL,
  `UserID` int NOT NULL,
  `Vote` tinyint(1) NOT NULL,
  `DescriptorID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forum_posts`
--

CREATE TABLE `forum_posts` (
  `PostID` int NOT NULL,
  `ThreadID` int NOT NULL,
  `UserID` int NOT NULL,
  `Content` text,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forum_threads`
--

CREATE TABLE `forum_threads` (
  `ThreadID` int NOT NULL,
  `Title` varchar(255) NOT NULL,
  `TopicID` int NOT NULL,
  `UserID` int NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forum_topics`
--

CREATE TABLE `forum_topics` (
  `TopicID` int NOT NULL,
  `Name` varchar(255) DEFAULT NULL,
  `Description` text,
  `ParentID` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lists`
--

CREATE TABLE `lists` (
  `ListID` int NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Description` text,
  `UserID` int NOT NULL,
  `Private` tinyint(1) NOT NULL DEFAULT '0',
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `list_hearts`
--

CREATE TABLE `list_hearts` (
  `HeartID` int NOT NULL,
  `ListID` int NOT NULL,
  `UserID` int NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `list_items`
--

CREATE TABLE `list_items` (
  `ItemID` int NOT NULL,
  `ListID` int NOT NULL,
  `Type` enum('person','beatmap','beatmapset') NOT NULL,
  `SubjectID` int NOT NULL,
  `Description` text,
  `order` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `LogID` int NOT NULL,
  `UserID` int NOT NULL,
  `LogData` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mappernames`
--

CREATE TABLE `mappernames` (
  `UserID` int NOT NULL,
  `Username` varchar(255) DEFAULT NULL,
  `Country` char(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `RatingID` int NOT NULL,
  `BeatmapID` int NOT NULL,
  `UserID` int NOT NULL,
  `Score` decimal(2,1) DEFAULT NULL,
  `date` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rating_tags`
--

CREATE TABLE `rating_tags` (
  `UserID` int DEFAULT NULL,
  `BeatmapID` int DEFAULT NULL,
  `Tag` varchar(150) DEFAULT NULL,
  `TagID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `ReviewID` int NOT NULL,
  `UserID` int NOT NULL,
  `SetID` varchar(32) NOT NULL,
  `Comment` text,
  `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_hearts`
--

CREATE TABLE `review_hearts` (
  `HeartID` int NOT NULL,
  `ReviewID` int NOT NULL,
  `UserID` int NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `setretrieveinfo`
--

CREATE TABLE `setretrieveinfo` (
  `LastRetrieval` datetime DEFAULT NULL,
  `LastDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int NOT NULL,
  `Username` varchar(255) DEFAULT NULL,
  `AccessToken` varchar(2000) DEFAULT NULL,
  `RefreshToken` varchar(2000) DEFAULT NULL,
  `TokenExpiresAt` DATETIME DEFAULT NULL,
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
  `moderator` tinyint(1) DEFAULT '0',
  `IsPatron` tinyint(1) NOT NULL DEFAULT '0',
  `PatronFromDate` datetime DEFAULT NULL,
  `PatronToDate` datetime DEFAULT NULL,
  `TotalPatronMonths` int NOT NULL DEFAULT '0',
  `ProfileTheme` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_correlations`
--

CREATE TABLE `user_correlations` (
  `user1_id` int DEFAULT NULL,
  `user2_id` int DEFAULT NULL,
  `correlation` float DEFAULT NULL,
  `count` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_relations`
--

CREATE TABLE `user_relations` (
  `UserIDFrom` int DEFAULT NULL,
  `UserIDTo` int DEFAULT NULL,
  `type` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `apikeys`
--
ALTER TABLE `apikeys`
  ADD PRIMARY KEY (`ApiID`),
  ADD UNIQUE KEY `ApiKey` (`ApiKey`(255));

--
-- Indexes for table `beatmaps`
--
ALTER TABLE `beatmaps`
  ADD PRIMARY KEY (`BeatmapID`),
  ADD KEY `beatmapset_id` (`SetID`),
  ADD KEY `idx_Mode` (`Mode`),
  ADD KEY `blacklisted_index` (`Blacklisted`);
ALTER TABLE `beatmaps` ADD FULLTEXT KEY `Artist` (`DifficultyName`);

--
-- Indexes for table `beatmapsets`
--
ALTER TABLE `beatmapsets`
  ADD PRIMARY KEY (`SetID`);

--
-- Indexes for table `beatmapset_credits`
--
ALTER TABLE `beatmapset_credits`
  ADD PRIMARY KEY (`AssignmentID`),
  ADD KEY `idx_beatmapsetid` (`MapID`);

--
-- Indexes for table `beatmapset_nominators`
--
ALTER TABLE `beatmapset_nominators`
  ADD UNIQUE KEY `beatmapset_nominators_pk` (`SetID`,`NominatorID`,`Mode`),
  ADD KEY `beatmapset_nominators_SetID_index` (`SetID`),
  ADD KEY `idx_nominatorid` (`NominatorID`);

--
-- Indexes for table `beatmap_creators`
--
ALTER TABLE `beatmap_creators`
  ADD PRIMARY KEY (`BeatmapID`,`CreatorID`),
  ADD KEY `idx_BeatmapID` (`BeatmapID`),
  ADD KEY `idx_creatorid` (`CreatorID`);

--
-- Indexes for table `beatmap_descriptors`
--
ALTER TABLE `beatmap_descriptors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_beatmap_descriptor` (`BeatmapID`,`DescriptorID`),
  ADD KEY `idx_bd_beatmap_weight` (`BeatmapID`,`Weight` DESC);

--
-- Indexes for table `beatmap_edit_requests`
--
ALTER TABLE `beatmap_edit_requests`
  ADD PRIMARY KEY (`EditID`);

--
-- Indexes for table `beatmap_roles`
--
ALTER TABLE `beatmap_roles`
  ADD PRIMARY KEY (`RoleID`);

--
-- Indexes for table `blacklist`
--
ALTER TABLE `blacklist`
  ADD PRIMARY KEY (`UserID`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`CommentID`);

--
-- Indexes for table `descriptors`
--
ALTER TABLE `descriptors`
  ADD PRIMARY KEY (`DescriptorID`),
  ADD UNIQUE KEY `descriptors_pk2` (`Name`);

--
-- Indexes for table `descriptor_proposals`
--
ALTER TABLE `descriptor_proposals`
  ADD PRIMARY KEY (`ProposalID`);

--
-- Indexes for table `descriptor_proposal_comments`
--
ALTER TABLE `descriptor_proposal_comments`
  ADD PRIMARY KEY (`CommentID`);

--
-- Indexes for table `descriptor_proposal_votes`
--
ALTER TABLE `descriptor_proposal_votes`
  ADD PRIMARY KEY (`VoteID`);

--
-- Indexes for table `descriptor_votes`
--
ALTER TABLE `descriptor_votes`
  ADD PRIMARY KEY (`VoteID`),
  ADD UNIQUE KEY `descriptor_votes_pk2` (`BeatmapID`,`UserID`,`DescriptorID`),
  ADD KEY `descriptor_votes_BeatmapID_index` (`BeatmapID`);

--
-- Indexes for table `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD PRIMARY KEY (`PostID`);

--
-- Indexes for table `forum_threads`
--
ALTER TABLE `forum_threads`
  ADD PRIMARY KEY (`ThreadID`);

--
-- Indexes for table `forum_topics`
--
ALTER TABLE `forum_topics`
  ADD PRIMARY KEY (`TopicID`);

--
-- Indexes for table `lists`
--
ALTER TABLE `lists`
  ADD PRIMARY KEY (`ListID`);
ALTER TABLE `lists` ADD FULLTEXT KEY `Title` (`Title`);

--
-- Indexes for table `list_hearts`
--
ALTER TABLE `list_hearts`
  ADD PRIMARY KEY (`HeartID`),
  ADD UNIQUE KEY `list_hearts_pk2` (`ListID`,`UserID`);

--
-- Indexes for table `list_items`
--
ALTER TABLE `list_items`
  ADD PRIMARY KEY (`ItemID`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`LogID`);

--
-- Indexes for table `mappernames`
--
ALTER TABLE `mappernames`
  ADD PRIMARY KEY (`UserID`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`RatingID`),
  ADD KEY `idx_beatmapID` (`BeatmapID`);

--
-- Indexes for table `rating_tags`
--
ALTER TABLE `rating_tags`
  ADD PRIMARY KEY (`TagID`),
  ADD UNIQUE KEY `rating_tags_pk` (`BeatmapID`,`UserID`,`Tag`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`ReviewID`),
  ADD UNIQUE KEY `unique_review` (`UserID`,`SetID`);

--
-- Indexes for table `review_hearts`
--
ALTER TABLE `review_hearts`
  ADD PRIMARY KEY (`HeartID`),
  ADD UNIQUE KEY `review_hearts_pk2` (`ReviewID`,`UserID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD KEY `idx_hideratings` (`HideRatings`),
  ADD KEY `users_HideRatings_index` (`HideRatings`);

--
-- Indexes for table `user_correlations`
--
ALTER TABLE `user_correlations`
  ADD UNIQUE KEY `user_correlations_pk` (`user1_id`,`user2_id`);

--
-- Indexes for table `user_relations`
--
ALTER TABLE `user_relations`
  ADD UNIQUE KEY `user_relations_pk` (`UserIDTo`,`UserIDFrom`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `apikeys`
--
ALTER TABLE `apikeys`
  MODIFY `ApiID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `beatmapset_credits`
--
ALTER TABLE `beatmapset_credits`
  MODIFY `AssignmentID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `beatmap_descriptors`
--
ALTER TABLE `beatmap_descriptors`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `beatmap_edit_requests`
--
ALTER TABLE `beatmap_edit_requests`
  MODIFY `EditID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `beatmap_roles`
--
ALTER TABLE `beatmap_roles`
  MODIFY `RoleID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `CommentID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `descriptors`
--
ALTER TABLE `descriptors`
  MODIFY `DescriptorID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `descriptor_proposals`
--
ALTER TABLE `descriptor_proposals`
  MODIFY `ProposalID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `descriptor_proposal_comments`
--
ALTER TABLE `descriptor_proposal_comments`
  MODIFY `CommentID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `descriptor_proposal_votes`
--
ALTER TABLE `descriptor_proposal_votes`
  MODIFY `VoteID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `descriptor_votes`
--
ALTER TABLE `descriptor_votes`
  MODIFY `VoteID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forum_posts`
--
ALTER TABLE `forum_posts`
  MODIFY `PostID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forum_threads`
--
ALTER TABLE `forum_threads`
  MODIFY `ThreadID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forum_topics`
--
ALTER TABLE `forum_topics`
  MODIFY `TopicID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lists`
--
ALTER TABLE `lists`
  MODIFY `ListID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `list_hearts`
--
ALTER TABLE `list_hearts`
  MODIFY `HeartID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `list_items`
--
ALTER TABLE `list_items`
  MODIFY `ItemID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `LogID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `RatingID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rating_tags`
--
ALTER TABLE `rating_tags`
  MODIFY `TagID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `ReviewID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_hearts`
--
ALTER TABLE `review_hearts`
  MODIFY `HeartID` int NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

CREATE TABLE `sessions` (
  `SessionToken`    VARCHAR(64)  NOT NULL,
  `UserID`          INT          NOT NULL,
  `ExpiresAt`       DATETIME     NOT NULL,
  `LastAccessedAt`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `IpAddress`       VARCHAR(45),
  `DeviceInfo`      VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`SessionToken`),
  INDEX (`UserID`)
);

CREATE TABLE `beatmap_recommendations` (
  `RecommendationID` int unsigned NOT NULL AUTO_INCREMENT,
  `MapID` int NOT NULL,
  `RecMapID` int NOT NULL,
  `RecScore` float DEFAULT NULL,
  `ProcessDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`RecommendationID`),
  KEY `idx_mapid_processdate` (`MapID`,`ProcessDate`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `cache` (
  `Attribute` varchar(64) NOT NULL,
  `Value` varchar(255) NOT NULL,
  PRIMARY KEY (`Attribute`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `news_posts` (
  `NewsID` INT NOT NULL AUTO_INCREMENT,
  `Title` VARCHAR(255) NOT NULL,
  `Content` TEXT NOT NULL,
  `AuthorID` INT NOT NULL,
  `DateCreated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `DateEdited` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`NewsID`),
  KEY `idx_news_date_created` (`DateCreated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `news_hearts` (
  `HeartID` INT NOT NULL AUTO_INCREMENT,
  `NewsID` INT NOT NULL,
  `UserID` INT NOT NULL,
  `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`HeartID`),
  UNIQUE KEY `news_hearts_pk2` (`NewsID`,`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `news_comments` (
  `CommentID` INT NOT NULL AUTO_INCREMENT,
  `UserID` INT NOT NULL,
  `NewsID` INT NOT NULL,
  `Comment` TEXT NOT NULL,
  `Timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CommentID`),
  KEY `idx_news_comments_newsid` (`NewsID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci 