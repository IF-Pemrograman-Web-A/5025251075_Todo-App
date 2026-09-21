let list_tgs = [
  {
    id: 1,
    title: "Ambil baju di laundry",
    date: "2026-09-10",
    completed: false,
  },
  { id: 2, title: "Jalan jalan pagi", date: "2026-09-13", completed: false },
  { id: 3, title: "Selesaikan tugas", date: "2026-09-13", completed: true },
  { id: 4, title: "Belanja kebutuhan", date: "2026-09-12", completed: false },
];

const ul_tgs = document.getElementById("todo-ul");
const frmTugas = document.getElementById("todo-form");
const inpt_cari = document.getElementById("todo-search");
const btn_tema = document.getElementById("theme-toggle");

function render_tgs(kunci = "") {
  ul_tgs.innerHTML = "";

  const hsl_cari = list_tgs.filter((tgs) =>
    tgs.title.toLowerCase().includes(kunci.toLowerCase()),
  );

  hsl_cari.forEach((tgs) => {
    const liItem = document.createElement("li");

    const cbk = document.createElement("input");
    cbk.type = "checkbox";
    cbk.className = "todo-checkbox";
    cbk.checked = tgs.completed;
    cbk.addEventListener("change", () => ubah_stat(tgs.id));

    const txt_jdl = document.createElement("span");
    txt_jdl.className = `todo-text ${tgs.completed ? "completed" : ""}`;
    txt_jdl.textContent = tgs.title;

    const txtTgl = document.createElement("span");
    txtTgl.className = "todo-date";
    const fmt_wkt = new Date(tgs.date);
    txtTgl.textContent = isNaN(fmt_wkt)
      ? tgs.date
      : fmt_wkt.toLocaleDateString("en-US", {
          month: "short",
          day: "numeric",
          year: "numeric",
        });

    const grpBtn = document.createElement("div");
    grpBtn.className = "action-btns";

    const btnEdit = document.createElement("button");
    btnEdit.className = "btn-edit";
    btnEdit.textContent = "Edit";
    btnEdit.addEventListener("click", () => edit_dt(tgs.id));

    const btnHapus = document.createElement("button");
    btnHapus.className = "btn-delete";
    btnHapus.textContent = "Delete";
    btnHapus.addEventListener("click", () => hpsData(tgs.id));

    grpBtn.appendChild(btnEdit);
    grpBtn.appendChild(btnHapus);

    liItem.appendChild(cbk);
    liItem.appendChild(txt_jdl);
    liItem.appendChild(txtTgl);
    liItem.appendChild(grpBtn);

    ul_tgs.appendChild(liItem);
  });
}

frmTugas.addEventListener("submit", function (e) {
  e.preventDefault();

  const inptJdl = document.getElementById("todo-title").value;
  const inptTgl = document.getElementById("todo-due").value;

  if (inptJdl === "" || inptTgl === "") return;

  const tgsBaru = {
    id: Date.now(),
    title: inptJdl,
    date: inptTgl,
    completed: false,
  };

  list_tgs.push(tgsBaru);
  frmTugas.reset();
  render_tgs();
});

function hpsData(id_tgt) {
  list_tgs = list_tgs.filter((tgs) => tgs.id !== id_tgt);
  render_tgs();
}

function edit_dt(id_tgt) {
  const dtLama = list_tgs.find((tgs) => tgs.id === id_tgt);
  if (!dtLama) return;

  const txtBaru = prompt("Edit judul tugas", dtLama.title);
  if (txtBaru !== null && txtBaru.trim() !== "") {
    dtLama.title = txtBaru.trim();
    render_tgs();
  }
}

function ubah_stat(id_tgt) {
  const dtPilih = list_tgs.find((tgs) => tgs.id === id_tgt);
  if (dtPilih) {
    dtPilih.completed = !dtPilih.completed;
    render_tgs();
  }
}

inpt_cari.addEventListener("input", (e) => {
  render_tgs(e.target.value);
});

btn_tema.addEventListener("click", () => {
  document.body.classList.toggle("dark-mode");
});

render_tgs();
