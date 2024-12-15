<?php
/*
 *  Lock Plugin Setup File
 *  Author: Olivier Moron
 *  Purpose: Provides Lock/Unlock functionality for GLPI items.
 */

// Initialize the plugin
function plugin_init_lock() {
   global $PLUGIN_HOOKS;

   // Enable CSRF compliance
   $PLUGIN_HOOKS['csrf_compliant']['lock'] = true;

   // Register plugin classes using GLPI 10-compatible methods
   Plugin::registerClass('PluginLockLock', ['classname' => 'PluginLockLock']);

   // Register hooks for pre and post item displays
   $PLUGIN_HOOKS['pre_show_item']['lock'] = [
      'Ticket' => ['PluginLockLock', 'pre_show_item_lock'],
      'Computer' => ['PluginLockLock', 'pre_show_item_lock'],
      'Reminder' => ['PluginLockLock', 'pre_show_item_lock']
   ];

   $PLUGIN_HOOKS['post_show_item']['lock'] = [
      'Ticket' => ['PluginLockLock', 'post_show_item_lock'],
      'Computer' => ['PluginLockLock', 'post_show_item_lock'],
      'Reminder' => ['PluginLockLock', 'post_show_item_lock']
   ];

   // Post-initialization hook
   $PLUGIN_HOOKS['post_init']['lock'] = 'plugin_lock_postinit';
}

// Plugin version information
function plugin_version_lock() {
   return [
      'name' => "Lock",
      'version' => "3.3.0",
      'license' => "GPLv2+",
      'author' => "Olivier Moron",
      'homepage' => "https://github.com/tomolimo/lock",
      'minGlpiVersion' => "10.0.0", // GLPI 10 minimum version
      'requirements' => [
         'glpi' => ['>=10.0.0'],
         'mhooks' => ['>=2.0.0'] // mhooks plugin requirement
      ]
   ];
}

// Prerequisites check
function plugin_lock_check_prerequisites() {
   global $LANG;

   // Ensure GLPI version compatibility
   if (version_compare(GLPI_VERSION, '10.0.0', '<')) {
      echo $LANG['lock']['errors']['version'] ?? "This plugin requires GLPI >= 10.0.0.";
      return false;
   }

   // Check if mhooks plugin is installed and activated
   $plug = new Plugin();
   if (!$plug->isInstalled('mhooks') || !$plug->isActivated('mhooks')) {
      echo $LANG['lock']['errors']['mhooks_missing'] ?? "'mhooks 2.0.0' plugin is required to run 'lock' plugin. Please install and activate it.";
      return false;
   }

   // Ensure mhooks plugin has the required version
   if (version_compare($plug->fields['version'], '2.0.0', '<')) {
      echo $LANG['lock']['errors']['mhooks_version'] ?? "'mhooks' plugin version 2.0.0 or higher is required.";
      return false;
   }

   return true;
}

// Configuration check
function plugin_lock_check_config($verbose = false) {
   global $LANG;

   $plug = new Plugin();
   if ($plug->isActivated('mhooks') && version_compare($plug->fields['version'], '2.0.0', '>=')) {
      return true;
   }

   if ($verbose) {
      echo $LANG['lock']['errors']['config'] ?? "'mhooks 2.0.0' plugin is required to run the 'lock' plugin.";
   }

   return false;
}
