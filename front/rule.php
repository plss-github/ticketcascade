<?php

include ('../../../inc/includes.php');

Session::checkRight("config", READ);

Html::header(PluginTicketcascadeRule::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "config", "pluginticketcascaderule");

Search::show('PluginTicketcascadeRule');

Html::footer();
