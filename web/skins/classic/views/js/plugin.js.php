var pluginOptionList = {};
<?php
foreach ( $options as $option )
{
?>
pluginOptionList['<?= $option['Name'] ?>'] = {};
<?php
   if (!isset($option['Require']))
      continue;
   foreach($option['Require'] as $req_couple)
   {
?>
pluginOptionList['<?= $option['Name'] ?>']['<?= $req_couple['Name'] ?>'] = '<?= $req_couple['Value'] ?>';
<?php
   }
}
?>
