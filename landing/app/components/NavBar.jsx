'use client';

import { useState } from 'react';

// This file needs "use client" because it holds interactive state (the
// mobile menu toggle). Everything else on the page (page.jsx) has no state
// or event handlers, so it stays a Server Component by default — rendered
// to HTML on the server with zero JS shipped to the browser for it.
export default function NavBar() {
    const [open, setOpen] = useState(false);

    return (
        <nav className="navbar">
            <a href="/" className="navbar-brand">
                <span className="brand-icon">⚡</span> Smarter Every Hour
            </a>
            <button className="navbar-menu-btn" aria-label="Menu" onClick={() => setOpen((v) => !v)}>
                ☰
            </button>
            <div className={`navbar-links ${open ? 'open' : ''}`}>
                <a href="/login">Sign in</a>
                <a href="/register" className="btn btn-primary btn-sm">Get started</a>
            </div>
        </nav>
    );
}
