# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: load.stress.spec.js >> Stress Test: Dashboard Analytics >> Concurrent Admin Dashboard Loading
- Location: tests\stress\load.stress.spec.js:4:3

# Error details

```
Error: browserType.launch: Target page, context or browser has been closed
Browser logs:

<launching> C:\Users\63917\AppData\Local\ms-playwright\chromium_headless_shell-1228\chrome-headless-shell-win64\chrome-headless-shell.exe --disable-field-trial-config --disable-background-networking --disable-background-timer-throttling --disable-backgrounding-occluded-windows --disable-back-forward-cache --disable-breakpad --disable-client-side-phishing-detection --disable-component-extensions-with-background-pages --disable-component-update --no-default-browser-check --disable-default-apps --disable-dev-shm-usage --disable-edgeupdater --disable-extensions --disable-features=AvoidUnnecessaryBeforeUnloadCheckSync,BoundaryEventDispatchTracksNodeRemoval,DestroyProfileOnBrowserClose,DialMediaRouteProvider,GlobalMediaControls,HttpsUpgrades,LensOverlay,MediaRouter,PaintHolding,ThirdPartyStoragePartitioning,Translate,AutoDeElevate,RenderDocument,OptimizationHints,msForceBrowserSignIn,msEdgeUpdateLaunchServicesPreferredVersion --enable-features=CDPScreenshotNewSurface --allow-pre-commit-input --disable-hang-monitor --disable-ipc-flooding-protection --disable-popup-blocking --disable-prompt-on-repost --disable-renderer-backgrounding --force-color-profile=srgb --metrics-recording-only --no-first-run --password-store=basic --use-mock-keychain --no-service-autorun --export-tagged-pdf --disable-search-engine-choice-screen --unsafely-disable-devtools-self-xss-warnings --edge-skip-compat-layer-relaunch --disable-infobars --disable-search-engine-choice-screen --disable-sync --enable-unsafe-swiftshader --headless --hide-scrollbars --mute-audio --blink-settings=primaryHoverType=2,availableHoverTypes=2,primaryPointerType=4,availablePointerTypes=4 --no-sandbox --user-data-dir=C:\Users\63917\AppData\Local\Temp\playwright_chromiumdev_profile-vGHSj1 --remote-debugging-pipe --no-startup-window
<launched> pid=41500
[pid=41500][err] [0723/152923.295:ERROR:content\browser\gpu\gpu_process_host.cc:999] GPU process launch failed: error_code=63
[pid=41500][err] [0723/152923.300:WARNING:content\browser\gpu\gpu_process_host.cc:1447] The GPU process has crashed 1 time(s)
Call log:
  - <launching> C:\Users\63917\AppData\Local\ms-playwright\chromium_headless_shell-1228\chrome-headless-shell-win64\chrome-headless-shell.exe --disable-field-trial-config --disable-background-networking --disable-background-timer-throttling --disable-backgrounding-occluded-windows --disable-back-forward-cache --disable-breakpad --disable-client-side-phishing-detection --disable-component-extensions-with-background-pages --disable-component-update --no-default-browser-check --disable-default-apps --disable-dev-shm-usage --disable-edgeupdater --disable-extensions --disable-features=AvoidUnnecessaryBeforeUnloadCheckSync,BoundaryEventDispatchTracksNodeRemoval,DestroyProfileOnBrowserClose,DialMediaRouteProvider,GlobalMediaControls,HttpsUpgrades,LensOverlay,MediaRouter,PaintHolding,ThirdPartyStoragePartitioning,Translate,AutoDeElevate,RenderDocument,OptimizationHints,msForceBrowserSignIn,msEdgeUpdateLaunchServicesPreferredVersion --enable-features=CDPScreenshotNewSurface --allow-pre-commit-input --disable-hang-monitor --disable-ipc-flooding-protection --disable-popup-blocking --disable-prompt-on-repost --disable-renderer-backgrounding --force-color-profile=srgb --metrics-recording-only --no-first-run --password-store=basic --use-mock-keychain --no-service-autorun --export-tagged-pdf --disable-search-engine-choice-screen --unsafely-disable-devtools-self-xss-warnings --edge-skip-compat-layer-relaunch --disable-infobars --disable-search-engine-choice-screen --disable-sync --enable-unsafe-swiftshader --headless --hide-scrollbars --mute-audio --blink-settings=primaryHoverType=2,availableHoverTypes=2,primaryPointerType=4,availablePointerTypes=4 --no-sandbox --user-data-dir=C:\Users\63917\AppData\Local\Temp\playwright_chromiumdev_profile-vGHSj1 --remote-debugging-pipe --no-startup-window
  - <launched> pid=41500
  - [pid=41500][err] [0723/152923.295:ERROR:content\browser\gpu\gpu_process_host.cc:999] GPU process launch failed: error_code=63
  - [pid=41500][err] [0723/152923.300:WARNING:content\browser\gpu\gpu_process_host.cc:1447] The GPU process has crashed 1 time(s)
  - [pid=41500] <gracefully close start>
  - [pid=41500] <kill>
  - [pid=41500] <will force kill>
  - [pid=41500] taskkill stderr: ERROR: Server execution failed
  - [pid=41500] <kill>
  - [pid=41500] <will force kill>
  - [pid=41500] taskkill stderr: ERROR: The process with PID 41500 (child process of PID 19300) could not be terminated.
Reason: There is no running instance of the task.
  - [pid=41500] <process did exit: exitCode=3221225725, signal=null>
  - [pid=41500] starting temporary directories cleanup
  - [pid=41500] finished temporary directories cleanup
  - [pid=41500] <gracefully close end>

```