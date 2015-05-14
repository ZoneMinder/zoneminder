var pluginOptionList = {};
<?php
foreach ( $pOptions as $key => $option )
{
?>
pluginOptionList['<?php echo $key; ?>'] = {};
<?php
   if (!isset($option['Require']))
      continue;
   foreach($option['Require'] as $req_couple)
   {
?>
pluginOptionList['<?php echo $key; ?>']['<?php echo $req_couple['Name']; ?>'] = '<?php echo $req_couple['Value']; ?>';
<?php
   }
}
?>

var onlyAlphaCharString = '<?php echo addslashes($SLANG['OnlyAlphaChars']) ?>';
var alreadyInList = '<?php echo addslashes($SLANG['AlreadyInList']) ?>';
var onlyIntegerString = '<?php echo addslashes($SLANG['OnlyIntegers']) ?>';
