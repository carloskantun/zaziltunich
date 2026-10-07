-- Categorías del blog (ES/EN, con jerarquía). Seguro de ejecutar más de una vez.
CREATE TABLE IF NOT EXISTS categories (
  id {{PK}},
  slug VARCHAR(190) NOT NULL UNIQUE,
  parent_id INT UNSIGNED NULL,
  sort_order INT NOT NULL DEFAULT 0
){{ENGINE}};

CREATE TABLE IF NOT EXISTS category_translations (
  id {{PK}},
  category_id INT UNSIGNED NOT NULL,
  lang CHAR(2) NOT NULL,
  name VARCHAR(190) NOT NULL DEFAULT '',
  UNIQUE (category_id, lang),
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
){{ENGINE}};

CREATE TABLE IF NOT EXISTS post_categories (
  post_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, category_id),
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
){{ENGINE}};
