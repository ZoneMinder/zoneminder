var dependencies = {};
<?php
foreach ( $options as $option )
{
   if (!isset($option['Require']))
      continue;
?>
dependencies['<?= $option['Name'] ?>'] = {};
<?php
   foreach($option['Require'] as $req_couple)
   {
?>
dependencies['<?= $option['Name'] ?>']['<?= $req_couple['Name'] ?>'] = '<?= $req_couple['Value'] ?>';
<?php
   }
}
?>
