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
$zmSlang24BitColour          = '24ビットカラー';
$zmSlang8BitGrey             = '8ビット濃淡画像';
$zmSlangActual               = '生中継';
$zmSlangAddNewMonitor        = 'モニター追加';
$zmSlangAddNewUser           = 'ユーザ追加';
$zmSlangAddNewZone           = 'ゾーン追加';
$zmSlangAlarm                = 'アラーム';
$zmSlangAlarmBrFrames        = 'アラーム<br/>フレーム';	
$zmSlangAlarmFrame           = 'アラーム フレーム';
$zmSlangAlarmLimits          = 'アラーム限度';
$zmSlangAlarmPx              = 'アラーム Px';
$zmSlangAlert                = '警告';
$zmSlangAll                  = '全て';
$zmSlangApply                = '適用';
$zmSlangApplyingStateChange  = '変更適用中';
$zmSlangArchArchived         = '保存分のみ';
$zmSlangArchive              = '保存';
$zmSlangArchUnarchived       = '保存分以外のみ';
$zmSlangAttrAlarmFrames      = 'アラーム フレーム';
$zmSlangAttrArchiveStatus    = '保存状態';
$zmSlangAttrAvgScore         = '平均スコアー';
$zmSlangAttrDate             = '日付';
$zmSlangAttrDateTime         = '日時';
$zmSlangAttrDuration         = '継続時間';
$zmSlangAttrFrames           = 'フレーム';
$zmSlangAttrMaxScore         = '最高スコアー';
$zmSlangAttrMontage          = 'モンタージュ';
$zmSlangAttrTime             = '時間';
$zmSlangAttrTotalScore       = '合計スコアー';
$zmSlangAttrWeekday          = '曜日';
$zmSlangAutoArchiveEvents    = '一致イベントを自動保存';
$zmSlangAutoDeleteEvents     = '一致イベントを自動削除';
$zmSlangAutoEmailEvents      = '一致イベント詳細を自動メール';
$zmSlangAutoMessageEvents    = '一致イベント詳細を自動メッセージ';
$zmSlangAutoUploadEvents     = '一致イベントを自動アップロード';
$zmSlangAvgBrScore           = '平均<br/>スコアー';
$zmSlangBandwidth            = '帯域幅';
$zmSlangBlobPx               = 'ブロブ Px';
$zmSlangBlobs                = 'ブロブ';
$zmSlangBlobSizes            = 'ブロブ サイズ';
$zmSlangBrightness           = '輝度';
$zmSlangBuffers              = 'バッファ';
$zmSlangCancel               = 'キャンセル';
$zmSlangCancelForcedAlarm    = '強制アラームのキャンセル';
$zmSlangCaptureHeight        = '取り込み高さ';
$zmSlangCapturePalette       = '取り込みパレット';
$zmSlangCaptureWidth         = '取り込み幅';
$zmSlangCheckAll             = '全て選択';
$zmSlangChooseFilter         = 'フィルターの選択';
$zmSlangClose                = '閉じる';
$zmSlangColour               = '色';
$zmSlangConfiguredFor        = '設定された:';
$zmSlangConfirmPassword      = 'パースワードの確認';
$zmSlangConjAnd              = '及び';
$zmSlangConjOr               = '又は';
$zmSlangConsole              = 'コンソール';
$zmSlangContactAdmin         = '管理者にお問い合わせください。';
$zmSlangContrast             = 'コントラスト';
$zmSlangCycleWatch           = 'サイクル観察';
$zmSlangDay                  = '曜日';
$zmSlangDeleteAndNext        = '次を削除';
$zmSlangDeleteAndPrev        = '前を削除';
$zmSlangDelete               = '削除';
$zmSlangDeleteSavedFilter    = '保存フィルターの削除';
$zmSlangDescription          = '説明';
$zmSlangDeviceChannel        = 'デバイス チャンネル';
$zmSlangDeviceFormat         = 'デバイス フォーマット (0=PAL,1=NTSC 等 )';
$zmSlangDeviceNumber         = 'デバイス番号 (/dev/video?)';
$zmSlangDimensions           = '寸法';
$zmSlangDuration             = '継続時間';
$zmSlangEdit                 = '編集';
$zmSlangEmail                = 'メール';
$zmSlangEnabled              = '使用可能';
$zmSlangEnterNewFilterName   = '新しいフィルター名の入力';
$zmSlangErrorBrackets        = 'エラー、開き括弧と閉じ括弧の数が合っているのかを確認してください';
$zmSlangError                = 'エラー';
$zmSlangErrorValidValue      = 'エラー、全ての項の数値が有効かどうかを確認してください';
$zmSlangEtc                  = '等';
$zmSlangEvent                = 'イベント';
$zmSlangEventFilter          = 'イベント フィルター';
$zmSlangEvents               = 'イベント';
$zmSlangExclude              = '排除';
$zmSlangFeed                 = '送り込む';
$zmSlangFilterPx             = 'フィルター Px';
$zmSlangFirst                = '最初';
$zmSlangForceAlarm           = '強制アラーム';
$zmSlangFPS                  = 'fps';
$zmSlangFPSReportInterval    = 'FPS報告間隔';
$zmSlangFrame                = 'フレーム';
$zmSlangFrameId              = 'フレーム ID';
$zmSlangFrameRate            = 'フレームレート';
$zmSlangFrames               = 'フレーム';
$zmSlangFrameSkip            = 'フレームスキップ';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = '機能';
$zmSlangFunction             = '機能';
$zmSlangGenerateVideo        = 'ビデオの生成';
$zmSlangGeneratingVideo      = 'ビデオ生成中';
$zmSlangGrey                 = 'グレー';
$zmSlangHighBW               = '高帯域';
$zmSlangHigh                 = '高';
$zmSlangHour                 = '時';
$zmSlangHue                  = '色相';
$zmSlangId                   = 'ID';
$zmSlangIdle                 = '待機状態';
$zmSlangIgnore               = '無視';
$zmSlangImageBufferSize      = '画像 バッファ サイズ';
$zmSlangImage                = '画像';
$zmSlangInclude              = '組み込む';
$zmSlangInverted             = '反転';
$zmSlangLanguage             = '言語';
$zmSlangLast                 = '最終';
$zmSlangLocal                = 'ローカル';
$zmSlangLoggedInAs           = 'ログイン済み:';
$zmSlangLoggingIn            = 'ログイン中';
$zmSlangLogin                = 'ログイン';
$zmSlangLogout               = 'ログアウト';
$zmSlangLowBW                = '低帯域';
$zmSlangLow                  = '低';
$zmSlangMark                 = '選択';
$zmSlangMaxBrScore           = '最高<br/>スコアー';
$zmSlangMaximumFPS           = '最高 FPS';
$zmSlangMax                  = '最高';
$zmSlangMediumBW             = '中帯域';
$zmSlangMedium               = '中';
$zmSlangMinAlarmGeMinBlob    = '最低アラームピクセルが最低ブロブピクセル同等か以上でなければいけない';
$zmSlangMinAlarmGeMinFilter  = '最低アラームピクセルが最低フィルターピクセル同等か以上でなければいけない';
$zmSlangMisc                 = '等';
$zmSlangMonitorIds           = 'モニター ID';
$zmSlangMonitor              = 'モニター';
$zmSlangMonitors             = 'モニター';
$zmSlangMontage              = 'モンタージュ';
$zmSlangMonth                = '月';
$zmSlangMustBeGe             = '同等か以上でなければいけない';
$zmSlangMustBeLe             = '同等か以下でなければいけない';
$zmSlangMustConfirmPassword  = 'パスワードの確認をしてください';
$zmSlangMustSupplyPassword   = 'パスワードを入力してください';
$zmSlangMustSupplyUsername   = 'ユーザ名を入力してください';
$zmSlangName                 = '名前';
$zmSlangNetwork              = 'ネットワーク';
$zmSlangNew                  = '新規';
$zmSlangNewPassword          = '新しいパスワード';
$zmSlangNewState             = '新規状態';	
$zmSlangNewUser              = '新しいユーザ';
$zmSlangNext                 = '次';
$zmSlangNoFramesRecorded     = 'このイベントのフレームは登録されていません';
$zmSlangNoneAvailable        = 'ありません';
$zmSlangNone                 = 'ありません';
$zmSlangNo                   = 'いいえ';
$zmSlangNormal               = '普通';
$zmSlangNoSavedFilters       = '保存されたフィルターはありません';
$zmSlangNoStatisticsRecorded = 'このイベント/フレームの統計は登録されていません';
$zmSlangOpEq                 = '同等';
$zmSlangOpGtEq               = '同等か以上';
$zmSlangOpGt                 = '以下';
$zmSlangOpLtEq               = '同等か以下';
$zmSlangOpLt                 = '以下';
$zmSlangOpNe                 = '同等でない';
$zmSlangOptionHelp           = 'オプション ヘルプ';
$zmSlangOptionRestartWarning = 'この変更は起動中反映されない場合があります。\n変更してからZoneMinderを再起動してください。';
$zmSlangOptions              = 'オプション';
$zmSlangOrEnterNewName       = '又は新しい名前を入力してください';
$zmSlangOrientation          = 'オリオンテーション';
$zmSlangOverwriteExisting    = '上書きします';
$zmSlangPaged                = 'ページ化';
$zmSlangParameter            = 'パラメータ';
$zmSlangPassword             = 'パスワード';
$zmSlangPasswordsDifferent   = '新しいパスワードと再入力パスワードが一致しません';
$zmSlangPaths                = 'パス';
$zmSlangPhoneBW              = '携帯用';
$zmSlangPixels               = 'ピクセル';
$zmSlangPleaseWait           = 'お待ちください';
$zmSlangPostEventImageBuffer = 'イベント イメージ バッファ後';
$zmSlangPreEventImageBuffer  = 'イベント イメージ バッファ前<';
$zmSlangPrev                 = '前';
$zmSlangRate                 = 'レート';
$zmSlangReal                 = '生中継';
$zmSlangRecord               = '録画';
$zmSlangRefImageBlendPct     = 'イメージ ブレンド 参照 %ge';
$zmSlangRefresh              = '最新の情報に更新';
$zmSlangRemoteHostName       = 'リモート ホスト 名';
$zmSlangRemoteHostPath       = 'リモート ホスト パス';
$zmSlangRemoteHostPort       = 'リモート ホスト ポート';
$zmSlangRemoteImageColours   = 'リモート イメージ カラー';
$zmSlangRemote               = 'リモート';
$zmSlangRename               = '新しい名前をつける';
$zmSlangReplay               = '再生';
$zmSlangResetEventCounts     = 'イベント カウント リセット';
$zmSlangRestarting           = '再起動中';
$zmSlangRestart              = '再起動';
$zmSlangRestrictedCameraIds  = '制限されたカメラ ID';
$zmSlangRotateLeft           = '左に回転';
$zmSlangRotateRight          = '右に回転';
$zmSlangRunMode              = '起動モード';
$zmSlangRunning              = '起動中';
$zmSlangRunState             = '起動状態';
$zmSlangSaveAs               = '名前をつけて保存';
$zmSlangSaveFilter           = 'フィルターを保存';
$zmSlangSave                 = '保存';
$zmSlangScale                = 'スケール';
$zmSlangScore                = 'スコアー';
$zmSlangSecs                 = '秒';
$zmSlangSectionlength        = '長さ';
$zmSlangServerLoad           = 'サーバー 負担率';
$zmSlangSetLearnPrefs        = 'Set Learn Prefs'; // 新しい設定の自動保存　This can be ignored for now
$zmSlangSetNewBandwidth      = '新しい帯域幅の設定';
$zmSlangSettings             = '設定';
$zmSlangShowFilterWindow     = 'フィルター ウインドーの表示';
$zmSlangSource               = 'ソース';
$zmSlangSourceType           = 'ソース タイプ';
$zmSlangStart                = 'スタート';
$zmSlangState                = '状態';
$zmSlangStats                = '統計';
$zmSlangStatus               = '状態';
$zmSlangStills               = 'スチール画像';
$zmSlangStopped              = '停止状態';
$zmSlangStop                 = '停止';
$zmSlangStream               = 'ストリーム';
$zmSlangSystem               = 'システム';
$zmSlangTimeDelta            = 'デルタ タイム';
$zmSlangTimestampLabelFormat = 'タイムスタンプ ラベル フォーマット';
$zmSlangTimestampLabelX      = 'タイムスタンプ ラベル X';
$zmSlangTimestampLabelY      = 'タイムスタンプ ラベル Y';
$zmSlangTimestamp            = 'タイムスタンプ';
$zmSlangTimeStamp            = 'タイム スタンプ';
$zmSlangTime                 = '時間';
$zmSlangTools                = 'ツール';
$zmSlangTotalBrScore         = '合計<br/>スコアー';
$zmSlangTriggers             = 'トリガー';
$zmSlangType                 = 'タイプ';
$zmSlangUnarchive            = '解凍';
$zmSlangUnits                = 'ユニット';
$zmSlangUnknown              = '不明';
$zmSlangUseFilterExprsPost   = '&nbsp;フィルター定義'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = '指定してください:&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUseFilter            = 'フィルターを使用してください';
$zmSlangUsername             = 'ユーザ名';
$zmSlangUsers                = 'ユーザ';
$zmSlangUser                 = 'ユーザ';
$zmSlangValue                = '数値';
$zmSlangVideoGenFailed       = 'ビデオ生成の失敗！';
$zmSlangVideoGenParms        = 'ビデオ生成 パラメータ';
$zmSlangVideoSize            = 'ビデオ サイズ';
$zmSlangVideo                = 'ビデオ';
$zmSlangViewAll              = '全部表示';
$zmSlangViewPaged            = 'ページ化の表示';
$zmSlangView                 = '表示';
$zmSlangWarmupFrames         = 'ウォームアップ フレーム';
$zmSlangWatch                = '見る';
$zmSlangWeb                  = 'ウェブ';
$zmSlangWeek                 = '週';
$zmSlangX10ActivationString  = 'X10起動文字列';
$zmSlangX10InputAlarmString  = 'X10入力アラーム文字列';
$zmSlangX10OutputAlarmString = 'X10出力アラーム文字列';
$zmSlangX10                  = 'X10';
$zmSlangYes                  = 'はい';
$zmSlangYouNoPerms           = 'この資源のアクセス権がありません。';
$zmSlangZoneAlarmColour      = 'アラーム カラー (RGB)';
$zmSlangZoneAlarmThreshold   = 'アラーム 閾値(0>=?<=255)';
$zmSlangZoneFilterHeight     = 'フィルター 高さ （ピクセル）';
$zmSlangZoneFilterWidth      = 'フィルター 幅 （ピクセル）';
$zmSlangZoneMaxAlarmedArea   = '最高アラーム領域';
$zmSlangZoneMaxBlobArea      = '最高ブロブ領域';
$zmSlangZoneMaxBlobs         = '最高ブロブ数';
$zmSlangZoneMaxFilteredArea  = '最高フィルター領域';
$zmSlangZoneMaxX             = 'X （右）最高';
$zmSlangZoneMaxY             = 'Y （左）最高';
$zmSlangZoneMinAlarmedArea   = '最低アラーム領域';
$zmSlangZoneMinBlobArea      = '最低ブロブ領域';
$zmSlangZoneMinBlobs         = '最低ブロブ数';
$zmSlangZoneMinFilteredArea  = '最低フィルター領域';
$zmSlangZoneMinX             = 'X （右）最低';
$zmSlangZoneMinY             = 'Y （左）最低';
$zmSlangZones                = 'ゾーン';
$zmSlangZone                 = 'ゾーン';

// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'ただ今\'%1$s\がログインしています';
$zmClangEventCount           = '%1$s %2$s';	
$zmClangLastEvents           = '最終 %1$s %2$s';
$zmClangMonitorCount         = '%1$s %2$s';
$zmClangMonitorFunction      = 'モニター%1$s 機能';

// Variable arrays expressing plurality
$zmVlangEvent                = array( 0=>'イベント', 1=>'イベント', 2=>'イベント' );
$zmVlangMonitor              = array( 0=>'モニター', 1=>'モニター', 2=>'モニター' );

?>
