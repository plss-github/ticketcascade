<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginTicketcascadeRule extends CommonDBTM {
  static $rightname = 'config';

  static function getTypeName($nb = 0) {
    return _n('Regra de Cascata', 'Regras de Cascata', $nb, 'ticketcascade');
  }

  static function getIcon() {
    return "fas fa-code-branch";
  }

  static function installBaseData(Migration $migration, $version) {
    global $DB;

    $default_charset   = DBConnection::getDefaultCharset();
    $default_collation = DBConnection::getDefaultCollation();
    $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();

    if (!$DB->tableExists(self::getTable())) {
      $query = "CREATE TABLE `" . self::getTable() . "` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `name` varchar(255) COLLATE {$default_collation} NOT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT '0',
        `itilcategories_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `date_mod` timestamp NULL DEFAULT NULL,
        `date_creation` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `is_active` (`is_active`),
        KEY `itilcategories_id` (`itilcategories_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};";
      $DB->doQuery($query);
    }
  }

  static function uninstall() {
    global $DB;
    $DB->dropTable(self::getTable());
    return true;
  }

  public function cleanDBonPurge() {
    global $DB;

    $id = $this->fields['id'];

    $DB->delete(
      PluginTicketcascadeBehavior::getTable(),
      [ 'plugin_ticketcascade_rules_id' => $id ]
    );

    $DB->delete(
      'glpi_plugin_ticketcascade_ticketbehaviors',
      [ 'plugin_ticketcascade_rules_id' => $id ]
    );
  }

  static function canCreate(): bool {
    return Session::haveRight(self::$rightname, UPDATE);
  }

  static function canView(): bool {
    return Session::haveRight(self::$rightname, READ);
  }

  static function canUpdate(): bool {
    return Session::haveRight(self::$rightname, UPDATE);
  }

  static function canPurge(): bool {
    return Session::haveRight(self::$rightname, UPDATE);
  }

  public function rawSearchOptions() {
    $tab = [];

    $tab[] = [
      'id' => 'common',
      'name' => __('Characteristics')
    ];

    $tab[] = [
      'id' => '1',
      'table' => $this->getTable(),
      'field' => 'name',
      'name' => __('Name'),
      'datatype' => 'itemlink',
      'massiveaction' => false,
    ];

    $tab[] = [
      'id' => '2',
      'table' => $this->getTable(),
      'field' => 'is_active',
      'name' => __('Active'),
      'datatype' => 'bool',
      'massiveaction' => true,
    ];

    $tab[] = [
      'id' => '3',
      'table' => 'glpi_itilcategories',
      'field' => 'name',
      'name' => __('Categoria ITIL'),
      'datatype' => 'dropdown',
      'massiveaction' => true,
    ];

    return $tab;
  }

  function showForm($ID, array $options = []) {
    $this->initForm($ID, $options);
    $this->showFormHeader($options);

    echo "<tr class='tab_bg_1'>";
    echo "<td>" . __('Name') . "</td>";
    echo "<td>";
    Html::autocompletionTextField($this, 'name');
    echo "</td>";
    echo "<td>" . __('Active') . "</td>";
    echo "<td>";
    Dropdown::showYesNo('is_active', $this->fields['is_active']);
    echo "</td>";
    echo "</tr>";

    echo "<tr class='tab_bg_1'>";
    echo "<td>" . __('Categoria ITIL') . "</td>";
    echo "<td>";
    ITILCategory::dropdown(['value' => $this->fields['itilcategories_id'], 'name' => 'itilcategories_id']);
    echo "</td>";
    echo "<td colspan='2'></td>";
    echo "</tr>";

    $this->showFormButtons($options);
    return true;
  }

  function defineTabs($options = []) {
    $ong = [];
    $this->addDefaultFormTab($ong);
    $this->addStandardTab(__CLASS__, $ong, $options);
    return $ong;
  }

  public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
    if ($item->getType() == static::class) {
      if (!$item->isNewItem()) {
        return [1 => __('Comportamentos', 'ticketcascade')];
      }
    }
    return '';
  }

  public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
    if ($item->getType() == static::class) {
      if ($tabnum == 1) {
        PluginTicketcascadeBehavior::showForRule($item);
      }
    }
    return true;
  }
}
