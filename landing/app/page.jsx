import NavBar from './components/NavBar';

// No "use client" here — this is a Server Component. Next.js renders it to
// static HTML at request time (or build time, since nothing here depends on
// per-request data) and sends zero JavaScript to the browser for it. Only
// <NavBar /> below ships its own small client bundle, for its one bit of
// interactivity (the mobile menu).
export default function Home() {
    return (
        <>
            <NavBar />

            <section className="hero">
                <div className="hero-content">
                    <h1>Learn anything.<br />One message at a time.</h1>
                    <p className="hero-sub">
                        Build a curriculum of topics you want to master. Get short, AI-generated
                        lessons sent as push notifications throughout your day. Tap to explore
                        deeper — no app download needed.
                    </p>
                    <div className="hero-actions">
                        <a href="/register" className="btn btn-primary btn-lg">Start learning free</a>
                        <a href="/login" className="btn btn-ghost btn-lg">Sign in</a>
                    </div>
                </div>
                <div className="hero-visual">
                    <div className="phone-mockup">
                        <div className="notification-card">
                            <div className="notif-header">
                                <span className="notif-icon">⚡</span>
                                <span className="notif-app">Smarter Every Hour</span>
                            </div>
                            <div className="notif-body">
                                A CPU executes billions of simple math operations per second — that
                                speed is what makes modern software feel instant.
                            </div>
                            <div className="notif-action">Tap to learn more ›</div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="features">
                <div className="feature">
                    <div className="feature-icon">📚</div>
                    <h3>Build your curriculum</h3>
                    <p>Organize any subject into topics. From AWS certifications to history, cooking techniques to programming.</p>
                </div>
                <div className="feature">
                    <div className="feature-icon">⏳</div>
                    <h3>Set your schedule</h3>
                    <p>Choose your active hours and how often you want reminders. Once an hour, every 30 minutes — you decide.</p>
                </div>
                <div className="feature">
                    <div className="feature-icon">🤖</div>
                    <h3>AI-powered micro-lessons</h3>
                    <p>Each notification delivers one focused insight about a topic, then lets you ask follow-up questions in a live chat.</p>
                </div>
            </section>
        </>
    );
}
