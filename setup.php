<?php
/*
 *  */

 // ----------------------------------------------------------------------
 // Original Author of file: Olivier Moron
 // Purpose of file: provides Lock/Unlock to GLPI items
 // ----------------------------------------------------------------------

function plugin_init_lock() {
   global $PLUGIN_HOOKS;

   // Enable CSRF compliance for the plugin
   $PLUGIN_HOOKS['csrf_compliant']['lock'] = true;

   // Register plugin classes using updated method for GLPI 10
   Plugin::registerClass('PluginLockLock', ['classname' => 'PluginLockLock']);

   // Register hooks with GLPI 10
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

   // Register post-initialization hook
   $PLUGIN_HOOKS['post_init']['lock'] = 'plugin_lock_postinit';
}

// Get the name and version of the plugin - Needed
function plugin_version_lock() {
   return [
      'name' => "Lock",
      'version' => "3.3.0",
      'license' => "GPLv2+",
      'author' => "Olivier Moron",
      'minGlpiVersion' => "10.0.0", // Update to GLPI 10 minimum version
   ];
}

// Optional: Check prerequisites before installation: may print errors or add to message after redirect
function plugin_lock_check_prerequisites() {
   // Check GLPI version compatibility for GLPI 10
   if (version_compare(GLPI_VERSION, '10.0.0', '<') ) {
      echo "This plugin requires GLPI >= 10.0.0";
      return false;
   }

   // Check if mhooks plugin is activated and has the required version
   $plug = new Plugin();
   if (!$plug->isActivated('mhooks') || version_compare($plug->fields['version'], '2.0.0', '<')) {
      echo "'mhooks 2.0.0' plugin is needed to run 'lock' plugin, please add it to your GLPI plugin configuration.";
      return false;
   }

   return true;
}

// Check configuration process for the plugin: needs to return true if succeeded
// Can display a message only if failure and $verbose is true
function plugin_lock_check_config($verbose = false) {
   global $LANG;

   $plug = new Plugin();
   if ($plug->isActivated('mhooks') && version_compare($plug->fields['version'], '2.0.0', '>=')) {
      return true;
   }

   if ($verbose) {
      echo "'mhooks 2.0.0' plugin is needed to run the 'lock' plugin, please add it to your GLPI plugin configuration.";
   }

   return false;
}
?>
