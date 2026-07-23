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

<launching> C:\Users\63917\AppData\Local\ms-playwright\chromium_headless_shell-1228\chrome-headless-shell-win64\chrome-headless-shell.exe --disable-field-trial-config --disable-background-networking --disable-background-timer-throttling --disable-backgrounding-occluded-windows --disable-back-forward-cache --disable-breakpad --disable-client-side-phishing-detection --disable-component-extensions-with-background-pages --disable-component-update --no-default-browser-check --disable-default-apps --disable-dev-shm-usage --disable-edgeupdater --disable-extensions --disable-features=AvoidUnnecessaryBeforeUnloadCheckSync,BoundaryEventDispatchTracksNodeRemoval,DestroyProfileOnBrowserClose,DialMediaRouteProvider,GlobalMediaControls,HttpsUpgrades,LensOverlay,MediaRouter,PaintHolding,ThirdPartyStoragePartitioning,Translate,AutoDeElevate,RenderDocument,OptimizationHints,msForceBrowserSignIn,msEdgeUpdateLaunchServicesPreferredVersion --enable-features=CDPScreenshotNewSurface --allow-pre-commit-input --disable-hang-monitor --disable-ipc-flooding-protection --disable-popup-blocking --disable-prompt-on-repost --disable-renderer-backgrounding --force-color-profile=srgb --metrics-recording-only --no-first-run --password-store=basic --use-mock-keychain --no-service-autorun --export-tagged-pdf --disable-search-engine-choice-screen --unsafely-disable-devtools-self-xss-warnings --edge-skip-compat-layer-relaunch --disable-infobars --disable-search-engine-choice-screen --disable-sync --enable-unsafe-swiftshader --headless --hide-scrollbars --mute-audio --blink-settings=primaryHoverType=2,availableHoverTypes=2,primaryPointerType=4,availablePointerTypes=4 --no-sandbox --user-data-dir=C:\Users\63917\AppData\Local\Temp\playwright_chromiumdev_profile-fVlB6S --remote-debugging-pipe --no-startup-window
<launched> pid=10004
[pid=10004][err] [0723/152843.513:ERROR:content\browser\gpu\gpu_process_host.cc:1005] GPU process exited unexpectedly: exit_code=-1073741523
[pid=10004][err] [0723/152843.630:WARNING:content\browser\gpu\gpu_process_host.cc:1447] The GPU process has crashed 1 time(s)
Call log:
  - <launching> C:\Users\63917\AppData\Local\ms-playwright\chromium_headless_shell-1228\chrome-headless-shell-win64\chrome-headless-shell.exe --disable-field-trial-config --disable-background-networking --disable-background-timer-throttling --disable-backgrounding-occluded-windows --disable-back-forward-cache --disable-breakpad --disable-client-side-phishing-detection --disable-component-extensions-with-background-pages --disable-component-update --no-default-browser-check --disable-default-apps --disable-dev-shm-usage --disable-edgeupdater --disable-extensions --disable-features=AvoidUnnecessaryBeforeUnloadCheckSync,BoundaryEventDispatchTracksNodeRemoval,DestroyProfileOnBrowserClose,DialMediaRouteProvider,GlobalMediaControls,HttpsUpgrades,LensOverlay,MediaRouter,PaintHolding,ThirdPartyStoragePartitioning,Translate,AutoDeElevate,RenderDocument,OptimizationHints,msForceBrowserSignIn,msEdgeUpdateLaunchServicesPreferredVersion --enable-features=CDPScreenshotNewSurface --allow-pre-commit-input --disable-hang-monitor --disable-ipc-flooding-protection --disable-popup-blocking --disable-prompt-on-repost --disable-renderer-backgrounding --force-color-profile=srgb --metrics-recording-only --no-first-run --password-store=basic --use-mock-keychain --no-service-autorun --export-tagged-pdf --disable-search-engine-choice-screen --unsafely-disable-devtools-self-xss-warnings --edge-skip-compat-layer-relaunch --disable-infobars --disable-search-engine-choice-screen --disable-sync --enable-unsafe-swiftshader --headless --hide-scrollbars --mute-audio --blink-settings=primaryHoverType=2,availableHoverTypes=2,primaryPointerType=4,availablePointerTypes=4 --no-sandbox --user-data-dir=C:\Users\63917\AppData\Local\Temp\playwright_chromiumdev_profile-fVlB6S --remote-debugging-pipe --no-startup-window
  - <launched> pid=10004
  - [pid=10004][err] [0723/152843.513:ERROR:content\browser\gpu\gpu_process_host.cc:1005] GPU process exited unexpectedly: exit_code=-1073741523
  - [pid=10004][err] [0723/152843.630:WARNING:content\browser\gpu\gpu_process_host.cc:1447] The GPU process has crashed 1 time(s)
  - [pid=10004] <gracefully close start>
  - [pid=10004] <kill>
  - [pid=10004] <will force kill>
  - [pid=10004] taskkill stderr: ERROR: Provider load failure
  - [pid=10004] <kill>
  - [pid=10004] <will force kill>
  - [pid=10004] taskkill stderr: ERROR: The process "10004" not found.
  - [pid=10004] <process did exit: exitCode=3221226525, signal=null>
  - [pid=10004] starting temporary directories cleanup
  - [pid=10004] finished temporary directories cleanup
  - [pid=10004] <gracefully close end>

```