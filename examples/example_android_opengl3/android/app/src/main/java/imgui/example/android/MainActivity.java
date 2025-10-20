package imgui.example.android;

import android.app.NativeActivity;
import android.content.Context;
import android.os.Bundle;
import android.view.KeyEvent;
import android.view.inputmethod.InputMethodManager;

import java.util.concurrent.LinkedBlockingQueue;

public class MainActivity extends NativeActivity {

    // Queue for Unicode characters to be polled from native code (via pollUnicodeChar())
    private final LinkedBlockingQueue<Integer> unicodeCharacterQueue = new LinkedBlockingQueue<>();

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
    }

    public void showSoftInput() {
        InputMethodManager imm = (InputMethodManager) getSystemService(Context.INPUT_METHOD_SERVICE);
        if (imm != null) {
            imm.showSoftInput(getWindow().getDecorView(), 0);
        }
    }

    public void hideSoftInput() {
        InputMethodManager imm = (InputMethodManager) getSystemService(Context.INPUT_METHOD_SERVICE);
        if (imm != null) {
            imm.hideSoftInputFromWindow(getWindow().getDecorView().getWindowToken(), 0);
        }
    }

    @Override
    public boolean dispatchKeyEvent(KeyEvent event) {
        if (event.getAction() == KeyEvent.ACTION_DOWN) {
            unicodeCharacterQueue.offer(event.getUnicodeChar(event.getMetaState()));
        }
        return super.dispatchKeyEvent(event);
    }

    /** Called from native code to retrieve the next Unicode character (0 if none). */
    public int pollUnicodeChar() {
        Integer ch = unicodeCharacterQueue.poll();
        return (ch != null) ? ch : 0;
    }
}
