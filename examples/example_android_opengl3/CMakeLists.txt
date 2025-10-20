cmake_minimum_required(VERSION 3.6)

project(ImGuiExample)

set(CMAKE_CXX_STANDARD 11)
set(CMAKE_CXX_STANDARD_REQUIRED ON)
set(CMAKE_CXX_EXTENSIONS OFF)

# ===========================
# RLottie paths (prebuilt)
# ===========================
set(RL_ROOT      ${CMAKE_CURRENT_SOURCE_DIR}/rlottie)
set(RL_LIB_OUT   ${RL_ROOT}/build/${ANDROID_ABI})
set(RL_BUILD_DIR ${RL_ROOT}/build)

add_library(${CMAKE_PROJECT_NAME} SHARED
  ${CMAKE_CURRENT_SOURCE_DIR}/main.cpp
  ${CMAKE_CURRENT_SOURCE_DIR}/imlottie_renderer.cpp
  ${CMAKE_CURRENT_SOURCE_DIR}/imlottie_backend_rlottie.cpp
  ${CMAKE_CURRENT_SOURCE_DIR}/../../imgui.cpp
  ${CMAKE_CURRENT_SOURCE_DIR}/../../imgui_demo.cpp
  ${CMAKE_CURRENT_SOURCE_DIR}/../../imgui_draw.cpp
  ${CMAKE_CURRENT_SOURCE_DIR}/../../imgui_tables.cpp
  ${CMAKE_CURRENT_SOURCE_DIR}/../../imgui_widgets.cpp
  ${CMAKE_CURRENT_SOURCE_DIR}/../../backends/imgui_impl_android.cpp
  ${CMAKE_CURRENT_SOURCE_DIR}/../../backends/imgui_impl_opengl3.cpp
  ${ANDROID_NDK}/sources/android/native_app_glue/android_native_app_glue.c
)

set(CMAKE_SHARED_LINKER_FLAGS
  "${CMAKE_SHARED_LINKER_FLAGS} -u ANativeActivity_onCreate"
)

target_compile_definitions(${CMAKE_PROJECT_NAME} PRIVATE
  IMGUI_IMPL_OPENGL_ES3
  IMLOTTIE_GLES3_IMPLEMENTATION
)


target_include_directories(${CMAKE_PROJECT_NAME} PRIVATE
  ${CMAKE_CURRENT_SOURCE_DIR}/../..
  ${CMAKE_CURRENT_SOURCE_DIR}/../../backends
  ${RL_ROOT}/inc
  ${RL_BUILD_DIR}
  ${ANDROID_NDK}/sources/android/native_app_glue
)

add_library(rlottie STATIC IMPORTED)
set_target_properties(rlottie PROPERTIES
        IMPORTED_LOCATION ${RL_LIB_OUT}/librlottie.a
)

target_link_libraries(${CMAKE_PROJECT_NAME} PRIVATE
  rlottie
  android
  EGL
  GLESv3
  log
)
