			<div id="footer" ng-controller="FooterController">
				<div class="container-fluid">
						<p class="pull-right text-muted"><?= $SLANG['Load'] ?>: <span ng-bind="load"></span> -
						<?= $SLANG['Disk'] ?>: <span ng-bind="diskPercent"></span>% -
						<a href="?view=version" target="_blank">v {{ version }}</a>
      <?php
if ( ZM_OPT_USE_AUTH )
{
?><?= $SLANG['LoggedInAs'] ?> <?= makePopupLink( '?view=logout', 'zmLogout', 'logout', $user['Username'], (ZM_AUTH_TYPE == "builtin") ) ?>, <?= strtolower( $SLANG['ConfiguredFor'] ) ?><?php
}
else
{
?><?= $SLANG['ConfiguredFor'] ?><?php
}
?>&nbsp;<?= makePopupLink( '?view=bandwidth', 'zmBandwidth', 'bandwidth', $bwArray[$_COOKIE['zmBandwidth']], ($user && $user['MaxBandwidth'] != 'low' ) ) ?> <?= $SLANG['BandwidthHead'] ?>
					</p>
				</div>
			</div> <!-- End #footer -->
