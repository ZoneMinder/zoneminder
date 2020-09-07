<?php
//
// ZoneMinder web Spain Spanish language file, $Date: 2011-07-26 21:30:00 +0100 (Wed, 06 Jul 2011) $, $Revision: 0002 $
// Copyright (C) 2001-2008 Philip Coombes
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

// ZoneMinder Spanish 'Spain' Translation by Rafael Medina

// Notes for Translators
// 0. Get some credit, put your name in the line above (optional)
// 1. When composing the language tokens in your language you should try and keep to roughly the
//   same length text if possible. Abbreviate where necessary as spacing is quite close in a number of places.
// 2. There are four types of string replacement
//   a) Simple replacements are words or short phrases that are static and used directly. This type of
//     replacement can be used 'as is'.
//   b) Complex replacements involve some dynamic element being included and so may require substitution
//     or changing into a different order. The token listed in this file will be passed through sprintf as
//     a formatting string. If the dynamic element is a number you will usually need to use a variable
//     replacement also as described below.
//   c) Variable replacements are used in conjunction with complex replacements and involve the generation
//     of a singular or plural noun depending on the number passed into the zmVlang function. See the 
//     the zmVlang section below for a further description of this.
//   d) Optional strings which can be used to replace the prompts and/or help text for the Options section
//     of the web interface. These are not listed below as they are quite large and held in the database
//     so that they can also be used by the zmconfig.pl script. However you can build up your own list
//     quite easily from the Config table in the database if necessary.
// 3. The tokens listed below are not used to build up phrases or sentences from single words. Therefore
//   you can safely assume that a single word token will only be used in that context.
// 4. In new language files, or if you are changing only a few words or phrases it makes sense from a 
//   maintenance point of view to include the original language file and override the old definitions rather
//   than copy all the language tokens across. To do this change the line below to whatever your base language
//   is and uncomment it.
//require_once( 'en_gb.php' );

// You may need to change the character set here, if your web server does not already
// do this by default, uncomment this if required.
//
// Example
header ('Content-type: text/html; charset=utf-8');

// You may need to change your locale here if your default one is incorrect for the
// language described in this file, or if you have multiple languages supported.
// If you do need to change your locale, be aware that the format of this function
// is subtlely different in versions of PHP before and after 4.3.0, see
// http://uk2.php.net/manual/en/function.setlocale.php for details.
// Also be aware that changing the whole locale may affect some floating point or decimal 
// arithmetic in the database, if this is the case change only the individual locale areas
// that don't affect this rather than all at once. See the examples below.
// Finally, depending on your setup, PHP may not enjoy have multiple locales in a shared 
// threaded environment, if you get funny errors it may be this.
//
// Examples
// setlocale( 'LC_ALL', 'en_GB' ); All locale settings pre-4.3.0
// setlocale( LC_ALL, 'en_GB' ); All locale settings 4.3.0 and after
// setlocale( LC_CTYPE, 'en_GB' ); Character class settings 4.3.0 and after
// setlocale( LC_TIME, 'en_GB' ); Date and time formatting 4.3.0 and after

// Simple String Replacements
$SLANG = array(
    '24BitColour'           => 'Color 24 bits',
    '32BitColour'          => '32 bit colour',          // Added - 2015-04-18
    '8BitGrey'              => 'Grises 8 bits',
    'Action'                => 'Acción',
    'Actual'                => 'Actual',
    'AddNewControl'         => 'Añadir nuevo control',
    'AddNewMonitor'         => 'Añadir nuevo monitor',
    'AddNewServer'         => 'Add New Server',         // Added - 2018-08-30
    'AddNewStorage'        => 'Add New Storage',        // Added - 2018-08-30
    'AddNewUser'            => 'Añadir nuevo usuario',
    'AddNewZone'            => 'Añadir nueva zona',
    'Alarm'                 => 'Alarma',
    'AlarmBrFrames'         => 'Marcos de alarma<br/>',
    'AlarmFrame'            => 'Marco de alarma',
    'AlarmFrameCount'       => 'Número de Marcos de alarma',
    'AlarmLimits'           => 'Límites de alarma',
    'AlarmMaximumFPS'       => 'Máximos MPS alarma',
    'AlarmPx'               => 'Px alarma',
    'AlarmRGBUnset'         => 'Debe establecer un color RGB para alarma',
    'AlarmRefImageBlendPct'=> 'Alarm Reference Image Blend %ge', // Added - 2015-04-18
    'Alert'                 => 'Alerta',
    'All'                   => 'Todo',
    'AnalysisFPS'          => 'Analysis FPS',           // Added - 2015-07-22
    'AnalysisUpdateDelay'  => 'Analysis Update Delay',  // Added - 2015-07-23
    'Apply'                 => 'Aplicar',
    'ApplyingStateChange'   => 'Aplicando cambio de estado...',
    'ArchArchived'          => 'Sólo archivados',
    'ArchUnarchived'        => 'Sólo no archivados',
    'Archive'               => 'Archivar',
    'Archived'              => 'Archivado',
    'Area'                  => 'Área',
    'AreaUnits'             => 'Área (px/%)',
    'AttrAlarmFrames'       => 'Marcos de alarma',
    'AttrArchiveStatus'     => 'Estado de archivo',
    'AttrAvgScore'          => 'Promed. señal',
    'AttrCause'             => 'Causa',
    'AttrDiskBlocks'        => 'Bloques del disco',
    'AttrDiskPercent'       => 'Porcentaje del disco',
    'AttrDiskSpace'        => 'Disk Space',             // Added - 2018-08-30
    'AttrDuration'          => 'Duración',
    'AttrEndDate'          => 'End Date',               // Added - 2018-08-30
    'AttrEndDateTime'      => 'End Date/Time',          // Added - 2018-08-30
    'AttrEndTime'          => 'End Time',               // Added - 2018-08-30
    'AttrEndWeekday'       => 'End Weekday',            // Added - 2018-08-30
    'AttrFilterServer'     => 'Server Filter is Running On', // Added - 2018-08-30
    'AttrFrames'            => 'Marcos',
    'AttrId'                => 'Id',
    'AttrMaxScore'          => 'Señal máxima',
    'AttrMonitorId'         => 'Id monitor',
    'AttrMonitorName'       => 'Nombre del monitor',
    'AttrMonitorServer'    => 'Server Monitor is Running On', // Added - 2018-08-30
    'AttrName'              => 'Nombre',
    'AttrNotes'             => 'Notas',
    'AttrStartDate'        => 'Start Date',             // Added - 2018-08-30
    'AttrStartDateTime'    => 'Start Date/Time',        // Added - 2018-08-30
    'AttrStartTime'        => 'Start Time',             // Added - 2018-08-30
    'AttrStartWeekday'     => 'Start Weekday',          // Added - 2018-08-30
    'AttrStateId'          => 'Run State',              // Added - 2018-08-30
    'AttrStorageArea'      => 'Storage Area',           // Added - 2018-08-30
    'AttrStorageServer'    => 'Server Hosting Storage', // Added - 2018-08-30
    'AttrSystemLoad'        => 'Carga del sistema',
    'AttrTotalScore'        => 'Señal total',
    'Auto'                  => 'Auto',
    'AutoStopTimeout'       => 'Autodetener tiempo de espera',
    'Available'             => 'Disponible',
    'AvgBrScore'            => 'Promed.<br/>señal',
    'Background'            => 'Segundo plano',
    'BackgroundFilter'      => 'Ejecutar filtro en segundo plano',
    'BadAlarmFrameCount'    => 'El número de marcos de alarma debe tener un número entero de uno o más',
    'BadAlarmMaxFPS'        => 'Máximos MPS de alarma debe ser un valor entero positivo o de punto flotante',
    'BadAnalysisFPS'       => 'Analysis FPS must be a positive integer or floating point value', // Added - 2015-07-22
    'BadAnalysisUpdateDelay'=> 'Analysis update delay must be set to an integer of zero or more', // Added - 2015-07-23
    'BadChannel'            => 'El canal debe estar establecido en un entero de cero o más',
    'BadColours'           => 'Target colour must be set to a valid value', // Added - 2015-04-18
    'BadDevice'             => 'El dispositivo debe tener un valor válido',
    'BadFPSReportInterval'  => 'El registro de intervalo de recuento búfer de MPS debe ser un entero de 100 o más',
    'BadFormat'             => 'El formato debe tener un valor válido',
    'BadFrameSkip'          => 'El número de omisión de marcos debe ser un entero de cero o más',
    'BadHeight'             => 'La altura debe tener un valor válido',
    'BadHost'               => 'El host debe tener una dirección ip o nombre de host válidos, no incluir http://',
    'BadImageBufferCount'   => 'El tamaño de búfer de imagen debe serun entero de 10 o más',
    'BadLabelX'             => 'La coordenada de la etiqueta X debe ser un entero de cero o más',
    'BadLabelY'             => 'La coordenada de la etiqueta Y debe ser un entero de cero o más',
    'BadMaxFPS'             => 'MPS máximos debe tener un valor entero positivo o de punto flotante',
    'BadMotionFrameSkip'    => 'Motion Frame skip count must be an integer of zero or more',
    'BadNameChars'          => 'Los nombre sólo pueden contener carácteres alfanuméricos, espacios además de guiones y guiones bajos',
    'BadPalette'            => 'La paleta debe tener un valor válido',
    'BadPath'               => 'La ruta debe tener un valo válido',
    'BadPort'               => 'El puerto debe ser un número válido',
    'BadPostEventCount'     => 'El número de imágenes post evento debe ser un entero de cero o más',
    'BadPreEventCount'      => 'El número de imágenes pre evento debe ser al menos cero, y menor que el tamaño búfer de imagen',
    'BadRefBlendPerc'       => 'El porcentaje de la referencia de mezcla debe ser un entero positivo',
    'BadSectionLength'      => 'La duración de la sección debe ser un entero de 30 o más',
    'BadSignalCheckColour'  => 'El color de verificación de señal debe ser una cadena de color RGB válida',
    'BadSourceType'        => 'Source Type \"Web Site\" requires the Function to be set to \"Monitor\"', // Added - 2018-08-30
    'BadStreamReplayBuffer' => 'La secuencia de búfer de reproducción debe ser un entero de cero o más',
    'BadWarmupCount'        => 'Los marcos de calentamiento deben ser un entero de cero o más',
    'BadWebColour'          => 'El color web debe ser una cadena de color web válida',
    'BadWebSitePath'       => 'Please enter a complete website url, including the http:// or https:// prefix.', // Added - 2018-08-30
    'BadWidth'              => 'El ancho debe tener un valor válido',
    'Bandwidth'             => 'Ancho de banda',
    'BandwidthHead'         => 'Bandwidth',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'                => 'Px gota',
    'BlobSizes'             => 'Tamaño gotas',
    'Blobs'                 => 'Gotas',
    'Brightness'            => 'Brillo',
    'Buffer'               => 'Buffer',                 // Added - 2015-04-18
    'Buffers'               => 'Búfers',
    'CSSDescription'       => 'Change the default css for this computer', // Added - 2015-04-18
    'CanAutoFocus'          => 'Puede enfocar automáticamente',
    'CanAutoGain'           => 'Puede usar ganancia automática',
    'CanAutoIris'           => 'Puede ajustar el iris automáticamente',
    'CanAutoWhite'          => 'Puede ajustar el balance de blancos automáticamente',
    'CanAutoZoom'           => 'Puede hacer zoom automáticamente',
    'CanFocus'              => 'Puede enfocar',
    'CanFocusAbs'           => 'Puede usar enfoque absoluto',
    'CanFocusCon'           => 'Puede usar enfoque continuo',
    'CanFocusRel'           => 'Puede usar enfoque relativo',
    'CanGain'               => 'Puede hacer ganancia ',
    'CanGainAbs'            => 'Puede hacer ganancia absoluta',
    'CanGainCon'            => 'Puede hacer ganancia contínua',
    'CanGainRel'            => 'Puede Hacer ganancia relativa',
    'CanIris'               => 'Puede ajustar el iris',
    'CanIrisAbs'            => 'Puede hacer iris Absoluto',
    'CanIrisCon'            => 'Puede hacer iris contínuo',
    'CanIrisRel'            => 'Puede hacer iris relativo',
    'CanMove'               => 'Puede moverse',
    'CanMoveAbs'            => 'Puede moverse de forma absoluta',
    'CanMoveCon'            => 'Puede moverse de forma continua',
    'CanMoveDiag'           => 'Puede moverse en diagonal',
    'CanMoveMap'            => 'Puede moverse de forma asignada',
    'CanMoveRel'            => 'Puede moverse de forma relativa',
    'CanPan'                => 'Puede desplazarse' ,
    'CanReset'              => 'Puede restablecerse',
	'CanReboot'             => 'Can Reboot',
    'CanSetPresets'         => 'Puede fefinir programaciones',
    'CanSleep'              => 'Puede dormirse',
    'CanTilt'               => 'Puede inclinarse',
    'CanWake'               => 'Puede despertarse',
    'CanWhite'              => 'Puede ajustar balance de blancos',
    'CanWhiteAbs'           => 'Puede hacer balance de blancos absoluto',
    'CanWhiteBal'           => 'Puede ajustar Bal.Blanc.',
    'CanWhiteCon'           => 'Puede hacer balance de blancos continuo',
    'CanWhiteRel'           => 'Puede hacer balance de blancos relativo',
    'CanZoom'               => 'Puede hacer zoom',
    'CanZoomAbs'            => 'Puede hacer zoom absoluto',
    'CanZoomCon'            => 'Puede hacer zoom continuo',
    'CanZoomRel'            => 'Puede hacer zoom relativo',
    'Cancel'                => 'Cancelar',
    'CancelForcedAlarm'     => 'Cancelar alarma forzada',
    'CaptureHeight'         => 'Altura de captura',
    'CaptureMethod'         => 'Método de captura',
    'CapturePalette'        => 'Paleta de captura',
    'CaptureResolution'    => 'Capture Resolution',     // Added - 2015-04-18
    'CaptureWidth'          => 'Ancho de captura',
    'Cause'                 => 'Causa',
    'CheckMethod'           => 'Método de comprobación de alarma',
    'ChooseDetectedCamera'  => 'Elegir cámara detectada',
    'ChooseFilter'          => 'Elegir filtro',
    'ChooseLogFormat'       => 'Elegir formato de registro',
    'ChooseLogSelection'    => 'Elegir selección de registro',
    'ChoosePreset'          => 'Elegir preprogramación',
    'Clear'                 => 'Limpiar',
    'CloneMonitor'         => 'Clone',                  // Added - 2018-08-30
    'Close'                 => 'Cerrar',
    'Colour'                => 'Color',
    'Command'               => 'Comando',
    'Component'             => 'Componente',
    'ConcurrentFilter'     => 'Run filter concurrently', // Added - 2018-08-30
    'Config'                => 'Config',
    'ConfiguredFor'         => 'Configurado para',
    'ConfirmDeleteEvents'   => '¿Seguro que desea borrar los eventos seleccionados?',
    'ConfirmPassword'       => 'Confirmar contraseña',
    'ConjAnd'               => 'y',
    'ConjOr'                => 'o',
    'Console'               => 'Consola',
    'ContactAdmin'          => 'Por favor contacte a su administrador para más detalles.',
    'Continue'              => 'Continuar',
    'Contrast'              => 'Contraste',
    'Control'               => 'Control',
    'ControlAddress'        => 'Dirección de control',
    'ControlCap'            => 'Capacidad de control',
    'ControlCaps'           => 'Capacidades de control',
    'ControlDevice'         => 'Controlar dispositivo',
    'ControlType'           => 'Tipo de control',
    'Controllable'          => 'Controlable',
    'Current'              => 'Current',                // Added - 2015-04-18
    'Cycle'                 => 'Ciclo',
    'CycleWatch'            => 'Visión ciclo',
    'DateTime'              => 'Fecha/Hora',
    'Day'                   => 'Día',
    'Debug'                 => 'Debug',
    'DefaultRate'           => 'Tasa por defecto',
    'DefaultScale'          => 'Escala por defecto',
    'DefaultView'           => 'Vista por defecto',
    'Deinterlacing'        => 'Deinterlacing',          // Added - 2015-04-18
    'Delay'                => 'Delay',                  // Added - 2015-04-18
    'Delete'                => 'Borrar',
    'DeleteAndNext'         => 'Borrar &amp; siguiente',
    'DeleteAndPrev'         => 'Borrar &amp; anterior',
    'DeleteSavedFilter'     => 'Borrar filtro guardado',
    'Description'           => 'Descripción',
    'DetectedCameras'       => 'Cámaras detectadas',
    'DetectedProfiles'     => 'Detected Profiles',      // Added - 2015-04-18
    'Device'                => 'Dispositivo',
    'DeviceChannel'         => 'Canal de dispositivo',
    'DeviceFormat'          => 'Formato de dispositivo',
    'DeviceNumber'          => 'Número de dispositivo',
    'DevicePath'            => 'Ruta de dispositivo',
    'Devices'               => 'Dispositivos',
    'Dimensions'            => 'Dimensiones',
    'DisableAlarms'         => 'Deshabilitar alarmas',
    'Disk'                  => 'Disco',
    'Display'               => 'Visualizar',
    'Displaying'            => 'Visualizando',
    'DoNativeMotionDetection'=> 'Do Native Motion Detection', // Added - 2015-04-18
    'Donate'                => 'Por favor, done',
    'DonateAlready'         => 'No, ya he donado',
    'DonateEnticement'      => 'Ha estado ejecutando ZoneMinder por un tiempo y con suerte le resultará un útil complemento para su seguridad en hogar y trabajo. Aunque ZoneMinder es, y será, libre y de código abierto, cuesta dinero desarrollarlo y mantenerlo. Si quiere ayudar a mantener un futuro desarrollo y nuevas funciones entonces considere hacer un donativo por favor. Donar es, por supuesto, opcional pero muy apreciado y puede donar tanto como desee sin importar la cantidad.<br/><br/>Si desea hacer una donación por favor seleccione la opción de debajo o vaya a https://zoneminder.com/donate/ en su navegador.<br/><br/>Muchas gracias por usar ZoneMinder y no se olvide de vistar los foros en ZoneMinder.com para obtener soporte o hacer sugerencias sobre cómo mejorar su experiencia con ZoneMinder aún más.',
    'DonateRemindDay'       => 'Aún no, recordarme de nuevo en 1 día',
    'DonateRemindHour'      => 'Aún no, recordarme de nuevo en 1 hora',
    'DonateRemindMonth'     => 'Aún no, recordarme de nuevo en 1 mes',
    'DonateRemindNever'     => 'No, no quiero hacer una donación, no recordar',
    'DonateRemindWeek'      => 'Aún no, recordarme de nuevo en 1 semana',
    'DonateYes'             => 'Sí, me gustaría hacer una donación ahora',
    'Download'              => 'Descargar',
    'DownloadVideo'        => 'Download Video',         // Added - 2018-08-30
    'DuplicateMonitorName'  => 'Duplicar nombre de monitor',
    'Duration'              => 'Duración',
    'Edit'                  => 'Editar',
    'EditLayout'           => 'Edit Layout',            // Added - 2018-08-30
    'Email'                 => 'Email',
    'EnableAlarms'          => 'Habilitar alarmas',
    'Enabled'               => 'Habilitado',
    'EnterNewFilterName'    => 'Introducir nuevo nombre de filtro',
    'Error'                 => 'Error',
    'ErrorBrackets'         => 'Error, por favor compruebe que tenga un mismo número de soportes de apertura y cierre',
    'ErrorValidValue'       => 'Error, por favor compruebe que todos los términos tienen un valor válido',
    'Etc'                   => 'etc',
    'Event'                 => 'Evento',
    'EventFilter'           => 'Filtro evento',
    'EventId'               => 'Id Evento',
    'EventName'             => 'Nombre evento',
    'EventPrefix'           => 'Prefijo de evento',
    'Events'                => 'Eventos',
    'Exclude'               => 'Excluir',
    'Execute'               => 'Ejecutar',
    'Exif'                 => 'Embed EXIF data into image', // Added - 2018-08-30
    'Export'                => 'Exportar',
    'ExportDetails'         => 'Exportar detalles de evento',
    'ExportFailed'          => 'Fallo al exportar',
    'ExportFormat'          => 'Formato del archivo a exportar',
    'ExportFormatTar'       => 'Tar',
    'ExportFormatZip'       => 'Zip',
    'ExportFrames'          => 'Exportar detalles del marco',
    'ExportImageFiles'      => 'Exportar archivos de imagen',
    'ExportLog'             => 'Exportar registro',
    'ExportMiscFiles'       => 'Exportar otros archivos (si hay)',
    'ExportOptions'         => 'Opciones de exportación',
    'ExportSucceeded'       => 'Éxito al exportar',
    'ExportVideoFiles'     => 'Export Video Files (if present)', // Added - 2011-08-23
    'Exporting'             => 'Exportando',
    'FPS'                   => 'MPS',
    'FPSReportInterval'     => 'Intervalo de informe de MPS',
    'FTP'                   => 'FTP',
    'Far'                   => 'Lejos',
    'FastForward'           => 'Avance rápido',
    'Feed'                  => 'Feed',
    'Ffmpeg'                => 'Ffmpeg',
    'File'                  => 'Archivo',
    'Filter'               => 'Filter',                 // Added - 2015-04-18
    'FilterArchiveEvents'   => 'Archivar todas las coincidencias',
    'FilterDeleteEvents'    => 'Borrar todas las coincidencias',
    'FilterEmailEvents'     => 'Enviar detalles de todas las coincidencias por email',
    'FilterExecuteEvents'   => 'Ejecutar comando para todas las coincidencias',
    'FilterLog'            => 'Filter log',             // Added - 2015-04-18
    'FilterMessageEvents'   => 'Detalles de mensaje de todas las coincidencias',
    'FilterMoveEvents'     => 'Move all matches',       // Added - 2018-08-30
    'FilterPx'              => 'Filtrar Px',
    'FilterUnset'           => 'Debe especificar un ancho y un alto para el filtro',
    'FilterUpdateDiskSpace'=> 'Update used disk space', // Added - 2018-08-30
    'FilterUploadEvents'    => 'Subir todas las coincidencias',
    'FilterVideoEvents'    => 'Create video for all matches', // Added - 2011-08-23
    'Filters'               => 'Filtros',
    'First'                 => 'Primero',
    'FlippedHori'           => 'Girado horizontalmente',
    'FlippedVert'           => 'Girado verticalmente',
    'FnMocord'              => 'Mocord',            // Added 2013.08.16.
    'FnModect'              => 'Modect',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'FnNone'                => 'None',            // Added 2013.08.16.
    'FnRecord'              => 'Record',            // Added 2013.08.16.
    'Focus'                 => 'Enfoque',
    'ForceAlarm'            => 'Forzar alama',
    'Format'                => 'Formato',
    'Frame'                 => 'Marco',
    'FrameId'               => 'Id del marco',
    'FrameRate'             => 'Ratío del marco',
    'FrameSkip'             => 'Omisión de marcos',
    'Frames'                => 'Marcos',
    'Func'                  => 'Func',
    'Function'              => 'Función',
    'Gain'                  => 'Ganancia',
    'General'               => 'General',
    'GenerateDownload'     => 'Generate Download',      // Added - 2018-08-30
    'GenerateVideo'        => 'Generate Video',         // Added - 2011-08-23
    'GeneratingVideo'      => 'Generating Video',       // Added - 2011-08-23
    'GoToZoneMinder'        => 'Ir a ZoneMinder.com',
    'Grey'                  => 'Gris',
    'Group'                 => 'Grupo',
    'Groups'                => 'Grupos',
    'HasFocusSpeed'         => 'Tiene velocidad de enfoque',
    'HasGainSpeed'          => 'Tiene velocidad de ganancia',
    'HasHomePreset'         => 'Tiene programaciones de inicio',
    'HasIrisSpeed'          => 'Tiene velocidad de iris',
    'HasPanSpeed'           => 'Tiene velocidad de desplazamiento',
    'HasPresets'            => 'Tiene Pprogramaciones',
    'HasTiltSpeed'          => 'Tiene velocidad de inclinación',
    'HasTurboPan'           => 'Tiene turbo desplazamiento',
    'HasTurboTilt'          => 'Tiene turbo inclinación',
    'HasWhiteSpeed'         => 'Tiene velocidad de balance de blancos',
    'HasZoomSpeed'          => 'Tiene velocidad de zoom',
    'High'                  => 'Alto',
    'HighBW'                => 'Alto&nbsp;B/B',
    'Home'                  => 'Inicio',
    'Hostname'             => 'Hostname',               // Added - 2018-08-30
    'Hour'                  => 'Hora',
    'Hue'                   => 'Matiz',
    'Id'                    => 'Id',
    'Idle'                  => 'Parado',
    'Ignore'                => 'Ignorar',
    'Image'                 => 'Imagen',
    'ImageBufferSize'       => 'Tamaño de búfer de imagen (marcos)',
    'Images'                => 'Imágenes',
    'In'                    => 'En',
    'Include'               => 'Incluir',
    'Inverted'              => 'Invertido',
    'Iris'                  => 'Iris',
    'KeyString'             => 'Cadena clave',
    'Label'                 => 'Etiqueta',
    'Language'              => 'Idioma',
    'Last'                  => 'Último',
    'Layout'                => 'Diseño',
    'Level'                 => 'Nivel',
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'      => 'Sólo resultados', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'       => 'Limitar al primero', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                  => 'Línea',
    'LinkedMonitors'        => 'Monitores enlazados',
    'List'                  => 'Lista',
    'ListMatches'          => 'List Matches',           // Added - 2018-08-30
    'Load'                  => 'Carga',
    'Local'                 => 'Local',
    'Log'                   => 'Registro',
    'LoggedInAs'            => 'Identificado como',
    'Logging'               => 'Registro',
    'LoggingIn'             => 'Iniciando sesión',
    'Login'                 => 'Iniciar sesión',
    'Logout'                => 'Cerrar sesión',
    'Logs'                  => 'Registros',
    'Low'                   => 'Bajo',
    'LowBW'                 => 'Bajo&nbsp;B/B',
    'Main'                  => 'Principal',
    'Man'                   => 'Man',
    'Manual'                => 'Manual',
    'Mark'                  => 'Marca',
    'Max'                   => 'Máx',
    'MaxBandwidth'          => 'Ancho de banda máximo',
    'MaxBrScore'            => 'Señal<br/>máxima',
    'MaxFocusRange'         => 'Rango de enfoque máximo',
    'MaxFocusSpeed'         => 'Velocidad de enfoque máxima',
    'MaxFocusStep'          => 'Grado de enfoque máximo',
    'MaxGainRange'          => 'Rango de ganancia máximo',
    'MaxGainSpeed'          => 'Velocidad de ganancia máxima',
    'MaxGainStep'           => 'Grado de ganancia máximo',
    'MaxIrisRange'          => 'Rango de iris máximo',
    'MaxIrisSpeed'          => 'Velocidad de iris máxima',
    'MaxIrisStep'           => 'Grado de iris máximo',
    'MaxPanRange'           => 'Rango de desplazamiento máximo',
    'MaxPanSpeed'           => 'Velocidad de desplazamiento máxima',
    'MaxPanStep'            => 'Grado de desplazamiento máximo',
    'MaxTiltRange'          => 'Rango de inclinación máximo',
    'MaxTiltSpeed'          => 'Velocidad de inclinación máxima',
    'MaxTiltStep'           => 'Grado de inclinación máximo',
    'MaxWhiteRange'         => 'Rango de balance de blancos máximo',
    'MaxWhiteSpeed'         => 'Velocidad de balance de blancos máxima',
    'MaxWhiteStep'          => 'Grado de balance de blancos máximo',
    'MaxZoomRange'          => 'Rango de zoom máximo',
    'MaxZoomSpeed'          => 'Velocidad de zoom máxima',
    'MaxZoomStep'           => 'Grado de zoom máximo',
    'MaximumFPS'            => 'MPS Máximos',
    'Medium'                => 'Medio',
    'MediumBW'              => 'Medio&nbsp;B/B',
    'Message'               => 'Mensaje',
    'MinAlarmAreaLtMax'     => 'El área mínima de alarma debe ser menor que la máxima',
    'MinAlarmAreaUnset'     => 'Debe especificar la mínima cantidad de píxeles de alarma',
    'MinBlobAreaLtMax'      => 'El área mínima de goteo debe ser menor que la máxima',
    'MinBlobAreaUnset'      => 'Debe especificar la mínima cantidad de píxeles de goteo',
    'MinBlobLtMinFilter'    => 'El área mínima de goteo debe ser menor o igual que el área mínima de filtro',
    'MinBlobsLtMax'         => 'Los goteos mínimos deben ser menores que los máximos',
    'MinBlobsUnset'         => 'Debe especificar una cantidad mínima de goteos',
    'MinFilterAreaLtMax'    => 'El área mínima del filtro debe ser menor que la máxima',
    'MinFilterAreaUnset'    => 'Debe especificar la cantidad mínima de píxeles del filtro',
    'MinFilterLtMinAlarm'   => 'El área mínima del filtro debe ser menor o igual a la máxima',
    'MinFocusRange'         => 'Rango de enfoque mínimo',
    'MinFocusSpeed'         => 'Velocidad de enfoque mínima',
    'MinFocusStep'          => 'Grado de enfoque mínimo',
    'MinGainRange'          => 'Rango de ganancia mínimo',
    'MinGainSpeed'          => 'Velocidad de ganancia mínima',
    'MinGainStep'           => 'Grado de ganancia mínimo',
    'MinIrisRange'          => 'Rango de iris mínimo',
    'MinIrisSpeed'          => 'Velocidad de iris mínima',
    'MinIrisStep'           => 'Grado de iris mínimo',
    'MinPanRange'           => 'Rango de desplazamiento mínima',
    'MinPanSpeed'           => 'Velocidad de desplazamiento mínima',
    'MinPanStep'            => 'Grado de desplazamiento mínimo',
    'MinPixelThresLtMax'    => 'El mínimo umbral de píxeles debe ser menor que el máximo',
    'MinPixelThresUnset'    => 'Debe especificar un umbral de píxeles mínimo',
    'MinTiltRange'          => 'Rango de inclinación mínimo',
    'MinTiltSpeed'          => 'Velocidad de inclinación mínima',
    'MinTiltStep'           => 'Grado de inclinación mínimo',
    'MinWhiteRange'         => 'Rango de balance de blancos mínimo',
    'MinWhiteSpeed'         => 'Velocidad de balance de blancos mínimo',
    'MinWhiteStep'          => 'Grado de balance de blancos mínimo',
    'MinZoomRange'          => 'Rango de zoom mínimo',
    'MinZoomSpeed'          => 'Velocidad de zoom mínima',
    'MinZoomStep'           => 'Grado de zoom mínimo',
    'Misc'                  => 'Misc',
    'Mode'                 => 'Mode',                   // Added - 2015-04-18
    'Monitor'               => 'Monitor',
    'MonitorIds'            => 'Ids&nbsp;monitor',
    'MonitorPreset'         => 'Programar monitor',
    'MonitorPresetIntro'    => 'Seleccione la programación más apropiada de la lista de debajo.<br/><br/>Por favor tenga en cuenta que esto podría sobrescribir cualquier valor que ya hubiera configurado para el monitor actual.<br/><br/>',
    'MonitorProbe'          => 'Sondear monitor',
    'MonitorProbeIntro'     => 'La lista de debajo muestra las cámaras analógicas y en red detectadas y si ya están siendo usadas o están disponibles para seleccionar.<br/><br/>Seleccione la entrada deseada de la lista de debajo.<br/><br/>Por favor tenga en cuenta que podrían no detectarse todas las cámaras y que elegir una cámara aquí podría sobrescribir cualquier valor que ya hubiera configurado para el monitor actual.<br/><br/>',
    'Monitors'              => 'Monitores',
    'Montage'               => 'Montaje',
    'MontageReview'        => 'Montage Review',         // Added - 2018-08-30
    'Month'                 => 'Mes',
    'More'                  => 'Más',
    'MotionFrameSkip'       => 'Motion Frame Skip',
    'Move'                  => 'Mover',
    'Mtg2widgrd'            => '2-wide grid',              // Added 2013.08.15.
    'Mtg3widgrd'            => '3-wide grid',              // Added 2013.08.15.
    'Mtg3widgrx'            => '3-wide grid, scaled, enlarge on alarm',              // Added 2013.08.15.
    'Mtg4widgrd'            => '4-wide grid',              // Added 2013.08.15.
    'MtgDefault'            => 'Default',              // Added 2013.08.15.
    'MustBeGe'              => 'debe ser mayor o igual que',
    'MustBeLe'              => 'debe ser menor o igual que',
    'MustConfirmPassword'   => 'Debe confirmar la contraseña',
    'MustSupplyPassword'    => 'Debe indicar una contraseña',
    'MustSupplyUsername'    => 'Debe indicar un nombre de usuario',
    'Name'                  => 'Nombre',
    'Near'                  => 'Cerca',
    'Network'               => 'Red',
    'New'                   => 'Nuevo',
    'NewGroup'              => 'Nuevo grupo',
    'NewLabel'              => 'Nueva etiqueta',
    'NewPassword'           => 'Nueva contraseña',
    'NewState'              => 'Nuevo estado',
    'NewUser'               => 'Nuevo usuario',
    'Next'                  => 'Siguiente',
    'No'                    => 'No',
    'NoDetectedCameras'     => 'No se detectaron cámaras',
    'NoDetectedProfiles'   => 'No Detected Profiles',   // Added - 2018-08-30
    'NoFramesRecorded'      => 'No hay marcos grabados para este evento',
    'NoGroup'               => 'Sin grupo',
    'NoSavedFilters'        => 'No hay filtros guardados',
    'NoStatisticsRecorded'  => 'No hay estadísticas guardadas para este evento/marco',
    'None'                  => 'Ninguno',
    'NoneAvailable'         => 'Ninguno disponible',
    'Normal'                => 'Normal',
    'Notes'                 => 'Notas',
    'NumPresets'            => 'Número programa',
    'Off'                   => 'Off',
    'On'                    => 'On',
    'OnvifCredentialsIntro'=> 'Please supply user name and password for the selected camera.<br/>If no user has been created for the camera then the user given here will be created with the given password.<br/><br/>', // Added - 2015-04-18
    'OnvifProbe'           => 'ONVIF',                  // Added - 2015-04-18
    'OnvifProbeIntro'      => 'The list below shows detected ONVIF cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'OpEq'                  => 'igual a',
    'OpGt'                  => 'mayor que',
    'OpGtEq'                => 'mayor que o igual a',
    'OpIn'                  => 'en conjunto',
    'OpIs'                 => 'is',                     // Added - 2018-08-30
    'OpIsNot'              => 'is not',                 // Added - 2018-08-30
    'OpLt'                  => 'menor que',
    'OpLtEq'                => 'menor que o igual a',
    'OpMatches'             => 'coincidencias',
    'OpNe'                  => 'distinto de',
    'OpNotIn'               => 'no en conjunto',
    'OpNotMatches'          => 'no coincide',
    'Open'                  => 'Abrir',
    'OptionHelp'            => 'Ayuda de la opción',
    'OptionRestartWarning'  => 'Estos cambios podrían no surtir un efecto completo mientras el sistema esté ejecutándose. Cuando haya terminado haciendo cambios por favor asegúrese de reiniciar ZoneMinder.',
    'OptionalEncoderParam' => 'Optional Encoder Parameters', // Added - 2018-08-30
    'Options'               => 'Opciones',
    'OrEnterNewName'        => 'o introduzca un nuevo nombre',
    'Order'                 => 'Orden',
    'Orientation'           => 'Orientación',
    'Out'                   => 'Fuera',
    'OverwriteExisting'     => 'Sobreescribir existente',
    'Paged'                 => 'Paginado',
    'Pan'                   => 'Desplazar',
    'PanLeft'               => 'Desplazar a la izquierda',
    'PanRight'              => 'Rotar a la derecha',
    'PanTilt'               => 'Rotar/Inclinar',
    'Parameter'             => 'Parámetro',
    'Password'              => 'Contraseña',
    'PasswordsDifferent'    => 'La nueva contraseña y la de confirmación son diferentes',
    'Paths'                 => 'Rutas',
    'Pause'                 => 'Pausar',
    'Phone'                 => 'Teléfono',
    'PhoneBW'               => 'B/B&nbsp;Teléfono',
    'Pid'                   => 'PID',
    'PixelDiff'             => 'Diferencia píxeles',
    'Pixels'                => 'píxeles',
    'Play'                  => 'Reproducir',
    'PlayAll'               => 'Reproducir rodo',
    'PleaseWait'            => 'Espere por favor',
    'Plugins'              => 'Plugins',                // Added - 2015-04-18
    'Point'                 => 'Punto',
    'PostEventImageBuffer'  => 'Cuenta de imagen post evento',
    'PreEventImageBuffer'   => 'Cuenta de imagen pre evento',
    'PreserveAspect'        => 'Preservar relación de aspecto',
    'Preset'                => 'Programa',
    'Presets'               => 'Programas',
    'Prev'                  => 'Anterior',
    'Probe'                 => 'Sondear',
    'ProfileProbe'         => 'Stream Probe',           // Added - 2015-04-18
    'ProfileProbeIntro'    => 'The list below shows the existing stream profiles of the selected camera .<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that ZoneMinder cannot configure additional profiles and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'Progress'             => 'Progress',               // Added - 2015-04-18
    'Protocol'              => 'Protocolo',
    'RTSPDescribe'         => 'Use RTSP Response Media URL', // Added - 2018-08-30
    'RTSPTransport'        => 'RTSP Transport Protocol', // Added - 2018-08-30
    'Rate'                  => 'Valorar',
    'Real'                  => 'Real',
    'RecaptchaWarning'     => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // Added - 2018-08-30
    'Record'                => 'Grabar',
    'RecordAudio'          => 'Whether to store the audio stream when saving an event.', // Added - 2018-08-30
    'RefImageBlendPct'      => 'Referencia de mezcla de imagen %ge',
    'Refresh'               => 'Refrescar',
    'Remote'                => 'Remoto',
    'RemoteHostName'        => 'Nombre del host remoto',
    'RemoteHostPath'        => 'Nombre de ruta del host',
    'RemoteHostPort'        => 'Puerto del host remoto',
    'RemoteHostSubPath'     => 'Nombre de subruta del host',
    'RemoteImageColours'    => 'Colores de imagen remota',
    'RemoteMethod'          => 'Método remoto',
    'RemoteProtocol'        => 'Protocolo remoto',
    'Rename'                => 'Renombrar',
    'Replay'                => 'Repetir',
    'ReplayAll'             => 'Todos los eventos',
    'ReplayGapless'         => 'Eventos sin espacios',
    'ReplaySingle'          => 'Evento individual',
    'ReportEventAudit'     => 'Audit Events Report',    // Added - 2018-08-30
    'Reset'                 => 'Restablecer',
    'ResetEventCounts'      => 'Restablecer número de eventos',
    'Restart'               => 'Reiniciar',
    'Restarting'            => 'Reinciando',
    'RestrictedCameraIds'   => 'Ids de cámara restringidos',
    'RestrictedMonitors'    => 'Monitores restringidos',
    'ReturnDelay'           => 'Retraso de entrega',
    'ReturnLocation'        => 'Lugar de entrega',
    'Rewind'                => 'Rebobinar',
    'RotateLeft'            => 'Rotar hacia la izquierda',
    'RotateRight'           => 'Rotar hacia la derecha',
    'RunLocalUpdate'        => 'Por favor, ejecute zmupdate.pl para actualizar',
    'RunMode'               => 'Modo de ejecución',
    'RunState'              => 'Estado de ejecución',
    'Running'               => 'En ejecución',
    'Save'                  => 'Guardar',
    'SaveAs'                => 'Guardar cómo',
    'SaveFilter'            => 'Guardar filtro',
    'SaveJPEGs'            => 'Save JPEGs',             // Added - 2018-08-30
    'Scale'                 => 'Escalar',
    'Score'                 => 'Cuenta',
    'Secs'                  => 'Segs',
    'Sectionlength'         => 'Duración de sección',
    'Select'                => 'Seleccionar',
    'SelectFormat'          => 'Seleccionar formato',
    'SelectLog'             => 'Seleccionar registro',
    'SelectMonitors'        => 'Seleccionar monitores',
    'SelfIntersecting'      => 'Arístas de polígonos no deben intersectar',
    'Set'                   => 'Establecer',
    'SetNewBandwidth'       => 'Establecer nuevo ancho de banda',
    'SetPreset'             => 'Establecer programación',
    'Settings'              => 'Ajustes',
    'ShowFilterWindow'      => 'Mostrar ventana de filtros',
    'ShowTimeline'          => 'Mostrar línea de tiempo',
    'SignalCheckColour'     => 'Color de comprobación de señal',
    'SignalCheckPoints'    => 'Signal Check Points',    // Added - 2018-08-30
    'Size'                  => 'Tamaño',
    'SkinDescription'       => 'Cambiar el tema por defecto para este ordenador',
    'Sleep'                 => 'Dormir',
    'SortAsc'               => 'Ascendente',
    'SortBy'                => 'Ordenar por',
    'SortDesc'              => 'Descendente',
    'Source'                => 'Origen',
    'SourceColours'         => 'Colores de origen',
    'SourcePath'            => 'Ruta de origen',
    'SourceType'            => 'Tipo de origen',
    'Speed'                 => 'Velocidad',
    'SpeedHigh'             => 'Velocidad alta',
    'SpeedLow'              => 'Velocidad baja',
    'SpeedMedium'           => 'Velocidad media',
    'SpeedTurbo'            => 'Turbo velocidad',
    'Start'                 => 'Iniciar',
    'State'                 => 'Estado',
    'Stats'                 => 'Estadísticas',
    'Status'                => 'Estado',
    'StatusConnected'      => 'Capturing',              // Added - 2018-08-30
    'StatusNotRunning'     => 'Not Running',            // Added - 2018-08-30
    'StatusRunning'        => 'Not Capturing',          // Added - 2018-08-30
    'StatusUnknown'        => 'Unknown',                // Added - 2018-08-30
    'Step'                  => 'Salto',
    'StepBack'              => 'Salto atrás',
    'StepForward'           => 'Salto adelante',
    'StepLarge'             => 'Salto largo',
    'StepMedium'            => 'Salto medio',
    'StepNone'              => 'Sin salto',
    'StepSmall'             => 'Salto pequeño',
    'Stills'                => 'Fijas',
    'Stop'                  => 'Detener',
    'Stopped'               => 'Detenido',
    'StorageArea'          => 'Storage Area',           // Added - 2018-08-30
    'StorageScheme'        => 'Scheme',                 // Added - 2018-08-30
    'Stream'                => 'Corriente',
    'StreamReplayBuffer'    => 'Secuencia de búfer de reproducción',
    'Submit'                => 'Enviar',
    'System'                => 'Sistema',
    'SystemLog'             => 'Registros del sistema',
    'TargetColorspace'     => 'Target colorspace',      // Added - 2015-04-18
    'Tele'                  => 'Tele',
    'Thumbnail'             => 'Thumbnail',
    'Tilt'                  => 'Inclinar',
    'Time'                  => 'Hora',
    'TimeDelta'             => 'Delta del tiempo',
    'TimeStamp'             => 'Marca de tiempo',
    'Timeline'              => 'Línea de tiempo',
    'TimelineTip1'          => 'Pass your mouse over the graph to view a snapshot image and event details.',              // Added 2013.08.15.
    'TimelineTip2'          => 'Click on the coloured sections of the graph, or the image, to view the event.',              // Added 2013.08.15.
    'TimelineTip3'          => 'Click on the background to zoom in to a smaller time period based around your click.',              // Added 2013.08.15.
    'TimelineTip4'          => 'Use the controls below to zoom out or navigate back and forward through the time range.',              // Added 2013.08.15.
    'Timestamp'             => 'Marca de tiempo',
    'TimestampLabelFormat'  => 'Formato de hora multinacional',
    'TimestampLabelSize'    => 'Tamaño de fuente',
    'TimestampLabelX'       => 'Etiqueta de tiempo X',
    'TimestampLabelY'       => 'Etiqueta de tiempo Y',
    'Today'                 => 'Hoy',
    'Tools'                 => 'Herramientas',
    'Total'                 => 'Total',
    'TotalBrScore'          => 'Cuenta<br/>total',
    'TrackDelay'            => 'Retraso de pista',
    'TrackMotion'           => 'Movimiento de pista',
    'Triggers'              => 'Interruptores',
    'TurboPanSpeed'         => 'Turbo velocidad de rotación',
    'TurboTiltSpeed'        => 'Turbo velocidad de inclinación',
    'Type'                  => 'Tipe',
    'Unarchive'             => 'Desarchivar',
    'Undefined'             => 'Indefinido',
    'Units'                 => 'Unidades',
    'Unknown'               => 'Desconocido',
    'Update'                => 'Actualizar',
    'UpdateAvailable'       => 'Hay una actualización disponible para ZoneMinder.',
    'UpdateNotNecessary'    => 'No es necesario actualizar.',
    'Updated'               => 'Actualizado',
    'Upload'               => 'Upload',                 // Added - 2011-08-23
    'UseFilter'             => 'Usar filtro',
    'UseFilterExprsPost'    => '&nbsp;filtros&nbsp;de expresión', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'     => 'Usar&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UsedPlugins'          => 'Used Plugins',           // Added - 2015-04-18
    'User'                  => 'Usuario',
    'Username'              => 'Nombre de usuario',
    'Users'                 => 'Usuarios',
    'V4L'                  => 'V4L',                    // Added - 2015-04-18
    'V4LCapturesPerFrame'  => 'Captures Per Frame',     // Added - 2015-04-18
    'V4LMultiBuffer'       => 'Multi Buffering',        // Added - 2015-04-18
    'Value'                 => 'Valor',
    'Version'               => 'Versión',
    'VersionIgnore'         => 'Ignorar esta versión',
    'VersionRemindDay'      => 'Volver a recordar en 1 día',
    'VersionRemindHour'     => 'Volver a recordar en 1 hora',
    'VersionRemindNever'    => 'No recordar nuevas versiones',
    'VersionRemindWeek'     => 'Volver a recordar en 1 semana',
    'Video'                => 'Video',                  // Added - 2011-08-23
    'VideoFormat'          => 'Video Format',           // Added - 2011-08-23
    'VideoGenFailed'       => 'Video Generation Failed!', // Added - 2011-08-23
    'VideoGenFiles'        => 'Existing Video Files',   // Added - 2011-08-23
    'VideoGenNoFiles'      => 'No Video Files Found',   // Added - 2011-08-23
    'VideoGenParms'        => 'Video Generation Parameters', // Added - 2011-08-23
    'VideoGenSucceeded'    => 'Video Generation Succeeded!', // Added - 2011-08-23
    'VideoSize'            => 'Video Size',             // Added - 2011-08-23
    'VideoWriter'          => 'Video Writer',           // Added - 2018-08-30
    'View'                  => 'Ver',
    'ViewAll'               => 'Ver todos',
    'ViewEvent'             => 'Ver evento',
    'ViewPaged'             => 'Ver paginados',
    'Wake'                  => 'Despertar',
    'WarmupFrames'          => 'Marcos de calentamiento',
    'Watch'                 => 'Observar',
    'Web'                   => 'Web',
    'WebColour'             => 'Color web',
    'WebSiteUrl'           => 'Website URL',            // Added - 2018-08-30
    'Week'                  => 'Semana',
    'White'                 => 'Blanco',
    'WhiteBalance'          => 'Balance de blancos',
    'Wide'                  => 'Ancho',
    'X'                     => 'X',
    'X10'                   => 'X10',
    'X10ActivationString'   => 'Cadena de activación de X10',
    'X10InputAlarmString'   => 'Alarma de entrada de cadena de X10',
    'X10OutputAlarmString'  => 'Salida de cadena de alarma de X10',
    'Y'                     => 'Y',
    'Yes'                   => 'Sí',
    'YouNoPerms'            => 'No tiene los permisos necesarios para acceder a este recurso.',
    'Zone'                  => 'Zona',
    'ZoneAlarmColour'       => 'Color de alarma (rojo/verde/bzul)',
    'ZoneArea'              => 'Área de zona',
    'ZoneExtendAlarmFrames'=> 'Extend Alarm Frame Count', // Added - 2015-04-18
    'ZoneFilterSize'        => 'Filtrar anchura/altura (píxeles)',
    'ZoneMinMaxAlarmArea'   => 'Mín/Máx área de alarma',
    'ZoneMinMaxBlobArea'    => 'Mín/Máx área de goteo',
    'ZoneMinMaxBlobs'       => 'Mín/Máx goteos',
    'ZoneMinMaxFiltArea'    => 'Mín/Máx áreas filtradas',
    'ZoneMinMaxPixelThres'  => 'Mín/Máx umbral de píxeles (0-255)',
    'ZoneMinderLog'         => 'Registros de ZoneMinder',
    'ZoneOverloadFrames'    => 'Ignorar número de sobrecarga de marcos',
    'Zones'                 => 'Zonas',
    'Zoom'                  => 'Zoom',
    'ZoomIn'                => 'Acercar imagen',
    'ZoomOut'               => 'Alejar imagen',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'          => 'Sesión actual: \'%1$s\'',
    'EventCount'            => '%1$s %2$s', // For example '37 Events' (from Vlang below)
    'LastEvents'            => 'Último(s) %1$s %2$s', // For example 'Last 37 Events' (from Vlang below)
    'LatestRelease'         => 'La última versión publicada es la v%1$s, tiene instalada la v%2$s.',
    'MonitorCount'          => '%1$s %2$s', // For example '4 Monitors' (from Vlang below)
    'MonitorFunction'       => 'Función del monitor %1$s',
    'RunningRecentVer'      => 'Está ejecutando la versión más reciente de ZoneMinder, v%s.',
    'VersionMismatch'      	=> 'La versión no coincide, la versión del sistema es la %1$s, la de la base de datos es la %2$s.',
);

// Variable arrays expressing plurality, see the zmVlang description above
$VLANG = array(
    'Event'                 => array( 0=>'Eventos', 1=>'Evento', 2=>'Eventos' ),
    'Monitor'               => array( 0=>'Monitores', 1=>'Monitor', 2=>'Monitores' ),
);

function zmVlang( $langVarArray, $count )
{
    krsort( $langVarArray );
    foreach ( $langVarArray as $key=>$value )
    {
        if ( abs($count) >= $key )
        {
            return( $value );
        }
    }
    die( 'Error, no se pudo correlacionar la variable de la cadena de idioma' );
}


$OLANG = array(
	'OPTIONS_FFMPEG' => array(
		'Help' => "Parameters in this field are passed on to FFmpeg. Multiple parameters can be separated by ,~~ ".
		          "Examples (do not enter quotes)~~~~".
		          "\"allowed_media_types=video\" Set datatype to request fromcam (audio, video, data)~~~~".
		          "\"reorder_queue_size=nnn\" Set number of packets to buffer for handling of reordered packets~~~~".
		          "\"loglevel=debug\" Set verbosity of FFmpeg (quiet, panic, fatal, error, warning, info, verbose, debug)"
	),
	'OPTIONS_LIBVLC' => array(
		'Help' => "Parameters in this field are passed on to libVLC. Multiple parameters can be separated by ,~~ ".
		          "Examples (do not enter quotes)~~~~".
		          "\"--rtp-client-port=nnn\" Set local port to use for rtp data~~~~". 
		          "\"--verbose=2\" Set verbosity of libVLC"
	),
	
);

?>
