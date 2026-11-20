"use strict";

document.querySelectorAll("[data-slot-form]").forEach((form) => {
  const date = form.querySelector("[data-slot-date]");
  const service = form.querySelector("[data-slot-service]");
  const time = form.querySelector("[data-slot-time]");
  const feedback = form.querySelector("[data-slot-feedback]");
  let controller;

  async function loadSlots() {
    controller?.abort();
    time.replaceChildren(new Option("Choose a date and service first", ""));
    if (!date.value || !service.value || date.disabled) return;

    controller = new AbortController();
    const requestController = controller;
    time.disabled = true;
    time.replaceChildren(new Option("Checking availability…", ""));
    feedback.textContent = "Checking available clinic times…";
    const parameters = new URLSearchParams({
      date: date.value,
      service_id: service.value,
    });
    if (form.dataset.appointmentId)
      parameters.set("appointment_id", form.dataset.appointmentId);

    try {
      const response = await fetch(`/slots.php?${parameters}`, {
        signal: requestController.signal,
        headers: { Accept: "application/json" },
      });
      const slots = await response.json();
      if (!response.ok || !Array.isArray(slots))
        throw new Error(slots.error || "Unable to load available times.");

      time.replaceChildren(
        new Option(
          slots.length ? "Choose a time" : "No open times on this day",
          "",
        ),
      );
      for (const slot of slots) {
        const [hours, minutes] = slot.split(":").map(Number);
        const label = `${hours % 12 || 12}:${String(minutes).padStart(2, "0")} ${hours >= 12 ? "PM" : "AM"}`;
        time.add(new Option(label, slot));
      }
      const selected = time.dataset.selected;
      if (selected)
        time.value = selected.length === 5 ? `${selected}:00` : selected;
      feedback.textContent = slots.length
        ? "Times are shown in Philippine time. Each visit uses a 30-minute slot."
        : "The clinic is closed or fully booked. Please choose another date.";
    } catch (error) {
      if (error.name === "AbortError") return;
      time.replaceChildren(new Option("Unable to load times", ""));
      feedback.textContent = `${error.message} Change the date to try again.`;
    } finally {
      if (!requestController.signal.aborted) time.disabled = false;
    }
  }

  date.addEventListener("change", loadSlots);
  service.addEventListener("change", loadSlots);
  form.addEventListener("schedulechange", loadSlots);
  loadSlots();
});

// Phone / walk-in form: the chosen cards decide which fields show and which are required.
document.querySelectorAll("[data-booking-form]").forEach((form) => {
  const picked = (name) =>
    form.querySelector(`input[name="${name}"]:checked`)?.value;

  function update() {
    const mode = picked("pet_mode");
    form.querySelectorAll("[data-mode]").forEach((fieldset) => {
      const active = fieldset.dataset.mode.split(" ").includes(mode);
      fieldset.hidden = !active;
      fieldset.disabled = !active;
    });
    const walkIn = picked("source") === "walk_in";
    const schedule = form.querySelector("[data-phone-schedule]");
    schedule.hidden = walkIn;
    schedule.disabled = walkIn;
    form.querySelector("[data-walk-in-note]").hidden = !walkIn;
    form.querySelectorAll("[data-phone-required]").forEach((input) => {
      input.required = !walkIn;
    });
    form.querySelectorAll("[data-phone-optional]").forEach((label) => {
      label.hidden = !walkIn;
    });
  }

  form.addEventListener("change", (event) => {
    if (event.target.name === "pet_mode") update();
    if (event.target.name === "source") {
      update();
      form.dispatchEvent(new Event("schedulechange"));
    }
  });
  update();
});

// Styled "Are you sure?" box for forms marked with data-confirm. Resolves true on confirm.
function askToConfirm(form) {
  return new Promise((resolve) => {
    const dialog = document.createElement("dialog");
    dialog.className = "confirm-dialog";
    dialog.innerHTML =
      '<span class="confirm-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z" /></svg></span>' +
      '<h2></h2><p></p><div class="confirm-actions"><button type="button" class="button button-secondary" data-answer="no">Cancel</button><button type="button" class="button" data-answer="yes"></button></div>';
    dialog.querySelector("h2").textContent = form.dataset.confirmTitle || "Are you sure?";
    dialog.querySelector("p").textContent = form.dataset.confirm;
    dialog.querySelector('[data-answer="yes"]').textContent = form.dataset.confirmAction || "Yes, continue";
    dialog.addEventListener("click", (event) => {
      const answer = event.target.closest("[data-answer]")?.dataset.answer;
      if (answer || event.target === dialog) dialog.close(answer === "yes" ? "yes" : "no");
    });
    dialog.addEventListener("close", () => {
      resolve(dialog.returnValue === "yes");
      dialog.remove();
    });
    document.body.append(dialog);
    dialog.showModal();
    dialog.querySelector('[data-answer="no"]').focus();
  });
}

// Listens on the document so forms swapped in by the background refresh are covered too.
document.addEventListener("submit", async (event) => {
  const form = event.target;
  if (form.dataset.confirm && form.dataset.confirmed !== "true") {
    event.preventDefault();
    const submitter = event.submitter;
    if (await askToConfirm(form)) {
      form.dataset.confirmed = "true";
      form.requestSubmit(submitter);
    }
    return;
  }
  if (form.method.toLowerCase() === "post") {
    if (form.dataset.submitting === "true") {
      event.preventDefault();
      return;
    }
    form.dataset.submitting = "true";
    const button = event.submitter;
    if (button) {
      button.setAttribute("aria-busy", "true");
      button.classList.add("is-submitting");
    }
  }
});

document
  .querySelector("[data-print]")
  ?.addEventListener("click", () => window.print());

const menuToggle = document.querySelector(".menu-toggle");
function setMenu(open) {
  menuToggle?.setAttribute("aria-expanded", String(open));
  document.body.classList.toggle("menu-open", open);
}
menuToggle?.addEventListener("click", () =>
  setMenu(menuToggle.getAttribute("aria-expanded") !== "true"),
);
document
  .querySelectorAll(".nav-links a")
  .forEach((link) => link.addEventListener("click", () => setMenu(false)));

function closeDropdowns(except) {
  document.querySelectorAll("details[data-dropdown][open]").forEach((menu) => {
    if (menu !== except) menu.removeAttribute("open");
  });
}
document.addEventListener("click", (event) =>
  closeDropdowns(event.target.closest("details[data-dropdown]")),
);
document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    setMenu(false);
    closeDropdowns();
  }
});

// Stronger header shadow and the back-to-top button once the page scrolls.
const header = document.querySelector("[data-header]");
const toTop = document.querySelector("[data-to-top]");
function onScroll() {
  header?.classList.toggle("is-scrolled", window.scrollY > 12);
  toTop?.classList.toggle("is-visible", window.scrollY > 700);
}
window.addEventListener("scroll", onScroll, { passive: true });
onScroll();

// Highlight the home page section that is on screen in the navigation.
const sectionLinks = [
  ...document.querySelectorAll('.nav-links a[href^="/#"]'),
].filter((link) => location.pathname === "/" && document.querySelector(link.hash));
if (sectionLinks.length) {
  const spy = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        sectionLinks.forEach((link) =>
          link.classList.toggle("active", link.hash === `#${entry.target.id}`),
        );
      });
    },
    { rootMargin: "-45% 0px -50% 0px" },
  );
  sectionLinks.forEach((link) => spy.observe(document.querySelector(link.hash)));
}

const observer = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("is-visible");
        observer.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.12 },
);
document
  .querySelectorAll(
    ".section-head, .split-head, .steps li, .app-mock, .side-card, .bento > *, .service-card, .why-card, .quote, .gallery-grid figure, .cta-banner, .contact-grid > *",
  )
  .forEach((element) => {
    element.classList.add("reveal");
    observer.observe(element);
  });


// One real input keeps paste, autofill, keyboard access, and screen readers working.
document.querySelectorAll('[data-otp-code]').forEach((wrapper) => {
  const input = wrapper.querySelector('input');
  const digits = [...wrapper.querySelectorAll('[data-otp-digit]')];
  wrapper.classList.add('is-enhanced');
  function renderCode() {
    input.value = input.value.replace(/\D/g, '').slice(0, 6);
    digits.forEach((digit, index) => {
      digit.textContent = input.value[index] || '';
      digit.classList.toggle('has-value', Boolean(input.value[index]));
      digit.classList.toggle('is-active', index === Math.min(input.value.length, 5));
    });
  }
  input.addEventListener('input', renderCode);
  input.addEventListener('paste', (event) => {
    const code = event.clipboardData?.getData('text').replace(/\D/g, '').slice(0, 6);
    if (code) { event.preventDefault(); input.value = code; renderCode(); }
  });
  renderCode();
});
const resendButton = document.querySelector('[data-resend-at]');
const expiryLabel = document.querySelector('[data-otp-expiry]');
const verificationStartedAt = performance.now();
const verificationServerNow = Number(document.querySelector('[data-otp-clock]')?.dataset.otpClock);
function updateVerificationTimers() {
  const now = verificationServerNow + Math.floor((performance.now() - verificationStartedAt) / 1000);
  if (resendButton) {
    const remaining = Math.max(0, Number(resendButton.dataset.resendAt) - now);
    resendButton.disabled = remaining > 0;
    resendButton.textContent = remaining ? `Resend in ${remaining}s` : 'Resend code';
  }
  if (expiryLabel) {
    const remaining = Math.max(0, Number(expiryLabel.dataset.otpExpiry) - now);
    expiryLabel.textContent = remaining ? `${Math.floor(remaining / 60)}:${String(remaining % 60).padStart(2, '0')}` : 'expired. Request a new code';
    if (!remaining) document.querySelector('[data-otp-submit]')?.setAttribute('disabled', '');
  }
}
if (resendButton || expiryLabel) { updateVerificationTimers(); setInterval(updateVerificationTimers, 1000); }

// Show or hide a password.
document.querySelectorAll("[data-password-toggle]").forEach((button) => {
  const input = button.parentElement.querySelector("input");
  button.addEventListener("click", () => {
    const show = input.type === "password";
    input.type = show ? "text" : "password";
    button.setAttribute("aria-pressed", String(show));
    button.setAttribute("aria-label", show ? "Hide password" : "Show password");
    input.focus();
  });
});

// Pet photo: shrink big phone photos before upload and show a preview.
document.querySelectorAll("[data-photo-input]").forEach((input) => {
  const preview = input
    .closest("[data-photo-field]")
    .querySelector("[data-photo-preview]");
  input.addEventListener("change", async () => {
    if (!input.files[0]) return;
    try {
      const bitmap = await createImageBitmap(input.files[0]);
      const scale = Math.min(1, 900 / Math.max(bitmap.width, bitmap.height));
      const canvas = document.createElement("canvas");
      canvas.width = Math.round(bitmap.width * scale);
      canvas.height = Math.round(bitmap.height * scale);
      const context = canvas.getContext("2d");
      context.fillStyle = "#fff";
      context.fillRect(0, 0, canvas.width, canvas.height);
      context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
      const blob = await new Promise((resolve) =>
        canvas.toBlob(resolve, "image/jpeg", 0.85),
      );
      if (blob) {
        const files = new DataTransfer();
        files.items.add(new File([blob], "pet.jpg", { type: "image/jpeg" }));
        input.files = files.files;
      }
    } catch {
      // This browser cannot read the format; upload the original and let the server check it.
    }
    const image = document.createElement("img");
    image.alt = "";
    image.src = URL.createObjectURL(input.files[0]);
    preview.replaceChildren(image);
  });
});

// Toasts fade away on their own (data-toast = milliseconds) or when closed.
document.querySelectorAll("[data-toast]").forEach((toast) => {
  const hide = () => {
    toast.classList.add("is-leaving");
    setTimeout(() => toast.remove(), 300);
  };
  const timer = setTimeout(hide, Number(toast.dataset.toast));
  toast.querySelector("[data-toast-close]")?.addEventListener("click", () => {
    clearTimeout(timer);
    hide();
  });
});
// Drop ?saved=1 from the address so a refresh does not show the toast again.
const pageUrl = new URL(location.href);
if (["saved", "sent", "failed"].some((key) => pageUrl.searchParams.has(key))) {
  ["saved", "sent", "failed"].forEach((key) => pageUrl.searchParams.delete(key));
  history.replaceState(null, "", pageUrl);
}

// Pet form pop-up. Links still work without JavaScript (?add=1, ?edit=ID).
const petDialog = document.getElementById("pet-dialog");
if (petDialog) {
  const addMode = () => petDialog.dataset.mode === "add";
  if (petDialog.hasAttribute("data-start-open")) {
    petDialog.close();
    petDialog.showModal();
  }
  document.addEventListener("click", (event) => {
    if (!event.target.closest("[data-pet-dialog-open]")) return;
    if (!addMode()) return; // An edit form is loaded: reload a blank one.
    event.preventDefault();
    petDialog.showModal();
  });
  petDialog.querySelectorAll("[data-pet-dialog-close]").forEach((link) =>
    link.addEventListener("click", (event) => {
      if (!addMode()) return; // Leave the edit page.
      event.preventDefault();
      petDialog.close();
    }),
  );
  petDialog.addEventListener("click", (event) => {
    if (event.target === petDialog && addMode()) petDialog.close();
  });
}

// Supplies loop: scrolls by itself, can be dragged, swiped, or wheel-scrolled by hand,
// and starts again 2.5 s after the visitor lets go. A plain click (no drag) stops or starts it.
document.querySelectorAll("[data-marquee]").forEach((marquee) => {
  const track = marquee.querySelector(".product-track");
  const half = track.children.length / 2;
  const speed = innerWidth < 720 ? 34 : 42; // pixels per second
  let paused = matchMedia("(prefers-reduced-motion: reduce)").matches;
  let holdUntil = 0;
  let pos = 1;
  let last = 0;
  let drag = null;
  let dragged = false;
  const loopWidth = () => track.children[half].offsetLeft - track.children[0].offsetLeft;
  const hold = () => {
    holdUntil = performance.now() + 2500;
  };
  // Both halves are identical, so jumping by one half keeps the view the same.
  const wrap = () => {
    const width = loopWidth();
    if (marquee.scrollLeft >= width) marquee.scrollLeft -= width;
    else if (marquee.scrollLeft < 1) marquee.scrollLeft += width;
  };
  marquee.scrollLeft = pos;

  function frame(now) {
    const seconds = last ? Math.min(now - last, 100) / 1000 : 0;
    last = now;
    if (!paused && !drag && now > holdUntil) {
      pos += speed * seconds;
      if (pos >= loopWidth()) pos -= loopWidth();
      marquee.scrollLeft = pos;
    } else {
      pos = marquee.scrollLeft;
    }
    requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);

  marquee.addEventListener("scroll", wrap, { passive: true });
  marquee.addEventListener("wheel", hold, { passive: true });
  marquee.addEventListener("touchstart", hold, { passive: true });
  marquee.addEventListener("pointerdown", (event) => {
    if (event.pointerType !== "mouse" || event.button !== 0) return;
    drag = { x: event.clientX, left: marquee.scrollLeft };
    dragged = false;
  });
  addEventListener("pointermove", (event) => {
    if (!drag) return;
    const dx = event.clientX - drag.x;
    if (Math.abs(dx) > 4) {
      dragged = true;
      marquee.classList.add("is-dragging");
    }
    const target = drag.left - dx;
    marquee.scrollLeft = target;
    wrap();
    drag.left += marquee.scrollLeft - target;
  });
  addEventListener("pointerup", () => {
    if (!drag) return;
    drag = null;
    marquee.classList.remove("is-dragging");
    hold();
  });
  const toggle = () => {
    paused = !paused;
    holdUntil = 0;
    marquee.classList.toggle("is-paused", paused);
    marquee.setAttribute("aria-pressed", String(paused));
  };
  marquee.addEventListener("click", (event) => {
    if (dragged) {
      dragged = false;
      event.preventDefault();
      return;
    }
    toggle();
  });
  marquee.addEventListener("keydown", (event) => {
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      toggle();
    } else if (event.key === "ArrowLeft" || event.key === "ArrowRight") {
      hold();
    }
  });
});

// Signed-in portal pages re-fetch themselves every 3 seconds and swap in only what changed, so the
// clinic and pet owners both see new requests and status changes without reloading. Nothing shows on screen.
if (document.body.hasAttribute("data-refresh")) {
  // Scripts keep their own state in these, so the refresh never touches them.
  const guarded = "dialog, details[open], [data-slot-form], [data-booking-form]";
  // Buttons and fieldsets have no default value, so they never count as edited.
  const dirty = (field) =>
    field.type === "file" ? field.files.length > 0
    : field.type === "checkbox" || field.type === "radio" ? field.checked !== field.defaultChecked
    : field.tagName !== "SELECT" ? "defaultValue" in field && field.value !== field.defaultValue
    : field.multiple ? [...field.options].some((option) => option.selected !== option.defaultSelected)
    : field.selectedIndex !== Math.max(0, [...field.options].findIndex((option) => option.defaultSelected));
  const inUse = (form) => form.contains(document.activeElement) || [...form.elements].some(dirty);
  // Never replace what the person is using: focus, a form they started, an open dialog or menu.
  const busy = (node) => {
    const element = node.nodeType === 1 ? node : node.parentElement;
    const form = element.closest("form");
    if (element.closest(guarded) !== null || (form !== null && inUse(form))) return true;
    return node.nodeType === 1 && (element.contains(document.activeElement) || element.querySelector(guarded) !== null
      || [...element.querySelectorAll("form")].some(inUse));
  };
  const sync = (live, next) => {
    live.getAttributeNames().forEach((name) => { if (!next.hasAttribute(name)) live.removeAttribute(name); });
    next.getAttributeNames().forEach((name) => {
      if (live.getAttribute(name) !== next.getAttribute(name)) live.setAttribute(name, next.getAttribute(name));
    });
  };
  const key = (node) => node.nodeType === 1 ? `${node.nodeName}#${node.id}.${node.getAttribute("class") || ""}` : `#${node.nodeType}`;
  const patch = (live, next) => {
    if (live.isEqualNode(next)) return;
    const free = !busy(live);
    const resized = live.childNodes.length !== next.childNodes.length;
    if (live.nodeType !== 1 || live.nodeName !== next.nodeName || (free && resized)) {
      if (free) live.replaceWith(next);
      return;
    }
    if (free) sync(live, next);
    if (resized) return merge(live, next);
    const kids = [...next.childNodes];
    [...live.childNodes].forEach((child, index) => patch(child, kids[index]));
  };
  // A panel that holds a booking form or a started form but gained or lost children: keep what is
  // in use, add the new children and drop stale ones, so a new action such as "Accept new time" appears.
  const merge = (live, next) => {
    let at = 0;
    for (const fresh of [...next.childNodes]) {
      const rest = [...live.childNodes].slice(at);
      const match = rest.findIndex((child) => key(child) === key(fresh));
      if (match === -1) {
        live.insertBefore(fresh, rest[0] ?? null);
        at++;
        continue;
      }
      rest.slice(0, match).forEach((stale) => { if (busy(stale)) at++; else stale.remove(); });
      patch(rest[match], fresh);
      at++;
    }
    [...live.childNodes].slice(at).forEach((stale) => { if (!busy(stale)) stale.remove(); });
  };
  let stopped = false;
  let running = false;
  const refresh = async () => {
    if (stopped || running || document.hidden) return;
    running = true;
    try {
      const response = await fetch(location.href, { cache: "no-store", credentials: "same-origin" });
      if (!response.ok || response.redirected || !response.headers.get("content-type")?.includes("text/html")) return;
      const fresh = new DOMParser().parseFromString(await response.text(), "text/html");
      if (stopped || !fresh.body.hasAttribute("data-refresh")) return;
      ["#main", ".sidebar-nav", ".tabbar"].forEach((selector) => {
        const live = document.querySelector(selector);
        const next = fresh.querySelector(selector);
        if (live && next) patch(live, next);
      });
    } catch {
      // Offline or mid-deploy: the next tick tries again.
    } finally {
      running = false;
    }
  };
  setInterval(refresh, 3000);
  document.addEventListener("visibilitychange", refresh);
  // A submitted form owns the next page, so stop fetching.
  document.addEventListener("submit", (event) => { if (!event.defaultPrevented) stopped = true; });
  addEventListener("pageshow", (event) => { if (event.persisted) { stopped = false; refresh(); } });
}
