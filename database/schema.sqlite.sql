CREATE TABLE IF NOT EXISTS interns (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  certificate_id TEXT UNIQUE,
  intern_level TEXT NOT NULL CHECK (intern_level IN ('college', 'shs')),
  full_name TEXT NOT NULL,
  gender TEXT NOT NULL CHECK (gender IN ('male', 'female')),
  school TEXT NOT NULL,
  course TEXT NULL,
  hours_rendered INTEGER NOT NULL,
  department TEXT NOT NULL,
  start_date TEXT NOT NULL,
  end_date TEXT NOT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
