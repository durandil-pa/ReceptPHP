-- Kör denna fil en gång i phpMyAdmin för att aktivera personliga receptanteckningar.
-- Anteckningar är kopplade till användaren och ändrar inte själva recepten.

CREATE TABLE IF NOT EXISTS recipe_notes (
    user_id INT UNSIGNED NOT NULL,
    recipe_id INT UNSIGNED NOT NULL,
    note TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, recipe_id),
    KEY recipe_notes_recipe_id_index (recipe_id),
    CONSTRAINT recipe_notes_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT recipe_notes_recipe_id_foreign
        FOREIGN KEY (recipe_id) REFERENCES recipes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;
