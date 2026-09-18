<?php
//
// ZoneMinder shared log panel
// Copyright (C) 2026 ZoneMinder LLC
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

# The Log view's toolbar and table, factored out so that any view can embed the
# same panel scoped to whatever it is about.  skins/classic/js/logpanel.js picks
# up every .logPanel in the page and drives it, reading the configuration from
# the markup, so a view only has to echo this.
#
# Options:
#   id          - prefix for the panel's element ids.  Default 'log'.
#   components  - array of Logs.Component names to lock the panel to.  The
#                 component selector and column are dropped and every query is
#                 restricted to them.  Null leaves the choice to the user.
#   page_size   - rows per page.  Defaults to what fits the browser window.
#   nav_buttons - show the Back and Refresh page buttons.  Default true.

# Rows that fit the browser window, which the client reports in the
# zmBrowserSizes cookie.  The overhead is the navbar, the panel summary, the
# toolbar, the table header and the pagination block, and a row is about 27px.
function logPanelPageSize() {
  if (isset($_COOKIE['zmBrowserSizes'])) {
    $sizes = jsonDecode($_COOKIE['zmBrowserSizes']);
    if (!empty($sizes['innerHeight'])) {
      $height = validInt($sizes['innerHeight']);
      if ($height) {
        return max(10, min(100, floor(($height - 56 - 18 - 66 - 25 - 58) / 27)));
      }
    }
  }
  return 25;
}

function getLogPanelHTML($options=array()) {
  global $Servers;

  $options = array_merge(array(
    'id' => 'log',
    'components' => null,
    'page_size' => logPanelPageSize(),
    'nav_buttons' => true,
  ), $options);

  $id = preg_replace('/[^A-Za-z0-9_]/', '', $options['id']);
  $locked = is_array($options['components']) ? array_values($options['components']) : null;
  $canEdit = canEdit('System');
  $multipleServers = count($Servers) > 1;

  $levels = array();
  foreach (array_values(ZM\Logger::$codes) as $level) {
    $levels[$level] = $level;
  }

  ob_start();
?>
<div class="logPanel" id="<?php echo $id ?>Panel" data-components="<?php echo $locked === null ? '' : validHtmlStr(json_encode($locked)) ?>">
  <div class="logPanel-summary text-center">
    <?php echo translate('State') ?>:&nbsp;<span class="logPanel-state"></span>&nbsp;-&nbsp;
    <?php echo translate('Total') ?>:&nbsp;<span class="logPanel-total"></span>&nbsp;-&nbsp;
<?php if ($locked === null) { # the unfiltered total is every log row, which means nothing to a scoped panel ?>
    <?php echo translate('Available') ?>:&nbsp;<span class="logPanel-available"></span>&nbsp;-&nbsp;
<?php } ?>
    <?php echo translate('Displaying') ?>:&nbsp;<span class="logPanel-displaying"></span>&nbsp;-&nbsp;
    <?php echo translate('Updated') ?>:&nbsp;<span class="logPanel-updated"></span><span class="logPanel-status"></span>
  </div>
  <div class="logPanel-table">
    <div class="logPanel-toolbar" id="<?php echo $id ?>Toolbar">
<?php if ($options['nav_buttons']) { ?>
      <button type="button" class="btn btn-normal logPanel-back" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
      <button type="button" class="btn btn-normal logPanel-reload" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>"><i class="fa fa-refresh"></i></button>
<?php } ?>
<?php if ($canEdit) { ?>
      <button type="button" class="btn btn-danger logPanel-clear" data-toggle="tooltip" data-placement="top" title="<?php echo translate('ClearLogs') ?>" disabled><i class="fa fa-trash"></i> <?php echo translate('ClearLogs') ?></button>
<?php } ?>
      <div class="controlHeader">
<?php if ($locked === null) { ?>
      <span class="term ComponentFilter">
        <label><?php echo translate('Component') ?></label>
<?php
  $components = dbFetchAll('SELECT DISTINCT Component FROM Logs ORDER BY Component', 'Component');
  $componentOptions = $components ? array_combine($components, $components) : array();
  // Multi-select: an empty selection means "All". Accept a scalar (legacy) or an
  // array from the session and keep only values that are still valid components.
  $selectedComponents = array();
  if (isset($_SESSION['zmLogComponent'])) {
    foreach ((array)$_SESSION['zmLogComponent'] as $c) {
      if (is_scalar($c) && isset($componentOptions[(string)$c])) $selectedComponents[] = (string)$c;
    }
  }
  echo '<span class="term-value-wrapper">';
  echo htmlSelect($id.'Component[]', $componentOptions, $selectedComponents,
      array('class'=>'chosen logPanel-filter logPanel-component',
        'multiple'=>'multiple', 'data-placeholder'=>translate('All')));
  echo '</span>';
?>
      </span>
<?php } ?>
<?php if ($multipleServers) { ?>
      <span class="term ServerFilter">
        <label><?php echo translate('Server') ?></label>
<?php
  $ServersById = array(''=>translate('All')) + array_to_hash_by_key('Id', $Servers);
  echo '<span class="term-value-wrapper">';
  echo htmlSelect($id.'ServerId', $ServersById, '',
      array('class'=>'chosen logPanel-filter logPanel-server'));
  echo '</span>';
?>
      </span>
<?php } ?>
      <span class="term LevelFilter">
        <label><?php echo translate('Level') ?></label>
<?php
  // Multi-select: an empty selection means "All". Accept a scalar (legacy) or an
  // array from the session and keep only values that are still valid levels.
  $selectedLevels = array();
  if ($locked === null and isset($_SESSION['zmLogFilterLevel'])) {
    foreach ((array)$_SESSION['zmLogFilterLevel'] as $l) {
      if (is_scalar($l) && isset($levels[(string)$l])) $selectedLevels[] = (string)$l;
    }
  }
  echo '<span class="term-value-wrapper">';
  echo htmlSelect($id.'Level[]', $levels, $selectedLevels,
      array('class'=>'chosen logPanel-filter logPanel-level',
        'multiple'=>'multiple', 'data-placeholder'=>translate('All')));
  echo '</span>';
?>
      </span>
      <span class="term StartDateTimeFilter">
        <label><?php echo translate('Start Date/Time') ?></label>
        <span class="term-value-wrapper">
          <input type="text" class="logPanel-start" name="<?php echo $id ?>StartDateTime" value=""/>
        </span>
      </span>
      <span class="term EndDateTimeFilter">
        <label><?php echo translate('End Date/Time') ?></label>
        <span class="term-value-wrapper">
          <input type="text" class="logPanel-end" name="<?php echo $id ?>EndDateTime" value=""/>
        </span>
      </span>
      </div>
    </div><!--logPanel-toolbar-->

    <table
      id="<?php echo $id ?>Table"
      class="logPanel-logs table-sm table-borderless"
      data-locale="<?php echo i18n() ?>"
      data-side-pagination="server"
      data-pagination="true"
      data-page-size="<?php echo (int)$options['page_size'] ?>"
      data-page-list="[10, 25, 50, 100, 200, 300, 400, 500]"
      data-search="true"
      data-advanced-search="true"
      data-id-table="advancedTable"
      data-cookie="true"
      data-cookie-id-table="zm<?php echo $id ?>Table"
      data-cookie-expire="2y"
      data-remember-order="true"
      data-show-columns="true"
      data-show-export="true"
      data-toolbar="#<?php echo $id ?>Toolbar"
      data-show-fullscreen="true"
      data-maintain-meta-data="true"
      data-buttons-class="btn btn-normal"
      data-show-jump-to="true"
      data-auto-refresh="<?php echo ((int)ZM_WEB_REFRESH_LOGS === 0) ? 'false' : 'true' ?>"
      data-auto-refresh-silent="true"
      data-show-refresh="true"
      <?php echo ((int)ZM_WEB_REFRESH_LOGS !== 0) ? 'data-auto-refresh-interval="'.(int)ZM_WEB_REFRESH_LOGS.'"' : '' ?>
<?php if ($canEdit) { ?>
      data-click-to-select="true"
<?php } ?>
      data-id-field="Id"
    >
      <thead class="thead-highlight">
        <tr>
<?php if ($canEdit) { ?>
          <th data-sortable="false" data-field="toggleCheck" data-checkbox="true"></th>
<?php } ?>
          <th data-field="Id" data-visible="false"></th>
          <th data-sortable="true" data-field="DateTime"><?php echo translate('DateTime') ?></th>
          <th data-sortable="true" data-field="Component"<?php echo $locked === null ? '' : ' data-visible="false"' ?>><?php echo translate('Component') ?></th>
<?php if ($multipleServers) { ?>
          <th data-sortable="false" data-field="Server"><?php echo translate('Server') ?></th>
<?php } ?>
          <th data-sortable="true" data-field="Pid"><?php echo translate('Pid') ?></th>
          <th data-sortable="true" data-field="Code"><?php echo translate('Level') ?></th>
          <th data-sortable="true" data-field="Message"><?php echo translate('Message') ?></th>
          <th data-sortable="true" data-field="File"><?php echo translate('File') ?></th>
          <th data-sortable="true" data-field="Line"><?php echo translate('Line') ?></th>
        </tr>
      </thead>

      <tbody>
      <!-- Row data populated via Ajax -->
      </tbody>

    </table>
  </div><!--logPanel-table-->
</div><!--logPanel-->
<?php
  return ob_get_clean();
} # end function getLogPanelHTML
?>
