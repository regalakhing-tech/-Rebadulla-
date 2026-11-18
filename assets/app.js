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

document.querySelectorAll("form").forEach((form) => {
  form.addEventListener("submit", async (event) => {
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
  document.querySelectorAll("[data-pet-dialog-open]").forEach((link) =>
    link.addEventListener("click", (event) => {
      if (!addMode()) return; // An edit form is loaded: reload a blank one.
      event.preventDefault();
      petDialog.showModal();
    }),
  );
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
