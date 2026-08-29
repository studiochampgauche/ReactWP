---
name: reactwp-state-and-interactions
description: ReactWP frontend guidance for choosing state ownership, async/loading/error states, forms, disclosures, overlays, URL/route state, animation state, event cleanup, and resilient user interactions.
---

# State and Interactions

## When to Use This Reference

Apply when adding interactive controls, async data, filters, forms, menus, dialogs, drawers, tabs, accordions, carousels, route-aware state, or animation-driven UI.

State should live in the narrowest layer that owns its meaning and lifecycle.

## Classify State Before Coding

| State kind | Preferred owner |
| --- | --- |
| WordPress route/content | ReactWP route payload and `RouteService` |
| Shareable filter/search/page | URL path/search parameters |
| Cross-route shell state | `AppShell`/route context only when truly global |
| Component disclosure/input | local React state |
| Server mutation/persistence | server/API response plus local pending/result state |
| Animation progress | GSAP/timeline/ScrollTrigger, not per-frame React state |
| Smooth scroll/lock position | ReactWP `Scroller` |
| Template/module/media readiness | ReactWP `Loader` |

Do not mirror route data into local state unless the user can edit a draft independently. Duplicated state creates stale navigation and hydration bugs.

## URL State

Use normalized query/path state when a user expects refresh, sharing, back/forward, or deep linking to preserve a selection.

- Keep parameters bounded and canonical.
- Use ReactWP's normalized search/path utilities and router navigation.
- Distinguish a same-route query change from a purely local visual toggle.
- Avoid writing to history on every keystroke without debouncing or a deliberate replace/push policy.
- Restore focus/scroll according to the interaction, not by blindly resetting every URL change.

Purely ephemeral state such as an open tooltip usually does not belong in the URL.

## Async State Model

Represent the states the user can observe:

```text
idle -> pending -> success
              -> empty
              -> error -> retry
```

- Keep existing content visible during background refresh when appropriate.
- Disable only the control/action that must not repeat; do not freeze the entire page.
- Prevent stale responses from replacing newer intent through cancellation, request identity, or effect cleanup.
- Preserve user input after recoverable failure.
- Give loading feedback only when latency is perceptible; avoid flashing spinners.
- Use meaningful empty states rather than treating an empty result as an exception.

ReactWP's route Loader owns navigation readiness. A component fetch should not alter `window.loader` or block route entry unless explicitly integrated into the route contract.

## Forms

- For formatted, constrained, submitted, or persisted fields, follow the shared [form field contracts](../../../backend-expert/references/form-field-contracts.md) and use the backend-approved revision rather than inventing a client-only grammar.
- Prefer native form semantics and browser behaviors.
- Keep field state local unless it is a multi-step workflow needing a shared owner.
- Validate on the server regardless of client validation.
- Apply field rules to typing, deletion, selection replacement, paste, drop, autofill, mobile, IME, browser restoration, and programmatic submission; a `keydown` filter alone is incomplete.
- Associate labels, instructions, and errors programmatically.
- Track `idle/submitting/succeeded/failed`; prevent accidental duplicate submission while pending.
- Keep secrets out of URL/query state and logs.
- On success, decide whether to update local state, refetch the route, navigate, or invalidate cache; do not combine all four without need.
- If content changes affect static/SSR output, use the relevant ReactWP cache invalidation contract on the server.

For security-sensitive mutations, also load `security-expert`.

## Disclosures, Tabs, and Accordions

Use native behavior when it fits (`details/summary`) or implement the expected semantic pattern:

- control is a button;
- expanded/selected state is exposed;
- relationship to panel is defined;
- keyboard behavior matches the chosen pattern;
- hidden panels are not accidentally focusable;
- animation does not delay state availability.

Do not place critical content exclusively in hover state. Tabs should not become an inaccessible horizontal carousel on mobile; choose a deliberate mobile pattern.

## Dialogs, Menus, and Drawers

An overlay lifecycle includes more than visibility:

1. trigger and accessible name;
2. open state and focus entry;
3. background inertness/scroll lock;
4. Escape and explicit close;
5. cleanup of listeners/animation;
6. focus restoration;
7. route-change behavior.

If an overlay should close on navigation, subscribe at the owner layer and finish cleanup before the old tree disappears. Integrate with ReactWP's scroll lock depth instead of applying unbalanced body styles.

## Optimistic UI

Use optimistic state only when:

- the action is likely to succeed;
- rollback is unambiguous;
- duplicate/reordered operations are understood;
- the UI can communicate pending state;
- failure does not create a dangerous false confirmation.

Avoid optimistic confirmation for payments, permissions, destructive actions, authentication, or operations where server truth has safety consequences.

## Animation State

React state should describe semantic UI state (`open`, `selected`, `submitted`), while GSAP describes interpolation between visual states.

- Do not set React state every animation frame.
- Create animations after commit and scope them to the component.
- Interrupt/overwrite obsolete animations when semantic state changes quickly.
- Ensure the DOM reaches the correct final state with reduced motion.
- Clean up timelines, contexts, triggers, observers, and event callbacks on unmount.
- Do not make an animation callback the only place essential business state changes unless interruption is safely handled.

## Global Events and Observers

- Attach only while the owning component/feature is active.
- Remove listeners with the same target/type/options/reference.
- Disconnect ResizeObserver/IntersectionObserver/MutationObserver.
- Cancel animation frames, timers, and async work.
- Prefer passive scroll/touch listeners unless cancellation is required.
- Avoid one global listener per repeated item when event delegation or an observer pool fits.
- Use current callbacks without repeatedly reattaching expensive global listeners.

## Error Boundaries and Failure Paths

Page-level runtime errors, route-fetch failures, media failures, and component request errors have different owners.

- A component error should not leave global scrolling locked or a route loader pending.
- Route failures retain the existing hard-navigation fallback.
- Media failures need reserved layout and an accessible fallback when content is meaningful.
- Retry must be bounded and user-controlled for persistent failures.
- Error UI should not expose internal stack/upstream details.

## Interaction Checklist

- Is state owned by the narrowest meaningful layer?
- Should it survive refresh/back/share through the URL?
- Are pending, empty, error, success, disabled, and retry states defined?
- Can rapid repeated input produce stale or contradictory state?
- Does keyboard/touch behavior match the semantic pattern?
- Are focus and scroll restored on every close/error/navigation path?
- Is animation derived from semantic state and safely interruptible?
- Are every listener, observer, timer, request, and GSAP object cleaned up?
- Does the interaction remain correct during client navigation and hydration?
