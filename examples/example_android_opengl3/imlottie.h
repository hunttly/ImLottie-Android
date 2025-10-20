// imlottie.h - public ImGui helper API for Lottie (memory-only, tagged IDs)
#pragma once
#include "imgui.h"
#include <stdint.h>
#include <stddef.h>

namespace ImLottie {

// --- Init/Shutdown/Per-frame ---
void Init();        // call once after your GL/ImGui backends are initialized
void Shutdown();    // optional
void Sync();        // call once per frame (before drawing any lotties)

// ---------------- Unique keys ----------------
static inline ImGuiID KeyFromMemoryTagged(const void* data, size_t size, const char* tag) {
#if (IMGUI_VERSION_NUM >= 18900)
    ImGuiID seed = ImHashStr(tag ? tag : "", 0, 0x5EED1234U);
    return ImHashData(data, size, seed);
#else
    // FNV-1a over tag then data
    uint32_t h = 0x811C9DC5u ^ 0x5EED1234u;
    if (tag) for (const unsigned char* p=(const unsigned char*)tag; *p; ++p) { h ^= *p; h *= 0x01000193u; }
    const uint8_t* d = (const uint8_t*)data;
    for (size_t i=0;i<size;++i) { h^=d[i]; h*=0x01000193u; }
    return (ImGuiID)h;
#endif
}

// ---------------- Internal (implemented in renderer) ----------------
void LottieAnimation_Internal_FromMemory(ImGuiID pid,
                                         const void* data, size_t size,
                                         ImVec2 sz, bool loop, bool play,
                                         int prerender, int customFps);

// ---------------- Draw (TAGGED; replaces old signature) ----------------
inline void LottieAnimationFromMemory(const void* data, size_t size,
                                      const char* unique_tag,
                                      ImVec2 sz, bool loop, bool play,
                                      int prerender, int customFps)
{
    const ImGuiID pid = KeyFromMemoryTagged(data, size, unique_tag);
    LottieAnimation_Internal_FromMemory(pid, data, size, sz, loop, play, prerender, customFps);
}

// ---------------- Controls (TAGGED; replace old ones) ----------------
void Play   (const void* data, size_t size, const char* unique_tag);
void Pause  (const void* data, size_t size, const char* unique_tag);
void Discard(const void* data, size_t size, const char* unique_tag);

} // namespace ImLottie
