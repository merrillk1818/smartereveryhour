import { useEffect, useRef, useState } from 'react';

export default function ChatPanel({ messages, sending, onSend }) {
    const [draft, setDraft] = useState('');
    const scrollRef = useRef(null);

    // Re-run after every render where `messages` changed — the dependency
    // array is what makes this "scroll to bottom on new message" instead of
    // "scroll to bottom on every render".
    useEffect(() => {
        if (scrollRef.current) {
            scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
        }
    }, [messages]);

    function handleSubmit(e) {
        e.preventDefault();
        const message = draft.trim();
        if (!message || sending) return;
        setDraft('');
        onSend(message);
    }

    return (
        <>
            <div className="chat-messages" ref={scrollRef}>
                {messages.map((msg, i) => (
                    <div key={i} className={`chat-message ${msg.role === 'user' ? 'user-msg' : 'ai-msg'}`}>
                        <div className="msg-bubble">{msg.content}</div>
                    </div>
                ))}
            </div>

            <div className="chat-input-area">
                <form className="chat-form" onSubmit={handleSubmit}>
                    <input
                        type="text"
                        placeholder="Ask a follow-up question..."
                        autoComplete="off"
                        maxLength={1000}
                        value={draft}
                        onChange={(e) => setDraft(e.target.value)}
                        disabled={sending}
                    />
                    <button type="submit" className="btn btn-primary" disabled={sending}>
                        {sending ? '...' : 'Send'}
                    </button>
                </form>
            </div>
        </>
    );
}
