<?php
namespace AIOSEO\BrokenLinkChecker\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class contains pre-updates necessary for the main Updates class to run.
 *
 * @since 1.0.0
 */
class PreUpdates {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		$lastActiveVersion = aioseoBrokenLinkChecker()->internalOptions->internal->lastActiveVersion;
		if ( version_compare( $lastActiveVersion, '1.0.0', '<' ) ) {
			$this->createCacheTable();
		}

		// This should be executed AFTER the cache table is created.
		if ( aioseoBrokenLinkChecker()->version !== $lastActiveVersion ) {
			// Bust the table/columns cache so that we can start the update migrations with a fresh slate.
			aioseoBrokenLinkChecker()->core->cache->delete( 'db_schema' );
		}
	}

	/**
	 * Creates the cache table.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function createCacheTable() {
		$db             = aioseoBrokenLinkChecker()->core->db->db;
		$charsetCollate = '';

		if ( ! empty( $db->charset ) ) {
			$charsetCollate .= "DEFAULT CHARACTER SET {$db->charset}";
		}
		if ( ! empty( $db->collate ) ) {
			$charsetCollate .= " COLLATE {$db->collate}";
		}

		// Check if the cache table exists with SQL. We don't want to use our own helper method here because
		// it relies on the cache table being created.
		$result = $db->get_var( "SHOW TABLES LIKE '{$db->prefix}aioseo_blc_cache'" );
		if ( empty( $result ) ) {
			$tableName = $db->prefix . 'aioseo_blc_cache';

			aioseoBrokenLinkChecker()->core->db->execute(
				"CREATE TABLE {$tableName} (
					`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					`key` varchar(80) NOT NULL,
					`value` longtext NOT NULL,
					`expiration` datetime NULL,
					`created` datetime NOT NULL,
					`updated` datetime NOT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY ndx_aioseo_blc_cache_key (`key`),
					KEY ndx_aioseo_blc_cache_expiration (`expiration`)
				) {$charsetCollate};"
			);
		}
	}
}