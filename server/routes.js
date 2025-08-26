const express = require('express');
const bcrypt = require('bcryptjs');
const { v4: uuidv4 } = require('uuid');
const dayjs = require('dayjs');
const db = require('./db');

const router = express.Router();

function requireAuth(req, res, next) {
	if (!req.session.user) return res.redirect('/login');
	next();
}

router.get('/', (req, res) => {
	res.render('index', { title: 'Dashboard' });
});

router.get('/login', (req, res) => {
	res.render('users/login', { title: 'Login' });
});

router.post('/login', (req, res) => {
	const { email, password } = req.body;
	const user = db.prepare('SELECT * FROM users WHERE email = ?').get(email);
	if (!user) return res.status(401).render('users/login', { title: 'Login' });
	const ok = bcrypt.compareSync(password, user.password_hash);
	if (!ok) return res.status(401).render('users/login', { title: 'Login' });
	req.session.user = { id: user.id, name: user.name, role: user.role };
	res.redirect('/');
});

router.post('/logout', (req, res) => {
	req.session.destroy(() => res.redirect('/login'));
});

// Rooms
router.get('/rooms', requireAuth, (req, res) => {
	const rooms = db.prepare('SELECT * FROM rooms ORDER BY number').all();
	res.render('rooms/list', { title: 'Rooms', rooms });
});

router.get('/rooms/new', requireAuth, (req, res) => {
	res.render('rooms/new', { title: 'New Room' });
});

router.post('/rooms', requireAuth, (req, res) => {
	const { number, type, rate, description } = req.body;
	const id = uuidv4();
	const created_at = new Date().toISOString();
	try {
		db.prepare(`INSERT INTO rooms (id, number, type, rate, status, description, created_at)
				VALUES (@id, @number, @type, @rate, 'available', @description, @created_at)`).run({ id, number, type, rate: Number(rate) || 0, description, created_at });
		res.redirect('/rooms');
	} catch (e) {
		console.error(e);
		res.status(400).render('rooms/new', { title: 'New Room' });
	}
});

// Guests
router.get('/guests', requireAuth, (req, res) => {
	const guests = db.prepare('SELECT * FROM guests ORDER BY created_at DESC').all();
	res.render('guests/list', { title: 'Guests', guests });
});

// Reservations - list only placeholder for now
router.get('/reservations', requireAuth, (req, res) => {
	const reservations = db.prepare(`SELECT r.*, rm.number as room_number, g.first_name || ' ' || g.last_name as guest_name
		FROM reservations r
		JOIN rooms rm ON rm.id = r.room_id
		JOIN guests g ON g.id = r.guest_id
		ORDER BY r.created_at DESC`).all();
	res.render('reservations/list', { title: 'Reservations', reservations });
});

// Utility: check room availability for a given date range
function isRoomAvailable(roomId, checkInISO, checkOutISO) {
	const row = db.prepare(`SELECT COUNT(1) as cnt FROM reservations 
		WHERE room_id = ? AND status IN ('booked','checked_in')
		AND NOT (check_out <= ? OR check_in >= ?)`)
		.get(roomId, checkInISO, checkOutISO);
	return row.cnt === 0;
}

// Rooms CRUD
router.get('/rooms/:id/edit', requireAuth, (req, res) => {
	const room = db.prepare('SELECT * FROM rooms WHERE id = ?').get(req.params.id);
	if (!room) return res.redirect('/rooms');
	res.render('rooms/edit', { title: 'Edit Room', room });
});

router.put('/rooms/:id', requireAuth, (req, res) => {
	const { number, type, rate, description, status } = req.body;
	try {
		db.prepare(`UPDATE rooms SET number=@number, type=@type, rate=@rate, description=@description, status=@status WHERE id=@id`)
			.run({ id: req.params.id, number, type, rate: Number(rate) || 0, description, status: status || 'available' });
		res.redirect('/rooms');
	} catch (e) {
		console.error(e);
		res.redirect('/rooms');
	}
});

router.delete('/rooms/:id', requireAuth, (req, res) => {
	try {
		db.prepare('DELETE FROM rooms WHERE id = ?').run(req.params.id);
	} catch (e) {
		console.error(e);
	}
	res.redirect('/rooms');
});

// Guests CRUD
router.get('/guests/new', requireAuth, (req, res) => {
	res.render('guests/new', { title: 'New Guest' });
});

router.post('/guests', requireAuth, (req, res) => {
	const { first_name, last_name, email, phone } = req.body;
	const id = uuidv4();
	const created_at = new Date().toISOString();
	db.prepare(`INSERT INTO guests (id, first_name, last_name, email, phone, created_at)
		VALUES (@id, @first_name, @last_name, @email, @phone, @created_at)`).run({ id, first_name, last_name, email, phone, created_at });
	res.redirect('/guests');
});

router.get('/guests/:id/edit', requireAuth, (req, res) => {
	const guest = db.prepare('SELECT * FROM guests WHERE id = ?').get(req.params.id);
	if (!guest) return res.redirect('/guests');
	res.render('guests/edit', { title: 'Edit Guest', guest });
});

router.put('/guests/:id', requireAuth, (req, res) => {
	const { first_name, last_name, email, phone } = req.body;
	db.prepare(`UPDATE guests SET first_name=@first_name, last_name=@last_name, email=@email, phone=@phone WHERE id=@id`)
		.run({ id: req.params.id, first_name, last_name, email, phone });
	res.redirect('/guests');
});

router.delete('/guests/:id', requireAuth, (req, res) => {
	try { db.prepare('DELETE FROM guests WHERE id = ?').run(req.params.id); } catch (e) { console.error(e); }
	res.redirect('/guests');
});

// Reservations
router.get('/reservations/new', requireAuth, (req, res) => {
	const rooms = db.prepare('SELECT * FROM rooms ORDER BY number').all();
	const guests = db.prepare('SELECT * FROM guests ORDER BY last_name, first_name').all();
	res.render('reservations/new', { title: 'New Reservation', rooms, guests });
});

router.post('/reservations', requireAuth, (req, res) => {
	const { room_id, guest_id, check_in, check_out } = req.body;
	const checkInISO = dayjs(check_in).startOf('day').toISOString();
	const checkOutISO = dayjs(check_out).startOf('day').toISOString();
	if (!isRoomAvailable(room_id, checkInISO, checkOutISO)) {
		const rooms = db.prepare('SELECT * FROM rooms ORDER BY number').all();
		const guests = db.prepare('SELECT * FROM guests ORDER BY last_name, first_name').all();
		return res.status(400).render('reservations/new', { title: 'New Reservation', rooms, guests });
	}
	const id = uuidv4();
	const created_at = new Date().toISOString();
	const room = db.prepare('SELECT * FROM rooms WHERE id = ?').get(room_id);
	const nights = Math.max(1, dayjs(checkOutISO).diff(dayjs(checkInISO), 'day'));
	const total_amount = Number((nights * (room?.rate || 0)).toFixed(2));
	db.prepare(`INSERT INTO reservations (id, room_id, guest_id, check_in, check_out, status, total_amount, created_at)
		VALUES (@id, @room_id, @guest_id, @check_in, @check_out, 'booked', @total_amount, @created_at)`).run({
		id, room_id, guest_id, check_in: checkInISO, check_out: checkOutISO, total_amount, created_at
	});
	res.redirect('/reservations');
});

router.post('/reservations/:id/checkin', requireAuth, (req, res) => {
	db.prepare(`UPDATE reservations SET status='checked_in' WHERE id = ?`).run(req.params.id);
	res.redirect('/reservations');
});

router.post('/reservations/:id/checkout', requireAuth, (req, res) => {
	db.prepare(`UPDATE reservations SET status='checked_out' WHERE id = ?`).run(req.params.id);
	res.redirect('/reservations');
});

router.delete('/reservations/:id', requireAuth, (req, res) => {
	db.prepare(`UPDATE reservations SET status='cancelled' WHERE id = ?`).run(req.params.id);
	res.redirect('/reservations');
});

// Housekeeping
router.get('/housekeeping/new', requireAuth, (req, res) => {
	const rooms = db.prepare('SELECT * FROM rooms ORDER BY number').all();
	res.render('housekeeping/new', { title: 'New Task', rooms });
});

router.post('/housekeeping', requireAuth, (req, res) => {
	const { room_id, title, description } = req.body;
	const id = uuidv4();
	db.prepare(`INSERT INTO housekeeping_tasks (id, room_id, title, description, status, created_at)
		VALUES (@id, @room_id, @title, @description, 'pending', @created_at)`).run({ id, room_id, title, description, created_at: new Date().toISOString() });
	res.redirect('/housekeeping');
});

router.post('/housekeeping/:id/status', requireAuth, (req, res) => {
	const { status } = req.body;
	db.prepare(`UPDATE housekeeping_tasks SET status=@status WHERE id=@id`).run({ id: req.params.id, status });
	res.redirect('/housekeeping');
});

router.get('/housekeeping', requireAuth, (req, res) => {
	const tasks = db.prepare(`SELECT t.*, rm.number as room_number FROM housekeeping_tasks t JOIN rooms rm ON rm.id = t.room_id ORDER BY t.created_at DESC`).all();
	res.render('housekeeping/list', { title: 'Housekeeping', tasks });
});

// Invoices & Payments
router.get('/invoices', requireAuth, (req, res) => {
	const invoices = db.prepare(`SELECT i.*, r.check_in, r.check_out, rm.number as room_number, g.first_name || ' ' || g.last_name as guest_name
		FROM invoices i
		JOIN reservations r ON r.id = i.reservation_id
		JOIN rooms rm ON rm.id = r.room_id
		JOIN guests g ON g.id = r.guest_id
		ORDER BY i.created_at DESC`).all();
	res.render('invoices/list', { title: 'Invoices', invoices });
});

router.post('/invoices/create', requireAuth, (req, res) => {
	const { reservation_id } = req.body;
	const existing = db.prepare('SELECT * FROM invoices WHERE reservation_id = ?').get(reservation_id);
	if (!existing) {
		const reservation = db.prepare('SELECT * FROM reservations WHERE id = ?').get(reservation_id);
		if (reservation) {
			const id = uuidv4();
			db.prepare(`INSERT INTO invoices (id, reservation_id, issue_date, total, status, created_at)
				VALUES (@id, @reservation_id, @issue_date, @total, 'unpaid', @created_at)`).run({
				id,
				reservation_id,
				issue_date: new Date().toISOString(),
				total: reservation.total_amount,
				created_at: new Date().toISOString(),
			});
		}
	}
	res.redirect('/invoices');
});

router.get('/invoices/:id', requireAuth, (req, res) => {
	const invoice = db.prepare('SELECT * FROM invoices WHERE id = ?').get(req.params.id);
	if (!invoice) return res.redirect('/invoices');
	const reservation = db.prepare(`SELECT r.*, rm.number as room_number, g.first_name || ' ' || g.last_name as guest_name
		FROM reservations r
		JOIN rooms rm ON rm.id = r.room_id
		JOIN guests g ON g.id = r.guest_id WHERE r.id = ?`).get(invoice.reservation_id);
	const payments = db.prepare('SELECT * FROM payments WHERE invoice_id = ? ORDER BY paid_at DESC').all(invoice.id);
	const paidTotal = payments.reduce((s,p)=>s + p.amount, 0);
	res.render('invoices/view', { title: 'Invoice', invoice, reservation, payments, paidTotal });
});

router.post('/invoices/:id/payments', requireAuth, (req, res) => {
	const { amount, method } = req.body;
	const id = uuidv4();
	const invoice = db.prepare('SELECT * FROM invoices WHERE id = ?').get(req.params.id);
	if (!invoice) return res.redirect('/invoices');
	db.prepare(`INSERT INTO payments (id, invoice_id, amount, method, paid_at, created_at)
		VALUES (@id, @invoice_id, @amount, @method, @paid_at, @created_at)`).run({
		id,
		invoice_id: req.params.id,
		amount: Number(amount) || 0,
		method: method || 'cash',
		paid_at: new Date().toISOString(),
		created_at: new Date().toISOString(),
	});
	const paid = db.prepare('SELECT COALESCE(SUM(amount),0) as total FROM payments WHERE invoice_id = ?').get(req.params.id).total;
	const status = paid >= invoice.total ? 'paid' : 'unpaid';
	db.prepare('UPDATE invoices SET status = ? WHERE id = ?').run(status, req.params.id);
	res.redirect(`/invoices/${req.params.id}`);
});

// Reports
router.get('/reports', requireAuth, (req, res) => {
	const { from, to } = req.query;
	let occupancy = null;
	let revenue = null;
	if (from && to) {
		const fromISO = dayjs(from).startOf('day').toISOString();
		const toISO = dayjs(to).startOf('day').toISOString();
		const roomNights = db.prepare(`SELECT SUM(
			(CASE WHEN check_out < @toISO THEN julianday(check_out) ELSE julianday(@toISO) END)
			- (CASE WHEN check_in > @fromISO THEN julianday(check_in) ELSE julianday(@fromISO) END)
		) as nights FROM reservations WHERE status IN ('booked','checked_in','checked_out') AND NOT (check_out <= @fromISO OR check_in >= @toISO)`)
			.get({ fromISO, toISO }).nights || 0;
		const roomCount = db.prepare('SELECT COUNT(1) as cnt FROM rooms').get().cnt || 1;
		const totalNights = roomCount * Math.max(1, dayjs(toISO).diff(dayjs(fromISO), 'day'));
		occupancy = totalNights ? Math.round((roomNights / totalNights) * 100) : 0;
		revenue = db.prepare(`SELECT COALESCE(SUM(amount),0) as rev FROM payments WHERE paid_at >= ? AND paid_at < ?`).get(fromISO, toISO).rev || 0;
	}
	res.render('reports/index', { title: 'Reports', occupancy, revenue, from, to });
});

module.exports = router;