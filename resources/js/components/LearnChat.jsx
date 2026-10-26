import { useState } from 'react';
import ChatPanel from './ChatPanel';
import SaveTopicPanel from './SaveTopicPanel';

export default function LearnChat({ token, csrfToken, initialMessages, curricula, defaultCurriculumId, defaultSkillLevel }) {
    const [messages, setMessages] = useState(initialMessages);
    const [sending, setSending] = useState(false);

    function appendMessage(role, content) {
        setMessages((prev) => [...prev, { role, content }]);
    }

    async function handleSend(message) {
        appendMessage('user', message);
        setSending(true);
        try {
            const res = await fetch(`/learn/${token}/chat`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ message }),
            });
            const data = await res.json();
            appendMessage('assistant', res.ok ? data.response : (data.error || 'Something went wrong.'));
        } catch {
            appendMessage('assistant', 'Network error. Please check your connection and try again.');
        } finally {
            setSending(false);
        }
    }

    async function handleSuggest() {
        const res = await fetch(`/learn/${token}/suggest-topic`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
        });
        const data = await res.json();
        return data.suggestion ?? null;
    }

    async function handleSave({ title, description, curriculumId, skillLevel }) {
        try {
            const res = await fetch(`/learn/${token}/save-topic`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ title, description, curriculum_id: curriculumId, skill_level: skillLevel }),
            });
            const data = await res.json();
            return res.ok
                ? { ok: true, text: `"${data.topic}" saved to ${data.curriculum}!` }
                : { ok: false, text: data.error ?? 'Something went wrong.' };
        } catch {
            return { ok: false, text: 'Request failed.' };
        }
    }

    return (
        <>
            <ChatPanel messages={messages} sending={sending} onSend={handleSend} />
            {curricula.length > 0 && (
                <SaveTopicPanel
                    curricula={curricula}
                    defaultCurriculumId={defaultCurriculumId}
                    defaultSkillLevel={defaultSkillLevel}
                    onSuggest={handleSuggest}
                    onSave={handleSave}
                />
            )}
        </>
    );
}
