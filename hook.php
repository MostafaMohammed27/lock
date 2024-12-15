<?php

// ----------------------------------------------------------------------
// Original Author of file: Olivier Moron
// Purpose of file: to provide Lock/unlock to GLPI items
// Updated for GLPI 10 compatibility
// ----------------------------------------------------------------------

// Install process for plugin: need to return true if succeeded
function plugin_lock_install() {
   global $DB;

   // Create the `glpi_plugin_lock_locks` table if it doesn't exist
   if (!TableExists("glpi_plugin_lock_locks")) {
      $query = "CREATE TABLE `glpi_plugin_lock_locks` (
         `id` INT(11) NOT NULL AUTO_INCREMENT,
         `items_id` INT(11) NOT NULL DEFAULT '0' COMMENT 'RELATION to various table, according to itemtype (ID)',
         `itemtype` VARCHAR(100) NOT NULL COLLATE 'utf8mb4_unicode_ci',
         `users_id` INT(11) NOT NULL,
         `lockdate` TIMESTAMP NOT NULL,
         PRIMARY KEY (`id`),
         UNIQUE INDEX `item` (`itemtype`, `items_id`)
         )
         COLLATE='utf8mb4_unicode_ci'
         ENGINE=InnoDB;";

      $DB->queryOrDie($query, "Error creating `glpi_plugin_lock_locks` table: " . $DB->error());
   }

   // Create the `glpi_plugin_lock_configs` table if it doesn't exist
   if (!TableExists("glpi_plugin_lock_configs")) {
      $query = "CREATE TABLE `glpi_plugin_lock_configs` (
         `id` INT(11) NOT NULL AUTO_INCREMENT,
         `read_only_profile_id` INT(11) NOT NULL DEFAULT '0',
         PRIMARY KEY (`id`)
         )
         COLLATE='utf8mb4_unicode_ci'
         ENGINE=InnoDB;";

      $DB->queryOrDie($query, "Error creating `glpi_plugin_lock_configs` table: " . $DB->error());
   }

   // Ensure the "Plugin Lock Read-Only Profile" exists in `glpi_profiles`
   $profile = new Profile();
   $query = "SELECT id FROM `glpi_profiles` WHERE `name` = 'Plugin Lock Read-Only Profile'";
   $result = $DB->query($query);

   if ($DB->numrows($result) === 0) {
      // Create the profile if it doesn't exist
      $profile->add([
         'name' => 'Plugin Lock Read-Only Profile',
         'interface' => 'central',
         'is_default' => 0,
         'comment' => "This profile is used to manage Lock/unlock of items and to give access to the Unlock form. Do not forget to set rights for this profile, otherwise nothing will be viewable!"
      ]);
   } else {
      $row = $DB->fetch_assoc($result);
      $profile_id = $row['id'];
   }

   // Insert the profile ID into `glpi_plugin_lock_configs`
   $query = "REPLACE INTO `glpi_plugin_lock_configs` (`id`, `read_only_profile_id`) VALUES (1, " . $profile_id . ")";
   $DB->queryOrDie($query, "Error inserting profile ID into `glpi_plugin_lock_configs`: " . $DB->error());

   // Register a cron task for unlocking items
   CronTask::register(
      'PluginLockLock',
      'unlock',
      DAY_TIMESTAMP,
      [
         'state' => CronTask::STATE_DISABLE,
         'mode' => CronTask::MODE_EXTERNAL
      ]
   );

   return true;
}

// Uninstall process for plugin: need to return true if succeeded
function plugin_lock_uninstall() {
   global $DB;

   CronTask::unregister('PluginLockLock');

   // Drop tables associated with the plugin
   foreach (["glpi_plugin_lock_locks", "glpi_plugin_lock_configs"] as $table) {
      if (TableExists($table)) {
         $DB->queryOrDie("DROP TABLE `$table`", "Error deleting `$table`");
      }
   }

   return true;
}

// Post-init plugin process
function plugin_lock_postinit() {
   global $DB;

   if (isset($_SESSION['glpiname']) && !isset($_SESSION['glpi_plugin_lock_read_only_profile'])) {
      // Initialize profile data
      $query = "SELECT * FROM `glpi_plugin_lock_configs`";
      $result = $DB->query($query);

      if ($result && $DB->numrows($result) === 1) {
         $row = $DB->fetch_assoc($result);
         $profile = new Profile();

         if ($profile->getFromDB($row['read_only_profile_id'])) {
            $profile->cleanProfile();
            $_SESSION['glpi_plugin_lock_read_only_profile'] = $profile->fields;
            $_SESSION['glpi_plugin_lock_read_only_profile']['entities'] = [
               0 => ['id' => 0, 'name' => '', 'is_recursive' => 1]
            ];
         }
      }
   }
}
