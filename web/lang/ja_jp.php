<?php
//
// ZoneMinder web Japanese language file, $Date$, $Revision$
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

// ZoneMinder Japanese Translation by Andrew Arkley

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
// require_once( 'zm_lang_en_gb.php' );

// You may need to change the character set here, if your web server does not already
// do this by default, uncomment this if required.
//
// Example
// header( "Content-Type: text/html; charset=iso-8859-1" );
header( "Content-Type: text/html; charset=UTF-8" );

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
    'SystemLog'             => 'システムログ',
    'DateTime'              => '日付/時刻',
    'Pid'                   => 'PID',
    '24BitColour'           => '24ビットカラー',
    '32BitColour'           => '32ビットカラー',
    '8BitGrey'              => '8ビットグレースケール',
    'AddNewControl'         => '新しいコントロールを追加',
    'AddNewMonitor'         => '追加',
    'AddMonitorDisabled'    => 'あなたのユーザーは新しいモニターを追加することが許可されていません',
    'AddNewServer'          => '新しいサーバーを追加',
    'AddNewStorage'         => '新しいストレージを追加',
    'AddNewUser'            => '新しいユーザーを追加',
    'AddNewZone'            => '新しいゾーンを追加',
    'AlarmBrFrames'         => 'アラーム<br/>フレーム',
    'AlarmFrame'            => 'アラームフレーム',
    'AlarmFrameCount'       => 'アラームフレーム数',
    'AlarmLimits'           => 'アラーム制限',
    'AlarmMaximumFPS'       => 'アラーム最大FPS',
    'AlarmPx'               => 'アラームPx',
    'AlarmRefImageBlendPct' => 'アラーム参照画像ブレンド％',
    'AlarmRGBUnset'         => 'アラームRGBカラーを設定する必要があります',
    'AllTokensRevoked'      => 'すべてのトークンが取り消されました',
    'AnalysisFPS'           => '分析FPS',
    'AnalysisUpdateDelay'   => '分析更新遅延',
    'APIEnabled'            => 'APIが有効',
    'ApplyingStateChange'   => '状態変更を適用中',
    'ArchArchived'          => 'アーカイブ済みのみ',
    'ArchUnarchived'        => '未アーカイブのみ',
    'AreaUnits'             => 'エリア(px/%）',
    'AttrAlarmFrames'       => 'アラームフレーム',
    'AttrAlarmedZone'       => 'アラームゾーン',
    'AttrArchiveStatus'     => 'アーカイブステータス',
    'AttrAvgScore'          => '平均スコア',
    'AttrCause'             => '原因',
    'AttrStartDate'         => '開始日',
    'AttrEndDate'           => '終了日',
    'AttrStartDateTime'     => '開始日時',
    'AttrEndDateTime'       => '終了日時',
    'AttrEventDiskSpace'    => 'イベントディスクスペース',
    'AttrDiskSpace'         => 'ファイルシステムディスクスペース',
    'AttrDiskBlocks'        => 'ディスクブロック',
    'AttrDiskPercent'       => 'ディスクパーセント',
    'AttrDuration'          => '持続時間',
    'AttrFrames'            => 'フレーム数',
    'AttrId'                => 'ID',
    'AttrMaxScore'          => '最大スコア',
    'AttrMonitorId'         => 'モニターID',
    'AttrMonitorName'       => 'モニター名',
    'AttrSecondaryStorageArea' => 'セカンダリーストレージエリア',
    'AttrStorageArea'       => 'ストレージエリア',
    'AttrFilterServer'      => 'サーバーフィルターが実行中',
    'AttrMonitorServer'     => 'サーバーモニターが実行中',
    'AttrStorageServer'     => 'ストレージホスティングサーバー',
    'AttrStateId'           => '実行状態',
    'AttrName'              => '名前',
    'AttrNotes'             => 'ノート',
    'AttrSystemLoad'        => 'システム負荷',
    'AttrStartTime'         => '開始時間',
    'AttrEndTime'           => '終了時間',
    'AttrTotalScore'        => '総合スコア',
    'AttrStartWeekday'      => '開始曜日',
    'AttrEndWeekday'        => '終了曜日',
    'AutoStopTimeout'       => '自動停止タイムアウト',
    'AvgBrScore'            => '平均<br/>スコア',
    'BackgroundFilter'      => 'フィルターをバックグラウンドで実行',
    'BadAlarmFrameCount'    => 'アラームフレーム数は1以上の整数である必要があります',
    'BadAlarmMaxFPS'        => 'アラーム最大FPSは正の整数または浮動小数点値である必要があります',
    'BadAnalysisFPS'        => '分析FPSは正の整数または浮動小数点値である必要があります',
    'BadAnalysisUpdateDelay'=> '分析更新遅延は0以上の整数で設定する必要があります',
    'BadChannel'            => 'チャンネルは0以上の整数で設定する必要があります',
    'BadDevice'             => 'デバイスは有効な値で設定する必要があります',
    'BadEncoderParameters'  => 'エンコーダーは、少なくともcrfの値が必要です。詳細はヘルプを参照してください。',
    'BadFormat'             => 'フォーマットは有効な値で設定する必要があります',
    'BadFPSReportInterval'  => 'FPSレポート間隔バッファ数は0以上の整数である必要があります',
    'BadFrameSkip'          => 'フレームスキップ数は0以上の整数である必要があります',
    'BadMotionFrameSkip'    => 'モーションフレームスキップ数は0以上の整数である必要があります',
    'BadHeight'             => '高さは有効な値で設定する必要があります',
    'BadHost'               => 'ホストは有効なIPアドレスまたはホスト名で設定する必要があります。http://を含めないでください。',
    'BadImageBufferCount'   => 'イメージバッファサイズは2以上の整数である必要があります',
    'BadLabelX'             => 'ラベルX座標は0以上の整数で設定する必要があります',
    'BadLabelY'             => 'ラベルY座標は0以上の整数で設定する必要があります',
    'BadMaxFPS'             => '最大FPSは正の整数または浮動小数点値である必要があります',
    'BadNameChars'          => '名前には英数字、スペース、ハイフン、アンダースコアのみ使用できます',
    'BadPalette'            => 'パレットは有効な値で設定する必要があります',
    'BadColours'            => 'ターゲットカラーは有効な値で設定する必要があります',
    'BadPassthrough'        => '録画 -> パススルーはffmpegタイプのモニターでのみ機能します。',
    'BadPath'               => 'ソース -> パスは有効な値で設定する必要があります',
    'BadPathNotEncoded'     => 'ソース -> パスは有効な値で設定する必要があります。無効な文字 !*\'()$ ,#[] が検出されました。これらはURLパーセントエンコードが必要な場合があります。',
    'BadPort'               => 'ソース -> ポートは有効な数値で設定する必要があります',
    'BadPostEventCount'     => 'ポストイベント画像数は0以上の整数である必要があります',
    'BadPreEventCount'      => 'プレイベント画像数は0以上で、イメージバッファサイズ未満である必要があります',
    'BadPreEventCountMaxImageBufferCount' => '最大イメージバッファサイズは、プレイベント画像数よりも大きくなければなりません。さもなければ満たされません。',
    'BadRefBlendPerc'       => '参照ブレンド割合は正の整数である必要があります',
    'BadNoSaveJPEGsOrVideoWriter' => 'SaveJPEGsとVideoWriterの両方が無効に設定されています。何も記録されません！',
    'BadSectionLength'      => 'セクションの長さは30以上の整数である必要があります',
    'BadSignalCheckColour'  => '信号チェックカラーは有効なRGBカラー文字列である必要があります',
    'BadStreamReplayBuffer' => 'ストリームリプレイバッファは0以上の整数である必要があります',
    'BadSourceType'         => 'ソースタイプ「Webサイト」は、機能を「モニター」に設定する必要があります',
    'BadWarmupCount'        => 'ウォームアップフレームは0以上の整数である必要があります',
    'BadWebColour'          => 'ウェブカラーは有効なウェブカラー文字列である必要があります',
    'BadWebSitePath'        => '完全なウェブサイトURLを入力してください。http://またはhttps://のプレフィックスを含めてください。',
    'BadWidth'              => '幅は有効な値に設定する必要があります',
    'BandwidthHead'         => '帯域幅',     // This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'                => 'ブロブ ピクセル',
    'BlobSizes'             => 'ブロブ サイズ',
    'CanAutoFocus'          => 'オートフォーカス可能',
    'CanAutoGain'           => 'オートゲイン可能',
    'CanAutoIris'           => 'オートアイリス可能',
    'CanAutoWhite'          => 'オートホワイトバランス可能',
    'CanAutoZoom'           => 'オートズーム可能',
    'CancelForcedAlarm'     => '強制アラームをキャンセル',
    'CanFocusAbs'           => '絶対フォーカス可能',
    'CanFocus'              => 'フォーカス可能',
    'CanFocusCon'           => '連続フォーカス可能',
    'CanFocusRel'           => '相対フォーカス可能',
    'CanGainAbs'            => '絶対ゲイン可能',
    'CanGain'               => 'ゲイン可能',
    'CanGainCon'            => '連続ゲイン可能',
    'CanGainRel'            => '相対ゲイン可能',
    'CanIrisAbs'            => '絶対アイリス可能',
    'CanIris'               => 'アイリス可能',
    'CanIrisCon'            => '連続アイリス可能',
    'CanIrisRel'            => '相対アイリス可能',
    'CanMoveAbs'            => '絶対移動可能',
    'CanMove'               => '移動可能',
    'CanMoveCon'            => '連続移動可能',
    'CanMoveDiag'           => '斜め移動可能',
    'CanMoveMap'            => 'マップ移動可能',
    'CanMoveRel'            => '相対移動可能',
    'CanPan'                => '水平移動可能',
    'CanReset'              => 'リセット可能',
    'CanReboot'             => '再起動可能',
    'CanSetPresets'         => 'プリセット設定可能',
    'CanSleep'              => 'スリープ可能',
    'CanTilt'               => '垂直移動可能',
    'CanWake'               => 'ウェイク可能',
    'CanWhiteAbs'           => '絶対ホワイトバランス可能',
    'CanWhiteBal'           => 'ホワイトバランス可能',
    'CanWhite'              => 'ホワイトバランス可能',
    'CanWhiteCon'           => '連続ホワイトバランス可能',
    'CanWhiteRel'           => '相対ホワイトバランス可能',
    'CanZoomAbs'            => '絶対ズーム可能',
    'CanZoom'               => 'ズーム可能',
    'CanZoomCon'            => '連続ズーム可能',
    'CanZoomRel'            => '相対ズーム可能',
    'CaptureHeight'         => 'キャプチャ高さ',
    'CaptureMethod'         => 'キャプチャ方法',
    'CaptureResolution'     => 'キャプチャ解像度',
    'CapturePalette'        => 'キャプチャパレット',
    'CaptureWidth'          => 'キャプチャ幅',
    'CheckMethod'           => 'アラームチェック方法',
    'ChooseDetectedCamera'  => '検出されたカメラを選択',
    'ChooseDetectedProfile' => '検出されたプロファイルを選択',
    'ChooseFilter'          => 'フィルタを選択',
    'ChooseLogFormat'       => 'ログフォーマットを選択',
    'ChooseLogSelection'    => 'ログ選択を選択',
    'ChoosePreset'          => 'プリセットを選択',
    'CloneMonitor'          => 'クローン',
    'ConcurrentFilter'      => 'フィルタを同時実行',
    'ConfigOptions'         => '設定オプション',
    'ConfigType'            => '設定タイプ',
    'ConfiguredFor'         => '設定対象',
    'ConfigURL'             => '設定ベースURL',
    'ConfirmDeleteControl'  => '警告、制御を削除すると、それを使用するすべてのモニターが制御不能になります。<br><br>本当に削除してもよろしいですか？',
    'ConfirmDeleteDevices'  => '選択されたデバイスを削除してもよろしいですか？',
    'ConfirmDeleteEvents'   => '選択されたイベントを削除してもよろしいですか？',
    'ConfirmDeleteLayout'   => '現在のレイアウトを削除してもよろしいですか？',
    'ConfirmDeleteTitle'    => '削除確認',
    'ConfirmPassword'       => 'パスワードを確認',
    'ConfirmUnarchiveEvents'=> '選択されたイベントのアーカイブを解除してもよろしいですか？',
    'ConjAnd'               => 'そして',
    'ConjOr'                => 'または',
    'ContactAdmin'          => '詳細については管理者に連絡してください。',
    'ControlAddress'        => '制御アドレス',
    'ControlCap'            => '制御能力',
    'ControlCaps'           => '制御機能',
    'ControlDevice'         => '制御デバイス',
    'Controllable'          => '制御可能',
    'ControlType'           => '制御タイプ',
    'CycleWatch'            => 'サイクルウォッチ',
    'DefaultRate'           => 'デフォルトレート',
    'DefaultScale'          => 'デフォルトスケール',
    'DefaultCodec'          => 'イベントビュー用のデフォルト方法',
    'DefaultView'           => 'デフォルトビュー',
    'RTSPDescribe'          => 'RTSP応答メディアURLを使用',
    'DeleteAndNext'         => '削除 &amp; 次へ',
    'DeleteAndPrev'         => '削除 &amp; 前へ',
    'DeleteSavedFilter'     => '保存されたフィルタを削除',
    'DetectedCameras'       => '検出されたカメラ',
    'DetectedProfiles'      => '検出されたプロファイル',
    'DeviceChannel'         => 'デバイスチャンネル',
    'DeviceFormat'          => 'デバイスフォーマット',
    'DeviceNumber'          => 'デバイス番号',
    'DevicePath'            => 'デバイスパス',
    'DisableAlarms'         => 'アラームを無効化',
    'DonateAlready'         => 'いいえ、既に寄付しました',
    'DonateEnticement'      => 'ZoneMinderをしばらく使用しており、自宅や職場のセキュリティに役立っていることを願っています。ZoneMinderは無料かつオープンソースですが、開発とサポートには費用がかかります。将来の開発や新機能のサポートを手助けしたい場合は、寄付を検討してください。寄付はもちろん任意ですが、大変感謝されます。また、好きな金額を寄付することができます。<br/><br/>寄付をご希望の場合は、以下のオプションを選択するか、ブラウザで<a href="https://zoneminder.com/donate/" target="_blank">https://zoneminder.com/donate/</a>にアクセスしてください。<br/><br/>ZoneMinderを使用していただきありがとうございます。また、サポートやZoneMinderの体験をさらに良くするための提案については、<a href="https://forums.zoneminder.com">ZoneMinder.com</a>のフォーラムを忘れずに訪れてください。',
    'Donate'                => '寄付をお願いします',
    'DonateRemindDay'       => 'まだ、1日後にリマインドしてください',
    'DonateRemindHour'      => 'まだ、1時間後にリマインドしてください',
    'DonateRemindMonth'     => 'まだ、1ヶ月後にリマインドしてください',
    'DonateRemindNever'     => 'いいえ、寄付する気はありません。二度とリマインドしないでください',
    'DonateRemindWeek'      => 'まだ、1週間後にリマインドしてください',
    'DonateYes'             => 'はい、今すぐ寄付したい',
    'DoNativeMotionDetection'=> 'ネイティブモーション検出を行う',
    'DuplicateMonitorName'  => 'モニター名が重複しています',
    'DuplicateRTSPStreamName' =>  'RTSPストリーム名が重複しています',
    'EditControl'           => '制御を編集',
    'EditLayout'            => 'レイアウトを編集',
    'EnableAlarms'          => 'アラームを有効化',
    'EnterNewFilterName'    => '新しいフィルタ名を入力',
    'ErrorBrackets'         => 'エラー、括弧の開きと閉じが等しいことを確認してください',
    'ErrorValidValue'       => 'エラー、すべての条件が有効な値であることを確認してください',
    'Etc'                   => 'など',
    'EventFilter'           => 'イベントフィルタ',
    'EventId'               => 'イベントID',
    'EventName'             => 'イベント名',
    'EventPrefix'           => 'イベント接頭辞',
    'ExportCompress'        => '圧縮を使用',
    'ExportDetails'         => 'イベント詳細をエクスポート',
    'ExportMatches'         => '一致したイベントをエクスポート',
    'Exif'                  => '画像にEXIFデータを埋め込む',
    'DownloadVideo'         => 'ビデオをダウンロード',
    'GenerateDownload'      => 'ダウンロードを生成',
    'EventsLoading'         => 'イベントを読み込み中',
    'ExistsInFileSystem'    => 'ファイルシステムに存在',
    'ExportFailed'          => 'エクスポートに失敗しました',
    'ExportFormat'          => 'エクスポートファイル形式',
    'ExportFormatTar'       => 'Tar',
    'ExportFormatZip'       => 'Zip',
    'ExportFrames'          => 'フレーム詳細をエクスポート',
    'ExportImageFiles'      => '画像ファイルをエクスポート',
    'ExportLog'             => 'ログをエクスポート',
    'Exporting'             => 'エクスポート中',
    'ExportMiscFiles'       => 'その他のファイルをエクスポート (存在する場合)',
    'ExportOptions'         => 'エクスポートオプション',
    'ExportSucceeded'       => 'エクスポートが成功しました',
    'ExportVideoFiles'      => 'ビデオファイルをエクスポート (存在する場合)',
    'FastForward'           => '早送り',
    'FilterArchiveEvents'   => '一致したイベントをアーカイブ',
    'FilterUnarchiveEvents' => '一致したイベントのアーカイブを解除',
    'FilterUpdateDiskSpace' => '使用済みディスクスペースを更新',
    'FilterDeleteEvents'    => '一致したイベントを削除',
    'FilterCopyEvents'      => '一致したイベントをコピー',
    'FilterLockRows'        => '行をロック',
    'FilterMoveEvents'      => '一致したイベントを移動',
    'FilterEmailEvents'     => '一致したイベントの詳細をメールで送信',
    'FilterEmailTo'         => '送信先メールアドレス',
    'FilterEmailSubject'    => 'メール件名',
    'FilterEmailBody'       => 'メール本文',
    'FilterExecuteEvents'   => '一致したイベントに対してコマンドを実行',
    'FilterLog'             => 'フィルターログ',
    'FilterMessageEvents'   => '一致したイベントの詳細をメッセージ送信',
    'FilterPx'              => 'フィルターピクセル',
    'FilterUnset'           => 'フィルターの幅と高さを指定する必要があります',
    'FilterUploadEvents'    => '一致したイベントをアップロード',
    'FilterUser'            => 'フィルターを実行するユーザー',
    'FilterVideoEvents'     => '一致したイベントのビデオを作成',
    'FlippedHori'           => '水平に反転',
    'FlippedVert'           => '垂直に反転',
    'ForceAlarm'            => 'アラームを強制',
    'FPS'                   => 'fps',
    'FPSReportInterval'     => 'FPSレポート間隔',
    'FrameId'               => 'フレームID',
    'FrameRate'             => 'フレームレート',
    'FrameSkip'             => 'フレームスキップ',
    'MotionFrameSkip'       => 'モーションフレームスキップ',
    'GenerateVideo'         => 'ビデオを生成',
    'GeneratingVideo'       => 'ビデオを生成中',
    'GetCurrentLocation'    => '現在の場所を取得',
    'GoToZoneMinder'        => 'ZoneMinder.comへ',
    'HasFocusSpeed'         => 'フォーカス速度を持つ',
    'HasGainSpeed'          => 'ゲイン速度を持つ',
    'HasHomePreset'         => 'ホームプリセットを持つ',
    'HasIrisSpeed'          => 'アイリス速度を持つ',
    'HasPanSpeed'           => '水平移動速度を持つ',
    'HasPresets'            => 'プリセットを持つ',
    'HasTiltSpeed'          => '垂直移動速度を持つ',
    'HasTurboPan'           => 'ターボ水平移動を持つ',
    'HasTurboTilt'          => 'ターボ垂直移動を持つ',
    'HasWhiteSpeed'         => 'ホワイトバランス速度を持つ',
    'HasZoomSpeed'          => 'ズーム速度を持つ',
    'HighBW'                => '高帯域',
    'ImageBufferSize'       => '画像バッファサイズ (フレーム数)',
    'MaxImageBufferCount'   => '最大画像バッファサイズ (フレーム数)',
    'InvalidateTokens'      => '生成されたすべてのトークンを無効にする',
    'KeyString'             => 'キー文字列',
    'LimitResultsPost'      => '結果のみ', // 「Limit to first N results only」のフレーズの最後に使用されます
    'LimitResultsPre'       => '最初のN結果に制限', // 「Limit to first N results only」のフレーズの最初に使用されます
    'LinkedMonitors'        => 'リンクされたモニター',
    'ListMatches'           => '一致したイベントのリスト',
    'LoggedInAs'            => 'ログイン中: ',
    'LoggingIn'             => 'ログイン中',
    'LowBW'                 => '低帯域',
    'MaxBandwidth'          => '最大帯域幅',
    'MaxBrScore'            => '最大<br/>スコア',
    'MaxFocusRange'         => '最大フォーカス範囲',
    'MaxFocusSpeed'         => '最大フォーカス速度',
    'MaxFocusStep'          => '最大フォーカスステップ',
    'MaxGainRange'          => '最大ゲイン範囲',
    'MaxGainSpeed'          => '最大ゲイン速度',
    'MaxGainStep'           => '最大ゲインステップ',
    'MaximumFPS'            => '最大FPS',
    'MaxIrisRange'          => '最大アイリス範囲',
    'MaxIrisSpeed'          => '最大アイリス速度',
    'MaxIrisStep'           => '最大アイリスステップ',
    'MaxPanRange'           => '最大水平移動範囲',
    'MaxPanSpeed'           => '最大水平移動速度',
    'MaxPanStep'            => '最大水平移動ステップ',
    'MaxTiltRange'          => '最大垂直移動範囲',
    'MaxTiltSpeed'          => '最大垂直移動速度',
    'MaxTiltStep'           => '最大垂直移動ステップ',
    'MaxWhiteRange'         => '最大ホワイトバランス範囲',
    'MaxWhiteSpeed'         => '最大ホワイトバランス速度',
    'MaxWhiteStep'          => '最大ホワイトバランスステップ',
    'MaxZoomRange'          => '最大ズーム範囲',
    'MaxZoomSpeed'          => '最大ズーム速度',
    'MaxZoomStep'           => '最大ズームステップ',
    'MediumBW'              => '中帯域',
    'MetaConfig'            => 'メタ構成',
    'MinAlarmAreaLtMax'     => '最小アラームエリアは最大値未満である必要があります',
    'MinAlarmAreaUnset'     => '最小アラームピクセル数を指定する必要があります',
    'MinBlobAreaLtMax'      => '最小ブロブエリアは最大値未満である必要があります',
    'MinBlobAreaUnset'      => '最小ブロブピクセル数を指定する必要があります',
    'MinBlobLtMinFilter'    => '最小ブロブエリアは最小フィルターエリア以下である必要があります',
    'MinBlobsLtMax'         => '最小ブロブ数は最大値未満である必要があります',
    'MinBlobsUnset'         => '最小ブロブ数を指定する必要があります',
    'MinFilterAreaLtMax'    => '最小フィルターエリアは最大値未満である必要があります',
    'MinFilterAreaUnset'    => '最小フィルターピクセル数を指定する必要があります',
    'MinFilterLtMinAlarm'   => '最小フィルターエリアは最小アラームエリア以下である必要があります',
    'MinFocusRange'         => '最小フォーカス範囲',
    'MinFocusSpeed'         => '最小フォーカス速度',
    'MinFocusStep'          => '最小フォーカスステップ',
    'MinGainRange'          => '最小ゲイン範囲',
    'MinGainSpeed'          => '最小ゲイン速度',
    'MinGainStep'           => '最小ゲインステップ',
    'MinIrisRange'          => '最小アイリス範囲',
    'MinIrisSpeed'          => '最小アイリス速度',
    'MinIrisStep'           => '最小アイリスステップ',
    'MinPanRange'           => '最小水平移動範囲',
    'MinPanSpeed'           => '最小水平移動速度',
    'MinPanStep'            => '最小水平移動ステップ',
    'MinPixelThresLtMax'    => '最小ピクセルしきい値は最大値未満である必要があります',
    'MinPixelThresUnset'    => '最小ピクセルしきい値を指定する必要があります',
    'MinSectionlength'      => '最小セクション長',
    'MinTiltRange'          => '最小垂直移動範囲',
    'MinTiltSpeed'          => '最小垂直移動速度',
    'MinTiltStep'           => '最小垂直移動ステップ',
    'MinWhiteRange'         => '最小ホワイトバランス範囲',
    'MinWhiteSpeed'         => '最小ホワイトバランス速度',
    'MinWhiteStep'          => '最小ホワイトバランスステップ',
    'MinZoomRange'          => '最小ズーム範囲',
    'MinZoomSpeed'          => '最小ズーム速度',
    'MinZoomStep'           => '最小ズームステップ',
    'ModectDuringPTZ'       => 'PTZ動作中にモーション検出を行う',
    'MonitorIds'            => 'モニター&nbsp;ID',
    'MonitorPresetIntro'    => '以下のリストから適切なプリセットを選択してください。<br/><br/>これは、現在のモニターに既に構成されている値を上書きする可能性がある',
    'MonitorPreset'         => 'モニタープリセット',
    'MonitorProbeIntro'     => '以下のリストには、検出されたアナログおよびネットワークカメラと、それらがすでに使用されているか、選択可能かどうかが表示されています。<br/><br/>以下のリストから目的のエントリを選択してください。<br/><br/>すべてのカメラが検出されるわけではないことに注意してください。また、ここでカメラを選択すると、現在のモニターに対してすでに構成されている値が上書きされる可能性があります。<br/><br/>',
    'MonitorProbe'          => 'モニタープローブ',
    'MontageReview'         => 'モンタージュレビュー',
    'MtgDefault'            => 'デフォルト',              // Added 2013.08.15.
    'Mtg2widgrd'            => '2幅グリッド',              // Added 2013.08.15.
    'Mtg3widgrd'            => '3幅グリッド',              // Added 2013.08.15.
    'Mtg4widgrd'            => '4幅グリッド',              // Added 2013.08.15.
    'Mtg3widgrx'            => '3幅グリッド、スケーリング、アラーム時に拡大',              // Added 2013.08.15.
    'MustBeGe'              => '以上でなければなりません',
    'MustBeLe'              => '以下でなければなりません',
    'MustConfirmPassword'   => 'パスワードを確認する必要があります',
    'MustSupplyPassword'    => 'パスワードを入力する必要があります',
    'MustSupplyUsername'    => 'ユーザー名を入力する必要があります',
    'NewGroup'              => '新しいグループ',
    'NewLabel'              => '新しいラベル',
    'NewPassword'           => '新しいパスワード',
    'NewState'              => '新しい状態',
    'NewUser'               => '新しいユーザー',
    'NextMonitor'           => '次のモニター',
    'NoDetectedCameras'     => '検出されたカメラはありません',
    'NoDetectedProfiles'    => '検出されたプロファイルはありません',
    'NoFramesRecorded'      => 'このイベントに記録されたフレームはありません',
    'NoGroup'               => 'グループなし',
    'NoneAvailable'         => '利用可能なものはありません',
    'NoSavedFilters'        => '保存されたフィルターはありません',
    'NoStatisticsRecorded'  => 'このイベント/フレームに記録された統計情報はありません',
    'NumPresets'            => 'プリセット数',
    'OnvifProbe'            => 'ONVIF',
    'OnvifProbeIntro'       => '以下のリストには、検出されたONVIFカメラと、それらがすでに使用されているか、選択可能かどうかが表示されています。<br/><br/>以下のリストから目的のエントリを選択してください。<br/><br/>すべてのカメラが検出されるわけではないことに注意してください。また、ここでカメラを選択すると、現在のモニターに対してすでに構成されている値が上書きされる可能性があります。<br/><br/>',
    'OnvifCredentialsIntro' => '選択したカメラのユーザー名とパスワードを入力してください。<br/>カメラにユーザーが作成されていない場合、ここで指定したユーザーが指定されたパスワードで作成されます。<br/><br/>',
    'OpEq'                  => '等しい',
    'OpGtEq'                => '以上',
    'OpGt'                  => 'より大きい',
    'OpIn'                  => 'セット内',
    'OpLtEq'                => '以下',
    'OpLt'                  => 'より小さい',
    'OpMatches'             => '一致する',
    'OpNe'                  => '等しくない',
    'OpNotIn'               => 'セット内でない',
    'OpNotMatches'          => '一致しない',
    'OpIs'                  => 'は',
    'OpIsNot'               => 'はない',
    'OpLike'                => '含む',
    'OpNotLike'             => '含まない',
    'OptionalEncoderParam'  => 'オプションエンコーダーパラメータ',
    'OptionHelp'            => 'オプションヘルプ',
    'OptionRestartWarning'  => 'これらの変更は、システムが実行中の間は完全には反映されない場合があります。変更が完了したら、ZoneMinderを再起動してください。',
    'Options'               => 'オプション',
    'Order'                 => '順序',
    'OrEnterNewName'        => 'または新しい名前を入力',
    'OverwriteExisting'     => '既存のものを上書き',
    'PanLeft'               => '左に水平移動',
    'PanRight'              => '右に水平移動',
    'PanTilt'               => '水平移動/垂直移動',
    'ParentGroup'           => '親グループ',
    'PasswordsDifferent'    => '新しいパスワードと確認用パスワードが異なります',
    'PathToIndex'           => 'インデックスへのパス',
    'PathToZMS'             => 'ZMSへのパス',
    'PathToApi'             => 'APIへのパス',
    'PauseCycle'            => 'サイクルを一時停止',
    'PhoneBW'               => '電話帯域',
    'PixelDiff'             => 'ピクセル差',
    'Pixels'                => 'ピクセル',
    'PlayAll'               => 'すべて再生',
    'PlayCycle'             => 'サイクルを再生',
    'PleaseWait'            => 'お待ちください',
    'PostEventImageBuffer'  => 'イベント後の画像数',
    'PreEventImageBuffer'   => 'イベント前の画像数',
    'PreserveAspect'        => 'アスペクト比を保持',
    'PreviousMonitor'       => '前のモニター',
    'PrivacyAbout'          => 'プライバシーについて',
    'PrivacyAboutText'      => '2002年から、ZoneMinderはLinuxプラットフォーム向けの無料およびオープンソースのビデオ管理システム（VMS）ソリューションとして最前線に立っています。ZoneMinderはコミュニティによってサポートされ、プロジェクトに余暇を費やすボランティアによって管理されています。ZoneMinderを改善する最良の方法は、参加することです。',
    'PrivacyContact'        => '連絡先',
    'PrivacyContactText'    => 'プライバシーポリシーに関する質問や情報の削除を希望される場合は、<a href="https://zoneminder.com/contact/">こちら</a>からご連絡ください。<br><br>サポートには、以下の3つの主要な方法があります：<ul><li>ZoneMinderの<a href="https://forums.zoneminder.com/">ユーザーフォーラム</a></li><li>ZoneMinderの<a href="https://zoneminder-chat.herokuapp.com/">Slackチャンネル</a></li><li>ZoneMinderの<a href="https://github.com/ZoneMinder/zoneminder/issues">Githubフォーラム</a></li></ul><p>Githubフォーラムはバグ報告専用です。その他の質問やコメントについては、ユーザーフォーラムまたはSlackチャンネルをご利用ください。</p>',
    'PrivacyCookies'        => 'クッキー',
    'PrivacyCookiesText'    => 'ZoneMinderサーバーと通信する際にウェブブラウザまたはモバイルアプリを使用する場合、クライアントにZMSESSIDクッキーが作成され、ZoneMinderサーバーとのセッションが一意に識別されます。ZmCSSおよびzmSkinクッキーが作成され、スタイルおよびスキンの選択が記憶されます。',
    'PrivacyTelemetry'      => 'テレメトリー',
    'PrivacyTelemetryText'  => 'ZoneMinderはオープンソースであるため、登録せずにインストールすることができます。これにより、どれだけのシステムが存在するのか、最大のシステムはどれか、どのようなシステムが存在するのか、これらのシステムがどこにあるのかなどの質問に答えるのが難しくなります。これらの質問への回答を知ることで、私たちはユーザーからの質問に答え、主要なユーザーベースに基づいて優先順位を設定するのに役立ちます。',
    'PrivacyTelemetryList'  => 'ZoneMinderテレメトリーデーモンは、次のデータを収集します：
    <ul>
      <li>一意の識別子（UUID）</li>
      <li>都市に基づく位置は<a href="https://ipinfo.io/geo">ipinfo.io</a>をクエリすることで収集されます。都市、地域、国、緯度、経度のパラメータが保存されます。緯度と経度の座標は、都市または町レベルまで正確です！</li>
      <li>現在の時刻</li>
      <li>モニターの総数</li>
      <li>イベントの総数</li>
      <li>システムアーキテクチャ</li>
      <li>オペレーティングシステムカーネル、ディストリビューション、ディストリビューションバージョン</li>
      <li>ZoneMinderのバージョン</li>
      <li>メモリの総量</li>
      <li>CPUコア数</li>
    </ul>',
    'PrivacyMonitorList'    => '各モニターから収集される構成パラメータは次のとおりです：
   <ul>
    <li>Id</li>
    <li>名前</li>
    <li>メーカー</li>
    <li>モデル</li>
    <li>タイプ</li>
    <li>機能</li>
    <li>幅</li>
    <li>高さ</li>
    <li>色数</li>
    <li>最大FPS</li>
    <li>アラーム最大FPS</li>
   </ul>',
    'PrivacyConclusionText' => '私たちは、あなたのカメラから特定の画像データを収集していません。カメラが何を見ているのかはわかりません。このデータは販売されたり、ここに記載されている以外の目的で使用されることはありません。受け入れをクリックすることで、ZoneMinderをより良い製品にするためにこのデータを送信することに同意したことになります。拒否をクリックすると、ZoneMinderとそのすべての機能を自由に使用し続けることができます。',
    'Probe'                 => 'プローブ',
    'ProfileProbe'          => 'ストリームプローブ',
    'ProfileProbeIntro'     => '以下のリストには、選択したカメラの既存のストリームプロファイルが表示されています。<br/><br/>以下のリストから目的のエントリを選択してください。<br/><br/>ZoneMinderは追加のプロファイルを構成することができず、ここでカメラを選択すると、現在のモニターに対してすでに構成されている値が上書きされる可能性があることに注意してください。<br/><br/>',
    'RecaptchaWarning'      => 'reCaptchaのシークレットキーが無効です。修正してください。さもないと、reCaptchaは機能しません。',
    'RecordAudio'           => 'イベントを保存する際に音声ストリームを保存するかどうか。',
    'RefImageBlendPct'      => '参照画像ブレンド％',
    'RemoteHostName'        => 'ホスト名',
    'RemoteHostPath'        => 'パス',
    'RemoteHostSubPath'     => 'サブパス',
    'RemoteHostPort'        => 'ポート',
    'RemoteImageColours'    => '画像の色',
    'RemoteMethod'          => 'メソッド',
    'RemoteProtocol'        => 'プロトコル',
    'ReplayAll'             => 'すべてのイベント',
    'ReplayGapless'         => 'ギャップレスイベント',
    'ReplaySingle'          => '単一イベント',
    'ReportEventAudit'      => 'イベント監査レポート',
    'ResetEventCounts'      => 'イベントカウントをリセット',
    'RestrictedCameraIds'   => '制限されたカメラID',
    'RestrictedMonitors'    => '制限されたモニター',
    'ReturnDelay'           => '戻る遅延',
    'ReturnLocation'        => '戻る位置',
    'RevokeAllTokens'       => 'すべてのトークンを取り消す',
    'RotateLeft'            => '左に回転',
    'RotateRight'           => '右に回転',
    'RTSPTransport'         => 'RTSPトランスポートプロトコル',
    'RunAudit'              => '監査プロセスを実行',
    'RunLocalUpdate'        => 'zmupdate.plを実行して更新してください',
    'RunMode'               => '実行モード',
    'RunState'              => '実行状態',
    'RunStats'              => '統計プロセスを実行',
    'RunTrigger'            => 'トリガープロセスを実行',
    'RunEventNotification'  => 'イベント通知プロセスを実行',
    'SaveAs'                => '名前を付けて保存',
    'SaveFilter'            => 'フィルターを保存',
    'SaveJPEGs'             => 'JPEGを保存',
    'Sectionlength'         => 'セクションの長さ',
    'SelectMonitors'        => 'モニターを選択',
    'SelectFormat'          => 'フォーマットを選択',
    'SelectLog'             => 'ログを選択',
    'SelfIntersecting'      => 'ポリゴンのエッジは交差してはいけません',
    'SetNewBandwidth'       => '新しい帯域幅を設定',
    'SetPreset'             => 'プリセットを設定',
    'ShowFilterWindow'      => 'フィルターウィンドウを表示',
    'ShowTimeline'          => 'タイムラインを表示',
    'SignalCheckColour'     => '信号チェックの色',
    'SignalCheckPoints'     => '信号チェックポイント',
    'SkinDescription'       => 'このセッションのスキンを変更',
    'CSSDescription'        => 'このセッションのCSSを変更',
    'SortAsc'               => '昇順',
    'SortBy'                => '並べ替え',
    'SortDesc'              => '降順',
    'SourceColours'         => 'ソースの色',
    'SourcePath'            => 'ソースパス',
    'SourceType'            => 'ソースタイプ',
    'SpeedHigh'             => '高速',
    'SpeedLow'              => '低速',
    'SpeedMedium'           => '中速',
    'SpeedTurbo'            => 'ターボスピード',
    'StatusUnknown'         => '不明',
    'StatusConnected'       => 'キャプチャ中',
    'StatusNotRunning'      => '実行されていません',
    'StatusRunning'         => 'キャプチャしていません',
    'StepBack'              => '戻る',
    'StepForward'           => '進む',
    'StepLarge'             => '大きなステップ',
    'StepMedium'            => '中程度のステップ',
    'StepNone'              => 'ステップなし',
    'StepSmall'             => '小さなステップ',
    'StorageArea'           => 'ストレージエリア',
    'StorageDoDelete'       => '削除する',
    'StorageScheme'         => 'スキーム',
    'StreamReplayBuffer'    => 'ストリームリプレイ画像バッファ',
    'TargetColorspace'      => 'ターゲットカラースペース',
    'TimeDelta'             => '時間差',
    'TimelineTip1'          => 'グラフ上にマウスを乗せると、スナップショット画像とイベントの詳細が表示されます。',
    'TimelineTip2'          => 'グラフの色付きセクションや画像をクリックしてイベントを表示します。',
    'TimelineTip3'          => '背景をクリックして、クリックに基づいた小さな時間範囲にズームインします。',
    'TimelineTip4'          => '以下のコントロールを使用して、ズームアウトしたり、時間範囲を前後にナビゲートします。',
    'TimestampLabelFormat'  => '時刻ラベル形式',
    'TimestampLabelX'       => '時刻ラベル X',
    'TimestampLabelY'       => '時刻ラベル Y',
    'TimestampLabelSize'    => 'フォントサイズ',
    'TimeStamp'             => '時刻',
    'TooManyEventsForTimeline' => 'タイムラインに対してイベントが多すぎます。モニターの数を減らすか、タイムラインの表示範囲を狭くしてください。',
    'TotalBrScore'          => '合計<br/>スコア',
    'TrackDelay'            => 'トラック遅延',
    'TrackMotion'           => 'トラックモーション',
    'TurboPanSpeed'         => 'ターボ水平移動速度',
    'TurboTiltSpeed'        => 'ターボティルト速度',
    'TZUnset'               => '設定されていません - php.ini の値を使用',
    'UpdateAvailable'       => 'ZoneMinder の更新があります。',
    'UpdateNotNecessary'    => '更新は必要ありません。',
    'UsedPlugins'           => '使用中のプラグイン',
    'UseFilterExprsPost'    => '&nbsp;フィルター&nbsp;式',
    'UseFilterExprsPre'     => '使用&nbsp;',
    'UseFilter'             => 'フィルターを使用',
    'VersionIgnore'         => 'このバージョンを無視',
    'VersionRemindDay'      => '1日後に再通知',
    'VersionRemindHour'     => '1時間後に再通知',
    'VersionRemindNever'    => '新しいバージョンについて再通知しない',
    'VersionRemindWeek'     => '1週間後に再通知',
    'VersionRemindMonth'    => '1ヶ月後に再通知',
    'ViewMatches'           => '一致したイベントを表示',
    'VideoFormat'           => 'ビデオフォーマット',
    'VideoGenFailed'        => 'ビデオ生成に失敗しました！',
    'VideoGenFiles'         => '既存のビデオファイル',
    'VideoGenNoFiles'       => 'ビデオファイルが見つかりません',
    'VideoGenParms'         => 'ビデオ生成パラメータ',
    'VideoGenSucceeded'     => 'ビデオ生成に成功しました！',
    'VideoSize'             => 'ビデオサイズ',
    'VideoWriter'           => 'ビデオライター',
    'ViewAll'               => 'すべて表示',
    'ViewEvent'             => 'イベントを表示',
    'ViewPaged'             => 'ページ分け表示',
    'V4LCapturesPerFrame'   => 'フレームごとのキャプチャ数',
    'V4LMultiBuffer'        => 'マルチバッファリング',
    'WarmupFrames'          => 'ウォームアップフレーム',
    'WebColour'             => 'ウェブカラー',
    'WebSiteUrl'            => 'ウェブサイトURL',
    'WhiteBalance'          => 'ホワイトバランス',
    'X10ActivationString'   => 'X10 アクティベーション文字列',
    'X10InputAlarmString'   => 'X10 入力アラーム文字列',
    'X10OutputAlarmString'  => 'X10 出力アラーム文字列',
    'YouNoPerms'            => 'このリソースにアクセスする権限がありません。',
    'ZoneAlarmColour'       => 'アラームカラー（赤/緑/青）',
    'ZoneArea'              => 'ゾーンエリア',
    'ZoneFilterSize'        => 'フィルター幅/高さ（ピクセル）',
    'ZoneMinderLog'         => 'ZoneMinder ログ',
    'ZoneMinMaxAlarmArea'   => '最小/最大アラームエリア',
    'ZoneMinMaxBlobArea'    => '最小/最大ブロブエリア',
    'ZoneMinMaxBlobs'       => '最小/最大ブロブ',
    'ZoneMinMaxFiltArea'    => '最小/最大フィルターエリア',
    'ZoneMinMaxPixelThres'  => '最小/最大ピクセル閾値（0-255）',
    'ZoneOverloadFrames'    => 'オーバーロードフレーム無視カウント',
    'ZoneExtendAlarmFrames' => 'アラームフレームカウントを拡張',
    'ZoomIn'                => 'ズームイン',
    'ZoomOut'               => 'ズームアウト',
// language names translation
    'es_la' => 'スペイン語（ラテンアメリカ）',
    'es_CR' => 'スペイン語（コスタリカ）',
    'es_ar' => 'スペイン語（アルゼンチン）',
    'es_es' => 'スペイン語（スペイン）',
    'en_gb' => 'イギリス英語',
    'en_us' => 'アメリカ英語',
    'fr_fr' => 'フランス語',
    'cs_cz' => 'チェコ語',
    'zh_cn' => '簡体字中国語',
    'zh_tw' => '繁体字中国語',
    'de_de' => 'ドイツ語',
    'it_it' => 'イタリア語',
    'ja_jp' => '日本語',
    'hu_hu' => 'ハンガリー語',
    'pl_pl' => 'ポーランド語',
    'pt_br' => 'ポルトガル語（ブラジル）',
    'ru_ru' => 'ロシア語',
    'nl_nl' => 'オランダ語',
    'se_se' => 'サーミ語',
    'et_ee' => 'エストニア語',
    'he_il' => 'ヘブライ語',
    'dk_dk' => 'デンマーク語',
    'ro_ro' => 'ルーマニア語',
    '12.5% (Outdoor)' => '12.5% (屋外)',
    '1 Hour' => '1 時間',
    '24 Hour' => '24 時間',
    '50% (Alarm lasts a moment)' => '50% (アラームは一瞬です)',
    '6.25% (Indoor)' => '6.25% (屋内)',
    '8 Hour' => '8 時間',
    'Accept' => '承認',
    'Action' => 'アクション',
    'Actions' => 'アクション',
    'Actual' => '実際',
    'Add' => '追加',
    'Add Monitors' => 'モニターを追加',
    'Add New Monitor' => '新しいモニターを追加',
    'Add New Report' => '新しいレポートを追加',
    'Add New Zone' => '新しいゾーンを追加',
    'Alarm' => 'アラーム',
    'Alert' => 'アラート',
    'All' => 'すべて',
    'All Events' => 'すべてのイベント',
    'All Groups' => 'すべてのグループ',
    'All Manufacturers' => 'すべてのメーカー',
    'All Monitors' => 'すべてのモニター',
    'All Tags' => 'すべてのタグ',
    'Always' => '常に',
    'Analysing' => '分析中',
    'Analysis' => '分析',
    'Analysis Enabled' => '分析が有効',
    'Analysis FPS' => '分析 FPS',
    'Analysis Image' => '分析画像',
    'Analysis images only (if available)' => '分析画像のみ (利用可能な場合)',
    'Analysis is disabled' => '分析が無効',
    'AnalysisSource' => '分析ソース',
    'API' => 'API',
    'API Enabled' => 'API が有効',
    'Apply' => '適用',
    'Apply the last tag, then play the next event' => '最後のタグを適用してから次のイベントを再生',
    'Apply the last tag, then play the previous event' => '最後のタグを適用してから前のイベントを再生',
    'Archive' => 'アーカイブ',
    'Archived' => 'アーカイブ済み',
    'Are you still watching?' => 'まだ視聴していますか?',
    'AttrStorageDiskSpace' => '属性ストレージディスクスペース',
    'Audio recording only available with FFMPEG' => '音声録音は FFMPEG でのみ利用可能',
    'auto' => '自動',
    'Auto' => '自動',
    'Available' => '利用可能',
    'Back' => '戻る',
    'Background' => '背景',
    'Bandwidth' => '帯域幅',
    'Bind Interfaces' => 'インターフェースをバインド',
    'Blend' => 'ブレンド',
    'Blend (25%)' => 'ブレンド (25%)',
    'Blobs' => 'ブロブ',
    'Both' => '両方',
    'Buffer' => 'バッファ',
    'Buffers' => 'バッファ',
    'Camera Passthrough' => 'カメラパススルー',
    'Camera Passthrough - only for FFMPEG' => 'カメラパススルー - FFMPEG のみ',
    'Cancel' => 'キャンセル',
    'Capturing' => 'キャプチャ中',
    'Capturing FPS' => 'キャプチャ FPS',
    'Cause' => '原因',
    'Change State' => '状態を変更',
    'Close' => '閉じる',
    'Codec' => 'コーデック',
    'Component' => 'コンポーネント',
    'Concurrent' => '同時',
    'Config' => '設定',
    'Configuration' => '構成',
    'ConfirmUnarchiveTitle' => 'アーカイブ解除の確認',
    'Console' => 'コンソール',
    'Control' => '制御',
    'Cpu' => 'CPU',
    'CpuLoad' => 'CPU負荷',
    'Create' => '作成',
    'Created By' => '作成者',
    'Created On' => '作成日',
    'Current' => '現在',
    'Custom' => 'カスタム',
    'Cycle' => 'サイクル',
    'Data' => 'データ',
    'Date Time' => '日時',
    'Day' => '日',
    'DB' => 'DB',
    'Debug' => 'デバッグ',
    'Decline' => '拒否',
    'Decoder' => 'デコーダー',
    'DecoderHWAccelDevice' => 'デコーダー HW アクセラレータデバイス',
    'DecoderHWAccelName' => 'デコーダー HW アクセラレータ名',
    'Decoding' => 'デコード中',
    'Decoding Enabled' => 'デコードが有効',
    'Deep' => '深い',
    'Default' => 'デフォルト',
    'Deinterlacing' => 'インターレース解除',
    'Delay' => '遅延',
    'Delete' => '削除',
    'Delete layout' => 'レイアウトを削除',
    'Description' => '説明',
    'Device' => 'デバイス',
    'Devices' => 'デバイス',
    'DHCP' => 'DHCP',
    'DHCP Authoritative' => 'DHCP 権威',
    'DHCP Range' => 'DHCP 範囲',
    'Disabled' => '無効',
    'Discard' => '破棄',
    'Disk' => 'ディスク',
    'DiskSpace' => 'ディスクスペース',
    'Display' => '表示',
    'Displaying' => '表示中',
    'Download' => 'ダウンロード',
    'Download Failed' => 'ダウンロード失敗',
    'Download File Name' => 'ダウンロードファイル名',
    'Download Image' => '画像をダウンロード',
    'Downloading' => 'ダウンロード中',
    'Download Succeeded' => 'ダウンロード成功',
    'Download Video' => 'ビデオをダウンロード',
    'Duration' => '期間',
    'Edit' => '編集',
    'Edit monitor' => 'モニターを編集',
    'Effective Permission' => '有効な権限',
    'Email' => 'メール',
    'Email Address' => 'メールアドレス',
    'Emailed' => 'メール送信済み',
    'Email Format' => 'メール形式',
    'Enabled' => '有効',
    'Encode' => 'エンコード',
    'Encoder' => 'エンコーダー',
    'End Date/Time' => '終了日時',
    'Ending' => '終了',
    'Error' => 'エラー',
    'Error reading Layout' => 'レイアウト読み込みエラー',
    'Estimated Ram Use' => '推定 RAM 使用量',
    'Event Close Mode' => 'イベント終了モード',
    'Event End Command' => 'イベント終了コマンド',
    'Event Id' => 'イベント ID',
    'Events' => 'イベント',
    'Event Start Command' => 'イベント開始コマンド',
    'Event Start Time' => 'イベント開始時間',
    'Event Type' => 'イベントタイプ',
    'Execute' => '実行',
    'Execute Interval' => '実行間隔',
    'Exit Fullscreen' => '全画面表示を終了',
    'Expires' => '期限切れ',
    'Export' => 'エクスポート',
    'Extra Large' => '特大',
    'False' => '偽',
    'Far' => '遠い',
    'Feed' => 'フィード',
    'Ffmpeg' => 'Ffmpeg',
    'File' => 'ファイル',
    'Filename' => 'ファイル名',
    'Files' => 'ファイル',
    'Filter' => 'フィルター',
    'FilterDebug' => 'フィルターデバッグ',
    'FilterEmailServer' => 'フィルターメールサーバー',
    'Filters' => 'フィルター',
    'First' => '最初',
    'FirstEvent' => '最初のイベント',
    'Fit' => 'フィット',
    'Fit to width' => '幅に合わせる',
    'FnMocord' => 'FnMocord',
    'FnModect' => 'FnModect',
    'FnMonitor' => 'FnMonitor',
    'FnNodect' => 'FnNodect',
    'FnNone' => 'FnNone',
    'FnRecord' => 'FnRecord',
    'Focus' => 'フォーカス',
    'Four field motion adaptive - Hard' => '4フィールド動き適応 - ハード',
    'Four field motion adaptive - Medium' => '4フィールド動き適応 - ミディアム',
    'Four field motion adaptive - Soft' => '4フィールド動き適応 - ソフト',
    'Frame' => 'フレーム',
    'Frame Id' => 'フレーム ID',
    'frames' => 'フレーム',
    'Frames' => 'フレーム',
    'Frames + Analysis images (if available)' => 'フレーム + 分析画像 (利用可能な場合)',
    'Frames only' => 'フレームのみ',
    'Free' => '無料',
    'Full Colour' => 'フルカラー',
    'Full Name' => 'フルネーム',
    'Fullscreen' => '全画面表示',
    'Function' => '機能',
    'Gain' => 'ゲイン',
    'General' => '一般',
    'Grayscale' => 'グレースケール',
    'Grey' => 'グレー',
    'Group' => 'グループ',
    'Groups' => 'グループ',
    'Groups Permissions' => 'グループの権限',
    'Height' => '高さ',
    'Hidden' => '非表示',
    'Hide Zones' => 'ゾーンを非表示',
    'High' => '高',
    'Home' => 'ホーム',
    'Home View' => 'ホームビュー',
    'Hostname' => 'ホスト名',
    'HostName' => 'ホスト名',
    'Hour' => '時間',
    'Id' => 'ID',
    'Idle' => 'アイドル',
    'Image' => '画像',
    'Images' => '画像',
    'Importance' => '重要性',
    'Import CSV' => 'CSVをインポート',
    'Import Monitors FROM CSV' => 'CSVからモニターをインポート',
    'In' => '内',
    'In +' => 'ズームイン',
    'Individual' => '個別',
    'Info' => '情報',
    'Inherit' => '継承',
    'Inside bottom' => '内部下部',
    'Interface' => 'インターフェース',
    'Interval' => '間隔',
    'Inverted' => '反転',
    'IP Address' => 'IPアドレス',
    'IPAddress' => 'IPアドレス',
    'Iris' => 'アイリス',
    'It is necessary to place monitors again and resave the Layout.' => 'モニターを再配置し、レイアウトを再保存する必要があります。',
    'Janus Audio Enabled' => 'Janusオーディオ有効',
    'Janus Enabled' => 'Janus有効',
    'Janus Live Stream' => 'Janusライブストリーム',
    'Janus Live Stream Audio' => 'Janusライブストリームオーディオ',
    'Janus Profile-ID Override' => 'JanusプロフィールIDオーバーライド',
    'Janus RTSP Session Timeout Override' => 'Janus RTSPセッションタイムアウトオーバーライド',
    'Janus Use RTSP Restream' => 'Janus RTSP再ストリーム使用',
    'Keyframes + Ondemand' => 'キーフレーム + オンデマンド',
    'KeyFrames Only' => 'キーフレームのみ',
    'Language' => '言語',
    'Large' => '大',
    'Last' => '最終',
    'Last Access' => '最終アクセス',
    'LastEvent' => '最終イベント',
    'Latitude' => '緯度',
    'Layout' => 'レイアウト',
    'Less important' => '重要度が低い',
    'Level' => 'レベル',
    'Libvlc' => 'Libvlc',
    'Line' => 'ライン',
    'Linear' => '線形',
    'List' => 'リスト',
    'Live' => 'ライブ',
    'Load' => '読み込み',
    'Local' => 'ローカル',
    'Location' => '場所',
    'Log' => 'ログ',
    'Logging' => 'ログ記録',
    'Login' => 'ログイン',
    'Logout' => 'ログアウト',
    'Longitude' => '経度',
    'Low' => '低',
    'Mac Address' => 'MACアドレス',
    'MAC Address' => 'MACアドレス',
    'Main' => 'メイン',
    'Man' => '人',
    'Manufacturer' => 'メーカー',
    'Map' => 'マップ',
    'Mark' => 'マーク',
    'Max 1024px' => '最大1024px',
    'Max 1280px' => '最大1280px',
    'Max 1600px' => '最大1600px',
    'Max 480px' => '最大480px',
    'Max 640px' => '最大640px',
    'Max 800px' => '最大800px',
    'MaxGap' => '最大ギャップ',
    'Medium' => '中',
    'Memory' => 'メモリ',
    'Message' => 'メッセージ',
    'MinGap' => '最小ギャップ',
    'minute' => '分',
    'minutes' => '分',
    'Misc' => 'その他',
    'MissingFiles' => '欠落ファイル',
    'MJPEG' => 'MJPEG',
    'Mode' => 'モード',
    'Model' => 'モデル',
    'Monitor is Deleted, Undelete' => 'モニターは削除されました。復元',
    'Monitor Permissions' => 'モニターの権限',
    'Monitors' => 'モニター',
    'Monitor status position' => 'モニターの状態位置',
    'Montage' => 'モンタージュ',
    'Montage Review' => 'モンタージュレビュー',
    'Month' => '月',
    'Motion Detection' => '動体検知',
    'Move' => '移動',
    'MP4' => 'MP4',
    'MQTT' => 'MQTT',
    'MQTT Enabled' => 'MQTT有効',
    'MQTT Subscriptions' => 'MQTTサブスクリプション',
    'Name' => '名前',
    'Near' => '近い',
    'Network' => 'ネットワーク',
    'Network Scan' => 'ネットワークスキャン',
    'New' => '新規',
    'NewStorage' => '新規ストレージ',
    'New Token' => '新しいトークン',
    'Next' => '次',
    'No' => 'いいえ',
    'No analysis FPS' => '分析FPSなし',
    'No blending (Alarm lasts forever)' => 'ブレンドなし (アラームは永続します)',
    'No capture FPS' => 'キャプチャFPSなし',
    'None' => 'なし',
    'Normal' => '通常',
    'Notes' => 'ノート',
    'Not important' => '重要でない',
    'Not Running' => '実行中ではない',
    'Not Showing Analysis' => '分析を表示していません',
    'NVSocket' => 'NVSocket',
    'Off' => 'オフ',
    'Offline' => 'オフライン',
    'On' => 'オン',
    'On Demand' => 'オンデマンド',
    'On Motion' => '動きに応じて',
    'On Motion / Trigger / etc' => '動き / トリガー / など',
    'ONVIF_Alarm_Text' => 'ONVIF_アラーム_テキスト',
    'ONVIF_Event_Listener' => 'ONVIF_イベント_リスナー',
    'ONVIF_EVENTS_PATH' => 'ONVIF_EVENTS_PATH',
    'ONVIF_Options' => 'ONVIF_オプション',
    'ONVIF_URL' => 'ONVIF_URL',
    'Open' => '開く',
    'Open full screen' => '全画面表示を開く',
    'Open watch page' => '視聴ページを開く',
    'Optimal' => '最適',
    'Orientation' => '向き',
    'Other' => 'その他',
    'Out' => '外',
    'Out -' => 'ズームアウト',
    'OutputCodec' => '出力コーデック',
    'OutputContainer' => '出力コンテナ',
    'Outside bottom' => '外部下部',
    'Pan' => '水平移動',
    'Password' => 'パスワード',
    'Path' => 'パス',
    'Pause' => '一時停止',
    'Permission' => '権限',
    'Phone' => '電話',
    'Play' => '再生',
    'Plugin' => 'プラグイン',
    'Point' => 'ポイント',
    'Port' => 'ポート',
    'Prealarm' => 'プレアラーム',
    'Preset' => 'プリセット',
    'Presets' => 'プリセット',
    'Prev' => '前',
    'Primary' => '主要',
    'Privacy' => 'プライバシー',
    'Progress' => '進行状況',
    'Protocol' => 'プロトコル',
    'PTZ Not available' => 'PTZ利用不可',
    'Rate' => 'レート',
    'Ratio' => '比率',
    'Reboot' => '再起動',
    'Recording' => '録画',
    'RecordingSource' => '録画ソース',
    'Reference' => '参照',
    'Refresh' => '更新',
    'Remote' => 'リモート',
    'Rename' => '名前変更',
    'Replay' => '再生',
    'Report Event Audit' => 'イベント監査レポート',
    'Reports' => 'レポート',
    'Reset' => 'リセット',
    'Resolution' => '解像度',
    'Restart' => '再起動',
    'Revoke Token' => 'トークンを取り消す',
    'Rewind' => '巻き戻し',
    'RTSP2Web Enabled' => 'RTSP2Web有効',
    'RTSP2Web Live Stream' => 'RTSP2Webライブストリーム',
    'RTSP2Web Type' => 'RTSP2Webタイプ',
    'RTSPServer' => 'RTSPサーバー',
    'RTSPStreamName' => 'RTSPストリーム名',
    'Running' => '実行中',
    'Run State' => '実行状態',
    's3fs' => 's3fs',
    'Save' => '保存',
    'Scale' => 'スケール',
    'Scan Network' => 'ネットワークスキャン',
    'Score' => 'スコア',
    'Secondary' => '副',
    'seconds' => '秒',
    'select' => '選択',
    'Select' => '選択',
    'Server' => 'サーバー',
    'Servers' => 'サーバー',
    'Service' => 'サービス',
    'Set' => '設定',
    'Settings' => '設定',
    'Shallow' => '浅い',
    'Showing Analysis' => '分析を表示',
    'Show on hover' => 'ホバー時に表示',
    'Show Zones' => 'ゾーンを表示',
    'Shutdown' => 'シャットダウン',
    'Size' => 'サイズ',
    'Skin' => 'スキン',
    'Skip Locked' => 'ロックされたものをスキップ',
    'Sleep' => 'スリープ',
    'Small' => '小',
    'Snapshot' => 'スナップショット',
    'Snapshots' => 'スナップショット',
    'SOAP WSA COMPLIANCE' => 'SOAP WSA準拠',
    'Sort' => 'ソート',
    'Source' => 'ソース',
    'SourceSecondPath' => 'ソースセカンドパス',
    'Speed' => '速度',
    'Start' => '開始',
    'Start Date/Time' => '開始日時',
    'Starting' => '開始中',
    'Startup Delay' => '起動遅延',
    'State' => '状態',
    'Stats' => '統計',
    'Status' => 'ステータス',
    'Stills' => '静止画',
    'Stop' => '停止',
    'Stopped' => '停止',
    'Storage' => 'ストレージ',
    'Stream' => 'ストリーム',
    'Stream quality' => 'ストリーム品質',
    'Summary' => '概要',
    'Swap' => 'スワップ',
    'System' => 'システム',
    'System Default' => 'システムデフォルト',
    'Tags' => 'タグ',
    'Tele' => 'テレ',
    'Telemetry' => 'テレメトリー',
    'This Layout was saved in previous version of ZoneMinder!' => 'このレイアウトは以前のバージョンのZoneMinderで保存されました！',
    'Thumbnail' => 'サムネイル',
    'Tilt' => '傾斜',
    'Time' => '時間',
    'Timeline' => 'タイムライン',
    'Timestamp' => '時刻',
    'to' => 'から',
    'Toggle cycle sidebar' => 'サイドバーの切り替え',
    'Toggle PTZ Controls' => 'PTZコントロールの切り替え',
    'Toggle Streaming/Stills' => 'ストリーミング/静止画の切り替え',
    'Total' => '合計',
    'Transform' => '変換',
    'Triggers' => 'トリガー',
    'True' => '真',
    'Type' => 'タイプ',
    'Unarchive' => 'アーカイブ解除',
    'Unarchived' => 'アーカイブ解除済み',
    'Units' => '単位',
    'Unknown' => '不明',
    'Unlimited' => '無制限',
    'Unspecified' => '未指定',
    'Update' => '更新',
    'Updated' => '更新済み',
    'Upload' => 'アップロード',
    'Url' => 'URL',
    'URL' => 'URL',
    'use_Amcrest_API' => 'use_Amcrest_API',
    'Use old ZoomPan' => '古いZoomPanを使用',
    'User' => 'ユーザー',
    'User for RTSP Server Auth' => 'RTSPサーバー認証用ユーザー',
    'Username' => 'ユーザー名',
    'Users' => 'ユーザー',
    'Use Wallclock Timestamps' => 'ウォールクロック時刻を使用',
    'Versions' => 'バージョン',
    'Video' => 'ビデオ',
    'Video paused. Continue watching?' => 'ビデオが一時停止しました。続けて視聴しますか？',
    'View' => 'ビュー',
    'Viewing' => '視聴中',
    'Viewing FPS' => '視聴FPS',
    'VNC' => 'VNC',
    'Wake' => '起動',
    'Warn if exceeded' => '超過時に警告',
    'Web' => 'ウェブ',
    'Week' => '週',
    'When' => 'いつ',
    'White' => '白',
    'Wide' => 'ワイド',
    'Width' => '幅',
    'X' => 'X',
    'X10' => 'X10',
    'Y' => 'Y',
    'Y-Channel (Greyscale)' => 'Yチャネル (グレースケール)',
    'Yes' => 'はい',
    'You are not logged in.' => 'ログインしていません。',
    'ZeroSize' => 'ゼロサイズ',
    'Zone' => 'ゾーン',
    'Zones' => 'ゾーン',
    'Zoom' => 'ズーム',
    'Zoom IN' => 'ズームイン',
    'Zoom OUT' => 'ズームアウト',
    'text or regular expression' => '文字列または正規表現を入力',
    'leave blank for auto' => '空白の場合は自動採番',
    'Python strftime format. %f for hundredths, %N for Monitor Name, %Q for show text.' => 'Pythonのstrftimeフォーマット。fは100分の1、%Nはモニター名、%Qはテキスト表示',
    'Camera IP Address' => 'カメラのIPアドレス',
    'Camera Username' => 'カメラのユーザ名',
    'Camera Password' => 'カメラのパスワード',
    'Enter new name for layout if desired' => 'レイアウト名を入力',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'          => '現在のログインは \'%1$s\' です',
    'EventCount'            => '%1$s %2$s', // 例えば '37 イベント' (下記の Vlang から)
    'LastEvents'            => '直近 %1$s %2$s', // 例えば '直近 37 イベント' (下記の Vlang から)
    'LatestRelease'         => '最新のリリースは v%1$s です。あなたは v%2$s を使用しています。',
    'MonitorCount'          => '%1$s %2$s', // 例えば '4 モニター' (下記の Vlang から)
    'MonitorFunction'       => 'モニター %1$s 機能',
    'RunningRecentVer'      => 'あなたは最新バージョンの ZoneMinder (v%s) を実行しています。',
    'VersionMismatch'       => 'バージョンの不一致。システムのバージョンは %1$s で、データベースのバージョンは %2$s です。',
);

// The next section allows you to describe a series of word ending and counts used to
// generate the correctly conjugated forms of words depending on a count that is associated
// with that word.
// This intended to allow phrases such a '0 potatoes', '1 potato', '2 potatoes' etc to
// conjugate correctly with the associated count.
// In some languages such as English this is fairly simple and can be expressed by assigning
// a count with a singular or plural form of a word and then finding the nearest (lower) value.
// generate the correctly conjugated forms of words depending on a count that is associated
// with that word.
// This intended to allow phrases such a '0 potatoes', '1 potato', '2 potatoes' etc to
// conjugate correctly with the associated count.
// In some languages such as English this is fairly simple and can be expressed by assigning
// a count with a singular or plural form of a word and then finding the nearest (lower) value.
// So '0' of something generally ends in 's', 1 of something is singular and has no extra
// ending and 2 or more is a plural and ends in 's' also. So to find the ending for '187' of
// something you would find the nearest lower count (2) and use that ending.
//
// So examples of this would be
// $zmVlangPotato = array( 0=>'Potatoes', 1=>'Potato', 2=>'Potatoes' );
// $zmVlangSheep = array( 0=>'Sheep' );
//
// where you can have as few or as many entries in the array as necessary
// If your language is similar in form to this then use the same format and choose the
// appropriate zmVlang function below.
// If however you have a language with a different format of plural endings then another
// approach is required . For instance in Russian the word endings change continuously
// depending on the last digit (or digits) of the numerator. In this case then zmVlang
// arrays could be written so that the array index just represents an arbitrary 'type'
// and the zmVlang function does the calculation about which version is appropriate.
//
// So an example in Russian might be (using English words, and made up endings as I
// don't know any Russian!!)
// 'Potato' => array( 1=>'Potati', 2=>'Potaton', 3=>'Potaten' ),
//
// and the zmVlang function decides that the first form is used for counts ending in
// 0, 5-9 or 11-19 and the second form when ending in 1 etc.
//

// Variable arrays expressing plurality, see the zmVlang description above
$VLANG = array(
    'Event'                 => array( 0=>'イベント', 1=>'イベント', 2=>'イベント' ),
    'Monitor'               => array( 0=>'モニター', 1=>'モニター', 2=>'モニター' ),
);
// You will need to choose or write a function that can correlate the plurality string arrays
// with variable counts. This is used to conjugate the Vlang arrays above with a number passed
// in to generate the correct noun form.
//
// In languages such as English this is fairly simple
// Note this still has to be used with printf etc to get the right formatting
function zmVlang($langVarArray, $count) {
  krsort($langVarArray);
  foreach ($langVarArray as $key=>$value) {
    if (abs($count) >= $key) {
      return $value;
    }
  }
  ZM\Error('Unable to correlate variable language string');
}

// This is an version that could be used in the Russian example above
// The rules are that the first word form is used if the count ends in
// 0, 5-9 or 11-19. The second form is used then the count ends in 1
// (not including 11 as above) and the third form is used when the
// count ends in 2-4, again excluding any values ending in 12-14.
//
// function zmVlang( $langVarArray, $count )
// {
//  $secondlastdigit = substr( $count, -2, 1 );
//  $lastdigit = substr( $count, -1, 1 );
//  // or
//  // $secondlastdigit = ($count/10)%10;
//  // $lastdigit = $count%10;
//
//  // Get rid of the special cases first, the teens
//  if ( $secondlastdigit == 1 && $lastdigit != 0 )
//  {
//      return( $langVarArray[1] );
//  }
//  switch ( $lastdigit )
//  {
//      case 0 :
//      case 5 :
//      case 6 :
//      case 7 :
//      case 8 :
//      case 9 :
//      {
//          return( $langVarArray[1] );
//          break;
//      }
//      case 1 :
//      {
//          return( $langVarArray[2] );
//          break;
//      }
//      case 2 :
//      case 5 :
//      case 6 :
//      case 7 :
//      case 8 :
//      case 9 :
//      {
//          return( $langVarArray[1] );
//          break;
//      }
//      case 1 :
//      {
//          return( $langVarArray[2] );
//          break;
//      }
//      case 2 :
//      case 3 :
//      case 4 :
//      {
//          return( $langVarArray[3] );
//          break;
//      }
//  }
//  die( 'Error, unable to correlate variable language string' );
// }

// This is an example of how the function is used in the code which you can uncomment and
// use to test your custom function.
//$monitors = array();
//$monitors[] = 1; // Choose any number
//echo sprintf( $CLANG['MonitorCount'], count($monitors), zmVlang( $VLANG['VlangMonitor'], count($monitors) ) );

// In this section you can override the default prompt and help texts for the options area
// These overrides are in the form show below where the array key represents the option name minus the initial ZM_
// So for example, to override the help text for ZM_LANG_DEFAULT do
$OLANG = array(
    'OPTIONS_FFMPEG' => array(
        'Help' => '
      このフィールドに入力されたパラメータはFFmpegに渡されます。複数のパラメータは , で区切ることができます。~~
      例 (引用符は入力しないでください)~~~~
      "allowed_media_types=video" カメラから要求するデータ型を設定します (audio, video, data)~~~~
      "reorder_queue_size=nnn" 再順序付けされたパケットの処理のためにバッファするパケットの数を設定します
    '
    ),
    'OPTIONS_ENCODER_PARAMETERS' => array(
        'Help' => '
    エンコーディングコーデックに渡すパラメータ。name=value は , または改行で区切ります。~~
    例えば、品質を変更するには crf オプションを使用します。1 は最高、51 は最悪、23 がデフォルトです。~~
~~
    crf=23~~
    ~~
    movflags の値を変更して、異なる動作をサポートすることもできます。frag_keyframe オプションにより、映像の再生に問題がある場合がありますが、このオプションは不完全なイベントの再生を可能にするはずです。詳細については
    [https://ffmpeg.org/ffmpeg-formats.html](https://ffmpeg.org/ffmpeg-formats.html)
    を参照してください。ZoneMinder のデフォルトは frag_keyframe,empty_moov~~
    ',
    ),
    'OPTIONS_DECODERHWACCELNAME' => array(
        'Help' => '
    これは ffmpeg -hwaccel コマンドラインオプションと同等です。intel グラフィックスのサポートがある場合は "vaapi" を使用します。NVIDIA cuda サポートが必要な場合は "cuda" を使用します。サポートを確認するには、コマンドラインで ffmpeg -hwaccels を実行してください。'
    ),
    'OPTIONS_DECODERHWACCELDEVICE' => array(
        'Help' => '
    これは ffmpeg -hwaccel_device コマンドラインオプションと同等です。複数の GPU がある場合のみ指定する必要があります。Intel VAAPI の場合の典型的な値は /dev/dri/renderD128 です。'
    ),
    'OPTIONS_RTSPTrans' => array(
        'Help' => '
        これは FFmpeg の RTSP トランスポートプロトコルを設定します。~~
        TCP - TCP (RTSP 制御チャネル内のインタリーブ) をトランスポートプロトコルとして使用します。~~
        UDP - UDP をトランスポートプロトコルとして使用します。高解像度カメラでは UDP を使用すると「ぼやけ」が発生することがあります。その場合は TCP を試してください。~~
        UDP Multicast - UDP マルチキャストをトランスポートプロトコルとして使用します~~
        HTTP - HTTP トンネリングをトランスポートプロトコルとして使用します。これはプロキシを通すのに便利です。~~
      '
    ),
    'OPTIONS_LIBVLC' => array(
        'Help' => '
      このフィールドに入力されたパラメータは libVLC に渡されます。複数のパラメータは , で区切ることができます。~~
      例 (引用符は入力しないでください)~~~~
      "--rtp-client-port=nnn" rtp データ用のローカルポートを設定します~~~~
      "--verbose=2" libVLC の詳細レベルを設定します
      '
    ),
    'OPTIONS_EXIF' => array(
        'Help' => 'このオプションを有効にすると、各 JPEG フレームに EXIF データを埋め込むことができます。'
    ),
    'OPTIONS_RTSPDESCRIBE' => array(
        'Help' => '
      初期 RTSP ハンドシェイク中に、カメラが更新されたメディア URL を送信することがあります。
      このオプションを有効にすると、ZoneMinder がこの URL を使用するようになります。このオプションを無効にすると、カメラからの値を無視し、モニター設定で入力された値を使用します~~~~
      一般的にはこれを有効にするべきです。ただし、カメラがファイアウォールを通してストリーミングしている場合など、カメラが自分の URL を間違えることがあります。
    '
    ),
    'OPTIONS_MAXFPS' => array(
        'Help' => '
      このフィールドには非ローカルデバイスで使用する際の制限があります。~~
      これらの制限に従わないと、ライブビデオの遅延、不規則なフレームスキップ、イベントの欠落が発生します~~
      ストリーミング IP カメラのために、このフィールドを使用してフレームレートを減少させるべきではありません。代わりにカメラでフレームレートを設定してください。過去にはカメラのフレームレートよりも高い値を設定することが推奨されていましたが、現在は必要ありませんし、良い考えでもありません。
      一部の古い IP カメラはスナップショットモードをサポートしています。この場合、ZoneMinder はカメラから新しい画像を積極的にポーリングします。この場合、このフィールドを使用するのは安全です。
      '
    ),
    'OPTIONS_ALARMMAXFPS' => array(
        'Help' => '
    このフィールドには非ローカルデバイスで使用する際の制限があります。~~
    これらの制限に従わないと、ライブビデオの遅延、不規則なフレームスキップ、イベントの欠落が発生します~
    この設定は、最大 FPS 値をこの状況で上書きすることを許可します。最大 FPS 設定と同様に、空白のままにすると制限がありません。
    '
    ),
    'OPTIONS_LINKED_MONITORS' => array(
        'Help' => '
      このフィールドでは、システム上の他のモニターを選択して、このモニターのトリガーとして使用することができます。例えば、プロパティのある側をカバーするカメラがある場合、そのカメラが動きを検出するとすべてのカメラが録画するように強制できます。リンクされたモニターを選択するには「選択」をクリックしてください。
      この機能で循環依存関係を作成しないように注意してください。無限に持続するアラームが発生することは、ほぼ間違いなく望んでいることではありません！ モニターのリンクを解除するには、ctrlクリックを使用できます。
      '
    ),
    'OPTIONS_CAPTURING' => array(
        'Help' => 'キャプチャを行うタイミング:~~~~
なし: プロセスを実行せず、キャプチャも行いません。古い機能 == なし と同等です。~~~~
オンデマンド: zmc プロセスが実行されますが、ビューア (ライブビュー、サムネイル、または RTSP サーバー接続) がカメラに接続するまで待機します。~~~~
常に: zmc プロセスが実行され、直ちに接続し、接続を維持します。~~~~
',
    ),
    'OPTIONS_RTSPSERVER' => array(
        'Help' => '
     ZM は独自の RTSP サーバーを提供し、RTSP を再ストリーミングするか、モニターストリームを RTSP に変換しようとします。これは、ZM ホストマシンのリソースを使用して、単一のカメラから複数のクライアントが引き出す代わりに使用したい場合に便利です。~~~~
     注意:~~
     オプション > ネットワーク > MIN_RTSP_PORT は構成可能です。
     ',
    ),
    'OPTIONS_RTSPSTREAMNAME' => array(
        'Help' => '
     RTSPServer が有効な場合、これがアクセス可能なエンドポイントになります。
     例えば、モニター ID が 6、MIN_RTSP_PORT が 20000 で、RTSPServerName が "my_camera" に設定されている場合、rtsp://ZM_HOST:20006/my_camera でストリームにアクセスします。
     ',
    ),
    'FUNCTION_ANALYSIS_ENABLED' => array(
        'Help' => '
      キャプチャされたビデオで動作検出を実行するタイミング。
      この設定はプロセスが起動するときのデフォルト状態を設定します。
      その後、外部トリガー zmtrigger zmu または Web UI を通じてオン/オフを切り替えることができます。
      有効でない場合、動作検出やリンクされたモニターのチェックは実行されず、イベントも作成されません。
      '
    ),
    'FUNCTION_DECODING' => array(
        'Help' => '
      動作検出を実行せず、H264Passthrough を使用して JPEG を保存しない場合、H264/H265 パケットをデコードしない選択肢があります。これにより CPU 使用率が大幅に削減されます。~~~~
常に: 各フレームがデコードされ、ライブビューとサムネイルが利用可能になります。~~~~
オンデマンド: 誰かが視聴しているときだけデコードします。~~~~
キーフレーム: キーフレームのみがデコードされるため、視聴フレームレートはカメラで設定されたキーフレーム間隔に応じて非常に低くなります。~~~~
なし: フレームはデコードされず、ライブビューとサムネイルは利用できません~~~~
'
    ),
    'FUNCTION_RTSP2WEB_ENABLED' => array(
        'Help' => '
      h264/h265 のライブビューのために RTSP2Web ストリーミングサーバーを使用しようとします。実験的ですが、かなり良いパフォーマンスを提供します。'
    ),
    'FUNCTION_RTSP2WEB_TYPE' => array(
        'Help' => '
      RTSP2Web は MSE (Media Source Extensions)、HLS (HTTP Live Streaming)、および WebRTC をサポートしています。
      それぞれに利点があり、WebRTC は最もパフォーマンスが良い可能性がありますが、コーデックに対して最も厳しい要求があります。'
    ),
    'FUNCTION_JANUS_ENABLED' => array(
        'Help' => '
      h264/h265 のライブビューのために Janus ストリーミングサーバーを使用しようとします。実験的ですが、かなり良いパフォーマンスを提供します。'
    ),
    'FUNCTION_JANUS_AUDIO_ENABLED' => array(
        'Help' => '
      Janus ストリームでオーディオを有効にしようとします。オーディオサポートがないカメラには効果がありませんが、カメラがブラウザでサポートされていないオーディオ形式を送信する場合、ストリームの再生を妨げる可能性があります。'
    ),
    'FUNCTION_JANUS_PROFILE_OVERRIDE' => array(
        'Help' => '
      プロファイル ID を手動で設定し、ブラウザに指定されたストリームを再生させることができます。普遍的にサポートされる値として "42e01f" を試すか、空白のままにしてソースによって指定されたプロファイル ID を使用します。'
    ),
    'FUNCTION_JANUS_USE_RTSP_RESTREAM' => array(
        'Help' => '
      他のオプションで Janus が機能しない場合は、ZoneMinder の RTSP リストリームを Janus のソースとして使用するためにこのオプションを有効にします。'
    ),
    'FUNCTION_JANUS_RTSP_SESSION_TIMEOUT' => array(
        'Help' => '
    RTSP セッションのタイムアウト期間を秒単位で上書きまたは設定します。Janus ログで 401 Unauthorized レスポンスが多く表示される場合に便利です。0 に設定すると、ソースから送信されたタイムアウトを使用します。'
    ),
    'ImageBufferCount' => array(
        'Help' => '
    /dev/shm に利用可能な生画像の数。現在は 3-5 の範囲に設定するべきです。ライブビューに使用されます。'
    ),
    'MaxImageBufferCount' => array(
        'Help' => '
    パケットキュー内に保持されるビデオパケットの最大数。
    パケットキューは通常自動的に管理し、Pre Event Count フレームまたはパススルーモードを使用している場合は最後のキーフレームからすべてを保持します。モニターが RAM を大量に消費しないように最大値を設定できますが、キーフレーム間隔がこの値より大きいとイベントがすべてのフレームを持っていない可能性があります。
    これについてログにエラーが表示されますので、キーフレーム間隔が低いか、十分な RAM があることを確認してください。
  '
    ),
// Help for soap_wsa issue with chinesse cameras
    'OPTIONS_SOAP_wsa' => array(
        'Help' => '
    もしエラーが発生したら無効にしてください
    ~~~~ Renew Error 12 ActionNotSupported <env:Text>The device do not support this feature</env:Text> ~~~~
    ONVIFを有効にする/使用しようとするときに動作させるのに役立つかもしれません... 
    ONVIFを完全に実装していない一部の中国製カメラで動作することが確認されています。
    '
    ),
//    'LANG_DEFAULT' => array(
//        'Prompt' => "This is a new prompt for this option",
//        'Help' => "This is some new help for this option which will be displayed in the window when the ? is clicked"
//    ),
);

?>
