-- Contenido: páginas y blog (ES/EN). Seguro de ejecutar más de una vez.
CREATE TABLE IF NOT EXISTS pages (
  id {{PK}},
  slug VARCHAR(190) NOT NULL UNIQUE,
  status VARCHAR(12) NOT NULL DEFAULT 'published',
  dark TINYINT NOT NULL DEFAULT 1,
  hero_image VARCHAR(255) NULL,
  show_in_nav TINYINT NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL
){{ENGINE}};

CREATE TABLE IF NOT EXISTS page_translations (
  id {{PK}},
  page_id INT UNSIGNED NOT NULL,
  lang CHAR(2) NOT NULL,
  title VARCHAR(190) NOT NULL DEFAULT '',
  nav_label VARCHAR(80) NOT NULL DEFAULT '',
  content MEDIUMTEXT NULL,
  seo_title VARCHAR(190) NOT NULL DEFAULT '',
  seo_description VARCHAR(300) NOT NULL DEFAULT '',
  UNIQUE (page_id, lang),
  FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
){{ENGINE}};

CREATE TABLE IF NOT EXISTS posts (
  id {{PK}},
  slug VARCHAR(190) NOT NULL UNIQUE,
  status VARCHAR(12) NOT NULL DEFAULT 'published',
  image VARCHAR(255) NULL,
  published_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
){{ENGINE}};

CREATE TABLE IF NOT EXISTS post_translations (
  id {{PK}},
  post_id INT UNSIGNED NOT NULL,
  lang CHAR(2) NOT NULL,
  title VARCHAR(255) NOT NULL DEFAULT '',
  excerpt TEXT NULL,
  content MEDIUMTEXT NULL,
  seo_title VARCHAR(190) NOT NULL DEFAULT '',
  seo_description VARCHAR(300) NOT NULL DEFAULT '',
  UNIQUE (post_id, lang),
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
){{ENGINE}};
