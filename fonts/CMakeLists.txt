# Glob all database upgrade scripts
file(GLOB fontfileslist RELATIVE "${CMAKE_CURRENT_SOURCE_DIR}" "*.zmfnt")
# Install the fonts
install(FILES ${fontfileslist} DESTINATION "${ZM_FONTDIR}")
