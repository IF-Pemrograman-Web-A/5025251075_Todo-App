let list_tgs = [];

const ul_tgs = document.getElementById("todo-ul");
const frmTugas = document.getElementById("todo-form");
const inpt_cari = document.getElementById("todo-search");
const btn_tema = document.getElementById("theme-toggle");
const todoStatus = document.getElementById("todo-status");
const cameraStart = document.getElementById("camera-start");
const cameraCapture = document.getElementById("camera-capture");
const cameraStop = document.getElementById("camera-stop");
const cameraVideo = document.getElementById("camera-video");
const cameraCanvas = document.getElementById("camera-canvas");
const todoImagePreview = document.getElementById("todo-image-preview");
const cameraStatus = document.getElementById("camera-status");

let db_tgs;
let camera_stream = null;
let gambar_tgs = "";

const DB_NAME = "TodoAppDB";
const DB_VERSION = 1;
const STORE_NAME = "todos";

function buka_db() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);

    request.onupgradeneeded = (e) => {
      const db = e.target.result;

      if (!db.objectStoreNames.contains(STORE_NAME)) {
        db.createObjectStore(STORE_NAME, {
          keyPath: "id",
        });
      }
    };

    request.onsuccess = (e) => {
      db_tgs = e.target.result;
      resolve(db_tgs);
    };

    request.onerror = () => {
      reject(request.error);
    };
  });
}

function simpan_semua() {
  return new Promise((resolve, reject) => {
    const transaksi = db_tgs.transaction(STORE_NAME, "readwrite");
    const store = transaksi.objectStore(STORE_NAME);

    store.clear();

    list_tgs.forEach((tgs) => {
      store.put(tgs);
    });

    transaksi.oncomplete = () => {
      resolve();
    };

    transaksi.onerror = () => {
      reject(transaksi.error);
    };
  });
}

function ambil_semua() {
  return new Promise((resolve, reject) => {
    const transaksi = db_tgs.transaction(STORE_NAME, "readonly");
    const store = transaksi.objectStore(STORE_NAME);
    const request = store.getAll();

    request.onsuccess = () => {
      resolve(request.result);
    };

    request.onerror = () => {
      reject(request.error);
    };
  });
}

function format_tanggal(tgl) {
  if (!tgl) return "";

  const fmt_wkt = new Date(`${tgl}T00:00:00`);

  return isNaN(fmt_wkt.getTime())
    ? tgl
    : fmt_wkt.toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
      });
}

function render_tgs(kunci = "") {
  ul_tgs.innerHTML = "";

  const hsl_cari = list_tgs.filter((tgs) =>
    tgs.title.toLowerCase().includes(kunci.toLowerCase()),
  );

  hsl_cari.forEach((tgs) => {
    const liItem = document.createElement("li");
    liItem.className = "todo-item";

    const cbk = document.createElement("input");
    cbk.type = "checkbox";
    cbk.className = "todo-checkbox";
    cbk.checked = tgs.completed;
    cbk.setAttribute("aria-label", `Mark ${tgs.title} as completed`);
    cbk.addEventListener("change", () => ubah_stat(tgs.id));

    const divContent = document.createElement("div");
    divContent.className = "todo-content";

    const txt_jdl = document.createElement("span");
    txt_jdl.className = `todo-text ${tgs.completed ? "completed" : ""}`;
    txt_jdl.textContent = tgs.title;

    const txtTgl = document.createElement("span");
    txtTgl.className = "todo-date";
    txtTgl.textContent = format_tanggal(tgs.date);

    divContent.appendChild(txt_jdl);
    divContent.appendChild(txtTgl);

    if (tgs.description) {
      const txtDeskripsi = document.createElement("span");
      txtDeskripsi.className = "todo-description";
      txtDeskripsi.textContent = tgs.description;
      divContent.appendChild(txtDeskripsi);
    }

    const grpBtn = document.createElement("div");
    grpBtn.className = "action-btns";

    const btnEdit = document.createElement("button");
    btnEdit.className = "btn-edit";
    btnEdit.type = "button";
    btnEdit.textContent = "Edit";
    btnEdit.setAttribute("aria-label", `Edit ${tgs.title}`);
    btnEdit.addEventListener("click", () => edit_dt(tgs.id));

    const btnHapus = document.createElement("button");
    btnHapus.className = "btn-delete";
    btnHapus.type = "button";
    btnHapus.textContent = "Delete";
    btnHapus.setAttribute("aria-label", `Delete ${tgs.title}`);
    btnHapus.addEventListener("click", () => hpsData(tgs.id));

    grpBtn.appendChild(btnEdit);
    grpBtn.appendChild(btnHapus);

    liItem.appendChild(cbk);
    liItem.appendChild(divContent);

    if (tgs.image) {
      const imgTgs = document.createElement("img");
      imgTgs.className = "todo-image";
      imgTgs.src = tgs.image;
      imgTgs.alt = `Image for ${tgs.title}`;
      liItem.appendChild(imgTgs);
    }

    liItem.appendChild(grpBtn);
    ul_tgs.appendChild(liItem);
  });

  if (hsl_cari.length === 0) {
    const liKosong = document.createElement("li");
    liKosong.className = "todo-item";
    liKosong.textContent = kunci ? "Todo tidak ditemukan." : "Belum ada todo.";
    ul_tgs.appendChild(liKosong);
  }

  todoStatus.textContent = `${hsl_cari.length} todo ditemukan.`;
}

frmTugas.addEventListener("submit", async function (e) {
  e.preventDefault();

  const inptJdl = document.getElementById("todo-title").value.trim();
  const inptDeskripsi = document
    .getElementById("todo-description")
    .value.trim();
  const inptTgl = document.getElementById("todo-due").value;
  const inptNotifikasi = document.getElementById("todo-notification").value;

  if (inptJdl === "" || inptTgl === "") {
    frmTugas.reportValidity();
    return;
  }

  const tgsBaru = {
    id: Date.now(),
    title: inptJdl,
    description: inptDeskripsi,
    date: inptTgl,
    notificationTime: inptNotifikasi,
    image: gambar_tgs,
    completed: false,
  };

  list_tgs.push(tgsBaru);

  await simpan_semua();

  if (inptNotifikasi) {
    jadwalkan_notifikasi(tgsBaru);
  }

  frmTugas.reset();
  gambar_tgs = "";
  todoImagePreview.src = "";
  todoImagePreview.hidden = true;

  render_tgs();

  cameraStatus.textContent = "Todo berhasil ditambahkan.";
});

async function hpsData(id_tgt) {
  const dtLama = list_tgs.find((tgs) => tgs.id === id_tgt);

  if (!dtLama) return;

  const yakin = confirm(`Hapus todo "${dtLama.title}"?`);

  if (!yakin) return;

  list_tgs = list_tgs.filter((tgs) => tgs.id !== id_tgt);

  await simpan_semua();

  render_tgs();
}

async function edit_dt(id_tgt) {
  const dtLama = list_tgs.find((tgs) => tgs.id === id_tgt);

  if (!dtLama) return;

  const txtBaru = prompt("Edit judul tugas", dtLama.title);

  if (txtBaru !== null && txtBaru.trim() !== "") {
    dtLama.title = txtBaru.trim();

    await simpan_semua();

    render_tgs();
  }
}

async function ubah_stat(id_tgt) {
  const dtPilih = list_tgs.find((tgs) => tgs.id === id_tgt);

  if (dtPilih) {
    dtPilih.completed = !dtPilih.completed;

    await simpan_semua();

    render_tgs();
  }
}

inpt_cari.addEventListener("input", (e) => {
  render_tgs(e.target.value);
});

btn_tema.addEventListener("click", () => {
  const aktif = document.body.classList.toggle("dark-mode");

  localStorage.setItem("todo-theme", aktif ? "dark" : "light");

  btn_tema.setAttribute("aria-pressed", aktif ? "true" : "false");
});

function muat_tema() {
  const tema = localStorage.getItem("todo-theme");

  if (tema === "dark") {
    document.body.classList.add("dark-mode");
    btn_tema.setAttribute("aria-pressed", "true");
  } else {
    document.body.classList.remove("dark-mode");
    btn_tema.setAttribute("aria-pressed", "false");
  }
}

async function mulai_kamera() {
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    cameraStatus.textContent = "Media Capture API tidak didukung browser ini.";
    return;
  }

  try {
    camera_stream = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: "environment",
      },
      audio: false,
    });

    cameraVideo.srcObject = camera_stream;
    cameraVideo.hidden = false;

    cameraStart.disabled = true;
    cameraCapture.disabled = false;
    cameraStop.disabled = false;

    cameraStatus.textContent = "Kamera aktif. Silakan ambil gambar.";
  } catch (err) {
    cameraStatus.textContent =
      "Kamera tidak dapat digunakan. Pastikan izin kamera diberikan.";
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

  cameraStatus.textContent = "Gambar berhasil diambil.";
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

  cameraStatus.textContent = "Kamera dihentikan.";
}

cameraStart.addEventListener("click", mulai_kamera);

cameraCapture.addEventListener("click", ambil_gambar);

cameraStop.addEventListener("click", berhenti_kamera);

async function minta_notifikasi() {
  if (!("Notification" in window)) return;

  if (Notification.permission === "default") {
    await Notification.requestPermission();
  }
}

async function jadwalkan_notifikasi(tgs) {
  if (!tgs.notificationTime) return;

  const waktuNotifikasi = new Date(tgs.notificationTime).getTime();
  const waktuSekarang = Date.now();
  const selisih = waktuNotifikasi - waktuSekarang;

  if (selisih <= 0) return;

  if (navigator.serviceWorker && navigator.serviceWorker.controller) {
    navigator.serviceWorker.controller.postMessage({
      type: "SCHEDULE_NOTIFICATION",
      todo: tgs,
      delay: selisih,
    });
  }
}

async function daftar_service_worker() {
  if (!("serviceWorker" in navigator)) return;

  try {
    const registration = await navigator.serviceWorker.register("sw.js");

    await navigator.serviceWorker.ready;

    if ("Notification" in window && Notification.permission === "default") {
      await minta_notifikasi();
    }

    list_tgs.forEach((tgs) => {
      jadwalkan_notifikasi(tgs);
    });

    return registration;
  } catch (err) {
    console.error(err);
  }
}

async function mulai_app() {
  try {
    await buka_db();

    const dataDB = await ambil_semua();

    if (dataDB.length === 0) {
      list_tgs = [
        {
          id: 1,
          title: "Ambil baju di laundry",
          description: "",
          date: "2026-09-10",
          notificationTime: "",
          image: "",
          completed: false,
        },
        {
          id: 2,
          title: "Jalan jalan pagi",
          description: "",
          date: "2026-09-13",
          notificationTime: "",
          image: "",
          completed: false,
        },
        {
          id: 3,
          title: "Selesaikan tugas",
          description: "",
          date: "2026-09-13",
          notificationTime: "",
          image: "",
          completed: true,
        },
        {
          id: 4,
          title: "Belanja kebutuhan",
          description: "",
          date: "2026-09-12",
          notificationTime: "",
          image: "",
          completed: false,
        },
      ];

      await simpan_semua();
    } else {
      list_tgs = dataDB;
    }

    muat_tema();
    render_tgs();
    daftar_service_worker();
  } catch (err) {
    todoStatus.textContent = "Gagal memuat data todo.";
  }
}

window.addEventListener("beforeunload", () => {
  berhenti_kamera();
});

mulai_app();
