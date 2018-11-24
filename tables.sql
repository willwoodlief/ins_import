-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 24, 2018 at 04:56 PM
-- Server version: 5.7.24-0ubuntu0.16.04.1
-- PHP Version: 7.0.32-0ubuntu0.16.04.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `ins_import`
--

-- --------------------------------------------------------

--
-- Table structure for table `exception_logs`
--

CREATE TABLE `exception_logs` (
                                `id` int(11) NOT NULL,
                                `parent_id` int(11) DEFAULT NULL,
                                `page_load_id` int(11) DEFAULT NULL COMMENT 'gokabam_api_page_loads',
                                `created_at_ts` int(11) NOT NULL,
                                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                                `user_id` int(11) DEFAULT NULL,
                                `user_roles` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                `hostname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                `machine_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                `caller_ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                `branch` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                `last_commit_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                `is_commit_modified` int(11) NOT NULL,
                                `argv` text COLLATE utf8mb4_unicode_ci,
                                `request_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                `post_super` mediumtext COLLATE utf8mb4_unicode_ci,
                                `get_super` mediumtext COLLATE utf8mb4_unicode_ci,
                                `cookies_super` mediumtext COLLATE utf8mb4_unicode_ci,
                                `server_super` mediumtext COLLATE utf8mb4_unicode_ci,
                                `message` mediumtext COLLATE utf8mb4_unicode_ci,
                                `class_of_exception` mediumtext COLLATE utf8mb4_unicode_ci,
                                `code_of_exception` mediumtext COLLATE utf8mb4_unicode_ci,
                                `file_name` mediumtext COLLATE utf8mb4_unicode_ci,
                                `line` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                `trace` mediumtext COLLATE utf8mb4_unicode_ci,
                                `class` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                `function_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                `trace_as_string` mediumtext COLLATE utf8mb4_unicode_ci,
                                `chained` mediumtext COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exported_user_data`
--

CREATE TABLE `exported_user_data` (
                                    `id` int(11) NOT NULL,
                                    `raw_user_data_id` int(11) NOT NULL,
                                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                    `member_id` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                    `UniqueID` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                    `Agent` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                    `Corpid` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                    `source` text COLLATE utf8mb4_unicode_ci,
                                    `sourcedetail` text COLLATE utf8mb4_unicode_ci,
                                    `Firstname` text COLLATE utf8mb4_unicode_ci,
                                    `Middlename` text COLLATE utf8mb4_unicode_ci,
                                    `Lastname` text COLLATE utf8mb4_unicode_ci,
                                    `Gender` text COLLATE utf8mb4_unicode_ci,
                                    `DOB` text COLLATE utf8mb4_unicode_ci,
                                    `Email` text COLLATE utf8mb4_unicode_ci,
                                    `Phone1` text COLLATE utf8mb4_unicode_ci,
                                    `Phone2` text COLLATE utf8mb4_unicode_ci,
                                    `ADDRESS1` text COLLATE utf8mb4_unicode_ci,
                                    `ADDRESS2` text COLLATE utf8mb4_unicode_ci,
                                    `CITY` text COLLATE utf8mb4_unicode_ci,
                                    `STATE` text COLLATE utf8mb4_unicode_ci,
                                    `ZIPCODE` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='some table columns will be same name and case as api call to make things easier';

-- --------------------------------------------------------

--
-- Table structure for table `export_log`
--

CREATE TABLE `export_log` (
                            `id` int(11) NOT NULL,
                            `exported_data_id` int(11) DEFAULT NULL,
                            `error_log_id` int(11) DEFAULT NULL,
                            `http_response_code` int(11) DEFAULT NULL,
                            `is_success` int(11) NOT NULL DEFAULT '0',
                            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            `json_response` text COLLATE utf8mb4_unicode_ci,
                            `notes` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `exception_logs`
--
ALTER TABLE `exception_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_at_ts_key` (`created_at_ts`);

--
-- Indexes for table `exported_user_data`
--
ALTER TABLE `exported_user_data`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `raw_user_data_id` (`raw_user_data_id`),
  ADD UNIQUE KEY `member_id` (`member_id`),
  ADD UNIQUE KEY `UniqueID` (`UniqueID`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `updated_at` (`updated_at`),
  ADD KEY `Agent` (`Agent`) USING BTREE;

--
-- Indexes for table `export_log`
--
ALTER TABLE `export_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `error_log_id` (`error_log_id`),
  ADD KEY `exported_data_id` (`exported_data_id`),
  ADD KEY `http_response_code` (`http_response_code`),
  ADD KEY `is_success` (`is_success`),
  ADD KEY `created_at` (`created_at`);

