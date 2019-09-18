#header-only library
include(vcpkg_common_functions)

set(SOURCE_PATH ${CURRENT_BUILDTREES_DIR}/src/jwt-cpp)

vcpkg_from_github(OUT_SOURCE_PATH SOURCE_PATH
    REPO Thalhammer/jwt-cpp
    REF f0e37a79f605312686065405dd720fc197cc3df0
    SHA512 ae83c205dbb340dedc58d0d3f0e2453c4edcf5ce43b401f49d02692dc8a2a4b7260f1ced05ddfa7c1d5d6f92446e232629ddbdf67a58a119b50c5c8163591598
    HEAD_REF master
    PATCHES fix-picojson.patch
            fix-warning.patch)

# Copy the constexpr header files
file(GLOB HEADER_FILES ${SOURCE_PATH}/include/jwt-cpp/*)
file(COPY ${HEADER_FILES}
     DESTINATION ${CURRENT_PACKAGES_DIR}/include/jwt-cpp
     REGEX "\.(gitattributes|gitignore|picojson.h)$" EXCLUDE)

# Put the licence file where vcpkg expects it
file(COPY ${SOURCE_PATH}/LICENSE
     DESTINATION ${CURRENT_PACKAGES_DIR}/share/jwt-cpp)
file(RENAME ${CURRENT_PACKAGES_DIR}/share/jwt-cpp/LICENSE ${CURRENT_PACKAGES_DIR}/share/jwt-cpp/copyright)