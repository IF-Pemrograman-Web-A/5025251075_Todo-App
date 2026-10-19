
        <section class="todo-detail" aria-labelledby="todo-detail-title">
          <h2 id="todo-detail-title">Todo Details</h2>

          <form id="todo-form">
            <label for="todo-title">Title:</label>
            <input
              type="text"
              id="todo-title"
              name="todo-title"
              placeholder="Enter todo title"
              required
              autocomplete="off"
            />

            <label for="todo-description">Description:</label>
            <textarea
              id="todo-description"
              name="todo-description"
              placeholder="Enter todo description"
            ></textarea>

            <label for="todo-due">Due Date:</label>
            <input
              type="date"
              id="todo-due"
              name="todo-due"
              required
            />

            <label for="todo-notification">Notification Time:</label>
            <input
              type="datetime-local"
              id="todo-notification"
              name="todo-notification"
            />

            <label for="todo-visibility">Ruang Kerja</label>

<select id="todo-visibility" name="todo-visibility">
  <option value="personal">Personal</option>
  <option value="shared">Shared</option>
</select>

            <fieldset class="camera-section">
              <legend>Todo Image</legend>

              <div class="camera-controls">
                <button
                  type="button"
                  id="camera-start"
                  class="camera-btn"
                >
                  Open Camera
                </button>

                <button
                  type="button"
                  id="camera-capture"
                  class="camera-btn"
                  disabled
                >
                  Capture Image
                </button>

                <button
                  type="button"
                  id="camera-stop"
                  class="camera-btn"
                  disabled
                >
                  Stop Camera
                </button>
              </div>

              <video
                id="camera-video"
                class="camera-video"
                autoplay
                playsinline
                muted
                aria-label="Camera preview"
                hidden
              ></video>

              <canvas
                id="camera-canvas"
                class="camera-canvas"
                hidden
              ></canvas>

              <img
                id="todo-image-preview"
                class="todo-image-preview"
                alt="Preview of captured todo image"
                hidden
              />

              <p
                id="camera-status"
                class="camera-status"
                aria-live="polite"
              ></p>
            </fieldset>

            <button type="submit">Add Todo</button>
          </form>
        </section>
