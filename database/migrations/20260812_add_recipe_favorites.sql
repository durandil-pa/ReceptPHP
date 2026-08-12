-- Kör denna fil en gång i phpMyAdmin för att aktivera favoriter.
-- Den ändrar inte några befintliga recept eller användare.

CREATE TABLE IF NOT EXISTS recipe_favorites (
    user_id INT UNSIGNED NOT NULL,
    recipe_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, recipe_id),
    KEY recipe_favorites_recipe_id_index (recipe_id),
    CONSTRAINT recipe_favorites_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT recipe_favorites_recipe_id_foreign
        FOREIGN KEY (recipe_id) REFERENCES recipes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;
