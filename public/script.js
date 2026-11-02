const API = "api";

const ul_tgs = document.getElementById("todo-ul");
const frmTugas = document.getElementById("todo-form");
const inpt_cari = document.getElementById("todo-search");
const btn_tema = document.getElementById("theme-toggle");
const todoStatus = document.getElementById("todo-status");

const menuPersonal = document.getElementById("menu-personal");
const menuShared = document.getElementById("menu-shared");
const personalCount = document.getElementById("personal-count");
const sharedCount = document.getElementById("shared-count");

const visibilityInput = document.getElementById("todo-visibility");

let activeView = "personal";

const cameraStart = document.getElementById("camera-start");
const cameraCapture = document.getElementById("camera-capture");
const cameraStop = document.getElementById("camera-stop");
const cameraVideo = document.getElementById("camera-video");
const cameraCanvas = document.getElementById("camera-canvas");
const todoImagePreview = document.getElementById("todo-image-preview");
const cameraStatus = document.getElementById("camera-status");

let list_tgs = [];
let camera_stream = null;
let gambar_tgs = "";
let serviceWorkerRegistration = null;

function apiRequest(endpoint, options = {}) {
  return fetch(`${API}/${endpoint}.php`, {
    ...options,
    headers: {
      "Content-Type": "application/json",
      ...(options.headers || {}),
    },
  }).then(async (response) => {
    const result = await response.json();

    if (!response.ok || result.success === false) {
      throw new Error(result.message || "Permintaan gagal.");
    }

    return result;
  });
}

function normalizeTodo(todo) {
  return {
    id: Number(todo.id),
    title: todo.title || "",
    description: todo.description || "",
    date: todo.due_date || "",
    notificationTime: todo.notification_time
      ? todo.notification_time.replace(" ", "T").slice(0, 16)
      : "",
    image: todo.image_path || "",
    completed: Number(todo.is_completed) === 1 || todo.is_completed === true,
    visibility: todo.visibility || "personal",
  };
}

function format_tanggal(tgl) {
  if (!tgl) return "";

  const date = new Date(`${tgl}T00:00:00`);

  return Number.isNaN(date.getTime())
    ? tgl
    : date.toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
      });
}

function escapeText(value) {
  return String(value ?? "");
}

function render_tgs(kunci = "") {
  ul_tgs.innerHTML = "";

  const keyword = kunci.trim().toLowerCase();

  const personalTodos = list_tgs.filter(
    (todo) => todo.visibility === "personal",
  );

  const sharedTodos = list_tgs.filter((todo) => todo.visibility === "shared");

  personalCount.textContent = personalTodos.length;
  sharedCount.textContent = sharedTodos.length;

  const activeMenu = activeView === "personal" ? menuPersonal : menuShared;

  const inactiveMenu = activeView === "personal" ? menuShared : menuPersonal;

  activeMenu.classList.add("active");
  activeMenu.setAttribute("aria-current", "page");

  inactiveMenu.classList.remove("active");
  inactiveMenu.removeAttribute("aria-current");

  const filtered = list_tgs.filter((todo) => {
    const matchesView = todo.visibility === activeView;

    const matchesSearch =
      todo.title.toLowerCase().includes(keyword) ||
      todo.description.toLowerCase().includes(keyword);

    return matchesView && matchesSearch;
  });

  filtered.forEach((todo) => {
    const li = document.createElement("li");
    li.className = "todo-item";

    const checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.className = "todo-checkbox";
    checkbox.checked = todo.completed;
    checkbox.setAttribute("aria-label", `Tandai ${todo.title} selesai`);

    checkbox.addEventListener("change", async () => {
      try {
        await apiRequest("update", {
          method: "POST",
          body: JSON.stringify({
            id: todo.id,
            is_completed: checkbox.checked,
          }),
        });

        todo.completed = checkbox.checked;
        render_tgs(inpt_cari.value);
      } catch (error) {
        checkbox.checked = todo.completed;
        alert(error.message);
      }
    });

    const content = document.createElement("div");
    content.className = "todo-content";

    const title = document.createElement("span");
    title.className = `todo-text ${todo.completed ? "completed" : ""}`;
    title.textContent = todo.title;

    const date = document.createElement("span");
    date.className = "todo-date";
    date.textContent = `Deadline: ${format_tanggal(todo.date)}`;

    const categoryLabel = document.createElement("span");
    categoryLabel.className = "todo-description";
    categoryLabel.textContent =
      todo.visibility === "shared"
        ? "Ruang kerja: Shared"
        : "Ruang kerja: Personal";

    content.append(title, date, categoryLabel);

    if (todo.description) {
      const description = document.createElement("span");
      description.className = "todo-description";
      description.textContent = todo.description;
      content.appendChild(description);
    }

    if (todo.notificationTime) {
      const reminder = document.createElement("span");
      reminder.className = "todo-description";
      reminder.textContent = `Pengingat: ${todo.notificationTime.replace("T", " ")}`;
      content.appendChild(reminder);
    }

    if (todo.image) {
      const img = document.createElement("img");
      img.className = "todo-image";
      img.src = todo.image;
      img.alt = `Foto untuk ${todo.title}`;
      content.appendChild(img);
    }

    const actions = document.createElement("div");
    actions.className = "action-btns";

    const editButton = document.createElement("button");
    editButton.type = "button";
    editButton.className = "btn-edit";
    editButton.textContent = "Edit";
    editButton.setAttribute("aria-label", `Edit ${todo.title}`);
    editButton.addEventListener("click", () => edit_dt(todo.id));

    const deleteButton = document.createElement("button");
    deleteButton.type = "button";
    deleteButton.className = "btn-delete";
    deleteButton.textContent = "Delete";
    deleteButton.setAttribute("aria-label", `Hapus ${todo.title}`);
    deleteButton.addEventListener("click", () => hpsData(todo.id));

    actions.append(editButton, deleteButton);
    li.append(checkbox, content, actions);
    ul_tgs.appendChild(li);
  });

  if (filtered.length === 0) {
    const empty = document.createElement("li");
    empty.className = "todo-empty";

    empty.textContent = keyword
      ? "Tidak ada todo yang cocok dengan pencarian"
      : activeView === "personal"
        ? "Belum ada tugas personal!"
        : "Belum ada tugas shared";

    ul_tgs.appendChild(empty);
  }

  todoStatus.textContent = `${filtered.length} todo ditemukan di ruang ${activeView}`;
}

async function muat_todos() {
  todoStatus.textContent = "Memuat todo...";

  try {
    const result = await apiRequest("list");
    list_tgs = result.data.map(normalizeTodo);
    render_tgs(inpt_cari.value);

    list_tgs.forEach(jadwalkan_notifikasi);
  } catch (error) {
    list_tgs = [];
    render_tgs();
    todoStatus.textContent = `Gagal memuat todo: ${error.message}`;
  }
}

async function unggah_gambar(dataUrl) {
  if (!dataUrl) return null;

  const result = await apiRequest("upload", {
    method: "POST",
    body: JSON.stringify({ image: dataUrl }),
  });

  return result.data.image_path;
}

frmTugas.addEventListener("submit", async (event) => {
  event.preventDefault();

  const title = document.getElementById("todo-title").value.trim();
  const description = document.getElementById("todo-description").value.trim();
  const dueDate = document.getElementById("todo-due").value;
  const notificationInput = document.getElementById("todo-notification").value;
  const visibility = visibilityInput.value;

  if (!title || !dueDate) {
    frmTugas.reportValidity();
    return;
  }

  const submitButton = frmTugas.querySelector('button[type="submit"]');
  submitButton.disabled = true;

  try {
    cameraStatus.textContent = "Menyimpan todo...";

    let imagePath = null;

    if (gambar_tgs) {
      imagePath = await unggah_gambar(gambar_tgs);
    }

    const notificationTime = notificationInput
      ? `${notificationInput.replace("T", " ")}:00`
      : null;

    await apiRequest("add", {
      method: "POST",
      body: JSON.stringify({
        title,
        description: description || null,
        due_date: dueDate,
        notification_time: notificationTime,
        image_path: imagePath,
        visibility,
      }),
    });

    frmTugas.reset();
    gambar_tgs = "";
    todoImagePreview.src = "";
    todoImagePreview.hidden = true;

    cameraStatus.textContent = "Todo berhasil ditambahkan.";
    await muat_todos();
  } catch (error) {
    cameraStatus.textContent = `Gagal menyimpan todo: ${error.message}`;
    alert(error.message);
  } finally {
    submitButton.disabled = false;
  }
});

async function hpsData(id) {
  const todo = list_tgs.find((item) => item.id === id);
  if (!todo) return;

  if (!confirm(`Hapus todo "${todo.title}"?`)) return;

  try {
    await apiRequest("delete", {
      method: "POST",
      body: JSON.stringify({ id }),
    });

    await muat_todos();
  } catch (error) {
    alert(`Gagal menghapus todo: ${error.message}`);
  }
}

async function edit_dt(id) {
  const todo = list_tgs.find((item) => item.id === id);
  if (!todo) return;

  const newTitle = prompt("Edit judul tugas", todo.title);

  if (newTitle === null || newTitle.trim() === "") return;

  try {
    await apiRequest("update", {
      method: "POST",
      body: JSON.stringify({
        id,
        title: newTitle.trim(),
      }),
    });

    await muat_todos();
  } catch (error) {
    alert(`Gagal mengubah todo: ${error.message}`);
  }
}

inpt_cari.addEventListener("input", () => {
  render_tgs(inpt_cari.value);
});

menuPersonal.addEventListener("click", (event) => {
  event.preventDefault();

  activeView = "personal";
  visibilityInput.value = "personal";

  render_tgs(inpt_cari.value);
});

menuShared.addEventListener("click", (event) => {
  event.preventDefault();

  activeView = "shared";
  visibilityInput.value = "shared";

  render_tgs(inpt_cari.value);
});

btn_tema.addEventListener("click", () => {
  const dark = document.body.classList.toggle("dark-mode");

  localStorage.setItem("todo-theme", dark ? "dark" : "light");
  btn_tema.setAttribute("aria-pressed", String(dark));
});

function muat_tema() {
  const dark = localStorage.getItem("todo-theme") === "dark";

  document.body.classList.toggle("dark-mode", dark);
  btn_tema.setAttribute("aria-pressed", String(dark));
}

async function mulai_kamera() {
  if (!navigator.mediaDevices?.getUserMedia) {
    cameraStatus.textContent = "Media Capture API tidak didukung browser ini.";
    return;
  }

  try {
    camera_stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: "environment" },
      audio: false,
    });

    cameraVideo.srcObject = camera_stream;
    cameraVideo.hidden = false;
    cameraStart.disabled = true;
    cameraCapture.disabled = false;
    cameraStop.disabled = false;

    cameraStatus.textContent = "Kamera aktif. Silakan ambil gambar.";
  } catch (error) {
    cameraStatus.textContent =
      "Kamera tidak dapat digunakan. Periksa izin kamera browser.";
  }
}

function ambil_gambar() {
  if (!camera_stream) return;

  const width = cameraVideo.videoWidth;
  const height = cameraVideo.videoHeight;

  if (!width || !height) {
    cameraStatus.textContent = "Kamera belum siap.";
    return;
  }

  cameraCanvas.width = width;
  cameraCanvas.height = height;

  const context = cameraCanvas.getContext("2d");
  context.drawImage(cameraVideo, 0, 0, width, height);

  gambar_tgs = cameraCanvas.toDataURL("image/jpeg", 0.8);
  todoImagePreview.src = gambar_tgs;
  todoImagePreview.hidden = false;

  cameraStatus.textContent = "Gambar siap. Tekan Add Todo untuk menyimpan.";
}

function berhenti_kamera() {
  if (camera_stream) {
    camera_stream.getTracks().forEach((track) => track.stop());
    camera_stream = null;
  }

  cameraVideo.srcObject = null;
  cameraVideo.hidden = true;
  cameraStart.disabled = false;
  cameraCapture.disabled = true;
  cameraStop.disabled = true;
}

cameraStart.addEventListener("click", mulai_kamera);
cameraCapture.addEventListener("click", ambil_gambar);
cameraStop.addEventListener("click", () => {
  berhenti_kamera();
  cameraStatus.textContent = "Kamera dihentikan.";
});

async function minta_notifikasi() {
  if (!("Notification" in window)) return false;

  if (Notification.permission === "default") {
    await Notification.requestPermission();
  }

  return Notification.permission === "granted";
}

async function jadwalkan_notifikasi(todo) {
  if (!todo.notificationTime || todo.completed) return;

  const when = new Date(todo.notificationTime).getTime();
  const delay = when - Date.now();

  if (delay <= 0) return;

  if (!("Notification" in window)) return;

  // Service Worker akan mencoba menampilkan notifikasi pada waktu yang ditentukan.
  if (!serviceWorkerRegistration) return;

  if (Notification.permission !== "granted") return;

  serviceWorkerRegistration.active?.postMessage({
    type: "SCHEDULE_NOTIFICATION",
    todo,
    delay,
  });
}

async function daftar_service_worker() {
  if (!("serviceWorker" in navigator)) return;

  try {
    serviceWorkerRegistration = await navigator.serviceWorker.register("sw.js");

    await navigator.serviceWorker.ready;
    serviceWorkerRegistration = await navigator.serviceWorker.ready;

    if ("Notification" in window && Notification.permission === "default") {
      await minta_notifikasi();
    }

    list_tgs.forEach(jadwalkan_notifikasi);
  } catch (error) {
    console.error("Service Worker gagal didaftarkan:", error);
  }
}

async function mulai_app() {
  muat_tema();
  await daftar_service_worker();
  await muat_todos();
}

window.addEventListener("beforeunload", berhenti_kamera);

const catEl = document.getElementById("bouncing-cat");

if (catEl) {
  let posX = Math.random() * (window.innerWidth - 100);
  let posY = Math.random() * (window.innerHeight - 100);

  let speedX = 2.5;
  let speedY = 2;
  let facingRight = true;

  function animateCat() {
    posX += speedX;
    posY += speedY;

    const catWidth = catEl.clientWidth || 75;
    const catHeight = catEl.clientHeight || 75;

    if (posX <= 0) {
      posX = 0;
      speedX *= -1;
      facingRight = true;
    } else if (posX >= window.innerWidth - catWidth) {
      posX = window.innerWidth - catWidth;
      speedX *= -1;
      facingRight = false;
    }

    if (posY <= 0) {
      posY = 0;
      speedY *= -1;
    } else if (posY >= window.innerHeight - catHeight) {
      posY = window.innerHeight - catHeight;
      speedY *= -1;
    }
    catEl.style.left = `${posX}px`;
    catEl.style.top = `${posY}px`;
    catEl.style.transform = facingRight ? "scaleX(1)" : "scaleX(-1)";

    requestAnimationFrame(animateCat);
  }

  requestAnimationFrame(animateCat);
}

let deferredPrompt;

const installBtn = document.getElementById("installBtn");

window.addEventListener("beforeinstallprompt", (event) => {
  event.preventDefault();

  deferredPrompt = event;

  if (installBtn) {
    installBtn.style.display = "block";
  }
});

if (installBtn) {
  installBtn.addEventListener("click", async () => {
    if (!deferredPrompt) {
      return;
    }

    deferredPrompt.prompt();

    const { outcome } = await deferredPrompt.userChoice;

    console.log("Installation result:", outcome);

    deferredPrompt = null;

    installBtn.style.display = "none";
  });
}

window.addEventListener("appinstalled", () => {
  console.log("Todo App successfully installed!");

  if (installBtn) {
    installBtn.style.display = "none";
  }
});

mulai_app();
