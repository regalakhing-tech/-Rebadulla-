// Gallery page: photo filters and full-size viewer. Without JavaScript each photo opens as a link.
const photos = [...document.querySelectorAll("[data-photo]")];
const filters = [...document.querySelectorAll("[data-filter]")];
const count = document.querySelector("[data-count]");
const viewer = document.querySelector("[data-viewer]");
const viewerImage = document.querySelector("[data-viewer-image]");
const viewerCaption = document.querySelector("[data-viewer-caption]");
const viewerPosition = document.querySelector("[data-viewer-position]");
let shown = photos;
let current = 0;

filters.forEach((button) =>
  button.addEventListener("click", () => {
    const group = button.dataset.filter;
    filters.forEach((item) => item.setAttribute("aria-pressed", String(item === button)));
    shown = photos.filter((photo) => group === "all" || photo.dataset.group === group);
    photos.forEach((photo) => {
      photo.hidden = !shown.includes(photo);
    });
    count.textContent = `${shown.length} ${shown.length === 1 ? "photo" : "photos"}`;
  }),
);

function showPhoto(index) {
  current = (index + shown.length) % shown.length;
  const thumbnail = shown[current].querySelector("img");
  viewerImage.sizes = "(max-width: 720px) 100vw, 80vw";
  viewerImage.srcset = thumbnail.srcset;
  viewerImage.src = thumbnail.src;
  viewerImage.alt = thumbnail.alt;
  viewerCaption.textContent = thumbnail.alt;
  viewerPosition.textContent = `${current + 1} of ${shown.length}`;
}

photos.forEach((photo) =>
  photo.addEventListener("click", (event) => {
    if (event.ctrlKey || event.metaKey || event.shiftKey) return;
    event.preventDefault();
    showPhoto(shown.indexOf(photo));
    viewer.showModal();
  }),
);
document.querySelector("[data-viewer-prev]").addEventListener("click", () => showPhoto(current - 1));
document.querySelector("[data-viewer-next]").addEventListener("click", () => showPhoto(current + 1));
document.querySelector("[data-viewer-close]").addEventListener("click", () => viewer.close());
viewer.addEventListener("click", (event) => {
  if (event.target === viewer) viewer.close();
});
viewer.addEventListener("keydown", (event) => {
  if (event.key === "ArrowLeft") showPhoto(current - 1);
  if (event.key === "ArrowRight") showPhoto(current + 1);
});
