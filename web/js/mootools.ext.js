/*
 * MooTools Extension script to support custom extensions to mootools
 */
var zmMooToolsVersion = '1.3.2';

/*
 * Firstly, lets check that mootools has been included and thus is present
 */
if ( typeof(MooTools) == "undefined" ) {
  alert( "MooTools not found! Please check that it was installed correctly in ZoneMinder web root." );
} else {
  /* Version check */
  if ( MooTools.version < zmMooToolsVersion ) {
    alert( "MooTools version "+MooTools.version+" found.\nVersion "+zmMooToolsVersion+" required, please check that it was installed correctly in ZoneMinder web root." );
  }
}
