<?php

if (!defined('GLPI_ROOT')) {
  include ('../../../inc/includes.php');
}

$rule = new PluginTicketcascadeRule();

if (isset($_POST["add"])) {
  $rule->check(-1, CREATE, $_POST);
  if ($newID = $rule->add($_POST)) {
    Html::redirect($rule->getFormURL()."?id=".$newID);
  }
  Html::back();
} else if (isset($_POST["update"])) {
  $rule->check($_POST["id"], UPDATE);
  $rule->update($_POST);
  Html::back();
} else if (isset($_POST["purge"])) {
  $rule->check($_POST["id"], PURGE);
  $rule->delete($_POST, 1);
  $rule->redirectToList();
}

Html::header(PluginTicketcascadeRule::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "config", "pluginticketcascaderule");

$rule->display([
  'id' => $_GET["id"] ?? -1
]);

Html::footer();
