AUTOMAKE_OPTIONS = gnu

# This should be set to your CGI directory
webdir = @WEB_PREFIX@
# And these to the user and group of your webserver
webuser = @WEB_USER@
webgroup = @WEB_GROUP@

web_DATA = \
	favicon.ico \
	zm_actions.php \
	zm_config.php \
	zm_db.php \
	zm_funcs.php \
	zm_html.php \
	zm_html_view_bandwidth.php \
	zm_html_view_console.php \
	zm_html_view_cycle.php \
	zm_html_view_error.php \
	zm_html_view_event.php \
	zm_html_view_events.php \
	zm_html_view_filter.php \
	zm_html_view_filtersave.php \
	zm_html_view_frame.php \
	zm_html_view_frames.php \
	zm_html_view_function.php \
	zm_html_view_imagefetch.php \
	zm_html_view_login.php \
	zm_html_view_logout.php \
	zm_html_view_monitor.php \
	zm_html_view_montagefeed.php \
	zm_html_view_montageframe.php \
	zm_html_view_montagemenu.php \
	zm_html_view_montage.php \
	zm_html_view_montagestatus.php \
	zm_html_view_none.php \
	zm_html_view_optionhelp.php \
	zm_html_view_options.php \
	zm_html_view_postlogin.php \
	zm_html_view_settings.php \
	zm_html_view_state.php \
	zm_html_view_stats.php \
	zm_html_view_user.php \
	zm_html_view_version.php \
	zm_html_view_video.php \
	zm_html_view_watchevents.php \
	zm_html_view_watchfeed.php \
	zm_html_view_watchmenu.php \
	zm_html_view_watch.php \
	zm_html_view_watchstatus.php \
	zm_html_view_zone.php \
	zm_html_view_zones.php \
	zm_lang.php \
	zm_lang_de_de.php \
	zm_lang_en_gb.php \
	zm_lang_en_us.php \
	zm_lang_fr_fr.php \
	zm_lang_ja_jp.php \
	zm_lang_pl_pl.php \
	zm_lang_ru_ru.php \
	zm_lang_nl_nl.php \
	zm_lang_it_it.php \
	zm.php \
	zm_styles.css \
	zm_wml.php \
	zm_wml_view_console.php \
	zm_wml_view_feed.php

EXTRA_DIST = \
	favicon.ico \
	retag.sh \
	swap.pl \
	zm_actions.php \
	zm_config.php.z \
	zm_db.php \
	zm_funcs.php \
	zm_html.php \
	zm_html_view_bandwidth.php \
	zm_html_view_console.php \
	zm_html_view_cycle.php \
	zm_html_view_error.php \
	zm_html_view_event.php \
	zm_html_view_events.php \
	zm_html_view_filter.php \
	zm_html_view_filtersave.php \
	zm_html_view_frame.php \
	zm_html_view_frames.php \
	zm_html_view_function.php \
	zm_html_view_imagefetch.php \
	zm_html_view_login.php \
	zm_html_view_logout.php \
	zm_html_view_monitor.php \
	zm_html_view_montagefeed.php \
	zm_html_view_montageframe.php \
	zm_html_view_montage.php \
	zm_html_view_montagemenu.php \
	zm_html_view_montagestatus.php \
	zm_html_view_none.php \
	zm_html_view_optionhelp.php \
	zm_html_view_options.php \
	zm_html_view_postlogin.php \
	zm_html_view_settings.php \
	zm_html_view_state.php \
	zm_html_view_stats.php \
	zm_html_view_user.php \
	zm_html_view_version.php \
	zm_html_view_video.php \
	zm_html_view_watchevents.php \
	zm_html_view_watchfeed.php \
	zm_html_view_watchmenu.php \
	zm_html_view_watch.php \
	zm_html_view_watchstatus.php \
	zm_html_view_zone.php \
	zm_html_view_zones.php \
	zm_lang.php \
	zm_lang_de_de.php \
	zm_lang_en_gb.php \
	zm_lang_en_us.php \
	zm_lang_fr_fr.php \
	zm_lang_ja_jp.php \
	zm_lang_pl_pl.php \
	zm_lang_ru_ru.php \
	zm_lang_nl_nl.php \
	zm_lang_it_it.php \
	zm.php \
	zm_styles.css \
	zm_wml.php \
	zm_wml_view_console.php \
	zm_wml_view_feed.php

# Yes, you are correct. This is a HACK!
install-data-hook:
	( cd $(webdir); chown $(webuser):$(webgroup) $(web_DATA) )
	@-( cd $(webdir); if ! test -e events; then mkdir events; fi; chown $(webuser):$(webgroup) events; chmod u+w events )
	@-( cd $(webdir); if ! test -e images; then mkdir images; fi; chown $(webuser):$(webgroup) images; chmod u+w images )
	@-( cd $(webdir); if ! test -e sounds; then mkdir sounds; fi; chown $(webuser):$(webgroup) sounds; chmod u+w sounds )
	@-( cd $(webdir); if ! test -e temp; then mkdir temp; fi; chown $(webuser):$(webgroup) temp; chmod u+w temp )
	-( cd $(webdir); ln -s zm.php index.php )

