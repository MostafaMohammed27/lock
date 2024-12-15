<?php
// Here we are going to try to unlock the given object
// URL should be of the form: 'http://.../.../unlock.php?item=ticket_xxxxxx'
// which means that object type is ticket and id of the object is xxxxxx
// OR URL should be of the form: 'http://.../.../unlock.php?id=yyyyyy'
// which means that lock id is yyyyyy

// Include the necessary GLPI files
include('../../inc/includes.php'); // GLPI includes for DB connection and others

if (isset($_GET["item"]) || isset($_GET["id"])) {
   // If we have something to unlock

   if (isset($_GET["item"])) {
      $Object = explode("_", $_GET["item"]);
   } else {
      $Object = $_GET["id"];
   }

   // Output headers for proper HTTP response
   header("Content-Type: text/html; charset=UTF-8");
   header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0"); // HTTP/1.1
   header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

   // Debug output for item info
   if (isset($_GET["item"])) {
      echo ("object type: " . $Object[0] . "\n");
      echo ("id: " . $Object[1] . "\n");
   } else {
      echo ("id: " . $Object . "\n");
   }

   // Get DB connection through GLPI's DB class
   $DB = new DB();

   // Prepare SQL query to unlock item
   if (isset($_GET["item"])) {
      $query = "DELETE FROM `glpi_plugin_lock_locks` WHERE `itemtype` = :itemtype AND `items_id` = :items_id";
   } else {
      $query = "DELETE FROM `glpi_plugin_lock_locks` WHERE `id` = :lock_id";
   }

   try {
      // Using PDO to prepare and execute the query
      $stmt = $DB->prepare($query);

      if (isset($_GET["item"])) {
         // Bind parameters for item type and id
         $stmt->bindValue(':itemtype', ucfirst($Object[0]), PDO::PARAM_STR);
         $stmt->bindValue(':items_id', $Object[1], PDO::PARAM_INT);
      } else {
         // Bind lock id for direct deletion
         $stmt->bindValue(':lock_id', $Object, PDO::PARAM_INT);
      }

      // Execute the query
      $stmt->execute();

      // Check how many rows were affected
      $rowsAffected = $stmt->rowCount();
      echo "Unlocked row: " . $rowsAffected . "\n";

   } catch (PDOException $e) {
      // Catch any database exceptions and output error message
      echo "Error: " . $e->getMessage();
   }
}
?>
