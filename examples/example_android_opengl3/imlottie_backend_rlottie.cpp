// imlottie_backend_rlottie.cpp - Samsung rlottie adapter (memory-only)
#include "imlottie_impl.h"

// Include <string> BEFORE rlottie.h to avoid binding issues on some toolchains
#include <string>
#include <memory>
#include <rlottie.h>

namespace {

class RlottieAnimAdapter : public imlottie::Animation {
public:
    explicit RlottieAnimAdapter(std::shared_ptr<rlottie::Animation> a)
        : anim_(std::move(a)) {}

    double frameRate() const override { return anim_ ? anim_->frameRate() : 0.0; }
    size_t totalFrame() const override { return anim_ ? anim_->totalFrame() : 0; }
    void size(size_t& w, size_t& h) const override { w = h = 0; }
    double duration() const override { return anim_ ? anim_->duration() : 0.0; }

    void renderSync(size_t frameIndex,
                    uint8_t* dstBGRA,
                    int width, int height,
                    int rowPitchBytes, bool /*keepAspect*/) override
    {
        if (!anim_ || !dstBGRA || width <= 0 || height <= 0) return;
        rlottie::Surface surf(reinterpret_cast<uint32_t*>(dstBGRA),
                              (size_t)width, (size_t)height, (size_t)rowPitchBytes);
        anim_->renderSync((double)frameIndex, surf);
    }

private:
    std::shared_ptr<rlottie::Animation> anim_;
};

} // namespace

namespace imlottie {

std::shared_ptr<Animation> animationLoadFromMemory(const unsigned char* data, size_t size) {
    if (!data || size == 0) return nullptr;

    // Build std::string and move it into rlottie (some overloads take &&)
    std::string json(reinterpret_cast<const char*>(data), size);

    // Pass explicit std::string lvalues and disable cache to avoid shared instances
    std::string keyPath;
    std::string resourcePath;
    bool cachePolicy = false; // <— IMPORTANT: avoid rlottie internal caching for memory loads

    auto rl = rlottie::Animation::loadFromData(std::move(json),
                                               keyPath,
                                               resourcePath,
                                               cachePolicy);
    if (!rl) return nullptr;

    return std::make_shared<RlottieAnimAdapter>(std::move(rl));
}

uint16_t animationTotalFrame(const std::shared_ptr<Animation>& a) {
    return a ? (uint16_t)a->totalFrame() : 0;
}

double animationDuration(const std::shared_ptr<Animation>& a) {
    return a ? a->duration() : 0.0;
}

void animationRenderSync(const std::shared_ptr<Animation>& a,
                         int frameIndex,
                         uint32_t* dstBGRA,
                         int width, int height, int rowPitchBytes)
{
    if (!a || !dstBGRA) return;
    a->renderSync((size_t)frameIndex, reinterpret_cast<uint8_t*>(dstBGRA),
                  width, height, rowPitchBytes, true);
}

} // namespace imlottie