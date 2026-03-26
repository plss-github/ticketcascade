<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginTicketcascadeBehavior extends CommonDBTM {
  static $rightname = 'config';

  static function getTypeName($nb = 0) {
    return _n('Comportamento', 'Comportamentos', $nb, 'ticketcascade');
  }

  static function installBaseData(Migration $migration, $version) {
    global $DB;

    $default_charset   = DBConnection::getDefaultCharset();
    $default_collation = DBConnection::getDefaultCollation();
    $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();

    if (!$DB->tableExists(self::getTable())) {
      $query = "CREATE TABLE `" . self::getTable() . "` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `plugin_ticketcascade_rules_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `ticketmodels_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `parent_behavior_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        KEY `plugin_ticketcascade_rules_id` (`plugin_ticketcascade_rules_id`),
        KEY `ticketmodels_id` (`ticketmodels_id`),
        KEY `parent_behavior_id` (`parent_behavior_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};";
      $DB->doQuery($query);
    } else {
      if (!$DB->fieldExists(self::getTable(), 'parent_behavior_id')) {
        $migration->addField(self::getTable(), 'parent_behavior_id', 'int(11) NOT NULL DEFAULT \'0\'');
      }
    }

    if (!$DB->tableExists('glpi_plugin_ticketcascade_ticketbehaviors')) {
      $query2 = "CREATE TABLE `glpi_plugin_ticketcascade_ticketbehaviors` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `tickets_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `plugin_ticketcascade_behaviors_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `root_tickets_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `plugin_ticketcascade_rules_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        KEY `tickets_id` (`tickets_id`),
        KEY `behaviors_id` (`plugin_ticketcascade_behaviors_id`),
        KEY `root_tickets_id` (`root_tickets_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};";
      $DB->doQuery($query2);
    }
  }

  static function uninstall() {
    global $DB;

    $DB->dropTable(self::getTable());
    $DB->dropTable('glpi_plugin_ticketcascade_ticketbehaviors');

    return true;
  }

  public function cleanDBonPurge() {
    global $DB;

    $id = $this->fields['id'];
    $parent_id = $this->fields['parent_behavior_id'];

    $DB->update(
      self::getTable(),
      [ 'parent_behavior_id' => $parent_id ],
      [ 'parent_behavior_id' => $id ]
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

  static function showForRule(PluginTicketcascadeRule $rule) {
    global $DB, $CFG_GLPI;

    $rule_id = $rule->getID();
    if (!$rule->can($rule_id, READ)) {
      return false;
    }

    $canedit = $rule->can($rule_id, UPDATE);

    if ($canedit) {
      echo "<form action='" . static::getFormURL() . "' method='post'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><th colspan='4'>" . __('Adicionar Comportamento', 'ticketcascade') . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . TicketTemplate::getTypeName(1) . "</td>";
      echo "<td>";
      echo "<input type='hidden' name='plugin_ticketcascade_rules_id' value='$rule_id'>";
      echo "<input type='hidden' id='selected_behavior' name='parent_behavior_id' value='0'>";
      TicketTemplate::dropdown(['name' => 'ticketmodels_id', 'condition' => ['is_recursive' => [0, 1]]]);
      echo "</td>";
      echo "<td>";
      echo "<button type='submit' name='add' class='vsubmit' style='display:flex; gap:10px;'><i class='fas fa-plus'></i> " . _sx('button', 'Add') . "</button>";
      echo "</td>";
      echo "<td>";
      Html::closeForm();

      echo "<form action='" . static::getFormURL() . "' method='post' style='display:inline;'>";
      echo "<input type='hidden' id='delete_behavior_id' name='id' value='0'>";
      echo "<button type='submit' id='delete_behavior_btn' name='purge' class='vsubmit' style='display:none; background-color: #ef4444; color: white; border-color: #ef4444; gap:10px;'><i class='fas fa-trash-alt'></i> " . __('Excluir Selecionado', 'ticketcascade') . "</button>";
      Html::closeForm();

      echo "</td>";
      echo "</tr>";
      echo "</table>";
    }

    echo "<div class='spaced' style='background: white; border: 1px solid #ccc; padding: 10px; border-radius: 4px;'>";
    echo "<div id='cy' style='width: 100%; height: 600px; background-color: #f9fafb;'></div>";
    echo "</div>";

    $elements = [];
    $elements[] = "{ data: { id: 'root', label: '" . addslashes($rule->fields['name'] ?? '') . "\\n(Raiz)' } }";

    $iterator = $DB->request([
      'FROM' => self::getTable(),
      'WHERE' => [ 'plugin_ticketcascade_rules_id' => $rule_id ]
    ]);

    foreach ($iterator as $data) {
      $template = new TicketTemplate();
      $template->getFromDB($data['ticketmodels_id']);
      $label = $template->fields['name'] ?? "Modelo Excluído";

      $id = $data['id'];
      $parent_id = $data['parent_behavior_id'] > 0 ? $data['parent_behavior_id'] : 'root';

      $elements[] = "{ data: { id: '$id', label: '" . addslashes($label) . "' } }";
      $elements[] = "{ data: { source: '$parent_id', target: '$id' } }";
    }

    $elements_js = implode(",\n", $elements);

    echo "<script>
      function loadCytoscape(callback) {
        if (typeof cytoscape !== 'undefined') {
          callback();
        } else {
          let script = document.createElement('script');
          script.src = 'https://cdnjs.cloudflare.com/ajax/libs/cytoscape/3.26.0/cytoscape.min.js';
          script.onload = callback;
          document.head.appendChild(script);
        }
      }

      loadCytoscape(function() {
        if (!document.getElementById('cy')) return;
        const cy = cytoscape({
          container: document.getElementById('cy'),
          elements: [
            $elements_js
          ],
          style: [
            {
              selector: 'node',
              style: {
                'label': 'data(label)',
                'background-color': '#f3f4f6',
                'color': '#111',
                'text-valign': 'center',
                'text-halign': 'center',
                'shape': 'round-rectangle',
                'padding': '12px',
                'border-width': 1,
                'border-color': '#d1d5db',
                'font-size': '10px',
                'text-wrap': 'wrap',
                'text-max-width': '100px'
              }
            },
            {
              selector: 'node:selected',
              style: {
                'border-width': 2,
                'border-color': '#2563eb',
                'background-color': '#FFF'
              }
            },
            {
              selector: '#root',
              style: {
                'background-color': '#FFF',
                'font-weight': 'bold',
                'font-size': '11px'
              }
            },
            {
              selector: 'edge',
              style: {
                'curve-style': 'unbundled-bezier',
                'target-arrow-shape': 'triangle',
                'line-color': '#3b82f6',
                'target-arrow-color': '#3b82f6',
                'width': 2,
                'control-point-distances': 10,
                'control-point-weights': 0.5
              }
            }
          ],
          layout: {
            name: 'breadthfirst',
            directed: true,
            spacingFactor: 1.5,
            padding: 40,
            nodeDimensionsIncludeLabels: true,
            idealEdgeLength: 75,
          },
          autoungrabify: true,
          boxSelectionEnabled: false,
          userZoomingEnabled: true,
          userPanningEnabled: true,
        });

        cy.on('select', 'node', function(evt){
          var node = evt.target;
          var id = node.id();
          if (id === 'root') {
            if(document.getElementById('selected_behavior'))
              document.getElementById('selected_behavior').value = 0;

            if(document.getElementById('delete_behavior_btn'))
              document.getElementById('delete_behavior_btn').style.display = 'none';
          } else {
            if(document.getElementById('selected_behavior'))
              document.getElementById('selected_behavior').value = id;

            if(document.getElementById('delete_behavior_btn')) {
              document.getElementById('delete_behavior_btn').style.display = 'flex';
              document.getElementById('delete_behavior_id').value = id;
            }
          }
        });

        cy.on('unselect', 'node', function(evt){
          if(document.getElementById('selected_behavior'))
            document.getElementById('selected_behavior').value = 0;

          if(document.getElementById('delete_behavior_btn')) {
            document.getElementById('delete_behavior_btn').style.display = 'none';
            document.getElementById('delete_behavior_id').value = 0;
          }
        });
      });
    </script>";
  }

  static function ticketAdd($ticket) {
    global $DB;

    if (!isset($ticket->fields['itilcategories_id']) || empty($ticket->fields['itilcategories_id'])) {
      return;
    }

    $category_id = $ticket->fields['itilcategories_id'];
    $ticket_id = $ticket->getID();

    $rule_iterator = $DB->request([
      'FROM' => PluginTicketcascadeRule::getTable(),
      'WHERE' => [
        'itilcategories_id' => $category_id,
        'is_active' => 1
      ]
    ]);

    foreach ($rule_iterator as $rule_data) {
      $rule_id = $rule_data['id'];
      self::generateChildTickets($ticket_id, $rule_id, 0, $ticket->fields['entities_id'], $ticket_id);
    }
  }

  static function generateChildTickets($parent_ticket_id, $rule_id, $parent_behavior_id, $entities_id, $root_ticket_id) {
    global $DB;

    $behavior_iterator = $DB->request([
      'FROM' => self::getTable(),
      'WHERE' => [
        'plugin_ticketcascade_rules_id' => $rule_id,
        'parent_behavior_id' => $parent_behavior_id
      ]
    ]);

    foreach ($behavior_iterator as $behavior_data) {
      $ticketmodel_id = $behavior_data['ticketmodels_id'];
      $behavior_id = $behavior_data['id'];

      $already = $DB->request([
        'FROM' => 'glpi_plugin_ticketcascade_ticketbehaviors',
        'WHERE' => [
          'plugin_ticketcascade_behaviors_id' => $behavior_id,
          'root_tickets_id' => $root_ticket_id
        ]
      ]);

      if (count($already) > 0) {
        continue;
      }

      $new_ticket = new Ticket();
      $input = [
        'entities_id' => $entities_id,
        'itilcategories_id' => 0,
        'tickettemplates_id' => $ticketmodel_id
      ];

      $tt = new TicketTemplate();
      if ($tt->getFromDBWithData($ticketmodel_id)) {
        if (isset($tt->predefined) && is_array($tt->predefined)) {
          $input = array_merge($tt->predefined, $input);
        }
      }

      if (!isset($input['name']) || empty($input['name'])) {
        $input['name'] = __('Ticket gerado automaticamente por Cascata', 'ticketcascade');
      }

      $child_id = $new_ticket->add($input);
      if ($child_id) {
        $ticketlink = new Ticket_Ticket();
        $ticketlink->add([
          'tickets_id_1' => $parent_ticket_id,
          'tickets_id_2' => $child_id,
          'link' => Ticket_Ticket::SON_OF
        ]);

        $DB->insert('glpi_plugin_ticketcascade_ticketbehaviors', [
          'tickets_id' => $child_id,
          'plugin_ticketcascade_behaviors_id' => $behavior_id,
          'root_tickets_id' => $root_ticket_id,
          'plugin_ticketcascade_rules_id' => $rule_id
        ]);

        $fup = new ITILFollowup();
        $fup->add([
          'itemtype' => 'Ticket',
          'items_id' => $parent_ticket_id,
          'content' => sprintf(__('Chamado filho de ID #%d gerado com sucesso pela regra de cascata.', 'ticketcascade'), $child_id),
          'is_private' => 1
        ]);
      }
    }
  }

  static function ticketUpdate($ticket) {
    global $DB;

    if (isset($ticket->updates) && in_array('status', $ticket->updates)) {
      $new_status = $ticket->fields['status'];
      if (in_array($new_status, [Ticket::SOLVED, Ticket::CLOSED])) {
        $ticket_id = $ticket->getID();

        $iterator = $DB->request([
          'FROM' => 'glpi_plugin_ticketcascade_ticketbehaviors',
          'WHERE' => ['tickets_id' => $ticket_id]
        ]);

        foreach ($iterator as $data) {
          $behavior_id = $data['plugin_ticketcascade_behaviors_id'];
          $rule_id = $data['plugin_ticketcascade_rules_id'];
          $root_id = $data['root_tickets_id'];

          $rule = new PluginTicketcascadeRule();
          if ($rule->getFromDB($rule_id) && $rule->fields['is_active']) {
            self::generateChildTickets($ticket_id, $rule_id, $behavior_id, $ticket->fields['entities_id'], $root_id);
          }
        }
      }
    }
  }

  static function hasUnresolvedDescendants($ticket_id) {
    global $DB;

    $iterator = $DB->request([
      'SELECT' => ['t2.id', 't2.status'],
      'FROM' => 'glpi_tickets_tickets',
      'INNER JOIN' => [
        'glpi_tickets AS t2' => [
          'ON' => [
            'glpi_tickets_tickets' => 'tickets_id_2',
            't2' => 'id'
          ]
        ]
      ],
      'WHERE' => [
        'glpi_tickets_tickets.tickets_id_1' => $ticket_id,
        'glpi_tickets_tickets.link' => Ticket_Ticket::SON_OF
      ]
    ]);

    foreach ($iterator as $data) {
      $child_id = $data['id'];
      if (!in_array($data['status'], [Ticket::SOLVED, Ticket::CLOSED])) {
        $behavior_iterator = $DB->request([
          'FROM'  => 'glpi_plugin_ticketcascade_ticketbehaviors',
          'WHERE' => ['tickets_id' => $child_id]
        ]);

        $is_active_cascade = false;
        foreach ($behavior_iterator as $b_data) {
          $rule = new PluginTicketcascadeRule();
          if ($rule->getFromDB($b_data['plugin_ticketcascade_rules_id']) && $rule->fields['is_active']) {
            $is_active_cascade = true;
            break;
          }
        }

        if ($is_active_cascade) {
          return true;
        }
      }
      if (self::hasUnresolvedDescendants($child_id)) {
        return true;
      }
    }

    return false;
  }

  static function preTicketUpdate($ticket) {
    global $DB;

    if (isset($ticket->input['status']) && in_array($ticket->input['status'], [Ticket::SOLVED, Ticket::CLOSED])) {
      $ticket_id = $ticket->getID();

      if (self::hasUnresolvedDescendants($ticket_id)) {
        Session::addMessageAfterRedirect(
          __('Não é possível solucionar este chamado, pois ele possui chamados filhos em aberto.', 'ticketcascade'),
          false,
          ERROR
        );

        unset($ticket->input['status']);
        if (isset($ticket->input['closedate'])) {
          unset($ticket->input['closedate']);
        }
        if (isset($ticket->input['solvedate'])) {
          unset($ticket->input['solvedate']);
        }
      }
    }
  }
}
