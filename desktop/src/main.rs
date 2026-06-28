/*
 * Copyright (c) 2026 Bastiaan van der Plaat
 *
 * SPDX-License-Identifier: MIT
 */

#![cfg_attr(all(windows, not(debug_assertions)), windows_subsystem = "windows")]
#![forbid(unsafe_code)]

use bwebview::{EventLoopBuilder, LogicalSize, Theme, WebviewBuilder};

fn main() {
    let event_loop = EventLoopBuilder::new()
        .app_id("nl", "diekantankys", "Strepen")
        .build();

    let _webview = WebviewBuilder::new()
        .title("Strepen")
        .size(LogicalSize::new(1280.0, 720.0))
        .min_size(LogicalSize::new(640.0, 480.0))
        .center()
        .remember_window_state()
        .background_color(0x0a0a0a0a)
        .theme(Theme::Dark)
        .load_url("https://stam.diekantankys.nl/")
        .build();

    event_loop.run(|_event| {});
}
