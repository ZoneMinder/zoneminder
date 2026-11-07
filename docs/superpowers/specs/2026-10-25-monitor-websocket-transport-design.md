# Per-Monitor WebSocket Transport — Design

Date: 2026-10-25
Branch: `2875-websocket` (based on `master`, builds on PR #4830 by AJ0070)
Status: Agreed contract (open questions resolved 2026-10-25) — implementing phase 1
Issue: refs #2875

## Problem

The web UI reaches live monitor data through `zms`, a per-request CGI that the
web server forks for each stream and each status/command call. Two costs drive
this effort:

1. **The browser 6-connection limit.** HTTP/1.1 browsers open at most ~6
   concurrent connections per host (Chromium, Firefox). A montage of more than a
   handful of cameras — each an `<img>`/`zms` stream plus status polling —
   exhausts that budget and stalls. The WebSocket connection pool is separate
   and far larger (~255 Chromium, ~200 Firefox), so moving streams onto
   WebSockets lifts the ceiling from 6 to ~200 with no other change.

2. **Polling latency and per-request cost.** Monitor status is read by the UI
   polling `zms`/`/ajax` on a timer. Each poll is a fork + connection, and the
   latency is bounded by the poll interval. A pushed update over a persistent
   socket removes both.

The longer-term aim is to **merge `zms` functionality into `zmc`**: the capture
daemon already owns each monitor's packet queue in memory, so it can serve that
monitor's live frames and status directly, without the per-request `zms` fork.

PR #4830 (AJ0070) implemented a per-monitor WebSocket listener in `zmc` that
does exactly this. This note fixes the contract for finishing it.

## Goals

- Serve live video and status for a monitor over a WebSocket from `zmc`,
  removing the per-request `zms` fork from the live path.
- Get past the 6-connection limit for montages.
- Replace status polling with pushed status updates (lower latency, lower cost).
- **No new runtime dependency** — in particular **not go2rtc** and not a
  WebSocket library. The transport stays in-tree in `zmc`.

## Non-goals (this phase)

- **Recorded event playback.** `zms` keeps serving stored events (scrubbing,
  `mode=mpeg`/`jpeg` over events, `CMD_SET_EVENT` navigation). The WebSocket is
  **live-only** for now; playback is explicitly phase 2 (see Phasing).
- Replacing `zms` for anything other than the live + status path.
- A general-purpose WebSocket library, permessage-deflate, client mode, or
  subprotocol negotiation.
- Single-connection multiplexing of many monitors over one socket (see the
  per-monitor decision below — not needed to meet the goals).

## How other projects shape this (and why we diverge)

- **Frigate** keeps its `/ws` purely for state: `{"topic","payload"}` pub/sub
  mirroring MQTT (events, stats, camera_activity). Live video is a **separate**
  path via go2rtc (WebRTC/MSE) + jsmpeg. ZoneMinder deliberately does **not**
  take the go2rtc path, so unlike Frigate our WebSocket **does** carry video —
  that is the whole point here.
- **Shinobi** uses a socket.io `f`/`ff` event model tied to its `ke`/`uid`
  account scheme. Not mimicked: it would add a socket.io dependency and an alien
  account model.
- **Considered and declined:** Frigate's flat, uniformly routable envelope. We
  keep ZoneMinder's richer command model (explicit subscribe/unsubscribe,
  interval, request correlation) and do **not** add a top-level `topic` to
  server→client messages (open question #3 resolved — see Envelope).

## Key decisions

- **WebSocket carries status + events + live video.** No go2rtc, no library.
- **MJPEG is the compatibility baseline** for video (no client-side decoder,
  drop-in for `zms mode=jpeg` montages). Passthrough `h264`/`h265`/`av1` over
  the socket is the efficiency/latency upgrade for clients that can do
  MSE/WebCodecs.
- **Per-monitor listeners** on `MIN_WEBSOCKET_PORT + MonitorId`, served from the
  owning `zmc` — zero-copy from the packet queue, no IPC. (Rationale and the
  rejected single-endpoint alternative below.)
- **Status is pushed on change**, not merely on an interval (see Status).
- **Playback stays in `zms`** this phase.

## Architecture

### Listener model: per-monitor, not single-multiplexed

Each monitor's `zmc` runs a listener on `MIN_WEBSOCKET_PORT + MonitorId`. This
matches `zmc` owning that monitor's packet queue, so the server thread reads
frames and encoded packets straight from it with no extra process or IPC.

The rejected alternative is Frigate's single `/ws` per server. It gives one
connection, one port, and batched status for free, but something must aggregate
every monitor — the opposite of zero-copy-from-packetqueue — which fights the
one-`zmc`-per-monitor model and the zms/zmc merge. We do not need it to beat the
6-limit: at ~200 WebSockets/host, a per-monitor montage scales to ~200 cameras.

Two downsides of per-monitor are handled operationally rather than
architecturally:

- **External surface / proxying.** Do not require operators to expose a port
  range. Front the per-monitor listeners with a single reverse-proxy path, e.g.
  `/live/ws/<MonitorId>` → internal `MIN_WEBSOCKET_PORT + MonitorId`, so one
  external port/vhost covers all monitors. (Proxy-config detail, not a code
  change here; document it.)
- **Status-only montages.** A dashboard showing many cameras' *state* with no
  video would otherwise open N sockets just to read status, worse than today's
  single batched `MonitorStatus` ajax. Keep a lightweight batched-status path
  for that case (the existing ajax for now; a future `server` WebSocket could
  batch it). When a view shows video, status rides the already-open per-monitor
  socket — the common case and a clear win.

### Status: event-driven push

`zmc` pushes a status frame to status-subscribers **when state changes**
(alarm/analysing transition, connect/disconnect, signal, fps/bandwidth beyond a
threshold), with the subscription `interval_ms` acting as a coalesce bound and
keepalive rather than the trigger. This is the actual improvement over `/ajax`
polling; an interval-only push still carries the poll latency.

### Video

- `mode` is chosen by the client per subscription via an explicit codec name
  (`mjpeg` | `h264` | `h265` | `av1`).
- `mjpeg`: server sends the latest decoded frame as JPEG; `interval_ms` throttles.
- passthrough codecs: server streams queued encoded packets in order from the
  latest keyframe, extradata prepended on keyframes, so a new subscriber has a
  decodable start; `interval_ms` is ignored, `sequence` tracks queue order.
- The capture thread never blocks on a client: the server thread reads an owned
  snapshot of the stream parameters and the queue's refcounted packets, never
  the live camera/AVStream (already implemented — see Current state).

### Envelope

Keep the command model: `{"command":"subscribe","topic":"status|events|stream",
"interval_ms":N,"codec":"...","request_id":"..."}` and one-shot
`{"command":"status|image|stream",...}`. Image/stream payloads are a JSON
metadata text frame followed by a binary frame.

Decided: **no** added top-level `topic` on server→client messages (open question
#3). The command model is kept as-is; a Frigate-shaped consumer routes on the
existing `command`/subscription fields rather than a separate `topic`.

### TLS and auth (already settled and implemented)

- TLS terminated in `zmc` via `WEBSOCKET_TLS_CERT`/`WEBSOCKET_TLS_KEY`, backend-
  neutral (`ZM_CRYPTO_BACKEND`), because this listener is not fronted by the web
  server. Reverse-proxy TLS termination remains possible (leave both unset).
- Authentication enforced in-handshake whenever `OPT_USE_AUTH` is on; the user
  needs stream-view permission and monitor access. IP-bound hashes
  (`AUTH_HASH_IPS`) validate against the socket peer (no CGI `REMOTE_ADDR`).

## Phasing

1. **This branch — live + status.** Per-monitor WebSocket carrying status,
   events, and live video from `zmc`; harden #4830 (done, see below); event-
   driven status (done); proxy-path documentation (done — see
   `docs/api_monitor_websocket.rst`). Phase 1 complete pending review.
2. **UI cutover.** Montage/watch views open the per-monitor WebSocket for video
   and status instead of `zms` streams + status polling. Measure against the
   6-limit and latency goals.
3. **Playback (later).** Decide whether recorded-event playback moves onto a
   WebSocket (extending this contract) or `zms` remains the playback server.
   The live API must not be shaped so it boxes playback out.

## Current state (branch `2875-websocket`)

PR #4830 merged onto `master` (authorship preserved), then hardened:

- Video stream read via an owned snapshot, not the live camera/AVStream — fixes
  a use-after-free on reconnect (`zm::websocket::VideoStreamInfo`).
- `AUTH_HASH_IPS` validated against the accepted socket peer.
- `Base64Encode` made thread-safe (constant table).
- Poll loop corrected: TLS want-read/want-write honored both directions,
  pipelined first frame drained, duplicate status-on-subscribe removed.
- Listener reconciled on config reload (port/TLS/enable change).
- Docs corrected to state the listener does terminate TLS and enforce auth.
- Status is now pushed on state change (open question #2): `zmc` bumps a status
  generation on a significant change and the server pushes as soon as it
  advances, coalesced by a short floor, with `interval_ms` kept as keepalive.

Builds clean (`zmc`/`zms`/`zmu`/`zma`/`tests`); WebSocket unit tests cover
framing, handshake, the stream-info snapshot, and peer-address parsing, with
`[races]` cases for ThreadSanitizer.

## Open questions — resolved 2026-10-25

1. **Resolved (2026-10-25, @connortechnology): per-monitor listeners + single
   reverse-proxy path.** The single-multiplexed endpoint is rejected.
2. **Resolved (2026-10-25, @connortechnology): event-driven status push.** `zmc`
   pushes a status frame on state change; `interval_ms` is the coalesce/keepalive
   bound, not the trigger. Replaces #4830's interval-only push for phase 1.
3. **Resolved (2026-10-25, @connortechnology): keep the richer command model, no
   added top-level `topic`.** Server→client messages stay command-shaped; we do
   not bolt on a Frigate-style `topic` field.
4. **Resolved (2026-10-25, @connortechnology): playback is phase 2, out of scope
   here.** The WebSocket stays live-only this phase; `zms` keeps serving recorded
   events. The live API must not be shaped so it boxes playback out later.
5. **Resolved (2026-10-25, @connortechnology): MJPEG is the documented baseline,
   passthrough (`h264`/`h265`/`av1`) is the efficiency/latency upgrade.** MJPEG is
   the compatibility floor (no client decoder); passthrough serves MSE/WebCodecs
   clients.

## Files (orientation, not a change list)

- `src/zm_websocket.{h,cpp}` — listener, poll loop, framing, auth, snapshot read.
- `src/zm_websocket_stream_info.h` — owned stream-parameter snapshot.
- `src/zm_monitor.{h,cpp}` — listener lifecycle, snapshot publish/invalidate,
  reload reconciliation.
- `src/zm_tls.{h,cpp}` — backend-neutral server TLS.
- `src/zmc.cpp` — start/stop/reload wiring, capture-lifecycle events.
- `docs/api_monitor_websocket.rst` — the user/integrator-facing API reference.
- `tests/zm_websocket*.cpp`, `tests/zm_utils.cpp` — framing, snapshot, peer addr.
