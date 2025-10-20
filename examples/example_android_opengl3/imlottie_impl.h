// imlottie_impl.h - backend interface (implemented by rlottie adapter)
#pragma once
#include <cstdint>
#include <cstddef>
#include <memory>

namespace imlottie {

struct Animation {
    virtual ~Animation() = default;

    virtual double frameRate() const = 0;
    virtual size_t totalFrame() const = 0;
    virtual void size(size_t& w, size_t& h) const = 0;
    virtual double duration() const = 0;

    // Render frame index into BGRA premultiplied buffer (width*height*4 bytes)
    virtual void renderSync(size_t frameIndex,
                            uint8_t* dstBGRA, int width, int height,
                            int rowPitchBytes, bool keepAspect) = 0;
};

// Backend functions implemented by imlottie_backend_rlottie.cpp
std::shared_ptr<Animation> animationLoadFromMemory(const unsigned char* data, size_t size);
uint16_t animationTotalFrame(const std::shared_ptr<Animation>& anim);
double   animationDuration(const std::shared_ptr<Animation>& anim);
void     animationRenderSync(const std::shared_ptr<Animation>& anim,
                             int frameIndex,
                             uint32_t* dstBGRA,
                             int width, int height, int rowPitchBytes);

} // namespace imlottie