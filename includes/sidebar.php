
        <aside class="todo-list" aria-labelledby="todo-list-title">
          <h2 id="todo-list-title">Todos</h2>

<p class="sidebar-description">
  Pilih ruang kerja untuk melihat daftar tugas
</p>

<nav class="sidebar-nav" aria-label="Todo navigation">
  <a href="#personal" id="menu-personal" class="active"
     aria-current="page" data-view="personal">
    <span>Personal</span>
    <span class="nav-count" id="personal-count">0</span>
  </a>

  <a href="#shared" id="menu-shared" data-view="shared">
    <span>Shared</span>
    <span class="nav-count" id="shared-count">0</span>
  </a>
</nav>

          <label for="todo-search">Search todo</label>
          <input
            type="search"
            id="todo-search"
            class="search-box"
            placeholder="Search todo..."
            autocomplete="off"
          />

          <p id="todo-status" class="sr-only" aria-live="polite"></p>

          <ul
            id="todo-ul"
            class="todo-list-items"
            aria-label="Todo items"
          ></ul>
        </aside>
