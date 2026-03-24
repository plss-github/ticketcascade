<?php

function plugin_ticketcascade_install() {
  $plugin_ticketcascade = new Plugin();
  $plugin_ticketcascade->getFromDBbyDir('ticketcascade');
  $version = $plugin_ticketcascade->fields['version'];


  $migration = new Migration($version);
  if (isCommandLine()) {
    echo __('MySQL instalação das tables', 'ticketcascade') . "\n";
  } else {
    echo '<center>';
    echo "<table class='tab_cadre_fixe'>";
    echo '<tr><th>' . __('MySQL instalação das tables', 'ticketcascade') . '<th></tr>';
    echo "<tr class='tab_bg_1'>";
    echo "<td align='center'>";
  }

  $classesToInstall = [
    PluginTicketcascadeRule::class,
    PluginTicketcascadeBehavior::class,
  ];

  foreach ($classesToInstall as $class) {
    if (method_exists($class, 'installBaseData')) {
      $class::installBaseData($migration, $version);
    }
  }
  $migration->executeMigration();

  foreach ($classesToInstall as $class) {
    if (method_exists($class, 'installUserData')) {
      $class::installUserData($migration, $version);
    }
  }
  $migration->executeMigration();

  if (!isCommandLine()) {
    echo '</td>';
    echo '</tr>';
    echo '</table></center>';
  }

  return true;
}

function plugin_ticketcascade_uninstall() {
  $_SESSION['uninstall_ticketcascade'] = true;

  echo '<center>';
  echo "<table class='tab_cadre_fixe'>";
  echo '<tr><th>' . __('MySQL desinstalação das tables', 'ticketcascade') . '<th></tr>';

  echo "<tr class='tab_bg_1'>";
  echo "<td align='center'>";

  $classesToUninstall = [
    'PluginTicketcascadeRule',
    'PluginTicketcascadeBehavior',
  ];

  foreach ($classesToUninstall as $class) {
    if ($plug = isPluginItemType($class)) {
      $dir  = PLUGINTICKETCASCADE_DIR . '/inc/';
      $item = strtolower($plug['class']);

      if (file_exists("$dir$item.class.php")) {
        include_once("$dir$item.class.php");
        if (!call_user_func([$class, 'uninstall'])) {
          return false;
        }
      }
    }
  }

  echo '</td>';
  echo '</tr>';
  echo '</table></center>';

  unset($_SESSION['uninstall_ticketcascade']);

  $pref = new DisplayPreference();
  $pref->deleteByCriteria([
    'itemtype' => ['LIKE' , 'PluginTicketcascade%'],
  ]);

  return true;
}
