<?php

namespace Glpi\Plugin\Lock;

use Glpi\CommonDBTM;
use Glpi\Session;
use Glpi\DB;
use Glpi\Plugin;

class Lock extends CommonDBTM {

   // This function is called before displaying an item to check if it's locked
   public static function pre_show_item_lock($item) {
      // Prevent locking if the interface is helpdesk or if item is in creation mode
      if (Session::getActiveProfile()['interface'] == 'helpdesk' || $item->getID() < 0) {
          return;
      }

      // Query to check if the item is locked
      $query = "SELECT glpi_plugin_lock_locks.*, glpi_users.name, glpi_useremails.email 
                FROM glpi_plugin_lock_locks
                LEFT JOIN glpi_users ON glpi_users.id = glpi_plugin_lock_locks.users_id
                LEFT JOIN glpi_useremails ON (glpi_users.id = glpi_useremails.users_id AND glpi_useremails.is_default = 1)
                WHERE glpi_plugin_lock_locks.items_id = ? AND glpi_plugin_lock_locks.itemtype = ?";

      // Execute the query
      $ret = DB::query($query, [$item->getID(), $item->getType()]);

      // Check if the item is locked and display the lock message
      if ($ret && DB::numrows($ret) == 1) {
         $row = DB::fetchAssoc($ret);
         if (!isset($_REQUEST['glpi_tab'])) {
            self::displayLockMessage($item, $row);
         }
      }
   }

   // Function to display the lock message and prevent writing to the locked item
   public static function displayLockMessage($item, $row) {
      global $CFG_GLPI;
      echo "<div class='box' style='margin-bottom:20px;'>";
      echo "<div class='box-tleft'><div class='box-tright'><div class='box-tcenter'></div></div></div>";
      echo "<div class='box-mleft'><div class='box-mright'><div class='box-mcenter'>";
      echo "<h3><span class='red'>" . $item->getType() . " has been locked by '" . $row['name'] . "' since '" . $row['lockdate'] . "'!</span></h3>";
      echo "<h3><span class='red'>To request unlock, click -> <a href=\"mailto:" . $row['email'] . "?subject=Please unlock item: " . $item->getType() . " " . $item->getID() . "&body=Hello,%0A%0ACould you go to this item and unlock it for me?%0A%0A" . $CFG_GLPI['url_base'] . "/?redirect=" . $item->getType() . "_" . $item->getID() . "%0A%0AThank you,%0A%0ARegards,%0A%0A" . $_SESSION['glpifirstname'] . "\">" . $row['name'] . "</a></span></h3>";
      echo "</div></div></div>";
      echo "<div class='box-bleft'><div class='box-bright'><div class='box-bcenter'></div></div></div>";
      echo "</div>";

      // Changes profile to prevent write access to the item
      $_SESSION['glpi_plugin_lock_former_profile'] = $_SESSION['glpiactiveprofile'];
      $_SESSION['glpiactiveprofile'] = $_SESSION['glpi_plugin_lock_read_only_profile'];
   }

   // This function handles locking the item
   public static function lock_item($item) {
      global $DB;
      
      // Prevent locking if already locked
      $query = "SELECT * FROM glpi_plugin_lock_locks WHERE items_id = ? AND itemtype = ?";
      $result = DB::query($query, [$item->getID(), $item->getType()]);

      if (DB::numrows($result) > 0) {
         return; // Item already locked
      }

      // Insert a new lock record
      $lockData = [
         'items_id' => $item->getID(),
         'itemtype' => $item->getType(),
         'users_id' => Session::getActiveUser()['id'],
         'lockdate' => date('Y-m-d H:i:s')
      ];
      DB::insert('glpi_plugin_lock_locks', $lockData);
   }

   // This function handles unlocking the item
   public static function unlock_item($item) {
      global $DB;

      // Remove the lock from the item
      $query = "DELETE FROM glpi_plugin_lock_locks WHERE items_id = ? AND itemtype = ?";
      DB::query($query, [$item->getID(), $item->getType()]);
   }

   // Function to check if the item is locked
   public static function is_item_locked($item) {
      $query = "SELECT * FROM glpi_plugin_lock_locks WHERE items_id = ? AND itemtype = ?";
      $ret = DB::query($query, [$item->getID(), $item->getType()]);
      return DB::numrows($ret) > 0;
   }

   // Function to display the lock status icon in the table view
   public static function display_lock_icon($item) {
      if (self::is_item_locked($item)) {
         echo "<img src='" . $_SESSION['glpi_plugin_lock_lock_icon'] . "' title='Item Locked' />";
      }
   }

   // Function to handle profile changes for locked items
   public static function change_profile_for_locked_items($item) {
      if (self::is_item_locked($item)) {
         $_SESSION['glpi_plugin_lock_former_profile'] = $_SESSION['glpiactiveprofile'];
         $_SESSION['glpiactiveprofile'] = $_SESSION['glpi_plugin_lock_read_only_profile'];
      }
   }

   // Function to reset profile after unlocking the item
   public static function reset_profile_after_unlock() {
      if (isset($_SESSION['glpi_plugin_lock_former_profile'])) {
         $_SESSION['glpiactiveprofile'] = $_SESSION['glpi_plugin_lock_former_profile'];
         unset($_SESSION['glpi_plugin_lock_former_profile']);
      }
   }
}
