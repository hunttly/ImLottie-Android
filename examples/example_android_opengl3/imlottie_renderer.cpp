// imlottie_renderer.cpp - renderer for ImLottie (memory-only, tagged, multi-instance-safe)
#include "imlottie.h"
#include "imlottie_impl.h"

#include "imgui.h"
#include "imgui_internal.h"
#include <vector>
#include <unordered_map>
#include <mutex>
#include <cmath>
#include <string>
#include <memory>
#include <string.h>

#if defined(__ANDROID__) || defined(IMLOTTIE_GLES3_IMPLEMENTATION)
# include <GLES3/gl3.h>
#else
# include <glad/glad.h>
#endif

using std::vector;

namespace ImLottie {

static inline double GetFrameDeltaSeconds() {
    return (double)ImGui::GetIO().DeltaTime;
}

// ---------- ARGB premul (BGRA order) -> RGBA straight ----------
static inline void argbPremul_to_rgbaStraight(uint8_t* dstRGBA, const uint8_t* srcBGRA, size_t pxCount)
{
    for (size_t i = 0; i < pxCount; ++i) {
        const uint8_t B = srcBGRA[4*i + 0];
        const uint8_t G = srcBGRA[4*i + 1];
        const uint8_t R = srcBGRA[4*i + 2];
        const uint8_t A = srcBGRA[4*i + 3];
        if (A == 0) {
            dstRGBA[4*i + 0] = 0;
            dstRGBA[4*i + 1] = 0;
            dstRGBA[4*i + 2] = 0;
            dstRGBA[4*i + 3] = 0;
        } else {
            dstRGBA[4*i + 0] = (uint8_t)((int)R * 255 / A);
            dstRGBA[4*i + 1] = (uint8_t)((int)G * 255 / A);
            dstRGBA[4*i + 2] = (uint8_t)((int)B * 255 / A);
            dstRGBA[4*i + 3] = A;
        }
    }
}

// ---------- Commands ----------
enum class CmdType : uint8_t { LOAD_MEM, PLAY, PAUSE, DISCARD };
struct Cmd {
    CmdType type{};
    ImGuiID pid = 0;
    vector<unsigned char> mem; // for LOAD_MEM
};

struct Tex {
    GLuint id = 0;
    int w = 0, h = 0;
};

struct TrackState {
    bool loop = true;
    bool play = true;

    int customFps = 0;
    double fps = 30.0;
    uint32_t total = 0;
    double duration = 0.0;

    double accum = 0.0;   // time accumulator for frame stepping
    int curFrame = 0;

    ImVec2 size = ImVec2(200,200);
    bool uploadedOnce = false;
};

struct Track {
    std::shared_ptr<imlottie::Animation> anim;
    TrackState st;
    Tex tex;
    vector<uint8_t> stagingBGRA; // rlottie output
    vector<uint8_t> stagingRGBA; // GL upload buffer
};

struct Renderer {
    std::mutex mtx;
    vector<Cmd> queue;
    std::unordered_map<ImGuiID, Track> tracks;

    void push(const Cmd& c) {
        std::lock_guard<std::mutex> l(mtx);
        queue.push_back(c);
    }

    void processQueue() {
        vector<Cmd> q;
        {
            std::lock_guard<std::mutex> l(mtx);
            q.swap(queue);
        }
        for (auto& c : q) {
            switch (c.type) {
                case CmdType::LOAD_MEM: onLoadMem(c); break;
                case CmdType::PLAY:     setPlay(c.pid, true); break;
                case CmdType::PAUSE:    setPlay(c.pid, false); break;
                case CmdType::DISCARD:  onDiscard(c.pid); break;
            }
        }
    }

    void setPlay(ImGuiID pid, bool play) {
        auto it = tracks.find(pid);
        if (it != tracks.end()) it->second.st.play = play;
    }

    void onDiscard(ImGuiID pid) {
        auto it = tracks.find(pid);
        if (it == tracks.end()) return;
        if (it->second.tex.id) {
            glDeleteTextures(1, &it->second.tex.id);
        }
        tracks.erase(it);
    }

    void onLoadMem(const Cmd& c) {
        if (c.mem.empty()) return;

        auto anim = imlottie::animationLoadFromMemory(c.mem.data(), c.mem.size());
        if (!anim) return;

        auto& tr = tracks[c.pid]; // create or fetch
        tr.anim = anim;

        tr.st.total    = imlottie::animationTotalFrame(anim);
        tr.st.duration = imlottie::animationDuration(anim);
        tr.st.fps      = (tr.st.duration > 0.0 && tr.st.total > 0) ? (double)tr.st.total / tr.st.duration : 30.0;
        tr.st.accum    = 0.0;
        tr.st.curFrame = 0;
        tr.st.uploadedOnce = false;
    }

    void produceFrames(double dt) {
        for (auto& kv : tracks) {
            auto& t = kv.second;
            if (!t.anim) continue;

            const double useFps = (t.st.customFps > 0) ? (double)t.st.customFps : t.st.fps;
            if (useFps <= 0.0) continue;

            if (t.st.play) {
                t.st.accum += dt;
                const double frameDur = 1.0 / useFps;
                while (t.st.accum >= frameDur) {
                    t.st.accum -= frameDur;
                    t.st.curFrame++;
                    if ((uint32_t)t.st.curFrame >= t.st.total) {
                        t.st.curFrame = t.st.loop ? 0 : (int)t.st.total - 1;
                    }
                }
            }
        }
    }

    void renderAt(ImGuiID pid, ImVec2 pos, ImVec2 size) {
        auto it = tracks.find(pid);
        if (it == tracks.end() || !it->second.anim) return;

        auto& tr = it->second;
        tr.st.size = size;

        // ensure texture
        if (tr.tex.id == 0 || tr.tex.w != (int)size.x || tr.tex.h != (int)size.y) {
            if (tr.tex.id) glDeleteTextures(1, &tr.tex.id);
            glGenTextures(1, &tr.tex.id);
            glBindTexture(GL_TEXTURE_2D, tr.tex.id);
            glTexParameteri(GL_TEXTURE_2D, GL_TEXTURE_MIN_FILTER, GL_LINEAR);
            glTexParameteri(GL_TEXTURE_2D, GL_TEXTURE_MAG_FILTER, GL_LINEAR);
            glTexParameteri(GL_TEXTURE_2D, GL_TEXTURE_WRAP_S, GL_CLAMP_TO_EDGE);
            glTexParameteri(GL_TEXTURE_2D, GL_TEXTURE_WRAP_T, GL_CLAMP_TO_EDGE);
            tr.tex.w = (int)size.x;
            tr.tex.h = (int)size.y;
            tr.stagingBGRA.resize(tr.tex.w * tr.tex.h * 4);
            tr.stagingRGBA.resize(tr.stagingBGRA.size());
            glBindTexture(GL_TEXTURE_2D, 0);
            tr.st.uploadedOnce = false;
        }

        // render rlottie frame into BGRA premul
        imlottie::animationRenderSync(tr.anim, tr.st.curFrame,
                                      (uint32_t*)tr.stagingBGRA.data(),
                                      tr.tex.w, tr.tex.h, tr.tex.w * 4);
        // convert to RGBA straight
        argbPremul_to_rgbaStraight(tr.stagingRGBA.data(), tr.stagingBGRA.data(),
                                   (size_t)tr.tex.w * (size_t)tr.tex.h);

        // upload
        glBindTexture(GL_TEXTURE_2D, tr.tex.id);
        if (!tr.st.uploadedOnce) {
            glPixelStorei(GL_UNPACK_ALIGNMENT, 1);
            glTexImage2D(GL_TEXTURE_2D, 0, GL_RGBA,
                         tr.tex.w, tr.tex.h, 0, GL_RGBA, GL_UNSIGNED_BYTE,
                         tr.stagingRGBA.data());
            tr.st.uploadedOnce = true;
        } else {
            glTexSubImage2D(GL_TEXTURE_2D, 0, 0, 0,
                            tr.tex.w, tr.tex.h,
                            GL_RGBA, GL_UNSIGNED_BYTE,
                            tr.stagingRGBA.data());
        }
        glBindTexture(GL_TEXTURE_2D, 0);

        // draw in ImGui
        ImGui::SetCursorScreenPos(pos);
        ImGui::Image((ImTextureID)(intptr_t)tr.tex.id, size);
    }

    void apiDrawMem(ImGuiID pid,
                    const void* data, size_t size,
                    ImVec2 sz, bool loop, bool play, int prerender, int customFps)
    {
        (void)prerender; // not used but kept for signature compatibility

        // lazy-load
        if (tracks.find(pid) == tracks.end()) {
            Cmd c;
            c.type = CmdType::LOAD_MEM;
            c.pid  = pid; // MUST be the tagged pid you computed in the header wrapper
            c.mem.resize(size);
            memcpy(c.mem.data(), data, size);
            push(c);

            auto& tr = tracks[pid]; // create early to store flags
            tr.st.loop = loop;
            tr.st.play = play;
            tr.st.customFps = customFps;
        } else {
            auto& tr = tracks[pid];
            tr.st.loop = loop;
            tr.st.play = play;
            tr.st.customFps = customFps;
        }

        const ImVec2 pos = ImGui::GetCursorScreenPos();
        renderAt(pid, pos, sz);
    }

    void apiPlay(ImGuiID pid, bool v) {
        Cmd c; c.type = v ? CmdType::PLAY : CmdType::PAUSE; c.pid = pid; push(c);
    }
    void apiDiscard(ImGuiID pid) {
        Cmd c; c.type = CmdType::DISCARD; c.pid = pid; push(c);
    }
};

// global
static Renderer* G = nullptr;

// ----------- Public API -----------
void Init() { if (!G) G = new Renderer(); }
void Shutdown() {
    if (!G) return;
    for (auto& kv : G->tracks) if (kv.second.tex.id) glDeleteTextures(1, &kv.second.tex.id);
    delete G; G = nullptr;
}
void Sync() {
    if (!G) return;
    const double dt = GetFrameDeltaSeconds();
    G->processQueue();
    G->produceFrames(dt);
}

void LottieAnimation_Internal_FromMemory(ImGuiID pid,
                                         const void* data, size_t size,
                                         ImVec2 sz, bool loop, bool play,
                                         int prerender, int customFps)
{
    if (!G) return;
    G->apiDrawMem(pid, data, size, sz, loop, play, prerender, customFps);
}

// TAGGED controls (replace old ones)
void Play(const void* data, size_t size, const char* tag)   { if (!G) return; G->apiPlay(KeyFromMemoryTagged(data, size, tag), true); }
void Pause(const void* data, size_t size, const char* tag)  { if (!G) return; G->apiPlay(KeyFromMemoryTagged(data, size, tag), false); }
void Discard(const void* data, size_t size, const char* tag){ if (!G) return; G->apiDiscard(KeyFromMemoryTagged(data, size, tag)); }

} // namespace ImLottie
