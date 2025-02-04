cmake_minimum_required(VERSION 3.8)
project(jwt-cpp-examples)

if(NOT TARGET jwt-cpp)
  find_package(jwt-cpp CONFIG REQUIRED)
endif()

add_subdirectory(traits)

if(JWT_DISABLE_PICOJSON)
  message(FATAL_ERROR "examples require picojson to be available!")
endif()

add_executable(print-claims print-claims.cpp)
target_link_libraries(print-claims jwt-cpp::jwt-cpp)

add_executable(private-claims private-claims.cpp)
target_link_libraries(private-claims jwt-cpp::jwt-cpp)

add_executable(rsa-create rsa-create.cpp)
target_link_libraries(rsa-create jwt-cpp::jwt-cpp)

add_executable(rsa-verify rsa-verify.cpp)
target_link_libraries(rsa-verify jwt-cpp::jwt-cpp)

add_executable(jwks-verify jwks-verify.cpp)
target_link_libraries(jwks-verify jwt-cpp::jwt-cpp)

add_executable(es256k es256k.cpp)
target_link_libraries(es256k jwt-cpp::jwt-cpp)

add_executable(partial-claim-verifier partial-claim-verifier.cpp)
target_link_libraries(partial-claim-verifier jwt-cpp::jwt-cpp nlohmann_json::nlohmann_json)
