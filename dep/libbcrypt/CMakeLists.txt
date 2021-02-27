enable_language(ASM)

set(BCRYPT_SOURCES
  ${CMAKE_CURRENT_SOURCE_DIR}/src/bcrypt.c
  ${CMAKE_CURRENT_SOURCE_DIR}/src/crypt_blowfish.c
  ${CMAKE_CURRENT_SOURCE_DIR}/src/crypt_gensalt.c
  ${CMAKE_CURRENT_SOURCE_DIR}/src/wrapper.c
  ${CMAKE_CURRENT_SOURCE_DIR}/src/x86.S)

add_library(bcrypt STATIC ${BCRYPT_SOURCES})
add_library(libbcrypt::bcrypt ALIAS bcrypt)

target_include_directories(bcrypt
  PUBLIC
    ${CMAKE_CURRENT_SOURCE_DIR}/include/bcrypt)

target_link_libraries(bcrypt
  PRIVATE
    zm-dependency-interface)

if(BSD)
  target_compile_definitions(bcrypt
    PRIVATE
      __SKIP_GNU)
endif()
