-- Esquema Zazil Tunich. Los marcadores {{PK}} y {{ENGINE}} se adaptan a MySQL/MariaDB o SQLite.
-- Reglas: cada sentencia termina en ";" al final de línea; sin funciones propias de un motor.

CREATE TABLE users (
  id {{PK}},
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(12) NOT NULL DEFAULT 'operator',
  active TINYINT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL
){{ENGINE}};

CREATE TABLE capacity_groups (
  id {{PK}},
  name VARCHAR(120) NOT NULL,
  capacity INT NOT NULL DEFAULT 20
){{ENGINE}};

CREATE TABLE experiences (
  id {{PK}},
  slug VARCHAR(190) NOT NULL UNIQUE,
  kind VARCHAR(12) NOT NULL DEFAULT 'slot',
  status VARCHAR(12) NOT NULL DEFAULT 'draft',
  pricing_model VARCHAR(16) NOT NULL DEFAULT 'per_person',
  base_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  currency CHAR(3) NOT NULL DEFAULT 'MXN',
  min_pax INT NOT NULL DEFAULT 1,
  max_pax INT NOT NULL DEFAULT 20,
  lead_hours INT NOT NULL DEFAULT 12,
  max_advance_days INT NOT NULL DEFAULT 365,
  payment_mode VARCHAR(8) NOT NULL DEFAULT 'full',
  deposit_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
  capacity_group_id INT UNSIGNED NULL,
  default_capacity INT NOT NULL DEFAULT 20,
  min_nights INT NOT NULL DEFAULT 1,
  checkin_time VARCHAR(5) NULL,
  checkout_time VARCHAR(5) NULL,
  hero_image VARCHAR(255) NULL,
  gallery TEXT NULL,
  show_contact TINYINT NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (capacity_group_id) REFERENCES capacity_groups(id)
){{ENGINE}};

CREATE TABLE experience_translations (
  id {{PK}},
  experience_id INT UNSIGNED NOT NULL,
  lang CHAR(2) NOT NULL,
  title VARCHAR(190) NOT NULL DEFAULT '',
  subtitle VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT NULL,
  meeting_point VARCHAR(255) NOT NULL DEFAULT '',
  highlights TEXT NULL,
  includes TEXT NULL,
  excludes TEXT NULL,
  bring TEXT NULL,
  itinerary TEXT NULL,
  faq TEXT NULL,
  seo_title VARCHAR(190) NOT NULL DEFAULT '',
  seo_description VARCHAR(300) NOT NULL DEFAULT '',
  UNIQUE (experience_id, lang),
  FOREIGN KEY (experience_id) REFERENCES experiences(id) ON DELETE CASCADE
){{ENGINE}};

CREATE TABLE price_tiers (
  id {{PK}},
  experience_id INT UNSIGNED NOT NULL,
  min_pax INT NOT NULL,
  max_pax INT NULL,
  price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (experience_id) REFERENCES experiences(id) ON DELETE CASCADE
){{ENGINE}};

CREATE TABLE price_overrides (
  id {{PK}},
  experience_id INT UNSIGNED NOT NULL,
  label VARCHAR(120) NOT NULL DEFAULT '',
  date_from DATE NOT NULL,
  date_to DATE NOT NULL,
  time VARCHAR(5) NULL,
  weekdays VARCHAR(20) NULL,
  price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (experience_id) REFERENCES experiences(id) ON DELETE CASCADE
){{ENGINE}};

CREATE TABLE variants (
  id {{PK}},
  experience_id INT UNSIGNED NOT NULL,
  name_es VARCHAR(160) NOT NULL,
  name_en VARCHAR(160) NOT NULL DEFAULT '',
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  FOREIGN KEY (experience_id) REFERENCES experiences(id) ON DELETE CASCADE
){{ENGINE}};

CREATE TABLE schedule_templates (
  id {{PK}},
  name VARCHAR(120) NOT NULL
){{ENGINE}};

CREATE TABLE schedule_template_times (
  id {{PK}},
  template_id INT UNSIGNED NOT NULL,
  time VARCHAR(5) NOT NULL,
  FOREIGN KEY (template_id) REFERENCES schedule_templates(id) ON DELETE CASCADE
){{ENGINE}};

CREATE TABLE experience_schedules (
  id {{PK}},
  experience_id INT UNSIGNED NOT NULL,
  template_id INT UNSIGNED NOT NULL,
  weekdays VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5,6,7',
  valid_from DATE NULL,
  valid_to DATE NULL,
  capacity INT NULL,
  FOREIGN KEY (experience_id) REFERENCES experiences(id) ON DELETE CASCADE,
  FOREIGN KEY (template_id) REFERENCES schedule_templates(id)
){{ENGINE}};

CREATE TABLE event_dates (
  id {{PK}},
  experience_id INT UNSIGNED NOT NULL,
  event_date DATE NOT NULL,
  time VARCHAR(5) NOT NULL DEFAULT '19:00',
  capacity INT NOT NULL DEFAULT 10,
  FOREIGN KEY (experience_id) REFERENCES experiences(id) ON DELETE CASCADE
){{ENGINE}};

CREATE TABLE blocks (
  id {{PK}},
  experience_id INT UNSIGNED NULL,
  capacity_group_id INT UNSIGNED NULL,
  date_from DATE NOT NULL,
  date_to DATE NOT NULL,
  time VARCHAR(5) NULL,
  reason VARCHAR(190) NOT NULL DEFAULT '',
  FOREIGN KEY (experience_id) REFERENCES experiences(id) ON DELETE CASCADE,
  FOREIGN KEY (capacity_group_id) REFERENCES capacity_groups(id) ON DELETE CASCADE
){{ENGINE}};

CREATE TABLE extra_groups (
  id {{PK}},
  name_es VARCHAR(160) NOT NULL,
  name_en VARCHAR(160) NOT NULL DEFAULT '',
  help_es VARCHAR(255) NOT NULL DEFAULT '',
  help_en VARCHAR(255) NOT NULL DEFAULT '',
  selection VARCHAR(10) NOT NULL DEFAULT 'list',
  required TINYINT NOT NULL DEFAULT 0,
  mode VARCHAR(8) NOT NULL DEFAULT 'manual',
  sort_order INT NOT NULL DEFAULT 0
){{ENGINE}};

CREATE TABLE extra_options (
  id {{PK}},
  group_id INT UNSIGNED NOT NULL,
  label_es VARCHAR(160) NOT NULL,
  label_en VARCHAR(160) NOT NULL DEFAULT '',
  charge VARCHAR(12) NOT NULL DEFAULT 'fixed',
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  pax_min INT NULL,
  pax_max INT NULL,
  max_qty INT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  FOREIGN KEY (group_id) REFERENCES extra_groups(id) ON DELETE CASCADE
){{ENGINE}};

CREATE TABLE experience_extra_groups (
  id {{PK}},
  experience_id INT UNSIGNED NOT NULL,
  group_id INT UNSIGNED NOT NULL,
  required_override TINYINT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  UNIQUE (experience_id, group_id),
  FOREIGN KEY (experience_id) REFERENCES experiences(id) ON DELETE CASCADE,
  FOREIGN KEY (group_id) REFERENCES extra_groups(id) ON DELETE CASCADE
){{ENGINE}};

CREATE TABLE customers (
  id {{PK}},
  name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NOT NULL DEFAULT '',
  lang CHAR(2) NOT NULL DEFAULT 'es',
  created_at DATETIME NOT NULL
){{ENGINE}};

CREATE TABLE bookings (
  id {{PK}},
  code VARCHAR(20) NOT NULL UNIQUE,
  experience_id INT UNSIGNED NOT NULL,
  variant_id INT UNSIGNED NULL,
  customer_id INT UNSIGNED NOT NULL,
  date DATE NOT NULL,
  time VARCHAR(5) NULL,
  end_date DATE NULL,
  nights INT NULL,
  pax INT NOT NULL DEFAULT 1,
  units INT NOT NULL DEFAULT 1,
  currency CHAR(3) NOT NULL DEFAULT 'MXN',
  base_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  extras_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  deposit_due DECIMAL(10,2) NOT NULL DEFAULT 0,
  paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  status VARCHAR(14) NOT NULL DEFAULT 'pending',
  source VARCHAR(8) NOT NULL DEFAULT 'web',
  lang CHAR(2) NOT NULL DEFAULT 'es',
  notes TEXT NULL,
  pricing_snapshot TEXT NULL,
  hold_expires_at DATETIME NULL,
  balance_due_date DATE NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (experience_id) REFERENCES experiences(id),
  FOREIGN KEY (customer_id) REFERENCES customers(id)
){{ENGINE}};

CREATE TABLE booking_extras (
  id {{PK}},
  booking_id INT UNSIGNED NOT NULL,
  group_id INT UNSIGNED NULL,
  option_id INT UNSIGNED NULL,
  label VARCHAR(255) NOT NULL,
  qty INT NOT NULL DEFAULT 1,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
){{ENGINE}};

CREATE TABLE payments (
  id {{PK}},
  booking_id INT UNSIGNED NOT NULL,
  gateway VARCHAR(20) NOT NULL DEFAULT 'manual',
  method VARCHAR(40) NOT NULL DEFAULT '',
  reference VARCHAR(190) NOT NULL DEFAULT '',
  kind VARCHAR(10) NOT NULL DEFAULT 'full',
  amount DECIMAL(10,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'MXN',
  status VARCHAR(12) NOT NULL DEFAULT 'succeeded',
  payload TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
){{ENGINE}};

CREATE TABLE settings (
  name VARCHAR(100) NOT NULL PRIMARY KEY,
  value TEXT NULL
){{ENGINE}};

CREATE INDEX idx_exp_status ON experiences(status, sort_order);
CREATE INDEX idx_bookings_date ON bookings(date, experience_id);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_bookings_customer ON bookings(customer_id);
CREATE INDEX idx_payments_booking ON payments(booking_id);
CREATE INDEX idx_blocks_dates ON blocks(date_from, date_to);
CREATE INDEX idx_event_dates ON event_dates(experience_id, event_date);
CREATE INDEX idx_customers_email ON customers(email);
