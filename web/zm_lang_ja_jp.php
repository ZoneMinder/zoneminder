<?php
//
// ZoneMinder web Japanese language file, $Date$, $Revision$
// Copyright (C) 2003  Philip Coombes
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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
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
$zmSlang24BitColour          = '24ﾋﾞｯﾄｶﾗｰ';
$zmSlang8BitGrey             = '8ﾋﾞｯﾄ濃淡画像';
$zmSlangActual               = '生中継';
$zmSlangAddNewMonitor        = 'ﾓﾆﾀｰ追加';
$zmSlangAddNewUser           = 'ﾕｰｻﾞ追加';
$zmSlangAddNewZone           = 'ｿﾞｰﾝ追加';
$zmSlangAlarmBrFrames        = 'ｱﾗｰﾑ<br/>ﾌﾚｰﾑ';	
$zmSlangAlarmFrame           = 'ｱﾗｰﾑ ﾌﾚｰﾑ';
$zmSlangAlarmLimits          = 'ｱﾗｰﾑ限度';
$zmSlangAlarm                = 'ｱﾗｰﾑ';
$zmSlangAlarmPx              = 'ｱﾗｰﾑ Px';
$zmSlangAlert                = '警告';
$zmSlangAll                  = '全て';
$zmSlangApplyingStateChange  = '変更適用中';
$zmSlangApply                = '適用';
$zmSlangArchArchived         = '保存分のみ';
$zmSlangArchive              = 'ｱｰｶｲﾌﾞ';
$zmSlangArchUnarchived       = '保存分以外のみ';
$zmSlangAttrAlarmFrames      = 'ｱﾗｰﾑ ﾌﾚｰﾑ';
$zmSlangAttrArchiveStatus    = '保存状態';
$zmSlangAttrAvgScore         = '平均ｽｺｱｰ';
$zmSlangAttrDateTime         = '日時';
$zmSlangAttrDate             = '日付';
$zmSlangAttrDuration         = '継続時間';
$zmSlangAttrFrames           = 'ﾌﾚｰﾑ';
$zmSlangAttrMaxScore         = '最高ｽｺｱｰ';
$zmSlangAttrMonitorId        = 'ﾓﾆﾀｰ Id';
$zmSlangAttrMonitorName      = 'ﾓﾆﾀｰ 名前';
$zmSlangAttrTime             = '時間';
$zmSlangAttrTotalScore       = '合計ｽｺｱｰ';
$zmSlangAttrWeekday          = '曜日';
$zmSlangAutoArchiveEvents    = '一致ｲﾍﾞﾝﾄを自動保存';
$zmSlangAutoDeleteEvents     = '一致ｲﾍﾞﾝﾄを自動削除';
$zmSlangAutoEmailEvents      = '一致ｲﾍﾞﾝﾄ詳細を自動ﾒｰﾙ';
$zmSlangAutoMessageEvents    = '一致ｲﾍﾞﾝﾄ詳細を自動ﾒｯｾｰｼﾞ';
$zmSlangAutoUploadEvents     = '一致ｲﾍﾞﾝﾄを自動ｱｯﾌﾟﾛｰﾄﾞ';
$zmSlangAvgBrScore           = '平均<br/>ｽｺｱｰ';
$zmSlangBadMonitorChars      = 'ﾓﾆﾀｰの名前に使える文字は小文字のa-z、0-9、-と_だけです';
$zmSlangBandwidth            = '帯域幅';
$zmSlangBlobPx               = 'ﾌﾞﾛﾌﾞ Px';
$zmSlangBlobSizes            = 'ﾌﾞﾛﾌﾞ ｻｲｽﾞ';
$zmSlangBlobs                = 'ﾌﾞﾛﾌﾞ';
$zmSlangBrightness           = '輝度';
$zmSlangBuffers              = 'ﾊﾞｯﾌｧ';
$zmSlangCancelForcedAlarm    = '強制ｱﾗｰﾑｷｬﾝｾﾙ';
$zmSlangCancel               = 'ｷｬﾝｾﾙ';
$zmSlangCaptureHeight        = '取り込み高さ';
$zmSlangCapturePalette       = '取り込みﾊﾟﾚｯﾄ';
$zmSlangCaptureWidth         = '取り込み幅';
$zmSlangCheckAll             = '全て選択';
$zmSlangCheckMethod          = 'ｱﾗｰﾑ ﾁｪｯｸ方法';
$zmSlangChooseFilter         = 'ﾌｨﾙﾀｰの選択';
$zmSlangClose                = '閉じる';
$zmSlangColour               = '色';
$zmSlangConfig               = 'Config';
$zmSlangConfiguredFor        = '設定:';
$zmSlangConfirmPassword      = 'ﾊﾟｽﾜｰﾄﾞの確認';
$zmSlangConjAnd              = '及び';
$zmSlangConjOr               = '又は';
$zmSlangConsole              = 'ｺﾝｿｰﾙ';
$zmSlangContactAdmin         = '管理者にお問い合わせください。';
$zmSlangContrast             = 'ｺﾝﾄﾗｽﾄ';
$zmSlangCycleWatch           = 'ｻｲｸﾙ観察';
$zmSlangDay                  = '曜日';
$zmSlangDeleteAndNext        = '次を削除';
$zmSlangDeleteAndPrev        = '前を削除';
$zmSlangDelete               = '削除';
$zmSlangDeleteSavedFilter    = '保存ﾌｨﾙﾀｰの削除';
$zmSlangDescription          = '説明';
$zmSlangDeviceChannel        = 'ﾃﾞﾊﾞｲｽ ﾁｬﾝﾈﾙ';
$zmSlangDeviceFormat         = 'ﾃﾞﾊﾞｲｽ ﾌｫｰﾏｯﾄ (0=PAL,1=NTSC 等 )';
$zmSlangDeviceNumber         = 'ﾃﾞﾊﾞｲｽ番号 (/dev/video?)';
$zmSlangDimensions           = '寸法';
$zmSlangDisk                 = 'Disk';
$zmSlangDuration             = '継続時間';
$zmSlangEdit                 = '編集';
$zmSlangEmail                = 'ﾒｰﾙ';
$zmSlangEnabled              = '使用可能\';
$zmSlangEnterNewFilterName   = '新しいﾌｨﾙﾀｰ名の入力';
$zmSlangErrorBrackets        = 'エラー、開き括弧と閉じ括弧の数が合っているのかを確認してください';
$zmSlangError                = 'エラー';
$zmSlangErrorValidValue      = 'エラー、全ての項の数値が有効かどうかを確認してください';
$zmSlangEtc                  = '等';
$zmSlangEvent                = 'ｲﾍﾞﾝﾄ';
$zmSlangEventFilter          = 'ｲﾍﾞﾝﾄ ﾌｨﾙﾀｰ';
$zmSlangEvents               = 'ｲﾍﾞﾝﾄ';
$zmSlangExclude              = '排除';
$zmSlangFeed                 = '送り込む';
$zmSlangFilterPx             = 'ﾌｨﾙﾀｰ Px';
$zmSlangFirst                = '最初';
$zmSlangForceAlarm           = '強制ｱﾗｰﾑ';
$zmSlangFPS                  = 'fps';
$zmSlangFPSReportInterval    = 'FPS報告間隔';
$zmSlangFrameId              = 'ﾌﾚｰﾑ ID';
$zmSlangFrame                = 'ﾌﾚｰﾑ';
$zmSlangFrameRate            = 'ﾌﾚｰﾑﾚｰﾄ';
$zmSlangFrames               = 'ﾌﾚｰﾑ';
$zmSlangFrameSkip            = 'ﾌﾚｰﾑｽｷｯﾌﾟ';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = '機能\';
$zmSlangFunction             = '機能\';
$zmSlangGenerateVideo        = 'ﾋﾞﾃﾞｵの生成';
$zmSlangGeneratingVideo      = 'ﾋﾞﾃﾞｵ生成中';
$zmSlangGoToZoneMinder       = 'ZoneMinder.comに行く';
$zmSlangGrey                 = 'ｸﾞﾚｰ';
$zmSlangHigh                 = '高';
$zmSlangHighBW               = '高帯域';
$zmSlangHour                 = '時';
$zmSlangHue                  = '色相';
$zmSlangId                   = 'ID';
$zmSlangIdle                 = '待機状態';
$zmSlangIgnore               = '無視';
$zmSlangImage                = '画像';
$zmSlangImageBufferSize      = '画像 ﾊﾞｯﾌｧ ｻｲｽﾞ';
$zmSlangInclude              = '組み込む';
$zmSlangInverted             = '反転';
$zmSlangLanguage             = '言語';
$zmSlangLast                 = '最終';
$zmSlangLoad                 = 'Load';
$zmSlangLocal                = 'ﾛｰｶﾙ';
$zmSlangLoggedInAs           = 'ﾛｸﾞｲﾝ済み:';
$zmSlangLoggingIn            = 'ﾛｸﾞｲﾝ中';
$zmSlangLogin                = 'ﾛｸﾞｲﾝ';
$zmSlangLogout               = 'ﾛｸﾞｱｳﾄ';
$zmSlangLow                  = '低';
$zmSlangLowBW                = '低帯域';
$zmSlangMark                 = '選択';
$zmSlangMax                  = '最高';
$zmSlangMaxBrScore           = '最高<br/>ｽｺｱｰ';
$zmSlangMaximumFPS           = '最高 FPS';
$zmSlangMedium               = '中';
$zmSlangMediumBW             = '中帯域';
$zmSlangMinAlarmGeMinBlob    = '最低アラームピクセルが最低ブロブピクセル同等か以上でなければいけない';
$zmSlangMinAlarmGeMinFilter  = '最低アラームピクセルが最低フィルターピクセル同等か以上でなければいけない';
$zmSlangMinAlarmPixelsLtMax  = '最低ｱﾗｰﾑﾋﾟｸｾﾙは最高値より以下でなければいけない';
$zmSlangMinBlobAreaLtMax     = '最低ﾌﾞﾛｯﾌﾞ範囲は最高値より以下でなければいけない';
$zmSlangMinBlobsLtMax        = '最低ﾌﾞﾛｯﾌﾞ数は最高数より以下でなければいけない';
$zmSlangMinFilterPixelsLtMax = '最低ﾌｨﾙﾀｰﾋﾟｸｾﾙ数は最高数より以下でなければいけない';
$zmSlangMinPixelThresLtMax   = '最低ﾋﾟｸｾﾙ閾値は最高値より以下でなければいけない';
$zmSlangMisc                 = 'その他';
$zmSlangMonitorIds           = 'ﾓﾆﾀｰ ID';
$zmSlangMonitor              = 'ﾓﾆﾀｰ';
$zmSlangMonitors             = 'ﾓﾆﾀｰ';
$zmSlangMontage              = 'ﾓﾝﾀｰｼﾞｭ';
$zmSlangMonth                = '月';
$zmSlangMustBeGe             = '同等か以上でなければいけない';
$zmSlangMustBeLe             = '同等か以下でなければいけない';
$zmSlangMustConfirmPassword  = 'パスワードの確認をしてください';
$zmSlangMustSupplyPassword   = 'パスワードを入力してください';
$zmSlangMustSupplyUsername   = 'ユーザ名を入力してください';
$zmSlangName                 = '名前';
$zmSlangNetwork              = 'ﾈｯﾄﾜｰｸ';
$zmSlangNewPassword          = '新しいﾊﾟｽﾜｰﾄﾞ';
$zmSlangNewState             = '新規状態';	
$zmSlangNewUser              = '新しいﾕｰｻﾞ';
$zmSlangNew                  = '新規';
$zmSlangNext                 = '次';
$zmSlangNo                   = 'いいえ';
$zmSlangNoFramesRecorded     = 'このｲﾍﾞﾝﾄのﾌﾚｰﾑは登録されていません';
$zmSlangNoneAvailable        = 'ありません';
$zmSlangNone                 = 'ありません';
$zmSlangNormal               = '普通';
$zmSlangNoSavedFilters       = '保存されたﾌｨﾙﾀｰはありません';
$zmSlangNoStatisticsRecorded = 'このｲﾍﾞﾝﾄ/ﾌﾚｰﾑの統計は登録されていません';
$zmSlangOpEq                 = '同等';
$zmSlangOpGt                 = '以下';
$zmSlangOpGtEq               = '同等か以上';
$zmSlangOpIn                 = 'ｾｯﾄに入っている';
$zmSlangOpLt                 = '以下';
$zmSlangOpLtEq               = '同等か以下';
$zmSlangOpMatches            = '一致する';
$zmSlangOpNe                 = '同等でない';
$zmSlangOpNotIn              = 'ｾｯﾄに入っていない';
$zmSlangOpNotMatches         = '一致しない';
$zmSlangOptionHelp           = 'ｵﾌﾟｼｮﾝ ﾍﾙﾌﾟ';
$zmSlangOptionRestartWarning = 'この変更は起動中反映されない場合があります。\n変更してからZoneMinderを再起動してください。';
$zmSlangOptions              = 'ｵﾌﾟｼｮﾝ';
$zmSlangOrEnterNewName       = '又は新しい名前を入力してください';
$zmSlangOrientation          = 'ｵﾘｵﾝﾃｰｼｮﾝ';
$zmSlangOverwriteExisting    = '上書きします';
$zmSlangPaged                = 'ﾍﾟｰｼﾞ化';
$zmSlangParameter            = 'ﾊﾟﾗﾒｰﾀ';
$zmSlangPassword             = 'ﾊﾟｽﾜｰﾄﾞ';
$zmSlangPasswordsDifferent   = '新しいパスワードと再入力パスワードが一致しません';
$zmSlangPaths                = 'ﾊﾟｽ';
$zmSlangPhoneBW              = '携帯用';
$zmSlangPixels               = 'ﾋﾟｸｾﾙ';
$zmSlangPleaseWait           = 'お待ちください';
$zmSlangPostEventImageBuffer = 'ｲﾍﾞﾝﾄ ｲﾒｰｼﾞ ﾊﾞｯﾌｧ後';
$zmSlangPreEventImageBuffer  = 'ｲﾍﾞﾝﾄ ｲﾒｰｼﾞ ﾊﾞｯﾌｧ前<';
$zmSlangPrev                 = '前';
$zmSlangRate                 = 'ﾚｰﾄ';
$zmSlangReal                 = '生中継';
$zmSlangRecord               = '録画';
$zmSlangRefImageBlendPct     = 'ｲﾒｰｼﾞ ﾌﾞﾚﾝﾄﾞ 参照 %';
$zmSlangRefresh              = '最新の情報に更新';
$zmSlangRemoteHostName       = 'ﾘﾓｰﾄ ﾎｽﾄ 名';
$zmSlangRemoteHostPath       = 'ﾘﾓｰﾄ ﾎｽﾄ ﾊﾟｽ';
$zmSlangRemoteHostPort       = 'ﾘﾓｰﾄ ﾎｽﾄ ﾎﾟｰﾄ';
$zmSlangRemoteImageColours   = 'ﾘﾓｰﾄ ｲﾒｰｼﾞ ｶﾗｰ';
$zmSlangRemote               = 'ﾘﾓｰﾄ';
$zmSlangRename               = '新しい名前をつける';
$zmSlangReplay               = '再生';
$zmSlangResetEventCounts     = 'ｲﾍﾞﾝﾄ ｶｳﾝﾄ ﾘｾｯﾄ';
$zmSlangRestart              = '再起動';
$zmSlangRestarting           = '再起動中';
$zmSlangRestrictedCameraIds  = '制限されたｶﾒﾗ ID';
$zmSlangRotateLeft           = '左に回転';
$zmSlangRotateRight          = '右に回転';
$zmSlangRunMode              = '起動ﾓｰﾄﾞ';
$zmSlangRunning              = '起動中';
$zmSlangRunState             = '起動状態';
$zmSlangSaveAs               = '名前をつけて保存';
$zmSlangSaveFilter           = 'ﾌｨﾙﾀｰを保存';
$zmSlangSave                 = '保存';
$zmSlangScale                = 'ｽｹｰﾙ';
$zmSlangScore                = 'ｽｺｱｰ';
$zmSlangSecs                 = '秒';
$zmSlangSectionlength        = '長さ';
$zmSlangSetLearnPrefs        = 'Set Learn Prefs'; // 新しい設定の自動保存　This can be ignored for now
$zmSlangSetNewBandwidth      = '新しい帯域幅の設定';
$zmSlangSettings             = '設定';
$zmSlangShowFilterWindow     = 'ﾌｨﾙﾀｰ ｳｲﾝﾄﾞｰの表示';
$zmSlangSource               = 'ｿｰｽ';
$zmSlangSourceType           = 'ｿｰｽ ﾀｲﾌﾟ';
$zmSlangStart                = 'ｽﾀｰﾄ';
$zmSlangState                = '状態';
$zmSlangStats                = '統計';
$zmSlangStatus               = '状態';
$zmSlangStills               = 'ｽﾁｰﾙ画像';
$zmSlangStop                 = '停止';
$zmSlangStopped              = '停止状態';
$zmSlangStream               = 'ｽﾄﾘｰﾑ';
$zmSlangSystem               = 'ｼｽﾃﾑ';
$zmSlangTimeDelta            = 'ﾃﾞﾙﾀ ﾀｲﾑ';
$zmSlangTime                 = '時間';
$zmSlangTimestamp            = 'ﾀｲﾑｽﾀﾝﾌﾟ';
$zmSlangTimeStamp            = 'ﾀｲﾑ ｽﾀﾝﾌﾟ';
$zmSlangTimestampLabelFormat = 'ﾀｲﾑｽﾀﾝﾌﾟ ﾗﾍﾞﾙ ﾌｫｰﾏｯﾄ';
$zmSlangTimestampLabelX      = 'ﾀｲﾑｽﾀﾝﾌﾟ ﾗﾍﾞﾙ X';
$zmSlangTimestampLabelY      = 'ﾀｲﾑｽﾀﾝﾌﾟ ﾗﾍﾞﾙ Y';
$zmSlangTools                = 'ﾂｰﾙ';
$zmSlangTotalBrScore         = '合計<br/>ｽｺｱｰ';
$zmSlangTriggers             = 'ﾄﾘｶﾞｰ';
$zmSlangType                 = 'ﾀｲﾌﾟ';
$zmSlangUnarchive            = '解凍';
$zmSlangUnits                = 'ﾕﾆｯﾄ';
$zmSlangUnknown              = '不明';
$zmSlangUpdateAvailable      = 'ZoneMinderのｱｯﾌﾟﾃﾞｰﾄがあります';
$zmSlangUpdateNotNecessary   = 'ｱｯﾌﾟﾃﾞｰﾄの必要はありません';
$zmSlangUseFilterExprsPost   = '&nbsp;ﾌｨﾙﾀｰ個数'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = '指定してください:&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUseFilter            = 'ﾌｨﾙﾀｰを使用してください';
$zmSlangUsername             = 'ﾕｰｻﾞ名';
$zmSlangUser                 = 'ﾕｰｻﾞ';
$zmSlangUsers                = 'ﾕｰｻﾞ';
$zmSlangValue                = '数値';
$zmSlangVersion              = 'ﾊﾞｰｼﾞｮﾝ';
$zmSlangVersionIgnore        = 'このﾊﾞｰｼﾞｮﾝを無視';
$zmSlangVersionRemindDay     = '1日後に再度知らせる';
$zmSlangVersionRemindHour    = '1時間後に再度知らせる';
$zmSlangVersionRemindNever   = '新しいﾊﾞｰｼﾞｮﾝの知らせは必要ない';
$zmSlangVersionRemindWeek    = '1週間後に再度知らせる';
$zmSlangVideo                = 'ﾋﾞﾃﾞｵ';
$zmSlangVideoGenFailed       = 'ﾋﾞﾃﾞｵ生成の失敗！';
$zmSlangVideoGenParms        = 'ﾋﾞﾃﾞｵ生成 ﾊﾟﾗﾒｰﾀ';
$zmSlangVideoSize            = 'ﾋﾞﾃﾞｵ ｻｲｽﾞ';
$zmSlangView                 = '表示';
$zmSlangViewAll              = '全部表示';
$zmSlangViewPaged            = 'ﾍﾟｰｼﾞ化の表示';
$zmSlangWarmupFrames         = 'ｳｫｰﾑｱｯﾌﾟ ﾌﾚｰﾑ';
$zmSlangWatch                = '監視';
$zmSlangWeb                  = 'ｳｪﾌﾞ';
$zmSlangWeek                 = '週';
$zmSlangX10ActivationString  = 'X10起動文字列';
$zmSlangX10InputAlarmString  = 'X10入力ｱﾗｰﾑ文字列';
$zmSlangX10OutputAlarmString = 'X10出力ｱﾗｰﾑ文字列';
$zmSlangX10                  = 'X10';
$zmSlangYes                  = 'はい';
$zmSlangYouNoPerms           = 'この資源のｱｸｾｽ権がありません。';
$zmSlangZoneAlarmColour      = 'ｱﾗｰﾑ ｶﾗｰ (RGB)';
$zmSlangZoneAlarmThreshold   = 'ｱﾗｰﾑ 閾値(0>=?<=255)';
$zmSlangZoneFilterHeight     = 'ﾌｨﾙﾀｰ 高さ (ﾋﾟｸｾﾙ)';
$zmSlangZoneFilterWidth      = 'ﾌｨﾙﾀｰ 幅 (ﾋﾟｸｾﾙ)';
$zmSlangZoneMaxAlarmedArea   = '最高ｱﾗｰﾑ領域';
$zmSlangZoneMaxBlobArea      = '最高ﾌﾞﾛﾌﾞ領域';
$zmSlangZoneMaxBlobs         = '最高ﾌﾞﾛﾌﾞ数';
$zmSlangZoneMaxFilteredArea  = '最高ﾌｨﾙﾀｰ領域';
$zmSlangZoneMaxPixelThres    = '最高ﾋﾟｸｾﾙ閾値 (0>=?<=255)';
$zmSlangZoneMaxX             = 'X (右)最高';
$zmSlangZoneMaxY             = 'Y (左)最高';
$zmSlangZoneMinAlarmedArea   = '最低ｱﾗｰﾑ領域';
$zmSlangZoneMinBlobArea      = '最低ﾌﾞﾛﾌﾞ領域';
$zmSlangZoneMinBlobs         = '最低ﾌﾞﾛﾌﾞ数';
$zmSlangZoneMinFilteredArea  = '最低ﾌｨﾙﾀｰ領域';
$zmSlangZoneMinPixelThres    = '最低ﾋﾟｸｾﾙ閾値 (0>=?<=255)';
$zmSlangZoneMinX             = 'X (右)最低';
$zmSlangZoneMinY             = 'Y (左)最低';
$zmSlangZones                = 'ｿﾞｰﾝ';
$zmSlangZone                 = 'ｿﾞｰﾝ';

// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'ただ今\'%1$s\がﾛｸﾞｲﾝしています';
$zmClangEventCount           = '%1$s %2$s';
$zmClangLastEvents           = '最終 %1$s %2$s';
$zmClangLatestRelease        = '最新ﾊﾞｰｼﾞｮﾝは v%1$s、ご利用ﾊﾞｰｼﾞｮﾝはv%2$s.';
$zmClangMonitorCount         = '%1$s %2$s';
$zmClangMonitorFunction      = 'ﾓﾆﾀｰ%1$s 機能\';
$zmClangRunningRecentVer     = 'あなたはZoneMinderの最新ﾊﾞｰｼﾞｮﾝ v%s.を使っています';

// The next section allows you to describe a series of word ending and counts used to 
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
// $zmVlangPotato = array( 1=>'Potati', 2=>'Potaton', 3=>'Potaten' );
//
// and the zmVlang function decides that the first form is used for counts ending in
// 0, 5-9 or 11-19 and the second form when ending in 1 etc.
//

// Variable arrays expressing plurality, see the zmVlang description above
$zmVlangEvent                = array( 0=>'ｲﾍﾞﾝﾄ', 1=>'ｲﾍﾞﾝﾄ', 2=>'ｲﾍﾞﾝﾄ' );
$zmVlangMonitor              = array( 0=>'ﾓﾆﾀｰ', 1=>'ﾓﾆﾀｰ', 2=>'ﾓﾆﾀｰ' );

// You will need to choose or write a function that can correlate the plurality string arrays
// with variable counts. This is used to conjugate the Vlang arrays above with a number passed
// in to generate the correct noun form.
//
// In languages such as English this is fairly simple 
// Note this still has to be used with printf etc to get the right formating
function zmVlang( $lang_var_array, $count )
{
	krsort( $lang_var_array );
	foreach ( $lang_var_array as $key=>$value )
	{
		if ( abs($count) >= $key )
		{
			return( $value );
		}
	}
	die( 'Error, unable to correlate variable language string' );
}

// This is an version that could be used in the Russian example above
// The rules are that the first word form is used if the count ends in
// 0, 5-9 or 11-19. The second form is used then the count ends in 1
// (not including 11 as above) and the third form is used when the 
// count ends in 2-4, again excluding any values ending in 12-14.
// 
// function zmVlang( $lang_var_array, $count )
// {
// 	$secondlastdigit = substr( $count, -2, 1 );
// 	$lastdigit = substr( $count, -1, 1 );
// 	// or
// 	// $secondlastdigit = ($count/10)%10;
// 	// $lastdigit = $count%10;
// 
// 	// Get rid of the special cases first, the teens
// 	if ( $secondlastdigit == 1 && $lastdigit != 0 )
// 	{
// 		return( $lang_var_array[1] );
// 	}
// 	switch ( $lastdigit )
// 	{
// 		case 0 :
// 		case 5 :
// 		case 6 :
// 		case 7 :
// 		case 8 :
// 		case 9 :
// 		{
// 			return( $lang_var_array[1] );
// 			break;
// 		}
// 		case 1 :
// 		{
// 			return( $lang_var_array[2] );
// 			break;
// 		}
// 		case 2 :
// 		case 3 :
// 		case 4 :
// 		{
// 			return( $lang_var_array[3] );
// 			break;
// 		}
// 	}
// 	die( 'Error, unable to correlate variable language string' );
// }

// This is an example of how the function is used in the code which you can uncomment and 
// use to test your custom function.
//$monitors = array();
//$monitors[] = 1; // Choose any number
//echo sprintf( $zmClangMonitorCount, count($monitors), zmVlang( $zmVlangMonitor, count($monitors) ) );

// In this section you can override the default prompt and help texts for the options area
// These overrides are in the form of $zmOlangPrompt<option> and $zmOlangHelp<option>
// where <option> represents the option name minus the initial ZM_
// So for example, to override the help text for ZM_LANG_DEFAULT do
//$zmOlangPromptLANG_DEFAULT = "This is a new prompt for this option";
//$zmOlangHelpLANG_DEFAULT = "This is some new help for this option which will be displayed in the popup window when the ? is clicked";
//

?>
