-- phpMyAdmin SQL Dump
-- version 3.3.2deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 24, 2010 at 07:30 PM
-- Server version: 5.1.41
-- PHP Version: 5.3.2-1ubuntu4.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `plants`
--

-- --------------------------------------------------------

--
-- Table structure for table `geolocations`
--

CREATE TABLE `geolocations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `resource_id` int(10) unsigned NOT NULL,
  `geolocation_service_id` int(10) unsigned NOT NULL,
  `latitude` text COLLATE utf8_unicode_ci,
  `longitude` text COLLATE utf8_unicode_ci,
  `request_uri` text COLLATE utf8_unicode_ci NOT NULL,
  `response` text COLLATE utf8_unicode_ci,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `inserted` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `resource_id` (`resource_id`,`geolocation_service_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `geolocation_services`
--

CREATE TABLE `geolocation_services` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `class_suffix` text COLLATE utf8_unicode_ci NOT NULL,
  `rank` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `geolocation_services`
--

INSERT INTO `geolocation_services` (`id`, `name`, `class_suffix`, `rank`) VALUES
(1, 'OpenStreetMap Nominatim', 'Nominatim', 3),
(2, 'Yahoo! PlaceFinder', 'PlaceFinder', 1),
(3, 'GeoNames', 'GeoNames', 2);

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `doi` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `collection_year` text COLLATE utf8_unicode_ci,
  `collection` text COLLATE utf8_unicode_ci,
  `collection_altitude` text COLLATE utf8_unicode_ci,
  `collection_date` text COLLATE utf8_unicode_ci,
  `collector` text COLLATE utf8_unicode_ci,
  `country` text COLLATE utf8_unicode_ci,
  `data_last_modified` text COLLATE utf8_unicode_ci,
  `herbarium` text COLLATE utf8_unicode_ci,
  `identifications` text COLLATE utf8_unicode_ci,
  `locality` text COLLATE utf8_unicode_ci,
  `notes` text COLLATE utf8_unicode_ci,
  `resource_type` text COLLATE utf8_unicode_ci,
  `title` text COLLATE utf8_unicode_ci,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `inserted` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `doi` (`doi`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `searches`
--

CREATE TABLE `searches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `jstor_url` text COLLATE utf8_unicode_ci NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `inserted` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `searches_resources`
--

CREATE TABLE `searches_resources` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `search_id` int(10) unsigned NOT NULL,
  `resource_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `search_id` (`search_id`,`resource_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
