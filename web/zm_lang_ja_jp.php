<?php
//
// ZoneMinder web UK English language file, $Date$, $Revision$
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

// Notes for Translators
// 1. When composing the language tokens in your language you should try and keep to roughly the
//   same length text if possible. Abbreviate where necessary as spacing is quite close in a number of places.
// 2. There are three types of string replacement
//   a) Simple replacements are words or short phrases that are static and used directly. This type of
//     replacement can be used 'as is'.
//   b) Complex replacements involve some dynamic element being included and so may require substitution
//     or changing into a different order. The token listed in this file will be passed through sprintf as
//     a formatting string. If the dynamic element is a number you will usually need to use a variable
//     replacement also as described below.
//   c) Variable replacements are used in conjunction with complex replacements and involve the generation
//     of a singular or plural noun depending on the number passed into the zmVlang function. This is
//     intended to allow phrases such a '0 potatoes', '1 potato', '2 potatoes' etc to conjunct correctly
//     with the associated numerator. Variable replacements are expressed are arrays with a series of
//     counts and their associated words. When doing a replacement the passed value is compared with 
//     those counts in descending order and the nearest match below is used if no exact match is found.
//     Therefore is you have a variable replacement with 0,1 and 2 counts, which would be the normal form
//     in English, if you have 5 'things' then the nearest match below is '2' and so that plural would be used.
// 3. The tokens listed below are not used to build up phrases or sentences from single words. Therefore
//   you can safely assume that a single word token will only be used in that context.
// 4. In new language files, or if you are changing only a few words or phrases it makes sense from a 
//   maintenance point of view to include the original language file and override the old definitions rather
//   than copy all the language tokens across. To do this change the line below to whatever your base language
//   is and uncomment it.
// require_once( 'zm_lang_en_gb.php' );

// Simple String Replacements
$zmSlang24BitColour          = '24ﾋﾞｯﾄｶﾗｰ';
$zmSlang8BitGrey             = '8ﾋﾞｯﾄ濃淡画像';
$zmSlangActual               = '生中継';
$zmSlangAddNewMonitor        = 'ﾓﾆﾀｰ追加';
$zmSlangAddNewUser           = 'ﾕｰｻﾞ追加';
$zmSlangAddNewZone           = 'ｿﾞｰﾝ追加';
$zmSlangAlarm                = 'ｱﾗｰﾑ';
$zmSlangAlarmBrFrames        = 'ｱﾗｰﾑ<br/>ﾌﾚｰﾑ';	
$zmSlangAlarmFrame           = 'ｱﾗｰﾑ ﾌﾚｰﾑ';
$zmSlangAlarmLimits          = 'ｱﾗｰﾑ限度';
$zmSlangAlarmPx              = 'ｱﾗｰﾑ Px';
$zmSlangAlert                = '警告';
$zmSlangAll                  = '全て';
$zmSlangApply                = '適用';
$zmSlangApplyingStateChange  = '変更適用中';
$zmSlangArchArchived         = '保存分のみ';
$zmSlangArchive              = 'ｱｰｶｲﾌﾞ';
$zmSlangArchUnarchived       = '保存分以外のみ';
$zmSlangAttrAlarmFrames      = 'ｱﾗｰﾑ ﾌﾚｰﾑ';
$zmSlangAttrArchiveStatus    = '保存状態';
$zmSlangAttrAvgScore         = '平均ｽｺｱｰ';
$zmSlangAttrDate             = '日付';
$zmSlangAttrDateTime         = '日時';
$zmSlangAttrDuration         = '継続時間';
$zmSlangAttrFrames           = 'ﾌﾚｰﾑ';
$zmSlangAttrMaxScore         = '最高ｽｺｱｰ';
$zmSlangAttrMontage          = 'ﾓﾝﾀｰｼﾞｭ';
$zmSlangAttrTime             = '時間';
$zmSlangAttrTotalScore       = '合計ｽｺｱｰ';
$zmSlangAttrWeekday          = '曜日';
$zmSlangAutoArchiveEvents    = '一致ｲﾍﾞﾝﾄを自動保存';
$zmSlangAutoDeleteEvents     = '一致ｲﾍﾞﾝﾄを自動削除';
$zmSlangAutoEmailEvents      = '一致ｲﾍﾞﾝﾄ詳細を自動ﾒｰﾙ';
$zmSlangAutoMessageEvents    = '一致ｲﾍﾞﾝﾄ詳細を自動ﾒｯｾｰｼﾞ';
$zmSlangAutoUploadEvents     = '一致ｲﾍﾞﾝﾄを自動ｱｯﾌﾟﾛｰﾄﾞ';
$zmSlangAvgBrScore           = '平均<br/>ｽｺｱｰ';
$zmSlangBandwidth            = '帯域幅';
$zmSlangBlobPx               = 'ﾌﾞﾛﾌﾞ Px';
$zmSlangBlobs                = 'ﾌﾞﾛﾌﾞ';
$zmSlangBlobSizes            = 'ﾌﾞﾛﾌﾞ ｻｲｽﾞ';
$zmSlangBrightness           = '輝度';
$zmSlangBuffers              = 'ﾊﾞｯﾌｧ';
$zmSlangCancel               = 'ｷｬﾝｾﾙ';
$zmSlangCancelForcedAlarm    = '強制ｱﾗｰﾑｷｬﾝｾﾙ';
$zmSlangCaptureHeight        = '取り込み高さ';
$zmSlangCapturePalette       = '取り込みﾊﾟﾚｯﾄ';
$zmSlangCaptureWidth         = '取り込み幅';
$zmSlangCheckAll             = '全て選択';
$zmSlangChooseFilter         = 'ﾌｨﾙﾀｰの選択';
$zmSlangClose                = '閉じる';
$zmSlangColour               = '色';
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
$zmSlangDuration             = '継続時間';
$zmSlangEdit                 = '編集';
$zmSlangEmail                = 'ﾒｰﾙ';
$zmSlangEnabled              = '使用可能';
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
$zmSlangFrame                = 'ﾌﾚｰﾑ';
$zmSlangFrameId              = 'ﾌﾚｰﾑ ID';
$zmSlangFrameRate            = 'ﾌﾚｰﾑﾚｰﾄ';
$zmSlangFrames               = 'ﾌﾚｰﾑ';
$zmSlangFrameSkip            = 'ﾌﾚｰﾑｽｷｯﾌﾟ';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = '機能';
$zmSlangFunction             = '機能';
$zmSlangGenerateVideo        = 'ﾋﾞﾃﾞｵの生成';
$zmSlangGeneratingVideo      = 'ﾋﾞﾃﾞｵ生成中';
$zmSlangGrey                 = 'ｸﾞﾚｰ';
$zmSlangHighBW               = '高帯域';
$zmSlangHigh                 = '高';
$zmSlangHour                 = '時';
$zmSlangHue                  = '色相';
$zmSlangId                   = 'ID';
$zmSlangIdle                 = '待機状態';
$zmSlangIgnore               = '無視';
$zmSlangImageBufferSize      = '画像 ﾊﾞｯﾌｧ ｻｲｽﾞ';
$zmSlangImage                = '画像';
$zmSlangInclude              = '組み込む';
$zmSlangInverted             = '反転';
$zmSlangLanguage             = '言語';
$zmSlangLast                 = '最終';
$zmSlangLocal                = 'ﾛｰｶﾙ';
$zmSlangLoggedInAs           = 'ﾛｸﾞｲﾝ済み:';
$zmSlangLoggingIn            = 'ﾛｸﾞｲﾝ中';
$zmSlangLogin                = 'ﾛｸﾞｲﾝ';
$zmSlangLogout               = 'ﾛｸﾞｱｳﾄ';
$zmSlangLowBW                = '低帯域';
$zmSlangLow                  = '低';
$zmSlangMark                 = '選択';
$zmSlangMaxBrScore           = '最高<br/>ｽｺｱｰ';
$zmSlangMaximumFPS           = '最高 FPS';
$zmSlangMax                  = '最高';
$zmSlangMediumBW             = '中帯域';
$zmSlangMedium               = '中';
$zmSlangMinAlarmGeMinBlob    = '最低アラームピクセルが最低ブロブピクセル同等か以上でなければいけない';
$zmSlangMinAlarmGeMinFilter  = '最低アラームピクセルが最低フィルターピクセル同等か以上でなければいけない';
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
$zmSlangNew                  = '新規';
$zmSlangNewPassword          = '新しいﾊﾟｽﾜｰﾄﾞ';
$zmSlangNewState             = '新規状態';	
$zmSlangNewUser              = '新しいﾕｰｻﾞ';
$zmSlangNext                 = '次';
$zmSlangNoFramesRecorded     = 'このｲﾍﾞﾝﾄのﾌﾚｰﾑは登録されていません';
$zmSlangNoneAvailable        = 'ありません';
$zmSlangNone                 = 'ありません';
$zmSlangNo                   = 'いいえ';
$zmSlangNormal               = '普通';
$zmSlangNoSavedFilters       = '保存されたﾌｨﾙﾀｰはありません';
$zmSlangNoStatisticsRecorded = 'このｲﾍﾞﾝﾄ/ﾌﾚｰﾑの統計は登録されていません';
$zmSlangOpEq                 = '同等';
$zmSlangOpGtEq               = '同等か以上';
$zmSlangOpGt                 = '以下';
$zmSlangOpLtEq               = '同等か以下';
$zmSlangOpLt                 = '以下';
$zmSlangOpNe                 = '同等でない';
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
$zmSlangRestarting           = '再起動中';
$zmSlangRestart              = '再起動';
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
$zmSlangServerLoad           = 'ｻｰﾊﾞｰ 負担率';
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
$zmSlangStopped              = '停止状態';
$zmSlangStop                 = '停止';
$zmSlangStream               = 'ｽﾄﾘｰﾑ';
$zmSlangSystem               = 'ｼｽﾃﾑ';
$zmSlangTimeDelta            = 'ﾃﾞﾙﾀ ﾀｲﾑ';
$zmSlangTimestampLabelFormat = 'ﾀｲﾑｽﾀﾝﾌﾟ ﾗﾍﾞﾙ ﾌｫｰﾏｯﾄ';
$zmSlangTimestampLabelX      = 'ﾀｲﾑｽﾀﾝﾌﾟ ﾗﾍﾞﾙ X';
$zmSlangTimestampLabelY      = 'ﾀｲﾑｽﾀﾝﾌﾟ ﾗﾍﾞﾙ Y';
$zmSlangTimestamp            = 'ﾀｲﾑｽﾀﾝﾌﾟ';
$zmSlangTimeStamp            = 'ﾀｲﾑ ｽﾀﾝﾌﾟ';
$zmSlangTime                 = '時間';
$zmSlangTools                = 'ﾂｰﾙ';
$zmSlangTotalBrScore         = '合計<br/>ｽｺｱｰ';
$zmSlangTriggers             = 'ﾄﾘｶﾞｰ';
$zmSlangType                 = 'ﾀｲﾌﾟ';
$zmSlangUnarchive            = '解凍';
$zmSlangUnits                = 'ﾕﾆｯﾄ';
$zmSlangUnknown              = '不明';
$zmSlangUseFilterExprsPost   = '&nbsp;ﾌｨﾙﾀｰ個数'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = '指定してください:&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUseFilter            = 'ﾌｨﾙﾀｰを使用してください';
$zmSlangUsername             = 'ﾕｰｻﾞ名';
$zmSlangUsers                = 'ﾕｰｻﾞ';
$zmSlangUser                 = 'ﾕｰｻﾞ';
$zmSlangValue                = '数値';
$zmSlangVideoGenFailed       = 'ﾋﾞﾃﾞｵ生成の失敗！';
$zmSlangVideoGenParms        = 'ﾋﾞﾃﾞｵ生成 ﾊﾟﾗﾒｰﾀ';
$zmSlangVideoSize            = 'ﾋﾞﾃﾞｵ ｻｲｽﾞ';
$zmSlangVideo                = 'ﾋﾞﾃﾞｵ';
$zmSlangViewAll              = '全部表示';
$zmSlangViewPaged            = 'ﾍﾟｰｼﾞ化の表示';
$zmSlangView                 = '表示';
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
$zmSlangZoneMaxX             = 'X (右)最高';
$zmSlangZoneMaxY             = 'Y (左)最高';
$zmSlangZoneMinAlarmedArea   = '最低ｱﾗｰﾑ領域';
$zmSlangZoneMinBlobArea      = '最低ﾌﾞﾛﾌﾞ領域';
$zmSlangZoneMinBlobs         = '最低ﾌﾞﾛﾌﾞ数';
$zmSlangZoneMinFilteredArea  = '最低ﾌｨﾙﾀｰ領域';
$zmSlangZoneMinX             = 'X (右)最低';
$zmSlangZoneMinY             = 'Y (左)最低';
$zmSlangZones                = 'ｿﾞｰﾝ';
$zmSlangZone                 = 'ｿﾞｰﾝ';

// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'ただ今\\'%1$s\がﾛｸﾞｲﾝしています';
$zmClangEventCount           = '%1$s %2$s';
$zmClangLastEvents           = '最終 %1$s %2$s';
$zmClangMonitorCount         = '%1$s %2$s';
$zmClangMonitorFunction      = 'ﾓﾆﾀｰ%1$s 機能';

// Variable arrays expressing plurality
$zmVlangEvent                = array( 0=>'ｲﾍﾞﾝﾄ', 1=>'ｲﾍﾞﾝﾄ', 2=>'ｲﾍﾞﾝﾄ' );
$zmVlangMonitor              = array( 0=>'ﾓﾆﾀｰ', 1=>'ﾓﾆﾀｰ', 2=>'ﾓﾆﾀｰ' );

?>
