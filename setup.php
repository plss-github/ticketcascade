<?php

define('PLUGIN_TICKETCASCADE_VERSION', '1.0.2');

define('PLUGIN_TICKETCASCADE_MIN_GLPI', '10.0.0');
// define('PLUGIN_TICKETCASCADE_MAX_GLPI', '11.0.99');

if (!defined('PLUGINTICKETCASCADE_DIR')) {
  define('PLUGINTICKETCASCADE_DIR', Plugin::getPhpDir('ticketcascade'));
}

function plugin_init_ticketcascade() {
  global $PLUGIN_HOOKS;

  $PLUGIN_HOOKS['csrf_compliant']['ticketcascade'] = true;

  $PLUGIN_HOOKS['config_page']['ticketcascade'] = 'front/rule.php';
  $PLUGIN_HOOKS['menu_toadd']['ticketcascade'] = ['config' => 'PluginTicketcascadeRule'];

  $PLUGIN_HOOKS['item_add']['ticketcascade'] = [
    'Ticket' => ['PluginTicketcascadeBehavior', 'ticketAdd']
  ];

  $PLUGIN_HOOKS['pre_item_update']['ticketcascade'] = [
    'Ticket' => ['PluginTicketcascadeBehavior', 'preTicketUpdate']
  ];
  $PLUGIN_HOOKS['item_update']['ticketcascade'] = [
    'Ticket' => ['PluginTicketcascadeBehavior', 'ticketUpdate']
  ];
}

function plugin_ticketcascade_script_endswith($scriptname) {
  $scriptname = 'ticketcascade/front/' . $scriptname;
  $script_name = $_SERVER['SCRIPT_NAME'];

  return substr($script_name, -strlen($scriptname)) === $scriptname;
}


function plugin_version_ticketcascade() {
  return [
    'name' => 'Chamado em Cascata',
    'version' => PLUGIN_TICKETCASCADE_VERSION,
    'author' => 'Ampris, Matheus Schmidt',
    'homepage' => 'https://github.com/plss-github/ticketcascade',
    'license' => 'GPLv2+',
    'requirements' => [
      'glpi' => [
        'min' => PLUGIN_TICKETCASCADE_MIN_GLPI
      ],
    ],
  ];
}

function plugin_ticketcascade_check_prerequisites() {
  return true;
}
