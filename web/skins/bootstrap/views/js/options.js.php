var restartWarning = <?= empty($restartWarning)?'false':'true' ?>;
if ( restartWarning )
{
    alert( "<?= $SLANG['OptionRestartWarning'] ?>" );
}
