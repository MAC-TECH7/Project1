const path = require('path');
const express = require('express');
const session = require('express-session');
const SQLiteStore = require('connect-sqlite3')(session);
const methodOverride = require('method-override');
const morgan = require('morgan');
const helmet = require('helmet');
const bodyParser = require('body-parser');

const app = express();
const PORT = process.env.PORT || 3000;

// Security headers
app.use(helmet({
	contentSecurityPolicy: false,
}));

// Logging
app.use(morgan('dev'));

// Body parsing
app.use(bodyParser.urlencoded({ extended: true }));
app.use(bodyParser.json());

// Method override for forms
app.use(methodOverride('_method'));

// Sessions
app.use(session({
	store: new SQLiteStore({ db: 'sessions.sqlite', dir: path.join(__dirname, 'data') }),
	secret: process.env.SESSION_SECRET || 'dev_secret_change_me',
	resave: false,
	saveUninitialized: false,
	cookie: { maxAge: 1000 * 60 * 60 * 4 },
}));

// Static files
app.use(express.static(path.join(__dirname, 'public')));

// View engine
app.set('views', path.join(__dirname, 'views'));
app.set('view engine', 'ejs');

// Load DB (ensures schema/seed)
require('./db');

// Routes
const router = require('./routes');
app.use(router);

// Expose session to templates
app.use((req, res, next) => {
	res.locals.session = req.session;
	next();
});

// Placeholder auth middleware
function requireAuth(req, res, next) {
	if (!req.session.user) {
		return res.redirect('/login');
	}
	next();
}

// 404 handler
app.use((req, res) => {
	res.status(404).render('404', { title: 'Not Found' });
});

// Error handler
app.use((err, req, res, next) => {
	console.error(err);
	res.status(500).render('500', { title: 'Server Error' });
});

app.listen(PORT, () => {
	console.log(`Server running on http://localhost:${PORT}`);
});