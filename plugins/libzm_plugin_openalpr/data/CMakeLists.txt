# CMakeLists.txt for openalpr plugin.

install (FILES openalpr.conf DESTINATION ${ZM_PLUGINSCONFDIR})
install (FILES config.php DESTINATION "${ZM_PLUGINSWEBDIR}/libzm_plugin_openalpr")
install (FILES en_gb.php fr_fr.php DESTINATION "${ZM_PLUGINSWEBDIR}/libzm_plugin_openalpr/lang")
