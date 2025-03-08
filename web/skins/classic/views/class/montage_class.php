<?php
namespace Skin;
use \ZM;
use \PDO;
//use \ZM_Object; //А нужно ли? Хотел для получения $monitor->Id(), но не помогло....

require_once('./includes/Object.php');

class Montage {
  public static $maxEvents = 200000; //Maximum number of events in the SQL samplek
  public static $presetLayoutsNames = array( //Order matters!
    'Auto',
    '1 Wide',
    '2 Wide',
    '3 Wide',
    '4 Wide',
    '6 Wide',
    '8 Wide',
    '12 Wide',
    '16 Wide'
  );
  public static $scale = ''; //This remains to be verified
  public static $layout_is_preset = false;
  public static $layout = '';
  public static $layouts = '';
  public static $layout_id = '';
  public static $layoutsById = '';
  public static $request = '';
  public static $options = [];
  public static $need_hls = false;
  public static $need_janus = false;
  public static $monitors = array();
  public static $showControl = false;
  public static $showZones = false;
  public static $displayMonitors = [];
  public static $resultMonitorFilters = [];
  public static $AutoLayoutName = '';

  public function __construct()
  {
    $buildLayouts = self::buildLayouts();
    self::$layout = $buildLayouts['layout'];
    self::$layout_id = $buildLayouts['layout_id'];
    self::$layoutsById = $buildLayouts['layoutsById'];
    self::$layout_is_preset = $buildLayouts['layout_is_preset'];
    self::$request = $buildLayouts['request'];
    self::$options = self::buildOptions();
    self::buildMonitors();
  }

  public static function implodeWithQuotes(array $data) {
    //Temporarily not used
    return sprintf("'%s'", implode("', '", $data));
  }

  public static function buildLayouts() {
    require_once('includes/MontageLayout.php');

    if (isset($_REQUEST['showZones'])) {
      if ($_REQUEST['showZones'] == 1) {
        self::$showZones = true;
      }
    }

    $layouts = ZM\MontageLayout::find(NULL, array('order'=>"lower('Name')"));
    // layoutsById is used in the dropdown, so needs to be sorted
    $layoutsById = array();

    /* Create an array "Name"=>layouts to make it easier to find IDs by name */
    $layoutsByName = array();
    foreach ($layouts as $l) {
      if ($l->Name() == 'Freeform') $l->Name('Auto');
      $layoutsByName[$l->Name()] = $l;
    }

    /* Fill with preinstalled Layouts. They should always come first.
     * Also sorting 1 Wide and 11 Wide fails... so need a smarter sort
     */
    foreach (self::$presetLayoutsNames as $name) {
      if (array_key_exists($name, $layoutsByName)) // Layout may be missing in DB (rare case during update process)
        $layoutsById[$layoutsByName[$name]->Id()] = $layoutsByName[$name];
    }

    /* Add custom Layouts & assign objects instead of names for preset Layouts */
    foreach ($layouts as $l) {
      $layoutsById[$l->Id()] = $l;
    }

    zm_session_start();
    $layout_id = 0;
    if (isset($_REQUEST['zmMontageLayout'])) {
      $layout_id = $_SESSION['zmMontageLayout'] = validCardinal($_REQUEST['zmMontageLayout']);
    } else if ( isset($_COOKIE['zmMontageLayout']) ) {
      $layout_id = $_SESSION['zmMontageLayout'] = validCardinal($_COOKIE['zmMontageLayout']);
    } else if ( isset($_SESSION['zmMontageLayout']) ) {
      $layout_id = validCardinal($_SESSION['zmMontageLayout']);
    }
    session_write_close();
    if (!$layout_id || !isset($layoutsById[$layout_id])) {
      $layout_id = $layoutsByName['Auto']->Id();
    }
    $layout = $layoutsById[$layout_id];
    self::$layout_is_preset = array_search($layout->Name(), self::$presetLayoutsNames) === false ? false : true;
    
    return [
      'layout'=>$layout, //current layout
      'layouts'=>$layouts, //all layouts
      'layout_id'=>$layout_id, //current layout ID
      'layoutsById'=>$layoutsById,
      'layout_is_preset'=>self::$layout_is_preset,
      'request'=>$_REQUEST
    ];
  }

  public static function buildMonitors() {
    require_once(getSkinFile('views/_monitor_filters.php'));

    /* +++ IMPORTANT !!! This code must be embedded in all files where _monitor_filters.php is used*/
    $resultMonitorFilters = buildMonitorsFilters();
    $filterbar = $resultMonitorFilters['filterBar'];
    $displayMonitors = $resultMonitorFilters['displayMonitors'];
    /* --- */
    self::$displayMonitors = $displayMonitors;
    self::$resultMonitorFilters = $resultMonitorFilters;

    foreach ($displayMonitors as &$row) {
      //if ($row['Capturing'] == 'None')
      //  continue;

      //$row['Scale'] = $scale;
      //$row['PopupScale'] = reScale(SCALE_BASE, $row['DefaultScale'], ZM_WEB_DEFAULT_SCALE);

      if (ZM_OPT_CONTROL && $row['ControlId'] && $row['Controllable'])
        self::$showControl = true;
      if (!isset($widths[$row['Width'].'px'])) {
        $widths[$row['Width'].'px'] = $row['Width'].'px';
      }
      if (!isset($heights[$row['Height'].'px'])) {
        $heights[$row['Height'].'px'] = $row['Height'].'px';
      }
      $monitor = self::$monitors[] = new ZM\Monitor($row);

      if ( $monitor->RTSP2WebEnabled() and $monitor->RTSP2WebType == "HLS") {
        self::$need_hls = true;
      }
      if ($monitor->JanusEnabled()) {
        self::$need_janus = true;
      }
    } # end foreach Monitor

    $default_layout = '';
    $monitorCount = count(self::$monitors);
    if ($monitorCount <= 3) {
      $default_layout = $monitorCount . ' Wide';
    } else if ($monitorCount <= 4) {
      $default_layout = '2 Wide';
    } else if ($monitorCount <= 6) {
      $default_layout = '3 Wide';
    } else if ($monitorCount%4 == 0) {
      $default_layout = '4 Wide';
    } else if ($monitorCount%6 == 0) {
      $default_layout = '6 Wide';
    } else {
      $default_layout = '4 Wide';
    }
    self::$AutoLayoutName = $default_layout;
  }
  
  public static function buildOptions() {
    $options = array();
    if (!empty($_REQUEST['maxfps']) and validHtmlStr($_REQUEST['maxfps']) and ($_REQUEST['maxfps']>0)) {
      $options['maxfps'] = validHtmlStr($_REQUEST['maxfps']);
    } else if (isset($_COOKIE['zmMontageRate'])) {
      $options['maxfps'] = validHtmlStr($_COOKIE['zmMontageRate']);
    } else {
      $options['maxfps'] = ''; // unlimited
    }
    return $options;
  }

  public static function buildMonitorsFilters() {
    //Temporarily not used
  }

  public static function buildGlobalFilters () {
    //Code moved from \skins\classic\views\montagereview.php
    global $user;

    $preference = ZM\User_Preference::find_one([
      'UserId'=>$user->Id(),
      'Name'=>'MontageSort'.(isset($_SESSION['GroupId']) ? implode(',', $_SESSION['GroupId']) : '')
    ]);
    if ($preference) {
      $monitors_by_id = array_to_hash_by_key('Id', $displayMonitors);
      $sorted_monitors = [];
      foreach (explode(',', $preference->Value()) as $id) {
        if (isset($monitors_by_id[$id])) {
          $sorted_monitors[] = $monitors_by_id[$id];
        } else {
          ZM\Debug("Ordered monitor not found in monitorsById $id");
        }
      }
      if (count($sorted_monitors)) $displayMonitors = $sorted_monitors;
    }

    // Parse input parameters -- note for future, validate/clean up better in case we don't get called from self.
    // Live overrides all the min/max stuff but it is still processed

    // The default (nothing at all specified) is for 1 hour so we do not read the whole database

    if (isset($_REQUEST['current'])) {
      $defaultCurrentTime = validHtmlStr($_REQUEST['current']);
      $defaultCurrentTimeSecs = strtotime($defaultCurrentTime);
    }

    if ( !isset($_REQUEST['minTime']) && !isset($_REQUEST['maxTime']) ) {
      if (isset($defaultCurrentTimeSecs)) {
        $minTime = date('Y-m-d H:i:s', $defaultCurrentTimeSecs - 1800);
        $maxTime = date('Y-m-d H:i:s', $defaultCurrentTimeSecs + 1800);
      } else {
        $time = time();
        $maxTime = date('Y-m-d H:i:s', $time);
        $minTime = date('Y-m-d H:i:s', $time - 3600);
     }
    } else {
      if (isset($_REQUEST['minTime']))
        $minTime = validHtmlStr($_REQUEST['minTime']);

      if (isset($_REQUEST['maxTime']))
        $maxTime = validHtmlStr($_REQUEST['maxTime']);
    }

    // AS a special case a "all" is passed in as an extreme interval - if so, clear them here and let the database query find them

    if ( (strtotime($maxTime) - strtotime($minTime))/(365*24*3600) > 30 ) {
      // test years
      $minTime = null;
      $maxTime = null;
    }

    $filter = null;
    if (isset($_REQUEST['filter'])) {
      $filter = ZM\Filter::parse($_REQUEST['filter']);
      $terms = $filter->terms();

      # Try to guess min/max time from filter
      foreach ($terms as &$term) {
        if ($term['attr'] == 'Notes') {
          $term['cookie'] = 'Notes';
          if (empty($term['val']) and isset($_COOKIE['Notes'])) $term['val'] = $_COOKIE['Notes'];
        } else if ($term['attr'] == 'StartDateTime') {
          if ($term['op'] == '<=' or $term['op'] == '<') {
            $maxTime = $term['val'];
            $term['id'] = 'EndDateTime';
          } else if ( $term['op'] == '>=' or $term['op'] == '>' ) {
            $minTime = $term['val'];
            $term['id'] = 'StartDateTime';
          }
        }
      } # end foreach term
      $filter->terms($terms);
    } else {
      $filter = new ZM\Filter();
      if (isset($_REQUEST['minTime']) && isset($_REQUEST['maxTime']) && (count($displayMonitors) != 0)) {
        $filter->addTerm(array('id' => 'StartDateTime', 'attr' => 'StartDateTime', 'op' => '>=', 'val' => $_REQUEST['minTime'], 'obr' => '1'));
        $filter->addTerm(array('id' => 'EndDateTime', 'attr' => 'StartDateTime', 'op' => '<=', 'val' => $_REQUEST['maxTime'], 'cnj' => 'and', 'cbr' => '1'));
        if (count($selected_monitor_ids)) {
          $filter->addTerm(array('attr' => 'Monitor', 'op' => 'IN', 'val' => implode(',',$selected_monitor_ids), 'cnj' => 'and'));
        } else if ( isset($_SESSION['GroupId']) || isset($_SESSION['ServerFilter']) || isset($_SESSION['StorageFilter']) || isset($_SESSION['StatusFilter']) ) {
          # this should be redundant
          for ( $i = 0; $i < count($displayMonitors); $i++ ) {
            if ( $i == '0' ) {
              $filter->addTerm(array('attr' => 'MonitorId', 'op' => '=', 'val' => $displayMonitors[$i]['Id'], 'cnj' => 'and', 'obr' => '1'));
            } else if ( $i == (count($displayMonitors)-1) ) {
              $filter->addTerm(array('attr' => 'MonitorId', 'op' => '=', 'val' => $displayMonitors[$i]['Id'], 'cnj' => 'or', 'cbr' => '1'));
            } else {
              $filter->addTerm(array('attr' => 'MonitorId', 'op' => '=', 'val' => $displayMonitors[$i]['Id'], 'cnj' => 'or'));
            }
          }
        }
      } # end if REQUEST[Filter]
    }
    if (!$filter->has_term('Archived')) {
      $filter->addTerm(array('id' => 'Archived', 'attr' => 'Archived', 'op' => '=', 'val' => '', 'cnj' => 'and'));
    }
    if (!$filter->has_term('StartDateTime', '>=')) {
      $filter->addTerm(array('id' => 'StartDateTime', 'attr' => 'StartDateTime', 'op' => '>=', 'cnj' => 'and'));
    }
    if (!$filter->has_term('StartDateTime', '<=')) {
      $filter->addTerm(array('id' => 'EndDateTime', 'attr' => 'StartDateTime', 'op' => '<=', 'cnj' => 'and'));
    }
    if (!$filter->has_term('Tags')) {
      $filter->addTerm(array('id' => 'Tags', 'attr' => 'Tags', 'op' => '=',
        'val' => (isset($_COOKIE['eventsTags']) ? $_COOKIE['eventsTags'] : ''),
        'cnj' => 'and', 'cookie'=>'eventsTags'));
    }
    if (!$filter->has_term('Notes')) {
      $filter->addTerm(array('id' => 'Notes', 'cnj'=>'and', 'attr'=>'Notes', 'op'=> 'LIKE', 'val'=>'', 'cookie'=>'eventsNotes'));
    }
    if (count($filter->terms()) ) {
      # This is to enable the download button
      zm_session_start();
      $_SESSION['montageReviewFilter'] = $filter;
      session_write_close();
    }
     return $filter;
  }

  public static function setScale($scale) {
    self::$scale = $scale;
  }

  /*
   * $width - monitor width "$monitor->Width()" or event
  */
  public static function scaleCalculation($width, $scale='') {
    $layout = self::$layout;
    if (!$width || (int)$width == 0) {
      return 100;
    } else {
      $newScale = intval(100*(1920/$width));
    }

    if (!$scale and ($layout->Name() != 'Auto')) {
      if (self::$layout_is_preset) {
        # We know the # of columns so can figure out a proper scale
        if (preg_match('/^(\d+) Wide$/', $layout->Name(), $matches)) {
          if ($matches[1]) {
            $newScale = intval(100*((1920/$matches[1])/$width));
          }
        }
      } else {
        # Custom, default to 25% of 1920 for now, because 25% of a 4k is very different from 25% of 640px
        $newScale = intval(100*((1920/4)/$width));
      }
    }
    if ($newScale > 100) $newScale = 100;
    return $newScale;
  }
  
  public static function buildGridMonitorsLive() {
    // This code is moved from skins/classic/views/montage.php

    $blockMonitors = "";
    foreach (self::$monitors as $monitor) {
      if ($monitor->Capturing() == 'None')
        continue;
      $monitor_options = self::$options;
      #ZM\Debug('Options: ' . print_r($monitor_options,true));

      if ($monitor->Type() == 'WebSite') {
        echo getWebSiteUrl(
          'liveStream'.$monitor->Id(),
          $monitor->Path(),
          (isset($monitor_options['width']) ? $monitor_options['width'] : reScale($monitor->ViewWidth(), $scale).'px' ),
          (isset($monitor_options['height']) ? $monitor_options['height'] : reScale($monitor->ViewHeight(), $scale).'px' ),
          $monitor->Name()
        );
      } else {
        $monitor_options['state'] = !ZM_WEB_COMPACT_MONTAGE;
        $monitor_options['zones'] = self::$showZones;
        $monitor_options['mode'] = 'paused';

        $monitor_options['scale'] = self::scaleCalculation($monitor->Width());

        $blockMonitors .= $monitor->getStreamHTML($monitor_options);
      }
    } # end foreach monitor

    return ['monitors'=>$blockMonitors];
  }

  public static function buildGridMonitorsInRecords($dateTime) {
    // Соберем массив с событиями-заглушками для отображения хоть чего-то.
	$monitorsId = [];
    foreach (self::$monitors as $monitor) {
      $monitorsId[] = $monitor->Id();
	}
    //Выбираем последние события.
    $lastEvents = self::queryEvents($filter='', $monitorsId, $startDateTime = $dateTime, $endDateTime='', $resolution=0, $action='queryEventsForMonitor', $actionRange='last', $maxFPS = null);
	
	
/* +++++ IgorA100 ВРЕМЕННО ПЕРЕНЕСЕНО для работы со слоями.... */
    $scale = ''; //IgorA100 Временно, что бы варнинга PHP не было, а вообще мы эту переменную не используем уже и еще... :)
/* ----- IgorA100 ВРЕМЕННО ПЕРЕНЕСЕНО для работы со слоями.... */
    $layout = self::$layout;
    $layout_is_preset =  self::$layout_is_preset;
    $blockMonitors = "";
    foreach (self::$monitors as $monitor) {
      $lastEventForMonitor = self::findDataInEventsArray($monitor->Id(), $lastEvents['events']);
      if (!$lastEventForMonitor) {
        //Т.к. нет записанных событий для монитора, то монитор не отображаем.
        continue;
      }
      $monitor_options = self::$options;
      $monitor_options['lastEvent'] = $lastEventForMonitor;
      #ZM\Debug('Options: ' . print_r($monitor_options,true));

      if ($monitor->Type() == 'WebSite') {
        echo getWebSiteUrl(
          'liveStream'.$monitor->Id(),
          $monitor->Path(),
          //(isset($options['width']) ? $options['width'] : reScale($monitor->ViewWidth(), $scale).'px' ),
          //(isset($options['height']) ? $options['height'] : reScale($monitor->ViewHeight(), $scale).'px' ),
          reScale($monitor->ViewWidth(), $scale).'px',
          reScale($monitor->ViewHeight(), $scale).'px',
          $monitor->Name()
        );
      } else {
        $monitor_options['state'] = !ZM_WEB_COMPACT_MONTAGE;
        $monitor_options['zones'] = self::$showZones;
        $monitor_options['mode'] = 'paused';
        if (!$scale and ($layout->Name() != 'Auto')) {
          if ($layout_is_preset) {
            # We know the # of columns so can figure out a proper scale
            if (preg_match('/^(\d+) Wide$/', $layout->Name(), $matches)) {
              if ($matches[1]) {
                $monitor_options['scale'] = intval(100*((1920/$matches[1])/$monitor->Width()));
              }
            }
          } else {
            # Custom, default to 25% of 1920 for now, because 25% of a 4k is very different from 25% of 640px
            $monitor_options['scale'] = intval(100*((1920/4)/$monitor->Width()));
          }
        } else {
          $monitor_options['scale'] = self::scaleCalculation($monitor->Width());
        }

        if ($monitor_options['scale'] > 100) $monitor_options['scale'] = 100;

        //$blockMonitors .= $monitor->getStreamHTML($monitor_options);
        $monitor_options['frameId'] = 'FrameID'; //НУЖНО ПОДСТАВЛЯТЬ ID Фрейма !!!
        $monitor_options['monitorId'] = $monitor->Id();

        $blockMonitors .= self::buildEventHTML($monitor_options);
      }
    } # end foreach monitor
//echo $blockMonitors;
    //return ['monitors'=>$blockMonitors, 'lastEvents'=>$lastEvents, 'lastEvents_events'=>$lastEvents['events'], 'lastEvent!!!'=>self::findDataInEventsArray(15, $lastEvents['events'])];
    return ['monitors'=>$blockMonitors, 'lastEvents'=>$lastEvents];
  }

  public static function getEventInfoHTML($monitor) {
    //IgorA100 Перенесено из includes\Monitor.php со значительными изменениями
    //Пересмотреть отображаемые данные

    $monitorId = $monitor->Id();
    $monitorName = $monitor->Name();
    //$monitorStatus = $monitor->Status(); //Получим WARNING, если монитора не будет в таблице 'Monitor_Status'
    // А его там может не быть, т.к. например его отключили, но события по нем есть.
    //$monitorStatus = '';
    $monitorAnalysing = $monitor->Analysing();
/*
Cause: "Continuous"
EndDateTime: "2024-07-19 14:30:20"
Frames: 9005
Id: 509973
Length: "600.08"
MonitorId: 6
StartDateTime: "2024-07-19 14:20:20"
Width: 1920
*/
    $html = '
<div id="monitorStatus'.$monitorId.'" class="monitorStatus">
  <span class="MonitorName">'.$monitorName.' (id='.$monitorId.')</span>
  <div id="frameInfo'.$monitorId.'" class="frameInfo">
    <span>'.translate('Event ID').':<span id="eventId'.$monitorId.'">'.''.'</span></span>
    <span class="viewingFPS" id="viewingFPS'.$monitorId.'" title="'.translate('Viewing FPS').'"><span id="viewingFPSValue'.$monitorId.'"></span> fps</span>
    <span id="cause'.$monitorId.'" class="cause">'.translate('Cause').': <span id="causeValue'.$monitorId.'"></span></span>
    <span id="length'.$monitorId.'" class="length">'.translate('Length').': <span id="lengthValue'.$monitorId.'"></span></span>
    <span id="width'.$monitorId.'" class="width">'.translate('Width').': <span id="widthValue'.$monitorId.'"></span>px</span>
    <span id="frames'.$monitorId.'" class="frames">'.translate('Frames').': <span id="framesValue'.$monitorId.'"></span></span>
    <br><span id="startDateTime'.$monitorId.'" class="startDateTime">'.translate('Start time').': <span id="startDateTimeValue'.$monitorId.'"></span></span>
    <br><span id="endDateTime'.$monitorId.'" class="endDateTime">'.translate('End time').': <span id="endDateTimeValue'.$monitorId.'"></span></span>
  </div>
</div>
';

/*
    $html = '
<div id="monitorStatus'.$monitorId.'" class="monitorStatus">
<span class="MonitorName">'.$monitorName.' (id='.$monitorId.')</span>
  <div id="monitorState'.$monitorId.'" class="monitorState">
   <span>'.translate('State').':<span id="stateValue'.$monitorId.'">'.$monitorStatus.'</span></span>
    <span class="viewingFPS" id="viewingFPS'.$monitorId.'" title="'.translate('Viewing FPS').'"><span id="viewingFPSValue'.$monitorId.'"></span> fps</span>
    <span class="captureFPS" id="captureFPS'.$monitorId.'" title="'.translate('Capturing FPS').'"><span id="captureFPSValue'.$monitorId.'"></span> fps</span>
';
    if ($monitorAnalysing != 'None') {
      $html .= '<span class="analysisFPS" id="analysisFPS'.$monitorId.'" title="'.translate('Analysis FPS').'"><span id="analysisFPSValue'.$monitorId.'"></span> fps</span>
      ';
    }
    $html .= '
    <span id="rate'.$monitorId.'" class="rate hidden">'.translate('Rate').': <span id="rateValue'.$monitorId.'"></span>x</span>
    <span id="delay'.$monitorId.'" class="delay hidden">'.translate('Delay').': <span id="delayValue'.$monitorId.'"></span>s</span>
    <span id="level'.$monitorId.'" class="buffer hidden">'.translate('Buffer').': <span id="levelValue'.$monitorId.'"></span>%</span>
    <span class="zoom hidden" id="zoom'.$monitorId.'">'. translate('Zoom').': <span id="zoomValue'.$monitorId.'"></span>x</span>
  </div>
</div>
';
*/
    return $html;
  }

  public static function buildEventHTML($options) {
    //ЭТО ПОТОМ, здесь СОБЫТИЕ НЕ ГРУЗИМ !!!
    //IgorA100 Перенесено из includes\Monitor.php со значительными изменениями
    global $basename;
    $basename = "montage"; //Временно....

    //Часть кода взята из skins\classic\views\event.php
    require_once('includes/Event.php');
    require_once('includes/Event_Data.php');
    require_once('includes/Filter.php');
    require_once('includes/Zone.php');

    //Монитор используем для расчета ширины и высоты (наложение зон детекции). ?????Конечно нужно смотреть размеры события, но у нас их пока нет. 
    //ВРЕМЕННО!!! Поэтому ориентируемся пока на монитор.
    $monitorId = $options['monitorId'];
//    $monitorId = 5;
    $monitor = new ZM\Monitor($monitorId);

    //$Event = new ZM\Event($eid);

    if (!$monitor->canView()) {
      $view = 'error';
      return;
    }


    $blockRatioControl = ($basename == "montage") ? '<div id="ratioControl'.$monitorId.'" class="ratioControl hidden"><select name="ratio'.$monitorId.'" id="ratio'.$monitorId.'" class="select-ratio chosen" data-on-change="changeRatio">
</select></div>' : '';
    $html = '
      <div id="m'. $monitorId . '" class="grid-monitor grid-stack-item" gs-id="'. $monitorId . '" gs-w="12" gs-auto-position="true">
        ' . $blockRatioControl . '
        <div class="grid-stack-item-content">
          <div id="monitor'. $monitorId . '" data-id="'.$monitorId.'" class="monitor"
            title="Shift+Click to Zoom, Click+Drag to Pan &#013;Ctrl+Click to Zoom out, Ctrl+Shift+Click to Zoom out completely"
            >

			<canvas id="canvas-monitor'.$monitorId.'" class="canvas-monitor" style="position: absolute; z-index: 2; left: 0;
    /* right: 50px; */
    width: 100%;
    /*height: 100%;*/"></canvas>


            <div
              id="imageFeed'. $monitorId .'"
              class="monitorStream imageFeed"
              data-monitor-id="'. $monitorId .'"
              data-width="'. '$this->ViewWidth()' .'"
              data-height="'. '$this->ViewHeight()' .'" style="'.
#(($options['width'] and ($options['width'] != '0px')) ? 'width: '.$options['width'].';' : '').
#(($options['height'] and ($options['height'] != '0px')) ? 'height: '.$options['height'].';' : '').
            '">';
              $html .= '
                <div id="button_zoom'.$monitorId.'" class="button_zoom hidden">
                  <button id="btn-zoom-in'.$monitorId.'" class="btn btn-zoom-in hidden" data-on-click="panZoomIn" title="'.translate('Zoom IN').'"><span class="material-icons md-36">add</span></button>
                  <button id="btn-zoom-out'.$monitorId.'" class="btn btn-zoom-out hidden" data-on-click="panZoomOut" title="'.translate('Zoom OUT').'"><span class="material-icons md-36">remove</span></button>
                  <div class="block-button-center">
                    <button id="btn-fullscreen'.$monitorId.'" class="btn btn-fullscreen" title="'.translate('Open full screen').'"><span class="material-icons md-30">fullscreen</span></button>
                    <button id="btn-view-watch'.$monitorId.'" class="btn btn-view-watch" title="'.translate('Open watch page').'"><span class="material-icons md-30">open_in_new</span></button>
                    <button id="btn-view-event'.$monitorId.'" class="btn btn-view-event" title="'.translate('Open event page').'"><span class="material-icons md-30">event</span></button>
                    <button id="btn-edit-monitor'.$monitorId.'" class="btn btn-edit-monitor" title="'.translate('Edit monitor').'"><span class="material-icons md-30">edit</span></button>
                  </div>
                </div>
                <div class="zoompan">';

    //$streamSrc = 'no data'; //Когда только формируем страницу, событий еще нет...
    $eid = $options['lastEvent']['Id'];
  //<img src="?view=image&eid='. $Event->Id().'&amp;fid=alarm&width='.ZM_WEB_LIST_THUMB_WIDTH.'" width="'.ZM_WEB_LIST_THUMB_WIDTH.'" alt="First alarmed frame" title="First alarmed frame"/>
    $Event = new ZM\Event($eid);
    $fid = null;
    if (file_exists($Event->Path().'/alarm.jpg') && filesize($Event->Path().'/alarm.jpg') > 0) {
      $fid = 'alarm';
    } else if (file_exists($Event->Path().'/objdetect.jpg') && filesize($Event->Path().'/objdetect.jpg') > 0) {
      $fid = 'objdetect';
    } else if (file_exists($Event->Path().'/snapshot.jpg') && filesize($Event->Path().'/snapshot.jpg') > 0) {
      $fid = 'snapshot';
    } else {
      //!!! IMPORTANT It is necessary to check the file with the event! It may not be there due to a failure. But something needs to be displayed!
      if ($Event->file_exists() && $Event->file_size() > 0) {
        require_once('includes/Frame.php');
        $numFrame = 1; //Номер фрейма, который получаем.
        $Frame = ZM\Frame::find_one(array('EventId'=>$eid, 'FrameId'=>$numFrame));
        if ($Frame) {
          $fid = $numFrame;
        }
      }
    }
    $width = intval($options['lastEvent']['Width'] / 100 * $options['scale']);
    if ($fid) {
      $streamSrc = '?view=image&eid='. $eid.'&fid='.$fid.'&width='.$width;
    } else {
      $streamSrc = '?view=image&path='. 'graphics/no-frame.jpg'.'&width='.$width;
    }
    //!!! ВАЖНО В данных о событиях есть Height, Width, значит можно рассчитать пропорции и выводить ЗАГЛУШКУ с высотой, что бы не портить расположение мониторов на экране.

    //src="http://192.168.111.244:30006/zm/cgi-bin/nph-zms?mode=jpeg&frame=8595703890&scale=51&rate=100&maxfps=5&replay=none&source=event&event=437496&rand=1719528464&auth=ad5fc1d3b184e2f4c100e1a60f7a5a80"
    //src="http://192.168.111.244:30006/zm/cgi-bin/nph-zms?mode=single&frame=8595703890&scale=51&rate=100&maxfps=5&replay=none&source=event&event=437496&rand=1719528464&auth=ad5fc1d3b184e2f4c100e1a60f7a5a80"
	//$streamSrc = $Event->getStreamSrc(array('mode'=>'jpeg', 'frame'=>$fid, 'scale'=>($scale > 0 ? $scale : 100), 'rate'=>$rate, 'maxfps'=>$maxFPS, 'replay'=>$replayMode),'&');
    //НЕ ЗАБЫТЬ убрать анализ "if (decodeURI(img.src).indexOf('no data') !== -1) {" из montage.js !!!
	//Но надо что-то выводить, например последний фрейм последнего события.
  $html .= getImageStreamHTML('evtStream'.$monitorId, $streamSrc,
//    ($scale ? reScale($Event->Width(), $scale).'px' : '100%'),
//    ($scale ? reScale($Event->Height(), $scale).'px' : 'auto'),
    'auto',
    'auto',
//    validHtmlStr($Event->Name()));
    '');
    








    if (isset($options['zones']) and $options['zones']) {
//      $html .= '<svg class="zones" id="zones'.$monitorId.'" viewBox="0 0 '.$this->ViewWidth().' '.$this->ViewHeight() .'" preserveAspectRatio="none">'.PHP_EOL;
//      $html .= '<svg class="zones" id="zones'.$monitorId.'" viewBox="0 0 '.'2000'.' '.'2000' .'" preserveAspectRatio="none">'.PHP_EOL;
      $html .= '<svg class="zones" id="zones'.$monitorId.'" viewBox="0 0 '.$monitor->ViewWidth().' '.$monitor->ViewHeight() .'" preserveAspectRatio="none">'.PHP_EOL;
      foreach (ZM\Zone::find(array('MonitorId'=>$monitorId), array('order'=>'Area DESC')) as $zone) {
        $html .= $zone->svg_polygon();
      } // end foreach zone
      $html .= '
  Sorry, your browser does not support inline SVG
</svg>
';
    } # end if showZones
    $html .= PHP_EOL.'</div><!--.zoompan--></div><!--monitorStream-->'.PHP_EOL;
    if (isset($options['state']) and $options['state']) {
    //if ((!ZM_WEB_COMPACT_MONTAGE) && ($this->Type() != 'WebSite')) {
      $html .= self::getEventInfoHTML($monitor);
    }

    $html .= PHP_EOL.'</div></div><!--.grid-stack-item-content--></div><!--.grid-stack-item-->'.PHP_EOL;
    return $html;
  } // end buildEventHTML

  public static function queryRequest($filter, $search, $advsearch, $sort, $offset, $order, $limit) {
    //Temporarily not used
    //Part of the code was moved from usr\share\zoneminder\www\ajax\events.php
    /*
    global $dateTimeFormatter;
    $data = array(
      'total'   =>  0,
      'totalNotFiltered' => 0,
      'rows'    =>  array(),
      'updated' =>  $dateTimeFormatter->format(time())
    );

    if (!$filter->test_pre_sql_conditions()) {
      ZM\Debug('Pre conditions failed, not doing sql');
      return $data;
    }

    // Put server pagination code here
    // The table we want our data from
    $table = 'Events';

    // The names of the dB columns in the events table we are interested in
    $columns = array('Id', 'MonitorId', 'StorageId', 'Name', 'Cause', 'StartDateTime', 'EndDateTime', 'Length', 'Frames', 'AlarmFrames', 'TotScore', 'AvgScore', 'MaxScore', 'Archived', 'Emailed', 'Notes', 'DiskSpace');

    // The names of columns shown in the event view that are NOT dB columns in the database
    $col_alt = array('Monitor', 'Tags', 'Storage');

    if ( $sort != '' ) {
      if (!in_array($sort, array_merge($columns, $col_alt))) {
        ZM\Error('Invalid sort field: ' . $sort);
        $sort = '';
      } else if ( $sort == 'Tags' ) {
         $sort = 'T.Name';
      } else if ( $sort == 'Monitor' ) {
        $sort = 'M.Name';
      } else if ($sort == 'EndDateTime') {
        if ($order == 'ASC') {
          $sort = 'E.EndDateTime IS NULL, E.EndDateTime';
        } else {
          $sort = 'E.EndDateTime IS NOT NULL, E.EndDateTime';
        }
      } else {
        $sort = 'E.'.$sort;
      }
    }

    $values = array();
    $likes = array();
    // ZM\Error($filter->sql());
    $where = $filter->sql()?' WHERE ('.$filter->sql().')' : '';
    $has_post_sql_conditions = count($filter->post_sql_conditions());


    $col_str = '
    E.*, 
    UNIX_TIMESTAMP(E.StartDateTime) AS StartTimeSecs, 
    CASE WHEN E.EndDateTime IS NULL THEN (SELECT NOW()) ELSE E.EndDateTime END AS EndDateTime, 
    CASE WHEN E.EndDateTime IS NULL THEN (SELECT UNIX_TIMESTAMP(NOW())) ELSE UNIX_TIMESTAMP(EndDateTime) END AS EndTimeSecs, 
    M.Name AS Monitor,
    GROUP_CONCAT(T.Name SEPARATOR ", ") AS Tags';

    $sql = 'SELECT '.$col_str.' FROM `Events` AS E 
    INNER JOIN Monitors AS M ON E.MonitorId = M.Id 
    LEFT JOIN Events_Tags AS ET ON E.Id = ET.EventId 
    LEFT JOIN Tags AS T ON T.Id = ET.TagId 
    '.$where.' 
    GROUP BY E.Id 
    '.($sort?' ORDER BY '.$sort.' '.$order:'');

    if ((int)($filter->limit()) and !$has_post_sql_conditions) {
      $sql .= ' LIMIT '.(int)($filter->limit());
    }

    $storage_areas = ZM\Storage::find();
    $StorageById = array();
    foreach ($storage_areas as $S) {
      $StorageById[$S->Id()] = $S;
    }

    $unfiltered_rows = array();
    $event_ids = array();

    ZM\Debug('Calling the following sql query: ' .$sql);
    $query = dbQuery($sql, $values);
    if (!$query) {
      ajaxError(dbError($sql));
      return;
    }
    while ($row = dbFetchNext($query)) {
      if ($has_post_sql_conditions) {
        $event = new ZM\Event($row);
        $event->remove_from_cache();
        if (!$filter->test_post_sql_conditions($event)) {
          continue;
        }
      }
      $event_ids[] = $row['Id'];
      $unfiltered_rows[] = $row;
    } # end foreach row

    # Filter limits come before pagination limits.
    if ($filter->limit() and ($filter->limit() > count($unfiltered_rows))) {
      ZM\Debug("Filtering rows due to filter->limit " . count($unfiltered_rows)." limit: ".$filter->limit());
      $unfiltered_rows = array_slice($unfiltered_rows, 0, $filter->limit());
    }

    ZM\Debug('Have ' . count($unfiltered_rows) . ' events matching base filter.');

    $filtered_rows = null;

    if (count($advsearch) or $search != '') {
      $search_filter = new ZM\Filter();
      $search_filter = $search_filter->addTerm(array('cnj'=>'and', 'attr'=>'Id', 'op'=>'IN', 'val'=>$event_ids));

      // There are two search bars in the log view, normal and advanced
      // Making an exuctive decision to ignore the normal search, when advanced search is in use
      // Alternatively we could try to do both
      if (count($advsearch)) {
        $terms = array();
        foreach ($advsearch as $col=>$text) {
          $terms[] = array('cnj'=>'and', 'attr'=>$col, 'op'=>'LIKE', 'val'=>$text);
        } # end foreach col in advsearch
        $terms[0]['obr'] = 1;
        $terms[count($terms)-1]['cbr'] = 1;
        $search_filter->addTerms($terms);
      } else if ($search != '') {
        $search = '%' .$search. '%';
        $terms = array();
        foreach ($columns as $col) {
          $terms[] = array('cnj'=>'or', 'attr'=>$col, 'op'=>'LIKE', 'val'=>strtolower($search), 'collate'=>'utf8mb4_general_ci');
        }
        $terms[0]['obr'] = 1;
        $terms[0]['cnj'] = 'and';
        $terms[count($terms)-1]['cbr'] = 1;
        $search_filter = $search_filter->addTerms($terms, array('obr'=>1, 'cbr'=>1, 'op'=>'OR'));
      } # end if search

      $sql = 'SELECT '.$col_str.' FROM `Events` AS E 
      INNER JOIN Monitors AS M ON E.MonitorId = M.Id 
      LEFT JOIN Events_Tags AS ET ON E.Id = ET.EventId 
      LEFT JOIN Tags AS T ON T.Id = ET.TagId 
      WHERE '.$search_filter->sql().' 
      GROUP BY E.Id 
      ORDER BY ' .$sort. ' ' .$order;

      $filtered_rows = dbFetchAll($sql);
      ZM\Debug('Have ' . count($filtered_rows) . ' events matching search filter: '.$sql);
    } else {
      $filtered_rows = $unfiltered_rows;
    } # end if search_filter->terms() > 1

    if ($limit and ($limit < count($filtered_rows))) {
      ZM\Debug("Filtering rows due to limit rows: " . count($filtered_rows)." offset: $offset limit: $limit");
      $filtered_rows = array_slice($filtered_rows, $offset, $limit);
    }

    $returned_rows = array();
    foreach ($filtered_rows as $row) {
      $event = new ZM\Event($row);
      $event->remove_from_cache();
      if (!$event->canView()) continue;
      if ($event->Monitor()->Deleted()) continue;

      $scale = intval(5*100*ZM_WEB_LIST_THUMB_WIDTH / $event->Width());
      $imgSrc = $event->getThumbnailSrc(array(), '&amp;');
      $streamSrc = $event->getStreamSrc(array(
        'mode'=>'jpeg', 'scale'=>$scale, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'replay'=>'single', 'rate'=>'400'), '&amp;');
      //$streamSrc = $event->getStreamSrc(array(
      //  'mode'=>'mpeg', 'scale'=>$scale, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'replay'=>'single', 'rate'=>'400'), '&amp;');

      // Modify the row data as needed
      $row['imgHtml'] = '<img id="thumbnail' .$event->Id(). '" src="' .$imgSrc. '" alt="Event '.$event->Id().'" width="' .validInt($event->ThumbnailWidth()). '" height="' .validInt($event->ThumbnailHeight()).'" stream_src="' .$streamSrc. '" still_src="' .$imgSrc. '" loading="lazy" />';
      $row['imgWidth'] = validInt($event->ThumbnailWidth());
      $row['imgHeight'] = validInt($event->ThumbnailHeight());

      $row['Name'] = validHtmlStr($row['Name']);
      $row['Archived'] = $row['Archived'] ? translate('Yes') : translate('No');
      $row['Emailed'] = $row['Emailed'] ? translate('Yes') : translate('No');
      $row['Cause'] = validHtmlStr($row['Cause']);
      $row['Tags'] = validHtmlStr($row['Tags']);
      $row['Storage'] = ( $row['StorageId'] and isset($StorageById[$row['StorageId']]) ) ? $StorageById[$row['StorageId']]->Name() : 'Default';
      $row['Notes'] = nl2br(htmlspecialchars($row['Notes']));
      $row['DiskSpace'] = human_filesize($event->DiskSpace());
      $returned_rows[] = $row;
    } # end foreach row matching search

    $data['rows'] = &$returned_rows;

    # totalNotFiltered must equal total, except when either search bar has been used
    $data['totalNotFiltered'] = count($unfiltered_rows);
    if ( $search != '' || count($advsearch) ) {
      $data['total'] = count($filtered_rows);
    } else {
      $data['total'] = $data['totalNotFiltered'];
    }
    ZM\Debug("Done");
    return $data;
    */
  }

  /*
  * Getting the minimum start date for event recording for a group of monitors
  */
  public static function queryMinData($filter, $monitorsId) {
    $sqlString = self::buildQueryString($filter, $monitorsId, $startDateTime='', $endDateTime='', $actionRange='minData', $fullResponse=false);
    $result = dbFetch($sqlString, $col='minData')[0];
    return $result;
  }

  /*
  * $actionRange - 'range', 'first', 'last', 'prev', 'next'
  * $endDateTime - only required for $actionRange = 'range'
  * $maxFPS - FPS at which the video will be played.
  */
  public static function queryEvents($filter, $monitorsId, $startDateTime, $endDateTime, $resolution, $action, $actionRange, $maxFPS = null) {
  //Part of the code has been moved from ajax\events.php

    if (!$maxFPS) {
      $maxFPS = self::$options['maxfps'];
    }

    $startDateTimeSec = strtotime($startDateTime);
    $endDateTimeSec = strtotime($endDateTime);
    //require_once('includes/Filter.php');
    $currentTimeEventMonitors = []; //Store the end time of the event for the current monitor of their SQL sample, so that $resolution can be used to exclude nearby events

    $fullResponse = ($action == 'queryEventsForMonitor' && $actionRange == 'last') ? true : false;

    $eventsSql = self::buildQueryString($filter, $monitorsId, $startDateTime, $endDateTime, $actionRange, $fullResponse);


    $EventsById = array();
    $longEvents = array();
    $streamSrc = array();

    $events = dbFetchAll($eventsSql);
    $result = [];
$start_ = microtime(true);

    $i = 1;
    //if ($resQuery) {
    if ($events) {
      $count_array = count($events);
      //$resolutionNew = $resolution * ($count_array / 3000);
      if ($endDateTimeSec - $startDateTimeSec < (60 * 60 * 3)) {
        //В Timeline отображается 3х часовой промежуток
        $resolutionNew = 1;
      } else if ($endDateTimeSec - $startDateTimeSec < (60 * 60 * 24)) {
        //В Timeline отображается суточный промежуток
        $resolutionNew = $resolution * 10;
      } else if ($endDateTimeSec - $startDateTimeSec < (60 * 60 * 24 * 30)) {
        //В Timeline отображается месячный промежуток
        $resolutionNew = $resolution * 20;
      } else {
        $resolutionNew = $resolution * 30;
      }
      if ($count_array < self::$maxEvents) {
        //Ограничим выборку
        $result['tooManyEvents'] = false;
        for($i = 0; $i < $count_array; $i++) {
          $event = $events[$i];
          if ($action == 'queryNextEventForMonitor') {
            //Не требуется дополнительных проверок.
            $addEvent = true;
          } else {
            //$eventEndTimeSecs = strtotime($event['EndDateTime']);
            //$eventStartTimeSecs = strtotime($event['StartDateTime']);
            $eventEndTimeSecs = $event['EndTimeSecs'];
            $eventStartTimeSecs = $event['StartTimeSecs'];
            $addEvent = false;
            if (!isset($currentTimeEventMonitors[$event['MonitorId']])) {
              //Первое событие для монитора.
              $addEvent = true;
              $currentTimeEventMonitors[$event['MonitorId']] = 1;
            } else if ($eventEndTimeSecs > ($resolutionNew) + $currentTimeEventMonitors[$event['MonitorId']] ) {
            //} else if ($eventStartTimeSecs > ($resolutionNew) + $currentTimeEventMonitors[$event['MonitorId']] ) {
              // Прореживаем последующие события.
              $addEvent = true;
              $currentTimeEventMonitors[$event['MonitorId']] = $eventEndTimeSecs;
            } else if ($event['Archived']) {
              //Архивные выводим все.
              $addEvent = true;
              $currentTimeEventMonitors[$event['MonitorId']] = $eventEndTimeSecs;
            } else {
            }
          }
          if ($addEvent) {
            if ($action == 'queryEventsForMonitor' || $action == 'queryNextEventForMonitor') {
              //Необходимо собрать SRC строку
              //$eid = 427483; //Id камеры = 8
              $eid = $event['Id'];
              $objEvent = new ZM\Event($eid);
              $monitor = $objEvent->Monitor();
              /* +++ ВРЕМЕННО */
              $rate = reScale(RATE_BASE, $monitor->DefaultRate(), ZM_WEB_DEFAULT_RATE);
              $replayMode = 'none';
              /* --- ВРЕМЕННО */
              $scale = self::scaleCalculation($event['Width']);
              //Найдем FID с которого запустить просмотр.
              //Сбойное событие может не иметь длинну! В этом случае считаем FPS=1, что бы не было ошибки при делении.
              $FPSEvent = ($event['Length'] > 0) ? $event['Frames'] / $event['Length'] : 1;
              if ($action == 'queryNextEventForMonitor') {
                $fid = 1;
              } else {
                $fid = intval($FPSEvent * ($startDateTimeSec - $event['StartTimeSecs']));
              }
//            $streamSrc[$eid] = $objEvent->getStreamSrc(array('mode'=>'jpeg', 'frame'=>$fid, 'scale'=>($scale > 0 ? $scale : 100), 'rate'=>$rate, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'replay'=>$replayMode),'&amp;');
//            $streamSrc[$eid] = $objEvent->getStreamSrc(array('mode'=>'jpeg', 'frame'=>$fid, 'scale'=>($scale > 0 ? $scale : 100), 'rate'=>$rate, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'replay'=>$replayMode),'&');
              if (!$maxFPS || $maxFPS == 'Unlimited') $maxFPS = ZM_WEB_VIDEO_MAXFPS;
              /*РАБОЧИЙ вариант*/ //$streamSrc[$eid] = $objEvent->getStreamSrc(array('mode'=>'jpeg', 'frame'=>$fid, 'scale'=>($scale > 0 ? $scale : 100), 'rate'=>$rate, 'maxfps'=>$maxFPS, 'replay'=>$replayMode, 'connkey'=>generateConnKey()),'&');
              /*БЕЗ FID*/ $streamSrc[$eid] = $objEvent->getStreamSrc(array('mode'=>'jpeg', 'scale'=>($scale > 0 ? $scale : 100), 'rate'=>$rate, 'maxfps'=>$maxFPS, 'replay'=>$replayMode, 'connkey'=>generateConnKey()),'&');
              /*Попытка через TIME*/ //$streamSrc[$eid] = $Event->getStreamSrc(array('mode'=>'jpeg', 'time'=>$startDateTimeSec, 'scale'=>($scale > 0 ? $scale : 100), 'rate'=>$rate, 'maxfps'=>$maxFPS, 'replay'=>$replayMode, 'connkey'=>generateConnKey()),'&');
              //$streamSrc[$eid] = $objEvent->getStreamSrc(array('mode'=>'mpeg', 'frame'=>$fid, 'scale'=>($scale > 0 ? $scale : 100), 'rate'=>$rate, 'maxfps'=>$maxFPS, 'replay'=>$replayMode),'&');
            }
            unset($event['StartTimeSecs']); //Уменьшим передаваемых данных
            unset($event['EndTimeSecs']);
            $EventsById[] = $event; 
          }
        }
      } else {
        $result['tooManyEvents'] = true;
      }
    }

/*
    if (is_array($filter)) {
      $filter['QQQ'] = ["df, sad,выап", "aaaaaaa"];
      foreach ($filter as $name => $value) { //ДЛЯ ТЕСТА
        $result['filter'.$name] = $value;
        if (is_array($value)) {
          $result['__filter'.$name] = self::implodeWithQuotes($value);
        } else {
          $result['__filter'.$name] = $value;
        }
      }
    } else {
        //$result['filter_STRING'] = $value;
    }
*/
    if (isset($FPSEvent)) $result['FPSEvent'] = $FPSEvent; //ДЛЯ ТЕСТА
    $result['filter'] = $filter; //ДЛЯ ТЕСТА
    $result['_action'] = $action; //ДЛЯ ТЕСТА
    $result['_actionRange'] = $actionRange; //ДЛЯ ТЕСТА
    $result['eventsSql'] = $eventsSql; //ДЛЯ ТЕСТА
//    $result['SQL'] = $events; //ДЛЯ ТЕСТА
    $result['SQL_COUNT'] = count($events); //ДЛЯ ТЕСТА
    $result['actionRange'] = $actionRange; //ДЛЯ ТЕСТА
//    $result['->fetch->event'] = $resQuery->fetch(PDO::FETCH_ASSOC); //ДЛЯ ТЕСТА
    $result['startDateTime'] = $startDateTime; //ДЛЯ ТЕСТА ???
    $result['endDateTime'] = $endDateTime; //ДЛЯ ТЕСТА ???
    $result['deltaDateTimeSec'] = $endDateTimeSec - $startDateTimeSec; //ДЛЯ ТЕСТА ???
    $result['resolution'] = $resolution; //ДЛЯ ТЕСТА ???
    //$result['event_Length'] = $event['Length']; //ДЛЯ ТЕСТА ???
    //$result['event_Frames'] = $event['Frames']; //ДЛЯ ТЕСТА ???
    $result['_microtime'] = microtime(true) - $start_; //ДЛЯ ТЕСТА
    $result['currentTimeEventMonitors'] = $currentTimeEventMonitors; //ДЛЯ ТЕСТА ???

    $result['event'] = (isset($event)) ? $event : null; //ЗАЧЕМ??? Может не используем ???
    $result['actionRange'] = $actionRange;
    $result['streamSrc'] = &$streamSrc;
    $result['events'] = &$EventsById;
    $result['allEventCount'] = count($events);

    return $result;
  }

  /*
  * $actionRange - 'range', 'first', 'last', 'prev', 'next', 'minData'
  * $endDateTime - only required for $actionRange = 'range'
  * $fullResponse - if 'true' then the response will contain all columns of the table. This will SLOW DOWN the query! For now it is only used when receiving the last event for the monitor.
  */
  public static function buildQueryString($filter, array $monitorsId=[], $startDateTime, $endDateTime, string $actionRange, bool $fullResponse=false) {
    $oneMonitors = (count($monitorsId) == 1) ? true : false;
    $where = ' WHERE';
    if (count($monitorsId) == 0) {
      $where .= ' 1=1';
    } else {
      $where .= ($oneMonitors) ? ' E.MonitorId='.$monitorsId[0] : ' (E.MonitorId IN ('.implode(',', $monitorsId).'))';
    }
    $select = 'SELECT';
    $join = '';
    $from = ' FROM Events AS E';
    $group = ''; 
    $order = '';
    $limit = '';

/*
++++++++++++++++
 SELECT E.Id, E.MonitorId, E.StartDateTime, E.EndDateTime, E.Cause, E.Length, E.Frames, E.Width, E.Archived
        ,UNIX_TIMESTAMP(E.StartDateTime) AS StartTimeSecs,
        #CASE WHEN E.EndDateTime IS NULL THEN NOW() ELSE E.EndDateTime END AS _EndDateTime,
        CASE WHEN E.EndDateTime IS NULL THEN UNIX_TIMESTAMP(NOW()) ELSE UNIX_TIMESTAMP(E.EndDateTime) END AS EndTimeSecs
       FROM Events AS E 
       
	INNER JOIN (
		SELECT
			MonitorId,
			MAX(Id) AS LastEvent
		FROM
			Events
        GROUP BY
			MonitorId) AS top ON (
                E.MonitorId = top.MonitorId
           )
        WHERE (E.MonitorId IN (5,6,22,33,37))
	            AND (
                  (E.Id = top.LastEvent AND E.EndDateTime IS NULL AND E.StartDateTime BETWEEN '2024-10-14 00:00:00' AND '2024-10-14 00:59:00') 
                  OR 
                  (E.EndDateTime >='2024-10-14 00:00:00' AND E.StartDateTime <='2024-10-14 00:59:00')
                )
        ORDER BY E.StartDateTime ASC
 -------------
*/

    if ($actionRange == 'range') {
      #SELECT Id, MonitorId, StartDateTime, EndDateTime, Cause 
      #FROM Events 
      #WHERE EndDateTime > '2024-06-18 10:08:57'
        #AND StartDateTime < '2024-06-20 10:08:57' 
        #AND MonitorId IN (5,6,20,15,33,37,38,41);
      $select .= ' E.Id, E.MonitorId, E.StartDateTime, E.EndDateTime, E.Cause, E.Length, E.Frames, E.Width, E.Archived';
      $select .= '
        ,UNIX_TIMESTAMP(E.StartDateTime) AS StartTimeSecs,
        CASE WHEN E.EndDateTime IS NULL THEN (SELECT NOW()) ELSE E.EndDateTime END AS EndDateTime,
        CASE WHEN E.EndDateTime IS NULL THEN (SELECT UNIX_TIMESTAMP(NOW())) ELSE UNIX_TIMESTAMP(E.EndDateTime) END AS EndTimeSecs
      ';
      $where .= " AND (";
      //$where .= " (E.EndDateTime >='".$startDateTime."'";
      //$where .= " AND E.StartDateTime <='".$endDateTime."')";
      //The last event that has not yet ended.
      $join .= "
        INNER JOIN (
          SELECT
            MonitorId,
            MAX(Id) AS LastEvent
          FROM
            Events
          GROUP BY
            MonitorId) AS top ON (E.MonitorId = top.MonitorId)
      ";
      if ($startDateTime == $endDateTime) {
        // We receive an event that should be played at a specific moment.
        $where .= "
          (E.Id = top.LastEvent AND E.EndDateTime IS NULL AND E.StartDateTime <='".$endDateTime."') 
          OR 
          (E.EndDateTime >='".$startDateTime."' AND E.StartDateTime <='".$endDateTime."')
        ";
        // Получаем ближайшую дату к дате по которой кликнули, т.к. могут быть сбойные события, которые не имеют даты окончания и могут "перекрывать" остальные события.
        $order .= ' ORDER BY E.StartDateTime DESC';
      } else {
        $where .= "
          (E.Id = top.LastEvent AND E.EndDateTime IS NULL AND E.StartDateTime BETWEEN '".$startDateTime."' AND '".$endDateTime."') 
          OR 
          (E.EndDateTime >='".$startDateTime."' AND E.StartDateTime <='".$endDateTime."')
        ";
        $order .= ' ORDER BY E.StartDateTime ASC, E.MonitorId ASC';
      }
      $where .= ")";
    } else if ($actionRange == 'first') {
      //Temporarily not used
      if ($oneMonitors) {
      } else {
      }
    } else if ($actionRange == 'prev') {
      if ($oneMonitors) {
        #SELECT * 
        #FROM Events 
        #WHERE StartDateTime<'2024-06-20 11:08:57' 
          #AND MonitorId=5 
        #ORDER BY StartDateTime DESC 
        #LIMIT 1;
        $select .= ' *';
        $where .= " AND E.StartDateTime <='".$startDateTime."'";
        $order .= ' ORDER BY E.StartDateTime DESC';
        $limit .= ' LIMIT 1';
      } else {
        #SELECT MonitorId, StartDateTime, MAX(Id) AS eventId
        #FROM Events
        #WHERE ((StartDateTime<'2024-06-20 11:08:57')
          #AND (MonitorId IN (5,6,20,15,33,37,38,41)))
        #GROUP BY MonitorId
        // StartDateTime в ответе будет НЕ правильный!
        $select .= ' E.MonitorId, ANY_VALUE(E.StartDateTime), MAX(E.Id) AS eventId';
        $where .= " AND E.StartDateTime <='".$startDateTime."'";
        $group .= ' GROUP BY E.MonitorId';
      }
    } else if ($actionRange == 'last') {
      #SELECT MonitorId, StartDateTime, MAX(Id) AS eventId
      #FROM Events
      #WHERE MonitorId IN (5,6,20,15,33,37,38,41)
      #GROUP BY MonitorId
      // StartDateTime в ответе будет НЕ правильный! Но мы его и не будем включать в выборку. 
      // Нам достаточно MAX(E.Id) - это и будет последнее соьытие.
#      $select .= ' E.MonitorId, ANY_VALUE(E.StartDateTime), MAX(E.Id) AS eventId';
      $select .= ' E.MonitorId, MAX(E.Id) AS eventId';
      #$where .= " AND E.EndDateTime IS NOT NULL"; //Если событие еще не окончено, то из него почему-то не возможно получить "Frame"
      $group .= ' GROUP BY E.MonitorId';
    } else if ($actionRange == 'next') {
      if ($oneMonitors) {
        #SELECT * 
        #FROM Events 
        #WHERE StartDateTime>'2024-06-20 11:08:57' 
          #AND MonitorId=5 
        #ORDER BY StartDateTime ASC 
        #LIMIT 1;
        $select .= ' *';
        $where .= " AND E.StartDateTime >='".$startDateTime."'";
        $order .= ' ORDER BY E.StartDateTime ASC';
        $limit .= ' LIMIT 1';
      } else {
        #SELECT MonitorId, StartDateTime, MIN(Id) AS eventId
        #FROM Events
        #WHERE ((StartDateTime>'2024-06-20 11:08:57')
          #AND (MonitorId IN (5,6,20,15,33,37,38,41)))
        #GROUP BY MonitorId
        // StartDateTime в ответе будет НЕ правильный!
        $select .= ' E.MonitorId, ANY_VALUE(E.StartDateTime), MIN(E.Id) AS eventId';
        $where .= " AND E.StartDateTime >='".$startDateTime."'";
        $group .= ' GROUP BY E.MonitorId';
      }
    } else if ($actionRange == 'minData') {
      #SELECT MonitorId, MIN(StartDateTime) AS minData
      #FROM Events
      #WHERE MonitorId IN (5,6,20,15,33,37,38,41)
      $select .= ' ANY_VALUE(E.MonitorId), MIN(E.StartDateTime) AS minData';
    }

    /* IMPORTANT! Here we process the filter used by IgorA100 instead of the main one. */
    if (is_array($filter)) {
      foreach ($filter as $name => $value) {
        if ($name == 'Archived') {
          if (isset($value) && $value !== '') {
            $where .= ' AND E.Archived ='.$value;
          }
        } else if ($name == 'Notes') {
          $whereArr = '';
          if (isset($value) && $value !== '') {
            if (is_array($value)) {
              $whereArr .= ' AND ('; 
              foreach($value as $val){
                $whereArr .= ' E.Notes LIKE \'%'.$val.'%\' OR';
              }
            } else {
              $where .= ' AND E.Notes LIKE \'%'.$val.'%\'';
            }
          }
          if ($whereArr) $whereArr = rtrim($whereArr, 'OR') . ')';
          $where .= $whereArr;
        } else if ($name == 'Tags') {
          if (isset($value) && $value !== '') {
            $select .= ' ,ET.EventId, ET.TagId';
            $where .= ' AND ET.EventId IS NOT NULL';
            if (is_array($value)) {
              $where .= ' AND ET.TagId IN ('.implode(",",$value).')';
            } else {
              $where .= ' AND ET.TagId="'.$value.'"';
            }
            $join .= '
              LEFT JOIN Events_Tags AS ET ON E.Id = ET.EventId
              LEFT JOIN Tags AS T ON T.Id = ET.TagId 
            ';
            //$group .= ($group) ? ', ET.EventId' : ' GROUP BY ET.EventId';
          }
        }
      }
    }
    
    $sql = $select . $from . $join . $where . $order . $group . $limit;

    if ($fullResponse) {
      //A long, but forced and rare query that returns all columns of the table
      $sql = '
      SELECT E.Id, E.MonitorId, E.Width, E.Height, E.Length, E.Frames, E.Archived, E.Cause, E.StartDateTime,
        UNIX_TIMESTAMP(E.StartDateTime) AS StartTimeSecs,
        CASE WHEN E.EndDateTime IS NULL THEN (SELECT NOW()) ELSE E.EndDateTime END AS EndDateTime,
        CASE WHEN E.EndDateTime IS NULL THEN (SELECT UNIX_TIMESTAMP(NOW())) ELSE UNIX_TIMESTAMP(E.EndDateTime) END AS EndTimeSecs
#      '.$from.' AS p1
      '.$from.'
      INNER JOIN
        ('
      . $sql . 
        ') AS p2
        ON E.MonitorId = p2.MonitorId
        AND E.Id = p2.eventId';
        $sql .= ' GROUP BY E.Id, E.MonitorId, E.Width, E.Height, E.Length, E.Frames, E.Archived, E.Cause, E.StartDateTime';
    }
    return $sql;
  }

  public static function findDataInEventsArray($monitorId, $eventsArray) {
    $item = null;
    foreach($eventsArray as $event) {
      if ($monitorId == $event['MonitorId']) {
        $item = $event;
        break;
      }
    }
    return $item;
  }
}
?>
