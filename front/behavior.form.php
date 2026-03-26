<?php

if (!defined('GLPI_ROOT')) {
  include ('../../../inc/includes.php');
}

$behavior = new PluginTicketcascadeBehavior();

if (isset($_POST["add"])) {
  $behavior->check(-1, CREATE, $_POST);
  $behavior->add($_POST);
  Html::back();
} else if (isset($_POST["purge"])) {
  $behavior->check($_POST["id"], PURGE);
  $behavior->delete($_POST, 1);
  Html::back();
}
