const path = require('path');
const fs = require('fs');
const Database = require('better-sqlite3');
const bcrypt = require('bcryptjs');

const dataDir = path.join(__dirname, 'data');
if (!fs.existsSync(dataDir)) {
	fs.mkdirSync(dataDir, { recursive: true });
}

const dbPath = path.join(dataDir, 'hotel.sqlite');
const db = new Database(dbPath);

db.pragma('journal_mode = WAL');

db.exec(`
CREATE TABLE IF NOT EXISTS users (
	id TEXT PRIMARY KEY,
	name TEXT NOT NULL,
	email TEXT UNIQUE NOT NULL,
	password_hash TEXT NOT NULL,
	role TEXT NOT NULL CHECK(role IN ('admin','staff')),
	created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS rooms (
	id TEXT PRIMARY KEY,
	number TEXT UNIQUE NOT NULL,
	type TEXT NOT NULL,
	status TEXT NOT NULL CHECK(status IN ('available','occupied','maintenance','cleaning')) DEFAULT 'available',
	rate REAL NOT NULL DEFAULT 0,
	description TEXT,
	created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS guests (
	id TEXT PRIMARY KEY,
	first_name TEXT NOT NULL,
	last_name TEXT NOT NULL,
	email TEXT,
	phone TEXT,
	created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS reservations (
	id TEXT PRIMARY KEY,
	room_id TEXT NOT NULL,
	guest_id TEXT NOT NULL,
	check_in TEXT NOT NULL,
	check_out TEXT NOT NULL,
	status TEXT NOT NULL CHECK(status IN ('booked','checked_in','checked_out','cancelled')) DEFAULT 'booked',
	total_amount REAL NOT NULL DEFAULT 0,
	created_at TEXT NOT NULL,
	FOREIGN KEY(room_id) REFERENCES rooms(id),
	FOREIGN KEY(guest_id) REFERENCES guests(id)
);

CREATE TABLE IF NOT EXISTS housekeeping_tasks (
	id TEXT PRIMARY KEY,
	room_id TEXT NOT NULL,
	title TEXT NOT NULL,
	description TEXT,
	status TEXT NOT NULL CHECK(status IN ('pending','in_progress','done')) DEFAULT 'pending',
	created_at TEXT NOT NULL,
	FOREIGN KEY(room_id) REFERENCES rooms(id)
);

CREATE TABLE IF NOT EXISTS invoices (
	id TEXT PRIMARY KEY,
	reservation_id TEXT NOT NULL,
	issue_date TEXT NOT NULL,
	total REAL NOT NULL,
	status TEXT NOT NULL CHECK(status IN ('unpaid','paid','void')) DEFAULT 'unpaid',
	created_at TEXT NOT NULL,
	FOREIGN KEY(reservation_id) REFERENCES reservations(id)
);

CREATE TABLE IF NOT EXISTS payments (
	id TEXT PRIMARY KEY,
	invoice_id TEXT NOT NULL,
	amount REAL NOT NULL,
	method TEXT NOT NULL,
	paid_at TEXT NOT NULL,
	created_at TEXT NOT NULL,
	FOREIGN KEY(invoice_id) REFERENCES invoices(id)
);
`);

function seedAdmin() {
	const row = db.prepare('SELECT COUNT(1) as count FROM users').get();
	if (row.count === 0) {
		const id = require('uuid').v4();
		const passwordHash = bcrypt.hashSync('admin123', 10);
		db.prepare(`INSERT INTO users (id, name, email, password_hash, role, created_at)
				  VALUES (@id, @name, @email, @password_hash, @role, @created_at)`).run({
			id,
			name: 'Admin',
			email: 'admin@example.com',
			password_hash: passwordHash,
			role: 'admin',
			created_at: new Date().toISOString(),
		});
		console.log('Seeded admin user: admin@example.com / admin123');
	}
}

seedAdmin();

module.exports = db;