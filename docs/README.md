# ImLottie-Android

> ⚡ A high-performance native C++ ImGui overlay for **Android** —  with built-in support for **Lottie** and **animated GIFs**, designed for real-time rendering directly on top of any app or game.

---

## 📸 Overview

This project is a native C++ overlay framework for Android that lets you draw **ImGui** interfaces directly over OpenGL / EGL surfaces — perfect for debugging tools, UI injectors, in-game menus, or custom overlays.

✨ Features:
- 🧩 Pure C++ overlay — no Java UI, no root required  
- 🪟 Renders directly to `ANativeWindow` via EGL and OpenGL ES3  
- 🎨 Fully customizable **ImGui** interface (supports themes, custom styles, fonts)  
- 🪄 **Lottie animation** support via [imlottie](https://github.com/dalerank/imlottie)  
- 🖼️ Animated GIF playback with transparent backgrounds  
- 🪶 Ultra-optimized build (`-O3`, `-flto`, `-fvisibility=hidden`, etc.)  
- 🔥 Multi-animation support — load multiple `.json` Lottie animations from memory or disk  
- 🧠 Built-in support for hex-embedded Lottie files for self-contained binaries  

---

## ✨ Preview

> 🎥 **Live Preview:** ImLottie-Android running with animated Lottie overlays

![ImLottie Preview](https://raw.githubusercontent.com/hunttly/ImLottie-Android/master/docs/preview.gif)

---

## ✨ Usage Example
```
 // ImLottie Sync
    ImLottie::Sync();

    // Show a simple window that we create ourselves. We use a Begin/End pair to created a named window.
    {
        static float f = 0.0f;
        static int counter = 0;

        ImGui::Begin("Hello, world!"); // Create a window called "Hello, world!" and append into it.

        ImGui::Text("This is some useful animations.");               // Display some text (you can use a format strings too)
        ImGui::BeginChild("##main_tab_1", ImVec2(793, 535), true, 0);
        {
            // --- Compute content region center ---
            const ImVec2 crMin = ImGui::GetWindowContentRegionMin();
            const ImVec2 crMax = ImGui::GetWindowContentRegionMax();
            const ImVec2 winPos = ImGui::GetWindowPos();
            const ImVec2 crSize = ImVec2(crMax.x - crMin.x, crMax.y - crMin.y);

            const float totalW = 400.0f;
            const float totalH = 400.0f;
            const float eachW  = totalW / 2.0f;
            const float eachH  = totalH / 2.0f;

            // Top-left corner of the entire 2×2 block
            ImVec2 base = winPos + crMin + ImVec2((crSize.x - totalW) * 0.5f,
                                                  (crSize.y - totalH) * 0.5f);
            base.x = floorf(base.x);
            base.y = floorf(base.y);

            // --- Top-left ---
            ImGui::SetCursorScreenPos(base + ImVec2(0, 0));
            ImLottie::LottieAnimationFromMemory(wow_json, sizeof(wow_json),
                                                "lottie_1", ImVec2(eachW, eachH), true, true, 2, 0);

            // --- Top-right ---
            ImGui::SetCursorScreenPos(base + ImVec2(eachW, 0));
            ImLottie::LottieAnimationFromMemory(cry_json, sizeof(cry_json),
                                                "lottie_2", ImVec2(eachW, eachH), true, true, 2, 0);

            // --- Bottom-left ---
            ImGui::SetCursorScreenPos(base + ImVec2(0, eachH));
            ImLottie::LottieAnimationFromMemory(cool_json, sizeof(cool_json),
                                                "lottie_3", ImVec2(eachW, eachH), true, true, 2, 0);

            // --- Bottom-right ---
            ImGui::SetCursorScreenPos(base + ImVec2(eachW, eachH));
            ImLottie::LottieAnimationFromMemory(nice_json, sizeof(nice_json),
                                                "lottie_4", ImVec2(eachW, eachH), true, true, 2, 0);
        }
        ImGui::EndChild();
        ImGui::End();
    }
```


## 📊 Performance Tips

- Compile with full LTO and strict aliasing:

  LOCAL_CPPFLAGS += -O3 -flto=thin -fstrict-aliasing -fvisibility=hidden -fno-rtti -fno-exceptions

- Use `-ffunction-sections -fdata-sections -Wl,--gc-sections` to minimize size.
- Pre-render Lottie frames (`prerender = 2`) for smoother animation.

---

## 🙏 Credits

This project builds on the amazing work of:

- 💻 Dear ImGui — the backbone of our immediate-mode UI system  
- 🪄 [imlottie](https://github.com/dalerank/imlottie) — high-performance Lottie renderer for ImGui, powered by RLottie  

---

## 📜 License

This project is licensed under the **MIT License** — see `LICENSE` for details.

---

## 💡 Contributing

Contributions are welcome! If you have bug fixes, performance optimizations, or new animation features — feel free to fork and open a pull request.

---

### 🌟 Star this repo if you like it!

If this project helped you build your overlay or game UI, please give it a ⭐ on GitHub — it helps others discover it!

---

# Buy me a coffe !

> Binance ID : 500231999
> USDT (TRC20) : TLY94XXD5CamCLP2qbFWvRhYbXZUfKCjzH
## Made by Hunter

